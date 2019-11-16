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

	public static function usuarioId(){
		return $_SESSION['usuarioId'];
	}

	public static function logged( $usuario, $admin = false ) {
		$_SESSION['logged'] = true;
		$_SESSION['admin'] = boolval($admin);
		$_SESSION['usuarioId'] = $usuario->id();
	}

	public static function logout() {
		if (session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION = [];
			setcookie(session_name(), "", time() - 3600);
			session_destroy();
		}
	}

}