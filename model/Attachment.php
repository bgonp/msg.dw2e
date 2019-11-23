<?php

class Attachment extends Database {

	private $id;
	private $date;
	private $mime_type;
	private $height;
	private $width;
	private $filename;
	private $chat_id;
	private $chat;

	private function __construct($id, $date, $mime_type, $height, $width, $filename, $chat_id) {
		$this->id = $id;
		$this->date = $date;
		$this->mime_type = $mime_type;
		$this->height = $height;
		$this->width = $width;
		$this->filename = $filename;
		$this->chat_id = $chat_id;
	}

	public static function get($id) {
		if (!($id = intval($id))) throw new Exception(Text::error('attachment_id'));
		$sql = "
			SELECT a.id,
				   a.date,
				   a.mime_type,
				   a.height,
				   a.width,
				   a.filename,
				   m.chat_id
			FROM attachment a
			LEFT JOIN message m
			ON a.id = m.attachment_id
			WHERE a.id = $id";
		$att = self::query($sql);
		if ($att->num_rows == 0) throw new Exception(Text::error('attachment_get'));
		$att = $att->fetch_assoc();
		return new Attachment($att['id'], $att['date'], $att['mime_type'], $att['height'], $att['width'], $att['filename'], $att['chat_id']);
	}

	public static function new($file) {
		if (!$file || $file['error'])
			throw new Exception(Text::error('attachment_invalid'));
		if (!($mime_type = self::escape($file['type'])) || !($filename = self::escape($file['name'])))
			throw new Exception(Text::error('attachment_data'));
		if (!($fileinfo = Helper::uploadAttachment($file)))
			throw new Exception(Text::error('attachment_upload'));
		$sql = "INSERT INTO attachment (mime_type, height, width, filename)
				VALUES ('$mime_type', {$fileinfo['height']}, {$fileinfo['width']}, '{$fileinfo['name']}')";
		if (!self::query($sql) || !($id = self::insertId())) {
			Helper::removeAttachment($fileinfo['name']);
			throw new Exception(Text::error('attachment_new'));
		}
		return Attachment::get($id);		
	}

	public function id() {
		return $this->id;
	}

	public function date() {
		return $this->date;
	}

	public function mime_type() {
		return $this->mime_type;
	}

	public function height() {
		return $this->height;
	}

	public function width() {
		return $this->width;
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