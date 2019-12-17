<?php
/**
 * Trait that groups all contact related actions to be used by user model.
 * 
 * @package model
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
trait Contact {

	/**
	 * Return an associative array with the users that are friends of this user. Once
	 * generated from database, the array is saved in the object in order to don't have to
	 * do the same query again if needed.
	 * 
	 * If this method receives a user id, it returns the user if he is a friend of current
	 * user (instead of the whole array) or false if not.
	 * 
	 * @param integer $friend_id (optional) User ID to check if he is a friend
	 * @return array|User|false Associative array of users or a user if friend_id was passed
	 */
	public function friends($friend_id = null) {
		if (!is_array($this->friends))
			$this->friends = self::contacts(Helper::ACCEPTED);
		if (is_null($friend_id)) return $this->friends;
		return $this->friends[intval($friend_id)] ?? false;
	}

	/**
	 * Given the last contact update moment, this method returns friends which have been added
	 * from that moment.
	 * 
	 * @param string $last Last contact update moment
	 * @return array Array of friends
	 */
	public function newFriends($last) {
		return $this->newContacts($last, Helper::ACCEPTED);
	}

	/**
	 * Return an associative array with the users that ask this user to be his friend. Once
	 * generated from database, the array is saved in the object in order to don't have to
	 * do the same query again if needed.
	 * 
	 * If this method receives a user id, it returns the user if this user has a pending
	 * friendship request with him (instead of the whole array) or false if not.
	 * 
	 * @param integer $request_id (optional) User ID to check if he request friendship
	 * @return array|User|false Associative array of users or a user if request_id was passed
	 */
	public function requests($request_id = null) {
		if (!is_array($this->requests))
			$this->requests = self::contacts(Helper::WAITING);
		if (is_null($request_id)) return $this->requests;
		return $this->requests[intval($request_id)] ?? false;
	}

	/**
	 * Given the last contact update moment, this method returns friendship requests which
	 * have been added from that moment.
	 * 
	 * @param string $last Last contact update moment
	 * @return array Array of requests
	 */
	public function newRequests($last) {
		return $this->newContacts($last, Helper::WAITING);
	}


	/**
	 * Return an associative array with the users that have some kind of friendship with this user.
	 * This friendship can have one of the following states: WAITING, ACCEPTED, DECLINED or BLOCKED.
	 * 
	 * @param integer $state State of the friendship to return
	 * @return array Associative array of users
	 */
	private function contacts($state) {
		$and = $state == Helper::WAITING ? "AND c.`user_state_id` <> :userid" : "";
		$sql = "
			SELECT u.`id`,
				   u.`email`,
				   u.`name`,
				   u.`password`,
				   u.`avatar`
			FROM `user` u
			WHERE u.`admin` = 0
			AND u.`id` IN (
				SELECT IF(c.`user_1_id` = :userid, c.`user_2_id`, c.`user_1_id`) user_id
				FROM `contact` c
				WHERE c.`state` = :state {$and}
				AND (c.`user_1_id` = :userid OR c.`user_2_id` = :userid)
			)
			ORDER BY u.`name` ASC";
		self::query($sql, [
			':userid' => $this->id,
			':state' => $state
		]);
		return self::gets();
	}

	/**
	 * Given the last contact update moment, this method returns contacts which
	 * have been updated from that moment and have the state passed.
	 * 
	 * @param string $last Last contact update moment
	 * @return array Array of contacts
	 */
	private function newContacts($last, $state) {
		$and = $state == Helper::WAITING ? " AND c.`user_state_id` <> :userid" : "";
		$sql = "
			SELECT u.`id`,
				   u.`name`,
				   u.`email`,
				   t.`date_upd`
			FROM `user` u
			RIGHT JOIN (
				SELECT
					c.`date_upd`, IF(c.`user_1_id` = :userid, c.`user_2_id`, c.`user_1_id`) user_id
				FROM `contact` c
				WHERE c.`state` = :state {$and}
				AND (c.`user_1_id` = :userid OR c.`user_2_id` = :userid)
				AND `date_upd` > :last
			) t
			ON u.`id` = t.`user_id`
			WHERE u.`admin` = 0
			ORDER BY t.`date_upd` DESC";
		self::query($sql, [
			':userid' => $this->id,
			':state' => $state,
			':last' => $last
		]);
		return self::fetch(true);
	}

	/**
	 * Return the last contact update moment
	 * 
	 * @return string Last contact update timestamp
	 */
	public function lastContactUpd() {
		$sql = "
			SELECT MAX(c.`date_upd`) last_contact_upd
			FROM `contact` c
			WHERE c.`user_1_id` = :id OR c.`user_2_id` = :id";
		self::query($sql, [':id' => $this->id]);
		return self::fetch()['last_contact_upd'];
	}


	/**
	 * Create a friendship request to the user passed (by ID or by email). This request
	 * is a new contact register with waiting state.
	 * 
	 * @param integer|string $id_o_email ID or email of the user to add
	 * @throws Exception If contact couldn't be created
	 */
	public function addContact($id_o_email) {
		$contact = self::get($id_o_email);
		if ($contact->id === $this->id) throw new Exception(Text::error('contact_self'));		
		$user1_id = min($this->id, $contact->id);
		$user2_id = max($this->id, $contact->id);
		$sql = "
			INSERT INTO `contact` (`user_1_id`, `user_2_id`, `user_state_id`)
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

	/**
	 * Update a contact to a certain state. If the contact is waiting, the user who doesn't
	 * init the action can accept or decline it. If the contact is accepted (that is they're
	 * friends) any of them can block the friendship.
	 * 
	 * @param integer $contact_id User ID which friendship has to be updated
	 * @param integer $state New contact state to update
	 * @return boolean True if contact was updated
	 * @throws Exception If some problem occurred when updating
	 */
	public function updateContact($contact_id, $state) {
		$state = intval($state);
		if ($state < Helper::ACCEPTED || $state > Helper::BLOCKED)
			throw new Exception(Text::error('contact_state'));
		$user1_id = min($this->id, $contact_id);
		$user2_id = max($this->id, $contact_id);
		$sql = "
			UPDATE `contact` SET `state` = :state, `user_state_id` = :userid
			WHERE `user_1_id` = :user1id
			AND `user_2_id` = :user2id
			AND `state` = :reqstate";
		$replace = [
			':state' => $state,
			':userid' => $this->id,
			':user1id' => $user1_id,
			':user2id' => $user2_id
		];
		if ($state === Helper::ACCEPTED || $state === Helper::DECLINED) {
			$replace[':reqstate'] = Helper::WAITING;
			$replace[':contactid'] = $contact_id;
			$sql .= " AND `user_state_id` = :contactid";
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

}