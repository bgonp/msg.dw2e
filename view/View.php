<?php

abstract class View {

	// ------------------------
	// Pages
	// ------------------------
	public static function main($usuario) {
		$contenido = self::menu($usuario);
		$contenido .= self::sidebar($usuario->chats(), $usuario->amigos(), $usuario->pendientes());
		$contenido .= self::mensajes();
		$contenido .= self::alert();
		$contenido .= self::loading();
		$contenido .= self::vars($usuario->id(), $usuario->amigos_last(), $usuario->pendientes_last());
		echo self::page($contenido,'main');
	}

	public static function login() {
		$contenido = self::loginForm();
		$contenido .= self::alert();
		$contenido .= self::loading();
		echo self::page($contenido,'login');
	}

	public static function recover($usuario, $clave) {
		$contenido = self::recoverForm($usuario, $clave);
		$contenido .= self::alert();
		$contenido .= self::loading();
		echo self::page($contenido,'recover');
	}
	
	public static function error($mensaje) {
		$contenido = self::errorMessage($mensaje);
		echo self::page($contenido, 'error');
	}

	// ------------------------
	// E-mails
	// ------------------------
	public static function emailConfirm($usuario) {
		$replace = [
			'{{ID}}' => $usuario->id(),
			'{{NOMBRE}}' => $usuario->nombre(),
			'{{CLAVE}}' => $usuario->getNewClave()
		];
		$contenido = strtr(file_get_contents(HTML_DIR.'email/confirm.html'), $replace);
		return self::email($contenido,'Confirm your account');
	}

	public static function emailReset($usuario) {
		$replace = [
			'{{ID}}' => $usuario->id(),
			'{{NOMBRE}}' => $usuario->nombre(),
			'{{CLAVE}}' => $usuario->getNewClave()
		];
		$contenido = strtr(file_get_contents(HTML_DIR.'email/recover.html'), $replace);
		return self::email($contenido,'Reset your password');
	}

	// ------------------------
	// Private functions
	// ------------------------
	private static function email($contenido, $titulo) {
		$replace = [
			'{{CONTENIDO}}' => $contenido,
			'{{TITULO}}' => $titulo
		];
		return strtr(file_get_contents(HTML_DIR.'email/email.html'), $replace);
	}

	private static function page($contenido, $clase) {
		$replace = [
			'{{CONTENIDO}}' => $contenido,
			'{{CLASE}}' => $clase
		];
		return strtr(file_get_contents(HTML_DIR.'page.html'), $replace);
	}

	private static function recoverForm($usuario ,$clave) {
		$replace = [
			'{{ID}}' => $usuario->id(),
			'{{CLAVE}}' => $clave
		];
		return strtr(file_get_contents(HTML_DIR.'recover.html'), $replace);
	}

	private static function loginForm() {
		return file_get_contents(HTML_DIR.'login.html');
	}
	
	private static function menu($usuario) {
		$replace = [
			'{{ID}}' => $usuario->id(),
			'{{EMAIL}}' => $usuario->email(),
			'{{NOMBRE}}' => $usuario->nombre()
		];
		return strtr(file_get_contents(HTML_DIR.'menu.html'), $replace);
	}
	
	private static function sidebar($chats, $amigos, $pendientes) {
		$replace = [
			'{{CHATS}}' => "",
			'{{AMIGOS}}' => "",
			'{{PENDIENTES}}' => ""
		];
		foreach ($chats as $chat)
			$replace['{{CHATS}}'] .= self::chat($chat);
		foreach ($amigos as $amigo)
			$replace['{{AMIGOS}}'] .= self::amigo($amigo);
		foreach ($pendientes as $pendiente)
			$replace['{{PENDIENTES}}'] .= self::pendiente($pendiente);
		return strtr(file_get_contents(HTML_DIR.'sidebar.html'), $replace);
	}
	
	private static function chat($chat) {
		$replace = [
			'{{ID}}' => $chat->id(),
			'{{NOMBRE}}' => $chat->nombre(),
			'{{CLASE}}' => $chat->unread() ? ' unread' : ''
		];
		return strtr(file_get_contents(HTML_DIR.'chat.html'), $replace);
	}
	
	private static function amigo($amigo) {
		$replace = [
			'{{ID}}' => $amigo->id(),
			'{{NOMBRE}}' => $amigo->nombre(),
			'{{EMAIL}}' => $amigo->email()
		];
		return strtr(file_get_contents(HTML_DIR.'amigo.html'), $replace);
	}
	
	private static function pendiente($pendiente) {
		$replace = [
			'{{ID}}' => $pendiente->id(),
			'{{NOMBRE}}' => $pendiente->nombre(),
			'{{EMAIL}}' => $pendiente->email()
		];
		return strtr(file_get_contents(HTML_DIR.'pendiente.html'), $replace);
	}

	private static function mensajes() {
		return file_get_contents(HTML_DIR.'mensajes.html');
	}

	private static function alert() {
		return file_get_contents(HTML_DIR.'alert.html');
	}

	private static function loading() {
		return file_get_contents(HTML_DIR.'loading.html');
	}

	private static function vars($usuario_id, $amigo, $pendiente) {
		$replace = [
			'{{ID}}' => $usuario_id,
			'{{AMIGO}}' => $amigo,
			'{{PENDIENTE}}' => $pendiente
		];
		return strtr(file_get_contents(HTML_DIR.'vars.html'), $replace);
	}

	private static function errorMessage($mensaje) {
		$replace = [
			'{{MENSAJE}}' => $mensaje
		];
		return strtr(file_get_contents(HTML_DIR.'error.html'), $replace);
	}

}