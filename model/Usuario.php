<?php

class Usuario extends Database {

	private $id;
	private $email;
	private $nombre;
	private $password;
	private $avatar;
	private $chats;
	private $contactos;

	private function __construct( $id, $email, $nombre, $password ){
		$this->id = $id;
		$this->email = $email;
		$this->nombre = $nombre;
		$this->password = $password;
	}

	public static function get( $id_o_email, $password = null ){
		if (is_numeric($id_o_email)){
			$id = intval($id_o_email);
			if ($id <= 0) throw new Exception("Identificación de usuario inválida");
			$sql = "SELECT * FROM usuario WHERE id = $id";
		} else {
			$email = self::escape($id_o_email);
			if (empty($email)) throw new Exception("Identificación de usuario inválida");
			$sql = "SELECT * FROM usuario WHERE email = '$email'";
		}
		$user_db = self::query($sql);
		if ($user_db->num_rows == 0) throw new Exception("No existe usuario");
		$user = $user_db->fetch_assoc();
		$usuario = new Usuario($user['id'], $user['email'], $user['nombre'], $user['password']);
		if (!empty($password) && !$usuario->verificar($password)) throw new Exception("Autentificación errónea");
		return $usuario;
	}

	public static function new( $email, $nombre, $password, $avatar = "" ){
		$email = self::escape($email);
		$nombre = self::escape($nombre);
		$password = self::hash($password);
		$avatar = self::escape($avatar);
		if (empty($email) || empty($nombre) || empty($password)) throw new Exception("Faltan datos de usuario");
		$sql = "INSERT INTO usuario (email, nombre, password, avatar) VALUES ('$email', '$nombre', '$password', '$avatar')";
		self::query($sql);
		if( !($id = self::insertId()) ) throw new Exception("No se creó usuario, quizá el e-mail ya esta en uso");
		return new Usuario($id, $email, $nombre, $password);
	}

	public static function list( $result_set ){
		$usuarios = [];
		if (is_object($result_set) && get_class($result_set) == 'mysqli_result')
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
		if ( !($email = self::escape($email)) ) return false;
		$this->email = $email;
		return true;
	}

	public function nombre( $nombre = null ){
		if (is_null($nombre)) return $this->nombre;
		if ( !($nombre = self::escape($nombre)) ) return false;
		$this->nombre = $nombre;
		return true;
	}

	public function password( $password ){
		if (!($password = self::hash($password))) return false;
		$this->password = $password;
		return true;
	}

	public function avatar( $avatar = null ){
		if (is_null($avatar)) return $this->avatar;
		if ( !($avatar = self::escape($avatar)) ) return false;
		$this->avatar = $avatar;
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
			$result = self::query($sql);
			$this->chats = Chat::list($result);
		}
		if (is_null($chat_id)) return $this->chats;
		return $this->chats[$chat_id] ?? false;
	}

	public function getPrivateChat($usuario_id) {
		$usuario_id = intval($usuario_id);
		$sql = "
			SELECT c.id, COUNT(*) n_usuarios
			FROM participa p
			LEFT JOIN chat c
			ON p.chat_id = c.id
			WHERE c.privado = 1
			AND (p.usuario_id = {$this->id}	OR p.usuario_id = $usuario_id)
			GROUP BY c.id
			HAVING n_usuarios = 2";
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
			$result = self::query($sql);
			$this->contactos = self::list($result);
		}
		if (is_null($contacto_id)) return $this->contactos;
		return $this->contactos[$contacto_id] ?? false;
	}

	public function addContacto( $id_o_email ){
		$contacto = self::get($id_o_email);
		$sql = "INSERT INTO contacto (usuario_id, contacto_id) VALUES ({$this->id}, {$contacto->id})";
		if( !(self::query($sql)) ) throw new Exception("No se creó contacto");
		$this->contactos = null;
		return true;
	}

	public function removeContacto( $contacto_id ){
		if (!($contacto = $this->contactos( $contacto_id ))) throw new Exception( "No existe contacto");
		$sql = "UPDATE contacto SET bloqueado = 1 WHERE usuario_id = {$this->id} AND contacto_id = {$contacto->id}";
		if( !(self::query($sql)) ) throw new Exception("No se eliminó contacto");
		$this->contactos = null;
		return true;
	}

	public function verificar( $password ){
		return password_verify( $password, $this->password );
	}

	public function save(){
		$sql = "UPDATE usuario SET email = '{$this->email}', nombre = '{$this->nombre}', password = '{$this->password}' WHERE id = {$this->id}";
		if (self::query($sql) === false) return false;
		return true;
	}

	public function delete(){
		$this->email = $this->nombre = $this->password = '';
		return $this->save();
	}

	private static function hash( $password ){
		return password_hash($password, PASSWORD_BCRYPT);
	}

}