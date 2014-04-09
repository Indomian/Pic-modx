<?php
/**
 * Сниппет выполняет ресайз и отрисовку картинок по переданным параметрам
 * принимаемые параметры:
 * src - адрес изображения на сервере
 * width - ширина
 * height - высота
 * mode - режим ресайза
 * default - изображение по умолчанию
 * Пример вызова:
 * [[Pic?src=`/[[+tv.image]]`&width=132&height=132&mode=crop]]
 * @author Blade39 <blade39@yandex.ru>
 */
if(!class_exists('CImageResizer')) {
	$modelPath = $modx->getOption('core_path').'components/pic/model/';
	include_once $modelPath.'pic.class.php';
}
$params=$scriptProperties;
//Совместимость с параметрами phpThumbOf
if(isset($params['input']) && isset($params['options'])) {
	$arParams=explode('&',$params['options']);
	unset($params['options']);
	unset($params['name']);
	unset($params['tag']);
	if(count($arParams)>0) {
		foreach ($arParams as $key => $value) {
			$arRow=explode('=',$value);
			$params[array_shift($arRow)]=join('=',$arRow);
		}
	}
}
if(isset($params['input'])) {
	$params['src']=$params['input'];
	unset($params['input']);
}
if(!isset($params['src']) || $params['src']=='')
	return '';
if(isset($params['w'])) {
	$params['width']=$params['w'];
	unset($params['w']);
}
if(isset($params['h'])) {
	$params['height']=$params['h'];
	unset($params['h']);
}
if(isset($params['zc'])) {
	if($params['zc']==0) $params['mode']='stretch';
	if($params['zc']==1) $params['mode']='fields';
	if($params['zc']==2) $params['mode']='crop';
	unset($params['zc']);
}
if(isset($params['bg'])) {
	$params['bgcolor']=$params['bg'];
	unset($params['bg']);
}
if(!isset($params['width']))
	$params['width']='';
if(!isset($params['height']))
	$params['height']='';
$attributes=array('src','mode','default','bgcolor','link','tag','input','output','token','wm');
//Фикс тупой фигни с неабсолютными адресами в модх
if(substr($params['src'],0,1)!='/') {
	$params['src']='/'.$params['src'];
}
if(!isset($params['mode'])) {
	$params['mode']='scale';
} elseif(!in_array($params['mode'],array('crop','croptop','scale','stretch','fields'))) {
	throw new Exception('WRONG RESIZE MODE');
}
$params['width']=intval($params['width']);
$params['height']=intval($params['height']);
if(file_exists(PIC_CACHE_SITE_ROOT.$params['src']) && is_file(PIC_CACHE_SITE_ROOT.$params['src'])) {
	$sSizeFile='';
	if($params['width']!='') {
		$sSizeFile.=$params['width'];
	}
	$sSizeFile.='x';
	if($params['height']!='') {
		$sSizeFile.=$params['height'];
	}
	$sSizeFile.='-'.$params['mode'];
	if(isset($params['fPosition'])) {
		$sSizeFile.='-'.$params['fPosition'];
	}
	$cacheDir=PIC_CACHE_PATH.$params['src'].'/';
	$cacheFile=PIC_CACHE_PATH_URL.$params['src'].'/'.$sSizeFile.'.jpeg';
	$cachePath=PIC_CACHE_PATH.$params['src'].'/'.$sSizeFile.'.jpeg';
	if(!file_exists($cachePath)) {
		//Такой файл не был закеширован, значит надо его создавать
		try {
			$obImage=new CImageResizer(PIC_CACHE_SITE_ROOT.$params['src']);
			if(isset($params['wm'])) {
				if(file_exists(PIC_CACHE_SITE_ROOT.$params['wm']) && is_file(PIC_CACHE_SITE_ROOT.$params['wm'])) {
					//Watermark
					if(isset($params['wm_pos']) && isset($params['wm_padding']))
						$obWM=new CWatermarkImageGenerator(PIC_CACHE_SITE_ROOT.$params['wm'],$params['wm_pos'],$params['wm_padding']);
					elseif(isset($params['wm_pos']))
						$obWM=new CWatermarkImageGenerator(PIC_CACHE_SITE_ROOT.$params['wm'],$params['wm_pos']);
					else
						$obWM=new CWatermarkImageGenerator(PIC_CACHE_SITE_ROOT.$params['wm']);
					$obImage=new CImageResizerWaterMark($obImage,$obWM);
				}
			}
			if($params['width']=='' && $params['height']=='') {
				//$obMode=new CScaleCopy(0,0);
				return 'Size should be set';
			} else {
				switch($params['mode']) {
					case 'stretch':
						$obMode=new CRectGenerator($params['width'],$params['height']);
					break;
					case 'crop':
						$obMode=new CCropToCenter($params['width'],$params['height']);
					break;
					case 'croptop':
						$obMode=new CCropToTop($params['width'],$params['height']);
					break;
					case 'fields':
						$obMode=new CFieldsCrop($params['width'],$params['height']);
						if(isset($params['fPosition'])) {
							$obMode->setPosition($params['fPosition']);
						}
						if(isset($params['bgcolor']))
							$obImage->SetBackgroundColor($params['bgcolor']);
						else
							$obImage->SetBackgroundColor(0,0,0);
					break;
					default:
						$obMode=new CScale($params['width'],$params['height']);
				}
			}
			if($obImage->Resize($obMode)) {
				if(!file_exists($cacheDir))
					if(!@mkdir($cacheDir,0755,true)) return '';
				if($obImage->Save($cachePath))
					chmod($cachePath,0655);
				else
					throw new Exception('SYSTEM_CANT_SAVE');
			} else
				throw new Exception('SYSTEM_CANT_RESIZE');
		} catch (Exception $e) {
			$cacheFile=$params['src'];
		}
	}
} elseif($params['default']!='')
	$cacheFile=$params['default'];
elseif(defined(PIC_CACHE_DEFAULT_IMAGE) && PIC_CACHE_DEFAULT_IMAGE!='')
	$cacheFile=PIC_CACHE_DEFAULT_IMAGE;
else
	$cacheFile='';
if(isset($params['link']) && $params['link']==1) 
	return $cacheFile;
if($cacheFile!='') {
	$res='<img src="'.$cacheFile.'"';
	foreach($params as $key=>$value)
		if(!in_array($key,$attributes))
			$res.=' '.$key.'="'.$value.'"';
	$res.='/>';
	return $res;
}
return '';