<?php
/**
 * Bootstrap file for the demo
 *
 * Initializes error reporting and include path. Also includes the Image class
 */

error_reporting(E_ALL^E_NOTICE);
ini_set('display_errors', 'On');

set_include_path(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . PATH_SEPARATOR . get_include_path());
require_once('Bedeabza' . DIRECTORY_SEPARATOR . 'Image.php');

