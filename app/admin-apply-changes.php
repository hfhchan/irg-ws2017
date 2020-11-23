<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

$prevVer = '5.1';
$thisVer = '5.2';

if (isset($_GET['action']) && $_GET['action'] === 'get-char' && isset($_GET['sn']) && ctype_digit($_GET['sn'])) {
	$sn = $_GET['sn'];
	$charPrev = DBCharacters::getCharacter($sn, $prevVer);
	$charNew = DBCharacters::getCharacter($sn, $thisVer);
	$discussionRecordMatch = $charPrev->discussion_record == $charNew->discussion_record;
?>
			<div class=change_entry_row>
				<div class=change_entry_cell><?=$charNew->renderPart4()?></div>
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
						<td><?=html_safe($charPrev->status)?></td>
						<td><?=html_safe($charPrev->radical)?></td>
						<td><?=html_safe($charPrev->stroke_count)?></td>
						<td><?=html_safe($charPrev->first_stroke)?></td>
						<td><?=html_safe($charPrev->trad_simp_flag)?></td>
						<td><?=html_safe($charPrev->ids)?></td>
						<td><?=html_safe($charPrev->total_stroke_count)?></td>
						<td><?=html_safe(implode(',', $charPrev->getAllSources(true)))?></td>
						<td class=change_table_version>v<?=$prevVer?> System</td>
					</tr>
					<tr>
						<td<?php if ($charPrev->status !== $charNew->status): ?> class=changed<? endif; ?>><?=html_safe($charNew->status)?></td>
						<td<?php if ($charPrev->radical !== $charNew->radical): ?> class=changed<? endif; ?>><?=html_safe($charNew->radical)?></td>
						<td<?php if ($charPrev->stroke_count !== $charNew->stroke_count): ?> class=changed<? endif; ?>><?=html_safe($charNew->stroke_count)?></td>
						<td<?php if ($charPrev->first_stroke !== $charNew->first_stroke): ?> class=changed<? endif; ?>><?=html_safe($charNew->first_stroke)?></td>
						<td<?php if ($charPrev->trad_simp_flag !== $charNew->trad_simp_flag): ?> class=changed<? endif; ?>><?=html_safe($charNew->trad_simp_flag)?></td>
						<td<?php if ($charPrev->ids !== $charNew->ids): ?> class=changed<? endif; ?>><?=html_safe($charNew->ids)?></td>
						<td<?php if ($charPrev->total_stroke_count !== $charNew->total_stroke_count): ?> class=changed<? endif; ?>><?=html_safe($charNew->total_stroke_count)?></td>
						<td><?=html_safe(implode(',', $charNew->getAllSources(true)))?></td>
						<td class=change_table_version>v<?=$thisVer?> System</td>
					</tr>
					<tr>
						<td colspan=8><?=$charPrev->discussion_record?></td>
						<td class=change_table_version>v<?=$prevVer?> System</td>
					</tr>
					<tr>
						<td colspan=8<? if (!$discussionRecordMatch): ?> class=changed<? endif; ?>>
							<?=($charNew->discussion_record)?>
						</td>
						<td class=change_table_version>v<?=$thisVer?> Excel</td>
				</table>
			</div>
<?
	exit;
}

if (!$session->isLoggedIn() || !$session->getUser()->isAdmin()) {
	$e = new FatalException('Requires admin permission / not logged in.');
	$e->title('Apply Changes based on consolidated comments | WS2017');
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
			$thisVer,
			$session->getUser()->getUserId()
		);
		echo 'Added';
		// header('Location: admin-apply-changes.php#sn-' . $_POST['sn']);
		exit;
	}
}

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1280">
<title>Apply Changes from Consolidated Comments | WS2017</title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
.content{max-width:1280px;margin:20px auto;padding:10px}
.content h1{margin:10px 0}

.sheet{margin:10px 0}

