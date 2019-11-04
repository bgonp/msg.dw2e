<?php

class Chat extends Database {
	
	private $id;
	private $fecha;
	private $nombre;
	private $mensajes;
	private $usuarios;
	private $n_usuarios;
	private $last_msg;
	private $last_readed;

	private function __construct($id, $fecha, $nombre, $n_usuarios, $last_msg = 0, $last_readed = 0){
		$this->id = $id;
		$this->fecha = $fecha;
		$this->nombre = $nombre;
		$this->n_usuarios = $n_usuarios;
		$this->last_msg = $last_msg ?? 0;
		$this->last_readed = $last_readed ?? 0;
	}

	public static function get($id) {
		if (!($id = intval($id))) throw new Exception("ID de chat inválido");
		$sql = "
			SELECT c.id,
				   c.fecha,
				   c.nombre,
				   COUNT(p.usuario_id) n_usuarios
			FROM chat c
			LEFT JOIN mensaje m ON c.id = m.chat_id
			LEFT JOIN participa p ON c.id = p.chat_id
			WHERE c.id = $id
			GROUP BY c.id";
		$chat_db = self::query($sql);
		if (!$chat_db || $chat_db->num_rows == 0) throw new Exception("No existe chat");
		$chat = $chat_db->fetch_assoc();
		return new Chat($chat['id'], $chat['fecha'], $chat['nombre'], $chat['n_usuarios']);
	}

	public static function new($nombre = "") {
		if (!Helper::validNombre($nombre) || !($nombre = self::escape($nombre))) throw new Exception("Nombre no válido");
		$sql = "INSERT INTO chat (nombre) VALUES ('$nombre')";
		self::query($sql);
		if (!($id = self::insertId())) throw new Exception("No se creó chat");
		return Chat::get($id);
	}

	public static function list($result_set){
		$chats = [];
		if (is_object($result_set) && get_class($result_set) == 'mysqli_result')
			while ($chat = $result_set->fetch_assoc())
				$chats[$chat['id']] = new Chat(
					$chat['id'],
					$chat['fecha'],
					$chat['nombre'],
					$chat['n_usuarios'],
					$chat['last_msg'],
					$chat['last_readed']
				);
		return $chats;
	}

	public function addUsuario($usuario) {
		if (is_numeric($usuario)) $usuario = Usuario::get($usuario);
		if (!is_object($usuario) || get_class($usuario) != 'Usuario') return false;
		$sql = "INSERT INTO participa (chat_id, usuario_id) VALUES ({$this->id}, {$usuario->id()})";
		if (!self::query($sql)) return false;
		$this->usuarios = null;
		return true;
	}

	public function removeUsuario($usuario) {
		if (!is_object($usuario) || get_class($usuario) != 'Usuario') return false;
		$sql = "DELETE FROM participa WHERE chat_id = {$this->id} AND usuario_id = {$usuario->id()}";
		if (!self::query($sql)) return false;
		$this->usuarios = null;
		return true;
	}

	public function addMensaje($usuario_id, $mensaje) {
		if (!$this->usuarios($usuario_id)) return false;
		if (!($mensaje = Mensaje::new($usuario_id, $this->id, $mensaje))) return false;
		$this->mensajes = null;
		return $mensaje;
	}

	public function id(){
		return $this->id;
	}

	public function fecha(){
		return $this->fecha;
	}

	public function nombre($nombre = null){
		if (is_null($nombre)) return $this->nombre;
		if (!Helper::validNombre($nombre) || !($nombre = self::escape($nombre))) return false;
		$this->nombre = $nombre;
		return true;
	}

	public function n_usuarios(){
		return $this->n_usuarios;
	}

	public function unread(){
		return $this->last_msg > $this->last_readed;
	}

	public function mensajes(){
		if (!is_array($this->mensajes)){
			$sql = "
				SELECT m.id,
					   DATE_FORMAT(m.fecha, '%H:%i %d/%m/%Y') fecha,
					   m.usuario_id,
					   u.nombre usuario_nombre,
					   m.chat_id,
					   m.contenido,
					   IF(m.id > {$this->last_readed}, 1, 0) unread
				FROM mensaje m
				LEFT JOIN usuario u
				ON m.usuario_id = u.id
				WHERE m.chat_id = {$this->id}
				ORDER BY m.fecha DESC
				LIMIT 100";
			$result = self::query($sql);
			$this->mensajes = Mensaje::list($result);
		}
		return $this->mensajes;
	}

	public function usuarios( $usuario_id = null){
		if (!is_array($this->usuarios)){
			$sql = "
				SELECT u.id,
					   u.email,
					   u.nombre,
					   u.password,
					   u.avatar
				FROM usuario u
				LEFT JOIN participa p
				ON u.id = p.usuario_id
				WHERE p.chat_id = {$this->id}
				ORDER BY u.nombre ASC";
			$result = self::query($sql);
			$this->usuarios = Usuario::list($result);
		}
		if (is_null($usuario_id)) return $this->usuarios;
		return $this->usuarios[$usuario_id] ?? false;
	}

	public function save() {
		$sql = "
			UPDATE chat SET
			nombre = '{$this->nombre}'
			WHERE id = {$this->id}";
		if (self::query($sql) === false) return false;
		return true;
	}

	public function newMensajes($last_id) {
		$sql = "
			SELECT m.id,
				   DATE_FORMAT(m.fecha, '%H:%i %d/%m/%Y') fecha,
				   m.usuario_id,
				   u.nombre usuario_nombre,
				   m.chat_id,
				   m.contenido
			FROM mensaje m
			LEFT JOIN usuario u
			ON m.usuario_id = u.id
			WHERE m.chat_id = {$this->id}
			AND m.id > $last_id
			ORDER BY m.fecha DESC
			LIMIT 100";
		$result = self::query($sql);
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	public function toArray($depth = 1){
		$chat = [
			'id' => $this->id,
			'fecha' => $this->fecha,
			'nombre' => $this->nombre,
			'last_msg' => $this->last_msg,
			'last_readed' => $this->last_readed
		];
		if ($depth > 0) {
			$depth--;
			$chat['messages'] = [];
			$chat['users'] = [];
			foreach ($this->mensajes() as $mensaje)
				$chat['messages'][] = $mensaje->toArray($depth);
			foreach ($this->usuarios() as $usuario)
				$chat['users'][] = $usuario->toArray($depth);
		}
		return $chat;
	}

}