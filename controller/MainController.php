<?php

class MainController {

	private const UNLOGGED_ACTIONS = ['login', 'register', 'resetSend', 'recover', 'resetPassword', 'installApp'];

	public static function ajax() {
		if (!empty($_POST['action']) && method_exists(__CLASS__, $_POST['action'])){
			try {
				$req_login = !in_array($_POST['action'], self::UNLOGGED_ACTIONS);
				if ($req_login xor SessionController::check())
					$response = ['redirect' => '/'];
				else
					$response = self::{$_POST['action']}($_POST, $_FILES);
			} catch (Exception $ex) {
				$response = ['type' => 'error', 'message' => $ex->getMessage()];
			}
		} else {
			$response = ['type' => 'error', 'message' => Helper::error('invalid_action')];
		}
		echo json_encode($response);
	}

	public static function main() {
		try {
			if (!Database::connect()) {
				View::install(['color_main' => '#1b377a', 'color_bg' => '#f0f5ff', 'color_border' => '#939db5']);
			} else if (!empty($_GET)) {
				if (SessionController::check() || empty($_GET['action']) || !method_exists(__CLASS__, $_GET['action'])) {
					header('Location: /');
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
	// Funciones de update
	// ------------------------
	private static function update($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$chat = $usuario->chats($post['chat_id']);
		$response = [
			'usuario_id' => SessionController::usuarioId(),
			'messages' => $chat ? $chat->newMensajes($post['last_readed']) : [],
			'chats' => $usuario->newChats(),
			'friends' => $usuario->newFriends($post['last_friend']),
			'requests' => $usuario->newRequests($post['last_request'])
		];
		return $response;
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
				header('Location: /');
				die();
			}
		}
		View::error(Helper::error('user_confirm'), Option::get());
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
			$response = ['redirect' => '/'];
		}
		return $response;
	}

	private static function logout($post, $files) {
		SessionController::logout();
		return ['redirect' => '/'];
	}

	private static function register($post, $files) {
		if (empty($post['email']) || empty($post['nombre']) || empty($post['password']) || empty($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => Helper::error('missing_data')];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => Helper::error('pass_diff')];
		} else {
			if (Option::get('mail_confirm')) {
				$usuario = Usuario::new($post['email'], $post['nombre'], $post['password'], $files['avatar']);
				$email = View::emailConfirm($usuario, Helper::currentUrl());
				MailController::send("Confirm your account", $email, $usuario->email());
				$response = ['type' => 'success', 'message' => 'Enviado un e-mail de confirmación de cuenta'];
			} else {
				$usuario = Usuario::new($post['email'], $post['nombre'], $post['password'], $files['avatar'], 1);
				SessionController::logged($usuario, $usuario->admin());
				$response = ['redirect' => '/'];
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
			$email = View::emailConfirm($usuario, Helper::currentUrl());
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
			$response = ['redirect' => '/'];
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
			$edited = false;
			if ($post['email'] != $usuario->email())
				$edited = $edited || $usuario->email($post['email']);
			if ($post['name'] != $usuario->nombre())
				$edited = $edited || $usuario->nombre($post['name']);
			if (!empty($post['password']))
				$edited = $edited || $usuario->password($post['password']);
			if (!$files['avatar']['error'])
				$edited = $edited || $usuario->avatar($files['avatar']);
			if ($edited) {
				$usuario->save();
				$response = ['type' => 'success', 'message' => 'Perfil actualizado', 'userdata' => $usuario->toArray(0)];
			}
		}
		return $response;
	}

	// ------------------------
	// Funciones de amigos
	// ------------------------
	private static function requestFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->addContacto($post['email']);
		return ['type' => 'success', 'message' => 'Solicitud de amistad enviada'];
	}

	private static function acceptFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->updateContacto($post['request_id'], Helper::ACEPTADO);
		return ['type' => 'success', 'message' => 'Solicitud de amistad aceptada', 'update' => 1];
	}

	private static function rejectFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->updateContacto($post['request_id'], Helper::RECHAZADO);
		return ['type' => 'success', 'message' => 'Solicitud de amistad rechazada'];
	}

	private static function blockFriend($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$usuario->updateContacto($post['friend_id'], Helper::BLOQUEADO);
		return ['type' => 'success', 'message' => 'Contacto bloqueado'];
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
			$response = ['update' => 1, 'focus' => 'chats'];
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
				$usuario->readChat($chat->id());
				$response = $chat->toArray();
				$response['usuario_id'] = SessionController::usuarioId();
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
				$response = ['update' => 1, 'type' => 'success', 'message' => 'Amigo añadido al chat'];
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
				$response = ['update' => 1, 'chat_id' => $chat->id()];
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
			$response = ['redirect' => '/'];
		}
		return $response;
	}

	private static function installApp($post, $files) {
		if (Database::connect() || Install::run($post)) {
			$response = ['redirect' => '/'];
		} else {
			$response = ['type' => 'error', 'message' => Helper::error('installation')];
		}
		return $response;
	}

}