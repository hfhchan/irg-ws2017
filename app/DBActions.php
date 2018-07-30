<?php

class DBActions {
	const ACTION_TYPES = [
		'UNIFIED BY (Working Set)',
		'UNIFIED BY (Horizontal Extension)',
		'UNIFIED BY (IVD)',
		'UNIFIED BY',
		'UNIFY',
		'NOT_UNIFIED_BY',
		'NOT_UNIFY',
		'WITHDRAWN',
		'EVIDENCE_ACCEPTED',
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

	public $db;
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	public function getSN() {
		return sprintf('%05d', $this->sn);
	}

	public function getTypeIndex() {
		return array_search($this->type, self::ACTION_TYPES);
	}

	public static function getList() {
		$q = Env::$db->prepare('SELECT * FROM "actions" ORDER BY "date" ASC');
		$q->execute([ $user ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getAll($sq_number) {
		$q = Env::$db->prepare('SELECT * FROM "actions" WHERE "sn" = ? ORDER BY "date" ASC, "version" DESC');
		$q->execute([$sq_number]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function save($sq_number, $type, $value, $session) {
		$q = Env::$db->prepare('INSERT INTO "actions" ("sn", "type", "value", "version", "date", "session") VALUES (?, ?, ?, ?, DATETIME(\'NOW\'), ?)');
		$q->execute([$sq_number, $type, $value, Workbook::VERSION, $session]);
	}
}
