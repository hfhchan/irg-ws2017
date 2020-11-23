<?php


class DBComments {
	const COMMENT_TYPES = [
		'LABEL',

		'UNIFICATION',
		'NO_UNIFICATION',
		'DISUNIFICATION',
		'UCV',

		'ATTRIBUTES_RADICAL',
		'ATTRIBUTES_SC',
		'ATTRIBUTES_FS',
		'ATTRIBUTES_TC',
		'ATTRIBUTES_IDS',
		'ATTRIBUTES_TRAD_SIMP',

		'MISIDENTIFIED_GLYPH',
		'UNCLEAR_EVIDENCE',
		'UNCLEAR_EVIDENCE_RESPONSE',
		'EVIDENCE',
		'NEW_EVIDENCE',

		'GLYPH_DESIGN',
		'NORMALIZATION',

		'EDITORIAL_ISSUE',

		'COMMENT',
		'COMMENT_IGNORE',
		'OTHER',

		'SEMANTIC_VARIANT',
		'SIMP_VARIANT',
		'TRAD_VARIANT',

		// Deprecated
		'CODEPOINT_CHANGED',
		'RESPONSE',
		'WITHDRAW',
	];

	public $db;
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
		$this->created_by = (int) $this->created_by;
		$this->modified_by = (int) $this->modified_by;
		$this->deleted_by = (int) $this->deleted_by;
		
