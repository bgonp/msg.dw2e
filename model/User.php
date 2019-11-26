<?php

class User extends Database implements JsonSerializable {

	private $id;
	private $email;
	private $name;
	private $password;
	private $avatar;
	private $confirmed;
	private $admin;
	private $code;
	private $expiration;
	private $chats;
	private $friends;
	private $requests;

	private function __construct($id, $email, $name, $password, $avatar, $confirmed = 0, $admin = 0, $code = "", $expiration = 0) {
		$this->id = $id;
		$this->email = $email;
		$this->name = $name;
		$this->password = $password;
		$this->avatar = $avatar;
		$this->confirmed = $confirmed;
		$this->admin = $admin;
		$this->code = $code;
		$this->expiration = $expiration > 0 ? strtotime($expiration) : 0;
	}

	public static function get($id_o_email, $password = null) {
		if (is_numeric($id_o_email)){
			if ($id_o_email <= 0) throw new Exception(Text::error('user_id'));
			$sql = "SELECT * FROM user WHERE id = :id";
			self::query($sql, [':id' => $id_o_email]);
		} else {
			if (empty($id_o_email)) throw new Exception(Text::error('user_email'));
			$sql = "SELECT * FROM user WHERE email = :email";
			self::query($sql, [':email' => $id_o_email]);
		}
		if (!self::count())
			throw new Exception(Text::error('user_get'));
		$user = self::fetch();
		$user = new User(
			$user['id'],
			$user['email'],
			$user['name'],
			$user['password'],
			$user['avatar'],
			$user['confirmed'],
			$user['admin'],
			$user['code'],
			$user['expiration']
		);
		if (!empty($password) && !$user->verificar($password)) throw new Exception(Text::error('pass_wrong'));
		return $user;
	}

	public static function new($email, $name, $password, $avatar = 0, $confirmed = 0, $admin = 0) {
		if (!Helper::validEmail($email))
			throw new Exception(Text::error('user_email'));
		if (!Helper::validName($name))
			throw new Exception(Text::error('user_name'));
		if (!Helper::validPassword($password))
			throw new Exception(Text::error('user_pass'));
		if (!$avatar || $avatar['error'] == 4)
			$avatar = '';
		else if (!($avatar = Helper::uploadAvatar($avatar)))
			throw new Exception(Text::error('user_avatar'));
		$password = self::hash($password);
		$sql = "
			INSERT INTO user (email, name, password, avatar, confirmed, admin)
			VALUES (:email, :name, :password, :avatar, :confirmed, :admin)";
		self::query($sql, [
			':email' => $email,
			':name' => $name,
			':password' => $password,
			':avatar' => $avatar,
			':confirmed' => $confirmed,
			':admin' => $admin
		]);
		if (!self::count() || !($id = self::insertId()))
			throw new Exception(Text::error('user_new'));
		return new User($id, $email, $name, $password, $avatar, $confirmed, $admin);
	}

	public static function list() {
		$users = [];
		while ($user = self::fetch())
			$users[$user['id']] = new User(
				$user['id'],
				$user['email'],
				$user['name'],
				$user['password'],
				$user['avatar']
			);
		return $users;
	}

	public function id() {
		return $this->id;
	}

	public function email($email = null) {
		if (is_null($email)) return $this->email;
		if (!Helper::validEmail($email)) return false;
		$this->email = $email;
		return true;
	}

	public function name($name = null) {
		if (is_null($name)) return $this->name;
		if (!Helper::validName($name)) return false;
		$this->name = $name;
		return true;
	}

	public function password($password) {
		if (!Helper::validPassword($password) || !($password = self::hash($password))) return false;
		$this->password = $password;
		return true;
	}

	public function avatar($avatar = null) {
		if (is_null($avatar)) return $this->avatar;
		if (!($avatar = Helper::uploadAvatar($avatar))) return false;
		Helper::removeAvatar($this->avatar);
		$this->avatar = $avatar;
		return true;
	}

	public function confirmed($confirmed = null) {
		if (is_null($confirmed)) return $this->confirmed;
		$this->confirmed = $confirmed ? 1 : 0;
		return true;
	}

