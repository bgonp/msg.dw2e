<?php
class MainController {
	private const UNLOGGED_ACTIONS = ['login', 'register', 'resetSend', 'recover', 'resetPassword', 'installApp'];
	public static function ajax() {
		if ($_SERVER["REQUEST_METHOD"] === 'POST') {
			header('Content-Type: application/json');
			if (!empty($_POST['action']) && method_exists(__CLASS__, $_POST['action'])){
				try {
					$req_login = !in_array($_POST['action'], self::UNLOGGED_ACTIONS);
					if ($req_login xor SessionController::check())
						$response = ['redirect' => Helper::currentUrl()];
					else
						$response = self::{$_POST['action']}($_POST, $_FILES);
				} catch (Exception $ex) {
					$response = ['type' => 'error', 'message' => $ex->getMessage()];
				}
			} else {
				$response = ['type' => 'error', 'message' => Text::error('invalid_action')];
			}
			echo json_encode($response);
		} else {
			header('Location: '.Helper::currentUrl());
			die();
		}
	}
	public static function main() {
		try {
			if (!Database::connect()) {
				SessionController::logout();
				View::install([
					'page_title' => 'DW2E Messaging App',
					'color_main' => '#1b377a',
					'color_bg' => '#f0f5ff',
					'color_border' => '#939db5'
				]);
			} else if (!empty($_GET)) {
				if (SessionController::check() || empty($_GET['action']) || !method_exists(__CLASS__, $_GET['action'])) {
					header('Location: '.Helper::currentUrl());
					die();
				} else {
					self::{$_GET['action']}($_GET);
				}
			} else if (SessionController::checkAdmin()) {
				View::options(Option::get());
			} else if (SessionController::check()) {
				View::main(User::get(SessionController::userId()), Option::get());
			} else {
				View::login(Option::get());
			}
		} catch (Exception $ex) {
			SessionController::logout();
			View::error($ex->getMessage(), Option::get());
		}
	}
	// ------------------------
	// Funciones por get
	// ------------------------
	private static function recover($get) {
		if (!empty($_GET['id']) && !empty($_GET['key'])) {
			$user = User::get($_GET['id']);
			if ($user->checkCode($_GET['key'])) {
				View::recover($user, $_GET['key'], Option::get());
				return;
			}
		}
		View::error(Text::error('key_check'), Option::get());
	}
	private static function confirm($get) {
		if (!empty($_GET['id']) && !empty($_GET['key'])) {
			$user = User::get($_GET['id']);
			if ($user->checkCode($_GET['key'])) {
				$user->confirm();
				header('Location: '.Helper::currentUrl());
				die();
			}
		}
		View::error(Text::error('user_confirm'), Option::get());
	}
	// ------------------------
	// Funciones de update
	// ------------------------
	private static function update($post, $files) {
		$user = User::get(SessionController::userId());
		$response = ['user_id' => $user->id()];
		if (!empty($post['chat_id']) && ($chat = $user->chats($post['chat_id'])))
			if ($messages = $chat->newMessages($post['last_read'])) {
				$user->readChat($chat->id());
				$response['messages'] = $messages;
				if ($chat->newMembers()) {
					$response['members'] = array_values($chat->users());
					$response['candidates'] = $chat->candidates($user->id());
				}
			}
		if (isset($post['last_received']) && ($chats = $user->newChats($post['last_received'])))
			$response['chats'] = $chats;
		if (isset($post['last_contact_upd']) && $post['last_contact_upd'] < ($update = $user->lastContactUpd())) {
			if ($friends = $user->newFriends($post['last_contact_upd']))
				$response['friends'] = $friends;
			if ($requests = $user->newRequests($post['last_contact_upd']))
				$response['requests'] = $requests;
			$response['last_contact_upd'] = $update;
		}
		return $response;
	}
	// ------------------------
	// Funciones de user
	// ------------------------
	private static function login($post, $files) {
		if (empty($post['email']) || empty($post['password'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get($post['email'], $post['password']);
			SessionController::logged($user, $user->admin());
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}
	private static function logout($post, $files) {
		SessionController::logout();
		return ['redirect' => Helper::currentUrl()];
	}
	private static function register($post, $files) {
		if (empty($post['email']) || empty($post['name']) || empty($post['password']) || empty($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Text::error('pass_diff')];
		} else {
			if (Option::get('email_confirm')) {
				$user = User::new($post['email'], $post['name'], $post['password'], $files['avatar']);
				$email = View::emailConfirm($user);
				if (MailController::send("Confirm your account", $email, $user->email())) {
					$response = ['type' => 'success', 'message' => Text::success('confirmation_sent')];
				} else {
					$user->delete();
					$response = ['type' => 'error', 'message' => Text::error('email_error')];
				}
			} else {
				$user = User::new($post['email'], $post['name'], $post['password'], $files['avatar'], 1);
				SessionController::logged($user, $user->admin());
				$response = ['redirect' => Helper::currentUrl()];
			}
		}
		return $response;
	}
	private static function resetSend($post, $files) {
		if (!Option::get('email_confirm')) {
			$response = ['type' => 'error', 'message' => Text::error('conf_error')];
		} if (empty($post['email'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!($user = User::get($post['email']))) {
			$response = ['type' => 'error', 'message' => Text::error('user_wrong')];
		} else {
			$email = View::emailReset($user);
			if (MailController::send("Reset your password", $email, $user->email()))
				$response = ['type' => 'success', 'message' => Text::success('recover_sent')];
			else
				$response = ['type' => 'error', 'message' => Text::error('email_error')];
		}
		return $response;
	}
	private static function resetPassword($post, $files) {
		if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Text::error('pass_diff')];
		} else if (!($user = User::get($post['id']))) {
			$response = ['type' => 'error', 'message' => Text::error('user_wrong')];
		} else if (!$user->checkCode($post['key'])) {
			$response = ['type' => 'error', 'message' => Text::error('key_check')];
		} else if (!$user->password($post['password'])) {
			$response = ['type' => 'error', 'message' => Text::error('pass_wrong')];
		} else {
			$user->removeCode();
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}
	private static function editProfile($post, $files) {
		if (empty($post['email']) || empty($post['name']) || !isset($post['password']) || !isset($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!empty($post['password']) && $post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Text::error('pass_diff')];
		} else {
			$user = User::get(SessionController::userId());
			$edited = $new_email = false;
			$note = '';
			if ($post['email'] != $user->email())
				$new_email = $edited = $user->email($post['email']);
			if ($post['name'] != $user->name())
				$edited = $user->name($post['name']) || $edited;
			if (!empty($post['password']))
				$edited = $user->password($post['password']) || $edited;
			if ($files['avatar']['error'] != 4)
				$edited = $user->avatar($files['avatar']) || $edited;
			if ($new_email && Option::get('email_confirm')) {
				$email = View::emailConfirm($user);
				if (MailController::send("Confirm your new e-mail", $email, $user->email())) {
					$user->confirmed(0);
					$note = '. '.Text::success('confirmation_needed').'.';
				}
			}
			if ($edited && $user->save())
				$response = ['type' => 'success', 'message' => Text::success('updated_profile').$note, 'userdata' => $user];
			else
				$response = ['type' => 'error', 'message' => Text::error('profile_save')];
		}
		return $response;
	}
	// ------------------------
	// Funciones de friends
	// ------------------------
	private static function requestFriend($post, $files) {
		$user = User::get(SessionController::userId());
		$user->addContact($post['email']);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_sent')
		];
		return $response;
	}
	private static function acceptFriend($post, $files) {
		$user = User::get(SessionController::userId());
		$user->updateContact($post['contact_id'], Helper::ACCEPTED);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_accepted'),
			'focus' => 'friends'
		];
		return $response;
	}
	private static function rejectFriend($post, $files) {
		$user = User::get(SessionController::userId());
		$user->updateContact($post['contact_id'], Helper::DECLINED);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_declined')
		];
		return $response;
	}
	private static function blockFriend($post, $files) {
		$user = User::get(SessionController::userId());
		$user->updateContact($post['contact_id'], Helper::BLOCKED);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_blocked')
		];
		return $response;
	}
	// ------------------------
	// Funciones de chats
	// ------------------------
	private static function createChat($post, $files) {
		if (empty($post['name'])) {
			$response = ['type' => 'error', 'message' => Text::error('chat_name')];
		} else if (!isset($post['members']) || !is_array($post['members'])) {
			$response = ['type' => 'error', 'message' => Text::error('chat_member')];
		} else {
			$user = User::get(SessionController::userId());
			$friends = [];
			foreach ($post['members'] as $friend)
				if (!$user->friends($friend))
					return ['type' => 'error', 'message' => Text::error('no_friend')];
			$chat = Chat::new($post['name']);
			$chat->addUser($user);
			$todos = true;
			foreach ($post['members'] as $member)
				if (!$chat->addUser($member)) $todos = false;
			$response = ['focus' => 'chats', 'chats' => $user->newChats($post['last_received'])];
			if (!$todos) {
				$response['type'] = 'error';
				$response['message'] = Text::error('chat_add');
			}
		}
		return $response;
	}
	private static function leaveChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get(SessionController::userId());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else {
				$chat->removeUser($user);
				$response = ['type' => 'success', 'message' => Text::success('chat_leave')];
			}
		}
		return $response;
	}
	private static function loadChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get(SessionController::userId());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else {
				$response = $chat->jsonSerialize();
				$response['messages'] = array_values($chat->messages());
				$response['members'] = array_values($chat->users());
				$response['candidates'] = $chat->candidates($user->id());
				$user->readChat($chat->id());
			}
		}
		return $response;
	}
	private static function addMember($post, $files) {
		if (empty($post['chat_id']) || empty($post['contact_id'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get(SessionController::userId());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else if (!($friend = $user->friends($post['contact_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('no_friend')];
			} else if (!$chat->addUser($friend)) {
				$response = ['type' => 'error', 'message' => Text::error('chat_add')];
			} else {
				$response = ['type' => 'success', 'message' => Text::success('chat_invite')];
			}
		}
		return $response;
	}
	// ------------------------
	// Funciones de messages
	// ------------------------
	private static function sendMessage($post, $files) {
		if (empty($post['chat_id']) || empty($post['message'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!Helper::validText($post['message'])) {
			$response = ['type' => 'error', 'message' => Text::error('msg_invalid')];
		} else {
			$user = User::get(SessionController::userId());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else if ($chat->addMessage($user->id(), $post['message'], $files['attachment'] ?? false) === false) {
				$response = ['type' => 'error', 'message' => Text::error('msg_add')];
			} else {
				$user->readChat($chat->id());
				$response = ['messages' => $chat->newMessages($post['last_read'])];
			}
		}
		return $response;
	}
	// ------------------------
	// Funciones de configuración
	// ------------------------
	private static function updateOptions($post, $files) {
		if (empty($post['options'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!SessionController::checkAdmin()) {
			$response = ['type' => 'error', 'message' => Text::error('permission')];
		} else {
			foreach ($post['options'] as $key => $value)
				Option::update($key, $value);
			if (Option::get('email_confirm') && !MailController::test())
				$response = ['type' => 'error', 'message' => Text::error('email_config')];
			else
				$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}
	private static function installApp($post, $files) {
		if (Database::connect() || Install::run($post))
			$response = ['redirect' => Helper::currentUrl()];
		else
			$response = ['type' => 'error', 'message' => Text::error('installation')];
		return $response;
	}
}