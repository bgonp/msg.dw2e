<?php

require_once "../init.php";

$imagen = 'default.png';
if (isset($_GET['id']) && SessionController::check()) {
	if ($user = User::get(SessionController::userId())) {
		$contact = $_GET['id'] == $user->id() ? $user : $user->friends($_GET['id']);
		if ($contact) {
			$avatar = $contact->avatar();
			if (!empty($avatar) && file_exists(AVATAR_DIR.$avatar)){
				$imagen = $avatar;
			}
		}
	}
}
$type = getimagesize(AVATAR_DIR.$imagen)['mime'];
header("Content-Type: $type");
readfile(AVATAR_DIR.$imagen);