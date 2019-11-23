<?php

class Chat extends Database implements JsonSerializable {
	
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
			WHERE c.id = $id
			GROUP BY c.id";
		$chat_db = self::query($sql);
		if (!$chat_db || $chat_db->num_rows == 0) throw new Exception(Text::error('chat_get'));
		$chat = $chat_db->fetch_assoc();
		return new Chat($chat['id'], $chat['date'], $chat['name'], $chat['last_msg']);
	}

	public static function new($name = "") {
		if (!Helper::validName($name) || !($name = self::escape($name))) throw new Exception(Text::error('chat_invalid'));
		$sql = "INSERT INTO chat (name) VALUES ('$name')";
		self::query($sql);
		if (!($id = self::insertId())) throw new Exception(Text::error('chat_new'));
		return Chat::get($id);
	}

	public static function list($result_set){
		$chats = [];
		if (is_object($result_set) && get_class($result_set) == 'mysqli_result')
			while ($chat = $result_set->fetch_assoc())
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
		$sql = "INSERT INTO participate (chat_id, user_id) VALUES ({$this->id}, {$user->id()})";
		if (!self::query($sql)) return false;
		$this->addMessage(0, $user->name().' joins the chat');
		$this->users = null;
		return true;
	}

	public function removeUser($user) {
		if (!is_object($user) || get_class($user) != 'User') return false;
		$sql = "DELETE FROM participate WHERE chat_id = {$this->id} AND user_id = {$user->id()}";
		if (!self::query($sql)) return false;
		$this->addMessage(0, $user->name().' leaves the chat');
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
		if (!Helper::validName($name) || !($name = self::escape($name))) return false;
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
				WHERE m.chat_id = {$this->id}
				ORDER BY m.id DESC
				LIMIT 100";
			$result = self::query($sql);
			$this->messages = Message::list($result);
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
				WHERE p.chat_id = {$this->id}
				ORDER BY u.name ASC";
			$result = self::query($sql);
			$this->users = User::list($result);
		}
		if (is_null($user_id)) return $this->users;
		return $this->users[$user_id] ?? false;
	}

	public function candidates($user_id) {
		$state = Helper::ACCEPTED;
		$sql = "
			SELECT u.id,
				   u.email,
				   u.name,
				   u.password,
				   u.avatar
			FROM user u
			INNER JOIN contact c
			ON (u.id = c.user_1_id AND c.user_2_id = $user_id)
			OR (u.id = c.user_2_id AND c.user_1_id = $user_id)
			LEFT JOIN participate p
			ON u.id = p.user_id AND p.chat_id = {$this->id}
			WHERE p.user_id IS NULL
			AND c.state = $state
			ORDER BY u.name ASC";
		$result = self::query($sql);
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	public function save() {
		$sql = "
			UPDATE chat SET
			name = '{$this->name}'
			WHERE id = {$this->id}";
		if (self::query($sql) === false) return false;
		return true;
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
			WHERE m.chat_id = {$this->id}
			AND m.id > $last_id
			ORDER BY m.id DESC
			LIMIT 100";
		$result = self::query($sql);
		$messages = [];
		while ($message = $result->fetch_assoc()) {
			if (!$message['user_id'])
				$this->new_members = true;
			$messages[] = $message;
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