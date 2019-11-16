<?php

class Install {

	public static function run($post) {
		if (empty($post['password']) || $post['password'] != $post['password_rep'] ||
			!($email = filter_var($post['email'], FILTER_VALIDATE_EMAIL)))
			throw new Exception(Helper::error('install_userpass'));

		$db = [
			'host' => $post['host'] ?? '',
			'name' => $post['name'] ?? '',
			'user' => $post['user'] ?? '',
			'pass' => $post['pass'] ?? ''
		];
		$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
		if ($conn->connect_errno)
			throw new Exception(Helper::error('install_connect'));

		if (!($sql = file_get_contents(CONFIG_DIR.'install.sql')))
			throw new Exception(Helper::error('install_getfile'));

		if ($conn->multi_query($sql)) {
			while ($conn->next_result());
		} else throw new Exception(Helper::error('install_tables'));

		if (!file_put_contents(CONFIG_DIR.'database.json', json_encode($db)))
			throw new Exception(Helper::error('install_putfile'));

		Usuario::new($email, 'admin', $post['password'], 0, 1, 1);

		return true;
	}

}