<?php

class DBCharacterGlyph {
	public $source_identifier;
	public $region;
	public $data;
	public $version;
	
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
		
		$this->data = json_decode($this->data);
		
		if (strpos($this->source_identifier, 'G') === 0) {
			$this->region = 'G';
		} else if (strpos($this->source_identifier, 'K') === 0) {
			$this->region = 'K';
		} else if (strpos($this->source_identifier, 'UK') === 0) {
			$this->region = 'UK';
		} else if (strpos($this->source_identifier, 'USAT') === 0) {
			$this->region = 'SAT';
		} else if (strpos($this->source_identifier, 'T') === 0) {
			$this->region = 'T';
		} else if (strpos($this->source_identifier, 'UTC') === 0) {
			$this->region = 'UTC';
		} else if (strpos($this->source_identifier, 'V') === 0) {
			$this->region = 'V';
		}
	}

	public static function get($sourceRef, $version = '4.0') {
		if (preg_match('@^G_([A-Z]+)([0-9]+)$@', $sourceRef, $matches)) {
			$sourceRef = 'G' . $matches[1] . '-' . $matches[2];
		}

		$q = Env::$db->prepare('SELECT * FROM "character_glyphs" WHERE "source_identifier" = ? AND "version" <= ? ORDER BY "version" DESC LIMIT 1');
		$q->execute([ $sourceRef, $version ]);
		$data = $q->fetch();
		if (!$data) {
			return null;
		}
		$entry = new self($data);
		return $entry;
	}

	public static function getAll($sourceRef, $version = '4.0') {
		if (preg_match('@^G_([A-Z]+)([0-9]+)$@', $sourceRef, $matches)) {
			$sourceRef = 'G' . $matches[1] . '-' . $matches[2];
		}

		$q = Env::$db->prepare('SELECT * FROM "character_glyphs" WHERE "source_identifier" = ? AND "version" <= ? ORDER BY "version" DESC');
		$q->execute([ $sourceRef, $version ]);
		$entries = [];
		while ($data = $q->fetch()) {
			$entries[] = new self($data);
		}
		return $entries;
	}

	public static function add($source_identifier, $image, $data, $version) {
		$q = Env::$db->prepare('INSERT INTO "character_glyphs" (source_identifier, data, version) VALUES(?, ?, ?, ?)');
		$q->execute([ $source_identifier, $data, $version ]);
	}
}