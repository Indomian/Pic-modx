<?php
/**
 * Класс выполняет расчёт координат с полями
 */
class CFieldsCrop extends CRectGenerator {
	const CENTER=0;
	const TOP=1;
	const RIGHT=2;
	const BOTTOM=3;
	const LEFT=4;
	const TOPLEFT=5;
	const TOPRIGHT=6;
	const BOTTOMRIGHT=7;
	const BOTTOMLEFT=8;

	private $position=0;

	function GetCoord() {
		$arResult=parent::GetCoord();
		//Считаем пропорции оригинала и результата
		$fProp=$this->arSource['width']/$this->arSource['height'];
		$fRProp=$this->arResult['width']/$this->arResult['height'];
		if($fRProp<$fProp) {
			//Если исходная картинка более широкая чем высокая, подгоняем её размеры по ширине
			$scale=$this->arResult['width']/$this->arSource['width'];
			$iScaledHeight = round($this->arSource['height']*$scale);
			if($this->position==self::TOP ||
				$this->position==self::TOPLEFT ||
				$this->position==self::TOPRIGHT) {
				$arResult['y1']=0;
			} elseif($this->position==self::BOTTOM ||
				$this->position==self::BOTTOMLEFT ||
				$this->position==self::BOTTOMRIGHT) {
				$arResult['y1']=$this->arResult['height']-$iScaledHeight;
			} else {
				$arResult['y1']=round(($this->arResult['height']-$iScaledHeight)/2);
			}
			$arResult['h1']=$iScaledHeight;
		} else {
			//Пропорции исходного больше, значит важнее высота
			$scale=$this->arResult['height']/$this->arSource['height'];
			$iScaledWidth = round($this->arSource['width']*$scale);
			if($this->position==self::LEFT ||
				$this->position==self::TOPLEFT ||
				$this->position==self::BOTTOMLEFT) {
				$arResult['x1']=0;
			} elseif($this->position==self::RIGHT ||
				$this->position==self::TOPRIGHT ||
				$this->position==self::BOTTOMRIGHT) {
				$arResult['x1']=$this->arResult['width']-$iScaledWidth;
			} else {
				$arResult['x1']=round(($this->arResult['width']-$iScaledWidth)/2);
			}
			$arResult['w1']=$iScaledWidth;
		}
		return $arResult;
	}

	function setPosition($value) {
		$this->position=$value;
	}
}