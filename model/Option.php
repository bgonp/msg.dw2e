<?php
/**
 * Option model that represents a stored option. Extends Database class in order to use
 * its methods to connect and handle database queries.
 * 
 * @package model
 * @author Borja Gonzalez <borja@bgon.es>
 * @link https://github.com/bgonp/msg.dw2e
 * @license https://opensource.org/licenses/GPL-3.0 GNU GPL 3
 */
class Option extends Database {
	
	/** @var integer Option ID */
	private $id;
	/** @var string Option key */
	private $key;
	/** @var string Option type */
	private $type;
	/** @var string Option display name */
	private $name;
	/** @var string Option value */
	private $value;
	/** @var array Array with all stored options */
	private static $list;

	/**
	 * Private constructor. An object can't be constructed directly, but through static
	 * factory methods.
	 * 
	 * @param integer $id Stored option ID
	 * @param string $key Option key
	 * @param string $type Option type
	 * @param string $name Option display name
	 * @param string $value Option value
	 */
	private function __construct($id, $key, $type, $name, $value) {
		$this->id = $id;
		$this->key = $key;
		$this->type = $type;
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * Static factory method that initialize static array with all the stored options if
	 * it isn't initialized yet. Then returns the Option object with passed $key or false
	 * if option doesn't exist.
	 * 
	 * @param string $key Key of the wanted option
	 * @return Option|bool Option object wanted or false if it doesn't exist
	 */
	public static function get($key = null) {
		if (!is_array(self::$list)) self::all();
		if (is_null($key)) return self::$list;
		return empty(self::$list[$key]) ? false : self::$list[$key]->value;
	}

	/**
	 * Return key property of the option
	 * 
	 * @return string Key of the option
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * Return type property of the option
	 * 
	 * @return string Type of the option
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * Return display name property of the option
	 * 
	 * @return string Display name of the option
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * Return value property of the option. This will be called automatically if trying to
	 * parse object to string.
	 * 
	 * @return string Value of the option
	 */
	public function __toString() {
		return $this->value;
	}

	/**
	 * Update stored option value identifying it by key.
	 * 
	 * @param  string $key Option key of the option to be updated
	 * @param  string $value New option value
	 * @return bool Whethever option could be updated or not
	 */
	public static function update($key, $value) {
		if (!is_array(self::$list)) self::all();
		if (isset(self::$list[$key])) {
			self::$list[$key]->value = $value;
			return self::$list[$key]->save();
		}
		return false;
	}

	/**
	 * Initialize static associative array with all the stored options in database. This
	 * array will have the following structure: [key_of_option] => Option object
	 */
	private static function all() {
		self::$list = [];
		$sql = "SELECT * FROM `option`";
		self::query($sql, []);
		while ($opt = self::fetch())
			self::$list[$opt['key']] = new Option(
				$opt['id'],
				$opt['key'],
				$opt['type'],
				$opt['name'],
				$opt['value']
			);
	}

	/**
	 * Update option in database with the current option object properties
	 */
	private function save() {
		$sql = "UPDATE `option` SET `value` = :value WHERE `id` = :id";
		self::query($sql, [
			':value' => $this->value,
			':id' => $this->id
		]);
	}

}