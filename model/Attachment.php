<?php

class Attachment extends Database {

	private $id;
	private $date_upload;
	private $mime_type;
	private $filename;
	private $chat_id;
	private $chat;

	private function __construct($id, $date_upload, $mime_type, $filename, $chat_id) {
		$this->id = $id;
		$this->date_upload = $date_upload;
		$this->mime_type = $mime_type;
		$this->filename = $filename;
		$this->chat_id = $chat_id;
	}

	public static function get($id) {
		if (!($id = intval($id))) throw new Exception("ID de attachment inválido");
		$sql = "
			SELECT a.id,
				   a.date_upload,
				   a.mime_type,
				   a.filename,
				   m.chat_id
			FROM attachment a
			LEFT JOIN mensaje m
			ON a.id = m.attachment_id
			WHERE a.id = $id";
		$att = self::query($sql);
		if ($att->num_rows == 0) throw new Exception("No existe attachment");
		$att = $att->fetch_assoc();
		return new Attachment($att['id'], $att['date_upload'], $att['mime_type'], $att['filename'], $att['chat_id']);		
	}

	public static function new($file) {
		if (!$file || $file['error'])
			throw new Exception("Adjunto no válido");
		if (!($mime_type = self::escape($file['type'])) || !($filename = self::escape($file['name'])))
			throw new Exception("Datos no válidos");
		if (!Helper::uploadAttachment($file))
			throw new Exception("No se pudo subir el archivo");
		$sql = "INSERT INTO attachment (mime_type, filename) VALUES ('$mime_type', '$filename')";
		if (!self::query($sql) || !($id = self::insertId())) throw new Exception("No se creó el attachment");
		return Attachment::get($id);		
	}

	public function id() {
		return $this->id;
	}

	public function date_upload() {
		return $this->date_upload;
	}

	public function mime_type() {
		return $this->mime_type;
	}

	public function filename() {
		return $this->filename;
	}

	public function isImage() {
		return explode('/', $this->mime_type)[0] == 'image';
	}

	public function chat() {
		if (is_null($this->chat))
			$this->chat = Chat::get($this->chat_id);
		return $this->chat;
	}
	
}