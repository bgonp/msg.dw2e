<?php

class SessionController {

	private static $checked = false;
	private static $logged = false;

	public static function check() {
		if (!self::$checked) {
			self::$checked = true;
			session_start();
			self::$logged = !empty($_SESSION['logged']);
		}
		return self::$logged;
	}

	public static function usuarioId(){
		return $_SESSION['usuarioId'];
	}

	public static function logged( $usuario ) {
		session_start();
		$_SESSION['logged'] = true;
		$_SESSION['usuarioId'] = $usuario->id();
	}

	public static function logout() {
		session_start();
		$_SESSION = [];
		setcookie(session_name(), "", time() - 3600);
		session_destroy();
	}

}