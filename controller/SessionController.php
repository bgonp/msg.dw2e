<?php
/**
 * Class with static functions to handle sessions in order to login, logout or check if
 * there is a logged user and if he is admin or not.
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class SessionController {

	/**
	 * It starts a session if an started session doesn't exists. Then, it receives a
	 * user and save his user_id and admin property into $_SESSION superglobal.
	 * 
	 * @param User $user User to be logged
	 */
	public static function login($user) {
		self::start();
		$_SESSION['admin'] = boolval($user->admin());
		$_SESSION['user_id'] = intval($user->id());
	}

	/**
	 * It starts a session if an started session doesn't exists and destroys it,
	 * logging out the current user.
	 */
	public static function logout() {
		self::start();
		if (session_status() !== PHP_SESSION_ACTIVE) return;
		$_SESSION = [];
		setcookie(session_name(), "", time() - 3600);
		session_destroy();
	}

	/**
	 * Check if there is a logged in user and return his ID.
	 * 
	 * @return int|bool User ID if logged or false if not
	 */
	public static function logged(){
		self::start();
		return $_SESSION['user_id'] ?? false;
	}

	/**
	 * Check if there is a logged in user and return whetever he is admin or not.
	 * 
	 * @return bool True if logged user is admin or false if not
	 */
	public static function admin() {
		self::start();
		return $_SESSION['admin'] ?? false;
	}

	/**
	 * Start a session if there isn't an already opened one.
	 */
	private static function start() {
		if (session_status() === PHP_SESSION_NONE)
			session_start();
	}

}