<?php
/**
 * @file Pic.php
 * Функция выполняет изменение размера картинки и кэширует результат
 *
 * @since 20.07.2011
 *
 * @author blade39 <blade39@kolosstudio.ru>,
 * @version 1.2
 * v 1.2
 *  - Добавлена поддержка форматов Gif, wbmp
 *  - Добавлен режим сжатия с полями
 *  - Добавлен режим автоматического расчёта неуказанной стороны
 */

define('PIC_CACHE_SITE_ROOT',MODX_BASE_PATH);
define('PIC_CACHE_PATH',PIC_CACHE_SITE_ROOT.'/assets/cache/Pic');
define('PIC_CACHE_PATH_URL','/assets/cache/Pic');
define('PIC_CACHE_DEFAULT_IMAGE','');
define('PIC_MAX_MEMORY',268435456); //Максимум используемой памяти, если лимит не указан

/**
 * Класс работы с изображениями v2.6
 * Изменение изображений по след. параметрам: Ширина, Высота, Пропорциональность, Белые поля.
 *
 * Автор: Егор Болгов
 */

class CImageResizer {
	protected $sFilename;
	protected $iWidth;
	protected $iHeight;
	protected $iType;
	protected $obRectangle;
	protected $arBackgroundColor;
  	/**
  	 * Переменная для хранения нового изображения после ресайза
  	 */
  	protected $newImage;
	protected $image;
  	/**
  	 * Статический массив допустимых расширений
  	 */
  	static $arAllowExt=array('jpg','jpeg','png');

	/**
	 * Конструктор, принимает только путь к файлу, абсолютный на сервере
	 */
	function __construct($inputfile) {
		$this->sFilename = $inputfile;
		if(!is_file($inputfile))
			throw new Exception('SYSTEM_NOT_A_FILE');

		if($arImageSize=@getimagesize($this->sFilename))
			list($width, $height, $type) = $arImageSize;
		else
			throw new Exception('SYSTEM_NOT_A_IMAGE');
		$this->iWidth = $width;
		$this->iHeight = $height;
		$this->iType = $type;
		$this->obRectangle=false;
		$this->arBackgroundColor=false;

		$currentMemoryUsage=memory_get_usage();

		if($this->iWidth*$this->iHeight*4>(CImageResizer::GetMaxMemory()-$currentMemoryUsage))
			throw new Exception('SYSTEM_NO_MEMORY '.($this->iWidth*$this->iHeight*4).'/'.(CImageResizer::GetMaxMemory()-$currentMemoryUsage));
	}

	/**
	 * Метод определяет максимальный доступный объём памяти для обработки изображения
	 */
	static function GetMaxMemory() {
		$val=ini_get('memory_limit');
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		$val=intval($val);
		if($val>0) {
			switch($last) {
				// The 'G' modifier is available since PHP 5.1.0
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}
		} else {
			return PIC_MAX_MEMORY;
		}
		return $val;
	}

	/**
	 * Деструктор выполняет автоматическое удаление изображения
	 */
	function __destruct() {
		if($this->newImage) {
			imagedestroy($this->newImage);
			$this->newImage=0;
		}
	}

	/**
	 * Метод выполняет переназначение цвета фона изображения
	 * @param string $r
	 * @param string|bool $g
	 * @param string|bool $b
	 * @return bool
	 */
	public function SetBackgroundColor($r,$g=false,$b=false) {
		if(preg_match('#^\#([0-9a-f]{3}|[0-9a-f]{6})$#i',$r,$html)) {
			$color=$html[1];
			if(strlen($color)==3) {
				$arBits=str_split($color,1);
				$color=$arBits[0].$arBits[0].$arBits[1].$arBits[1].$arBits[2].$arBits[2];
			}
			$arBits=str_split($color,2);
			return $this->SetBackgroundColor(hexdec($arBits[0]),hexdec($arBits[1]),hexdec($arBits[2]));
		} elseif(is_int($r) && is_int($g) && is_int($b)) {
			$this->arBackgroundColor=array(
				'r'=>$r,
				'g'=>$g,
				'b'=>$b
			);
			return true;
		}
		return false;
	}

