<?php

/**
 * Класс обеспечивает ресайз с сохранением пропорций изображения 
 */
class CScale extends CRectGenerator {
	/**
	 * Метод возвращает координаты для усечения по центру
	 */
	function GetCoord() {
		$arResult=parent::GetCoord();
		if($arResult['w1']==0 && $arResult['h1']==0) return false;
		$fProp=$this->arSource['width']/$this->arSource['height'];
		if($arResult['h1']==0) {
			$arResult['h1']=$arResult['w1']/$fProp;
		} elseif($arResult['w1']==0) {
			$arResult['w1']=$arResult['h1']*$fProp;
		}
		if($arResult['w1']>$this->arSource['width'] || $arResult['h1']>$this->arSource['height']) {
			$arResult['w1']=$this->arSource['width'];
			$arResult['h1']=$this->arSource['height'];
		}
		$this->arResult['width']=$arResult['w1'];
		$this->arResult['height']=$arResult['h1'];
		return $arResult;
	}
}