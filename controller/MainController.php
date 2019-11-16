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
				$response = ['type' => 'error', 'message' => Helper::error('invalid_action')];
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
				View::install(['color_main' => '#1b377a', 'color_bg' => '#f0f5ff', 'color_border' => '#939db5']);
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
				View::main(Usuario::get(SessionController::usuarioId()), Option::get());
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
			$usuario = Usuario::get($_GET['id']);
			if ($usuario->checkClave($_GET['key'])) {
				View::recover($usuario, $_GET['key'], Option::get());
				return;
			}
		}
		View::error(Helper::error('key_check'), Option::get());
	}

	private static function confirm($get) {
		if (!empty($_GET['id']) && !empty($_GET['key'])) {
			$usuario = Usuario::get($_GET['id']);
			if ($usuario->checkClave($_GET['key'])) {
				$usuario->confirm();
				header('Location: '.Helper::currentUrl());
				die();
			}
		}
		View::error(Helper::error('user_confirm'), Option::get());
	}

	// ------------------------
	// Funciones de update
	// ------------------------
	private static function update($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$response = ['usuario_id' => $usuario->id()];
		if (!empty($post['chat_id']))
			if ($chat = $usuario->chats($post['chat_id']))
				$response['messages'] = $chat->newMessages($post['last_read']);
		if (isset($post['last_received']))
			$response['chats'] = $usuario->newChats($post['last_received']);
		if (isset($post['last_contact_upd'])) {
			$response['friends'] = $usuario->newFriends($post['last_contact_upd']);
			$response['requests'] = $usuario->newRequests($post['last_contact_upd']);
			$response['last_contact_upd'] = $usuario->lastContactUpd();
		}
		return $response;
	}

	// ------------------------
	// Funciones de usuario
	// ------------------------
	private static function login($post, $files) {
		if (empty($post['email']) || empty($post['password'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else {
			$usuario = Usuario::get($post['email'], $post['password']);
			SessionController::logged($usuario, $usuario->admin());
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}

	private static function logout($post, $files) {
		SessionController::logout();
		return ['redirect' => Helper::currentUrl()];
	}

	private static function register($post, $files) {
		if (empty($post['email']) || empty($post['nombre']) || empty($post['password']) || empty($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Helper::error('pass_diff')];
		} else {
			if (Option::get('email_confirm')) {
				$usuario = Usuario::new($post['email'], $post['nombre'], $post['password'], $files['avatar']);
				$email = View::emailConfirm($usuario);
				if (MailController::send("Confirm your account", $email, $usuario->email())) {
					$response = ['type' => 'success', 'message' => 'Enviado un e-mail de confirmación de cuenta'];
				} else {
					$usuario->delete();
					$response = ['type' => 'error', 'message' => Helper::error('email_error')];
				}
			} else {
				$usuario = Usuario::new($post['email'], $post['nombre'], $post['password'], $files['avatar'], 1);
				SessionController::logged($usuario, $usuario->admin());
				$response = ['redirect' => Helper::currentUrl()];
			}
		}
		return $response;
	}

	private static function resetSend($post, $files) {
		if (empty($post['email'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else if (!($usuario = Usuario::get($post['email']))) {
			$response = ['type' => 'error', 'message' => Helper::error('user_wrong')];
		} else {
			$email = View::emailConfirm($usuario);
			MailController::send("Reset your password", $email, $usuario->email());
			$response = ['type' => 'success', 'message' => 'Se ha enviado e-mail de recuperación'];
		}
		return $response;
	}

	private static function resetPassword($post, $files) {
		if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Helper::error('pass_diff')];
		} else if (!($usuario = Usuario::get($post['id']))) {
			$response = ['type' => 'error', 'message' => Helper::error('user_wrong')];
		} else if (!$usuario->checkClave($post['key'])) {
			$response = ['type' => 'error', 'message' => Helper::error('key_check')];
		} else if (!$usuario->password($post['password'])) {
			$response = ['type' => 'error', 'message' => Helper::error('pass_wrong')];
		} else {
			$usuario->removeClave();
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}

	private static function editProfile($post, $files) {
		if (empty($post['email']) || empty($post['name']) || !isset($post['password']) || !isset($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else if (!empty($post['password']) && $post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Helper::error('pass_diff')];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			$edited = $new_email = false;
			$note = '';
			if ($post['email'] != $usuario->email())
				$new_email = $edited = $usuario->email($post['email']);
			if ($post['name'] != $usuario->nombre())
				$edited = $usuario->nombre($post['name']) || $edited;
			if (!empty($post['password']))
				$edited = $usuario->password($post['password']) || $edited;
			if (!$files['avatar']['error'])
				$edited = $usuario->avatar($files['avatar']) || $edited;
			if ($new_email && Option::get('email_confirm')) {
				$email = View::emailConfirm($usuario);
				if (MailController::send("Confirm your new e-mail", $email, $usuario->email())) {
					$usuario->confirmado(0);
					$note = '. You have to confirm your new e-mail in order to login again.';
				}
			}
			if ($edited && $usuario->save())
				$response = ['type' => 'success', 'message' => 'Perfil actualizado'.$note, 'userdata' => $usuario->toArray(0)];
			else
				$response = ['type' => 'error', 'message' => Helper::error('profile_save')];
		}
		return $response;
	}

	// ------------------------
	// Funciones de amigos
	// ------------------------
	private static function requestFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->addContacto($post['email']);
		$response = ['type' => 'success', 'message' => 'Solicitud de amistad enviada'];
		$response['friends'] = $usuario->newFriends($post['last_contact_upd']);
		$response['requests'] = $usuario->newRequests($post['last_contact_upd']);
		$response['last_contact_upd'] = $usuario->lastContactUpd();
		return $response;
	}

	private static function acceptFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->updateContacto($post['contact_id'], Helper::ACEPTADO);
		$response = ['type' => 'success', 'message' => 'Solicitud de amistad aceptada'];
		$response['friends'] = $usuario->newFriends($post['last_contact_upd']);
		$response['requests'] = $usuario->newRequests($post['last_contact_upd']);
		$response['last_contact_upd'] = $usuario->lastContactUpd();
		return $response;
	}

	private static function rejectFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->updateContacto($post['contact_id'], Helper::RECHAZADO);
		$response = ['type' => 'success', 'message' => 'Solicitud de amistad rechazada'];
		$response['friends'] = $usuario->newFriends($post['last_contact_upd']);
		$response['requests'] = $usuario->newRequests($post['last_contact_upd']);
		$response['last_contact_upd'] = $usuario->lastContactUpd();
		return $response;
	}

	private static function blockFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->updateContacto($post['contact_id'], Helper::BLOQUEADO);
		$response = ['type' => 'success', 'message' => 'Contacto bloqueado'];
		$response['friends'] = $usuario->newFriends($post['last_contact_upd']);
		$response['requests'] = $usuario->newRequests($post['last_contact_upd']);
		$response['last_contact_upd'] = $usuario->lastContactUpd();
		return $response;
	}

	// ------------------------
	// Funciones de chats
	// ------------------------
	private static function createChat($post, $files) {
		if (empty($post['name'])) {
			$response = ['type' => 'error', 'message' => Helper::error('chat_name')];
		} else if (!isset($post['members']) || !is_array($post['members'])) {
			$response = ['type' => 'error', 'message' => Helper::error('chat_member')];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			$amigos = [];
			foreach ($post['members'] as $amigo)
				if (!$usuario->amigos($amigo))
					return ['type' => 'error', 'message' => Helper::error('no_friend')];
			$chat = Chat::new($post['name']);
			$chat->addUsuario($usuario);
			$todos = true;
			foreach ($post['members'] as $member)
				if (!$chat->addUsuario($member)) $todos = false;
			$response = ['focus' => 'chats', 'chats' => $usuario->newChats($post['last_received'])];
			if (!$todos) {
				$response['type'] = 'error';
				$response['message'] = Helper::error('chat_add');
			}
		}
		return $response;
	}

	private static function leaveChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Helper::error('chat_wrong')];
			} else {
				$chat->removeUsuario($usuario);
				$response = ['type' => 'success', 'message' => 'Abandonaste el chat'];
			}
		}
		return $response;
	}

	private static function loadChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Helper::error('chat_wrong')];
			} else {
				$response = $chat->toArray();
				$usuario->readChat($chat->id());
			}
		}
		return $response;
	}

	private static function addMember($post, $files) {
		if (empty($post['chat_id']) || empty($post['friend_id'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Helper::error('chat_wrong')];
			} else if (!($friend = $usuario->amigos($post['friend_id']))) {
				$response = ['type' => 'error', 'message' => Helper::error('no_friend')];
			} else if (!$chat->addUsuario($friend)) {
				$response = ['type' => 'error', 'message' => Helper::error('chat_add')];
			} else {
				$members = [];
				foreach ($chat->usuarios() as $member)
					$members[] = $member->toArray(0);
				$response = ['type' => 'success', 'message' => 'Amigo añadido al chat', 'members' => $members];
			}
		}
		return $response;
	}

	// ------------------------
	// Funciones de mensajes
	// ------------------------
	private static function sendMessage($post, $files) {
		if (empty($post['chat_id']) || empty($post['mensaje'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else if (!Helper::validTexto($post['mensaje'])) {
			$response = ['type' => 'error', 'message' => Helper::error('msg_wrong')];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Helper::error('chat_wrong')];
			} else if ($chat->addMensaje($usuario->id(), $post['mensaje']) === false) {
				$response = ['type' => 'error', 'message' => Helper::error('msg_add')];
			} else {
				$usuario->readChat($chat->id());
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
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else if (!SessionController::checkAdmin()) {
			$response = ['type' => 'error', 'message' => Helper::error('permission')];
		} else {
			foreach ($post['options'] as $key => $value)
				Option::update($key, $value);
			$response = ['redirect' => Helper::currentUrl()];
		}
		return $response;
	}

	private static function installApp($post, $files) {
		if (Database::connect() || Install::run($post)) {
			$response = ['redirect' => Helper::currentUrl()];
		} else {
			$response = ['type' => 'error', 'message' => Helper::error('installation')];
		}
		return $response;
	}

}