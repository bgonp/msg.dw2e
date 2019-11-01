<?php

abstract class View {

	public static function main($usuario) {
		$contenido = self::menu($usuario);
		$contenido .= self::chats($usuario->chats());
		$contenido .= self::contactos($usuario->contactos());
		$contenido .= file_get_contents(HTML_DIR.'mensajes.html');
		echo self::page($contenido);
	}

	public static function login(){
		$contenido = file_get_contents(HTML_DIR.'login.html');
		echo self::page($contenido);
	}
	
	public static function error($mensaje) {
		$replace = ['{{MENSAJE}}' => $mensaje];
		$contenido = strtr(file_get_contents(HTML_DIR.'error.html'), $replace);
		echo self::page($contenido);
	}

	private static function page($contenido) {
		$replace = ['{{CONTENIDO}}' => $contenido];
		return strtr(file_get_contents(HTML_DIR.'page.html'), $replace);
	}
	
	private static function menu($usuario) {
		$replace = [
			'{{USUARIO}}' => $usuario->id(),
			'{{NOMBRE}}' => $usuario->nombre(),
			'{{AVATAR}}' => $usuario->avatar()
		];
		return strtr(file_get_contents(HTML_DIR.'menu.html'), $replace);
	}
	
	private static function chats($chats) {
		$replace = ['{{CHATS}}' => ""];
		foreach ($chats as $chat) {
			$replace['{{CHATS}}'] .= self::chat($chat);
		}
		if ($replace['{{CHATS}}'] == "") $replace['{{CHATS}}'] = "No chats yet...";
		return strtr(file_get_contents(HTML_DIR.'chats.html'), $replace);
	}
	
	private static function chat($chat) {
		$replace = [
			'{{CHAT}}' => $chat->id(),
			'{{NOMBRE}}' => $chat->nombre()
		];
		return strtr(file_get_contents(HTML_DIR.'chat.html'), $replace);
	}
	
	private static function contactos($contactos) {
		$replace = ['{{CONTACTOS}}' => ""];
		foreach ($contactos as $contacto) {
			$replace['{{CONTACTOS}}'] .= self::contacto($contacto);
		}
		if ($replace['{{CONTACTOS}}'] == "") $replace['{{CONTACTOS}}'] = "No friends yet...";
		return strtr(file_get_contents(HTML_DIR.'contactos.html'), $replace);
	}
	
	private static function contacto($contacto) {
		$replace = [
			'{{CONTACTO}}' => $contacto->id(),
			'{{NOMBRE}}' => $contacto->nombre()
		];
		return strtr(file_get_contents(HTML_DIR.'contacto.html'), $replace);
	}

}