<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

define('EVIDENCE_PATH', '../data');

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

if (isset($_GET['irg'])) {
	$irg = intval($_GET['irg']);
} else {
	$irg = 51;
}

if ($irg != 50 && $irg != 51) {
	throw new Exception('Supported IRG session is 50 or 51 only.');
}

$this_session = $irg;

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1100">
<title>Discussion Record (Trial) | WS2017</title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
hr{border:none;border-top:1px solid #999}
h2{font-size:16px;margin:10px 0 5px}
form{margin:0}

.ws2017_header{background:#eee;padding:10px;border-bottom:1px solid #333}
.ws2017_header .center_wrap{max-width:387mm}

.footer{width:1200px;margin:20px auto}

.ws2017_cutting>img,.ws2017_cutting>canvas{width:auto!important;height:auto!important;max-width:100%}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.ws2017_comments{max-width:297mm;padding:10px;margin:0 auto}
.ws2017_comments table{border-collapse:collapse;width:100%}
.ws2017_comments td,.ws2017_comments th{border:1px solid #000;padding:10px}
.ws2017_comments td{vertical-align:top}
.ws2017_comments th{text-align:left}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}

.sheet-1{background:#ccc}
.sheet-2{background:#ff0}

.pdam2_2 img{max-width:100%}

@media print {
	.table-add-change{display:none}
}
</style>
<script src="jquery.js"></script>
<body>
<?
define("DISCUSSION_RECORD", 1);
require_once 'index.searchbar.php';
?>
<section class=ws2017_header>
<div class=center_wrap>
	<div>Show Discussion Record for:</div>
<?

$sessions = DBDiscussionRecord::getSessions();
foreach ($sessions as $_session) {
	$meeting = DBMeeting::getMeeting($_session);
	echo '<div>';
	echo '<a href="' . ($_session >= 52 ? 'discussion-record.php' : 'actions-trial.php') . '?irg=' . urlencode($_session) . '">';
	if ($_session == $irg) {
		echo '<b>';
	}
	echo 'IRG #' . htmlspecialchars($_session) . ' (' . $meeting->location . ')';
	if ($_session == $irg) {
		echo '</b>';
	}
	echo '</a>';
	if (!empty($meeting->comment)) echo ' ' . $meeting->comment;
	echo '</div>';
}
?>
</div>
</section>

<section class=ws2017_comments>
	<p>
		<b>IRG Meeting #<?=$irg?></b> (<?=DBMeeting::getLocation($irg . '')?>)</br>
		Discussion Record (Timezone offset: <?=DBMeeting::getOffset($irg . '')?>)<br>
		Retrieved on <?=date("Y-m-d H:i O")?>
	</p>
	<p>
		<b>Note 1</b>: Highlighting is based on final status <i>after applying all decisions</i> made in IRG Meeting #<?=$irg?>.  Rows in YELLOW are characters moved to the Pending set and rows in GRAY are characters moved to the Unified&amp;Withdrawn Set. Source references and attributes shown reflect values before the meeting.
	</p>
<?
if ($irg == 51) {
?>
	<p>
		<b>Note 2</b>: As a result of unconcluded debate of the issue "change NUCV Rule #194 攴/攵 to UCV" near the end of the meeting, several characters which were originally concluded unified had their status changed to POSTPONE after the meeting.  Such characters are listed at the end of the discussion record.  The transliteration characters submitted by UK, due to submittor's agreement to unify, have not been kept as is (unified) and not changed to postponed.
	</p>
	<p>
		<b>Note 3</b>: As a result of IRG discussion, the characters with Clerical script evidence and epigraphic characters are to be POSTPONED for other evidence and/or further investigation.  The characters from specific versions of 《一切經音義》 are also POSTPONED for other evidence. (Please refer to IRGN2328.) In this discussion record, only characters <i>that had been discussed</i> have their status changed.
	</p>
<?
}
?>

<?php

date_default_timezone_set('UTC');

$list = DBDiscussionRecord::getList($irg);
$dates = [];
foreach ($list as $cm) {
	$dates[$cm->toLocalDate()][] = $cm;
}

const G_SOURCE     = 7;
const K_SOURCE     = 16;
const UK_SOURCE    = 26;
const SAT_SOURCE   = 37;
const T_SOURCE     = 42;
const UTC_SOURCE   = 49;
const V_SOURCE     = 54;

foreach ($dates as $date => $list) {
	if ($date === '2018-10-27 (Sat)') {
		$showTime = false;
		echo '<h2>Appendix I</h2>';
		echo '<div style="margin-bottom:5px">(see note 2 - added 2018-10-27 (Sat))</div>';
	} else if ($date === '2018-11-02 (Fri)') {
		$showTime = false;
		echo '<h2>Appendix II</h2>';
		echo '<div style="margin-bottom:5px">(addendum - added 2018-11-02 (Fri))</div>';
	} else {
		$showTime = true;
		echo '<h2>' . $date . '</h2>';
	}
	echo '<table style="table-layout:fixed;width:100%" border=1 cellpadding=10>';
	echo '<col width=100>';
	echo '<!--col width=80-->';
	echo '<col width=320>';
	echo '<col width=200>';
	echo '<col>';
	echo '<thead><tr><th>Time</th><!--th>Sn</th--><th>Image/Source</th>';
	echo '<th>Type</th>';
	echo '<th>Discussion Record</th>';
	echo '</tr></thead>';
	foreach ($list as $cm) {
		
		// Highlighting is based on final value
		$char = $character_cache->get($cm->getSN(), $this_session);
		$char->applyChangesFromDiscussionRecord($this_session);
		$sheet = $char->sheet;

		echo '<tr class=sheet-'.$sheet.' id="row-'.$cm->id.'">';

		echo '<td style="font-size:13px" title="Record ' . $cm->id . '">';
		if ($showTime)
			echo $cm->toLocalTime();
		else
			echo '-';
		echo '</td>';
		echo '<!--td><b><a href="index.php?id='.htmlspecialchars($cm->getSN()).'" target=_blank>'.htmlspecialchars($cm->getSN()).'</a></b></td-->';
		echo '<td>';
		$char = $character_cache->get($cm->getSN(), $this_session);
		$char->renderPart4();
		echo '</td>';

		echo '<td><b>';
		echo htmlspecialchars($cm->type);
		echo '</b></td>';

		echo '<td>';

		if ($cm->isUnification()) {
			$cm->value = preg_replace_callback('@ to U\+([0-9A-F]{4,5})@', function ($m) {
				return ' to ' . codepointToChar('U+' . $m[1]) . ' (U+' . $m[1] . ')';
			}, $cm->value);
				
			$pos1 = strpos($cm->value, "\n");
			if ($pos1 === false) {
				$str = $cm->value;
			} else {
				$str = substr($cm->value, 0, $pos1);
			}
			$pos2 = strpos($str, ';');
			if ($pos2) {
				$str = substr($str, 0, $pos2);
			}
			$str = ' ' . trim($str);
			preg_match_all("/ (([\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}])|(WS(2015|2017))-([0-9]{5}))/u", $str, $matches);
			foreach ($matches[1] as $i => $match) {
				if (!empty($matches[2][$i])) {
					$codepoint = charToCodepoint($match);
					echo getImageHTML($codepoint);
				}
				if (!empty($matches[3][$i])) {
					$year = $matches[4][$i];
					$sn = $matches[5][$i];
					if ($year === '2017') {
						$ref_char = $character_cache->get($matches[5][$i], $this_session);
						$ref_char->renderPart4();
					} else {
						$url = 'https://hc.jsecs.org/irg/ws'.$year.'/app/cache/canvas'.$sn.'ws'.$year.'_cutting.png';
						echo '<a href="index.php?id='.$sn.'"><img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($match).'" style="max-width:100%"></a><br>';
					}
				}
			}

			if (preg_match('@^U\+([0-9A-F]+)$@', $cm->value)) {
				$codepoint = $cm->value;
				echo getImageHTML($codepoint);
			}

			if (preg_match('@^[0-9]{5}$@', $cm->value)) {
				$sn = $cm->value;
				$ref_char = $character_cache->get($sn, $this_session);
				$ref_char->renderPart4();
			}
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
				$char->renderPart3();
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
				$char->renderPart3();
			}
			echo '</a>';
			return ob_get_clean();
		}, $text);
		echo '<div>' . $text . '</div>';
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '<br>';
}
echo '<br>';

?>
<? if ($this_session == 52) { ?>
<hr>
<h2>Other Changes</h2>

<?php

echo '<table style="table-layout:fixed;width:100%" border=1 cellpadding=10>';
echo '<col width=80>';
echo '<!--col width=80-->';
echo '<col width=280>';
echo '<col width=300>';
//echo '<col width=400>';
echo '<thead><tr><th>Time</th><!--th>Sn</th--><th>Image/Source</th>';
echo '<th>Discussion Record</th><th>Changes</th></tr></thead>';

$changesList = DBChanges::getOrphanedChanges(3.0);
$changesMap = [];
foreach ($changesList as $change) {
	$changesMap[$change->getSN()][] = $change;
}

foreach ($changesMap as $changes) {
	echo '<tr id="change-'.$changes[0]->id.'">';
	echo '<td style="font-size:13px">';
	echo '-';
	echo ' (Change Record ';
	foreach ($changes as $change) {
		echo $change->id . ' ';
	}
	echo ')';
	echo '</td>';
	echo '<!--td><b><a href="index.php?id='.htmlspecialchars($changes[0]->getSN()).'" target=_blank>'.htmlspecialchars($changes[0]->getSN()).'</a></b></td-->';
	echo '<td>';
	$char = $character_cache->get($changes[0]->getSN(), $this_session);
	$char->renderPart4();
	echo '</td>';
	echo '<td> - </td>';
	echo '<td>';

	foreach ($changes as $change) {
		echo '<div>';
		echo $change->getDescription();
		echo '</div>';
	}

	$char = $character_cache->getVersion($changes[0]->getSN(), $changes[0]->version1);
	echo '<table style="margin-top:3px">';
	echo '<thead><tr><th style="text-align:center">' . $changes[0]->version1 . '</th><td></td><th style="text-align:center">' . $changes[0]->version2 . '</th></tr></thead>';
	echo '<tr><td>';
	$char->renderPart4();
	echo '</td><td style="width:0">';
	echo '&#x1f87a';
	echo '</td><td>';
	$char->applyChanges($changes);
	$char->renderPart4();
	echo '</td></tr>';
	echo '</table>';

	echo '</td></tr>';
}

echo "</table>";
?>

<?
if ($session->isLoggedIn() && $session->getUser()->isAdmin() && $session->getUser()->getUserId() < 3) {
?>

<form method=post action="actions.php" style="margin-bottom:10px" class="table-add-change" id="action-form">
	<b>Add Change</b>: 
	<input name=sn value="" required placeholder="SN">
	<select name=type>		
		<option value="Status">Status</option>
		<option value="Radical">Radical</option>
		<option value="Stroke Count">Stroke Count</option>
		<option value="First Stroke">First Stroke</option>
		<option value="Total Stroke Count">Total Stroke Count</option>
		<option value="IDS">IDS</option>
		<option value="Trad/Simp Flag">Trad/Simp Flag</option>
	</select>
	<input name=value>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit>
	<div><small>For radical, input e.g. 103 (for trad), 103.1 (for simp).</small></div>
	<div><small>For Status, input Unified / Withdrawn / Postponed / Not Unified / Disunified</small></div>
</form>
<?
}
?>
<? } ?>
</section>
