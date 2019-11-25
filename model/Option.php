<?php

class Option extends Database {
	
	private $id;
	private $key;
	private $type;
	private $name;
	private $value;
	private static $list;

	private function __construct($id, $key, $type, $name, $value) {
		$this->id = $id;
		$this->key = $key;
		$this->type = $type;
		$this->name = $name;
		$this->value = $value;
	}

	public static function get($key = null) {
		if (!is_array(self::$list)) self::list();
		if (is_null($key)) return self::$list;
		return empty(self::$list[$key]) ? false : self::$list[$key]->value;
	}

	public function key() {
		return $this->key;
	}

	public function type() {
		return $this->type;
	}

	public function name() {
		return $this->name;
	}

	public function value() {
		return $this->value;
	}

	public function __toString() {
		return $this->value;
	}

	public static function update($key, $value) {
		if (!is_array(self::$list)) self::list();
		if (isset(self::$list[$key])) {
			$value = self::escape($value);
			self::$list[$key]->value = $value;
			return self::$list[$key]->save();
		}
		return false;
	}

	private static function list() {
		self::$list = [];
		$sql = "SELECT * FROM option";
		$list = self::query($sql);
		while ($opt = $list->fetch_object())
			self::$list[$opt->key] = new Option($opt->id, $opt->key, $opt->type, $opt->name, $opt->value);
	}

	private function save() {
		$sql = "UPDATE option SET value = '{$this->value}' WHERE id = {$this->id}";
		return self::query($sql);
	}

}