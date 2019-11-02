<?php

define("VENDOR_DIR", __DIR__.'/vendor/');
define("CORE_DIR", __DIR__.'/core/');
define("CONFIG_DIR", __DIR__.'/config/');
define("MODEL_DIR", __DIR__.'/model/');
define("VIEW_DIR", __DIR__.'/view/');
define("HTML_DIR", VIEW_DIR.'html/');
define("CONTROLLER_DIR", __DIR__.'/controller/');
define("IMAGE_DIR", __DIR__.'/upload/');

require_once VENDOR_DIR."autoload.php";
require_once CORE_DIR."Helper.php";
require_once CORE_DIR."Database.php";
require_once MODEL_DIR."Mensaje.php";
require_once MODEL_DIR."Usuario.php";
require_once MODEL_DIR."Chat.php";
require_once VIEW_DIR."View.php";
require_once CONTROLLER_DIR."MainController.php";
require_once CONTROLLER_DIR."SessionController.php";
require_once CONTROLLER_DIR."MailController.php";