	public function GetBackgroundColor() {
		if(!$this->arBackgroundColor) {
			return array(
				'r'=>0,
				'g'=>0,
				'b'=>0
			);
		}
		return $this->arBackgroundColor;
	}

	/**
	 * Метод изменения размера изображения
	 * @param      $image_w - требуемая ширина изображения
	 * @param       $image_h - требуемая высота изображения
	 *
	 * @throws Exception
	 * @return bool
	 */
	function Resize($image_w,$image_h=false) {
		if(is_object($image_w) && $image_w instanceof CRectGenerator) {
			$this->obRectangle=$image_w;
		} else {
			if($image_w == 0 && $image_h == 0)
				throw new Exception('WH by zero');
			$this->obRectangle=new CRectGenerator($this->iWidth,$this->iHeight,$image_w,$image_h);
		}
		$this->obRectangle->SetSourceSize($this->iWidth,$this->iHeight);
		$arCoord=$this->obRectangle->GetCoord();
		switch ($this->iType) {
			case IMAGETYPE_GIF: $this->image = imagecreatefromgif($this->sFilename); break;
			case IMAGETYPE_JPEG: $this->image = imagecreatefromjpeg($this->sFilename);  break;
			case IMAGETYPE_PNG: $this->image = imagecreatefrompng($this->sFilename); break;
			case IMAGETYPE_WBMP: $this->image = imagecreatefromwbmp($this->sFilename); break;
			default:  throw new Exception('PHOTOGALLERY_WRONG_FILE '.$this->iType, E_USER_WARNING);  break;
		}
		if($this->obRectangle->GetResultWidth()<1)
			throw new Exception('IMAGE_RESULT_WIDTH_LESS_1');
		if($this->obRectangle->GetResultHeight()<1)
			throw new Exception('IMAGE_RESULT_HEIGHT_LESS_1');
		$newImg = imagecreatetruecolor($this->obRectangle->GetResultWidth(), $this->obRectangle->GetResultHeight());
		//Если указан цвет фона, зальём фон этим цветом
		if($this->arBackgroundColor) {
			$iBgColor=imagecolorallocate($newImg, $this->arBackgroundColor['r'], $this->arBackgroundColor['g'], $this->arBackgroundColor['b']);
			imagefill($newImg,0,0,$iBgColor);
		}
		$this->BeforeResize();
		imagecopyresampled($newImg, $this->image, $arCoord['x1'], $arCoord['y1'], $arCoord['x'], $arCoord['y'], $arCoord['w1'],$arCoord['h1'], $arCoord['w'], $arCoord['h']);
		$this->newImage=$newImg;
		$this->AfterResize();
		return true;
	}

	/**
	 * Метод выполняется перед ресайзом
	 */
	protected function BeforeResize() {
		
	}
	
	/**
	 * Метод выпонляется после ресайза
	 */
	protected function AfterResize() {
		
	}

	public function GetNewImage() {
		if($this->newImage) {
			return $this->newImage;
		}
		return false;
	}

	/**
	 * Метод выполняет сохранение нового изображения по новому пути
	 */
	function Save($path,$quality=98) {
		if($this->newImage) {
			$res=@imagejpeg($this->newImage,$path,$quality);
			if(!imagedestroy($this->newImage)) throw new Exception('SYSTEM_STRANGE_ERROR');
			$this->newImage=0;
			return $res;
		}
		return false;
	}
}

/**
 * Класс обеспечивает отрисовку watermark поверх изображения
 */
