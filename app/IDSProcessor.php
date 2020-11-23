<?php

class IDSProcessor {
	
	public $idsString;
	public $ids;
	public $radicals;
	public $results;
	
	public function __construct($ids, $radical, $isSimp) {
		if (!is_string($ids)) {
			throw new Exception('$ids should be string.');
		}
		$ids = trim($ids);
		$this->idsString = $ids;
		$this->ids = parseStringIntoCodepointArray(trim($ids));
		
		if ($isSimp) {
			$radicals = getIdeographForSimpRadical(intval($radical));
		} else {
			$radicals = getIdeographForRadical($radical);
		}

		$this->radicals = $radicals;
	}
	
	private static function parse_children($ids_row, $part_codepoint) {
		$children = [];
		foreach ($ids_row->ids_list as $i => $ids_list) {
			$poisoned = false;
			$components = $ids_list->components;
			if (count($components) === 1 && $components[0] === $ids_row->char) {
				continue;
			}

			$obj2 = [];
			foreach ($components as $component) {
				if ($component === '&CDP-8D50;') {
					$component = '監';
				} else if (substr($component, 0, 5) === '&CDP-') {
					$poisoned = true;
					continue;
				}
				
				$codepoint = charToCodepoint($component);
				if (codepointIsIDC($codepoint)) {
					continue;
				}

				list($ts1, $fs1) = getTotalStrokes($codepoint);
				$obj3 = new Character($codepoint, $ts1, $fs1);
				if (!$obj3->strokeCount) {
					$ids_row = \IDS\getIDSforCodepoint($codepoint);
					if ($ids_row) {
						$obj3->children = self::parse_children($ids_row, $obj3->codepoint);
					}
				}
				$obj2[] = $obj3;
			}

			if (!$poisoned) {
				$children[] = new SubSequence($obj2, $part_codepoint);
			}
		}

		return $children;
	}
	
	public function getResults() {
		if ($this->results != null) {
			return $this->results;
		}

		$radical_found = false;
		
		$self = $this;

		$results = array_map(function ($part) use ($self, $radical_found) {
			if ($part[0] === '&') {
				if (preg_match('@^&([H|S|P|D|Z])([0-9]+)-[0-9]{2};$@', $part, $matches)) {
					if ($matches[1] == 'H') $fs = 1;
					if ($matches[1] == 'S') $fs = 2;
					if ($matches[1] == 'P') $fs = 3;
					if ($matches[1] == 'D') $fs = 4;
					if ($matches[1] == 'Z') $fs = 5;
					$response = new Character($part, $matches[2], $fs);
					return $response;
				}
				return null;
			}
			if (codepointIsIDC($part)) {
				return null;
			}
			if ($part === ' ') {
				return null;
			}
			$codepoint = $part;
			$char = codepointToChar($codepoint);

			$response = new Character($codepoint, null, null);	

			if (in_array($char, $self->radicals) && !$radical_found) {
				$response->isRadical = true;
				$radical_found = true;
			}

			list($stroke_count, $first_stroke) = getTotalStrokes($part);

			if ($stroke_count) {
				$response->strokeCount = +$stroke_count;
			} else {
				$response->strokeCount = null;
			}
			if ($first_stroke) {
				$response->firstStroke = +$first_stroke;
			} else {
				$response->firstStroke = null;
			}
			
			if ($response->strokeCount) {
				return $response;
			}
			
			// no stroke count...
			$ids_row = \IDS\getIDSforCodepoint($codepoint);
			if (!$ids_row) {
				return $response;
			}
			
			$response->children = self::parse_children($ids_row, $response->codepoint);
			return $response;
		}, $this->ids);

		$this->results = array_values(array_filter($results, function($part) { return $part !== null; }));

		return $this->results;
	}
	
	public function getCounts() {
		$results = $this->getResults();

		$stroke_count = 0;
		$total_count  = 0;
		$first_stroke = false;
		$radical_found = false;
		foreach ($results as $i => $part) {
			if ($part->isRadical) {
				$radical_found = true;
				$total_count += $part->strokeCount;
			} else {
				$stroke_count += $part->strokeCount;
				$total_count  += $part->strokeCount;
				if ($first_stroke === false) {
					$first_stroke = $part->firstStroke;
				}
			}
		}
		
		return [$stroke_count, $total_count, $first_stroke, $radical_found];
	}
}

class Character{
	public $char;
	public $codepoint;
	public $identifier;
	public $isRadical = false;
	public $strokeCount;
	public $firstStroke;
	
	public function __construct($codepoint, $strokeCount, $firstStroke) {
		if ($codepoint[0] === '&') {
			$this->char = $codepoint;
			$this->codepoint = $codepoint;
			$this->identifier = $codepoint;
		} else {
			$this->char = codepointToChar($codepoint);
			$this->codepoint = $codepoint;
			$this->identifier = $this->char . ' (' . $codepoint . ')';
		}
		$this->strokeCount = $strokeCount != null ? +$strokeCount : null;
		$this->firstStroke = $firstStroke != null ? +$firstStroke : null;
	}
}

class SubSequence{
	public $ts;
	public $fs;
	public $charList;
	public $link;
	
	public static function calculateTotalStrokeAndFirstStroke($charList) {
		$total_strokes = 0;
		$first_stroke = null;

		foreach ($charList as $i => $part) {
			if (isset($part->children)) {
				$data = array_map(function($subSequence) {
					list ($ts, $fs) = self::calculateTotalStrokeAndFirstStroke($subSequence->charList);
					if ($ts) {
						return $ts . '|' . $fs;
					}
					return 0;
				}, $part->children);

				$data = array_values(array_filter(array_unique($data)));

				if (count($data) === 1) {
					list ($ts, $fs) = explode('|', $data[0]);
					$total_strokes += $ts;
					if (is_null($first_stroke)) {
						$first_stroke = $fs;
					}
				}
				continue;
			}
			$total_strokes += $part->strokeCount;
			if ($part->char === codepointToChar('U+8FB6')) {
				continue;
			}
			if (isset($charList[$i - 1]) && $charList[$i - 1]->char === codepointToChar('U+2FF4') && $part->char === '北') {
				continue;
			}
			if (is_null($first_stroke)) {
				$first_stroke = $part->firstStroke;
			}
		}
		return [$total_strokes, $first_stroke];
	}
		
	public function __construct($charList, $codepoint) {
		list ($ts, $fs) = self::calculateTotalStrokeAndFirstStroke($charList);
		$this->ts = +$ts;
		$this->fs = +$fs;
		$this->charList = $charList;
		$this->link = '?add_strokes=' . urlencode($codepoint . " " . $ts . '|' . $fs);
	}
}
