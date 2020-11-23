<?php
declare(strict_types=1);

//const NEWEST_IRG = 54;
//const PREV_VERSION = '5.0';
//const NEXT_VERSION = '5.1';
const NEWEST_IRG = 53;
const PREV_VERSION = '4.0';
const NEXT_VERSION = '5.0';

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

define('EVIDENCE_PATH', '../data');

if ($session->isLoggedIn() && $session->getUser()->isAdmin()) {
	if (isset($_POST['action']) && $_POST['action'] == 'change') {
		if (!isset($_POST['value']) || trim($_POST['value']) === '') {
			echo 'Value cannot be empty';
			exit;
		}
		if (empty($_POST['discussion_record_id'])) {
			DBChanges::add(
				null,
				$_POST['sn'],
				$_POST['type'],
				trim($_POST['value']),
				PREV_VERSION,
				NEXT_VERSION
			);
			header('Location: apply-changes.php#action-form');
			exit;
		}
		DBChanges::add(
			$_POST['discussion_record_id'],
			$_POST['sn'],
			$_POST['type'],
			trim($_POST['value']),
			PREV_VERSION,
			NEXT_VERSION
		);
		header('Location: apply-changes.php#row-' . $_POST['discussion_record_id']);
		exit;
	}
}

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

$irg = NEWEST_IRG;

if ($irg < 52) {
	throw new Exception('Supported IRG session is 52 or above.');
}

$version1 = PREV_VERSION;
$version2 = NEXT_VERSION;

