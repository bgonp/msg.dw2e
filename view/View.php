<?php
/**
 * Abstract class View with static methods to echo or get html content.
 * Each method here calls another methods from the same class for each part
 * of the html code to get.
 * 
 * @package view
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class View {

	/**
	 * Echoes the whole main page of the app.
	 * 
	 * @param User $user Current logged user to echo his main page
	 * @param array $options Associative array of strings with options to be applied
	 */
	public static function main($user, $options) {
		$content = self::menu($user);
		$content .= self::content($user->chats(), $user->friends(), $user->requests());
		$content .= self::alert();
		$content .= self::loading();
		$content .= self::vars($user->id(), $user->lastReceived(), $user->lastContactUpd());
		echo self::page($content, 'main', $options);
	}

	/**
	 * Echoes the login page.
	 * 
	 * @param array $options Associative array of strings with options to be applied
	 */
	public static function login($options) {
		$content = self::loginForm();
		$content .= self::alert();
		$content .= self::loading();
		$content .= self::vars();
		echo self::page($content, 'login', $options);
	}

	/**
	 * Echoes the reset password page.
	 * 
	 * @param User $user Current logged user to echo his main page
	 * @param string $code Code to be validated
	 * @param array $options Associative array of strings with options to be applied
	 */
	public static function recover($user, $code, $options) {
		$content = self::recoverForm($user, $code);
		$content .= self::alert();
		$content .= self::loading();
		echo self::page($content, 'recover', $options);
	}

	/**
	 * Echoes the error messager page.
	 * 
	 * @param string $message Error message to be shown
	 * @param array $options Associative array of strings with options to be applied
	 */
	public static function error($message, $options) {
		$content = self::errorMessage($message);
		echo self::page($content, 'error', $options);
	}

	/**
	 * Echoes the admin options page.
	 * 
	 * @param array $options Associative array of strings with options to be shown
	 */
	public static function options($options) {
		$content = self::optionsForm($options);
		$content .= self::alert();
		$content .= self::loading();
		$content .= self::vars();
		echo self::page($content, 'options', $options);
	}

	/**
	 * Echoes the first use installation page.
	 * 
	 * @param array $options Associative array of strings with options to be applied
	 */
	public static function install($options) {
		$content = self::installForm();
		$content .= self::alert();
		$content .= self::loading();
		echo self::page($content, 'options', $options);
	}

	/**
	 * Returns the confirm account e-mail html body.
	 * 
	 * @param User $user User to be confirmed
	 * @return string Html e-mail content
	 */
	public static function emailConfirm($user) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{NAME}}' => $user->name(),
			'{{CODE}}' => $user->getNewCode(),
			'{{DOMAIN}}' => Helper::currentUrl()
		];
		$content = strtr(file_get_contents(HTML_DIR.'email/confirm.html'), $replace);
		return self::email($content, Text::translate('{{TR:CONFIRM}}'));
	}

	/**
	 * Returns the reset password e-mail html body.
	 * 
	 * @param User $user User to be confirmed
	 * @return string Html e-mail content
	 */
	public static function emailReset($user) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{NAME}}' => $user->name(),
			'{{CODE}}' => $user->getNewCode(),
			'{{DOMAIN}}' => Helper::currentUrl()
		];
		$content = strtr(file_get_contents(HTML_DIR.'email/recover.html'), $replace);
		return self::email($content,Text::translate('{{TR:RESET}}'));
	}

	// --------------------------------------------------
	// Each of the following private function returns a
	// part of the web html code from html files and
	// replace {{KEYWORDS}} by crucial data.
	// --------------------------------------------------

	/** Return email framed html content */
	private static function email($content, $title) {
		$replace = [
			'{{CONTENT}}' => Text::translate($content),
			'{{TITLE}}' => $title
		];
		return strtr(file_get_contents(HTML_DIR.'email/email.html'), $replace);
	}

	/** Return page framed html content */
	private static function page($content, $clase, $options) {
		$replace = [
			'{{CONTENT}}' => Text::translate($content),
			'{{CLASS}}' => $clase,
			'{{TITLE}}' => $options['page_title'],
			'{{COLORS}}' => self::colors($options['color_main'], $options['color_aux']),
		];
		return strtr(file_get_contents(HTML_DIR.'page.html'), $replace);
	}

	/** Return html header code with style tag containing css colors vars */
	private static function colors($main, $aux) {
		$replace = [
			'{{MAIN}}' => $main,
			'{{AUX}}' => $aux
		];
		return strtr(file_get_contents(HTML_DIR.'colors.html'), $replace);
	}

	/** Return install html form */
	private static function installForm() {
		return file_get_contents(HTML_DIR.'install.html');
	}

	/** Return reset password html form */
	private static function recoverForm($user ,$code) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{CODE}}' => $code
		];
		return strtr(file_get_contents(HTML_DIR.'recover.html'), $replace);
	}

	/** Return login/register html form */
	private static function loginForm() {
		$replace = [
			'{{CLASS}}' => 'email-' . Option::get('email_confirm')
		];
		return strtr(file_get_contents(HTML_DIR.'login.html'), $replace);
	}
	
	/** Return main menu of main page */
	private static function menu($user) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{EMAIL}}' => $user->email(),
			'{{NAME}}' => $user->name()
		];
		return strtr(file_get_contents(HTML_DIR.'menu.html'), $replace);
	}
	
	/** Return main content of main page */
	private static function content($chats, $friends, $requests) {
		$replace = [
			'{{SIDEBAR}}' => self::sidebar($chats, $friends, $requests),
			'{{MESSAGES}}' => self::messages()
		];
		return strtr(file_get_contents(HTML_DIR.'content.html'), $replace);
	}
	
	/** Return sidebar of main page */
	private static function sidebar($chats, $friends, $requests) {
		$replace = [
			'{{CHATS}}' => "",
			'{{FRIENDS}}' => "",
			'{{REQUESTS}}' => ""
		];
		foreach ($chats as $chat)
			$replace['{{CHATS}}'] .= self::chat($chat);
		foreach ($friends as $friend)
			$replace['{{FRIENDS}}'] .= self::friend($friend);
		foreach ($requests as $request)
			$replace['{{REQUESTS}}'] .= self::request($request);
		$replace['{{NEWREQUESTS}}'] = $replace['{{REQUESTS}}'] ? ' new' : '';
		return strtr(file_get_contents(HTML_DIR.'sidebar.html'), $replace);
	}
	
	/** Return single chat list element */
	private static function chat($chat) {
		$replace = [
			'{{ID}}' => $chat->id(),
			'{{NAME}}' => $chat->name(),
			'{{LASTMSG}}' => $chat->last_msg(),
			'{{CLASS}}' => $chat->unread() ? ' unread' : ''
		];
		return strtr(file_get_contents(HTML_DIR.'chat.html'), $replace);
	}
	
	/** Return single friend list element */
	private static function friend($friend) {
		$replace = [
			'{{ID}}' => $friend->id(),
			'{{NAME}}' => $friend->name(),
			'{{EMAIL}}' => $friend->email()
		];
		return strtr(file_get_contents(HTML_DIR.'friend.html'), $replace);
	}
	
	/** Return single request list element */
	private static function request($request) {
		$replace = [
			'{{ID}}' => $request->id(),
			'{{NAME}}' => $request->name(),
			'{{EMAIL}}' => $request->email()
		];
		return strtr(file_get_contents(HTML_DIR.'request.html'), $replace);
	}

	/** Return messages container */
	private static function messages() {
		return file_get_contents(HTML_DIR.'messages.html');
	}

	/** Return alert message box */
	private static function alert() {
		return file_get_contents(HTML_DIR.'alert.html');
	}

	/** Return loading screen (hidden by default) */
	private static function loading() {
		return file_get_contents(HTML_DIR.'loading.html');
	}

	/** Return html code with script tag containing js vars */
	private static function vars($user_id = null, $last_msg = null, $last_contact_upd = null) {
		$replace = [
			'{{ID}}' => $user_id ?? 0,
			'{{LASTMESSAGE}}' => $last_msg ?? 0,
			'{{LASTCONTACT}}' => $last_contact_upd ?? ''
		];
		return strtr(file_get_contents(HTML_DIR.'vars.html'), $replace);
	}

	/** Return critical error screen */
	private static function errorMessage($message) {
		$replace = [
			'{{MESSAGE}}' => $message,
			'{{URL}}' => Helper::currentUrl()
		];
		return strtr(file_get_contents(HTML_DIR.'error.html'), $replace);
	}

	/** Return admin options form */
	private static function optionsForm($options) {
		$replace = ['{{OPTIONS}}' => ""];
		foreach ($options as $option)
			$replace['{{OPTIONS}}'] .= self::option($option);
		return strtr(file_get_contents(HTML_DIR.'options.html'), $replace);
	}

	/** Return single option list element */
	private static function option($option) {
		$replace = [
			'{{KEY}}' => $option->key(),
			'{{TYPE}}' => $option->type(),
			'{{NAME}}' => $option->name(),
			'{{VALUE}}' => $option
		];
		return strtr(file_get_contents(HTML_DIR.'option.html'), $replace);
	}

}