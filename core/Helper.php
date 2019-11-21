<?php

class Helper {

	// Estados de contacto
	public const PENDIENTE = 1;
	public const ACEPTADO = 2;
	public const RECHAZADO = 3;
	public const BLOQUEADO = 4;

	public static function validNombre($nombre) {
		$pattern = '/'.Option::get('regex_name').'/';
		return preg_match($pattern, $nombre);
	}

	public static function validPassword($password) {
		$pattern = '/'.Option::get('regex_password').'/';
		return preg_match($pattern, $password);
	}

	public static function validEmail($email) {
		$pattern = '/'.Option::get('regex_email').'/';
		return $pattern ? preg_match($pattern, $email) : filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public static function validTexto($texto) {
		return strlen($texto) <= 1000;
	}

	public static function uploadImagen($imagen) {
		if ($imagen['error'] || $imagen['size'] > Option::get('image_maxweight') * 1024 )
			return false;

		$extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
		if (!in_array($extension, ['png','jpg','jpeg','gif']))
			return false;

		$size = getimagesize($imagen['tmp_name']);
		if ($size[0] > 1000 || $size[1] > 1000)
			return false;

		do {
			$filename = self::randomString(16).'.'.$extension;
		} while (file_exists(AVATAR_DIR.$filename));
		if (!move_uploaded_file($imagen['tmp_name'], AVATAR_DIR.$filename))
			return false;

		return $filename;
	}

	public static function uploadAttachment($attachment) {
		return move_uploaded_file($attachment['tmp_name'], ATTACHMENT_DIR.$attachment['name']);
	}

	public static function removeImagen($imagen) {
		$pattern = "/^[a-zA-Z0-9]{16}\.(?:png|jpg|jpeg|gif)$/";
		if (!preg_match($pattern, $imagen) || !file_exists(AVATAR_DIR.$imagen)) return false;
		return unlink(AVATAR_DIR.$imagen);
	}

	public static function randomString($length) {
    	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$len = strlen($chars) - 1;
    	$result = '';
    	for ($i = 0; $i < $length; $i++) $result .= $chars[rand(0, $len)];
    	return $result;
	}

	public static function currentUrl() {
		$url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		$url .= '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		return dirname($url);
	}

	public static function error($error) {
		switch ($error) {
			case 'invalid_action':
				return 'Operación no válida'; break;
			case 'missing_data':
				return 'Falta información'; break;
			case 'pass_diff':
				return 'Las contraseñas no coinciden'; break;
			case 'pass_wrong':
				return 'Autentificación incorrecta'; break;
			case 'user_confirm':
				return 'No se pudo verificar usuario'; break;
			case 'user_wrong':
				return 'Usuario incorrecto'; break;
			case 'key_check':
				return 'No se pudo verificar el enlace, quizás han pasado más de 24 horas'; break;
			case 'file_size':
				return 'La imagen es mayor que el límite establecido (1000x1000)'; break;
			case 'file_weight':
				return 'La imagen pesa más que el límite establecido (512KB)'; break;
			case 'chat_name':
				return 'Falta nombre de chat'; break;
			case 'chat_member':
				return 'Selecciona al menos un amigo'; break;
			case 'chat_add':
				return 'Alguien no se pudo agregar al chat'; break;
			case 'chat_wrong':
				return 'Chat incorrecto'; break;
			case 'msg_add':
				return 'No se pudo enviar el mensaje'; break;
			case 'msg_wrong':
				return 'El texto no puede contener más de 1000 caracteres'; break;
			case 'no_friend':
				return 'Alguien no es tu amigo'; break;
			default:
				return "Ocurrió un error inesperado: $error";
		}
	}

}