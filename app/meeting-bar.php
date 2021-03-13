<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

header('Cache-Control: max-age=604800');

define('EVIDENCE_PATH', '../data');

if (isset($_POST['store']) && $session->isLoggedIn() && $session->getUser()->isAdmin()) {
	if (!preg_match('@^[a-z0-9-_.]+$@', $_POST['store'])) {
		throw new Exception('Invalid filename');
	}
	$data = substr($_POST['data'], strlen('data:image/png;base64,'));
	$data = base64_decode($data);
	file_put_contents('cache/' . $_POST['store'], $data);
	exit;
}

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

$user_id = isset($_GET['user']) ? intval($_GET['user']) : (!empty($session->getUser()) ? $session->getUser()->getUserId() : 0);
if ($user_id !== 0) {
	$user = IRGUser::getById($user_id);
	if (!$user) {
		throw Exception('$user unknown');
	}
} else {
	$user = null;
}


if (isset($_GET['version']) && DBVersions::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
	$max_session = DBCharacters::toSessionNumber($version);
} else {
	$version = Workbook::VERSION;
	$max_session = DBCharacters::toSessionNumber($version);
}

const SOURCES = [
	'G_SOURCE' => 8,
	'K_SOURCE' => 17,
	'UK_SOURCE' => 27,
	'SAT_SOURCE' => 38,
	'T_SOURCE' => 43,
	'UTC_SOURCE' => 50,
	'V_SOURCE' => 55,
];

$list = DBComments::getListAll($version);

const G_SOURCE     = 8;
const K_SOURCE     = 17;
const UK_SOURCE    = 27;
const SAT_SOURCE   = 38;
const T_SOURCE     = 43;
const UTC_SOURCE   = 50;
const V_SOURCE     = 55;

$type = array_map(function($cm) {
	return $cm->getTypeIndex();
}, $list);
$source1 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::G_SOURCE] ? $char->data[Workbook::G_SOURCE] : 'ZZZ';
}, $list);
$source2 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::K_SOURCE] ? $char->data[Workbook::K_SOURCE] : 'ZZZ';
}, $list);
$source3 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::SAT_SOURCE] ? $char->data[Workbook::SAT_SOURCE] : 'ZZZ';
}, $list);
$source4 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::T_SOURCE] ? $char->data[Workbook::T_SOURCE] : 'ZZZ';
}, $list);
$source5 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::UTC_SOURCE] ? $char->data[Workbook::UTC_SOURCE] : 'ZZZ';
}, $list);
$source6 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::UK_SOURCE] ? $char->data[Workbook::UK_SOURCE] : 'ZZZ';
}, $list);
$source7 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[Workbook::V_SOURCE] ? $char->data[Workbook::V_SOURCE] : 'ZZZ';
}, $list);
$date = array_map(function($cm) {
	return $cm->created_date;
}, $list);
array_multisort($type, $source1, $source2, $source3, $source4, $source5, $source6, $source7, $date, $list);

function getFriendlyTypeName($type) {
	$friendlyType = ucfirst(strtolower(strtr($type, '_', ' ')));
	if (strpos($friendlyType, 'Attributes') === 0) {
		$friendlyType = substr($friendlyType, strlen('Attributes') + 1);
		$friendlyType = strtoupper($friendlyType);
		if ($friendlyType === 'TRAD SIMP') {
			$friendlyType = 'Trad/Simp flag';
		}
		if ($friendlyType === 'RADICAL') {
			$friendlyType = 'Radical';
		}
		if ($friendlyType === 'SC') {
			$friendlyType = 'Residual Stroke Count';
		}
		if ($friendlyType === 'TC') {
			$friendlyType = 'Total Stroke Count';
		}
	}
	return $friendlyType;
}


$chunks = [];
foreach ($list as $item) {
	if (!$user && $item->type === 'LABEL') {
		continue;
	}
	if ($item->isDeleted()) {
		echo 'd';
		continue;
	}
	
	$category = $item->getCategoryForCommentType();
	if ($category === null) {
		echo 'c';
		continue;
	}

	if (!isset($chunks[$category])) {
		$chunks[$category] = [];
	}

	$chunks[$category][] = $item;
}

foreach ($chunks as $category => $chunk) {
	if (empty($chunk)) {
		continue;
	}

	if ($category === 'Labels') {
		continue;
	}

	echo '<h3>' . $category . '</h3>';
	$lastSN = null;
	foreach ($chunk as $i => $cm) {
		if ($cm->type === 'OTHER') {
			if (strpos(strtolower($cm->comment), '** note') !== false) {
				continue;
			}
			if (strpos(strtolower($cm->comment), 'private note') !== false) {
				continue;
			}
		}

		$sheet = $character_cache->get(sprintf('%05d', $cm->sn))->sheet;
		
		if ($lastSN != $cm->getSN()) {
			echo '<a href="index.php?id='.htmlspecialchars($cm->getSN()).'" target=_blank>';
			$char = $character_cache->get($cm->getSN(), $max_session);
			//$char->renderPart4();
			echo '<img src="../data' . WSCharacter::getFileName($char->getSources()).'" width=50 height=50>';
			echo htmlspecialchars($cm->getSN()).'</a>';
		}

		$lastSN = $cm->getSN();
	}
	echo '<hr>';
}