$this_session = $irg;

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1100">
<title>Apply Changes for Discussion Record | WS2017</title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
blockquote{margin:10px 0;padding:0;padding-left:10px;border-left:3px solid #ccc}
hr{border:none;border-top:1px solid #999}
h2{font-size:16px;margin:10px 0 5px}
form{margin:0}

.ws2017_header{background:#eee;padding:10px;border-bottom:1px solid #333}
.ws2017_header .center_wrap{max-width:387mm}

.footer{width:1200px;margin:20px auto}

.ws2017_cutting>img,.ws2017_cutting>canvas{width:auto!important;height:auto!important;max-width:100%}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.content{max-width:387mm;padding:10px;margin:0 auto}
.content table{border-collapse:collapse;width:100%}
.content td,.content th{border:1px solid #000;padding:10px}
.content td{vertical-align:top}
.content th{text-align:left}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}

.pdam2_2 img{max-width:100%}

@media print {
	.table-add-change{display:none}
}
@media screen {
	.content{margin-top:40px}
}

.ws2017_chart_table_discussion{display:grid;align-content:center;text-align:left!important;padding:8px;overflow:auto}
</style>
<script src="jquery.js"></script>
<body>
<?
define("DISCUSSION_RECORD", 1);
require_once 'index.searchbar.php';
?>
<section class=content>
	<h1>Apply Changes for Discussion Record</h1>
	<p>
		<b>IRG Meeting #<?=$irg?></b> (<?=DBMeeting::getLocation($irg . '')?>)</br>
		Discussion Record (Timezone offset: <?=DBMeeting::getOffset($irg . '')?>)<br>
		Retrieved on <?=date("Y-m-d H:i O")?>
	</p>

<?php

date_default_timezone_set('UTC');

$list = DBDiscussionRecord::getList($irg);
$dates = [];
foreach ($list as $cm) {
	$dates[$cm->getSN()][] = $cm;
}

const G_SOURCE     = 7;
const K_SOURCE     = 16;
const UK_SOURCE    = 26;
const SAT_SOURCE   = 37;
const T_SOURCE     = 42;
const UTC_SOURCE   = 49;
const V_SOURCE     = 54;

foreach ($dates as $date => $list) {
	echo '<h2>' . $date . '</h2>';

	echo '<table style="table-layout:fixed;width:100%" border=1 cellpadding=10>';
	echo '<col width=100>';
	echo '<col width=300>';
	echo '<col width=320>';
	echo '<col>';
	echo '<thead><tr><th>Time</th><!--th>Sn</th--><th>Image/Source</th>';
	echo '<th>Discussion Record</th>';
	echo '<th>Related Changes</th>';
	echo '</tr></thead>';

	foreach ($list as $cm) {
		echo '<tr id="row-'.$cm->id.'">';
		echo '<td style="font-size:13px">';
		echo $cm->toLocalDate();
		echo '<br>';
		echo $cm->toLocalTime();
		echo '<br>';
		echo '#' . $cm->id;
		echo '</td>';
		echo '<!--td><b><a href="index.php?id='.htmlspecialchars($cm->getSN()).'" target=_blank>'.htmlspecialchars($cm->getSN()).'</a></b></td-->';
		echo '<td>';
		$char = $character_cache->get($cm->getSN(), $this_session);
		$char->renderPart4();
		if ($char->data[1]) {
			echo '<blockquote>';
			$char->renderPart3();
			echo '</blockquote>';
		}
		echo '</td>';
		echo '<td>';

		preg_match_all("/(unify|unified|Unify|Unified) to (([\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}])|(WS(2015|2017))-([0-9]{5}))/u", $cm->value, $matches);
		foreach ($matches[2] as $i => $match) {
			if (!empty($matches[3][$i])) {
				$codepoint = charToCodepoint($match);
				echo getImageHTML($codepoint);
			}
			if (!empty($matches[4][$i])) {
				$year = $matches[5][$i];
				$sn = $matches[6][$i];
				if ($year === '2017') {
					$ref_char = $character_cache->get($matches[6][$i], $this_session);
					$ref_char->renderPart4();
				} else {
					$url = 'https://hc.jsecs.org/irg/ws'.$year.'/app/cache/canvas'.$sn.'ws'.$year.'_cutting.png';
					echo '<a href="https://hc.jsecs.org/irg/ws'.$year.'/app/?id='.$sn.'"><img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($match).'" style="max-width:100%"></a><br>';
				}
			}
		}

		if (preg_match('@(unify|unified|Unify|Unified) to U\+([0-9A-F]+)@', $cm->value, $matches)) {
			$codepoint = 'U+' . $matches[2];
			echo getImageHTML($codepoint);
		}

		if (preg_match('@(unify|unified|Unify|Unified) to ([0-9]{5})@', $cm->value, $matches)) {
			$sn = $matches[2];
			$ref_char = $character_cache->get($sn, $this_session);
			$ref_char->renderPart4();
		}

		$text = nl2br(htmlspecialchars($cm->value));
		$text = preg_replace('@{?{(([0-9]){5}-jpy-unification\\.png)}}?@', '<img src="../comments/jpy/\\1" style="max-width:100%">', $text);
		$text = preg_replace('@{?{(([0-9]){5}-([0-9a-f]){3,64}\\.png)}}?@', '<img src="../comments/\\1" style="max-width:100%">', $text);
		$text = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}@', function ($m) {
			$codepoint = $m[1];
			if (!env::$readonly) {
				return getImageHTML($codepoint);
			} else {
				return '';
			}
		}, $text);
		$text = preg_replace_callback('@{{WS2015-(([0-9]){5})}}@', function ($m) use ($character_cache) {
			return
				'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting1.png" style="max-width:100%">' .
				'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting2.png" style="max-width:100%">';
		}, $text);
		$text = preg_replace_callback('@{{WS2017-(([0-9]){5})}}@', function ($m) use ($character_cache) {
			$char = $character_cache->get($m[1], $this_session);
			ob_start();
			echo '<a href="?id=' . $m[1] . '" target=_blank>';
			$char->renderPart4();
			if ($char->data[1]) {
				echo '<blockquote>';
				$char->renderPart3();
				echo '</blockquote>';
			}
			echo '</a>';
			return ob_get_clean();
		}, $text);
		$text = preg_replace_callback('@{{(([0-9]){5})}}@', function ($m) use ($character_cache) {
			$char = $character_cache->get($m[1], $this_session);
			ob_start();
			echo '<a href="?id=' . $m[1] . '" target=_blank>';
			$char->renderPart4();
			if ($char->data[1]) {
				echo '<blockquote>';
				$char->renderPart3();
				echo '</blockquote>';
			}
			echo '</a>';
			return ob_get_clean();
		}, $text);
		echo '<div>' . $text . '</div>';
		
		echo '</td>';
		
		echo '<td>';
		
		if ($session->isLoggedIn() && $session->getUser()->isAdmin() && $session->getUser()->getUserId() < 3 && $irg == NEWEST_IRG) {
?>

<form method=post action="apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change (<?=$version1?> → <?=$version2?>)</b>: 
	<input type=hidden name=discussion_record_id value="<?=$cm->id?>">
	<input name=sn value="<?=$cm->sn?>" style="width:5ch">
	<select name=type>		
		<option value="Status">Status</option>
		<option value="Radical">Radical</option>
		<option value="Stroke Count">Stroke Count</option>
		<option value="First Stroke">First Stroke</option>
		<option value="Total Stroke Count">Total Stroke Count</option>
		<option value="IDS">IDS</option>
		<option value="Trad/Simp Flag">Trad/Simp Flag</option>
		<option value="Discussion Record">Discussion Record</option>
	</select>
	<input name=value>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit>
	<div><small>For radical, input e.g. 103 (for trad), 103.1 (for simp).</small></div>
	<div><small>For Status, input Unified / Withdrawn / Postponed / Not Unified / Disunified / OK</small></div>
</form>
<?
		}
		
		$changesList = DBChanges::getChangesForDiscussionRecord($cm->id);
		$changesMap = [];
		$discussionRecordChanges = [];
		foreach ($changesList as $change) {
			if ($change->type === 'Discussion Record') {
				$discussionRecordChanges[$change->sn] = true;
			}
			$changesMap[$change->sn][] = $change;
		}

		foreach ($changesList as $change) {
			echo '<div>';
			echo $change->getDescription();
			echo '</div>';
		}

		foreach ($changesMap as $sn => $changes) {
			$char = DBCharacters::getCharacter(sprintf('%05d', $sn), $changes[0]->version1);
			echo '<table style="margin-top:3px;table-layout:fixed">';
			echo '<thead><tr><th style="text-align:center">' . $changes[0]->version1 . '</th><td style="width:16px"></td><th style="text-align:center">' . $changes[0]->version2 . '</th></tr></thead>';
			echo '<tr><td>';
			$char->renderPart4();
			if (isset($discussionRecordChanges[$sn])) {
				echo '<blockquote>';
				$char->renderPart3();
				echo '</blockquote>';
			}
			echo '</td><td style="width:0">';
			echo '&#x1f87a';
			echo '</td><td>';
			$char->applyChanges($changes);
			$char->renderPart4();
			if (isset($discussionRecordChanges[$sn])) {
				echo '<blockquote>';
				$char->renderPart3();
				echo '</blockquote>';
			}
			echo '</td></tr>';
			echo '</table>';
		}
	
		if (empty($changesList)) {
			echo 'No change / Not applicable';
		}
		
		echo '</td>';
		echo '</tr>' . "\n";
	}
	echo '</table>' . "\n";
	echo '<br>' . "\n";
}
echo '<br>';

