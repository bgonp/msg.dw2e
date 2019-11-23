<?php

abstract class Database {

	private static $conn = false;

	public static function connect(){
		if (self::$conn === false) {
			$conf = json_decode(@file_get_contents(CONFIG_DIR.'database.json'));
			if (!$conf) return false;
			$conn = new mysqli($conf->host, $conf->user, $conf->pass, $conf->name);
			if ($conn->connect_errno)
				throw new Exception(Text::error('database_connect'));
			else
				self::$conn = $conn;
		}
		return true;
	}

	public static function query($sql) {
		self::Connect();
		return self::$conn->query($sql);
	}

	public static function escape($string) {
		self::Connect();
		return self::$conn->real_escape_string($string);
	}

	public static function insertId(){
		self::Connect();
		return self::$conn->insert_id;
	}

}
