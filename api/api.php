<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
/**
 * @author Hussein Alwesi
 * @copyright 2017
 */
//  if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
//     $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//     header('HTTP/1.1 301 Moved Permanently');
//     header('Location: ' . $redirect);
//     exit();
// }
 date_default_timezone_set('Asia/Amman');
 include("config.php");
 include("include/lib.php");
 include("include/func.php");
 include("include/mainController.php");
 $melhem = new melhem();
 $className = $melhem->getSecureParams("type");
 $melhem->getControllerHandler();
 $melhem->newClassObject();
?>
