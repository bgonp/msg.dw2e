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
 * @package msg.dw2e (https://github.com/bgonp/msg.dw2e)
 * @author Borja Gonzalez <borja@bgon.es>
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */

require_once "../init.php";

$imagen = 'default.png';
// Check if there is an ID passed by GET and a user logged in
if (isset($_GET['id']) && SessionController::check()) {
	// Try to load current logged user
	if ($user = User::get(SessionController::userId())) {
		// Check if the requested avatar is from a friend or from himself
		$contact = $_GET['id'] == $user->id() ? $user : $user->friends($_GET['id']);
		if ($contact) {
			// Check if the avatar exists
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
