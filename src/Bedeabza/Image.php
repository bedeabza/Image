<?php
/**
 * PHP Image
 *
 * This class provides basic functionality for image manipulation using the GD library
 *
 * @category	Bedeabza
 * @package		Default
 * @author 		Dragos Badea	<bedeabza@gmail.com>
 */

class Ws_Image
{
	/**
	 * Resize to the exact specified dimensions
	 */
	const RESIZE_TYPE_STRICT        = 1;

	/**
	 * Resize preserving aspect ratio
	 */
	const RESIZE_TYPE_RATIO         = 2;

	/**
	 * Resize after a crop from center was performed to respect dimensions
	 */
	const RESIZE_TYPE_CROP          = 3;

	/**
	 * Watermark positions
	 */
	const WM_POS_TOP_LEFT           = 1;
	const WM_POS_TOP_RIGHT          = 2;
	const WM_POS_BOTTOM_RIGHT       = 3;
	const WM_POS_BOTTOM_LEFT        = 4;
	const WM_POS_CENTER             = 5;

	/**
	 * Watermark offset from border
	 */
	const WM_OFFSET                 = 15;

	/**
	 * @var array
	 */
	protected $_errors = array(
		'NotExists'         => 'The file %s does not exist',
		'NotReadable'       => 'The file %s is not readable',
		'Format'            => 'Unknown image format: %s',
		'GD'                => 'The PHP extension GD is not enabled',
		'WidthHeight'       => 'Please specify at least one of the width and height parameters',
		'CropDimExceed'     => 'The cropping dimensions must be smaller than the original ones',
	);

	/**
	 * @var string
	 */
    protected $_fileName = null;

	/**
	 * @var string
	 */
	protected $_format = null;

	/**
	 * @var array
	 */
	protected $_acceptedFormats = array('png','gif','jpeg');

	/**
	 * @var resource
	 */
	protected $_sourceImage = null;

	/**
	 * @var resource
	 */
	protected $_workingImage = null;

	/**
	 * @var array
	 */
	protected $_originalSize = null;