class CImageResizerWaterMark extends CImageResizer {
	protected $obWatermark;
	/**
	 * Конструктор, принимает только путь к файлу, абсолютный на сервере
	 */
	function __construct($inputfile,CWatermarkGenerator $watermark) {
		if($inputfile instanceof CImageResizer) {
			$this->sFilename=$inputfile->sFilename;
			$this->iWidth=$inputfile->iWidth;
			$this->iHeight=$inputfile->iHeight;
			$this->iType=$inputfile->iType;
			$this->obRectangle=$inputfile->obRectangle;
			$this->arBackgroundColor=$inputfile->arBackgroundColor;
			$this->newImage=$inputfile->newImage;
		} else {
			parent::__construct($inputfile);
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

/**
 * Класс обеспечивает генерацию различных Watermark
 */
abstract class CWatermarkGenerator {
	/**
	 * Метод выполняет отрисовку вотермарка на изображении
	 */
	abstract function Process($img);
}

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
		if($arImageSize=@getimagesize($this->sWatermarkPath))
			list($width, $height, $type) = $arImageSize;
		else
			throw new Exception('SYSTEM_NOT_A_IMAGE');
		$this->iWidth = $width;
		$this->iHeight = $height;
		$this->iType = $type;
		$this->iPos=$iPos;
		$this->iPadding=$iPadding;
	}

	public function SetPosition($iPos) {
		$this->iPos=$iPos;
	}
	
	/**
	 * Метод выполняет отрисовку вотермарка на изображении
	 */
	function Process($img) {
		//Определяем, что нам скормили
		switch ($this->iType) {
			case IMAGETYPE_GIF: $wf = imagecreatefromgif($this->sWatermarkPath); break;
			case IMAGETYPE_PNG: $wf = imagecreatefrompng($this->sWatermarkPath); break;
			default:  throw new Exception('PHOTOGALLERY_WATERMARK_WRONG_FILE');  break;
		}
		$img_w=imagesx($img);
		$img_h=imagesy($img);
		switch($this->iPos) {
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
			default:
				$dst_x=round(($img_w-$this->iWidth)/2);
				$dst_y=round(($img_h-$this->iHeight)/2);
			break;
		}
		imagealphablending($img,true);
		imagecopyresampled($img, $wf, $dst_x, $dst_y, 0, 0, $this->iWidth, $this->iHeight, $this->iWidth, $this->iHeight);
		imagedestroy($wf);
		return true;
	}
}

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
		if(!$this->arSource)
			throw new Exception('SYSTEM_SOURCE_SIZE_EMPTY');
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
			$arResult['h1']=round($arResult['w1']/$fProp);
		} elseif($arResult['w1']==0) {
			$arResult['w1']=round($arResult['h1']*$fProp);
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

/**
 * Метод выполняет усечение по верхней грани изображения
 */
class CCropToTop extends CRectGenerator {
	/**
	 * Метод возвращает координаты для усечения по центру или по верхнему краю
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
				$arResult['y']=0;
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

/**
 * Класс выполняет расчёт координат с полями
 */
class CFieldsCrop extends CRectGenerator {
	function GetCoord() {
		$arResult=parent::GetCoord();
		//Считаем пропорции оригинала и результата
		$fProp=$this->arSource['width']/$this->arSource['height'];
		$fRProp=$this->arResult['width']/$this->arResult['height'];
		if($fRProp<$fProp) {
			//Если исходная картинка более широкая чем высокая, подгоняем её размеры по ширине
			$scale=$this->arResult['width']/$this->arSource['width'];
			$iScaledHeight = round($this->arSource['height']*$scale);
			$arResult['y1']=round(($this->arResult['height']-$iScaledHeight)/2);
			$arResult['h1']=$iScaledHeight;
		} else {
			//Пропорции исходного больше, значит важнее высота
			$scale=$this->arResult['height']/$this->arSource['height'];
			$iScaledWidth = round($this->arSource['width']*$scale);
			$arResult['x1']=round(($this->arResult['width']-$iScaledWidth)/2);
			$arResult['w1']=$iScaledWidth;
		}
		return $arResult;
	}
}

