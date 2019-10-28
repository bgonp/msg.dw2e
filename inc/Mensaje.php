<?php

require_once "autoload.php";

class Mensaje {

	private $id;
	private $fecha;
	private $fecha_edit;
	private $usuario_id;
	private $usuario_nombre;
	private $chat_id;
	private $contenido;
	private $oculto;
	private $destacado;

	private function __construct($id, $fecha, $fecha_edit, $usuario_id, $usuario_nombre, $chat_id, $contenido, $oculto, $destacado){
		$this->id = $id;
		$this->fecha = $fecha;
		$this->fecha_edit = $fecha_edit;
		$this->usuario_id = $usuario_id;
		$this->usuario_nombre = $usuario_nombre;
		$this->chat_id = $chat_id;
		$this->contenido = $contenido;
		$this->oculto = $oculto;
		$this->destacado = $destacado;
	}

	public static function Get($id){
		if (($id = intval($id)) <= 0) die("ID de mensaje inválido");
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
			WHERE m.id = $id";
		$mensaje_db = Database::query($sql);
		if (!$mensaje_db || $mensaje_db->num_rows == 0) die("No existe mensaje");
		$mensaje = $mensaje_db->fetch_assoc();
		return new Mensaje(
			$mensaje['id'],
			$mensaje['fecha'],
			$mensaje['fecha_edit'],
			$mensaje['usuario_id'],
			$mensaje['usuario_nombre'],
			$mensaje['chat_id'],
			$mensaje['contenido'],
			boolval($mensaje['oculto']),
			boolval($mesaje['destacado'])
		);
	}

	public static function New($usuario_id, $chat_id, $contenido){
		$usuario_id = intval($usuario_id);
		$chat_id = intval($chat_id);
		$contenido = Database::Escape($contenido);
		$oculto = $oculto ? 1 : 0;
		$destacado = $oculto ? 1 : 0;
		if (empty($usuario_id) || empty($chat_id) || empty($contenido)) die("No se creó mensaje");
		$sql = "INSERT INTO mensaje (usuario_id, chat_id, contenido) VALUES ($usuario_id, $chat_id, '$contenido')";
		Database::query($sql);
		if( !($id = Database::InsertId()) ) die("No se creó mensaje");
		return Mensaje::Get($id);
	}

	public static function List($result_set){
		$mensajes = [];
		if (get_class($result_set) == 'mysqli_result')
			while ($msg = $result_set->fetch_assoc())
				$mensajes[$msg['id']] = new Mensaje(
					$msg['id'],
					$msg['fecha'],
					$msg['fecha_edit'],
					$msg['usuario_id'],
					$msg['usuario_nombre'],
					$msg['chat_id'],
					$msg['contenido'],
					boolval($msg['oculto']),
					boolval($msg['destacado'])
				);
		return $mensajes;
	}

	public function id(){
		return $this->id;
	}

	public function fecha(){
		return $this->fecha;
	}

	public function fecha_edit(){
		return $this->fecha_edit;
	}

	public function usuario_id(){
		return $this->usuario_id;
	}

	public function usuario_nombre(){
		return $this->usuario_nombre;
	}

	public function usuario(){
		return Usuario::Get($this->usuario_id);
	}

	public function chat_id(){
		return $this->chat_id;
	}

	public function chat(){
		return Chat::Get($this->chat_id);
	}

	public function contenido( $contenido = null ){
		if (is_null($contenido)) return $this->contenido;
		if( !($contenido = Database::Escape($contenido)) ) return false;
		$this->contenido = $contenido;
		return true;
	}

	public function oculto( $oculto = null ){
		if (is_null($oculto)) return $this->oculto;
		$this->oculto = boolval($oculto);
		return true;
	}

	public function destacado( $destacado = null ){
		if (is_null($destacado)) return $this->destacado;
		$this->destacado = boolval($destacado);
		return true;
	}

	public function save(){
		$sql = "UPDATE usuario SET email = '{$this->email}', nombre = '{$this->nombre}', password = '{$this->password}' WHERE id = {$this->id}";
		if (Database::query($sql) === false) return false;
		return true;
	}

}