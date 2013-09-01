<?php
/**
 * PHP Image
 *
 * @category    Bedeabza
 * @package	    Default
 * @author 	    Dragos Badea	<bedeabza@gmail.com>
 */

use \Bedeabza\Image;

class ImageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Image
     */
    protected $image = null;

    public function setUp()
    {
        $this->image = new Image($this->getFile('demo.jpg'));
    }

    public function tearDown()
    {
        $this->image = null;

        $saveFile = $this->getFile('save-test.jpg');
        if(file_exists($saveFile))
            unlink($saveFile);
    }

    protected function getFile($file)
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @test
     * @expectedException \Bedeabza\Image\Exception
     * @expectedExceptionMessage No image was loaded
     */
    public function throwExceptionWhenResizingEmptyImage()
    {
        $this->image = new Image();
        $this->image->resize(100);
    }

    /**
     * @test
     * @expectedException \Bedeabza\Image\Exception
     * @expectedExceptionMessage No image was loaded
     */
    public function throwExceptionWhenCroppingEmptyImage()
    {
        $this->image = new Image();
        $this->image->crop(100, 100);
    }

    /**
     * @test
     * @expectedException \Bedeabza\Image\Exception
     * @expectedExceptionMessage No image was loaded
     */
    public function throwExceptionWhenWatermarkingEmptyImage()
    {
        $this->image = new Image();
        $this->image->watermark($this->getFile('watermark.png'));
    }

    /**
     * @test
     */
    public function testStrictResize()
    {
        $this->image->resize(200, 200, \Bedeabza\Image::RESIZE_TYPE_STRICT);

        $res = $this->image->getWorkingImage();
        $this->assertEquals(imagesx($res), 200);
        $this->assertEquals(imagesy($res), 200);
    }

    /**
     * @test
     */
    public function testAspectRatioResize1()
    {
        $this->image->resize(200, null, \Bedeabza\Image::RESIZE_TYPE_RATIO);

        $res = $this->image->getWorkingImage();
        $this->assertEquals(imagesy($res), 125);
    }

    /**
     * @test
     */
    public function testAspectRatioResize2()
    {
        $this->image->resize(200, 100, \Bedeabza\Image::RESIZE_TYPE_RATIO);

        $res = $this->image->getWorkingImage();
        $this->assertEquals(imagesx($res), 160);
        $this->assertEquals(imagesy($res), 100);
    }

    /**
     * @test
     */
    public function testCropResize()
    {
        $this->image->resize(200, 200, \Bedeabza\Image::RESIZE_TYPE_CROP);

        $res = $this->image->getWorkingImage();
        $this->assertEquals(imagesx($res), 200);
        $this->assertEquals(imagesy($res), 200);
    }

    /**
     * @test
     * @expectedException \Bedeabza\Image\Exception
     */
    public function testWatermarkWithInvalidFile()
    {
        $this->image->watermark($this->getFile('unknown.png'));
    }

    /**
     * @test
     */
    public function testWatermark()
    {
        $this->image->watermark($this->getFile('watermark.png'));
    }

    /**
     * @test
     */
    public function testSaveToFile()
    {
        $filename = $this->getFile('save-test.jpg');

        $this->image->resize(200, 200);
        $this->image->save($filename);

        $size = getimagesize($filename);

        $this->assertEquals($size[0], 200);
        $this->assertEquals($size[1], 200);
    }
}
