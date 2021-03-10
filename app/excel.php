<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

Log::disable();

if (!$session->isLoggedIn() || !$session->getUser()->isAdmin()) {
	$e = new FatalException('Requires admin permission / not logged in.');
	$e->title('Export data to excel | WS2017');
	$e->allowLogin(true);
	throw $e;
}

if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
}

$character_cache = new CharacterCache();
$chars = $character_cache->getAll(Workbook::VERSION);

$chars = array_map(function($char) use ($version) {
	return DBCharacters::getCharacter($char->data[0], $version);
}, $chars);

usort($chars, function($a, $b) {
	$c = $a->getRadicalStrokeFull();
	$d = $b->getRadicalStrokeFull();
	if ($c === $d) {
		return strcmp($a->ids, $b->ids);
	}
	return strnatcmp($c, $d);
});

$sheets = [
	0 => [],
	1 => [],
	2 => [],
];

foreach ($chars as $char) {
	$sheets[$char->status][] = $char;
} unset($chars);

$objPHPExcel = new PHPExcel(); 

foreach ($sheets as $sheet_number => $chars) {
	$sheet_name = 'WorkingSet';
	if ($sheet_number == 1) {
		$sheet_name = 'Unified&Withdrawn';
	}
	if ($sheet_number == 2) {
		$sheet_name = 'Pending';
	}

	$objPHPExcel->createSheet(NULL, $sheet_number + 1);
	$objPHPExcel->setActiveSheetIndex($sheet_number + 1); 
	$objPHPExcel->getActiveSheet()->setTitle($sheet_name);

	$rowCount = 1; 
	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, 'Sn');
	$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, 'Discussion Record');
	$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, 'Radical');
	$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, 'Stroke Count');
	$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, 'First Stroke');
	$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, 'T/S Flag');
	$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, 'IDS');
	$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, 'Total Stroke Count');
	$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, 'G Source');
	$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, 'K Source');
	$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, 'UK Source');
	$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, 'SAT Source');
	$objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowCount, 'T Source');
	$objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowCount, 'UTC Source');
	$objPHPExcel->getActiveSheet()->SetCellValue('O'.$rowCount, 'V Source');

	foreach ($chars as $char) {
		$rowCount++;
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $char->sn);
		$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $char->discussion_record);
		$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $char->radical);
		$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $char->stroke_count);
		$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $char->first_stroke);
		$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $char->trad_simp_flag);
		$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $char->ids);

		$total_stroke_count = str_replace('22,21', '22', $char->total_stroke_count);
		$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $total_stroke_count);
		$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $char->g_source);
		$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, $char->k_source);
		$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, $char->uk_source);
		$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, $char->sat_source);
		$objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowCount, $char->t_source);
		$objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowCount, $char->utc_source);

		$v_source = strcmp($version, '5.1') >= 0 ? vSourceFixup($char->v_source) : $char->v_source;
		$objPHPExcel->getActiveSheet()->SetCellValue('O'.$rowCount, $v_source);
	} 

}

$objPHPExcel->removeSheetByIndex(0);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="IRGWS2017v' . $version . 'consolidated.xlsx"');
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
$objWriter->save('php://output');
