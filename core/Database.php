<?php
/**
 * Abstract class Database to be extended by modals in order to connect and make
 * operations over the database.
 * 
 * @package msg.dw2e (https://github.com/bgonp/msg.dw2e)
 * @author Borja Gonzalez <borja@bgon.es>
 */
abstract class Database {

	private static $conn = false;
	private static $stmt;

	/**
	 * Get database configuration from file config/database.json and stablish a connection
	 * with the database.
	 * 
	 * @return bool
	 * @throws Exception If error occurred while connecting
	 */
	public static function connect(){
		if (self::$conn === false) {
			if (!($conf = json_decode(@file_get_contents(CONFIG_DIR.'database.json'))))
				return false;
			try {
				$conn = new PDO("mysql:host={$conf->host};dbname={$conf->name}", $conf->user, $conf->pass);
			} finally {
				if (!$conn) throw new Exception(Text::error('database_connect'));
			}
			self::$conn = $conn;
		}
		return true;
	}

	/**
	 * Makes a query over the database. Result of the query must be obteined by fetch method below.
	 * 
	 * @param  string $sql SQL string to create the prepared statement
	 * @param  array $params Associative arrays with params to bind in the statement
	 * @throws Exception If error occurred while executing the query
	 */
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

	/**
	 * Fetch result of the previous called query. Fetch mode is FETCH_ASSOC.
	 * 
	 * @param  boolean $all Fetch all or fetch single row
	 * @return array Fetched data
	 */
	protected static function fetch($all = false) {
		if (!self::$stmt) return false;
		if ($all) return self::$stmt->fetchAll();
		else return self::$stmt->fetch();
	}

	/**
	 * Get the numer of rows affected by a query.
	 * 
	 * @return int Numer of affected rows
	 */
	protected static function count() {
		if (!self::$stmt) return 0;
		return self::$stmt->rowCount();
	}

	/**
	 * Get the primary key of last INSERT statement.
	 * 
	 * @return int|bool Primary key of last inserted row or false if error.
	 */
	protected static function insertId(){
		if (!self::$conn) return false;
		return self::$conn->lastInsertId();
	}

}
