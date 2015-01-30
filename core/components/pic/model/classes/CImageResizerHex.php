<?php

/**
 * Класс обеспечивает отрисовку watermark поверх изображения
 */
class CImageResizerHex extends CImageResizer {
	public $angle;
	public $upscale=4;
	public $border=0;
	public $arBorderColor=array();

	/**
	 * Конструктор, принимает только путь к файлу, абсолютный на сервере
	 */
	function __construct($src) {
		if($src instanceof CImageResizer) {
			$this->sFilename=$src->sFilename;
			$this->iWidth=$src->iWidth;
			$this->iHeight=$src->iHeight;
			$this->iType=$src->iType;
			$this->obRectangle=$src->obRectangle;
			$this->arBackgroundColor=$src->arBackgroundColor;
			$this->newImage=$src->newImage;
		} else {
			parent::__construct($src);
		}
		$this->arBorderColor=array(
			'r'=>255,
			'g'=>255,
			'b'=>255
		);
	}

	public function checkMemoryRequirementsAfter($w,$h) {
		$memory=CImageResizer::GetMaxMemory()-1024*1024;
		$imageMemory=$w*$h*4;
		$processMemory=$imageMemory*$this->upscale*$this->upscale+$imageMemory;
		if($imageMemory+$processMemory>$memory) {
			throw new Exception('Not enough memory for resize: required '.($imageMemory+$processMemory).' bytes has '.$memory,1);
		}
	}

	public function setAngle($angle) {
		$this->angle=$angle;
	}

	public function setBorderColor($r,$g=false,$b=false) {
		$this->arBorderColor=$this->processColor($r,$g,$b);
	}

	public function setBorderWidth($w) {
		$this->border=$w;
	}

	public function setUpscale($u) {
		$this->upscale=$u;
	}

	private function rotateVector($angle,$cx,$cy,$x,$y) {
		$x-=$cx;
		$y-=$cy;
		return array($x*cos($angle)- $y*sin($angle)+$cx,$x*sin($angle)+ $y*cos($angle)+$cy);
	}

	function image_mask(&$src, &$mask) {
		imagesavealpha($src, true);
		imagealphablending($src, false);
		// scan image pixels
		for ($x = 0; $x < imagesx($src); $x++) {
			for ($y = 0; $y < imagesy($src); $y++) {
				$mask_pix = imagecolorat($mask,$x,$y);
				$mask_pix_color = imagecolorsforindex($mask, $mask_pix);
				if ($mask_pix_color['alpha'] < 127) {
					$src_pix = imagecolorat($src,$x,$y);
					$src_pix_array = imagecolorsforindex($src, $src_pix);
					imagesetpixel($src, $x, $y, imagecolorallocatealpha($src, $src_pix_array['red'], $src_pix_array['green'], $src_pix_array['blue'], 127-$mask_pix_color['alpha']));
				}
			}
		}
	}

	/**
	 * Метод генерирует координаты шестиугольника в центре поля
	 * размером $w, на $h, с радиусом $r
	 * @param $w
	 * @param $h
	 * @param $hr
	 * @return array
	 */
	protected function getHex($w,$h,$hr) {
		$c=$hr/2;
		$r=$c*sqrt(3)/2;
		$a=$hr/4;

		$vertex=array(
			$c,0,
			$c+$r,$c-$a,
			$c+$r,$c+$a,
			$c,$hr,
			$c-$r,$c+$a,
			$c-$r,$c-$a
		);

		if($hr!=$w || $hr!=$h) {
			//Есть смешение, надо сдвигать
			$dx=($w-$hr)/2;
			$dy=($h-$hr)/2;
			$index=0;
			while($index<count($vertex)) {
				$vertex[$index]+=$dx;
				$vertex[$index+1]+=$dy;
				$index+=2;
			}
		}
		$index=0;
		while($index<count($vertex)) {
			list($vertex[$index],$vertex[$index+1])=$this->rotateVector($this->angle,$w/2,$h/2,$vertex[$index],$vertex[$index+1]);
			$index+=2;
		}
		return $vertex;
	}

