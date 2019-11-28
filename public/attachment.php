<?php
/**
 * This file handle access to attached files of messages.
 * 
 * It receives the ID of the attachment entry in database by a query var, and then checks if
 * the request comes from a logged user and he is allowed to get this file.
 * Finally, it returns the file to be downloaded or show the whole file if it is an image.
 * 
 * @package msg.dw2e (https://github.com/bgonp/msg.dw2e)
 * @author Borja Gonzalez <borja@bgon.es>
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */

require_once "../init.php";

// Check if there is an ID passed by GET and a valid file with that ID
if (isset($_GET['id']) && $file = Attachment::get($_GET['id'])) {
	// Check if there is a valid user logged in
	if (SessionController::check() && $user = User::get(SessionController::userId())) {
		// Check if the user belongs to the chat where this attachment was sent
		if ($file->chat()->users($user->id())) {
			// Check if the file exists in upload/attachment/ folder
			if (!empty($file->filename()) && file_exists(ATTACHMENT_DIR.$file->filename())) {
				if ($file->isImage()) {
					// If the file is an image, show it
					header('Content-Type: '.$file->mime_type());
					header('Content-Disposition: inline; filename="'.$file->filename().'"'); 
				} else {
					// If not, download it
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
			} else {
				// File not found
				http_response_code(404);
			}
		} else {
			// User unable to get the file
			http_build_query(403);
		}
	} else {
		// User not authenticated
		http_build_query(401);
	}
} else {
	// Bad request
	http_build_query(400);
}
