<?php

class Workbook {

	const VERSION = '2.0'; // WS2017 version
	
	// Indexes
	const DISCUSSION_RECORD = 1;

	// Indexes - Sources
	const SOURCE       = [8, 17, 27, 38, 43, 50, 55]; // Put SAT before UK since U column can only contain one char
	const G_SOURCE     = 8;
	const K_SOURCE     = 17;
	const UK_SOURCE    = 27;
	const SAT_SOURCE   = 38;
	const T_SOURCE     = 43;
	const UTC_SOURCE   = 50;
	const V_SOURCE     = 55;

	// Indexes - Attributes
	const RADICAL      = 2;
	const STROKE       = 3;
	const FS           = 4;
	const TS_FLAG      = 5;
	const IDS          = 6;
	const TOTAL_STROKE = 7;
	const SIMILAR      = [10, 19, 29, 40, 45, 52, 57];

	//const TOTAL_STROKES = [21, 22]; //, 27, 32, 35, 41, 43, 50];
	
	// Indexes - Extras
	const G_EVIDENCE = 15;
	const K_EVIDENCE = 24;
	const UK_EVIDENCE = 33;
	const SAT_EVIDENCE = 64; // Autogen
	const T_EVIDENCE = 48;
	const UTC_EVIDENCE = 65; // Autogen
	const V_EVIDENCE = 60;
	
	
	const G_EVIDENCE_NAME = [11, 16];
	const T_EVIDENCE_NAME = [46, 47];
	const K_EVIDENCE_NAME = [20, 21, 22, 23];
	const V_EVIDENCE_NAME = [59, 58];
	const UK_EVIDENCE_NAME = [31, 32];
	const UTC_EVIDENCE_NAME = [53, 54];
	const SAT_EVIDENCE_NAME = [41, 42];
	
	const K_ADDITIONAL_INFO = 25;

	const UK_TRAD_SIMP = 35;

	static function loadWorkbook() {
		Log::add('Load File Start');
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objReader->setReadDataOnly(true);
		$workbook = $objReader->load("../data/IRGN2309WS2017v2.0Attributes2018-07-09.xlsx");
		Log::add('Load File End');
		return $workbook;
	}
	static function getFields() {
		return [
			'G'   => [self::G_SOURCE,   self::G_EVIDENCE,   self::G_EVIDENCE_NAME],
			'K'   => [self::K_SOURCE,   self::K_EVIDENCE,   self::K_EVIDENCE_NAME, self::K_ADDITIONAL_INFO],
			'SAT' => [self::SAT_SOURCE, self::SAT_EVIDENCE, self::SAT_EVIDENCE_NAME],
			'T'   => [self::T_SOURCE,   self::T_EVIDENCE,   self::T_EVIDENCE_NAME],
			'UTC' => [self::UTC_SOURCE, self::UTC_EVIDENCE, self::UTC_EVIDENCE_NAME],
			'UK'  => [self::UK_SOURCE,  self::UK_EVIDENCE,  self::UK_EVIDENCE_NAME],
			'V'   => [self::V_SOURCE,   self::V_EVIDENCE,   self::V_EVIDENCE_NAME]
		];
	}
}
