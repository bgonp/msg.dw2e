<?php
/**
 * This file handle access to avatar images of users.
 * 
 * It receives the ID of a user by GET and check if the current user is logged
 * in and has permission to view the other user avatar (only friends can view
 * avatars of each other).
 * This file sends to the client the data as an image directly, not as a file
 * to be downloaded.
 * 
 * @package public
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */

require_once "../init.php";

// Check if there is an ID passed by GET and it correspond to a valid user
if (isset($_GET['id']) && $user = User::get($_GET['id'])) {
	// Check if there is a logged user
	if ($logged_id = SessionController::logged()) {
		// Check if the requested avatar is from an allowed user (himself or a friend)
		if ($logged_id == $user->id() || $user->friends($logged_id)) {
			// Check if the requested user has an avatar or set the default image
			$avatar = $user->avatar() ?: 'default.png';
			// Check if the file exists
			if (file_exists(AVATAR_DIR.$avatar)) {
				// Print the avatar
				$type = getimagesize(AVATAR_DIR.$avatar)['mime'];
				header("Content-Type: $type");
				readfile(AVATAR_DIR.$avatar);
			} else {
				// File not found
				http_response_code(404);
			}
		} else {
			// User unable to view the avatar
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