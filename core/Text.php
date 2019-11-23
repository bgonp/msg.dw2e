<?php

class Text {

	public static function error($error) {
		switch ($error) {
			case 'attachment_id':
				return 'Invalid attachment id'; break;
			case 'attachment_get':
				return 'Attachment doesn\'t exist'; break;
			case 'attachment_invalid':
				return 'Invalid file'; break;
			case 'attachment_data':
				return 'Invalid file data'; break;
			case 'attachment_upload':
				return 'Unable to upload attachment'; break;
			case 'attachment_new':
				return 'Unable to create attachment'; break;
			case 'chat_add':
				return 'Can\'t add someone to the chat'; break;
			case 'chat_id':
				return 'Invalid chat id'; break;
			case 'chat_get':
				return 'Chat doesn\'t exist'; break;
			case 'chat_invalid':
				return 'Invalid chat name'; break;
			case 'chat_new':
				return 'Unable to create chat'; break;
			case 'chat_name':
				return 'Name of chat can\'t be empty'; break;
			case 'chat_member':
				return 'Please, select at least a friend'; break;
			case 'chat_wrong':
				return 'Wrong chat ID'; break;
			case 'conf_error':
				return 'Configuration error'; break;
			case 'contact_self':
				return 'You can\'t be your own friend'; break;
			case 'contact_new':
				return 'Friendship hasn\'t been requested'; break;
			case 'contact_state':
				return 'Wrong friendship state'; break;
			case 'contact_update':
				return 'Friendship couldn\'t be updated'; break;
			case 'database_connect':
				return 'Database connection error'; break;
			case 'email_error':
				return 'An error occurred while sending the e-mail'; break;
			case 'email_config':
				return 'E-mail configuration error'; break;
			case 'file_size':
				return 'The image can\'t be greater than 1000 x 1000'; break;
			case 'file_weight':
				return 'The image can\'t be greater than '.Option::get('image_maxweight').'KB'; break;
			case 'install_userpass':
				return 'Invalid user credentials'; break;
			case 'install_getfile':
				return 'Unable to load installation database script'; break;
			case 'install_tables':
				return 'Database tables couldn\'t be created'; break;
			case 'install_putfile':
				return 'Configuration file couldn\'t be created. This can be a permissions problem'; break;
			case 'installation':
				return 'An error occurred during installation'; break;
			case 'invalid_action':
				return 'Invalid operation'; break;
			case 'key_check':
				return 'Can\'t verify your code. Remember: that link explires after 24 hours'; break;
			case 'message_id':
				return 'Invalid message id'; break;
			case 'message_get':
				return 'Message doesn\'t exist'; break;
			case 'message_invalid':
				return 'Invalid message content'; break;
			case 'message_new':
				return 'Unable to create message'; break;
			case 'missing_data':
				return 'Some data is missing'; break;
			case 'msg_add':
				return 'Message can\'t be sent'; break;
			case 'msg_invalid':
				return 'Text must have less than 1000 characters'; break;
			case 'no_friend':
				return 'Someone is not your friend anymore'; break;
			case 'pass_diff':
				return 'Passwords didn\'t match'; break;
			case 'pass_wrong':
				return 'Wrong credentials'; break;
			case 'permission':
				return 'You don\'t have permissions to complete this action'; break;
			case 'profile_save':
				return 'Can\'t save your profile data'; break;
			case 'user_id':
				return 'Invalid user id'; break;
			case 'user_name':
				return 'Invalid user name'; break;
			case 'user_email':
				return 'Invalid user e-mail'; break;
			case 'user_pass':
				return 'Invalid user password'; break;
			case 'user_avatar':
				return 'Invalid user avatar'; break;
			case 'user_get':
				return 'User doesn\'t exist'; break;
			case 'user_new':
				return 'Unable to create user. Maybe the e-mail address is already in use.'; break;
			case 'user_confirm':
				return 'Can\'t verify user'; break;
			case 'user_wrong':
				return 'Can\'t get the user'; break;
			default:
				return "Unexpected error occurred: $error";
		}
	}

	public static function success($message) {
		switch ($message) {
			case 'chat_invite':
				return 'Your friend joins the chat'; break;
			case 'chat_leave':
				return 'You left the chat'; break;
			case 'confirmation_sent':
				return 'A confirmation e-mail was sent'; break;
			case 'confirmation_needed':
				return 'You have to confirm your new e-mail in order to login again'; break;
			case 'friendship_accepted':
				return 'Friendship accepted'; break;
			case 'friendship_blocked':
				return 'Friendship blocked'; break;
			case 'friendship_declined':
				return 'Friendship declined'; break;
			case 'friendship_sent':
				return 'Friendship request sent'; break;
			case 'recover_sent':
				return 'An e-mail was sent to recover your password'; break;
			case 'updated_profile':
				return 'Your profile was updated'; break;
			default:
				return "Unregistered message: $message";
		}
	}

}