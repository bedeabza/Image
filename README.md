PHP Image
=========

This class provides a simple way to resize, crop and add watermarks to images using the PHP GD library.
The source is written for PHP 5.3 using namespaces, but it is very easy to adapt the class to PHP < 5.3.

Theory of operation
-------------------

The class is instantiated with a filename as a parameter. That file is persistent, which means that all subsequent operations are applied to that file. So you can first crop it, then resize it without creating a new object.

```php
$image = new Image('somefile.jpg');
```
    
Resizing
-------------------

Resizing works in 3 ways, depending on what mode is specified when executing resize():

* RESIZE_TYPE_STRICT - resizes the image to the exact specified dimensions
* RESIZE_TYPE_RATIO - takes into account the aspect ratio of the image and it will be resized to fit the specified dimensions
* RESIZE_TYPE_CROP - First crops the image so that aspect ratio of the target size is met, then the cropped image is resized to the specified dimensions (default)

Resizing examples:

```php
$image->resize(200, 200); //will crop the image to fit the aspect ratio and then resize
$image->resize(200, null, Image::RESIZE_TYPE_RATIO); //will resize so that width is 200 keeping original a/r
$image->resize(200, 200, Image::RESIZE_TYPE_STRICT); //will schew the image the the a/r is not 1:1
```

Cropping
-------------------

```php
$image->crop($x, $y, $width, $height); //$width and $height are optional
$image->cropFromCenter($width, $height);
```

Watermark
-------------------

```php
$image->watermark($watermarkFile); //will add the watermark to the bottom right corner of the image with the original dimensions
$image->watermark($watermarkFile, Image::WM_POS_TOP_LEFT, 50, 50); //also specifies the position of the watermark and the resize dimensions
```

Possible positions for watermarks are:

* WM_POS_TOP_LEFT
* WM_POS_TOP_RIGHT
* WM_POS_BOTTOM_LEFT
* WM_POS_BOTTOM_RIGHT
* WM_POS_CENTER

Rendering and Saving
--------------------

```php
$image->render(); //will render the image on screen
$image->save(); //saves the image replacing the original file
$image->save($filename, $quality); //saves the image in the specified $filename with the specified quality
```

Get in touch
-------------------

You can contact me at bedeabza at gmail dot com or send pull requests for the repo.