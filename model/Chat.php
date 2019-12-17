<?php
/**
 * Chat model that represents a stored chat room. A chat holds many users and messages,
 * every user from a chat can read every single message on it. Also, he can leave
 * whenever he wants or invite whoever he wants.
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
class Chat extends Database implements JsonSerializable {
	
	/** @var integer $id Stored chat ID */
	private $id;
	/** @var string $date Chat creation date */
	private $date;
	/** @var string $name Name of the chat room */
	private $name;
	/** @var array Associative array of messages in this chat */
	private $messages;
	/** @var array Associative array of users in this chat */
	private $users;
	/** @var integer $last_msg (optional) ID of last message published in this chat */
	private $last_msg;
	/** @var integer $last_read (optional) ID of last message read by a certain user */
	private $last_read;
	/** @var boolean Whatever something changed in members list */
	private $new_members;

	/**
	 * Private constructor. An object can't be constructed directly, but through static
	 * factory methods.
	 * 
	 * @param integer $id Stored chat ID
	 * @param string $date Chat creation date
	 * @param string $name Name of the chat room
	 * @param integer $last_msg (optional) ID of last message published in this chat
	 * @param integer $last_read (optional) ID of last message read by a certain user
	 */
	private function __construct($id, $date, $name, $last_msg = 0, $last_read = 0){
		$this->id = $id;
		$this->date = $date;
		$this->name = $name;
		$this->last_msg = $last_msg ?? 0;
		$this->last_read = $last_read ?? 0;
		$this->new_members = false;
	}

	/**
	 * Static factory method that returns a Chat object from a register from database.
	 * 
	 * @param integer $id ID of the stored chat room
	 * @return Chat Chat object identified by passed ID in database
	 * @throws Exception If chat doesn't exists
	 */
	public static function get($id) {
		if (!($id = intval($id)))
			throw new Exception(Text::error('chat_id'));
		$sql = "
			SELECT c.`id`,
				   c.`date`,
				   c.`name`,
				   MAX(m.`id`) last_msg
			FROM `chat` c
			LEFT JOIN `message` m ON c.`id` = m.`chat_id`
			WHERE c.`id` = :id
			GROUP BY c.`id`";
		self::query($sql, [':id' => $id]);
		if (!self::count())
			throw new Exception(Text::error('chat_get'));
		$chat = self::fetch();
		return new Chat($chat['id'], $chat['date'], $chat['name'], $chat['last_msg']);
	}

	/**
	 * Static factory method that store a register in database and returns the
	 * corresponding Chat object.
	 * 
	 * @param  string $name Name of the chat room
	 * @return Chat Chat object with passed data
	 * @throws Exception If chat couldn't be stored
	 */
	public static function create($name = "") {
		if (!Helper::validName($name)) throw new Exception(Text::error('chat_invalid'));
		$sql = "INSERT INTO `chat` (`name`) VALUES (:name)";
		self::query($sql, [':name' => $name]);
		if (!self::count() || !($id = self::insertId()))
			throw new Exception(Text::error('chat_new'));
		return Chat::get($id);
	}

	/**
	 * Static factory method that returns an array of Chat objects from last executed
	 * query. This must be called after execute a query that returns the following info:
	 * <li>id - Chat ID
	 * <li>date - Chat date
	 * <li>name - Chat name
	 * <li>last_msg - Chat last published message ID (optional)
	 * <li>last_read - Chat last read message ID (optional)
	 * 
	 * @return array Associative array of Chat objects. Keys will be chats ids
	 */
	public static function gets(){
		$chats = [];
		while ($chat = self::fetch())
			$chats[$chat['id']] = new Chat(
				$chat['id'],
				$chat['date'],
				$chat['name'],
				$chat['last_msg'] ?? 0,
				$chat['last_read'] ?? 0
			);
		return $chats;
	}

	/**
	 * Add a user to this chat. Also add a message in the chat reporting addition.
	 * 
	 * @param User|integer $user User ID or whole User object to be added
	 * @return boolean False if user coudn't be added or true if everything was ok
	 */
	public function addUser($user) {
		if (is_numeric($user)) $user = User::get($user);
		if (!is_object($user) || get_class($user) != 'User') return false;
		$sql = "INSERT INTO `participate` (`chat_id`, `user_id`) VALUES (:chatid, :userid)";
		self::query($sql, [':chatid' => $this->id, ':userid' => $user->id()]);
		if (!self::count()) return false;
		$this->addMessage(0, $user->name().' {{TR:JOINS}}');
		// If success, reset the users array because it has changed in database
		$this->users = null;
		return true;
	}

	/**
	 * Remove a user from this chat. Also add a message in the chat reporting deletion.
	 * 
	 * @param User|integer $user User ID or whole User object to be removed
	 * @return boolean False if user coudn't be removed or true if everything was ok
	 */
	public function removeUser($user) {
		if (is_numeric($user)) $user = User::get($user);
		if (!is_object($user) || get_class($user) != 'User') return false;
		$sql = "DELETE FROM `participate` WHERE `chat_id` = :chatid AND `user_id` = :userid";
		self::query($sql, [':chatid' => $this->id, ':userid' => $user->id()]);
		if (!self::count()) return false;
		$this->addMessage(0, $user->name().' {{TR:LEAVES}}');
		// If success, reset the users array because it has changed in database
		$this->users = null;
		return true;
	}

	/**
	 * Create a new message from its content and add it to this chat.
	 * 
	 * @param integer $user_id User ID who wrote the message
	 * @param string $message Message content
	 * @param array $file (optional) Array with attachment. Structure of $_FILES superglobal
	 * @return Message Message object just created and added
	 */
	public function addMessage($user_id, $message, $file = false) {
		if ($user_id > 0 && !$this->users($user_id)) return false;
		if (!($message = Message::create($user_id, $this->id, $message, $file))) return false;
		$this->messages = null;
		return $message;
	}

	/**
	 * Return id of object. Id is the primary key in database
	 * 
	 * @return integer ID of current Chat object
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Return date property of object. It represents the date when the chat was created.
	 * 
	 * @return string YYYY-MM-DD HH:MM:SS format
	 */
	public function date() {
		return $this->date;
	}

	/**
	 * Return the name of the chat room.
	 * 
	 * @return string Chat room name
	 */
	public function name($name = null) {
		if (is_null($name)) return $this->name;
		if (!Helper::validName($name)) return false;
		$this->name = $name;
		return true;
	}

	/**
	 * Return the ID of the last message published in this chat room
	 * 
	 * @return integer Message ID
	 */
	public function last_msg() {
		return $this->last_msg;
	}

	/**
	 * Return if there is unread messages in this chat.
	 * 
	 * @return boolean True if the last message in this chat is newer than the last read one
	 */
	public function unread() {
		return $this->last_msg > $this->last_read;
	}

	/**
	 * Return an associative array with the last messages in this chat (default 100). Keys
	 * of the array will be each message ID. Once generated from the database, the array is
	 * saved in the object in order to not have to do the same query again if needed.
	 * 
	 * @param integer Limit of messages to be fetched
	 * @return array Associative array of messages 
	 */
	public function messages($limit = 100) {
		if (!is_array($this->messages) || $limit > count($this->messages)){
			$sql = "
				SELECT m.`id`,
					   DATE_FORMAT(m.`date`, '%H:%i %d/%m/%Y') date,
					   m.`user_id`,
					   u.`name user_name`,
					   m.`chat_id`,
					   m.`attachment_id`,
					   m.`content`,
					   a.`mime_type`,
					   a.`height`,
					   a.`width`
				FROM `message` m
				LEFT JOIN `user` u
				ON m.`user_id` = u.`id`
				LEFT JOIN attachment a
				ON m.`attachment_id` = a.`id`
				WHERE m.`chat_id` = :id
				ORDER BY m.`id` DESC
				LIMIT ".intval($limit);
			self::query($sql, [':id' => $this->id]);
			$this->messages = Message::gets();
		}
		return $this->messages;
	}

	/**
	 * Return an associative array with the users that belongs to this chat. Once generated
	 * from database, the array is saved in the object in order to don't have to do the same
	 * query again if needed.
	 * 
	 * If this method receives a user id, it returns the user if he belongs to this chat
	 * (instead of the whole array) or false if not.
	 * 
	 * @param integer (optional) User ID to get if he belongs to this chat
	 * @return array|User|false Associative array of users or a user if user_id was passed
	 */
	public function users($user_id = null) {
		if (!is_array($this->users)){
			$sql = "
				SELECT u.`id`,
					   u.`email`,
					   u.`name`,
					   u.`password`,
					   u.`avatar`
				FROM `user` u
				LEFT JOIN `participate` p
				ON u.`id` = p.`user_id`
				WHERE p.`chat_id` = :id
				ORDER BY u.`name` ASC";
			self::query($sql, [':id' => $this->id]);
			$this->users = User::gets();
		}
		if (is_null($user_id)) return $this->users;
		return $this->users[$user_id] ?? false;
	}

	/**
	 * Given a user ID, this returns an array of friends of this user that could be added
	 * to this chat room.
	 * 
	 * @param integer $user_id User whose friends can be added to this chat
	 * @return array Array of users
	 */
	public function candidates($user_id) {
		$sql = "
			SELECT u.`id`,
				   u.`email`,
				   u.`name`,
				   u.`password`,
				   u.`avatar`
			FROM user u
			INNER JOIN contact c
			ON (u.`id` = c.`user_1_id` AND c.`user_2_id` = :userid)
			OR (u.`id` = c.`user_2_id` AND c.`user_1_id` = :userid)
			LEFT JOIN participate p
			ON u.`id` = p.`user_id` AND p.`chat_id` = :chatid
			WHERE p.`user_id` IS NULL
			AND c.`state` = :state
			ORDER BY u.`name` ASC";
		self::query($sql, [
			':userid' => $user_id,
			':chatid' => $this->id,
			':state' => Helper::ACCEPTED
		]);
		return self::fetch(true);
	}

	/**
	 * Given the last received message ID, this returns new messages added after that one.
	 * This method also check if some user leaves or joins the chat and set the new_members
	 * object property to true.
	 * 
	 * @param integer $last_id Last received messaged ID
	 * @return array Array of new messages
	 */
	public function newMessages($last_id) {
		$sql = "
			SELECT m.`id`,
				   DATE_FORMAT(m.`date`, '%H:%i %d/%m/%Y') 'date',
				   m.`user_id`,
				   u.`name user_name`,
				   m.`chat_id`,
				   m.`attachment_id`,
				   m.`content`,
				   a.`mime_type`,
				   a.`height`,
				   a.`width`
			FROM `message` m
			LEFT JOIN `user` u
			ON m.`user_id` = u.`id`
			LEFT JOIN `attachment` a
			ON m.`attachment_id` = a.`id`
			WHERE m.`chat_id` = :chatid
			AND m.`id` > :lastid
			ORDER BY m.`id` DESC
			LIMIT 100";
		self::query($sql, [
			':chatid' => $this->id,
			':lastid' => $last_id
		]);
		$messages = array_values(Message::gets());
		foreach ($messages as $message)
			// A message with no author is a join/leave report message
			if (!$message->user_id()) {
				$this->new_members = true;
				break;
			}
		return $messages;
	}

	/**
	 * Return whetever there is new members in (or someone leaves) the chat.
	 * This will be always falso until newMessages method was called.
	 * 
	 * @return bool True if there are changes in members list
	 */
	public function newMembers() {
		return $this->new_members;
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
			'name' => $this->name,
			'last_msg' => $this->last_msg,
			'last_read' => $this->last_read
		];
	}

}