<?php

/**
 * Класс обеспечивает режим расчёта координат для усечения по центру без полей
 */
class CCropToCenter extends CRectGenerator {
	/**
	 * Метод возвращает координаты для усечения по центру
	 */
	function GetCoord() {
		$arResult=parent::GetCoord();
		//Считаем пропорции оригинала и результата
		$fProp=$this->arSource['width']/$this->arSource['height'];
		$fRProp=$this->arResult['width']/$this->arResult['height'];
		if($fRProp>$fProp) {
			//Если пропорции результата больше (т.е. ширина важнее)
			$scale=$this->arResult['width']/$this->arSource['width'];
			$iScaledHeight = round($this->arSource['height']*$scale);
			if($iScaledHeight>$this->arResult['height']) {
				//Высота ресайза больше высоты результата
				$arResult['y']=round(($iScaledHeight-$this->arResult['height'])/2/$scale);
				$arResult['h']=round($this->arResult['height']/$scale);
			}
		} else {
			//Пропорции исходного больше, значит важнее высота
			$scale=$this->arResult['height']/$this->arSource['height'];
			$iScaledWidth = round($this->arSource['width']*$scale);
			if($iScaledWidth>$this->arResult['width']) {
				//Если ширина картинки оказалась больше чем допустимая ширина
				//То надо посчитать смещение и изменить выводимую ширину
				$arResult['x']=round(($iScaledWidth-$this->arResult['width'])/2/$scale);
				$arResult['w']=round($this->arResult['width']/$scale);
			}
		}
		return $arResult;
	}
}