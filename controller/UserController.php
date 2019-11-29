<?php
/**
 * Trait that groups all user related actions to be used by main controller.
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
trait UserController {

	/**
	 * Show reset password form. If passed code doesn't match with user ID it will
	 * show error page.
	 * 
	 * Get info needed to perform this action is:
	 * <li>id - User ID which password will be changed
	 * <li>key - Key string to check if match with user code
	 * 
	 * @param array $get Contains needed key-value pairs to be used
	 */
	private static function recover($get) {
		if (!empty($get['id']) && !empty($get['key'])) {
			$user = User::get($get['id']);
			if ($user->checkCode($get['key'])) {
				View::recover($user, $get['key'], Option::get());
				return;
			}
		}
		View::error(Text::error('key_check'), Option::get());
	}
	
	/**
	 * Confirm a user account and redirect to login page.
	 * If passed code doesn't match with user ID it will show error page.
	 * 
	 * Get info needed to perform this action is:
	 * <li>id - User ID which password will be changed
	 * <li>key - Key string to check if match with user code
	 * 
	 * @param array $get Contains needed key-value pairs to be used
	 */
	private static function confirm($get) {
		if (!empty($get['id']) && !empty($get['key'])) {
			$user = User::get($get['id']);
			if ($user->checkCode($get['key'])) {
				$user->confirm();
				header('Location: '.Helper::currentUrl());
				die();
			}
		}
		View::error(Text::error('user_confirm'), Option::get());
	}

	/**
	 * Try to log in. It receives login user info and if it's correct the response will
	 * ask the client to refresh the page once SessionController::login was called.
	 * 
	 * Post info needed to perform this action is:
	 * <li>email - User email which password will be checked
	 * <li>password - User password to be checked
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return array Array that contains result of the operation
	 */
	private static function login($post, $files) {
		if (empty($post['email']) || empty($post['password'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get($post['email'], $post['password']);
			SessionController::login($user);
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}

	/**
	 * Handle logout request, destroy the session.
	 * 
	 * @param array $post This param is not used
	 * @param array $files This param is not used
	 * @return array Array that contains result of the operation
	 */
	private static function logout($post, $files) {
		SessionController::logout();
		return ['redirect' => Helper::currentUrl()];
	}

	/**
	 * Register a new user. Check if all data is correct and then try to store new user
	 * in database. If email confirmation option is enabled, it will send the email and
	 * user will be unable to log in until he confirm his account. If this option is not
	 * enabled, it will autologin after user creation.
	 * 
	 * Post info needed to perform this action is:
	 * <li>name - User display name. Must match the stored name regexp
	 * <li>email - User email. Must match the stored email regexp
	 * <li>password - User password. Must match the stored password regexp
	 * <li>password_rep - User password confirmation
	 * 
	 * Optional file can be uploaded through $files param
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param can contain an array of file info to be processed
	 * @return array Array that contains result of the operation
	 */
	private static function register($post, $files) {
		if (empty($post['email']) || empty($post['name']) || empty($post['password']) || empty($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Text::error('pass_diff')];
		} else {
			if (Option::get('email_confirm')) {
				$user = User::create($post['email'], $post['name'], $post['password'], $files['avatar']);
				$email = View::emailConfirm($user);
				if (MailController::send("Confirm your account", $email, $user->email())) {
					$response = ['type' => 'success', 'message' => Text::success('confirmation_sent')];
				} else {
					$user->delete();
					$response = ['type' => 'error', 'message' => Text::error('email_error')];
				}
			} else {
				$user = User::create($post['email'], $post['name'], $post['password'], $files['avatar'], 1);
				SessionController::login($user);
				$response = ['redirect' => Helper::currentUrl()];
			}
		}
		return $response;
	}

	/**
	 * Send reset password email. It checks if confirmation option is enabled and if passed
	 * email belongs to a user. Then, it send an email with a unique link to reset the user
	 * password. This links contains as GET data the user ID and a just generated validation
	 * code.
	 * 
	 * Post info needed to perform this action is:
	 * <li>email - User email who wants to reset his password
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return array Array that contains result of the operation
	 */
	private static function resetSend($post, $files) {
		if (!Option::get('email_confirm')) {
			$response = ['type' => 'error', 'message' => Text::error('conf_error')];
		} if (empty($post['email'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!($user = User::get($post['email']))) {
			$response = ['type' => 'error', 'message' => Text::error('user_wrong')];
		} else {
			$email = View::emailReset($user);
			if (MailController::send("Reset your password", $email, $user->email()))
				$response = ['type' => 'success', 'message' => Text::success('recover_sent')];
			else
				$response = ['type' => 'error', 'message' => Text::error('email_error')];
		}
		return $response;
	}

	/**
	 * Set a new user password. It checks if user ID and reset code are valid and if the
	 * password match the stored regexp. If everything is correct, it resets user
	 * password.
	 * 
	 * Post info needed to perform this action is:
	 * <li>id - User ID which password will be changed
	 * <li>key - User reset code to be checked
	 * <li>password - New user password
	 * <li>password_rep - New user password confirmation
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return array Array that contains result of the operation
	 */
	private static function resetPassword($post, $files) {
		if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Text::error('pass_diff')];
		} else if (!($user = User::get($post['id']))) {
			$response = ['type' => 'error', 'message' => Text::error('user_wrong')];
		} else if (!$user->checkCode($post['key'])) {
			$response = ['type' => 'error', 'message' => Text::error('key_check')];
		} else if (!$user->password($post['password'])) {
			$response = ['type' => 'error', 'message' => Text::error('pass_wrong')];
		} else {
			$user->removeCode();
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}

	/**
	 * Edit logged in user profile data. It checks if all the data is correct and if it's
	 * different from stored one. If everything is ok, update the user profile. If email
	 * confirmation option is enabled and new email address was passed, the user will
	 * receive a notice of new email confirmation needed. Once he logs out, it won't be
	 * able to log in againt until he re-confirm his account.
	 * 
	 * Post info to perform this action is:
	 * <li>name - User new (or not) name
	 * <li>email - User new (or not) email
	 * <li>password - User new password. If empty, password won't be changed
	 * <li>password_rep - User new password confirmation
	 * 
	 * Optional avatar imgage can be uploaded through $files param.
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param can contain an array of file info to be processed
	 * @return array Array that contains result of the operation
	 */
	private static function editProfile($post, $files) {
		if (empty($post['email']) || empty($post['name']) || !isset($post['password']) || !isset($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!empty($post['password']) && $post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Text::error('pass_diff')];
		} else {
			$user = User::get(SessionController::logged());
			$edited = $new_email = false;
			$note = '';
			if ($post['email'] != $user->email())
				$new_email = $edited = $user->email($post['email']);
			if ($post['name'] != $user->name())
				$edited = $user->name($post['name']) || $edited;
			if (!empty($post['password']))
				$edited = $user->password($post['password']) || $edited;
			if ($files['avatar']['error'] != 4)
				$edited = $user->avatar($files['avatar']) || $edited;
			if ($new_email && Option::get('email_confirm')) {
				$email = View::emailConfirm($user);
				if (MailController::send("Confirm your new e-mail", $email, $user->email())) {
					$user->confirmed(0);
					$note = '. '.Text::success('confirmation_needed').'.';
				}
			}
			if ($edited && $user->save())
				$response = ['type' => 'success', 'message' => Text::success('updated_profile').$note, 'userdata' => $user];
			else
				$response = ['type' => 'error', 'message' => Text::error('profile_save')];
		}
		return $response;
	}

}