<?php

class Helper {

	// Estados de contacto
	public const PENDIENTE = 1;
	public const ACEPTADO = 2;
	public const RECHAZADO = 3;
	public const BLOQUEADO = 4;

	public static function validNombre($nombre) {
		$pattern = "/^[a-zA-Z\s].{4,32}$/";
		return preg_match( $pattern, $nombre );
	}

	public static function validPassword($password) {
		$pattern = "/^(?=.*[0-9]+)(?=.*[A-Z]+)(?=.*[a-z]+)(?=.*[^a-zA-Z0-9]+).{6,16}$/";
		return preg_match( $pattern, $password );
	}

	public static function validEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public static function validTexto($texto) {
		return strlen($texto) <= 1000;
	}

	public static function uploadImagen($imagen) {
		if ($imagen['error'] || $imagen['size'] > 512 * 1024 )
			return false;

		$extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
		if (!in_array($extension, ['png','jpg','jpeg','gif']))
			return false;

		$size = getimagesize($imagen['tmp_name']);
		if ($size[0] > 500 || $size[1] > 500)
			return false;

		do {
			$filename = self::randomNombre().'.'.$extension;
		} while (file_exists(IMAGE_DIR.$filename));
		if (!move_uploaded_file($imagen['tmp_name'], IMAGE_DIR.$filename))
			return false;

		return $filename;
	}

	public static function removeImagen($imagen) {
		$pattern = "/^[a-zA-Z0-9]{16}\.(?:png|jpg|jpeg|gif)$/";
		if (!preg_match($pattern, $imagen) || !file_exists(IMAGE_DIR.$imagen)) return false;
		return unlink(IMAGE_DIR.$imagen);
	}

	private static function randomNombre() {
    	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$len = strlen($chars) - 1;
    	$result = '';
    	for ($i=0; $i < 16; $i++) $result .= $chars[rand(0, $len)];
    	return $result;
	}

}