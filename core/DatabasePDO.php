<?php

abstract class DatabasePDO {

	private static $conn = false;
	private static $stmt;

	protected static function connect(){
		if (self::$conn === false) {
			if (!($conf = json_decode(@file_get_contents(CONFIG_DIR.'database.json'))))
				return false;
			try {			
				$conn = new PDO("mysql:host={$conf->host};dbname={$conf->name}", $conf->user, $conf->pass);
			} catch (PDOException $e) {
				throw new Exception($e->getMessage());
				throw new Exception(Text::error('database_connect'));
			}
			self::$conn = $conn;
		}
		return true;
	}

	protected static function query($sql, $params) {
		self::connect();
		try {
			self::$stmt = self::$conn->prepare($sql);
			self::$stmt->setFetchMode(PDO::FETCH_ASSOC);
			self::$stmt->execute($params);
		} catch (PDOException $e) {
			throw new Exception(Text::error('database_query'));			
		}
	}

	protected static function fetch($all = false) {
		if (!self::$stmt) return false;
		if ($all) return self::$stmt->fetchAll();
		else return self::$stmt->fetch();
	}

	protected static function count() {
		if (!self::$stmt) return 0;
		return self::$stmt->rowCount();
	}

	protected static function insertId(){
		if (!self::$conn) return false;
		return self::$conn->lastInsertId();
	}

}
