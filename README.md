PHP Image
=========

This class provides a simple way to resize, crop and add watermarks to images using the PHP GD library.
The source is written for PHP 5.3 using namespaces, but it is very easy to adapt the class to PHP < 5.3.

Theory of operation
-------------------

The class is instanciated with a filename as a parameter. That file is persistent, which means that all subsequent operations are applied to that file. So you can first crop it, then resize it without creating a new object.

$image = new Image('somefile.jpg');

Resizing works in 3 ways, depending on what mode is specified when executing resize():

1) RESIZE_TYPE_STRICT 	- resizes the image to the exact specified dimensions
2) RESIZE_TYPE_RATIO 	- takes into account the aspect ratio of the image and it will be resized to fit the specified dimensions
3) RESIZE_TYPE_CROP 	- First crops the image so that aspect ratio of the target size is met, then the cropped image is resized to the specified dimensions (default)
