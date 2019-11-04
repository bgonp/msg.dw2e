<?php

require_once "../init.php";

$imagen = 'default.png';
if (isset($_GET['id']) && SessionController::check()) {
	if ($usuario = Usuario::get(SessionController::usuarioId())) {
		$contacto = $_GET['id'] == $usuario->id() ? $usuario : $usuario->amigos($_GET['id']);
		if ($contacto) {
			$avatar = $contacto->avatar();
			if (!empty($avatar) && file_exists(IMAGE_DIR.$avatar)){
				$imagen = $avatar;
			}
		}
	}
}
$type = getimagesize(IMAGE_DIR.$imagen)['mime'];
header("Content-Type: $type");
echo file_get_contents(IMAGE_DIR.$imagen);