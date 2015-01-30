<?php
/**
 * Класс обеспечивает копирование изображения 1 к 1
 */
class CScaleCopy extends CRectGenerator{
	/**
	 * Метод возвращает координаты для усечения по центру
	 */
	function GetCoord() {
		$arResult=parent::GetCoord();
		$arResult['h1']=$arResult['h'];
		$arResult['w1']=$arResult['w'];
		$this->arResult['width']=$arResult['w1'];
		$this->arResult['height']=$arResult['h1'];
		return $arResult;
	}
}
