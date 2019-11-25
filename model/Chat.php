<?php

class Chat extends DatabasePDO implements JsonSerializable {
	
	private $id;
	private $date;
	private $name;
	private $messages;
	private $users;
	private $last_msg;
	private $last_read;
	private $new_members;

	private function __construct($id, $date, $name, $last_msg = 0, $last_read = 0){
		$this->id = $id;
		$this->date = $date;
		$this->name = $name;
		$this->last_msg = $last_msg ?? 0;
		$this->last_read = $last_read ?? 0;
		$this->new_members = false;
	}

	public static function get($id) {
		if (!($id = intval($id))) throw new Exception(Text::error('chat_id'));
		$sql = "
			SELECT c.id,
				   c.date,
				   c.name,
				   MAX(m.id) last_msg
			FROM chat c
			LEFT JOIN message m ON c.id = m.chat_id
			WHERE c.id = :id
			GROUP BY c.id";
		self::query($sql, [':id' => $id]);
		if (!self::count())
			throw new Exception(Text::error('chat_get'));
		$chat = self::fetch();
		return new Chat($chat['id'], $chat['date'], $chat['name'], $chat['last_msg']);
	}

	public static function new($name = "") {
		if (!Helper::validName($name)) throw new Exception(Text::error('chat_invalid'));
		$sql = "INSERT INTO chat (name) VALUES (:name)";
		self::query($sql, [':name' => $name]);
		if (!self::count() || !($id = self::insertId()))
			throw new Exception(Text::error('chat_new'));
		return Chat::get($id);
	}

	public static function list(){
		$chats = [];
		while ($chat = self::fetch())
			$chats[$chat['id']] = new Chat(
				$chat['id'],
				$chat['date'],
				$chat['name'],
				$chat['last_msg'],
				$chat['last_read']
			);
		return $chats;
	}

	public function addUser($user) {
		if (is_numeric($user)) $user = User::get($user);
		if (!is_object($user) || get_class($user) != 'User') return false;
		$sql = "INSERT INTO participate (chat_id, user_id) VALUES (:chatid, :userid)";
		self::query($sql, [':chatid' => $this->id, ':userid' => $user->id()]);
		if (!self::count()) return false;
		$this->addMessage(0, $user->name().' {{TR:JOINS}}');
		$this->users = null;
		return true;
	}

	public function removeUser($user) {
		if (!is_object($user) || get_class($user) != 'User') return false;
		$sql = "DELETE FROM participate WHERE chat_id = :chatid AND user_id = :userid";
		self::query($sql, [':chatid' => $this->id, ':userid' => $user->id()]);
		if (!self::count()) return false;
		$this->addMessage(0, $user->name().' {{TR:LEAVES}}');
		$this->users = null;
		return true;
	}

	public function addMessage($user_id, $message, $file = false) {
		if ($user_id > 0 && !$this->users($user_id)) return false;
		if (!($message = Message::new($user_id, $this->id, $message, $file))) return false;
		$this->messages = null;
		return $message;
	}

	public function id(){
		return $this->id;
	}

	public function date(){
		return $this->date;
	}

	public function name($name = null){
		if (is_null($name)) return $this->name;
		if (!Helper::validName($name)) return false;
		$this->name = $name;
		return true;
	}

	public function last_msg(){
		return $this->last_msg;
	}

	public function unread(){
		return $this->last_msg > $this->last_read;
	}

	public function messages(){
		if (!is_array($this->messages)){
			$sql = "
				SELECT m.id,
					   DATE_FORMAT(m.date, '%H:%i %d/%m/%Y') date,
					   m.user_id,
					   u.name user_name,
					   m.chat_id,
					   m.attachment_id,
					   m.content,
					   a.mime_type,
					   a.height,
					   a.width
				FROM message m
				LEFT JOIN user u
				ON m.user_id = u.id
				LEFT JOIN attachment a
				ON m.attachment_id = a.id
				WHERE m.chat_id = :id
				ORDER BY m.id DESC
				LIMIT 100";
			self::query($sql, [':id' => $this->id]);
			$this->messages = Message::list();
		}
		return $this->messages;
	}

	public function users( $user_id = null){
		if (!is_array($this->users)){
			$sql = "
				SELECT u.id,
					   u.email,
					   u.name,
					   u.password,
					   u.avatar
				FROM user u
				LEFT JOIN participate p
				ON u.id = p.user_id
				WHERE p.chat_id = :id
				ORDER BY u.name ASC";
			self::query($sql, [':id' => $this->id]);
			$this->users = User::list();
		}
		if (is_null($user_id)) return $this->users;
		return $this->users[$user_id] ?? false;
	}

	public function candidates($user_id) {
		$sql = "
			SELECT u.id,
				   u.email,
				   u.name,
				   u.password,
				   u.avatar
			FROM user u
			INNER JOIN contact c
			ON (u.id = c.user_1_id AND c.user_2_id = :userid)
			OR (u.id = c.user_2_id AND c.user_1_id = :userid)
			LEFT JOIN participate p
			ON u.id = p.user_id AND p.chat_id = :chatid
			WHERE p.user_id IS NULL
			AND c.state = :state
			ORDER BY u.name ASC";
		self::query($sql, [
			':userid' => $user_id,
			':chatid' => $this->id,
			':state' => Helper::ACCEPTED
		]);
		return self::fetch(true);
	}

	public function newMessages($last_id) {
		$sql = "
			SELECT m.id,
				   DATE_FORMAT(m.date, '%H:%i %d/%m/%Y') 'date',
				   m.user_id,
				   u.name user_name,
				   m.chat_id,
				   m.attachment_id,
				   m.content,
				   a.mime_type,
				   a.height,
				   a.width
			FROM message m
			LEFT JOIN user u
			ON m.user_id = u.id
			LEFT JOIN attachment a
			ON m.attachment_id = a.id
			WHERE m.chat_id = :chatid
			AND m.id > :lastid
			ORDER BY m.id DESC
			LIMIT 100";
		self::query($sql, [
			':chatid' => $this->id,
			':lastid' => $last_id
		]);
		$messages = array_values(Message::list());
		foreach ($messages as $message)
			if (!$message->user_id()) {
				$this->new_members = true;
				break;
			}
		return $messages;
	}

	public function newMembers() {
		return $this->new_members;
	}

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