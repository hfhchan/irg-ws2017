<?php

declare(strict_types=1);

class DBChanges {
	
	public $id;
	public $discussion_record_id;
	public $sn;
	public $type;
	public $value;
	
	public function __construct(object $data) {
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
		$this->sn = sprintf('%05d', $data->sn);
	}
	
	public function getSN() : string {
		return sprintf('%05d', $this->sn);
	}
	
	public static function getChangeById($id) : array{
		$q = Env::$db->prepare('SELECT * FROM "changes" WHERE "id" = ? LIMIT 1');
		$q->execute([ $sn ]);
		$data = $q->fetch();
		if ($data) return new self($data);
		return null;
	}
	
	public static function getChangesForSN($sn) : array{
		$q = Env::$db->prepare('SELECT * FROM "changes" WHERE "sn" = ? ORDER BY "version"');
		$q->execute([ $sn ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getChangesForSNVersion($sn, $version) : array{
		$q = Env::$db->prepare('SELECT * FROM "changes" WHERE "sn" = ? AND "version2" <= ? ORDER BY "version"');
		$q->execute([ $sn, $version ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}
	
	public static function getChangesForDiscussionRecord($discussion_record_id) : array{
		$q = Env::$db->prepare('SELECT * FROM "changes" WHERE "discussion_record_id" = ?');
		$q->execute([ $discussion_record_id ]);
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getChanges($version) : array{
		$q = Env::$db->prepare('SELECT * FROM "changes" WHERE "version1" = ?');
		$q->execute(array($version));
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function getOrphanedChanges($version) : array{
		$q = Env::$db->prepare('SELECT * FROM "changes" WHERE "discussion_record_id" IS NULL AND "version1" = ?');
		$q->execute(array($version));
		$results = [];
		while ($data = $q->fetch()) {
			$results[] = new self($data);
		}
		return $results;
	}

	public static function add($discussion_record_id, $sn, $type, $value, $version1, $version2, $user_id) : void {
		$q = Env::$db->prepare('INSERT INTO "changes" (discussion_record_id, sn, type, value, version1, version2, user_id) VALUES(?, ?, ?, ?, ?, ?, ?)');
		$q->execute([ $discussion_record_id, $sn, $type, $value, $version1, $version2, $user_id ]);
	}
	
	public function getDescription() : string {
		ob_start();
		
		if ($this->type === 'Discussion Record') {
			if ($this->discussion_record_id) {
				if ($this->value !== '(superseded)') {
					echo 'For ' . $this->sn .', update ' . $this->type . ' to "' . $this->value . '"';
				} else {
					echo 'Discussion Record #' . $this->discussion_record_id . ' marked as superseded';
				}
			} else {
				echo 'For ' . $this->sn .', add ' . $this->type . ' "' . $this->value . '"';
			}
		} else if (preg_match('@^Discussion Record #([0-9]+)$@', $this->type, $matches)) {
			$supercededChange = DBChanges::getChangeById($matches[1]);
			echo 'For ' . $this->sn .', replace ' . $this->type . ' with "' . $this->value . '"';
		} else if ($this->type === 'Radical') {
			echo 'For ' . $this->sn .', change ' . $this->type . ' to ' . $this->value;
			
			if (ctype_digit($this->value)) {
				$radicals = getIdeographForRadical($this->value);
				echo ' (' . $radicals[0] . ')';
			} else if (ctype_digit(substr($this->value, 0, -2)) && substr($this->value, -2) === '.1') {
				$radicals = getIdeographForSimpRadical(substr($this->value, 0, -2));
				echo ' (' . $radicals[0] . ')';
			} else {
				echo 'WARNING: could not parse radical';
			}
			
		} else {
			if (strpos($this->type, ' Source') !== false && $this->value === '(empty)') {
				echo 'For ' . $this->sn .', remove ' . $this->type . '.';
			} else {
				echo 'For ' . $this->sn .', change ' . $this->type . ' to ' . $this->value;
			}
		}

		return ob_get_clean();
	}

	private static function endsWith($haystack, $needle) {
		return substr($haystack,-strlen($needle)) === $needle;
	}

	public function getEffectiveSession() : string {
		if (self::endsWith($this->value, 'IRG 52.') || ($this->version1 == '3.0' && $this->version2 == '4.0')) {
			return "52";
		}

		// Records changed after IRG 53 missing from v5.0 due to merge issue, added back in v5.1
		if (self::endsWith($this->value, 'IRG 53.') && ($this->version1 == '5.0' && $this->version2 == '5.1')) {
			return "53m";
		}

		// Sort V source corrections at the end of the list
		if ($this->value === 'V source corrected, 2020-06.') {
			return '0';
		}

		// Meeting was cancelled
		if (self::endsWith($this->value, '2020-06.')) {
			return "54c";
		}

		// Meeting was cancelled
		if (self::endsWith($this->value, '2020-11.') || ($this->version1 == '5.1' && $this->version2 == '5.2')) {
			return "55c";
		}

		// Changes after IRG#56
		if (self::endsWith($this->value, '2021-03.') || ($this->version1 == '5.2' && $this->version2 == '6.0')) {
			return "56a";
		}

		// Changes after IRG#56
		if (self::endsWith($this->value, '2021-06.') || ($this->version1 == '6.0' && $this->version2 == '6.1')) {
			return "56b";
		}

		throw new Exception('Unknown session for entry ' . $this->value . ' (Change #' . $this->id . ')');
	}
}