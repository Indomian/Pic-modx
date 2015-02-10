<?php
/**
 *
 */

define('PIC_CACHE_SITE_ROOT',MODX_BASE_PATH);
define('PIC_CACHE_PATH',PIC_CACHE_SITE_ROOT.'/assets/cache/Pic');
define('PIC_CACHE_PATH_URL','/assets/cache/Pic');
define('PIC_CACHE_DEFAULT_IMAGE','');
define('PIC_MAX_MEMORY',268435456); //Максимум используемой памяти, если лимит не указан

include_once MODX_CORE_PATH.'components/pic/model/classes/CWatermarkGenerator.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CWatermarkImageGenerator.php';

include_once MODX_CORE_PATH.'components/pic/model/classes/CRectGenerator.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CScale.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CScaleCopy.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CCropToTop.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CCropToCenter.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CFieldsCrop.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CMaxField.php';

include_once MODX_CORE_PATH.'components/pic/model/classes/CImageResizer.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CImageResizerHex.php';
include_once MODX_CORE_PATH.'components/pic/model/classes/CImageResizerWatermark.php';

class Pic {
	const CLASS_VERSION=1;
	public $sSiteRoot;
	public $sCacheUrl;
	public $sDefaultImg;

	private $_bInitialized=false;
	private $sCachePath;
	private $arSelfAttributes=array(
		'src','mode','default','bgcolor','link','tag',
		'input','output','token','wm','angle','wm_pos',
		'wm_padding','border','borderColor','type','fPosition',
		'quality'
	);

	private $modx;
	private $scriptProperties;

	public function __construct($modx,$scriptProperties) {
		$this->modx=$modx;
		$this->scriptProperties=$scriptProperties;
	}

	public function init() {
		if($this->sSiteRoot=='') $this->sSiteRoot=PIC_CACHE_SITE_ROOT;
		if($this->sCacheUrl=='') $this->sCacheUrl=PIC_CACHE_PATH_URL;
		$this->sCachePath=$this->sSiteRoot.$this->sCacheUrl;
		$this->_bInitialized=true;
	}

	/**
	 * @return boolean whether the {@link init()} method has been invoked.
	 */
	public function getIsInitialized() {
		return $this->_bInitialized;
	}

	private function getHash($arParams) {
		$attributes=$this->arSelfAttributes;
		$attributes[]='width';
		$attributes[]='height';
		return md5(join('|',array_intersect_key($arParams,array_flip($attributes))).'='.self::CLASS_VERSION);
	}

	private function getParams() {
		$arParams=$this->scriptProperties;
		if(!isset($arParams['src'])) {
			throw new Exception('No image src set');
		}
		if(!isset($arParams['width'])) {
			$arParams['width']='';
		}
		if(!isset($arParams['height'])) {
			$arParams['height']='';
		}
		if(!isset($arParams['type']) || !in_array($arParams['type'],array('png','jpg'))) {
			$arParams['type']='jpg';
		}
		if(!isset($arParams['quality'])) {
			$arParams['quality']=90;
		}
		if($arParams['width']=='' || $arParams['height']=='') {
			unset($arParams['mode']);
		}
		if(!isset($arParams['mode'])) {
			$arParams['mode']='';
		} else {
			if($arParams['mode']=='hexagon') {
				$arParams['type']='png';
			}
		}
		if(isset($arParams['bgcolor']) && $arParams['bgcolor']=='transparent') {
			$arParams['type']='png';
		}
		if(!isset($arParams['default'])) {
			$arParams['default']='';
		}
		return $arParams;
	}

	private function processResult($cacheFile,$cachePath,$arParams) {
		if(isset($arParams['link']) && $arParams['link']==1) {
			return $cacheFile;
		}
		if($cacheFile!='' && file_exists($cachePath)) {
			if($arImageSize=getimagesize($cachePath)) {
				list($width, $height, $type, $attr) = $arImageSize;
				$arParams['width']=$width;
				$arParams['height']=$height;
			}
			$res='<img src="'.$cacheFile.'"';
			foreach($arParams as $key=>$value)
				if(!in_array($key,$this->arSelfAttributes))
					$res.=' '.$key.'="'.$value.'"';
			$res.='/>';
			return $res;
		}
		return '';
	}

