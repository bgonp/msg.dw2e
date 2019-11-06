<?php

class MainController {

	private const UNLOGGED_ACTIONS = ['login', 'register', 'resetSend', 'recover', 'resetPassword'];

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
			$response = ['type' => 'error', 'message' => 'Operación no válida: '.$_POST["action"]];
		}
		echo json_encode($response);
	}

	public static function main() {
		try {
			if (!empty($_GET)) {
				if (SessionController::check() || empty($_GET['action']) || !method_exists(__CLASS__, $_GET['action'])) {
					header('Location: /');
					die();
				} else {
					self::{$_GET['action']}($_GET);
				}
			} else if (SessionController::check()) {
				$usuario = Usuario::get(SessionController::usuarioId());
				View::main($usuario);
			} else {
				View::login();
			}
		} catch (Exception $ex) {
			SessionController::logout();
			View::error($ex->getMessage());
		}
	}

	// ------------------------
	// Funciones de update
	// ------------------------
	private static function update($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		$error = [];
		$response = [];
		if ($chat = $usuario->chats($post['chat_id'])) {
			$response['usuario_id'] = SessionController::usuarioId();
			$response['messages'] = $chat->newMensajes($post['last_readed']);
		}
		$response['chats'] = $usuario->newChats();
		$response['friends'] = $usuario->newFriends($post['last_friend']);
		$response['requests'] = $usuario->newRequests($post['last_request']);
		return $response;
	}

	// ------------------------
	// Funciones por get
	// ------------------------
	private static function recover($get) {
		if (!empty($_GET['id']) && !empty($_GET['key'])) {
			$usuario = Usuario::get($_GET['id']);
			if ($usuario->checkClave($_GET['key']))
				View::recover($usuario, $_GET['key']);
			else
				View::error('Hay algún error con la clave de usuario');
		} else {
			View::error('Hay algún error con el enlace de recuperación');
		}
	}

	private static function confirm($get) {
		if (!empty($_GET['id']) && !empty($_GET['key'])) {
			$usuario = Usuario::get($_GET['id']);
			if ($usuario->checkClave($_GET['key'])) {
				$usuario->confirm();
				header('Location: /');
				die();
			} else {
				View::error('Ocurrió un error al confirmar tu usuario');
			}
		} else {
			View::error('Hay algún error con el enlace de confirmación');
		}
	}

	// ------------------------
	// Funciones de usuario
	// ------------------------
	private static function login($post, $files) {
		if (empty($post['email']) || empty($post['password'])) {
			$response = ['type' => 'error', 'message' => 'Falta información'];
		} else {
			$usuario = Usuario::get($post['email'], $post['password']);
			SessionController::logged($usuario);
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
			$response = ['type' => 'error', 'message' => 'Falta información'];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => 'Las contraseñas no coinciden'];
		} else {
			$usuario = Usuario::new($post['email'], $post['nombre'], $post['password'], $files['avatar']);
			MailController::send("Confirm your account", View::emailConfirm($usuario), $usuario->email());
			$response = ['type' => 'success', 'message' => 'Enviado un e-mail de confirmación de cuenta'];
		}
		return $response;
	}

	private static function resetSend($post, $files) {
		if (empty($post['email'])) {
			$response = ['type' => 'error', 'message' => 'Falta e-mail'];
		} else if (!($usuario = Usuario::get($post['email']))) {
			$response = ['type' => 'error', 'message' => 'No existe usuario'];
		} else {
			MailController::send("Reset your password", View::emailReset($usuario), $usuario->email());
			$response = ['type' => 'success', 'message' => 'Se ha enviado e-mail de recuperación'];
		}
		return $response;
	}

	private static function resetPassword($post, $files) {
		if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => 'Las contraseñas no coinciden'];
		} else if (!($usuario = Usuario::get($post['id']))) {
			$response = ['type' => 'error', 'message' => 'No existe usuario'];
		} else if (!$usuario->checkClave($post['key'])) {
			$response = ['type' => 'error', 'message' => 'Clave incorrecta, quizá pasaron más de 24 horas'];
		} else if (!$usuario->password($post['password'])) {
			$response = ['type' => 'error', 'message' => 'Password inválido'];
		} else {
			$usuario->removeClave();
			$response = ['redirect' => '/'];
		}
		return $response;
	}

	private static function editProfile($post, $files) {
		if (empty($post['email']) || empty($post['name']) || !isset($post['password']) || !isset($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => 'Falta información'];
		} else if (!empty($post['password']) && $post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => 'Las contraseñas no coinciden'];
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
			if (!$edited) {
				$response = ['type' => 'error', 'message' => 'Nada que actualizar'];
			} else {
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
			$response = ['type' => 'error', 'message' => 'Falta un nombre para el chat'];
		} else if (!isset($post['members']) || !is_array($post['members'])) {
			$response = ['type' => 'error', 'message' => 'Selecciona al menos un amigo'];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			$amigos = [];
			foreach ($post['members'] as $amigo)
				if (!$usuario->amigos($amigo))
					return ['type' => 'error', 'message' => 'Algún miembro no es tu amigo'];
			$chat = Chat::new($post['name']);
			$chat->addUsuario($usuario);
			$todos = true;
			foreach ($post['members'] as $member)
				if (!$chat->addUsuario($member)) $todos = false;
			$response = ['update' => 1, 'focus' => 'chats'];
			if (!$todos) {
				$response['type'] = 'error';
				$response['message'] = 'Algún amigo no se pudo agregar al chat';
			}
		}
		return $response;
	}

	private static function leaveChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => 'Falta identificador de chat'];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => 'Chat incorrecto'];
			} else {
				$chat->removeUsuario($usuario);
				$response = ['type' => 'success', 'message' => 'Abandonaste el chat'];
			}
		}
		return $response;
	}

	private static function loadChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => 'Falta identificador de chat'];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => 'Chat incorrecto'];
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
			$response = ['type' => 'error', 'message' => 'Falta información'];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => 'Chat incorrecto'];
			} else if (!($friend = $usuario->amigos($post['friend_id']))) {
				$response = ['type' => 'error', 'message' => 'Solo puedes añadir amigos'];
			} else if (!$chat->addUsuario($friend)) {
				$response = ['type' => 'error', 'message' => 'Ocurrió un error al añadir al chat'];
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
			$response = ['type' => 'error', 'message' => 'Falta información'];
		} else if (strlen($post['mensaje']) > 1000){
			$response = ['type' => 'error', 'message' => 'El mensaje puede tener 1000 caracteres máximo'];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => 'Chat incorrecto'];
			} else if ($chat->addMensaje($usuario->id(), $post['mensaje']) === false) {
				$response = ['type' => 'error', 'message' => 'No se añadió el mensaje'];
			} else {
				$usuario->readChat($chat->id());
				$response = ['update' => 1, 'chat_id' => $chat->id()];
			}
		}
		return $response;
	}

}