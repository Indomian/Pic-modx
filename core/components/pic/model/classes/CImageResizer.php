<?php
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
	/**
	 * @var CRectGenerator
	 */
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
	function __construct($src) {
		$this->sFilename = $src;
		if(!is_file($src))
			throw new Exception('SYSTEM_NOT_A_FILE');

		$info = pathinfo($src); // Информация о файле
		if($arImageSize=getimagesize($this->sFilename))
			list($width, $height, $type, $attr) = $arImageSize;
		else
			throw new Exception('SYSTEM_NOT_A_IMAGE');
		$this->iWidth = $width;
		$this->iHeight = $height;
		$this->iType = $type;
		$this->obRectangle=false;
		$this->arBackgroundColor=false;
		$this->isTransparent=false;

		$this->checkMemoryRequirements();
	}

	public function checkMemoryRequirements() {
		$imageMemory=$this->iWidth*$this->iHeight*4;
		$memory=CImageResizer::GetMaxMemory()-1024*1024;
		if($imageMemory>$memory) {
			throw new Exception('Not enough memory for resize: required '.$imageMemory.' bytes has '.$memory,1);
		}
	}

	/**
	 * Метод определяет максимальный доступный объём памяти для обработки изображения
	 */
	static function GetMaxMemory() {
		$val=ini_get('memory_limit');
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/**
	 * Деструктор выполняет автоматическое удаление изображения
	 */
	function __destruct() {
		if($this->image) {
			imagedestroy($this->image);
			$this->image=0;
		}
		if($this->newImage) {
			imagedestroy($this->newImage);
			$this->newImage=0;
		}
	}

	protected function processColor($r,$g=false,$b=false) {
		if(preg_match('#^\#([0-9a-f]{3}|[0-9a-f]{6})$#i',$r,$html)) {
			if(strlen($html[1])==3) {
				//HTML цвет в трёхсимволоьном формате
				$html[1]=$html[1][0].$html[1][0].$html[1][1].$html[1][1].$html[1][2].$html[1][2];
			}
			$arBits=str_split($html[1],2);
			$arColor=$this->processColor(hexdec($arBits[0]),hexdec($arBits[1]),hexdec($arBits[2]));
		} elseif(is_numeric($r) && is_numeric($g) && is_numeric($b)) {
			$arColor=array(
				'r'=>$r,
				'g'=>$g,
				'b'=>$b
			);
		} else {
			$arColor=array(
				'r'=>255,
				'g'=>255,
				'b'=>255
			);
		}
		return $arColor;
	}

	/**
	 * Метод выполняет переназначение цвета изображения
	 */
	function SetBackgroundColor($r,$g=false,$b=false) {
		if($r=='transparent') {
			$this->arBackgroundColor='transparent';
		} else {
			$this->arBackgroundColor=$this->processColor($r,$g,$b);
		}
	}

	/**
	 * Метод изменения размера изображения
	 * @param $image_w - требуемая ширина изображения
	 * @param $image_h - требуемая высота изображения
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
		if($arCoord=$this->obRectangle->GetCoord()) {
			switch ($this->iType) {
				case IMAGETYPE_GIF: $this->image = imagecreatefromgif($this->sFilename); break;
				case IMAGETYPE_JPEG: $this->image = imagecreatefromjpeg($this->sFilename);  break;
				case IMAGETYPE_PNG: $this->image = imagecreatefrompng($this->sFilename); break;
				case IMAGETYPE_WBMP: $this->image = imagecreatefromwbmp($this->sFilename); break;
				default:  throw new Exception('PHOTOGALLERY_WRONG_FILE', E_USER_WARNING);  break;
			}
			$newImg = imagecreatetruecolor($this->obRectangle->GetResultWidth(), $this->obRectangle->GetResultHeight());
			//Если указан цвет фона, зальём фон этим цветом
			if($this->arBackgroundColor=='transparent') {
				imagesavealpha($newImg,true);
				imagealphablending($newImg,false);
				$transparent=imagecolorallocatealpha($newImg,0,0,0,127);
				imagefill($newImg,0,0,$transparent);
			}elseif($this->arBackgroundColor) {
				$iBgColor=imagecolorallocate($newImg, $this->arBackgroundColor['r'], $this->arBackgroundColor['g'], $this->arBackgroundColor['b']);
				imagefill($newImg,0,0,$iBgColor);
			}
			$this->BeforeResize();
			if(imagecopyresampled($newImg, $this->image, $arCoord['x1'], $arCoord['y1'], $arCoord['x'], $arCoord['y'], $arCoord['w1'],$arCoord['h1'], $arCoord['w'], $arCoord['h'])) {
				$this->newImage=$newImg;
				$this->AfterResize();
				return true;
			}
		}
		return false;
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

	/**
	 * Метод выполняет сохранение нового изображения по новому пути
	 */
	function Save($path,$type='png',$quality=95) {
		if($this->newImage) {
			if($type=='jpg') {
				$res=@imagejpeg($this->newImage,$path,$quality);
			} else {
				$res=@imagepng($this->newImage,$path,9);
			}
			if(!imagedestroy($this->newImage)) throw new Exception('SYSTEM_STRANGE_ERROR');
			$this->newImage=0;
			return $res;
		}
		return false;
	}
}