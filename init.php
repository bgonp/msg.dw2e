<?php
/**
 * This file define all the constants to be used in the applicacion and
 * then require all the files needed to load the whole environment.
 * 
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */

define("VENDOR_DIR", __DIR__.'/vendor/');
define("CORE_DIR", __DIR__.'/core/');
define("CONFIG_DIR", __DIR__.'/config/');
define("MODEL_DIR", __DIR__.'/model/');
define("VIEW_DIR", __DIR__.'/view/');
define("HTML_DIR", VIEW_DIR.'html/');
define("CONTROLLER_DIR", __DIR__.'/controller/');
define("AVATAR_DIR", __DIR__.'/upload/avatar/');
define("ATTACHMENT_DIR", __DIR__.'/upload/attachment/');

require_once VENDOR_DIR."autoload.php";
require_once CORE_DIR."Install.php";
require_once CORE_DIR."Database.php";
require_once CORE_DIR."Helper.php";
require_once CORE_DIR."Text.php";
require_once MODEL_DIR."Message.php";
require_once MODEL_DIR."User.php";
require_once MODEL_DIR."Chat.php";
require_once MODEL_DIR."Option.php";
require_once MODEL_DIR."Attachment.php";
require_once VIEW_DIR."View.php";
require_once CONTROLLER_DIR."SessionController.php";
require_once CONTROLLER_DIR."MailController.php";
require_once CONTROLLER_DIR."AdminController.php";
require_once CONTROLLER_DIR."ChatController.php";
require_once CONTROLLER_DIR."FriendController.php";
require_once CONTROLLER_DIR."UserController.php";
require_once CONTROLLER_DIR."MainController.php";
