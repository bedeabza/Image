<?php
require_once('Bootstrap.php');

use \Bedeabza\Image;

$image = new Image(dirname(__FILE__).'/images/demo.jpg');
$image->resize(200, 200, Image::RESIZE_TYPE_CROP);
$image->watermark(dirname(__FILE__).'/images/watermark.png', Image::WM_POS_CENTER);
$image->render();
