<?php
/**
 * Main demo file
 * 
 * This example illustrates a resize procedure with auto cropping, as well as adding a watermark to the image.
 * Then the image is rendered
 */
require_once('Bootstrap.php');

$image = new \Bedeabza\Image(dirname(__FILE__).'/images/demo.jpg');
$image->resize(200, 200, \Bedeabza\Image::RESIZE_TYPE_CROP);
$image->watermark(dirname(__FILE__).'/images/watermark.png', \Bedeabza\Image::WM_POS_CENTER);
$image->render();
