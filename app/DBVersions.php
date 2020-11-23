<?php

class DBVersions {
	
	public $version;
	public $name;
	public $locked;
	public $review_phase;
	public $response_phase;
	public $current;
	
	public function __construct($data) {
		foreach ($data as $key => $val) {
			if ($key == 'locked') $val = (bool) $val;
			if ($key == 'current') $val = (bool) $val;
			$this->$key = $val;
		}
	}
	
	public static $table;

	public static function init() {
		$q = Env::$db->query('SELECT * FROM "versions" ORDER BY "version" ASC');
		$results = [];
		while ($data = $q->fetch()) {
			$results[$data->version] = new DBMeeting($data);
		}
		self::$table = $results;
	}
	
	public static function hasVersion($version) {
		return isset(self::$table[$version]);
	}
	
	public static function getVersion($version) {
		return self::$table[$version];
	}
	
	public static function getCurrentVersion() {
		foreach (self::$table as $entry) {
			if ($entry->current) {
				return $entry;
			}
		}
	}
}