.change_entry{margin:40px 0;padding-bottom:40px;border-bottom:1px solid #ccc}
.change_comment{padding:10px;font-size:13px;border:1px solid #ccc}
.change_entry_row{margin-top:2px;display:grid;grid-template-columns:320px auto;grid-gap:10px}
.change_table{margin:0}
.change_table th{padding:4px;text-align:left;font-weight:normal;font-size:13px;color:#555}
.change_table td{padding:4px}
.change_table td.changed{background:yellow}
.change_table_version{font-size:13px;line-height:1}

.table-add-change-sn{width:80px}
</style>
<div class=content>
	<h1>Apply Changes from Consolidated Comments</h1>
	<div>List dicussion record for v<?=htmlspecialchars($prevVer)?> to be applied to v<?=htmlspecialchars($thisVer)?></div>
<?
	$list = DBComments::getListAll($prevVer);
	$groupedComments = [];
	foreach ($list as $cm) {
		if ($cm->type === 'COMMENT_IGNORE') continue;
		if ($cm->type === 'TRAD_VARIANT') continue;
		if ($cm->type === 'SIMP_VARIANT') continue;
		if ($cm->type === 'SEMANTIC_VARIANT') continue;
		$groupedComments[$cm->sn][] = $cm;
	}

	$i = 0;
	foreach ($groupedComments as $comments) {
		$i++;
		$cm = $comments[0];
?>
		<div id="sn-<?=$cm->sn?>" class=change_entry data-sn="<?=$cm->sn?>">
			<div><i><?=$i?></i></div>
<?
		foreach ($comments as $comment) {
			$comment_user = IRGUser::getById($comment->created_by);
?>
			<div id=comment-<?=$comment->id?> class=change_comment>
				<b><?=html_safe($comment_user->getName())?></b>: 
				<?=html_safe($comment->type)?> - <?=html_safe($comment->comment)?>
			</div>
<?
		}

		$charPrev = DBCharacters::getCharacter($cm->sn, $prevVer);
		$charNew = DBCharacters::getCharacter($cm->sn, $thisVer);

		$discussionRecordMatch = $charPrev->discussion_record == $charNew->discussion_record;
?>
			<div class=change_entry_row>
				<div class=change_entry_cell><?=$charNew->renderPart4()?></div>
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
						<td><?=html_safe($charPrev->status)?></td>
						<td><?=html_safe($charPrev->radical)?></td>
						<td><?=html_safe($charPrev->stroke_count)?></td>
						<td><?=html_safe($charPrev->first_stroke)?></td>
						<td><?=html_safe($charPrev->trad_simp_flag)?></td>
						<td><?=html_safe($charPrev->ids)?></td>
						<td><?=html_safe($charPrev->total_stroke_count)?></td>
						<td><?=html_safe(implode(',', $charPrev->getAllSources(true)))?></td>
						<td class=change_table_version>v<?=$prevVer?> System</td>
					</tr>
					<tr>
						<td<?php if ($charPrev->status !== $charNew->status): ?> class=changed<? endif; ?>><?=html_safe($charNew->status)?></td>
						<td<?php if ($charPrev->radical !== $charNew->radical): ?> class=changed<? endif; ?>><?=html_safe($charNew->radical)?></td>
						<td<?php if ($charPrev->stroke_count !== $charNew->stroke_count): ?> class=changed<? endif; ?>><?=html_safe($charNew->stroke_count)?></td>
						<td<?php if ($charPrev->first_stroke !== $charNew->first_stroke): ?> class=changed<? endif; ?>><?=html_safe($charNew->first_stroke)?></td>
						<td<?php if ($charPrev->trad_simp_flag !== $charNew->trad_simp_flag): ?> class=changed<? endif; ?>><?=html_safe($charNew->trad_simp_flag)?></td>
						<td<?php if ($charPrev->ids !== $charNew->ids): ?> class=changed<? endif; ?>><?=html_safe($charNew->ids)?></td>
						<td<?php if ($charPrev->total_stroke_count !== $charNew->total_stroke_count): ?> class=changed<? endif; ?>><?=html_safe($charNew->total_stroke_count)?></td>
						<td><?=html_safe(implode(',', $charNew->getAllSources(true)))?></td>
						<td class=change_table_version>v<?=$thisVer?> System</td>
					</tr>
					<tr>
						<td colspan=8><?=$charPrev->discussion_record?></td>
						<td class=change_table_version>v<?=$prevVer?> System</td>
					</tr>
					<tr>
						<td colspan=8<? if (!$discussionRecordMatch): ?> class=changed<? endif; ?>>
							<?=($charNew->discussion_record)?>
						</td>
						<td class=change_table_version>v<?=$thisVer?> Excel</td>
				</table>
			</div>

<?
if ($session->isLoggedIn() && $session->getUser()->isAdmin()) {
?>

<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="Discussion Record">Discussion Record</option>
	</select>
	<input name=value value="" required>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="Status">Status</option>
	</select>
	<select name=value>
		<option value="" selected>(No Change)</option>
		<option value="Unified">Unified</option>
		<option value="Withdrawn">Withdrawn</option>
		<option value="Not Unified">Not Unified (Use this option if already in M-set)</option>
		<option value="Disunified">Disunified (Move back to M-set - use this option if previously Unified)</option>
		<option value="OK">OK (Move back to M-set - use this option if previously postponed due to evidence / withdrawn)</option>
		<option value="Postponed">Postponed</option>
	</select>
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="Radical">Radical</option>
	</select>
	<input name=value value="" pattern="[0-9]+">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="Stroke Count">Stroke Count</option>
	</select>
	<input name=value value="" pattern="[0-9]+">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="First Stroke">First Stroke</option>
	</select>
	<input name=value value="" pattern="[0-5]">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="Trad/Simp Flag">Trad/Simp Flag</option>
	</select>
	<input name=value value="" pattern="(0|1)">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="IDS">IDS</option>
	</select>
	<input name=value value="">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<form method=post action="admin-apply-changes.php" style="margin-bottom:10px" class="table-add-change">
	<b>Add Change</b>: 
	<input name=sn class=table-add-change-sn value="<?=html_safe($cm->sn)?>" required>
	<select name=type>		
		<option value="Total Stroke Count">Total Stroke Count</option>
	</select>
	<input name=value value="" pattern="[0-9]+">
	<input type=hidden name=action value=change>
	<input type=submit value=Submit name=submitBtn>
</form>


<?
}
?>
		</div>
<?
	}
?>
</div>
<script>
var refreshEntry = (parent) => {
	if (!parent) return;
	parent.style.opacity = '.3';
	parent.style.pointerEvents = 'none';
	var sn = parent.dataset.sn;
	var promise = fetch('?action=get-char&sn=' + sn, { credentials: 'same-origin' }).then(res => res.text()).then(data => {
		var originalRow = parent.querySelector('.change_entry_row');
		originalRow.insertAdjacentHTML('beforebegin', data);
		originalRow.remove();
		parent.style.opacity = '1';
		parent.style.pointerEvents = 'auto';
	});
	promise.catch(() => {
		alert('Error ocurred - could not refresh entry');
		window.location.href = window.location.href;
	});
	return promise;
}

[...document.querySelectorAll('.table-add-change')].forEach(form => {
	form.reset();
	form.addEventListener('submit', (e) => {
		e.preventDefault();
		form.submitBtn.disabled = true;
		var formData = new FormData(form);
		fetch('admin-apply-changes.php', {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		}).then(async (res) => {
			const data = await res.text();
			if (data !== 'Added') {
				alert(data);
				window.location.href = window.location.href;
			} else {
				form.reset();
				refreshEntry(form.closest('.change_entry'));
			}
		})
	});
});

</script>