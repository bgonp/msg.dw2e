<?php

class Install {

	public static function run($post) {
		if (empty($post['password']) || $post['password'] != $post['password_rep'] ||
			!($email = filter_var($post['email'], FILTER_VALIDATE_EMAIL)))
			throw new Exception("Installation a");

		$db = [
			'host' => $post['host'] ?? '',
			'name' => $post['name'] ?? '',
			'user' => $post['user'] ?? '',
			'pass' => $post['pass'] ?? ''
		];
		$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
		if ($conn->connect_errno)
			throw new Exception("Installation b");

		if (!($sql = file_get_contents(CONFIG_DIR.'install.sql')))
			throw new Exception("Installation c");

		if ($conn->multi_query($sql)) {
			while ($conn->next_result());
		} else throw new Exception("Installation d");

		if (!file_put_contents(CONFIG_DIR.'database.json', json_encode($db)))
			throw new Exception("Installation e");

		Usuario::new($email, 'admin', $post['password'], 0, 1, 1);

		// TODO: Mail configuration

		return true;
	}

}