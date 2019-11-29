<?php
/**
 * This file is the endpoint for ajax calls. It imports all the filesystem through the
 * init.php file and then calls the main controller to process the request.
 * 
 * @package public
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */

require_once "../init.php";

MainController::ajax();
