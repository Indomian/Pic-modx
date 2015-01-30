<?php

/**
 * Класс обеспечивает отрисовку watermark поверх изображения
 */
class CImageResizerWaterMark extends CImageResizer {
	protected $obWatermark;
	/**
	 * Конструктор, принимает только путь к файлу, абсолютный на сервере
	 */
	function __construct($src,CWatermarkGenerator $watermark) {
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
		$this->obWatermark=$watermark;
	}
	
	/**
	 * Метод выполняет отрисовку watermark на изображении
	 */
	protected function AfterResize() {
		$this->obWatermark->Process($this->newImage);
	}
}