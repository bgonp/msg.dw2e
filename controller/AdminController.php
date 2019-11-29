<?php
/**
 * Trait that groups all admin related actions to be used by main controller.
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
trait AdminController {

	/**
	 * This method update options in database. Requires user to be admin.
	 * 
	 * Post info needed to perform this action is:
	 * <li>options - Associative array with options to be updated
	 * 
	 * @param  array $post Contains needed key-value pairs to be updated
	 * @param  array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function updateOptions($post, $files) {
		if (empty($post['options'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!SessionController::admin()) {
			$response = ['type' => 'error', 'message' => Text::error('permission')];
		} else {
			foreach ($post['options'] as $key => $value)
				Option::update($key, $value);
			if (Option::get('email_confirm') && !MailController::test())
				$response = ['type' => 'error', 'message' => Text::error('email_config')];
			else
				$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}

	/**
	 * This method calls Install class to install all the environment needed to
	 * use the application.
	 * 
	 * Post info needed to perform this action is:
	 * <li>usr - Associative array with admin data to create the user
	 * <li>db - Associative array with configuration data to be stored in a file
	 * 
	 * @param  array $post Contains needed key-value pairs to configure the app
	 * @param  array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function installApp($post, $files) {
		if (Database::connect() || Install::run($post))
			$response = ['redirect' => Helper::currentUrl()];
		else
			$response = ['type' => 'error', 'message' => Text::error('installation')];
		return $response;
	}
	
}