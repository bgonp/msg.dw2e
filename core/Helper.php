<?php
/**
 * Class with useful static functions for several uses.
 * 
 * @package core
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class Helper {

	/** Friendship state: waiting for accept or decline */
	const WAITING = 1;
	/** Friendship state: accepted, they are now friends */
	const ACCEPTED = 2;
	/** Friendship state: declined, they aren't friends */
	const DECLINED = 3;
	/** Friendship state: blocked, they aren't friends anymore */
	const BLOCKED = 4;

	/**
	 * Apply the stored regular expression to a name and returns if it matches.
	 * 
	 * @param string $name Name to be checked
	 * @return bool If name matches regex
	 */
	public static function validName($name) {
		$pattern = '/^'.Option::get('regex_name').'$/';
		return boolval(preg_match($pattern, $name));
	}

	/**
	 * Apply the stored regular expression to a password and returns if it matches.
	 * 
	 * @param string $password Password to be checked
	 * @return bool If password matches regex
	 */
	public static function validPassword($password) {
		$pattern = '/^'.Option::get('regex_password').'$/';
		return boolval(preg_match($pattern, $password));
	}

	/**
	 * Apply the stored regular expression to a email and returns if it matches.
	 * 
	 * @param string $email Email to be checked
	 * @return bool If email matches regex
	 */
	public static function validEmail($email) {
		$pattern = '/^'.Option::get('regex_email').'$/';
		return boolval(preg_match($pattern, $email));
	}


	/**
	 * Check if a text is valid to be stored.
	 * 
	 * @param string $text Text to be checked
	 * @return bool If text is valid
	 */
	public static function validText($text) {
		return strlen($text) <= 1000;
	}

	/**
	 * Creates and save an image in upload/avatar/ from an array of an uploaded file.
	 * If necessary, it will crop and resize the image to get the 200x200 central square.
	 * 
	 * @param  array $avatar Array with the info of the received file
	 * @return string Name of the uploaded file
	 * @throws Exception If error occurred while handling the avatar
	 */
	public static function uploadAvatar($avatar) {
		// Check avatar size
		if ($avatar['error'] || $avatar['size'] > Option::get('avatar_maxweight') * 1024 )
			throw new Exception(Text::error("file_weight"));

		// Get and check the file extension
		$extension = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));
		if (!in_array($extension, ['jpg','jpeg','png','gif']))
			throw new Exception(Text::error("file_extension"));
		if ($extension == 'jpg') $extension = 'jpeg';

		// Get a random valid filename
		do {
			$filename = self::randomString(16).'.'.$extension;
		} while (file_exists(AVATAR_DIR.$filename));

		// Crop and resize image if needed
		$image = ('imagecreatefrom'.$extension)($avatar['tmp_name']);
		$width = imagesx($image);
		$height = imagesy($image);
		$sidelength = min($width, $height);
		$x = max(0, intval(($width-$sidelength)/2));
		$y = max(0, intval(($height-$sidelength)/2));
		if ($sidelength > 200) {
			// If greater than 200x200 resize and crop
			$extension = 'jpeg';
			$resized = imagecreatetruecolor(200, 200);
			imagecopyresized($resized, $image, 0, 0, $x, $y, 200, 200, $sidelength, $sidelength);
		} else if ($height != $sidelength || $width != $sidelength){
			// If not squared, crop
			$resized = imagecrop($image, ['x'=>$x, 'y'=>$y, 'width'=>$sidelength, 'height'=>$sidelength]);
		} else {
			// If not, no operation needed
			$resized = $image;
		}
		$quality = $extension == 'jpeg' ? 95 : $extension == 'png' ? -1 : null;
		if (!('image'.$extension)($resized, AVATAR_DIR.$filename, $quality))
			throw new Exception(Text::error("file_upload"));

		// If success, return new filename
		return $filename;
	}

	/**
	 * Remove an existing avatar file stored in upload/avatar/.
	 * 
	 * @param  string $filename Filename
	 * @return bool If avatar has been successfully removed
	 */
	public static function removeAvatar($filename) {
		$pattern = "/^[a-zA-Z0-9]{16}\.(?:png|jpg|jpeg|gif)$/";
		if (!preg_match($pattern, $filename) || !file_exists(AVATAR_DIR.$filename))
			return false;
		return unlink(AVATAR_DIR.$filename);
	}

	/**
	 * Upload an attachment file in upload/attachment/ from an array of an uploaded file.
	 * 
	 * @param  array $attachment Array with the info of the received file
	 * @return array Associative array with file info (name, width, height)
	 * @throws Exception If error occurred while handling the file
	 */
	public static function uploadAttachment($attachment) {
		// Check attachment weight
		if ($attachment['error'] || $attachment['size'] > Option::get('attachment_maxweight') * 1024 )
			throw new Exception(Text::error("file_weight"));

		// Get valid filename
		$filename = preg_replace('/[^\w](?=.*[^\w])/','_',$attachment['name']);
		if (!preg_match("/^[\w]+\.[\w]+$/", $filename) || strlen($filename) > 250)
			throw new Exception(Text::error("file_name"));
		while (file_exists(ATTACHMENT_DIR.$filename))
			$filename = 'cp_'.$filename;

		// Try to move the file to upload/attachment/
		if (!move_uploaded_file($attachment['tmp_name'], ATTACHMENT_DIR.$filename))
			throw new Exception(Text::error("file_upload"));

		if (explode('/', $attachment['type'])[0] == 'image') {
			// If file is an image, returns filename, width and height
			$size = getimagesize(ATTACHMENT_DIR.$filename);
			$fileinfo = ['name' => $filename, 'width' => $size[0], 'height' => $size[1]];
		} else {
			// If not, width and height will be 0
			$fileinfo = ['name' => $filename, 'width' => 0,	'height' => 0];
		}

		// If success, return new file info
		return $fileinfo;
	}

	/**
	 * Remove an existing file stored in upload/attachment/.
	 * 
	 * @param  string $filename Filename
	 * @return bool If file has been successfully removed
	 */
	public static function removeAttachment($filename) {
		$pattern = "/^[\w]+\.[\w]+$/";
		if (!preg_match($pattern, $filename) || !file_exists(ATTACHMENT_DIR.$filename))
			return false;
		return unlink(ATTACHMENT_DIR.$filename);
	}

	/**
	 * Get a random string using only letters and numbers with a given length.
	 * 
	 * @param  int $length Length of the final string
	 * @return string Generated random string
	 */
	public static function randomString($length) {
    	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$len = strlen($chars) - 1;
    	$result = '';
    	for ($i = 0; $i < $length; $i++) $result .= $chars[rand(0, $len)];
    	return $result;
	}

	/**
	 * Get current complete url
	 * 
	 * @return string Complete current url
	 */
	public static function currentUrl() {
		$url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		$url .= '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		return dirname($url);
	}

}
