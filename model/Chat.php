<?php

class Chat extends Database {
	
	private $id;
	private $fecha;
	private $nombre;
	private $descripcion;
	private $imagen;
	private $oculto;
	private $cerrado;
	private $mensajes;
	private $usuarios;
	private $n_mensajes;
	private $n_usuarios;

	private function __construct($id, $fecha, $nombre, $descripcion, $imagen, $oculto, $cerrado, $n_mensajes, $n_usuarios){
		$this->id = $id;
		$this->fecha = $fecha;
		$this->nombre = $nombre;
		$this->descripcion = $descripcion;
		$this->imagen = $imagen;
		$this->oculto = $oculto;
		$this->cerrado = $cerrado;
		$this->n_mensajes = $n_mensajes;
		$this->n_usuarios = $n_usuarios;
	}

	public static function get($id){
		if (!($id = intval($id))) die("ID de chat inválido");
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
			WHERE c.id = $id
			GROUP BY c.id";
		$chat_db = self::query($sql);
		if (!$chat_db || $chat_db->num_rows == 0) die("No existe chat");
		$chat = $chat_db->fetch_assoc();
		return new Chat($chat['id'], $chat['fecha'], $chat['nombre'], $chat['descripcion'], $chat['imagen'], $chat['oculto'], $chat['cerrado'], $chat['n_mensajes'], $chat['n_usuarios']);
	}

	public static function new($nombre, $descripcion, $imagen = "", $oculto = false, $cerrado = false){
		$nombre = self::escape($nombre);
		$descripcion = self::escape($descripcion);
		$imagen = self::escape($imagen);
		$oculto = $oculto ? 1 : 0;
		$cerrado = $cerrado ? 1 : 0;
		if (empty($nombre)) die("No se creó chat");
		$sql = "INSERT INTO chat (nombre, descripcion, imagen, oculto, cerrado) VALUES ('$nombre', '$descripcion', '$imagen', $oculto, $cerrado)";
		self::query($sql);
		if (!($id = self::insertId())) die("No se creó chat");
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
					$chat['descripcion'],
					$chat['imagen'],
					$chat['oculto'],
					$chat['cerrado'],
					boolval($chat['oculto']),
					boolval($chat['destacado']),
					$chat['n_mensajes'],
					$chat['n_usuarios']
				);
		return $chats;
	}

	public function addUsuario( $usuario ){
		if (is_numeric($usuario)) $usuario = Usuario::get($usuario);
		if (!is_object($usuario) || get_class($usuario) != 'Usuario') return false;
		$sql = "INSERT INTO participa (chat_id, usuario_id) VALUES ({$this->id}, {$usuario->id()})";
		if (!self::query($sql)) return false;
		$this->usuarios = null;
		return true;
	}

	public function removeUsuario( $usuario ){
		if (!is_object($usuario) || get_class($usuario) != 'Usuario') return false;
		$sql = "DELETE FROM participa WHERE chat_id = {$this->id} AND usuario_id = {$usuario->id}";
		if (!self::query($sql)) return false;
		$this->usuarios = null;
		return true;
	}

	public function addMensaje( $usuario_id, $mensaje ){
		if (!$this->usuarios($usuario_id)) return "A";
		if (!($mensaje = Mensaje::new($usuario_id, $this->id, $mensaje))) return "B";
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
		if (!($nombre = self::escape($nombre))) return false;
		$this->nombre = $nombre;
		return true;
	}

	public function descripcion($descripcion = null){
		if (is_null($descripcion)) return $this->descripcion;
		if (!($descripcion = self::escape($descripcion))) return false;
		$this->descripcion = $descripcion;
		return true;
	}

	public function imagen($imagen = null){
		if (is_null($imagen)) return $this->imagen;
		if (!($imagen = self::escape($imagen))) return false;
		$this->imagen = $imagen;
		return true;
	}

	public function oculto($oculto = null){
		if (is_null($oculto)) return $this->oculto;
		$this->oculto = boolval($oculto);
		return true;
	}

	public function cerrado($cerrado = null){
		if (is_null($cerrado)) return $this->cerrado;
		$this->cerrado = boolval($cerrado);
		return true;
	}

	public function n_mensajes(){
		return $this->n_mensajes;
	}

	public function n_usuarios(){
		return $this->n_usuarios;
	}

	public function mensajes(){
		if (!is_array($this->mensajes)){
			$sql = "
				SELECT m.id,
					   m.fecha,
					   m.fecha_edit,
					   m.usuario_id,
					   u.nombre usuario_nombre,
					   m.chat_id,
					   m.contenido,
					   m.oculto,
					   m.destacado
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
					   u.password
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

}