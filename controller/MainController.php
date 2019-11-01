<?php

class MainController {

	public static function ajax() {
		if (isset($_POST['action']) && method_exists(__CLASS__, $_POST['action'])){
			try {
				$response = self::{$_POST['action']}($_POST);
			} catch (Exception $ex) {
				$response = ['error' => 1, 'message' => $ex->getMessage()];
			} catch (Error $er) {
				$response = ['error' => 1, 'message' => 'Ocurrió un error inesperado'];
			}
		} else {
			$response = ['error' => 1, 'message' => 'Operación no válida'];
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

	private static function login($post) {
		if (SessionController::check()) {
			$response = ['error' => 1, 'message' => 'Ya hay iniciada una sesión'];
		} else if (!isset($post['email']) || !isset($post['password'])) {
			$response = ['error' => 1, 'message' => 'Falta información'];
		} else {
			$usuario = Usuario::get($post['email'], $post['password']);
			SessionController::logged($usuario);
			$response = ['refresh' => 1];
		}
		return $response;
	}

	private static function logout($post) {
		if (!SessionController::check()) {
			$response = ['error' => 1, 'message' => 'No hay sesión iniciada'];
		} else {
			SessionController::logout();
			$response = ['refresh' => 1];
		}
		return $response;
	}

	private static function register($post) {
		if (SessionController::check()) {
			$response = ['error' => 1, 'message' => 'Ya hay iniciada una sesión'];
		} else if (!isset($post['email']) || !isset($post['nombre']) || !isset($post['password']) || !isset($post['password_rep'])) {
			$response = ['error' => 1, 'message' => 'Falta información'];
		} else if ($post['password'] !== $post['password_rep']) {
			$response = ['error' => 1, 'message' => 'Las contraseñas no coinciden'];
		} else {
			$usuario = Usuario::new($post['email'], $post['nombre'], $post['password']);
			SessionController::logged($usuario);
			$response = ['refresh' => 1];
		}
		return $response;
	}

}