<?php

abstract class View {

	public static function main($usuario) {
		$contenido = self::menu($usuario);
		$contenido .= self::sidebar($usuario->chats(), $usuario->amigos(), $usuario->pendientes());
		$contenido .= self::mensajes();
		$contenido .= self::alert();
		echo self::page($contenido,'main');
	}

	public static function login(){
		$contenido = file_get_contents(HTML_DIR.'login.html');
		$contenido .= self::alert();
		echo self::page($contenido,'login');
	}
	
	public static function error($mensaje) {
		$replace = ['{{MENSAJE}}' => $mensaje];
		$contenido = strtr(file_get_contents(HTML_DIR.'error.html'), $replace);
		echo self::page($contenido);
	}

	private static function page($contenido, $clase) {
		$replace = [
			'{{CONTENIDO}}' => $contenido,
			'{{CLASE}}' => $clase
		];
		return strtr(file_get_contents(HTML_DIR.'page.html'), $replace);
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
			'{{NOMBRE}}' => $chat->nombre()
		];
		return strtr(file_get_contents(HTML_DIR.'chat.html'), $replace);
	}
	
	private static function amigo($amigo) {
		$replace = [
			'{{ID}}' => $amigo->id(),
			'{{NOMBRE}}' => $amigo->nombre()
		];
		return strtr(file_get_contents(HTML_DIR.'amigo.html'), $replace);
	}
	
	private static function pendiente($pendiente) {
		$replace = [
			'{{ID}}' => $pendiente->id(),
			'{{NOMBRE}}' => $pendiente->nombre()
		];
		return strtr(file_get_contents(HTML_DIR.'pendiente.html'), $replace);
	}

	private static function mensajes() {
		return file_get_contents(HTML_DIR.'mensajes.html');
	}

	private static function alert() {
		return file_get_contents(HTML_DIR.'alert.html');
	}

}