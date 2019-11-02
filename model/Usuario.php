<?php

class Usuario extends Database {

	private $id;
	private $email;
	private $nombre;
	private $password;
	private $avatar;
	private $chats;
	private $amigos;
	private $pendientes;

	private function __construct($id, $email, $nombre, $password, $avatar) {
		$this->id = $id;
		$this->email = $email;
		$this->nombre = $nombre;
		$this->password = $password;
		$this->avatar = $avatar;
	}

	public static function get($id_o_email, $password = null) {
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
		$usuario = new Usuario($user['id'], $user['email'], $user['nombre'], $user['password'], $user['avatar']);
		if (!empty($password) && !$usuario->verificar($password)) throw new Exception("Autentificación errónea");
		return $usuario;
	}

	public static function new($email, $nombre, $password, $avatar = null) {
		if (!Helper::validEmail($email)) throw new Exception("E-mail no válido");
		if (!Helper::validNombre($nombre)) throw new Exception("Nombre no válido");
		if (!Helper::validPassword($password)) throw new Exception("Contraseña no válida");
		if (!is_null($avatar) && !($avatar = Helper::uploadImagen($avatar))) throw new Exception("Avatar no válido");
		$email = self::escape($email);
		$nombre = self::escape($nombre);
		$password = self::hash($password);
		$sql = "INSERT INTO usuario (email, nombre, password, avatar) VALUES ('$email', '$nombre', '$password', '$avatar')";
		self::query($sql);
		if( !($id = self::insertId()) ) throw new Exception("No se creó usuario, quizá el e-mail ya esta en uso");
		return new Usuario($id, $email, $nombre, $password, $avatar);
	}

	public static function list($result_set) {
		$usuarios = [];
		if (is_object($result_set) && get_class($result_set) == 'mysqli_result')
			while ($user = $result_set->fetch_assoc())
				$usuarios[$user['id']] = new Usuario(
					$user['id'],
					$user['email'],
					$user['nombre'],
					$user['password'],
					$user['avatar']
				);
		return $usuarios;
	}

	public function id() {
		return $this->id;
	}

	public function email($email = null) {
		if (is_null($email)) return $this->email;
		if ( !Helper::validEmail($email) || !($email = self::escape($email)) ) return false;
		$this->email = $email;
		return true;
	}

	public function nombre($nombre = null) {
		if (is_null($nombre)) return $this->nombre;
		if (!Helper::validNombre($nombre) || !($nombre = self::escape($nombre))) return false;
		$this->nombre = $nombre;
		return true;
	}

	public function password($password) {
		if ( !Helper::validPassword($password) || !($password = self::hash($password))) return false;
		$this->password = $password;
		return true;
	}

	public function avatar($avatar = null) {
		if (is_null($avatar)) return $this->avatar;
		if (!($avatar = Helper::uploadImagen($avatar))) return false;
		Helper::removeImagen($this->avatar);
		$this->avatar = $avatar;
		return true;
	}

	public function chats($chat_id = null) {
		if (!is_array($this->chats)){
			$sql = "
				SELECT c.id,
					   c.fecha,
					   c.nombre,
					   c.privado,
					   COUNT(m.id) n_mensajes,
					   COUNT(p.usuario_id) n_usuarios,
					   MAX(m.fecha) last_msg
				FROM chat c
				LEFT JOIN mensaje m ON c.id = m.chat_id
				LEFT JOIN participa p ON c.id = p.chat_id
				WHERE p.usuario_id = {$this->id}
				GROUP BY c.id
				ORDER BY last_msg DESC";
			$result = self::query($sql);
			$this->chats = Chat::list($result);
		}
		if (is_null($chat_id)) return $this->chats;
		return $this->chats[intval($chat_id)] ?? false;
	}

	public function readChat($chat_id) {
		$chat_id = intval($chat_id);
		$sql = "
			UPDATE participa p SET p.last_readed = (
				SELECT MAX(m.id) FROM mensaje m WHERE m.chat_id = {$chat_id}
			) WHERE p.usuario_id = {$this->id} AND p.chat_id = {$chat_id};";
		return self::query($sql) !== false;
	}

