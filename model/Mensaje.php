<?php

class Mensaje extends Database {

	private $id;
	private $fecha;
	private $usuario_id;
	private $usuario_nombre;
	private $chat_id;
	private $contenido;
	private $unread;

	private function __construct($id, $fecha, $usuario_id, $usuario_nombre, $chat_id, $contenido, $unread = false) {
		$this->id = $id;
		$this->fecha = $fecha;
		$this->usuario_id = $usuario_id;
		$this->usuario_nombre = $usuario_nombre;
		$this->chat_id = $chat_id;
		$this->contenido = $contenido;
		$this->unread = $unread;
	}

	public static function get($id) {
		if (($id = intval($id)) <= 0) throw new Exception("ID de mensaje inválido");
		$sql = "
			SELECT m.id,
				   m.fecha,
				   m.usuario_id,
				   u.nombre usuario_nombre,
				   m.chat_id,
				   m.contenido
			FROM mensaje m
			LEFT JOIN usuario u
			ON m.usuario_id = u.id
			WHERE m.id = $id";
		$mensaje_db = self::query($sql);
		if (!$mensaje_db || $mensaje_db->num_rows == 0) throw new Exception("No existe mensaje");
		$mensaje = $mensaje_db->fetch_assoc();
		return new Mensaje(
			$mensaje['id'],
			$mensaje['fecha'],
			$mensaje['usuario_id'],
			$mensaje['usuario_nombre'],
			$mensaje['chat_id'],
			$mensaje['contenido']
		);
	}

	public static function new($usuario_id, $chat_id, $contenido) {
		$usuario_id = $usuario_id == 0 ? 'NULL' : intval($usuario_id);
		$chat_id = intval($chat_id);
		$contenido = self::escape($contenido);
		if (empty($chat_id) || empty($contenido)) throw new Exception("A No se creó mensaje");
		$sql = "INSERT INTO mensaje (usuario_id, chat_id, contenido) VALUES ($usuario_id, $chat_id, '$contenido')";
		self::query($sql);
		if( !($id = self::insertId()) ) throw new Exception("B No se creó mensaje");
		return Mensaje::get($id);
	}

	public static function list($result_set) {
		$mensajes = [];
		if (is_object($result_set) && get_class($result_set) == 'mysqli_result')
			while ($msg = $result_set->fetch_assoc())
				$mensajes[$msg['id']] = new Mensaje(
					$msg['id'],
					$msg['fecha'],
					$msg['usuario_id'],
					$msg['usuario_nombre'],
					$msg['chat_id'],
					$msg['contenido'],
					$msg['unread']
				);
		return $mensajes;
	}

	public function id() {
		return $this->id;
	}

	public function fecha() {
		return $this->fecha;
	}

	public function usuario_id() {
		return $this->usuario_id;
	}

	public function usuario_nombre() {
		return $this->usuario_nombre;
	}

	public function usuario() {
		return Usuario::get($this->usuario_id);
	}

	public function chat_id() {
		return $this->chat_id;
	}

	public function chat() {
		return Chat::get($this->chat_id);
	}

	public function contenido($contenido = null) {
		if (is_null($contenido)) return $this->contenido;
		if( !($contenido = self::escape($contenido)) ) return false;
		$this->contenido = $contenido;
		return true;
	}

	public function unread() {
		return $this->unread;
	}

	public function save() {
		$sql = "UPDATE usuario SET email = '{$this->email}', nombre = '{$this->nombre}', password = '{$this->password}' WHERE id = {$this->id}";
		if (self::query($sql) === false) return false;
		return true;
	}

	public function toArray($depth = 1) {
		$mensaje = [
			'id' => $this->id,
			'fecha' => $this->fecha,
			'usuario_id' => $this->usuario_id,
			'usuario_nombre' => $this->usuario_nombre,
			'chat_id' => $this->chat_id,
			'contenido' => $this->contenido
		];
		if ($depth > 0) {
			$depth--;
			$mensaje['chat'] = $this->chat()->toArray($depth);
			$mensaje['usuario'] = $this->usuario()->toArray($depth);
		}
		return $mensaje;
	}

}