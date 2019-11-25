<?php

class Message extends DatabasePDO implements JsonSerializable {

	private $id;
	private $date;
	private $user_id;
	private $user_name;
	private $chat_id;
	private $attachment_id;
	private $content;
	private $mime_type;
	private $height;
	private $width;

	private function __construct($id, $date, $user_id, $user_name, $chat_id, $attachment_id, $content, $mime_type = null, $height = null, $width = null) {
		$this->id = $id;
		$this->date = $date;
		$this->user_id = $user_id;
		$this->user_name = $user_name;
		$this->chat_id = $chat_id;
		$this->attachment_id = $attachment_id;
		$this->content = $content;
		$this->mime_type = $mime_type;
		$this->height = $height;
		$this->width = $width;
	}

	public static function get($id) {
		if (($id = intval($id)) <= 0) throw new Exception(Text::error('message_id'));
		$sql = "
			SELECT m.id,
				   m.date,
				   m.user_id,
				   u.name user_name,
				   m.chat_id,
				   m.attachment_id,
				   m.content
			FROM message m
			LEFT JOIN user u
			ON m.user_id = u.id
			WHERE m.id = :id";
		self::query($sql, [':id' => $id]);
		if (!self::count())
			throw new Exception(Text::error('message_get'));
		$message = self::fetch();
		return new Message(
			$message['id'],
			$message['date'],
			$message['user_id'],
			$message['user_name'],
			$message['chat_id'],
			$message['attachment_id'],
			$message['content']
		);
	}

	public static function new($user_id, $chat_id, $content, $attachment = false) {
		$user_id = $user_id ? intval($user_id) : null;
		$chat_id = intval($chat_id);
		$attachment_id = $attachment && $attachment['error'] != 4 ? Attachment::new($attachment)->id() : null;
		if (empty($chat_id) || empty($content))
			throw new Exception(Text::error('message_invalid'));
		$sql = "INSERT INTO message (user_id, chat_id, attachment_id, content)
				VALUES (:userid, :chatid, :attid, :content)";
		self::query($sql, [
			':userid' => $user_id,
			':chatid' => $chat_id,
			':attid' => $attachment_id,
			':content' => $content,
		]);
		if (!self::count() || !($id = self::insertId()))
			throw new Exception(Text::error('message_new'));
		return Message::get($id);
	}

	public static function list() {
		$messages = [];
		while ($msg = self::fetch())
			$messages[$msg['id']] = new Message(
				$msg['id'],
				$msg['date'],
				$msg['user_id'],
				$msg['user_name'],
				$msg['chat_id'],
				$msg['attachment_id'],
				$msg['content'],
				$msg['mime_type'],
				$msg['height'],
				$msg['width']
			);
		return $messages;
	}

	public function id() {
		return $this->id;
	}

	public function date() {
		return $this->date;
	}

	public function user_id() {
		return $this->user_id;
	}

	public function user_name() {
		return $this->user_name;
	}

	public function user() {
		return User::get($this->user_id);
	}

	public function chat_id() {
		return $this->chat_id;
	}

	public function attachment_id() {
		return $this->attachment_id;
	}

	public function content() {
		if (!$this->user_id) return Text::translate($this->content);
		return $this->content;
	}

	public function chat() {
		return Chat::get($this->chat_id);
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'date' => $this->date,
			'user_id' => $this->user_id,
			'user_name' => $this->user_name,
			'chat_id' => $this->chat_id,
			'attachment_id' => $this->attachment_id,
			'content' => $this->content(),
			'mime_type' => $this->mime_type,
			'height' => $this->height,
			'width' => $this->width
		];
	}

}