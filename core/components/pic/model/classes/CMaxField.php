<?php
/**
 * Class CMaxField
 * Performs resize using maximum side of image
 */
class CMaxField extends CRectGenerator {
	function GetCoord() {
		$arResult=parent::GetCoord();
		//Считаем пропорции оригинала и результата
		$fProp=$this->arSource['width']/$this->arSource['height'];
		$fRProp=$this->arResult['width']/$this->arResult['height'];
		if($fRProp<$fProp) {
			//Если исходная картинка более широкая чем высокая, подгоняем её размеры по ширине
			$scale=$this->arResult['width']/$this->arSource['width'];
			$iScaledHeight = round($this->arSource['height']*$scale);
			$this->arResult['height']=$iScaledHeight;
			$arResult['h1']=$this->arResult['height'];
		} else {
			//Пропорции исходного больше, значит важнее высота
			$scale=$this->arResult['height']/$this->arSource['height'];
			$iScaledWidth = round($this->arSource['width']*$scale);
			$this->arResult['width']=$iScaledWidth;
			$arResult['w1']=$this->arResult['width'];
		}
		return $arResult;
	}
}