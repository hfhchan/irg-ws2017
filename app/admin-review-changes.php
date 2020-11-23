<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

$prevVer = '5.1';
$thisVer = '5.2';

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1280">
<title>Changelog | WS2017<?=$thisVer?></title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
.content{max-width:1280px;margin:20px auto;padding:10px}
.content h1{margin:0 0 10px}

.sheet{margin:10px 0;break-after:always}

.change_entry{padding:10px 0;border-bottom:1px solid #ccc;break-inside:avoid}
.change_entry_row{margin-top:2px;display:grid;grid-template-columns:320px auto;grid-gap:10px}
.change_entry_cell{width:320px}
.change_table{margin:0;align-self:start}
.change_table th{padding:4px;text-align:left;font-weight:normal;font-size:13px;color:#555}
.change_table td{padding:4px}
.change_table td.changed{background:yellow}

.table-add-change-sn{width:80px}
</style>
<div class=content>
	<h1>WS2017 <?=$thisVer?> Changelog</h1>
	<div>Review changes for v<?=htmlspecialchars($prevVer)?> to v<?=htmlspecialchars($thisVer)?></div>
<?

$changesMap = [];
$changes = DBChanges::getChanges($prevVer);
foreach ($changes as $change) {
	$changesMap[$change->sn][] = $change;
}
$changedSN = array_map(function($change) {
	return $change->sn;
}, $changes);

$charsPrev = array_combine($changedSN, array_map(function($changedSN) use ($prevVer) {
	return DBCharacters::getCharacter($changedSN, $prevVer);
}, $changedSN));

$charsThis = array_combine($changedSN, array_map(function($changedSN) use ($thisVer) {
	return DBCharacters::getCharacter($changedSN, $thisVer);
}, $changedSN));

$sheets = [];
foreach ($charsThis as $char) {
	if (!empty($char->g_source))   $sheets['G'][] = $char->sn;
	if (!empty($char->k_source))   $sheets['K'][] = $char->sn;
	if (!empty($char->uk_source))  $sheets['UK'][] = $char->sn;
	if (!empty($char->sat_source)) $sheets['SAT'][] = $char->sn;
	if (!empty($char->t_source))   $sheets['T'][] = $char->sn;
	if (!empty($char->utc_source)) $sheets['UTC'][] = $char->sn;
	if (!empty($char->v_source))   $sheets['V'][] = $char->sn;
}

ksort($sheets);

foreach ($sheets as $currentSheet => $charSNList) {
	echo '<div class=sheet>';
	echo '<div><b>' . html_safe($currentSheet) . '</b></div>'."\r\n";

	$i = 0;
	foreach ($charSNList as $charSN) {
		$char1 = $charsPrev[$charSN];
		$char2 = $charsThis[$charSN];
		$char2->changes = $changesMap[$charSN];

		$source1 = implode("\n", $char1->getAllSources(strcmp($prevVer, '5.1') >= 0));
		$source2 = implode("\n", $char2->getAllSources(strcmp($thisVer, '5.1') >= 0));

		$discussionRecordMatch = $char1->discussion_record === $char2->discussion_record;

		$i++;
		
		//if ($i > 40) exit;
?>
		<div id="sn-<?=$charSN?>" class=change_entry>
			<div class=change_entry_id><?=$i?></div>
			<div class=change_entry_row>
				<div class=change_entry_cell>
					<?=$char1->renderPart4(strcmp($prevVer, '5.1') >= 0)?>
					<div style="text-align:center">🡻</div>
					<?=$char2->renderPart4(strcmp($thisVer, '5.1') >= 0)?>
				</div>
				<table style="border-collapse:collapse" class=change_table>
					<col width=100>
					<col width=64>
					<col width=64>
					<col width=64>
					<col width=64>
					<col width=100>
					<col width=64>
					<col width=100>
					<col width=64>
					<tr>
						<th>Sheet</th>
						<th>Radical</th>
						<th>Stroke</th>
						<th>First Stroke</th>
						<th>T/S Flag</th>
						<th>IDS</th>
						<th>Total Stroke</th>
						<th>Source</th>
						<th>Version</th>
					</tr>
					<tr>
						<td><?=html_safe($char1->getStatusText())?></td>
						<td><?=html_safe($char1->radical)?></td>
						<td><?=html_safe($char1->stroke_count)?></td>
						<td><?=html_safe($char1->first_stroke)?></td>
						<td><?=html_safe($char1->trad_simp_flag)?></td>
						<td><?=html_safe($char1->ids)?></td>
						<td><?=html_safe($char1->total_stroke_count)?></td>
						<td><?=nl2br(html_safe($source1))?></td>
						<td>v<?=html_safe($prevVer)?></td>
					</tr>
					<tr>
						<td<? if ($char1->status != $char2->status) echo ' class=changed';?>><?=html_safe($char2->getStatusText())?></td>
						<td<? if ($char1->radical != $char2->radical) echo ' class=changed';?>><?=html_safe($char2->radical)?></td>
						<td<? if ($char1->stroke_count != $char2->stroke_count) echo ' class=changed';?>><?=html_safe($char2->stroke_count)?></td>
						<td<? if ($char1->first_stroke != $char2->first_stroke) echo ' class=changed';?>><?=html_safe($char2->first_stroke)?></td>
						<td<? if ($char1->trad_simp_flag != $char2->trad_simp_flag) echo ' class=changed';?>><?=html_safe($char2->trad_simp_flag)?></td>
						<td<? if ($char1->ids != $char2->ids) echo ' class=changed';?>><?=html_safe($char2->ids)?></td>
						<td<? if ($char1->total_stroke_count != $char2->total_stroke_count) echo ' class=changed';?>><?=html_safe($char2->total_stroke_count)?></td>
						<td<? if ($source1 != $source2) echo ' class=changed';?>><?=nl2br(html_safe($source2))?></td>
						<td>v<?=html_safe($thisVer)?></td>
					</tr>
					<tr>
						<td colspan=8><?=$char1->discussion_record?></td>
						<td>v<?=html_safe($prevVer)?></td>
					</tr>
					<tr>
						<td colspan=8<? if (!$discussionRecordMatch): ?> class=changed<? endif; ?>>
							<?=$char2->discussion_record?></td>
						<td>v<?=html_safe($thisVer)?></td>
				</table>
			</div>
		</div>
<?
	}
	echo '</div>';
}
?>
</div>
