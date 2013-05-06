<?php
/**
 * PHP Image
 *
 * This class provides basic functionality for image manipulation using the GD library
 *
 * @category    Bedeabza
 * @package	    Default
 * @author 	    Dragos Badea	<bedeabza@gmail.com>
 */

namespace Bedeabza;

require 'Exception.php';

use \Bedeabza\Image\Exception as ImageException;

class Image
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
     *
     * @var int
	 */
	protected $watermarkOffset = 15;

	/**
	 * @var array
	 */
	protected $errors = array(
		'NotLoaded'         => 'No image was loaded',
		'NotExists'         => 'The file %s does not exist',
		'NotReadable'       => 'The file %s is not readable',
		'Format'            => 'Unknown image format: %s',
		'GD'                => 'The PHP extension GD is not enabled',
		'WidthHeight'       => 'Please specify at least one of the width and height parameters',
		'CropDimExceed'     => 'The cropping dimensions must be smaller than the original ones',
		'InvalidResource'   => 'Invalid image resource provided',
	);

	/**
	 * @var string
	 */
	protected $fileName = null;

	/**
	 * @var string
	 */
	protected $format = null;

	/**
	 * @var array
	 */
	protected $acceptedFormats = array('png','gif','jpeg');

	/**
	 * @var resource
	 */
	protected $sourceImage = null;

	/**
	 * @var resource
	 */
	protected $workingImage = null;

	/**
	 * @var array
	 */
	protected $originalSize = null;

	/**
     * @param string|null $fileName
     */
	public function __construct($fileName = null)
	{
        if(!is_null($fileName))
		    $this->setSourceImage($fileName);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @param int $mode
	 * @return void
	 */
	public function resize($width = null, $height = null, $mode = self::RESIZE_TYPE_CROP)
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		list($width, $height)   = $this->calcDefaultDimensions($width, $height);
        $cropAfter              = false;
        $cropDimensions         = array();

		if($mode != self::RESIZE_TYPE_STRICT){
			//recalculate width and height if they exceed original dimensions
			if($width > $this->originalSize[0] || $height > $this->originalSize[1]){
				$width = $this->originalSize[0];
				$height = $this->originalSize[1];
			}

			//reclaculate to preserve aspect ratio
			if($width/$height != $this->originalSize[0]/$this->originalSize[1]){
				//mark for cropping
				if($mode == self::RESIZE_TYPE_CROP){
					$cropAfter = true;
					$cropDimensions = array($width, $height);
				}

				if(
					($width/$this->originalSize[0] > $height/$this->originalSize[1] && $mode == self::RESIZE_TYPE_RATIO) ||
					($width/$this->originalSize[0] < $height/$this->originalSize[1] && $mode == self::RESIZE_TYPE_CROP)
				){
					$width = $height/$this->originalSize[1]*$this->originalSize[0];
				}else{
					$height = $width/$this->originalSize[0]*$this->originalSize[1];
				}
			}
		}

		//create new image
		$this->workingImage = $this->createImage($width, $height);

		//move the pixels from source to new image
		imagecopyresampled($this->workingImage, $this->sourceImage, 0, 0, 0, 0, $width, $height, $this->originalSize[0], $this->originalSize[1]);
		$this->replaceAndReset($width, $height);

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
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		if($width > $this->originalSize[0] || $height > $this->originalSize[1])
			$this->error('CropDimExceed');

		list($width, $height) = $this->calcDefaultDimensions($width, $height);

		//create new image
		$this->workingImage = $this->createImage($width, $height);

		//move the pixels from source to new image
		imagecopyresampled($this->workingImage, $this->sourceImage, 0, 0, $x, $y, $width, $height, $width, $height);
		$this->replaceAndReset($width, $height);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return void
	 */
	public function cropFromCenter($width, $height)
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		$x = (int)(($this->originalSize[0] - $width) / 2);
		$y = (int)(($this->originalSize[1] - $height) / 2);

		$this->crop($x, $y, $width, $height);
	}

    /**
     * @param int $offset
     */
    public function setWatermarkOffset($offset)
    {
        $this->watermarkOffset = (int)$offset;
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
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		$watermark = new Image($fileName);
		if($width || $height)
			$watermark->resize($width, $height, self::RESIZE_TYPE_STRICT);

		$this->workingImage = $this->createImage($this->originalSize[0], $this->originalSize[1]);
		imagealphablending($this->workingImage, true);

		switch($position){
			case self::WM_POS_TOP_LEFT:
		        $x = $y = $this->watermarkOffset;
		        break;
			case self::WM_POS_TOP_RIGHT:
				$x = $this->originalSize[0] - $watermark->getWidth() - $this->watermarkOffset;
		        $y = $this->watermarkOffset;
		        break;
			case self::WM_POS_BOTTOM_RIGHT:
		        $x = $this->originalSize[0] - $watermark->getWidth() - $this->watermarkOffset;
		        $y = $this->originalSize[1] - $watermark->getHeight() - $this->watermarkOffset;
		        break;
			case self::WM_POS_BOTTOM_LEFT:
				$x = $y = $this->watermarkOffset;
		        $y = $this->originalSize[1] - $watermark->getHeight() - $this->watermarkOffset;
		        break;
			case self::WM_POS_CENTER:
		        $x = ($this->originalSize[0] - $watermark->getWidth()) / 2;
		        $y = ($this->originalSize[1] - $watermark->getHeight()) / 2;
		        break;
            default:
                $x = 0;
                $y = 0;
                break;
		}

		imagecopy($this->workingImage, $this->sourceImage, 0, 0, 0, 0, $this->originalSize[0], $this->originalSize[1]);
		imagecopy($this->workingImage, $watermark->getSourceImage(), $x, $y, 0, 0, $watermark->getWidth(), $watermark->getHeight());

		$this->replaceAndReset($this->originalSize[0], $this->originalSize[1]);

		$watermark->destroy();
	}

	/**
	 * @return resource
	 */
	public function getSourceImage()
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		return $this->sourceImage;
	}

    /**
     * @param string $fileName
     * @return void
     */
    public function setSourceImage($fileName)
    {
        if(!function_exists('gd_info'))
            $this->error('GD');

        if(!file_exists($fileName))
            $this->error('NotExists', $fileName);

        if(!is_readable($fileName))
            $this->error('NotReadable', $fileName);

        $this->originalSize    = getimagesize($fileName);
        $this->format          = array_pop(explode('/', $this->originalSize['mime']));

        if(!in_array($this->format, $this->acceptedFormats))
            $this->error('Format', $this->format);

        $this->fileName        = $fileName;
        $this->sourceImage     = $this->createImageFromFile();
        $this->workingImage    = $this->createImageFromFile();
    }

	/**
	 * @return resource
	 */
	public function getWorkingImage()
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		return $this->workingImage;
	}

    /**
     * @param resource $image
     * @return void
     */
    public function setWorkingImage($image)
    {
        if(!is_resource($image))
            $this->error('InvalidResource');

        $this->workingImage = $image;
    }

	/**
	 * @return int
	 */
	public function getWidth()
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		return $this->originalSize[0];
	}

	/**
	 * @return int
	 */
	public function getHeight()
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		return $this->originalSize[1];
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	protected function calcDefaultDimensions($width = null, $height = null)
	{
		if(!$width && !$height)
			$this->error('WidthHeight');

		//autocalculate width and height if one of them is missing
		if(!$width)
			$width = $height/$this->originalSize[1]*$this->originalSize[0];

		if(!$height)
			$height = $width/$this->originalSize[0]*$this->originalSize[1];

		return array($width, $height);
	}

	/**
	 * @return resource
	 */
	protected function createImageFromFile()
	{
		$function = 'imagecreatefrom'.$this->format;
		return $function($this->fileName);
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return resource
	 */
	protected function createImage($width, $height)
	{
		$function = function_exists('imagecreatetruecolor') ? 'imagecreatetruecolor' : 'imagecreate';
		$image = $function($width, $height);

		//special conditions for png transparence
		if($this->format == 'png'){
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
	protected function replaceAndReset($width, $height)
	{
		$this->sourceImage = $this->createImage($width, $height);
		imagecopy($this->sourceImage, $this->workingImage, 0, 0, 0, 0, $width, $height);
		imagedestroy($this->workingImage);
		$this->workingImage = $this->sourceImage;

		$this->originalSize[0] = $width;
		$this->originalSize[1] = $height;
	}

	/**
	 * @throws ImageException
	 * @param string $code
	 * @param array $params
	 * @return void
	 */
	protected function error($code, $params = array())
	{
		if(!is_array($params))
			$params = array($params);

		throw new ImageException(vsprintf($this->errors[$code], $params));
	}

	/**
	 * @return void
	 */
	public function destroy()
	{
		if($this->workingImage)
			imagedestroy($this->workingImage);
		if($this->sourceImage)
			imagedestroy($this->sourceImage);
	}

	/**
	 * @param string $name
	 * @param int $expires in seconds
	 * @param int $lastMod in seconds
	 * @return void
	 */
	public function sendHeaders($name = '', $expires = 0, $lastMod = null)
	{
		header('Content-type: image/'.$this->format);
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
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		$this->sendHeaders($name);
		$this->execute(null, $quality);

		$this->destroy();
		die;
	}

	/**
     * @param null|string $fileName
     * @param int $quality
     * @return void
     */
	public function save($fileName = null, $quality = 100)
	{
        if(!$this->sourceImage)
            $this->error('NotLoaded');

		$this->execute($fileName ? $fileName : $this->fileName, $quality);
	}

	/**
	 * @param string $fileName
	 * @param int $quality
	 * @return void
	 */
	protected function execute($fileName = null, $quality = 75)
	{
		$function = 'image'.$this->format;
		$function($this->sourceImage, $fileName, $this->getQuality($quality));
	}

	/**
	 * @param int $quality
	 * @return int|null
	 */
	protected function getQuality($quality)
	{
		switch($this->format){
			case 'gif':
		        return null;
			case 'jpeg':
		        return $quality;
			case 'png':
		        return (int)($quality/10 - 1);
		}

        return null;
	}
}
