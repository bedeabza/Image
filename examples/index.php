<?php
require_once('Bootstrap.php');

$image = new \Bedeabza\Image(dirname(__FILE__).'/images/demo.jpg');
$image->resize(200, 200, \Bedeabza\Image::RESIZE_TYPE_CROP);
$image->watermark(dirname(__FILE__).'/images/watermark.png', \Bedeabza\Image::WM_POS_CENTER);
$image->render();