		// Legacy
		$this->date = $this->created_at;
		$this->submitter = $this->created_by;
		$this->deleted = $this->deleted_by > 0 ? '1' : '0';
	}
	
	public function isModified() {
		return $this->modified_by !== $this->created_by && $this->modified_at !== $this->created_at;
	}
	
	public function isDeleted() {
		return $this->deleted_by > 0;
	}

	public function getSN() {
		return sprintf('%05d', $this->sn);
	}

	public function getCategoryForCommentType() {
		return self::getCategoryForType($this->type);
	}

	public function mayHaveUnificationTargets() {
		return $this->type === 'UNIFICATION' || $this->type === 'DISUNIFICATION';
	}

	public static function getCategoryForType($type) {
		if ($type === 'LABEL') {
			return 'Labels';
		}
		if ($type === 'UNIFICATION' || $type === 'NO_UNIFICATION' || $type === 'DISUNIFICATION' || $type === 'UCV') {
			return 'Unification';
		}
		if ($type === 'ATTRIBUTES_RADICAL' || $type === 'ATTRIBUTES_SC' ||
			$type === 'ATTRIBUTES_FS' || $type === 'ATTRIBUTES_TC' ||
			$type === 'ATTRIBUTES_IDS' || $type === 'ATTRIBUTES_TRAD_SIMP') {
			return 'Attributes';
		}
		if ($type === 'UNCLEAR_EVIDENCE' || $type === 'EVIDENCE' || $type === 'UNCLEAR_EVIDENCE_RESPONSE' || $type === 'NEW_EVIDENCE' || $type === 'MISIDENTIFIED_GLYPH') {
			return 'Evidence';
		}
		if ($type === 'NORMALIZATION' || $type === 'GLYPH_DESIGN') {
			return 'Glyph Design & Normalization';
		}
		if ($type === 'EDITORIAL_ISSUE') {
			return 'Editorial';
		}
		if ($type === 'COMMENT' || $type === 'OTHER' || $type === 'COMMENT_IGNORE') {
			return 'Other';
		}
		if ($type === 'TRAD_VARIANT' || $type === 'SIMP_VARIANT' || $type === 'SEMANTIC_VARIANT') {
			return 'Data for Unihan';
		}
		return $type;
	}

	public function getTypeIndex() {
		$type = $this->type;
		if ($type === 'LABEL') {
			return -100;
		}
		if ($type === 'UNIFICATION' || $type === 'NO_UNIFICATION' || $type === 'DISUNIFICATION' || $type === 'UCV') {
			return -99;
		}
		if ($type === 'ATTRIBUTES_RADICAL' || $type === 'ATTRIBUTES_SC' ||
			$type === 'ATTRIBUTES_FS' || $type === 'ATTRIBUTES_TC' ||
			$type === 'ATTRIBUTES_IDS' || $type === 'ATTRIBUTES_TRAD_SIMP') {
			return -98;
		}
		if ($type === 'UNCLEAR_EVIDENCE' || $type === 'EVIDENCE' || $type === 'NEW_EVIDENCE' || $type === 'UNCLEAR_EVIDENCE_RESPONSE' || $type === 'MISIDENTIFIED_GLYPH') {
			return -97;
		}
		if ($type === 'NORMALIZATION' || $type === 'GLYPH_DESIGN') {
			return -96;
		}
		if ($type === 'EDITORIAL_ISSUE') {
			return -95;
		}
		if ($type === 'COMMENT' || $type === 'OTHER') {
			return -94;
		}
		return array_search($this->type, self::COMMENT_TYPES);
	}

	public static function getList($user = '2', $version = null) {
		if ($version === null) {
			$version = Workbook::VERSION;
		}
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "created_by" = ? AND "version" = ? ORDER BY "created_at" ASC');
		$q->execute([ $user, $version ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getListAll($version = null) {
		if ($version === null) {
			$version = Workbook::VERSION;
		}
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "version" = ? ORDER BY "created_at" ASC');
		$q->execute([ $version ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getAll($sq_number) {
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "sn" = ? ORDER BY "created_at" ASC, "version" DESC');
		$q->execute([$sq_number]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getAllLabelsForChar($sq_number) {
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "sn" = ? AND "type" = ? ORDER BY "created_at" ASC, "version" DESC');
		$q->execute([$sq_number, 'LABEL']);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getById($id) {
		$q = Env::$db->prepare('SELECT * FROM "comments" WHERE "id" = ?');
		$q->execute([ $id ]);
		$data = $q->fetch();
		unset($q);
		if (!$data) return null;
		return new self($data);
	}
	
	public static function getByQuery($query) {
		$q = Env::$db->prepare('SELECT DISTINCT "sn" FROM "comments" WHERE "comment" LIKE ? ORDER BY "version" DESC');
		$q->execute(['%' . $query . '%']);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = str_pad(intval(ltrim($data->sn, '0')), 5, '0', STR_PAD_LEFT);
		}
		return $results;
	}

	public static function getByLabel($label) {
		$q = Env::$db->prepare('SELECT DISTINCT "sn" FROM "comments" WHERE "type" = ? AND "comment" = ? ORDER BY "version" DESC');
		$q->execute(['LABEL', $label]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = str_pad(intval(ltrim($data->sn, '0')), 5, '0', STR_PAD_LEFT);
		}
		return $results;
	}

	public static function getAllLabels($user_id) {
		$q = Env::$db->prepare('SELECT DISTINCT "comment" as "label" FROM "comments" WHERE "type" = ? AND "created_by" = ?');
		$q->execute(['LABEL', $user_id]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = $data->label;
		}
		return $results;
	}
	
	public function canEdit($user) {
		return $user && !$this->isDeleted() && ($user->isAdmin() || $user->getUserId() == $this->created_by);
	}

	public function canDelete($user) {
		return $user && !$this->isDeleted() && ($user->isAdmin() || $user->getUserId() == $this->created_by);
	}
	
	public function edit($type, $comment, $user_id) {
		Env::$db->beginTransaction();
		$q = Env::$db->prepare('
			UPDATE "comments" SET
				"type" = ?, "comment" = ?,
				"current_version" = "current_version" + 1,
				"modified_at" = DATETIME(\'NOW\'), "modified_by" = ? WHERE "id" = ?');
		$q->execute([ $type, $comment, $user_id, $this->id ]);
		self::archive($this->id);
		$result = Env::$db->commit();
		return $result;
	}

	public function delete($user_id) {
		$q = Env::$db->prepare('UPDATE "comments" SET "deleted_at" = datetime(\'now\'), "deleted_by" = ? WHERE "id" = ?');
		$q->execute([ $user_id, $this->id ]);
		return (bool) $q->rowCount();
	}

	public static function save($sq_number, $type, $comment, $created_by) {
		Env::$db->beginTransaction();
		$q = Env::$db->prepare('
			INSERT INTO "comments" (
				"sn", "type", "comment", "version",
				"created_by", "created_at", "modified_by", "modified_at"
			) VALUES (
				?, ?, ?, ?, ?, DATETIME(\'NOW\'), ?, DATETIME(\'NOW\')
			)
		');
		$q->execute([$sq_number, $type, $comment, Workbook::VERSION, $created_by, $created_by]);
		$id = Env::$db->lastInsertId();
		self::archive($id);
		$result = Env::$db->commit();
		return $result;
	}

	private static function archive($id) {
		$q = Env::$db->prepare('INSERT INTO "comment_log" (
			"comment_id",
			"sn",
			"type",
			"comment",
			"version",
			"comment_version",
			"archived_by",
			"archived_at"
		) SELECT 
			"id",
			"sn",
			"type",
			"comment",
			"version",
			"comment_version",
			"modified_by",
			"modified_at"
		FROM "comments" WHERE "id" = ?');
		$q->execute([ $id ]);
	}
	
	public function toLocalDate() {
		$date = new DateTime($this->created_at . ' +0000');
		$date->setTimezone(DBMeeting::getTimezone(52));
		return $date->format('Y-m-d (D)');
	}

	public function toLocalTime() {
		$date = new DateTime($this->created_at . ' +0000');
		$date->setTimezone(DBMeeting::getTimezone(52));
		return $date->format('g:i a');
	}
	
	public function toLocalTimezone() {
		return DBMeeting::getOffset(52);
	}
	
	public function getVersion() {
		if ($this->version == intval($this->version)) {
			return $this->version . '.0';
		}
		return $this->version . '';
	}
}
