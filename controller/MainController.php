<?php
/**
 * Class with static functions to handle requests of the whole app.
 * It takes requests data, calls the models needed, manage the actions to perform and returns json
 * response or calls the view to echo html content or send an email.
 * 
 * @package controller
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class MainController {

	// This class uses traits AdminController, ChatController, FriendController, UserController in
	// order to organize the different actions by groups of related ones.
	use AdminController, ChatController, FriendController, UserController;

	/**
	 * Handle ajax requests, call the correspond action method and echo json response to be
	 * processed by the client.
	 */
	public static function ajax() {
		if ($_SERVER["REQUEST_METHOD"] === 'POST') {
			header('Content-Type: application/json');
			if (!empty($_POST['action']) && method_exists(__CLASS__, $_POST['action'])){
				// These are the actions which don't require to be logged in
				$unlogged_actions = ['login', 'register', 'resetSend', 'resetPassword', 'installApp'];
				try {
					$req_login = !in_array($_POST['action'], $unlogged_actions);
					if ($req_login xor SessionController::logged())
						// If requested action requires login and no user logged in (or viceversa)
						$response = ['redirect' => Helper::currentUrl()];
					else
						$response = self::{$_POST['action']}($_POST, $_FILES);
				} catch (Exception $e) {
					$response = ['type' => 'error', 'message' => $e->getMessage()];
				}
			} else {
				$response = ['type' => 'error', 'message' => Text::error('invalid_action')];
			}
			echo json_encode($response);
		} else {
			header('Location: '.Helper::currentUrl());
			die();
		}
	}

	/**
	 * Handle main requests. It echoes the complete HTML code by calling View class.
	 * 
	 * It shows main app page if user are logged in or login form if not. It also can show
	 * installation page if app is not installed or settings page if logged user has admin role.
	 * It will show error page if some error occurred.
	 */
	public static function main() {
		try {
			if (!Database::connect()) {
				// If can't connect to database, shows installation page
				SessionController::logout();
				View::install([
					'page_title' => 'DW2E Messaging App',
					'color_main' => '#1b377a',
					'color_aux' => '#f0f5ff'
				]);
			} else if (!empty($_GET)) {
				// If GET data exists, calls the related method or redirect if it doesn't exist
				if (SessionController::logged() || empty($_GET['action']) || !method_exists(__CLASS__, $_GET['action'])) {
					header('Location: '.Helper::currentUrl());
					die();
				} else {
					self::{$_GET['action']}($_GET);
				}
			} else if (SessionController::admin()) {
				// If logged user has admin role, shows options page
				View::options(Option::get());
			} else if ($user_id = SessionController::logged()) {
				// If there is a logged user, shows his main page
				View::main(User::get($user_id), Option::get());
			} else {
				// If not, shows login/registration page
				View::login(Option::get());
			}
		} catch (Exception $ex) {
			SessionController::logout();
			View::error($ex->getMessage(), Option::get());
		}
	}

	/**
	 * This method will be called by the client (through main method) to check if there is something new
	 * in order to refresh front page.
	 * 
	 * Post info needed to perform this action is:
	 * <li>chat_id - Current chat id opened in front page
	 * <li>last_read - Last read message id of the current chat
	 * <li>last_received - Last received message\n
	 * <li>last_contact_upd - Timestamp of last contact received (friend or request)
	 * 
	 * @param  array $post Contains needed info to check for updates
	 * @param  array $files This param is not used
	 * @return string JSON that contains new data to be processed by client
	 */
	private static function update($post, $files) {
		$user = User::get(SessionController::logged());
		$response = ['user_id' => $user->id()];
		if (!empty($post['chat_id']) && ($chat = $user->chats($post['chat_id'])))
			if ($messages = $chat->newMessages($post['last_read'])) {
				$user->readChat($chat->id());
				$response['messages'] = $messages;
				if ($chat->newMembers()) {
					$response['members'] = array_values($chat->users());
					$response['candidates'] = $chat->candidates($user->id());
				}
			}
		if (isset($post['last_received']) && ($chats = $user->newChats($post['last_received'])))
			$response['chats'] = $chats;
		if (isset($post['last_contact_upd']) && $post['last_contact_upd'] < ($update = $user->lastContactUpd())) {
			if ($friends = $user->newFriends($post['last_contact_upd']))
				$response['friends'] = $friends;
			if ($requests = $user->newRequests($post['last_contact_upd']))
				$response['requests'] = $requests;
			$response['last_contact_upd'] = $update;
		}
		return $response;
	}

}