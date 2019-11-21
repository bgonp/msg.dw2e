<?php

require_once "../init.php";

$imagen = 'default.png';
if (isset($_GET['id']) && SessionController::check()) {
	if ($usuario = Usuario::get(SessionController::usuarioId())) {
		$contacto = $_GET['id'] == $usuario->id() ? $usuario : $usuario->amigos($_GET['id']);
		if ($contacto) {
			$avatar = $contacto->avatar();
			if (!empty($avatar) && file_exists(AVATAR_DIR.$avatar)){
				$imagen = $avatar;
			}
		}
	}
}
$type = getimagesize(AVATAR_DIR.$imagen)['mime'];
header("Content-Type: $type");
readfile(AVATAR_DIR.$imagen);