<?php

class DBDiscussionRecord {

	const ACTION_TYPES = [
		'UNIFIED BY',
		'UNIFIED BY (Working Set)',
		'UNIFIED BY (Horizontal Extension)',
		'UNIFIED BY (IVD)',
		'UNIFY',
		'NOT_UNIFIED_TO',
		'NOT_UNIFY',
		'WITHDRAWN',
		'EVIDENCE_ACCEPTED',
		'POSTPONE',
		'PENDING',
		'PENDING_RESOLVED',
		'UPDATE_RADICAL',
		'UPDATE_SC',
		'UPDATE_FS',
		'UPDATE_TC',
		'UPDATE_IDS',
		'UPDATE_TRAD_SIMP',
		'UPDATE_GLYPH_SHAPE',
		'MODIFY_CODED_CHARACTER',
		'OTHER_DECISION'
	];
	
	const UNIFICATION_TYPES = [
		'UNIFIED BY (Working Set)',
		'UNIFIED BY (Horizontal Extension)',
		'UNIFIED BY (IVD)',
		'UNIFIED BY',
		'UNIFY',
		'NOT_UNIFIED_TO',
		'NOT_UNIFY',
	];

	public $db;
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	public function getSN() {
		return sprintf('%05d', $this->sn);
	}

	public function isUnification() {
		return array_search($this->type, self::UNIFICATION_TYPES) !== false;
	}

	public function getTypeIndex() {
		return array_search($this->type, self::ACTION_TYPES);
	}
	
	public function toLocalDate() {
		$date = new DateTime($this->date . ' +0000');
		$date->setTimezone(DBMeeting::getTimezone($this->session));
		return $date->format('Y-m-d (D)');
	}

	public function toLocalTime() {
		$date = new DateTime($this->date . ' +0000');
		$date->setTimezone(DBMeeting::getTimezone($this->session));
		return $date->format('g:i a');
	}
	
	public function toLocalTimezone() {
		return DBMeeting::getOffset($this->session);
	}

	public function getVersion() {
		$version = $this->session - 49;
		if ($version == 8) {
			return '6.0';
		}
		if ($version == 7) {
			return '5.2';
		}
		if ($version == 6) {
			return '5.1';
		}
		if ($version == 1) {
			return '1.1';
		}
		return $version . '.0';
	}

	public static function getSessions() {
		$q = Env::$db->query('SELECT DISTINCT "session" FROM "discussion_record" ORDER BY "session" DESC');
		$results = [];
		while ($data = $q->fetchColumn()) {
			$results[] = $data;
		}
		return $results;
	}

	public static function getList($session) {
		$q = Env::$db->prepare('SELECT * FROM "discussion_record" WHERE "session" =? ORDER BY "date" ASC');
		$q->execute([ $session ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getAll($sq_number) {
		$q = Env::$db->prepare('SELECT * FROM "discussion_record" WHERE "sn" = ? ORDER BY "date" ASC, "session" DESC');
		$q->execute([$sq_number]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getByQuery($query) {
		$q = Env::$db->prepare('SELECT DISTINCT "sn" FROM "discussion_record" WHERE "value" LIKE ? ORDER BY "session" DESC');
		$q->execute(['%' . $query . '%']);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = str_pad(intval(ltrim($data->sn, '0')), 5, '0', STR_PAD_LEFT);
		}
		return $results;
	}

	public static function save($sq_number, $type, $value, $session, $user) {
		$q = Env::$db->prepare('INSERT INTO "discussion_record" ("sn", "type", "user", "value", "date", "session") VALUES (?, ?, ?, ?, DATETIME(\'NOW\'), ?)');
		$q->execute([$sq_number, $type, $user, $value, $session]);
	}
}
