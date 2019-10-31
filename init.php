<?php

define("VENDOR_DIR", __DIR__.'/vendor/');
define("CONFIG_DIR", __DIR__.'/conf/');
define("MODEL_DIR", __DIR__.'/model/');
define("VIEW_DIR", __DIR__.'/view/');
define("HTML_DIR", VIEW_DIR.'html/');
define("CONTROLLER_DIR", __DIR__.'/controller/');

require_once VENDOR_DIR."autoload.php";
require_once MODEL_DIR."Database.php";
require_once MODEL_DIR."Mensaje.php";
require_once MODEL_DIR."Usuario.php";
require_once MODEL_DIR."Chat.php";
require_once VIEW_DIR."View.php";
require_once CONTROLLER_DIR."MainController.php";
require_once CONTROLLER_DIR."SessionController.php";
require_once CONTROLLER_DIR."MailController.php";
