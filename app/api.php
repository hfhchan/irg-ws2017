<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

if (!$session->isLoggedIn()) {
	die('Require Login');
}

if (!isset($_GET['action'])) {
?>
<meta charset=utf-8>
<h1>API Example</h1>
<pre>https://hc.jsecs.org/irg/ws2017/app/api.php?action=index&amp;version=4.0</pre>
<pre>https://hc.jsecs.org/irg/ws2017/app/api.php?action=sources&amp;version=4.0</pre>

<pre>https://hc.jsecs.org/irg/ws2017/app/api.php?action=rawdata&amp;sn=01282&amp;version=3.0 (will deprecate)</pre>
<pre>https://hc.jsecs.org/irg/ws2017/app/api.php?action=character&amp;sn=01282&amp;version=3.0 (new)</pre>
<pre>https://hc.jsecs.org/irg/ws2017/app/api.php?action=source&amp;source=GDM-00068&amp;version=3.0 (to be added)</pre>
<pre>https://hc.jsecs.org/irg/ws2017/app/api.php?action=ids&amp;sn=01285&amp;version=3.0 (new)</pre>
<?
	exit;
}

$action = $_GET['action'];

if ($action == 'index') {
	if (!isset($_GET['version'])) {
		throw new Exception('Missing $version');
	}

	$version = $_GET['version'];
	
	$status_cache = new StatusCache();
	$status = $status_cache->getGroupBySerial($version);

	header('Content-Type: application/json');
	echo json_encode($status);
	exit;
}

if ($action == 'sources') {
	if (!isset($_GET['version'])) {
		throw new Exception('Missing $version');
	}

	$version = $_GET['version'];
	
	$status_cache = new StatusCache();
	$souces_cache = new SourcesCache();
	
	$status = $status_cache->getGroupBySourceReference($version);
	$groups = $souces_cache->getGroupBySourceRef($version);

	header('Content-Type: application/json');
	echo json_encode([
		'groups' => $groups,
		'status' => $status
	]);
	exit;
}

if ($action == 'rawdata') {
	if (!isset($_GET['sn']) || !isset($_GET['version'])) {
		throw new Exception('Missing $sn and $version');
	}
	$character_cache = new CharacterCache();
	$char = $character_cache->getVersion($_GET['sn'], $_GET['version']);
	header('Content-Type: application/json');
	echo json_encode($char);
	exit;
}

if ($action == 'character') {
	if (!isset($_GET['sn']) || !isset($_GET['version'])) {
		throw new Exception('Missing $sn and $version');
	}
	$char = DBCharacters::getCharacter($_GET['sn'], $_GET['version']);
	$char->discussion_record = $char->getDiscussionRecord();
	header('Content-Type: application/json');
	echo json_encode($char);
	exit;
}

if ($action == 'ids') {
	if (!isset($_GET['sn']) || !isset($_GET['version'])) {
		throw new Exception('Missing $sn and $version');
	}
	$char = DBCharacters::getCharacter($_GET['sn'], $_GET['version']);
	$idsProcessor = new IDSProcessor($char->ids, $char->radical, $char->trad_simp_flag);
	$results = $idsProcessor->getResults();
	list($stroke_count, $total_count, $first_stroke, $radical_found) = $idsProcessor->getCounts();

	header('Content-Type: application/json');
	echo json_encode([
		'result' => $results,
		'radical_found' => $radical_found,
		'predicted_stroke_count' => $radical_found ? $stroke_count : -1,
		'predicted_first_stroke' => $radical_found ? $first_stroke : -1,
		'predicted_total_count' => $total_count,

		'current_stroke_count' => +$char->stroke_count,
		'current_first_stroke' => +$char->first_stroke,
		'current_total_count' => +$char->total_stroke_count,
	]);
	exit;
}

throw new Exception("Unknown action, supported action = rawdata, character, ids; ");