	public function admin() {
		return $this->admin;
	}

	public function chats($chat_id = null) {
		if (!is_array($this->chats)){
			$sql = "
				SELECT c.id,
					   c.date,
					   c.name,
					   COUNT(m.id) n_messages,
					   p.last_read,
					   MAX(m.id) last_msg
				FROM chat c
				LEFT JOIN message m
				ON c.id = m.chat_id
				LEFT JOIN participate p
				ON c.id = p.chat_id
				WHERE p.user_id = :id
				GROUP BY c.id
				ORDER BY IF(MAX(m.id) > p.last_read, 1, 0) DESC, last_msg DESC";
			self::query($sql, [':id' => $this->id]);
			$this->chats = Chat::list();
		}
		if (is_null($chat_id)) return $this->chats;
		return $this->chats[intval($chat_id)] ?? false;
	}

	public function readChat($chat_id) {
		$sql = "
			UPDATE participate p SET p.last_read = (
				SELECT MAX(m.id) FROM message m WHERE m.chat_id = :chatid
			) WHERE p.user_id = :userid AND p.chat_id = :chatid";
		self::query($sql, [
			':chatid' => $chat_id,
			':userid' => $this->id
		]);
		return self::count() !== 0;
	}

	public function newChats($last_received) {
		$sql = "
			SELECT c.id,
				   c.date,
				   c.name,
				   COUNT(m.id) n_messages,
				   p.last_read,
				   MAX(m.id) last_msg
			FROM chat c
			LEFT JOIN message m ON c.id = m.chat_id
			LEFT JOIN participate p ON c.id = p.chat_id
			WHERE p.user_id = :userid
			AND (m.id > p.last_read OR p.last_read IS NULL)
			GROUP BY c.id
			HAVING last_msg > :lastid
			ORDER BY last_msg DESC";
		self::query($sql, [
			':userid' => $this->id,
			':lastid' => $last_received
		]);
		return self::fetch(true);
	}

	public function friends($friend_id = null) {
		if (!is_array($this->friends))
			$this->friends = self::contacts($friend_id, Helper::ACCEPTED);
		if (is_null($friend_id)) return $this->friends;
		return $this->friends[intval($friend_id)] ?? false;
	}

	public function newFriends($last) {
		return $this->newContacts($last, Helper::ACCEPTED);
	}

	public function requests($request_id = null) {
		if (!is_array($this->requests))
			$this->requests = self::contacts($request_id, Helper::WAITING);
		if (is_null($request_id))	return $this->requests;
		return $this->requests[intval($request_id)] ?? false;
	}

	public function newRequests($last) {
		return $this->newContacts($last, Helper::WAITING);
	}

	public function checkCode($code) {
		return !empty($code) && $this->code == $code && time() <= $this->expiration;
	}

	public function getNewCode() {
		$this->code = Helper::randomString(32);
		$this->expiration = time()+60*60*24;
		$this->save();
		return $this->code;
	}

	public function removeCode() {
		$this->code = "";
		$this->expiration = time();
		$this->save();
	}

	public function confirm() {
		$this->confirmed = 1;
		$this->code = "";
		$this->expiration = time();
		$this->save();
	}

	private function contacts($contact_id, $state) {
		$and = $state == Helper::WAITING ? "AND c.user_state_id <> :userid" : "";
		$sql = "
			SELECT u.id,
				   u.email,
				   u.name,
				   u.password,
				   u.avatar
			FROM user u
			WHERE u.admin = 0
			AND id IN (
				SELECT IF(c.user_1_id = :userid, c.user_2_id, c.user_1_id) user_id
				FROM contact c
				WHERE c.state = :state {$and}
				AND (c.user_1_id = :userid OR c.user_2_id = :userid)
			)
			ORDER BY u.name ASC";
		self::query($sql, [
			':userid' => $this->id,
			':state' => $state
		]);
		return self::list();
	}

