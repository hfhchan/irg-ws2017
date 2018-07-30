<?php

class DBProcessedInstance {
	const TYPE_UNIFICATION = 1;
	const TYPE_ATTRIBUTES = 2;
	public $db;

	public function __construct() {
		$this->db = Env::$db;
	}

	public function getTotal() {
		$stmt = $this->db->prepare('SELECT COUNT(*) FROM "processed" WHERE "type" = ?');
		$stmt->execute([ self::TYPE_UNIFICATION ]);
		return $stmt->fetchColumn();
	}

	public function get($sq_number, $type) {
		static $stmt;
		if (!$stmt) 
			$stmt = $this->db->prepare('SELECT COUNT(*) FROM "processed" WHERE "sn" = ? and "type" = ?');
		$stmt->execute([ $sq_number, $type ]);
		return (bool) $stmt->fetchColumn();
	}

	public function set($sq_number, $type) {
		$stmt = $this->db->prepare('INSERT INTO "processed" ("sn", "type", "date") VALUES (?, ?, ?)');
		$stmt->execute([ $sq_number, $type, date('Y-m-d H:i:s') ]);
		return true;
	}

	public function hasReviewedUnification($sq_number) {
		return $this->get($sq_number, self::TYPE_UNIFICATION);
	}

	public function hasReviewedAttributes($sq_number) {
		return $this->get($sq_number, self::TYPE_ATTRIBUTES);
	}

	public function setReviewedUnification($sq_number) {
		$this->set($sq_number, self::TYPE_UNIFICATION);
	}

	public function setReviewedAttributes($sq_number) {
		$this->set($sq_number, self::TYPE_ATTRIBUTES);
	}
}