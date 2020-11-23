<?php

class DBProcessedInstance {
	const TYPE_UNIFICATION = 1;
	const TYPE_ATTRIBUTES = 2;
	public $db;

	public function __construct($user_id) {
		$this->db = Env::$db;
		$this->user_id = $user_id;
	}

	public function getTotal() {
		$stmt = $this->db->prepare('SELECT COUNT(*) FROM "processed" WHERE "type" = ? AND "submitter" = ?');
		$stmt->execute([ self::TYPE_UNIFICATION, $this->user_id ]);
		return $stmt->fetchColumn();
	}

	public function get($sq_number, $type) {
		static $stmt;
		if (!$stmt) 
			$stmt = $this->db->prepare('SELECT COUNT(*) FROM "processed" WHERE "sn" = ? AND "type" = ? AND "submitter" = ?');
		$stmt->execute([ $sq_number, $type, $this->user_id ]);
		return (bool) $stmt->fetchColumn();
	}

	public function set($sq_number, $type) {
		$stmt = $this->db->prepare('INSERT OR IGNORE INTO "processed" ("sn", "type", "date", "submitter") VALUES (?, ?, ?, ?)');
		$stmt->execute([ $sq_number, $type, date('Y-m-d H:i:s'), $this->user_id ]);
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