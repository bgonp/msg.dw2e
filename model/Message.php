<?php
/**
 * Message model that represents a stored message. Every message belongs to a chat room
 * and is written by a user. A message can also hold an attachment.
 * 
 * This class extends Database in order to use its methods to connect and handle
 * database queries.
 * 
 * This class implements JsonSerializable interface in order to can be casted to a json
 * string.
 * 
 * @package model
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class Message extends Database implements JsonSerializable {

	/** @var integer $id Stored message ID */
	private $id;
	/** @var string $date Timestamp when the message was written */
	private $date;
	/** @var integer $user_id User ID who wrote this message */
	private $user_id;
	/** @var string $user_name User name who wrote this message */
	private $user_name;
	/** @var integer $chat_id Chat ID where this message was published */
	private $chat_id;
	/** @var string $content Content text of this message */
	private $content;
	/** @var integer $attachment_id Attachment held by this message */
	private $attachment_id;
	/** @var string $mime_type Mime type of attached file */
	private $mime_type;
	/** @var integer $height Height of attached image */
	private $height;
	/** @var integer $width Width of attached image */
	private $width;

	/**
	 * Private constructor. An object can't be constructed directly, but through static
	 * factory methods.
	 * 
	 * @param integer $id Stored message ID
	 * @param string $date Timestamp when the message was written
	 * @param integer $user_id User ID who wrote this message
	 * @param string $user_name User name who wrote this message
	 * @param integer $chat_id Chat ID where this message was published
	 * @param string $content Content text of this message
	 * @param integer $attachment_id (optional) Attachment held by this message
	 * @param string $mime_type (optional) Mime type of attached file
	 * @param integer $height (optional) Height of attached image
	 * @param integer $width (optional) Width of attached image
	 */
	private function __construct($id, $date, $user_id, $user_name, $chat_id, $content, $attachment_id = null, $mime_type = null, $height = null, $width = null) {
		$this->id = $id;
		$this->date = $date;
		$this->user_id = $user_id;
		$this->user_name = $user_name;
		$this->chat_id = $chat_id;
		$this->content = $content;
		$this->attachment_id = $attachment_id;
		$this->mime_type = $mime_type;
		$this->height = $height;
		$this->width = $width;
	}

	/**
	 * Static factory method that returns a Message object from a register from database.
	 * 
	 * @param integer $id ID of the stored message
	 * @return Message Message object identified by passed ID in database
	 * @throws Exception If message doesn't exists
	 */
	public static function get($id) {
		if (($id = intval($id)) <= 0)
			throw new Exception(Text::error('message_id'));
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
			$message['content'],
			$message['attachment_id']
		);
	}

	/**
	 * Static factory method that store a register in database and returns the
	 * corresponding Message object.
	 * 
	 * @param integer $user_id User ID who write the message
	 * @param integer $chat_id Chat ID where the message is written
	 * @param string $content Content text of message
	 * @param array $attachment (optional) Array with attachment. Structure of $_FILES superglobal
	 * @return Message Message object just created and stored
	 * @throws Exception If message couldn't be stored
	 */
	public static function create($user_id, $chat_id, $content, $attachment = false) {
		$user_id = $user_id ? intval($user_id) : null;
		$chat_id = intval($chat_id);
		$attachment_id = $attachment && $attachment['error'] != 4 ? Attachment::create($attachment)->id() : null;
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

	/**
	 * Static factory method that returns an array of Message objects from last executed
	 * query. This must be called after execute a query that returns the following info:
	 * <li>id - Message ID
	 * <li>date - Message creation date
	 * <li>user_id - Author ID
	 * <li>user_name - Author name 
	 * <li>chat_id - Chat ID where message is published
	 * <li>content - Content text
	 * <li>attachment_id - (optional) Attachment ID 
	 * <li>mime_type - (optional) Attachment mime type
	 * <li>height - (optional) Attached image height
	 * <li>width - (optional) Attached image width
	 * 
	 * @return array Associative array of Message objects. Keys will be message ids
	 */
	public static function gets() {
		$messages = [];
		while ($msg = self::fetch())
			$messages[$msg['id']] = new Message(
				$msg['id'],
				$msg['date'],
				$msg['user_id'],
				$msg['user_name'],
				$msg['chat_id'],
				$msg['content'],
				$msg['attachment_id'] ?? null,
				$msg['mime_type'] ?? null,
				$msg['height'] ?? null,
				$msg['width'] ?? null
			);
		return $messages;
	}

	/**
	 * Return id of object. Id is the primary key in database
	 * 
	 * @return int ID of current Message object
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Return date property of object. It represents the date when the message was created.
	 * 
	 * @return string YYYY-MM-DD HH:MM:SS format
	 */
	public function date() {
		return $this->date;
	}

	/**
	 * Return user_id property of object. It represents the user id who wrote the message.
	 * 
	 * @return int Author user ID
	 */
	public function user_id() {
		return $this->user_id;
	}

	/**
	 * Return user_name property of object. It represents the user name who wrote the message.
	 * 
	 * @return string Author user name
	 */
	public function user_name() {
		return $this->user_name;
	}

	/**
	 * Return chat_id property of object. It represents the chat where the message was published.
	 * 
	 * @return int Author user ID
	 */
	public function chat_id() {
		return $this->chat_id;
	}

	/**
	 * Return attachment_id property of object (if has). It represents the attachment that belongs
	 * to this message.
	 * 
	 * @return int|null Attachment ID
	 */
	public function attachment_id() {
		return $this->attachment_id;
	}

	/**
	 * Return content text of message.
	 * 
	 * @return srting Content text of message
	 */
	public function content() {
		if (!$this->user_id) return Text::translate($this->content);
		return $this->content;
	}

	/**
	 * Implements jsonSerialize method from JsonSerializable interface to be executed
	 * when casting this object to a JSON.
	 * 
	 * @return array Associative array with properties to be parsed
	 */
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