	private function newContacts($last, $state) {
		$and = $state == Helper::WAITING ? " AND c.user_state_id <> :userid" : "";
		$sql = "
			SELECT u.id,
				   u.name,
				   u.email,
				   t.date_upd
			FROM user u
			RIGHT JOIN (
				SELECT
					c.date_upd, IF(c.user_1_id = :userid, c.user_2_id, c.user_1_id) user_id
				FROM contact c
				WHERE c.state = :state {$and}
				AND (c.user_1_id = :userid OR c.user_2_id = :userid)
				AND date_upd > :last
			) t
			ON u.id = t.user_id
			ORDER BY t.date_upd DESC";
		self::query($sql, [
			':userid' => $this->id,
			':state' => $state,
			':last' => $last
		]);
		return self::fetch(true);
	}

	public function lastContactUpd() {
		$sql = "
			SELECT MAX(date_upd) last_contact_upd
			FROM contact c
			WHERE c.user_1_id = :id OR c.user_2_id = :id";
		self::query($sql, [':id' => $this->id]);
		return self::fetch()['last_contact_upd'];
	}

	public function lastReceived() {
		$last = 0;
		foreach ($this->chats() as $chat)
			if ($chat->last_msg() > $last)
				$last = $chat->last_msg();
		return $last;
	}

	public function addContact($id_o_email) {
		$contact = self::get($id_o_email);
		if ($contact->id === $this->id) throw new Exception(Text::error('contact_self'));		
		$user1_id = min($this->id, $contact->id);
		$user2_id = max($this->id, $contact->id);
		$sql = "
			INSERT INTO contact (user_1_id, user_2_id, user_state_id)
			VALUES (:user1id, :user2id, :userid)";
		self::query($sql, [
			':user1id' => $user1_id,
			':user2id' => $user2_id,
			':userid' => $this->id
		]);
		if (!self::count())
			throw new Exception(Text::error('contact_new'));
		return true;
	}

	public function updateContact($contact_id, $state) {
		$state = intval($state);
		if ($state < Helper::ACCEPTED || $state > Helper::BLOCKED)
			throw new Exception(Text::error('contact_state'));
		$user1_id = min($this->id, $contact_id);
		$user2_id = max($this->id, $contact_id);
		$sql = "
			UPDATE contact SET state = :state, user_state_id = :userid
			WHERE user_1_id = :user1id
			AND user_2_id = :user2id
			AND state = :reqstate";
		$replace = [
			':state' => $state,
			':userid' => $this->id,
			':user1id' => $user1_id,
			':user2id' => $user2_id
		];
		if ($state === Helper::ACCEPTED || $state === Helper::DECLINED) {
			$replace[':reqstate'] = Helper::WAITING;
			$replace[':contactid'] = $contact_id;
			$sql .= " AND user_state_id = :contactid";
		} else if ($state == Helper::BLOCKED) {
			$replace[':reqstate'] = Helper::ACCEPTED;
		}
		self::query($sql, $replace);
		if (!self::count())
			throw new Exception(Text::error('contact_update'));
		$this->friends = null;
		$this->requests = null;
		return true;
	}

	public function verificar($password) {
		return $this->confirmed && password_verify( $password, $this->password );
	}

	public function save() {
		$expiration = $this->expiration ? date('Y-m-d H:i:s', $this->expiration) : null;
		$sql = "
			UPDATE user SET
			email = :email,
			name = :name,
			password = :password,
			avatar = :avatar,
			confirmed = :confirmed,
			admin = :admin,
			code = :code,
			expiration = :expiration
			WHERE id = :id";
		self::query($sql, [
			':email' => $this->email,
			':name' => $this->name,
			':password' => $this->password,
			':avatar' => $this->avatar,
			':confirmed' => $this->confirmed,
			':admin' => $this->admin,
			':code' => $this->code,
			':expiration' => $expiration,
			':id' => $this->id
		]);
		return self::count();
	}

	public function delete() {
		$sql = "DELETE FROM user WHERE id = :id";
		self::query($sql, [':id' => $this->id]);
		return self::count();
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'email' => $this->email,
			'name' => $this->name
		];
	}

	private static function hash($password) {
		return password_hash($password, PASSWORD_BCRYPT);
	}

}