	/**
	 * Метод генерирует маску с формой шестиугольника
	 * @param $width
	 * @param $height
	 * @return resource
	 */
	protected function getHexMask($width,$height) {
		$w=$width*$this->upscale;
		$h=$width*$this->upscale;
		//Prepare mask
		$maskImage=imagecreatetruecolor($w,$h);
		$fillColor=imagecolorallocatealpha($maskImage,$this->arBackgroundColor['r'],$this->arBackgroundColor['g'],$this->arBackgroundColor['b'],127);
		$transparentColor=imagecolorallocatealpha($maskImage,255-$this->arBackgroundColor['r'],255-$this->arBackgroundColor['g'],255-$this->arBackgroundColor['b'],0);
		imagefill($maskImage,0,0,$transparentColor);
		imagesavealpha($maskImage,true);
		imagealphablending($maskImage,false);

		$vertex=$this->getHex($w,$h,$w-2);
		imagefilledpolygon($maskImage,$vertex,count($vertex)/2,$fillColor);

		$smallMask=imagecreatetruecolor($width,$height);
		imagesavealpha($smallMask,true);
		imagealphablending($smallMask,false);
		imagecopyresampled($smallMask,$maskImage,0,0,0,0,$width,$height,$w,$h);
		imagedestroy($maskImage);
		return $smallMask;
	}

	/**
	 * Метод генерирует рамку в шестиугольнике
	 * @param $width
	 * @param $height
	 * @return resource
	 */
	protected function getHexBorder($width,$height) {
		$w=$width*$this->upscale;
		$h=$width*$this->upscale;
		//Prepare mask
		$maskImage=imagecreatetruecolor($w,$h);
		imagesavealpha($maskImage,true);
		imagealphablending($maskImage,false);

		$fillColor=imagecolorallocatealpha($maskImage,$this->arBorderColor['r'],$this->arBorderColor['g'],$this->arBorderColor['b'],0);
		$transparentColor=imagecolorallocatealpha($maskImage,$this->arBackgroundColor['r'],$this->arBackgroundColor['g'],$this->arBackgroundColor['b'],127);
		imagefill($maskImage,0,0,$transparentColor);

		$vertex=$this->getHex($w,$h,$w);
		imagefilledpolygon($maskImage,$vertex,count($vertex)/2,$fillColor);
		$vertex=$this->getHex($w,$h,$w-$this->border*$this->upscale*2);
		imagefilledpolygon($maskImage,$vertex,count($vertex)/2,$transparentColor);

		$smallMask=imagecreatetruecolor($width,$height);
		imagesavealpha($smallMask,true);
		imagealphablending($smallMask,false);
		imagecopyresampled($smallMask,$maskImage,0,0,0,0,$width,$height,$w,$h);
		imagedestroy($maskImage);
		return $smallMask;
	}

	/**
	 * Метод выполняет отрисовку watermark на изображении
	 */
	protected function AfterResize() {
		$arCoord=$this->obRectangle->getCoord();
		$this->checkMemoryRequirementsAfter($arCoord['w1'],$arCoord['h1']);
		$maskImage=$this->getHexMask($arCoord['w1'],$arCoord['h1']);

		imagesavealpha($this->newImage,true);
		imagealphablending($this->newImage,false);
		$this->image_mask($this->newImage,$maskImage);
		imagedestroy($maskImage);

		if($this->border>0) {
			$borderImage=$this->getHexBorder($arCoord['w1'],$arCoord['h1']);
			imagealphablending($this->newImage,true);
			imagecopy($this->newImage,$borderImage,0,0,0,0,$arCoord['w1'],$arCoord['h1']);
			imagedestroy($borderImage);
		}
	}
}