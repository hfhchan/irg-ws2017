<?php

class DBMeeting {
	
	public $session;
	public $timezone_offset;
	public $location;
	public $comment;
	
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}
	
	public static $table;

	public static function init() {
		$q = Env::$db->query('SELECT * FROM "meetings"');
		$results = [];
		while ($data = $q->fetch()) {
			$results[$data->session] = new DBMeeting($data);
		}
		self::$table = $results;
	}
	
	public static function getMeeting($meeting) {
		return self::$table[$meeting];
	}

	public static function getLocation($meeting) {
		return self::$table[$meeting]->location;
	}
	
	public static function getOffset($meeting) {
		return self::$table[$meeting]->timezone_offset;
	}
	
	public static function getTimezone($meeting) {
		return new DateTimeZone(self::$table[$meeting]->timezone_offset);
	}
}