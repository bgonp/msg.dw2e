<?php

class SessionController {

	private static $checked = false;
	private static $logged = false;
	private static $admin = false;

	public static function check() {
		if (!self::$checked && session_status() === PHP_SESSION_NONE) {
			self::$checked = true;
			session_start();
			self::$logged = !empty($_SESSION['logged']);
			self::$admin = !empty($_SESSION['admin']);
		}
		return self::$logged;
	}

	public static function checkAdmin() {
		self::check();
		return self::$admin;
	}

	public static function userId(){
		return $_SESSION['userId'];
	}

	public static function logged( $user, $admin = false ) {
		$_SESSION['logged'] = true;
		$_SESSION['admin'] = boolval($admin);
		$_SESSION['userId'] = $user->id();
	}

	public static function logout() {
		if (session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION = [];
			setcookie(session_name(), "", time() - 3600);
			session_destroy();
		}
	}

}