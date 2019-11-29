<?php
/**
 * Lorem ipsum
 * 
 * @package model
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class Attachment extends Database {

	private $id;
	private $date;
	private $mime_type;
	private $height;
	private $width;
	private $filename;
	private $chat_id;
	private $chat;

	/**
	 * Private constructor. An object can't be constructed directly, but through static
	 * factory methods.
	 * 
	 * @param int $id Stored attachment ID
	 * @param string $date Attachment upload date
	 * @param string $mime_type Attachment mime type
	 * @param int $height Image height in pixels (if attachment is an image)
	 * @param int $width Image width in pixels (if attachment is an image)
	 * @param string $filename Name of the file saved in upload/attachment/ folder
	 * @param int $chat_id Chat ID where the attachment was sent
	 */
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
			WHERE a.id = :id";
		self::query($sql, [':id' => $id]);
		if (!self::count()) throw new Exception(Text::error('attachment_get'));
		$att = self::fetch();
		return new Attachment($att['id'], $att['date'], $att['mime_type'], $att['height'], $att['width'], $att['filename'], $att['chat_id']);
	}

	public static function create($file) {
		if (!$file || $file['error'])
			throw new Exception(Text::error('attachment_invalid'));
		if (!$file['type'] || !$file['name'])
			throw new Exception(Text::error('attachment_data'));
		if (!($fileinfo = Helper::uploadAttachment($file)))
			throw new Exception(Text::error('attachment_upload'));
		$sql = "INSERT INTO attachment (mime_type, height, width, filename)
				VALUES (:type, :height, :width, :name)";
		self::query($sql, [
			':type' => $file['type'],
			':height' => $fileinfo['height'],
			':width' => $fileinfo['width'],
			':name' => $fileinfo['name']
		]);
		if (!self::count() || !($id = self::insertId())) {
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