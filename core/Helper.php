<?php

class Helper {

	// States de contact
	public const WAITING = 1;
	public const ACCEPTED = 2;
	public const DECLINED = 3;
	public const BLOCKED = 4;

	public static function validName($name) {
		$pattern = '/'.Option::get('regex_name').'/';
		return preg_match($pattern, $name);
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

	public static function removeImagen($filename) {
		$pattern = "/^[a-zA-Z0-9]{16}\.(?:png|jpg|jpeg|gif)$/";
		if (!preg_match($pattern, $filename) || !file_exists(AVATAR_DIR.$filename)) return false;
		return unlink(AVATAR_DIR.$filename);
	}

	public static function uploadAttachment($attachment) {
		if ($attachment['error'] || $attachment['size'] > Option::get('attachment_maxweight') * 1024 )
			return false;
		while (file_exists(ATTACHMENT_DIR.$attachment['name']))
			$attachment['name'] = 'cp_'.$attachment['name'];
		if (!move_uploaded_file($attachment['tmp_name'], ATTACHMENT_DIR.$attachment['name']))
			return false;
		if (explode('/', $attachment['type'])[0] != 'image') {
			$fileinfo = [
				'name' => $attachment['name'],
				'width' => 0,
				'height' => 0
			];
		} else {
			$imagesize = getimagesize(ATTACHMENT_DIR.$attachment['name']);
			$fileinfo = [
				'name' => $attachment['name'],
				'width' => $imagesize[0],
				'height' => $imagesize[1]
			];
		}
		return $fileinfo;
	}

	public static function removeAttachment($filename) {
		if (!file_exists(ATTACHMENT_DIR.$filename)) return false;
		return unlink(ATTACHMENT_DIR.$filename);
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

}