	public function Resize() {
		if(!$this->getIsInitialized())
			return '';
		try {
			$arParams=$this->getParams();
			$sAbsoluteFilePath=$this->sSiteRoot.$arParams['src'];
			if(!file_exists($sAbsoluteFilePath) || !is_file($sAbsoluteFilePath)) {
				throw new Exception('File not found: '.$arParams['src']);
			}
			$sSizeFile=$this->getHash($arParams);
			$cacheDir=$this->sCachePath.$arParams['src'].'/';
			$cacheFile=$this->sCacheUrl.$arParams['src'].'/'.$sSizeFile.'.'.$arParams['type'];
			$cachePath=$this->sCachePath.$arParams['src'].'/'.$sSizeFile.'.'.$arParams['type'];
			if(!file_exists($cachePath) || filemtime($cachePath)<filemtime($sAbsoluteFilePath)) {
				//Такой файл не был закеширован или кэш старше чем сам файл, значит надо его создавать
				$obImage=new CImageResizer($sAbsoluteFilePath);
				if(isset($arParams['wm'])) {
					$sAbsoluteWatermarkFilePath=$this->sSiteRoot.$arParams['wm'];
					if(file_exists($sAbsoluteWatermarkFilePath) && is_file($sAbsoluteWatermarkFilePath)) {
						//Watermark
						if(isset($arParams['wm_pos']) && isset($arParams['wm_padding']))
							$obWM=new CWatermarkImageGenerator($sAbsoluteWatermarkFilePath,$arParams['wm_pos'],$arParams['wm_padding']);
						elseif(isset($arParams['wm_pos']))
							$obWM=new CWatermarkImageGenerator($sAbsoluteWatermarkFilePath,$arParams['wm_pos']);
						else
							$obWM=new CWatermarkImageGenerator($sAbsoluteWatermarkFilePath);
						$obImage=new CImageResizerWaterMark($obImage,$obWM);
					}
				}
				if($arParams['width']=='' && $arParams['height']=='') {
					$obMode=new CScaleCopy(0,0);
				} else {
					$obMode=new CScale(intval($arParams['width']),intval($arParams['height']));
					switch($arParams['mode']) {
						case 'stretch':
							$obMode=new CRectGenerator(intval($arParams['width']),intval($arParams['height']));
							break;
						case 'crop':
							$obMode=new CCropToCenter(intval($arParams['width']),intval($arParams['height']));
							break;
						case 'croptop':
							$obMode=new CCropToTop(intval($arParams['width']),intval($arParams['height']));
							break;
						case 'fields':
							$obMode=new CFieldsCrop(intval($arParams['width']),intval($arParams['height']));
							if(isset($arParams['fPosition'])) {
								$obMode->setPosition($arParams['fPosition']);
							}
							break;
						case 'maxField':
							$obMode=new CMaxField(intval($arParams['width']),intval($arParams['height']));
							break;
						case 'hexagon':
							$obMode=new CCropToTop(intval($arParams['width']),intval($arParams['height']));
							$obImage=new CImageResizerHex($obImage);
							if(isset($arParams['angle'])) {
								$obImage->setAngle($arParams['angle']);
							} else {
								$obImage->setAngle(0);
							}
							if(isset($arParams['border'])) {
								$obImage->setBorderWidth($arParams['border']);
							}
							if(isset($arParams['borderColor'])) {
								$obImage->setBorderColor($arParams['borderColor']);
							}
							break;
					}
					if(isset($arParams['bgcolor'])) {
						$obImage->SetBackgroundColor($arParams['bgcolor']);
					} else {
						$obImage->SetBackgroundColor(0,0,0);
					}
				}
				if($obImage->Resize($obMode)) {
					if(!file_exists($cacheDir)) {
						if(!@mkdir($cacheDir,0755,true)) return '';
					}
					if($obImage->Save($cachePath,$arParams['type'],$arParams['quality'])) {
						chmod($cachePath,0655);
					} else {
						throw new Exception('SYSTEM_CANT_SAVE');
					}
				} else {
					throw new Exception('SYSTEM_CANT_RESIZE');
				}
			}
			return $this->processResult($cacheFile,$cachePath,$arParams);
		} catch (Exception $e) {
			$this->modx->log(xPDO::LOG_LEVEL_ERROR,'system',$e->getMessage());
			if(isset($arParams['default']) && $arParams['default']!='') {
				$cacheFile=$arParams['default'];
				$cachePath=$this->sSiteRoot.$cacheFile;
			} elseif($this->sDefaultImg!='') {
				$cacheFile=$this->sDefaultImg;
				$cachePath=$this->sSiteRoot.$cacheFile;
			} else {
				$cacheFile='';
				$cachePath='';
			}
			return $this->processResult($cacheFile,$cachePath,$arParams);
		}
	}
}