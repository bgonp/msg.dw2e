<?php

abstract class Database {

	private static $conn = false;

	private static function connect(){
		if (self::$conn === false) {
			$conf = json_decode(file_get_contents(CONFIG_DIR.'database.json'));
			$conn = new mysqli($conf->host, $conf->user, $conf->pass, $conf->name);
			if ($conn->connect_errno)
				die("Fallo al conectar a BD: " . $conn->connect_error);
			else
				self::$conn = $conn;
		}

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
