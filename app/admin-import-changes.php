<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

$fileName = "IRGN2423WS2017v5.1.xlsx";
$prevVer = '5.0';
$thisVer = '5.1';

if (!$session->isLoggedIn() || !$session->getUser()->isAdmin()) {
	$e = new FatalException('Requires admin permission / not logged in.');
	$e->title('Import Changes from Excel | WS2017');
	$e->allowLogin(true);
	throw $e;
}

if ($session->isLoggedIn() && $session->getUser()->isAdmin()) {
	if (isset($_POST['action']) && $_POST['action'] == 'change') {
		if (!isset($_POST['value']) || trim($_POST['value']) === '') {
			echo 'Value cannot be empty';
			exit;
		}
		DBChanges::add(
			null,
			$_POST['sn'],
			$_POST['type'],
			trim($_POST['value']),
			$prevVer,
			$thisVer
		);
		echo 'Added';
		//header('Location: admin-import-changes.php#sn-' . $_POST['sn']);
		exit;
	}
}

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1280">
<title>Import Changes from Excel | WS2017</title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
.content{max-width:1280px;margin:20px auto;padding:10px}
.content h1{margin:10px 0}

.sheet{margin:10px 0}

.change_entry{margin:10px 0;border-bottom:1px solid #ccc}
.change_entry_row{margin-top:2px;display:grid;grid-template-columns:320px auto;grid-gap:10px}
.change_table{margin:0}
.change_table th{padding:4px;text-align:left;font-weight:normal;font-size:13px;color:#555}
.change_table td{padding:4px}
.change_table td.changed{background:yellow}
.change_table_version{font-size:13px;line-height:1}

.table-add-change-sn{width:80px}
</style>
<div class=content>
	<h1>Import changes from Excel</h1>
	<div>Importing from <?=htmlspecialchars($fileName)?> for v<?=htmlspecialchars($thisVer)?></div>
<?

function cleanDiscussionRecord($str) {
	if (strpos($str, 'V source corrected, 2020-06.') === 0) {
		$str = substr($str, strlen('V source corrected, 2020-06.')) . 'V source corrected, 2020-06.';
	}

	// temp override
	$str = str_replace('postoponed', 'postponed', $str);
	$str = str_replace('Postoponed', 'Postponed', $str);
	$str = str_replace('radcial', 'radical', $str);
	$str = str_replace('eivdence', 'evidence', $str);
	$str = str_replace('2020-05', '2020-06', $str);
	$str = str_replace('2020--6', '2020-06', $str);
	$str = str_replace('20020-06', '2020-06', $str);

	$str = str_replace(' ', '', $str);
	$str = str_replace('.', '', $str);
	$str = str_replace(',', '', $str);
	$str = str_replace('/', '', $str);
	$str = str_replace('(', '', $str);
	$str = str_replace(')', '', $str);
	$str = str_replace('+', '', $str);
	$str = str_replace('=', '', $str);
	$str = str_replace('@', '', $str);
	$str = str_replace("\r", '', $str);
	$str = str_replace("\n", '', $str);
	return strtolower($str);
}

function getSources($rowData) {
	$src = [];
	for ($i = 8; $i <= 14; $i++) {
		if (!empty($rowData[$i])) {
			$src[] = $rowData[$i];
		}
	}
	return $src;
}

$character_cache = new CharacterCache();

$objReader = PHPExcel_IOFactory::createReader('Excel2007');
$objReader->setReadDataOnly(true);
$workbook = $objReader->load($fileName);


foreach ([0, 1, 2] as $currentSheet) {
	echo '<div class=sheet>';
	echo '<div><b>Sheet ' . ($currentSheet + 1) . '</b></div>'."\r\n";
	$worksheet = $workbook->getSheet($currentSheet);

	$highestRow    = $worksheet->getHighestRow(); 
	$highestColumn = $worksheet->getHighestColumn();

	$firstRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];

	$i = 0;
	for ($row = 2; $row <= $highestRow; $row++) {
		$rowData    = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
		$sq_number  = trim($rowData[0]);
		$sq_number  = str_pad($sq_number, 5, "0", STR_PAD_LEFT);
		$rowData[0] = $sq_number;

		$data        = new StdClass();
		$data->data  = $rowData;

		$char = DBCharacters::getCharacter($rowData[0], '5.1');
		
		$rowData[3] = (String) $rowData[3];
		$rowData[6] = (String) trim($rowData[6]);
		$rowData[7] = (String) $rowData[7];

		$char->stroke_count = (String) $char->stroke_count;
		$char->ids = trim($char->ids);
		$char->total_stroke_count = (String) $char->total_stroke_count;
		
		$discussionRecordExisting = cleanDiscussionRecord($char->discussion_record);
		$discussionRecordIncoming = cleanDiscussionRecord($rowData[1]);
		$discussionRecordMatch = @levenshtein($discussionRecordExisting, $discussionRecordIncoming, 1, 9999, 1) <= 4;
		
		$sourcesExisting = implode(',', $char->getAllSources(true));
		$sourcesIncoming = implode(',', getSources($rowData));
		
		$rowData[1] = str_replace('Postoponed', 'Postponed', $rowData[1]); // temp override
		$rowData[1] = str_replace('postoponed', 'postponed', $rowData[1]); // temp override
		$rowData[1] = str_replace('irg53.', 'IRG 53.', $rowData[1]); // temp override
		$rowData[1] = str_replace('evidences', 'evidence', $rowData[1]); // temp override

		if (
			$sourcesExisting != $sourcesIncoming ||
			!$discussionRecordMatch ||
			$char->status != $currentSheet ||
			$rowData[2] != $char->radical ||
			$rowData[3] != $char->stroke_count ||
			$rowData[4] != $char->first_stroke ||
			$rowData[5] != $char->trad_simp_flag ||
			$rowData[6] != $char->ids ||
			$rowData[7] != $char->total_stroke_count
		) {
			$i++;
?>
		<div id="sn-<?=$char->data[0]?>" class=change_entry>
			<div class=change_entry_id><?=$i?></div>
			<div class=change_entry_row>
				<div class=change_entry_cell><?=$char->renderPart4()?></div>
				<table style="border-collapse:collapse" class=change_table>
					<col width=64>
					<col width=64>
					<col width=64>
					<col width=64>
					<col width=64>
					<col width=100>
					<col width=64>
					<col width=100>
					<col width=100>
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
						<td><?=html_safe($char->status)?></td>
						<td><?=html_safe($char->radical)?></td>
						<td><?=html_safe($char->stroke_count)?></td>
						<td><?=html_safe($char->first_stroke)?></td>
						<td><?=html_safe($char->trad_simp_flag)?></td>
						<td><?=html_safe($char->ids)?></td>
						<td><?=html_safe($char->total_stroke_count)?></td>
						<td><?=html_safe($sourcesExisting)?></td>
						<td class=change_table_version>v<?=$thisVer?> System</td>
					</tr>
					<tr>
						<td<? if ($char->status != $currentSheet) echo ' class=changed'?>><?=$currentSheet?></td>
						<td<? if ($char->radical != $rowData[2]) echo ' class=changed'?>><?=html_safe($rowData[2])?></td>
						<td<? if ($char->stroke_count != $rowData[3]) echo ' class=changed'?>><?=html_safe($rowData[3])?></td>
						<td<? if ($char->first_stroke != $rowData[4]) echo ' class=changed'?>><?=html_safe($rowData[4])?></td>
						<td<? if ($char->trad_simp_flag != $rowData[5]) echo ' class=changed'?>><?=html_safe($rowData[5])?></td>
						<td<? if ($char->ids != $rowData[6]) echo ' class=changed'?>><?=html_safe($rowData[6])?></td>
						<td<? if ($char->total_stroke_count != $rowData[7]) echo ' class=changed'?>><?=html_safe($rowData[7])?></td>
						<td<? if ($sourcesExisting != $sourcesIncoming) echo ' class=changed'?>><?=html_safe($sourcesIncoming)?></td>
						<td class=change_table_version>v<?=$thisVer?> Excel</td>
					</tr>
					<tr>
						<td colspan=8><?=$char->discussion_record?></td>
						<td class=change_table_version>v<?=$thisVer?> System</td>
					</tr>
					<tr>
						<td colspan=8<? if (!$discussionRecordMatch): ?> class=changed<? endif; ?>>
							<?=ucfirst(trim($rowData[1]))?>
						</td>
						<td class=change_table_version>v<?=$thisVer?> Excel</td>
				</table>
			</div>

<?
if ($session->isLoggedIn() && $session->getUser()->isAdmin() && $session->getUser()->getUserId() < 3) {
?>

<? if ($discussionRecordExisting != $discussionRecordIncoming) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="Discussion Record">Discussion Record</option>
	</select>
	<input name=value value="" required>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->status != $currentSheet) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="Status">Status</option>
	</select>
	<input name=value value="" required>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->radical != $rowData[2]) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="Radical">Radical</option>
	</select>
	<input name=value value="<?=html_safe($rowData[2])?>">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->stroke_count != $rowData[3]) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="Stroke Count">Stroke Count</option>
	</select>
	<input name=value value="<?=html_safe($rowData[3])?>">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->first_stroke != $rowData[4]) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="First Stroke">First Stroke</option>
	</select>
	<input name=value value="<?=html_safe($rowData[4])?>">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->trad_simp_flag != $rowData[5]) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="Trad/Simp Flag">Trad/Simp Flag</option>
	</select>
	<input name=value value="<?=html_safe($rowData[5])?>">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->ids != $rowData[6]) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="IDS">IDS</option>
	</select>
	<input name=value value="<?=html_safe($rowData[6])?>">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>
<? if ($char->total_stroke_count != $rowData[7]) {?>
<form method=post action="admin-import-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($rowData[0])?>" required>
	<select name=type>		
		<option value="Total Stroke Count">Total Stroke Count</option>
	</select>
	<input name=value value="<?=html_safe($rowData[7])?>">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
	Reject Reason: <textarea></textarea>
</form>
<? } ?>

<?
}
?>
		</div>
<?
		}
	}
	echo '</div>';
}
?>
</div>
<script>
[...document.querySelectorAll('.table-add-change')].forEach(form => {
	form.addEventListener('submit', (e) => {
		console.log('hi');
		e.preventDefault();
		var formData = new FormData(form);
		fetch('admin-import-changes.php', {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		}).then(() => { 
			form.remove();
		})
	});
});
</script>