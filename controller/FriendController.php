<?php
/**
 * Trait that groups all friends related actions to be used by main controller.
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
trait FriendController {

	/**
	 * Request friendship to another user by email.
	 * 
	 * Post info needed to perform this action is:
	 * <li>email - Email of user to be added as a friend of current user
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function requestFriend($post, $files) {
		$user = User::get(SessionController::logged());
		$user->addContact($post['email']);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_sent')
		];
		return $response;
	}

	/**
	 * Accept requested friendship from another user.
	 * 
	 * Post info needed to perform this action is:
	 * <li>contact_id - User ID which friendship will be accepted
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function acceptFriend($post, $files) {
		$user = User::get(SessionController::logged());
		$user->updateContact($post['contact_id'], Helper::ACCEPTED);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_accepted'),
			'focus' => 'friends'
		];
		return $response;
	}

	/**
	 * Decline requested friendship from another user.
	 * 
	 * Post info needed to perform this action is:
	 * <li>contact_id - User ID which friendship will be accepted
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function rejectFriend($post, $files) {
		$user = User::get(SessionController::logged());
		$user->updateContact($post['contact_id'], Helper::DECLINED);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_declined')
		];
		return $response;
	}

	/**
	 * Block existing friendship. This operation can't be restored.
	 * 
	 * Post info needed to perform this action is:
	 * <li>contact_id - User ID which friendship will be accepted
	 * 
	 * @param array $post Contains needed key-value pairs to be used
	 * @param array $files This param is not used
	 * @return string JSON that contains result of the operation
	 */
	private static function blockFriend($post, $files) {
		$user = User::get(SessionController::logged());
		$user->updateContact($post['contact_id'], Helper::BLOCKED);
		$response = [
			'type' => 'success',
			'message' => Text::success('friendship_blocked')
		];
		return $response;
	}
	
}