	/**
	 * @param string $fileName
	 * @return void
	 */
	public function __construct($fileName)
	{
		if(!file_exists($fileName))
			$this->error('NotExists', $fileName);

		if(!is_readable($fileName))
			$this->error('NotReadable', $fileName);

		$this->_originalSize    = getimagesize($fileName);
		$this->_format          = array_pop(explode('/', $this->_originalSize['mime']));

		if(!in_array($this->_format, $this->_acceptedFormats))
			$this->error('Format', $this->_format);

		$this->_fileName        = $fileName;
		$this->_sourceImage     = $this->_createImageFromFile();
		$this->_workingImage    = $this->_createImageFromFile();
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @param int $mode
	 * @return void
	 */
	public function resize($width = null, $height = null, $mode = self::RESIZE_TYPE_CROP)
	{
		list($width, $height) = $this->_calcDefaultDimensions($width, $height);

		if($mode != self::RESIZE_TYPE_STRICT){
			//recalculate width and height if they exceed original dimensions
			if($width > $this->_originalSize[0] || $height > $this->_originalSize[1]){
				$width = $this->_originalSize[0];
				$height = $this->_originalSize[1];
			}

			//reclaculate to preserve aspect ratio
			if($width/$height != $this->_originalSize[0]/$this->_originalSize[1]){
				//mark for cropping
				if($mode == self::RESIZE_TYPE_CROP){
					$cropAfter = true;
					$cropDimensions = array($width, $height);
				}

				if(
					($width/$this->_originalSize[0] > $height/$this->_originalSize[1] && $mode == self::RESIZE_TYPE_RATIO) ||
					($width/$this->_originalSize[0] < $height/$this->_originalSize[1] && $mode == self::RESIZE_TYPE_CROP)
				){
					$width = $height/$this->_originalSize[1]*$this->_originalSize[0];
				}else{
					$height = $width/$this->_originalSize[0]*$this->_originalSize[1];
				}
			}
		}

		//create new image
		$this->_workingImage = $this->_createImage($width, $height);

		//move the pixels from source to new image
		imagecopyresampled($this->_workingImage, $this->_sourceImage, 0, 0, 0, 0, $width, $height, $this->_originalSize[0], $this->_originalSize[1]);
		$this->_replaceAndReset($width, $height);

		if($cropAfter)
			$this->cropFromCenter($cropDimensions[0], $cropDimensions[1]);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @return void
	 */
	public function crop($x = 0, $y = 0, $width = null, $height = null)
	{
		if($width > $this->_originalSize[0] || $height > $this->_originalSize[1])
			$this->error('CropDimExceed');

		list($width, $height) = $this->_calcDefaultDimensions($width, $height);

		//create new image
		$this->_workingImage = $this->_createImage($width, $height);

		//move the pixels from source to new image
		imagecopyresampled($this->_workingImage, $this->_sourceImage, 0, 0, $x, $y, $width, $height, $width, $height);
		$this->_replaceAndReset($width, $height);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return void
	 */
	public function cropFromCenter($width, $height)
	{
		$x = (int)(($this->_originalSize[0] - $width) / 2);
		$y = (int)(($this->_originalSize[1] - $height) / 2);

		$this->crop($x, $y, $width, $height);
	}

	/**
	 * @param int $position
	 * @param string $fileName
	 * @param int $width
	 * @param int $height
	 * @return void
	 */
	public function watermark($fileName, $position = self::WM_POS_BOTTOM_RIGHT, $width = null, $height = null)
	{
		$watermark = new Ws_Image($fileName);
		if($width || $height)
			$watermark->resize($width, $height, self::RESIZE_TYPE_STRICT);

		$this->_workingImage = $this->_createImage($this->_originalSize[0], $this->_originalSize[1]);
		imagealphablending($this->_workingImage, true);

		switch($position){
			case self::WM_POS_TOP_LEFT:
		        $x = $y = self::WM_OFFSET;
		        break;
			case self::WM_POS_TOP_RIGHT:
				$x = $this->_originalSize[0] - $watermark->getWidth() - self::WM_OFFSET;
		        $y = self::WM_OFFSET;
		        break;
			case self::WM_POS_BOTTOM_RIGHT:
		        $x = $this->_originalSize[0] - $watermark->getWidth() - self::WM_OFFSET;
		        $y = $this->_originalSize[1] - $watermark->getHeight() - self::WM_OFFSET;
		        break;
			case self::WM_POS_BOTTOM_LEFT:
				$x = $y = self::WM_OFFSET;
		        $y = $this->_originalSize[1] - $watermark->getHeight() - self::WM_OFFSET;
		        break;
			case self::WM_POS_CENTER:
		        $x = ($this->_originalSize[0] - $watermark->getWidth()) / 2;
		        $y = ($this->_originalSize[1] - $watermark->getHeight()) / 2;
		        break;
		}

		imagecopy($this->_workingImage, $this->_sourceImage, 0, 0, 0, 0, $this->_originalSize[0], $this->_originalSize[1]);
		imagecopy($this->_workingImage, $watermark->getSourceImage(), $x, $y, 0, 0, $watermark->getWidth(), $watermark->getHeight());

		$this->_replaceAndReset($this->_originalSize[0], $this->_originalSize[1]);

		$watermark->destroy();
	}

	/**
	 * @return resource
	 */
	public function getSourceImage()
	{
		return $this->_sourceImage;
	}

	/**
	 * @return resource
	 */
	public function getWorkingImage()
	{
		return $this->_workingImage;
	}

	/**
	 * @return int
	 */
	public function getWidth()
	{
		return $this->_originalSize[0];
	}

	/**
	 * @return int
	 */
	public function getHeight()
	{
		return $this->_originalSize[1];
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	protected function _calcDefaultDimensions($width = null, $height = null)
	{
		if(!$width && !$height)
			$this->error('WidthHeight');

		//autocalculate width and height if one of them is missing
		if(!$width)
			$width = $height/$this->_originalSize[1]*$this->_originalSize[0];

		if(!$height)
			$height = $width/$this->_originalSize[0]*$this->_originalSize[1];

		return array($width, $height);
	}

	/**
	 * @return resource
	 */
	protected function _createImageFromFile()
	{
		$function = 'imagecreatefrom'.$this->_format;
		if(!function_exists($function))
			$this->error('GD');

		return $function($this->_fileName);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return void
	 */
	protected function _createImage($width, $height)
	{
		$function = function_exists('imagecreatetruecolor') ? 'imagecreatetruecolor' : 'imagecreate';
		$image = $function($width, $height);

		//special conditions for png transparence
		if($this->_format == 'png'){
			imagealphablending($image, false);
			imagesavealpha($image, true);
			imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocatealpha($image, 255, 255, 255, 127));
		}

		return $image;
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return void
	 */
	protected function _replaceAndReset($width, $height)
	{
		$this->_sourceImage = $this->_createImage($width, $height);
		imagecopy($this->_sourceImage, $this->_workingImage, 0, 0, 0, 0, $width, $height);
		imagedestroy($this->_workingImage);
		$this->_workingImage = null;

		$this->_originalSize[0] = $width;
		$this->_originalSize[1] = $height;
	}

	/**
	 * @throws Zend_Exception
	 * @param string $code
	 * @param array $params
	 * @return void
	 */
	protected function error($code, $params = array())
	{
		if(!is_array($params))
			$params = array($params);

		throw new Zend_Exception(vsprintf($this->_errors[$code], $params));
	}

	/**
	 * @return void
	 */
	public function destroy()
	{
		if($this->_workingImage)
			imagedestroy($this->_workingImage);
		if($this->_sourceImage)
			imagedestroy($this->_sourceImage);
	}

	/**
	 * @param string $name
	 * @param int $expires in seconds
	 * @return void
	 */
	public function sendHeaders($name = '', $expires = 0, $lastMod = null)
	{
		header('Content-type: image/'.$this->_format);
		header("Content-Disposition: inline".($name ? "; filename=".$name : ''));
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', ($lastMod ? $lastMod : time())) . ' GMT');
		header("Cache-Control: maxage={$expires}");
		if($expires)
			header("Expires: " . gmdate('D, d M Y H:i:s', ($lastMod ? $lastMod : time())+$expires) . ' GMT');
		header("Pragma: public");
	}

	/**
	 * @param string $name
	 * @param int $quality
	 * @return void
	 */
	public function render($name = '', $quality = 100)
	{
		$this->sendHeaders($name);
		$this->_execute(null, $quality);

		$this->destroy();
		die;
	}

	/**
	 * @param string $fileName
	 * @return void
	 */
	public function save($fileName = null, $quality = 100)
	{
		$this->_execute($fileName ? $fileName : $this->_fileName, $quality);
	}

	/**
	 * @param string $fileName
	 * @param  $quality
	 * @return void
	 */
	protected function _execute($fileName = null, $quality)
	{
		$function = 'image'.$this->_format;
		$function($this->_sourceImage, $fileName, $this->_getQuality($quality));
	}

	/**
	 * @param int $quality
	 * @return int
	 */
	protected function _getQuality($quality)
	{
		switch($this->_format){
			case 'gif':
		        return null;
			case 'jpeg':
		        return $quality;
			case 'png':
		        return (int)($quality/10 - 1);
		}
	}
}
