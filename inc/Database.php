<?php

class Database {

	private static $conn = false;

	private static function Connect(){
		if (self::$conn === false) {
			$conf = json_decode(file_get_contents(dirname(__FILE__).'/configuration.json'));
			$conn = new mysqli($conf->host, $conf->user, $conf->pass, $conf->name);
			if ($conn->connect_errno)
				die("Fallo al conectar a BD: " . $conn->connect_error);
			else
				self::$conn = $conn;
		}

	}

	public static function Query($sql) {
		self::Connect();
		return self::$conn->query($sql);
	}

	public static function Escape($string) {
		self::Connect();
		return self::$conn->real_escape_string($string);
	}

	public static function InsertId(){
		self::Connect();
		return self::$conn->insert_id;
	}

}
