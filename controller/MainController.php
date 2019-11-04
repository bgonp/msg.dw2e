<?php

class MainController {

	public static function ajax() {
		if (isset($_POST['action']) && method_exists(__CLASS__, $_POST['action'])){
			try {
				$req_login = $_POST['action'] != 'login' && $_POST['action'] != 'register';
				if ($req_login xor SessionController::check())
					$response = ['update' => 'page'];
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
		if (SessionController::check()) {
			try {
				$usuario = Usuario::get(SessionController::usuarioId());
				View::main($usuario);
			} catch (Exception $ex) {
				SessionController::logout();
				View::error($ex->getMessage());
			}
		} else {
			View::login();
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
			$response = ['update' => 'page'];
		}
		return $response;
	}

	private static function logout($post, $files) {
		SessionController::logout();
		return ['update' => 'page'];
	}

	private static function register($post, $files) {
		if (empty($post['email']) || empty($post['nombre']) || empty($post['password']) || empty($post['password_rep'])) {
			$response = ['type' => 'error', 'message' => 'Falta información'];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['type' => 'error', 'message' => 'Las contraseñas no coinciden'];
		} else {
			$usuario = Usuario::new($post['email'], $post['nombre'], $post['password'], $files['avatar']);
			SessionController::logged($usuario);
			$response = ['update' => 'page'];
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
				$response = ['type' => 'success', 'message' => 'Perfil actualizado correctamente', 'update' => 'userdata'];
			}
		}
		return $response;
	}

	private static function updateUserdata($post, $files) {
		return Usuario::get(SessionController::usuarioId())->toArray(0);
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
		return ['type' => 'success', 'message' => 'Solicitud de amistad aceptada', 'update' => 'friends'];
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

	private static function updateFriends($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		return ['friends' => $usuario->newFriends($post['last'])];
	}

	private static function updateRequests($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		return ['requests' => $usuario->newRequests($post['last'])];
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
			$response = ['update' => 'chats', 'focus' => 'chats'];
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

	private static function updateChats($post, $files) {
		$usuario = Usuario::get(SessionController::usuarioId());
		return ['chats' => $usuario->newChats()];
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
				$response = ['update' => 'messages', 'chat_id' => $chat->id()];
			}
		}
		return $response;
	}

	private static function updateMessages($post, $files) {
		if (empty($post['chat_id']) || empty($post['last_readed'])) {
			$response = ['type' => 'error', 'message' => 'Falta identificador de chat'];
		} else {
			$usuario = Usuario::get(SessionController::usuarioId());
			if (!($chat = $usuario->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => 'Chat incorrecto'];
			} else {
				$usuario->readChat($chat->id());
				$response = [
					'messages' => $chat->newMensajes($post['last_readed']),
					'usuario_id' => SessionController::usuarioId()
				];
			}
		}
		return $response;
	}

}