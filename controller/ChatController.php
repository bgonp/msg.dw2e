<?php
/**
 * Trait that groups all chat related actions to be used by main controller.
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
trait ChatController {

	/**
	 * Create a chat room. If some member is not current user's friend that user won't
	 * be added.
	 * 
	 * Post info needed to perform this action is:
	 * <li>name - Name of the chat room
	 * <li>members - Array of users ID
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function createChat($post, $files) {
		if (empty($post['name'])) {
			$response = ['type' => 'error', 'message' => Text::error('chat_name')];
		} else if (!isset($post['members']) || !is_array($post['members'])) {
			$response = ['type' => 'error', 'message' => Text::error('chat_member')];
		} else {
			$user = User::get(SessionController::logged());
			$friends = [];
			foreach ($post['members'] as $friend)
				if (!$user->friends($friend))
					return ['type' => 'error', 'message' => Text::error('no_friend')];
			$chat = Chat::create($post['name']);
			$chat->addUser($user);
			$todos = true;
			foreach ($post['members'] as $member)
				if (!$chat->addUser($member)) $todos = false;
			$response = ['focus' => 'chats', 'chats' => $user->newChats($post['last_received'])];
			if (!$todos) {
				$response['type'] = 'error';
				$response['message'] = Text::error('chat_add');
			}
		}
		return $response;
	}

	/**
	 * Remove current user from a chat room.
	 * 
	 * Post info needed to perform this action is:
	 * <li>chat_id - ID of chat to be removed from
	 * 
	 * @param  array $post Contains needed key-value pairs to be used
	 * @param  array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function leaveChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get(SessionController::logged());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else {
				$chat->removeUser($user);
				$response = ['type' => 'success', 'message' => Text::success('chat_leave')];
			}
		}
		return $response;
	}

	/**
	 * Load chat information if current user belongs to the chat.
	 * 
	 * Post info needed to perform this action is:
	 * <li>chat_id - ID of chat to be loaded
	 * 
	 * @param  array $post Contains needed key-value pairs to be used
	 * @param  array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function loadChat($post, $files) {
		if (empty($post['chat_id'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get(SessionController::logged());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else {
				$response = $chat->jsonSerialize();
				$response['messages'] = array_values($chat->messages());
				$response['members'] = array_values($chat->users());
				$response['candidates'] = $chat->candidates($user->id());
				$user->readChat($chat->id());
			}
		}
		return $response;
	}

	/**
	 * Add a member to a chat, only if member is a friend of current user.
	 * 
	 * Post info needed to perform this action is:
	 * <li>chat_id - ID of chat where the user will be added
	 * <li>contact_id - ID of the user to be added
	 * 
	 * @param  array $post Contains needed key-value pairs to be used
	 * @param  array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function addMember($post, $files) {
		if (empty($post['chat_id']) || empty($post['contact_id'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else {
			$user = User::get(SessionController::logged());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else if (!($friend = $user->friends($post['contact_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('no_friend')];
			} else if (!$chat->addUser($friend)) {
				$response = ['type' => 'error', 'message' => Text::error('chat_add')];
			} else {
				$response = ['type' => 'success', 'message' => Text::success('chat_invite')];
			}
		}
		return $response;
	}

	/**
	 * Add a message to a chat, only if current user belongs to the chat.
	 * 
	 * Post info needed to perform this action is:
	 * <li>message - Message as a string
	 * <li>chat_id - ID of the chat where the message will be sent
	 * <li>last_read - ID of the last message read, to sync front and server side
	 * 
	 * @param  array $post Contains needed key-value pairs to be used
	 * @param  array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function sendMessage($post, $files) {
		if (empty($post['chat_id']) || empty($post['message'])) {
			$response = ['type' => 'error', 'message' => Text::error('missing_data')];
		} else if (!Helper::validText($post['message'])) {
			$response = ['type' => 'error', 'message' => Text::error('message_length')];
		} else {
			$user = User::get(SessionController::logged());
			if (!($chat = $user->chats($post['chat_id']))) {
				$response = ['type' => 'error', 'message' => Text::error('chat_wrong')];
			} else if ($chat->addMessage($user->id(), $post['message'], $files['attachment'] ?? false) === false) {
				$response = ['type' => 'error', 'message' => Text::error('message_add')];
			} else {
				$user->readChat($chat->id());
				$response = ['messages' => $chat->newMessages($post['last_read'])];
			}
		}
		return $response;
	}

}