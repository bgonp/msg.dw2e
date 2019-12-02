<?php
/**
 * User model that represents a stored user. A user has contacts, whose can be friends or
 * unreplied requests. Also, a user belongs to chat rooms.
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
class User extends Database implements JsonSerializable {

	/** This class uses trait Contact in order to group contact related actions */
	use Contact;

	/** @var integer $id Stored user ID */
	private $id;
	/** @var string $email User e-mail address */
	private $email;
	/** @var string $name User name */
	private $name;
	/** @var srting $password User hashed password */
	private $password;
	/** @var string $avatar User avatar filename */
	private $avatar;
	/** @var boolean $confirmed If user account is confirmed */
	private $confirmed;
	/** @var boolean $admin If user has administrator role */
	private $admin;
	/** @var string $code Random-generated key used to confirm account or reset password */
	private $code;
	/** @var integer $expiration Time when the code will expire */
	private $expiration;
	/** @var array Array of chats this user belongs to */
	private $chats;
	/** @var array Array of users who are friends of this user  */
	private $friends;
	/** @var array Array of users who request this user friendship  */
	private $requests;

	/**
	 * Private constructor. An object can't be constructed directly, but through static
	 * factory methods.
	 * 
	 * @param integer $id Stored user ID
	 * @param string $email User e-mail address
	 * @param string $name User name
	 * @param srting $password User hashed password
	 * @param string $avatar User avatar filename
	 * @param boolean $confirmed If user account is confirmed
	 * @param boolean $admin If user has administrator role
	 * @param string $code Random-generated key used to confirm account or reset password
	 * @param integer $expiration Time when the code will expire
	 */
	private function __construct($id, $email, $name, $password, $avatar, $confirmed = false, $admin = false, $code = "", $expiration = 0) {
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

	/**
	 * Static factory method that returns a User object from a register from database.
	 * 
	 * @param integer|string $id_o_email ID of the stored user or his email
	 * @param string (optional) $password If passed, checks the password before creating the object
	 * @return User Usser object identified by passed ID in database
	 * @throws Exception If user doesn't exists or something went wrong
	 */
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

	/**
	 * Static factory method that store a register in database and returns the
	 * corresponding User object.
	 * 
	 * @param string $email User e-mail
	 * @param string $name User name
	 * @param string $password User unhashed password
	 * @param integer $avatar (optional) Array with avatar file. Structure of $_FILES superglobal
	 * @param boolean $confirmed (optional) If user account is confirmed
	 * @param boolean $admin (optional) If user has administrator role
	 * @return User User object with passed data
	 * @throws Exception If user couldn't be stored
	 */
	public static function create($email, $name, $password, $avatar = false, $confirmed = false, $admin = false) {
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

	/**
	 * Static factory method that returns an array of User objects from last executed
	 * query. This must be called after execute a query that returns the following info:
	 * <li>id - User ID
	 * <li>email - User email
	 * <li>name - User name
	 * <li>password - User hashed password
	 * <li>avatar - User avatar filename
	 * 
	 * @return array Associative array of User objects. Keys will be users ids
	 */
	public static function gets() {
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

	/**
	 * Return id of object. Id is the primary key in database
	 * 
	 * @return integer ID of current User object
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * Return the email address of this user. If an email is passed, this method will update
	 * the user email property. If this change has to be stored, method save must be called
	 * after this.
	 * 
	 * @param string $email (optional) User email to be changed
	 * @return string|boolean If an email is passed, returns true if it was changed. Otherwise returns email
	 */
	public function email($email = null) {
		if (is_null($email)) return $this->email;
		if (!Helper::validEmail($email)) return false;
		$this->email = $email;
		return true;
	}

	/**
	 * Return the name of this user. If an name is passed, this method will update the user
	 * name property. If this change has to be stored, method save must be called after this.
	 * 
	 * @param string $name (optional) User name to be changed
	 * @return string|boolean If a name is passed, returns true if it was changed. Otherwise returns user name
	 */
	public function name($name = null) {
		if (is_null($name)) return $this->name;
		if (!Helper::validName($name)) return false;
		$this->name = $name;
		return true;
	}

	/**
	 * Update the user password property. If this change has to be stored, method save must be called
	 * after this. The passed password will be hashed before set it to the object.
	 * 
	 * @param string $password Unhashed user password to be changed
	 * @return boolean True if password was changed
	 */
	public function password($password) {
		if (!Helper::validPassword($password) || !($password = self::hash($password))) return false;
		$this->password = $password;
		return true;
	}

	/**
	 * Return the avatar filename of this user. If an avatar is passed, this method will update
	 * the user avatar property. If this change has to be stored, method save must be called
	 * after this.
	 * 
	 * @param string $avatar (optional) User avatar filename to be changed
	 * @return string|boolean If an avatar is passed, returns true if it was changed. Otherwise returns avatar
	 */
	public function avatar($avatar = null) {
		if (is_null($avatar)) return $this->avatar;
		if (!($avatar = Helper::uploadAvatar($avatar))) return false;
		Helper::removeAvatar($this->avatar);
		$this->avatar = $avatar;
		return true;
	}

	/**
	 * Return the confirmed property of this user. If a boolean is passed, this method will update
	 * the user confirmed property. If this change has to be stored, method save must be called
	 * after this.
	 * 
	 * @param boolean $confirmed (optional) User confirmed property to be changed
	 * @return boolean Whatever the user account is confirmed or not
	 */
	public function confirmed($confirmed = null) {
		if (is_null($confirmed)) return $this->confirmed;
		$this->confirmed = $confirmed ? 1 : 0;
		return true;
	}

	/**
	 * Return the admin property of the user. That is this user is an administrator.
	 * 
	 * @return boolean Whatever this user has administrator role or not
	 */
	public function admin() {
		return $this->admin;
	}

	/**
	 * Check if the security code passed is the same of this user and if it didn't expires.
	 * This check is used to confirm an account or to recover a user password.
	 * 
	 * @param string $code Security code
	 * @return boolean True if code is correct, false if not
	 */
	public function checkCode($code) {
		return !empty($code) && $this->code == $code && time() <= $this->expiration;
	}

	/**
	 * Generate a new random security code and update the expiration date. Also save the object
	 * in database. Once generated, this code can't be obtained from object, you will have
	 * to generate a new one.
	 * 
	 * @return string New security code
	 */
	public function getNewCode() {
		$this->code = Helper::randomString(32);
		$this->expiration = time()+60*60*24;
		$this->save();
		return $this->code;
	}

	/**
	 * Removes the existing securty code from object and database.
	 */
	public function removeCode() {
		$this->code = "";
		$this->expiration = time();
		$this->save();
	}

	/**
	 * Set this user account as confirmed and save it in the database.
	 * @return [type] [description]
	 */
	public function confirm() {
		$this->confirmed = 1;
		$this->code = "";
		$this->expiration = time();
		$this->save();
	}

	/**
	 * Return an associative array with the chats where this user participate. Keys
	 * of the array will be each chat ID. Once generated from the database, the array is
	 * saved in the object in order to not have to do the same query again if needed.
	 * 
	 * If this method receives a chat id, it returns the chat if this user belongs to that
	 * chat (instead of the whole array) or false if not.
	 * 
	 * @param integer (optional) Chat ID to get if this user belongs to that chat
	 * @return array|User|false Associative array of chats or a chat if chat_id was passed
	 */
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
			$this->chats = Chat::gets();
		}
		if (is_null($chat_id)) return $this->chats;
		return $this->chats[intval($chat_id)] ?? false;
	}

	/**
	 * Save in database that this user read the last message published in given chat room
	 * until now.
	 * 
	 * @param integer $chat_id Chat to be marked as read
	 * @return boolean True if this data was saved in database succesfully
	 */
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

	/**
	 * Given the last received message ID, this method returns chats which have changed from
	 * that moment.
	 * 
	 * @param integer $last_received Last message ID received
	 * @return array Array of updated or new chats
	 */
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

	/**
	 * Return newest message ID from all chats this user belongs to.
	 * 
	 * @return integer Last received message ID
	 */
	public function lastReceived() {
		$last = 0;
		foreach ($this->chats() as $chat)
			if ($chat->last_msg() > $last)
				$last = $chat->last_msg();
		return $last;
	}
	/**
	 * Given an unhashed password, verify if it match with this user stored one.
	 * 
	 * @param string $password Unhashed password
	 * @return boolean True if password is correct or false if not
	 */
	public function verificar($password) {
		return $this->confirmed && password_verify( $password, $this->password );
	}

	/**
	 * Update this user in database with current property values
	 * 
	 * @return integer 1 if user could be updated. 0 if not
	 */
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

	/**
	 * Delete this user from database.
	 * 
	 * @return integer 1 if user could be deleted. 0 if not
	 */
	public function delete() {
		$sql = "DELETE FROM user WHERE id = :id";
		self::query($sql, [':id' => $this->id]);
		return self::count();
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
			'email' => $this->email,
			'name' => $this->name
		];
	}

	/**
	 * Hash and return the given password.
	 * 
	 * @param string $password Unhashed password
	 * @return string Just hashed password
	 */
	private static function hash($password) {
		return password_hash($password, PASSWORD_BCRYPT);
	}

}