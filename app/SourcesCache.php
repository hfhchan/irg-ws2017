<?php

class SourcesCache {

	private $sources = array();

	private function load() {
		if (empty($this->sources)) {
			$this->sources = json_decode(file_get_contents('../data/attributes-cache/sources.json'), true);
		}
	}

	public function getKeys() {
		return array_keys($this->sources);
	}

	public function getAll() {
		$this->load();
		$sn = [];
		foreach ($this->sources as $source) {
			array_push($sn, ...$source);
		}
		return $sn;
	}

	public function getFirst() {
		$this->load();
		foreach ($this->sources as $source => $value) {
			return $source;
		}
	}

	public function find(string $source) {
		$this->load();
		if (isset($this->sources[$source][0])) {
			return $this->sources[$source];
		}
		return null;
	}

	public function findPrev($source) {
		$this->load();
		$prev = null;
		foreach ($this->sources as $key => $value) {
			if ($source === $key) {
				return $prev;
			}
			$prev = $key;
		}
		return null;
	}

	public function findNext($source) {
		$this->load();
		$next = false;
		foreach ($this->sources as $key => $value) {
			if ($source === $key) {
				$next = true;
				continue;
			}
			if ($next) {
				return $key;
			}
		}
		return null;
	}

	public function generate() {
		$workbook = loadWorkbook();
		$this->generateForWorksheet($workbook, 0);
		$this->generateForWorksheet($workbook, 1);
		$this->generateForWorksheet($workbook, 2);
		ksort($this->sources);
		if (!file_exists('../data/attributes-cache/sources.json')) {
			file_put_contents('../data/attributes-cache/sources.json', json_encode($this->sources));
		}
	}

	private function add($sq_number, $source) {
		$sources = &$this->sources;
		if (!isset($sources[$source])) {
			$sources[$source] = array();
		}
		$sources[$source][] = $sq_number;
	}

	private function generateForWorksheet($workbook, int $sheet) {
		Log::add('Generate Source Cache for Worksheet ' . $sheet . ' Loop Data Start');
		$worksheet     = $workbook->getSheet($sheet);
		$highestRow    = $worksheet->getHighestRow(); 
		$highestColumn = $worksheet->getHighestColumn();

		$firstRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];
		if (!file_exists('../data/attributes-cache/sheet.'.$sheet.'.firstRow.json')) {
			file_put_contents('../data/attributes-cache/sheet.'.$sheet.'.firstRow.json', json_encode($firstRow));
		}

		for ($row = 2; $row <= $highestRow; $row++) {
			$rowData = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
			$sq_number = trim($rowData[0]);
			$sq_number = str_pad($sq_number, 5, '0', STR_PAD_LEFT);
			$rowData[0] = $sq_number;

			foreach (Workbook::SOURCE as $col) {
				if (!empty($rowData[$col] )) {
					$this->add($sq_number, $rowData[$col] );
				}
			}
		}
		Log::add('Loop Data End');
		return null;
	}
}
