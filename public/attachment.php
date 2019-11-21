<?php

require_once "../init.php";

if (isset($_GET['id']) && SessionController::check()) {
	if ($usuario = Usuario::get(SessionController::usuarioId())) {
		if ($file = Attachment::get($_GET['id'])) {
			if ($file->chat()->usuarios($usuario->id())) {
				if (!empty($file->filename()) && file_exists(ATTACHMENT_DIR.$file->filename())) {
					if ($file->isImage()) {
						header('Content-Type: '.$file->mime_type());
						header('Content-Disposition: inline; filename="'.$file->filename().'"'); 
					} else {
						$size = filesize(ATTACHMENT_DIR.$file->filename());
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename="'.$file->filename().'"'); 
						header('Content-Transfer-Encoding: binary');
						header('Connection: Keep-Alive');
						header('Expires: 0');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Pragma: public');
						header('Content-Length: '.$size);
					}
					readfile(ATTACHMENT_DIR.$file->filename());
				}
			}
		}
	}
}