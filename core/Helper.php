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

	public static function uploadAvatar($avatar) {
		if ($avatar['error'] || $avatar['size'] > Option::get('avatar_maxweight') * 1024 )
			return false;

		$extension = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));
		if (!in_array($extension, ['jpg','jpeg','png','gif']))
			return false;
		if ($extension == 'jpg') $extension = 'jpeg';

		do {
			$filename = self::randomString(16).'.'.$extension;
		} while (file_exists(AVATAR_DIR.$filename));

		$image = ('imagecreatefrom'.$extension)($avatar['tmp_name']);
		$width = imagesx($image);
		$height = imagesy($image);
		$sidelength = min($width, $height);
		$x = max(0, intval(($width-$sidelength)/2));
		$y = max(0, intval(($height-$sidelength)/2));
		if ($sidelength > 200) {
			$extension = 'jpeg';
			$resized = imagecreatetruecolor(200, 200);
			imagecopyresized($resized, $image, 0, 0, $x, $y, 200, 200, $sidelength, $sidelength);
		} else if ($height != $sidelength || $width != $sidelength){
			$resized = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $sidelength, 'height' => $sidelength]);
		} else {
			$resized = $image;
		}
		$quality = $extension == 'jpeg' ? 95 : $extension == 'png' ? -1 : null;
		if (!('image'.$extension)($resized, AVATAR_DIR.$filename, $quality))
			return false;

		return $filename;
	}

	public static function removeAvatar($filename) {
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