?>
<hr>
<h2>Other Changes</h2>

<?php

echo '<table style="table-layout:fixed;width:100%" border=1 cellpadding=10>';
echo '<col width=100>';
echo '<col width=300>';
echo '<col width=320>';
echo '<col>';
echo '<thead><tr><th>Time</th><!--th>Sn</th--><th>Image/Source</th>';
echo '<th>Discussion Record</th><th>Changes</th></tr></thead>';

$changesList = DBChanges::getOrphanedChanges($version1);
$changesMap = [];
$discussionRecordChanges = [];
foreach ($changesList as $change) {
	$changesMap[$change->getSN()][] = $change;
	if ($change->type === 'Discussion Record') {
		$discussionRecordChanges[$change->getSN()] = true;
	}
}

foreach ($changesMap as $changes) {
	$sn = $changes[0]->getSN();
	echo '<tr id="change-'.$changes[0]->id.'">';
	echo '<td style="font-size:13px">';
	echo '-';
	echo ' (Change Record ';
	foreach ($changes as $change) {
		echo $change->id . ' ';
	}
	echo ')';
	echo '</td>';
	echo '<!--td><b><a href="index.php?id='.htmlspecialchars($sn).'" target=_blank>'.htmlspecialchars($sn).'</a></b></td-->';
	echo '<td>';
	$char = DBCharacters::getCharacter($sn, PREV_VERSION);
	$char->renderPart4();
	echo '</td>';
	echo '<td> - </td>';
	echo '<td>';

	foreach ($changes as $change) {
		echo '<div>';
		echo $change->getDescription();
		echo '</div>';
	}

	$char = DBCharacters::getCharacter($sn, PREV_VERSION);
	echo '<table style="margin-top:3px;table-layout:fixed">';
	echo '<thead><tr><th style="text-align:center">v' . PREV_VERSION . '</th><td style="width:16px"></td><th style="text-align:center">' . NEXT_VERSION . '</th></tr></thead>';
	echo '<tr><td>';
	$char->renderPart4();
	if (isset($discussionRecordChanges[$sn])) {
		echo '<blockquote>';
		$char->renderPart3();
		echo '</blockquote>';
	}
	echo '</td><td style="width:0">';
	echo '&#x1f87a';
	echo '</td><td>';
	$char->applyChanges($changes);
	$char->renderPart4();
	if (isset($discussionRecordChanges[$sn])) {
		echo '<blockquote>';
		$char->renderPart3();
		echo '</blockquote>';
	}
	echo '</td></tr>';
	echo '</table>';

	echo '</td></tr>';
}

echo "</table>";
?>

<?
if ($session->isLoggedIn() && $session->getUser()->isAdmin() && $session->getUser()->getUserId() < 3 && $irg == NEWEST_IRG) {
?>

<form method=post action="apply-changes.php" style="margin-bottom:10px" class="table-add-change" id="action-form">
	<b>Add Change (<?=$version1?> → <?=$version2?>)</b>: 
	<input name=sn value="" required placeholder="SN">
	<select name=type>		
		<option value="Status">Status</option>
		<option value="Radical">Radical</option>
		<option value="Stroke Count">Stroke Count</option>
		<option value="First Stroke">First Stroke</option>
		<option value="Total Stroke Count">Total Stroke Count</option>
		<option value="IDS">IDS</option>
		<option value="Trad/Simp Flag">Trad/Simp Flag</option>
		<option value="Discussion Record">Discussion Record</option>
	</select>
	<input name=value>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit>
	<div><small>For radical, input e.g. 103 (for trad), 103.1 (for simp).</small></div>
	<div><small>For Status, input Unified / Withdrawn / Postponed / Not Unified / Disunified / OK</small></div>
</form>
<?
}
?>
</section>
