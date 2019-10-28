<?php

require_once "autoload.php";

class Usuario {

	private $id;
	private $email;
	private $nombre;
	private $password;
	private $chats;
	private $contactos;

	private function __construct( $id, $email, $nombre, $password ){
		$this->id = $id;
		$this->email = $email;
		$this->nombre = $nombre;
		$this->password = $password;
	}

	public static function Get( $id_o_email, $password = null ){
		if (is_numeric($id_o_email)){
			$id = intval($id_o_email);
			if ($id <= 0) die("Identificación de usuario inválida");
			$sql = "SELECT * FROM usuario WHERE id = $id";
		} else {
			$email = Database::Escape($id_o_email);
			if (empty($email)) die("Identificación de usuario inválida");
			$sql = "SELECT * FROM usuario WHERE email = '$email'";
		}
		$user_db = Database::query($sql);
		if ($user_db->num_rows == 0) die("No existe usuario");
		$user = $user_db->fetch_assoc();
		$usuario = new Usuario($user['id'], $user['email'], $user['nombre'], $user['password']);
		if (!empty($password) && !$usuario->verificar($password)) die("Autentificación errónea");
		return $usuario;
	}

	public static function New( $email, $nombre, $password ){
		$email = Database::Escape($email);
		$nombre = Database::Escape($nombre);
		$password = self::Hash($password);
		if (empty($email) || empty($nombre) || empty($password)) die("No se creó usuario");
		$sql = "INSERT INTO usuario (email, nombre, password) VALUES ('$email', '$nombre', '$password')";
		Database::query($sql);
		if( !($id = Database::InsertId()) ) die("No se creó usuario");
		return new Usuario($id, $email, $nombre, $password);
	}

	public static function List( $result_set ){
		$usuarios = [];
		if (get_class($result_set) == 'mysqli_result')
			while ($user = $result_set->fetch_assoc())
				$usuarios[$user['id']] = new Usuario(
					$user['id'],
					$user['email'],
					$user['nombre'],
					$user['password']
				);
		return $usuarios;
	}

	public function id(){
		return $this->id;
	}

	public function email( $email = null ){
		if (is_null($email)) return $this->email;
		if ( !($email = Database::Escape($email)) ) return false;
		$this->email = $email;
		return true;
	}

	public function nombre( $nombre = null ){
		if (is_null($nombre)) return $this->nombre;
		if ( !($nombre = Database::Escape($nombre)) ) return false;
		$this->nombre = $nombre;
		return true;
	}

	public function password( $password ){
		if (!($password = self::Hash($password))) return false;
		$this->password = $password;
		return true;
	}

	public function chats( $chat_id = null ){
		if (!is_array($this->chats)){
			$sql = "
				SELECT c.id,
					   c.fecha,
					   c.nombre,
					   c.descripcion,
					   c.imagen,
					   c.oculto,
					   c.cerrado,
					   COUNT(m.id) n_mensajes,
					   COUNT(p.usuario_id) n_usuarios
				FROM chat c
				LEFT JOIN mensaje m ON c.id = m.chat_id
				LEFT JOIN participa p ON c.id = p.chat_id
				WHERE p.usuario_id = {$this->id}
				GROUP BY c.id
				ORDER BY c.nombre ASC";
			$result = Database::Query($sql);
			$this->chats = Chat::List($result);
		}
		if (is_null($chat_id)) return $this->chats;
		return $this->chats[$chat_id] ?? false;
	}

	public function getPrivateChat( $usuario_id ){
		$usuario_id = intval($usuario_id);
		$sql = "
			SELECT p.chat_id, COUNT(*) n_usuarios
			FROM participa p
			WHERE p.usuario_id = {$this->id}
			OR p.usuario_id = $usuario_id
			GROUP BY p.chat_id
			HAVING n_usuarios = 2";
		// SELECT chat_id, COUNT(*) n_usuarios FROM participa WHERE chat_id IN (SELECT chat_id FROM participa WHERE usuario_id = 1) AND chat_id IN (SELECT chat_id FROM participa WHERE usuario_id = 2) GROUP BY chat_id HAVING n_usuarios = 2;
	}

	public function contactos( $contacto_id = null ){
		if (!is_array($this->contactos)){
			$sql = "
				SELECT u.id,
					   u.email,
					   u.nombre,
					   u.password
				FROM contacto c
				LEFT JOIN usuario u
				ON c.contacto_id = u.id
				WHERE c.usuario_id = {$this->id}
				AND c.bloqueado = 0
				ORDER BY u.nombre ASC";
			$result = Database::Query($sql);
			$this->contactos = self::List($result);
		}
		if (is_null($contacto_id)) return $this->contactos;
		return $this->contactos[$contacto_id] ?? false;
	}

	public function addContacto( $id_o_email ){
		$contacto = self::Get($id_o_email);
		$sql = "INSERT INTO contacto (usuario_id, contacto_id) VALUES ({$this->id}, {$contacto->id})";
		if( !(Database::query($sql)) ) die("No se creó contacto");
		$this->contactos = null;
		return true;
	}

	public function removeContacto( $contacto_id ){
		if (!($contacto = $this->contactos( $contacto_id ))) die( "No existe contacto");
		$sql = "UPDATE contacto SET bloqueado = 1 WHERE usuario_id = {$this->id} AND contacto_id = {$contacto->id}";
		if( !(Database::query($sql)) ) die("No se eliminó contacto");
		$this->contactos = null;
		return true;
	}

	public function verificar( $password ){
		return password_verify( $password, $this->password );
	}

	public function save(){
		$sql = "UPDATE usuario SET email = '{$this->email}', nombre = '{$this->nombre}', password = '{$this->password}' WHERE id = {$this->id}";
		if (Database::query($sql) === false) return false;
		return true;
	}

	public function delete(){
		$this->email = $this->nombre = $this->password = '';
		return $this->save();
	}

	private static function Hash( $password ){
		return password_hash($password, PASSWORD_BCRYPT);
	}

}