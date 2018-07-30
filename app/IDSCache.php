<?php

class IDSCache {

	private $ids = array();

	private function load() {
		if (empty($this->ids)) {
			$this->ids = json_decode(file_get_contents('../data/attributes-cache/ids.json'), true);
		}
	}

	public function find(string $ids_sequence) {
		$char = parseStringIntoCodepointArray($ids_sequence);
		$char = array_map(function($char) {
			if ($char[0] === 'U' && $char[1] === '+') {
				return codepointToChar($char);
			}
			return $char;
		}, $char);
		$results = [];
		$this->load();
		foreach ($this->ids as $key => $value) {
			foreach ($char as $i) {
				if (strpos($key, $i) === false) {
					continue 2;
				}
			}
			$results = array_merge($results, $this->ids[$key]);
		}
		return $results;
	}

	public function generate() {
		$workbook = loadWorkbook();
		$this->generateForWorksheet($workbook, 0);
		$this->generateForWorksheet($workbook, 1);
		$this->generateForWorksheet($workbook, 2);
		ksort($this->ids);
		if (!file_exists('../data/attributes-cache/ids.json')) {
			file_put_contents('../data/attributes-cache/ids.json', json_encode($this->ids));
		}
	}

	private function add($sq_number, $ids_sequence) {
		$ids = &$this->ids;
		if (!isset($ids[$ids_sequence])) {
			$ids[$ids_sequence] = array();
		}
		$ids[$ids_sequence][] = $sq_number;
	}

	private function generateForWorksheet($workbook, int $sheet) {
		Log::add('Generate IDS Cache for Worksheet ' . $sheet . ' Loop Data Start');
		$worksheet     = $workbook->getSheet($sheet);
		$highestRow    = $worksheet->getHighestRow(); 
		$highestColumn = $worksheet->getHighestColumn();

		$firstRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];
		if (!file_exists('../data/attributes-cache/sheet.'.$sheet.'.firstRow.json')) {
			file_put_contents('../data/attributes-cache/sheet.'.$sheet.'.firstRow.json', json_encode($firstRow));
		}

		for ($row = 2; $row <= $highestRow; $row++) {
			$rowData    = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
			$sq_number  = trim($rowData[0]);
			$sq_number  = str_pad($sq_number, 5, "0", STR_PAD_LEFT);
			$rowData[0] = $sq_number;

			if (!empty($rowData[Workbook::IDS] )) { 
				$this->add($sq_number, $rowData[Workbook::IDS]);
			}
		}
		Log::add('Loop Data End');
		return null;
	}
}
