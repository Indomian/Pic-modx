<?php
/**
 * Сниппет выполняет ресайз и отрисовку картинок по переданным параметрам
 * принимаемые параметры:
 * src - адрес изображения на сервере
 * width - ширина
 * height - высота
 * mode - режим ресайза
 * default - изображение по умолчанию
 * type - вид вывода - png или jpg
 * Пример вызова:
 * [[Pic?src=`/[[+tv.image]]`&width=132&height=132&mode=crop]]
 * @author Blade39 <blade39@yandex.ru>
 *
 * @var array $scriptProperties
 */
if(!class_exists('Pic')) {
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

//Фикс тупой фигни с неабсолютными адресами в модх
if(substr($params['src'],0,1)!='/') {
	$params['src']='/'.$params['src'];
}

if(!isset($params['mode'])) {
	$params['mode']='scale';
} elseif(!in_array($params['mode'],array('crop','croptop','scale','stretch','fields','maxField','hexagon'))) {
	throw new Exception('WRONG RESIZE MODE');
}
$obPic=new Pic($modx,$params);
$obPic->init();
return $obPic->Resize();