	public function newChats() {
		$sql = "
			SELECT c.id,
				   c.fecha,
				   c.nombre,
				   c.privado,
				   COUNT(m.id) n_mensajes,
				   COUNT(p.usuario_id) n_usuarios,
				   MAX(m.fecha) last_msg
			FROM mensaje m
			LEFT JOIN chat c ON m.chat_id = c.id
			LEFT JOIN participa p ON c.id = p.chat_id
			WHERE p.usuario_id = {$this->id}
			AND (m.id > p.last_readed OR p.last_readed IS NULL)
			GROUP BY c.id
			ORDER BY last_msg DESC";
		$result = self::query($sql);
		return $result->fetch_all(MYSQLI_ASSOC);
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

	public function amigos($amigo_id = null) {
		if (!is_array($this->amigos))
			$this->amigos = self::contactos($amigo_id, Helper::ACEPTADO);
		if (is_null($amigo_id)) return $this->amigos;
		return $this->amigos[intval($amigo_id)] ?? false;
	}

	public function pendientes($pendiente_id = null) {
		if (!is_array($this->pendientes))
			$this->pendientes = self::contactos($pendiente_id, Helper::PENDIENTE);
		if (is_null($pendiente_id)) return $this->pendientes;
		return $this->pendientes[intval($pendiente_id)] ?? false;
	}

	private function contactos($contacto_id, $estado) {
		$sql = "
			SELECT u.id,
				   u.email,
				   u.nombre,
				   u.password,
				   u.avatar
			FROM usuario u
			WHERE id IN (
				SELECT IF(c.usuario_1_id = {$this->id}, c.usuario_2_id, c.usuario_1_id) usuario_id
				FROM contacto c
				WHERE c.estado = {$estado}
				AND (c.usuario_1_id = {$this->id} OR c.usuario_2_id = {$this->id})
			)
			ORDER BY u.nombre ASC";
		$result = self::query($sql);
		return self::list($result);
	}

	public function addContacto($id_o_email) {
		$contacto = self::get($id_o_email);
		if ($contacto->id === $this->id) throw new Exception("No puedes ser tu propio contacto");		
		$user1_id = min($this->id, $contacto->id);
		$user2_id = max($this->id, $contacto->id);
		$sql = "
			INSERT INTO contacto (usuario_1_id, usuario_2_id, usuario_estado_id)
			VALUES ({$user1_id}, {$user2_id}, {$this->id})";
		if (!self::query($sql)) throw new Exception("No se creó contacto");
		return true;
	}

	public function updateContacto($contacto_id, $estado) {
		if (!is_numeric($estado)) throw new Exception( "Error de estado de contacto");
		$estado = intval($estado);
		$user1_id = min($this->id, $contacto_id);
		$user2_id = max($this->id, $contacto_id);
		if ($estado === Helper::ACEPTADO || $estado === Helper::RECHAZADO)
			$condition = "estado = ".Helper::PENDIENTE." AND usuario_estado_id = {$contacto_id}";
		else if ($estado == Helper::BLOQUEADO)
			$condition = "estado = ".Helper::ACEPTADO;
		else
			throw new Exception("Error de estado de contacto");			
		$sql = "
			UPDATE contacto SET estado = {$estado}, usuario_estado_id = {$this->id}
			WHERE usuario_1_id = {$user1_id}
			AND usuario_2_id = {$user2_id}
			AND $condition";
		if (!self::query($sql)) throw new Exception("No se actualizó contacto");
		$this->amigos = null;
		$this->pendientes = null;
		return true;
	}

	public function verificar($password) {
		return password_verify( $password, $this->password );
	}

	public function save() {
		$sql = "
			UPDATE usuario SET
			email = '{$this->email}',
			nombre = '{$this->nombre}',
			password = '{$this->password}',
			avatar = '{$this->avatar}'
			WHERE id = {$this->id}";
		if (self::query($sql) === false) return false;
		return true;
	}

	public function delete() {
		$this->email = $this->nombre = $this->password = '';
		return $this->save();
	}

	public function toArray($depth = 1) {
		$usuario = [
			'id' => $this->id,
			'email' => $this->email,
			'nombre' => $this->nombre
		];
		if ($depth > 0) {
			$depth--;
			$usuario['chats'] = [];
			$usuario['amigos'] = [];
			$usuario['pendientes'] = [];
			foreach ($this->chats() as $chat)
				$usuario['chats'][] = $chat->toArray($depth);
			foreach ($this->amigos() as $amigo)
				$usuario['amigos'][] = $amigo->toArray($depth);
			foreach ($this->pendientes() as $pendiente)
				$usuario['pendientes'][] = $pendiente->toArray($depth);
		}
		return $usuario;
	}

	private static function hash($password) {
		return password_hash($password, PASSWORD_BCRYPT);
	}

}