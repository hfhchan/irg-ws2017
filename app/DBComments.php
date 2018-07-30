<?php


class DBComments {
	const COMMENT_TYPES = [
		'KEYWORD',
		'UNIFICATION',
		'ATTRIBUTES_RADICAL',
		'ATTRIBUTES_SC',
		'ATTRIBUTES_FS',
		'ATTRIBUTES_TC',
		'ATTRIBUTES_IDS',
		'ATTRIBUTES_TRAD_SIMP',
		'GLYPH_DESIGN',
		'NORMALIZATION',
		'COMMENT',
		'DISUNIFICATION',
		'UNCLEAR_EVIDENCE',
		'EDITORIAL_ISSUE',
		'SEMANTIC_VARIANT',
		'SIMP_VARIANT',
		'TRAD_VARIANT',
		'CODEPOINT_CHANGED',
		'OTHER',
		'COMMENT_IGNORE',
		'RESPONSE'
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
		return array_search($this->type, self::COMMENT_TYPES);
	}

	public static function getList($user = '2') {
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "submitter" = ? ORDER BY "date" ASC');
		$q->execute([ $user ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getListAll() {
		$q = Env::$db->prepare('SELECT * FROM "comments" ORDER BY "date" ASC');
		$q->execute([]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getAll($sq_number) {
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "sn" = ? ORDER BY "date" ASC, "version" DESC');
		$q->execute([$sq_number]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getByKeyword($keyword) {
		$q = Env::$db->prepare('SELECT "sn" FROM "comments" WHERE "type" = ? AND "comment" = ? ORDER BY "version" DESC');
		$q->execute(['KEYWORD', $keyword]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = str_pad(intval(ltrim($data->sn, '0')), 5, '0', STR_PAD_LEFT);
		}
		return $results;
	}

	public static function getAllKeywords($user_id) {
		$q = Env::$db->prepare('SELECT DISTINCT "comment" as "keyword" FROM "comments" WHERE "type" = ? AND "submitter" = ?');
		$q->execute(['KEYWORD', $user_id]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = $data->keyword;
		}
		return $results;
	}

	public static function save($sq_number, $type, $comment, $submitter) {
		$q = Env::$db->prepare('INSERT INTO "comments" ("sn", "type", "comment", "version", "submitter", "date") VALUES (?, ?, ?, ?, ?, DATETIME(\'NOW\'))');
		$q->execute([$sq_number, $type, $comment, Workbook::VERSION, $submitter]);
	}
}
