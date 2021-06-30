<?php

class CharacterCache {

	const SHEETS = [
		'IRGN2270 Main Set',
		'IRGN2270 Unified & Withdrawn',
		'IRGN2270 Pending'
	];
	
	public static function hasVersion($version) {
		return DBVersions::hasVersion($version);
	}
	
	public static function getSheetName($version, $sheetIdx) {		
		if ($version === '1.1') {
			return [
				'IRGN2270WS2017v1.1 Working Set [DATA NOT AVAILALBE]',
				'IRGN2270WS2017v1.1 Unified & Withdrawn [DATA NOT AVAILALBE]',
				'IRGN2270WS2017v1.1 Pending [DATA NOT AVAILALBE]'
			][$sheetIdx];
		}

		$sheetName = [
			'Working Set',
			'Unified & Withdrawn',
			'Pending',
		];
		
		$entry = DBVersions::getVersion($version);
		if ($entry) {
			return $entry->name . 'WS2017v' . $version . ' ' . $sheetName[$sheetIdx];
		}

		throw new Exception("Unknown version!");
	}

	public function generate() {
		$workbook = loadWorkbook();
		$result = $this->generateForWorksheet($workbook, 0);
		$result = $this->generateForWorksheet($workbook, 1);
		$result = $this->generateForWorksheet($workbook, 2);
	}

	public function generateForWorksheet($workbook, int $sheet) {
		Log::add('Generate Cache for Worksheet ' . $sheet . ' Loop Data Start');
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

			$data        = new StdClass();
			$data->sheet = $sheet;
			$data->data  = $rowData;

			if (!file_exists('../data/attributes-cache/' . $sq_number . '.json')) {
				file_put_contents('../data/attributes-cache/' . $sq_number . '.json', json_encode($data));
			}
		}
		Log::add('Loop Data End');
		return null;
	}

	public function getAll($version = '4.0') {
		if (strcmp($version, '2.0') <= 0) {
			$files = glob('../data/attributes-cache-v2/*.json');
		} else {
			$files = glob('../data/attributes-cache/*.json');
		}
		$files = array_filter(array_map(function($filename) {
			$filename = basename($filename);
			$sq_number = @substr($filename, 0, 5);
			$ext = @substr($filename, 5);
			if (strlen($sq_number) !== 5 || !ctype_digit($sq_number) || $ext !== '.json') {
				return null;
			}
			return $sq_number;
		}, $files));
		$files = array_map(function($sq_number) use ($version) {
			return $this->getVersion($sq_number, $version);
		}, $files);
		return $files;
	}

	public function get($sq_number, $max_session = 99) {
		if ($max_session == 99) {
			$version = '4.0';
		} else if ($max_session == 50) {
			$version = '1.1';
		} else if ($max_session == 51) {
			$version = '2.0';
		} else if ($max_session == 52) {
			$version = '3.0';
		} else if ($max_session == 53) {
			$version = '4.0';
		} else if ($max_session == 54) {
			$version = '5.0';
		} else if ($max_session == 56) {
			$version = '5.2';
		} else if ($max_session == 57) {
			// 6.0 is originally for IRG 57
			$version = '6.1';
		} else {
			throw new Exception("Unknown session: " . $max_session);
		}

		return $this->getVersion($sq_number, $version);
	}

	public function getVersion($sq_number, $version) {
		if (!self::hasVersion($version)) {
			throw new Exception("Unknown version number: " . $version);
		}

		if (strcmp($version, '2.0') <= 0) {
			if (file_exists('../data/attributes-cache-v2/' . $sq_number . '.json')) {
				$result = json_decode(file_get_contents('../data/attributes-cache-v2/' . $sq_number . '.json'));
				return new WSCharacter($result, $version);
			}
			throw new Exception('Not Found');
		}

		if (file_exists('../data/attributes-cache/' . $sq_number . '.json')) {
			$result = json_decode(file_get_contents('../data/attributes-cache/' . $sq_number . '.json'));
			return new WSCharacter($result, $version);
		}

		throw new Exception('Not Found');
	}

	public function getColumns() {
		$json = file_get_contents('../data/attributes-cache/sheet.0.firstRow.json');
		return json_decode($json);
	}
}

