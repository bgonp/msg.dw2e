<?php
/**
 * Attachment model that represents a stored attached file. An attachment always belongs to
 * a message. This class extends Database in order to use its methods to connect and handle
 * database queries.
 * 
 * @package model
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class Attachment extends Database {

	/** @var integer $id Stored attachment ID */
	private $id;
	/** @var string $date Attachment upload date */
	private $date;
	/** @var string $mime_type Attachment mime type */
	private $mime_type;
	/** @var integer $height Image height in pixels (if attachment is an image) */
	private $height;
	/** @var integer $width Image width in pixels (if attachment is an image) */
	private $width;
	/** @var string $filename Name of the file saved in upload/attachment/ folder */
	private $filename;
	/** @var integer $chat_id Chat ID where the attachment was sent */
	private $chat_id;
	/** @var Chat $chat Chat where the attachment was sent */
	private $chat;

	/**
	 * Private constructor. An object can't be constructed directly, but through static
	 * factory methods.
	 * 
	 * @param integer $id Stored attachment ID
	 * @param string $date Attachment upload date
	 * @param string $mime_type Attachment mime type
	 * @param integer $height Image height in pixels (if attachment is an image)
	 * @param integer $width Image width in pixels (if attachment is an image)
	 * @param string $filename Name of the file saved in upload/attachment/ folder
	 * @param integer $chat_id Chat ID where the attachment was sent
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

	/**
	 * Static factory method that returns an Attachment object from a register from database.
	 * 
	 * @param integer $id ID of the stored attachment
	 * @return Attachment Object Attachment identified by passed ID in database
	 * @throws Exception If attachment doesn't exists
	 */
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
		if (!self::count())
			throw new Exception(Text::error('attachment_get'));
		$att = self::fetch();
		return new Attachment($att['id'], $att['date'], $att['mime_type'], $att['height'], $att['width'], $att['filename'], $att['chat_id']);
	}

	/**
	 * Static factory method that store a register in database and returns the corresponding
	 * Attachment object.
	 * 
	 * @param array $file Array with file info. Structure of $_FILES superglobal
	 * @return Attachment Object Attachment with passed data
	 */
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

	/**
	 * Return id of object. Id is the primary key in database
	 * 
	 * @return integer ID of current Attachment object
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Return date property of object. It represents the date when the file was uploaded.
	 * 
	 * @return string YYYY-MM-DD HH:MM:SS format
	 */
	public function date() {
		return $this->date;
	}

	/**
	 *  It represents the date when the file was uploaded.
	 * 
	 * @return string Mime type
	 */
	public function mime_type() {
		return $this->mime_type;
	}

	/**
	 * If attachment file is an image, this will return its height in pixels. If not, it
	 * will return 0.
	 * 
	 * @return integer Height in pixels
	 */
	public function height() {
		return $this->height;
	}

	/**
	 * If attachment file is an image, this will return its width in pixels. If not, it
	 * will return 0.
	 * 
	 * @return integer Width in pixels
	 */
	public function width() {
		return $this->width;
	}

	/**
	 * Name of the file associated to this object.
	 * 
	 * @return string Filename
	 */
	public function filename() {
		return $this->filename;
	}

	/**
	 * Based in property mime_type, this will return if the file is an image or not.
	 * 
	 * @return boolean True if file is an image, false if not
	 */
	public function isImage() {
		return explode('/', $this->mime_type)[0] == 'image';
	}

	/**
	 * Return the Chat object where the message associated with this attachment was published.
	 * 
	 * @return Chat Object Chat where this attachment was published
	 */
	public function chat() {
		if (is_null($this->chat))
			$this->chat = Chat::get($this->chat_id);
		return $this->chat;
	}
	
}