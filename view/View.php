<?php

abstract class View {

	// ------------------------
	// Pages
	// ------------------------
	public static function main($user, $options) {
		$content = self::menu($user);
		$content .= self::sidebar($user->chats(), $user->friends(), $user->requests());
		$content .= self::messages();
		$content .= self::alert();
		$content .= self::loading();
		$content .= self::vars($user->id(), $user->lastReceived(), $user->lastContactUpd());
		echo self::page($content, 'main', $options);
	}

	public static function login($options) {
		$content = self::loginForm();
		$content .= self::alert();
		$content .= self::loading();
		echo self::page($content, 'login', $options);
	}

	public static function recover($user, $code, $options) {
		$content = self::recoverForm($user, $code);
		$content .= self::alert();
		$content .= self::loading();
		echo self::page($content, 'recover', $options);
	}
	
	public static function error($message, $options) {
		$content = self::errorMessage($message);
		echo self::page($content, 'error', $options);
	}

	public static function options($options) {
		$content = self::optionsForm($options);
		$content .= self::alert();
		$content .= self::loading();
		echo self::page($content, 'options', $options);
	}

	public static function install($options) {
		$content = self::installForm();
		$content .= self::alert();
		$content .= self::loading();
		echo self::page($content, 'options', $options);
	}

	// ------------------------
	// E-mails
	// ------------------------
	public static function emailConfirm($user) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{NAME}}' => $user->name(),
			'{{CODE}}' => $user->getNewCode(),
			'{{DOMAIN}}' => Helper::currentUrl()
		];
		$content = strtr(file_get_contents(HTML_DIR.'email/confirm.html'), $replace);
		return self::email($content,'Confirm your account');
	}

	public static function emailReset($user) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{NAME}}' => $user->name(),
			'{{CODE}}' => $user->getNewCode(),
			'{{DOMAIN}}' => Helper::currentUrl()
		];
		$content = strtr(file_get_contents(HTML_DIR.'email/recover.html'), $replace);
		return self::email($content,'Reset your password');
	}

	// ------------------------
	// Page parts functions
	// ------------------------
	private static function email($content, $title) {
		$replace = [
			'{{CONTENT}}' => Text::translate($content),
			'{{TITLE}}' => $title
		];
		return strtr(file_get_contents(HTML_DIR.'email/email.html'), $replace);
	}

	private static function page($content, $clase, $options) {
		$replace = [
			'{{CONTENT}}' => Text::translate($content),
			'{{CLASS}}' => $clase,
			'{{TITLE}}' => $options['page_title'],
			'{{COLORS}}' => self::colors($options['color_main'], $options['color_bg'], $options['color_border']),
		];
		return strtr(file_get_contents(HTML_DIR.'page.html'), $replace);
	}

	private static function colors($main, $background, $border) {
		$replace = [
			'{{MAIN}}' => $main,
			'{{BACKGROUND}}' => $background,
			'{{BORDER}}' => $border
		];
		return strtr(file_get_contents(HTML_DIR.'colors.html'), $replace);
	}

	private static function installForm() {
		return file_get_contents(HTML_DIR.'install.html');
	}

	private static function recoverForm($user ,$code) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{CODE}}' => $code
		];
		return strtr(file_get_contents(HTML_DIR.'recover.html'), $replace);
	}

	private static function loginForm() {
		$replace = [
			'{{CLASS}}' => 'email-' . Option::get('email_confirm')
		];
		return strtr(file_get_contents(HTML_DIR.'login.html'), $replace);
	}
	
	private static function menu($user) {
		$replace = [
			'{{ID}}' => $user->id(),
			'{{EMAIL}}' => $user->email(),
			'{{NAME}}' => $user->name()
		];
		return strtr(file_get_contents(HTML_DIR.'menu.html'), $replace);
	}
	
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
	
	private static function chat($chat) {
		$replace = [
			'{{ID}}' => $chat->id(),
			'{{NAME}}' => $chat->name(),
			'{{LASTMSG}}' => $chat->last_msg(),
			'{{CLASS}}' => $chat->unread() ? ' unread' : ''
		];
		return strtr(file_get_contents(HTML_DIR.'chat.html'), $replace);
	}
	
	private static function friend($friend) {
		$replace = [
			'{{ID}}' => $friend->id(),
			'{{NAME}}' => $friend->name(),
			'{{EMAIL}}' => $friend->email()
		];
		return strtr(file_get_contents(HTML_DIR.'friend.html'), $replace);
	}
	
	private static function request($request) {
		$replace = [
			'{{ID}}' => $request->id(),
			'{{NAME}}' => $request->name(),
			'{{EMAIL}}' => $request->email()
		];
		return strtr(file_get_contents(HTML_DIR.'request.html'), $replace);
	}

	private static function messages() {
		return file_get_contents(HTML_DIR.'messages.html');
	}

	private static function alert() {
		return file_get_contents(HTML_DIR.'alert.html');
	}

	private static function loading() {
		return file_get_contents(HTML_DIR.'loading.html');
	}

	private static function vars($user_id, $last_msg, $last_contact_upd) {
		$replace = [
			'{{ID}}' => $user_id,
			'{{LASTMESSAGE}}' => $last_msg,
			'{{LASTCONTACT}}' => $last_contact_upd
		];
		return strtr(file_get_contents(HTML_DIR.'vars.html'), $replace);
	}

	private static function errorMessage($message) {
		$replace = [
			'{{MESSAGE}}' => $message,
			'{{URL}}' => Helper::currentUrl()
		];
		return strtr(file_get_contents(HTML_DIR.'error.html'), $replace);
	}

	private static function optionsForm($options) {
		$replace = ['{{OPTIONS}}' => ""];
		foreach ($options as $option)
			$replace['{{OPTIONS}}'] .= self::option($option);
		return strtr(file_get_contents(HTML_DIR.'options.html'), $replace);
	}

	private static function option($option) {
		$replace = [
			'{{KEY}}' => $option->key(),
			'{{TYPE}}' => $option->type(),
			'{{NAME}}' => $option->name(),
			'{{VALUE}}' => $option->value()
		];
		return strtr(file_get_contents(HTML_DIR.'option.html'), $replace);
	}

}