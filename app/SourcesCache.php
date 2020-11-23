<?php

class SourcesCache {

	private $sources = array();

	private function load($version = '4.0') {
		if (empty($this->sources)) {
			if ($version === '2.0') {
				$this->sources = json_decode(file_get_contents('../data/attributes-cache-v2/sources.json'), true);
			} else {
				$this->sources = json_decode(file_get_contents('../data/attributes-cache/sources.json'), true);
			}
		}
	}

	public function getKeys($version = '4.0') {
		$this->load($version);
		return array_keys($this->sources);
	}

	public function getSources($version = '4.0') {
		$this->load($version);
		return $this->sources;
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
	
	public function getGroupBySourceRef($version) {		
		if (!CharacterCache::hasVersion($version)) {
			throw new Exception('Invalid $version');
		}

		$character_cache = new CharacterCache();

		$prefixReplacements = [
			'GDM' => 'China (GDM/GXM - 公安部治安管理局)',
			'GXM' => 'China (GDM/GXM - 公安部治安管理局)',
			'GHC' => 'China (GHC - 汉语大词典（第一版）)',
			'GKJ' => 'China (GKJ - 《中医字典》)',
			'GZ' => 'China (Zhuang Characters)',
			'GZA' => 'China (Zhuang Characters)',
			'GPGLG' => 'China (Zhuang Characters)',
			'GLGYJ' => 'China (Zhuang Characters)',
			'KC' => 'ROK',
			'T12' => 'TCA (《化學命名原則（第四版）》 Chemical Nomenclature: 4th Edition)',
			'T13' => 'TCA (MOE Dictionary)',
			'TB' => 'TCA (MOE Dictionary)',
			'TC' => 'TCA (MOE Dictionary)',
			'TD' => 'TCA (MOE Dictionary)',
			'TE' => 'TCA (MOE Dictionary)',
			'USAT' => 'SAT',
			'V' => 'Vietnam',
		];
		$groups = [];

		$keys = $this->getKeys($version);
		foreach ($keys as $key) {
			if ($key[0] === 'U' && $key[1] === 'S') {
				$prefix = 'USAT';
			}
			if (strpos($key, '-')) {
				list($prefix, $junk) = explode('-', $key);
			}
			if (isset($prefixReplacements[$prefix])) {
				$prefix = $prefixReplacements[$prefix];
			}
			if ($key === 'TB-6231') {
				$prefix = 'TCA (《化學命名原則（第四版）》 Chemical Nomenclature: 4th Edition)';
			}
			if ($key === 'TB-6B25') {
				$prefix = 'TCA (《化學命名原則（第四版）》 Chemical Nomenclature: 4th Edition)';
			}
			if ($key === 'TB-7D55') {
				$prefix = 'TCA (《化學命名原則（第四版）》 Chemical Nomenclature: 4th Edition)';
			}
			if ($key === 'TC-4162') {
				$prefix = 'TCA (《化學命名原則（第四版）》 Chemical Nomenclature: 4th Edition)';
			}
			if ($key === 'TC-6635') {
				$prefix = 'TCA (《化學命名原則（第四版）》 Chemical Nomenclature: 4th Edition)';
			}
			if (!isset($groups[$prefix])) {
				$groups[$prefix] = [];
			}
			$groups[$prefix][] = $key;
		}
		
		ksort($groups);
		
		return $groups;
	}
}
