<?php
class CWatermarkImageGenerator extends CWatermarkGenerator {
	const C=0;
	const NW=1;
	const NC=2;
	const NE=3;
	const EC=4;
	const SE=5;
	const SC=6;
	const SW=7;
	const WC=8;

	protected $sWatermarkPath;
	protected $iWidth;
	protected $iHeight;
	protected $iType;
	protected $iPos;
	protected $iPadding;
	
	function __construct($path,$iPos=self::C,$iPadding=5) {
		$this->sWatermarkPath=$path;
		if($arImageSize=getimagesize($this->sWatermarkPath))
			list($width, $height, $type, $attr) = $arImageSize;
		else
			throw new Exception('SYSTEM_NOT_A_IMAGE');
		$this->iWidth = $width;
		$this->iHeight = $height;
		$this->iType = $type;
		$this->iPos=$iPos;
		$this->iPadding=$iPadding;
	}
	
	/**
	 * Метод выполняет отрисовку вотермарка на изображении
	 */
	function Process($img) {
		//Определяем, что нам скормили
		switch ($this->iType) {
			case IMAGETYPE_GIF: $wf = imagecreatefromgif($this->sWatermarkPath); break;
			case IMAGETYPE_PNG: $wf = imagecreatefrompng($this->sWatermarkPath); break;
			default:  throw new Exception('PHOTOGALLERY_WATERMARK_WRONG_FILE', E_USER_WARNING);  break;
		}
		$img_w=imagesx($img);
		$img_h=imagesy($img);
		switch($this->iPos) {
			case self::C:
				$dst_x=round(($img_w-$this->iWidth)/2);
				$dst_y=round(($img_h-$this->iHeight)/2);
			break;
			case self::NW:
				$dst_x=$this->iPadding;
				$dst_y=$this->iPadding;
			break;
			case self::NC:
				$dst_x=round(($img_w-$this->iWidth)/2);
				$dst_y=$this->iPadding;
			break;
			case self::NE:
				$dst_x=round($img_w-$this->iWidth-$this->iPadding);
				$dst_y=$this->iPadding;
			break;
			case self::EC:
				$dst_x=round($img_w-$this->iWidth-$this->iPadding);
				$dst_y=round(($img_h-$this->iHeight)/2);
			break;
			case self::SE:
				$dst_x=round($img_w-$this->iWidth-$this->iPadding);
				$dst_y=round($img_h-$this->iHeight-$this->iPadding);
			break;
			case self::SC:
				$dst_x=round(($img_w-$this->iWidth)/2);
				$dst_y=round($img_h-$this->iHeight-$this->iPadding);
			break;
			case self::SW:
				$dst_x=$this->iPadding;
				$dst_y=round($img_h-$this->iHeight-$this->iPadding);
			break;
			case self::WC:
				$dst_x=$this->iPadding;
				$dst_y=round(($img_h-$this->iHeight)/2);
			break;
		}
		imagealphablending($img,true);
		if(imagecopyresampled($img, $wf, $dst_x, $dst_y, 0, 0, $this->iWidth, $this->iHeight, $this->iWidth, $this->iHeight)) {
			imagedestroy($wf);
			return true;
		}
		imagedestroy($wf);
		return false;
	}
}
