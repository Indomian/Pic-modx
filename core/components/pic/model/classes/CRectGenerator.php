<?php
/**
 * Класс выполняет генерацию координат для ресайза картинки
 */
class CRectGenerator {
	protected $arSource;
	protected $arResult;

	function __construct($arSource,$arResult,$rWidth=false,$rHeight=false) {
		if(is_array($arSource) && is_array($arResult)) {
			$this->arSource=$arSource;
			$this->arResult=$arResult;
		} elseif($rWidth===false && $rHeight===false) {
			$this->arResult=array('width'=>$arSource,'height'=>$arResult);
			$this->arSource=false;
		} else {
			$this->arSource=array('width'=>$arSource,'height'=>$arResult);
			$this->arResult=array('width'=>$rWidth,'height'=>$rHeight);
		}
	}

	/**
	 * Метод устанавливает размеры источника (ширина и высота)
	 */
	function SetSourceSize($w,$h) {
		$this->arSource=array('width'=>$w,'height'=>$h);
	}

	function GetResultWidth() {
		return $this->arResult['width'];
	}
	
	function GetResultHeight() {
		return $this->arResult['height'];
	}
	
	/**
	 * Метод возвращает координаты по простому способу
	 */
	function GetCoord() { 
		if(!$this->arSource) return false;
		$arResult=array(
			'x'=>0,
			'y'=>0,
			'w'=>$this->arSource['width'],
			'h'=>$this->arSource['height'],
			'x1'=>0,
			'y1'=>0,
			'w1'=>$this->arResult['width'],
			'h1'=>$this->arResult['height']
		);
		return $arResult;
	}
}