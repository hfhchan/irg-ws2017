<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

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


if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
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

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1100">
<title><?=($user ? $user->getName() : 'Consolidated') ?> Comments | WS2017v<?=$version?></title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
blockquote{margin:10px 0;padding:0;padding-left:10px;border-left:3px solid #ccc}
.footer{width:1200px;margin:20px auto}

.ws2017_cutting>img,.ws2017_cutting>canvas{width:auto!important;height:auto!important;max-width:100%}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.ws2017_comments{width:28.4cm;margin:0 auto}
.ws2017_comments table{border-collapse:collapse;width:100%}
.ws2017_comments td,.ws2017_comments th{border:1px solid #000;padding:10px}
.ws2017_comments td{vertical-align:top}
.ws2017_comments th{text-align:left}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}

.sheet-1{background:#ccc}
.sheet-2{background:#ff0}

@media print{
	#view_toolbar{display:none!important}
	#format{display:none!important}
}
</style>
<script src="jquery.js"></script>
<body>
<? require_once 'index.searchbar.php'; ?>
	<div style="position:fixed;right:10px;top:10px;background:#333;padding:10px 20px;color:#fff" id=loading>Please wait while loading</div>
	<div style="position:fixed;right:10px;top:10px;background:#333;padding:10px 20px;color:#fff;display:none" id=format>Click here to format document for copy/pasting</div>
<section class=ws2017_comments>
	<h2>IRG Working Set 2017v<?=$version?></h2>
<? if ($user) {?>
	<p>
		Source: <?=$user->getName()?><br>
		Date: Generated on <?=date("Y-m-d")?>
	</p>
	<? if (empty($_GET['show_deleted'])) { ?>
		<div>
			<a href="list.php?show_deleted=1&user=<?=$user->getUserId()?>">Show Deleted</a>
		</div>
	<? } ?>
<? } else { ?>
	<div><b>Consolidated Comments</b></div>
	<div id=view_toolbar>
		<form method=get action="list.php">
			<input type=hidden name=user value=0>
			Filter By Source:
			<select name=filter_column>
<? if (empty($_GET['filter_column'])) { ?>
		<option value="" selected>Show All</option>
<? } else { ?>
		<option value="">Show All</option>
<? } ?>
<? foreach (SOURCES as $source => $col) { ?>
	<? if (isset($_GET['filter_column']) && intval($_GET['filter_column']) === $col) { ?>
		<option value="<?=$col?>" selected><?=$source?> only</option>
	<? } else { ?>
		<option value="<?=$col?>"><?=$source?> only</option>
	<? } ?>
<? } ?>
			</select>
			Sort By:
			<select name=sort_by_date>
				<option value=""<? if (empty($_GET['sort_by_date'])) echo ' selected';?>>Sort By Category</option>
				<option value="1"<? if (!empty($_GET['sort_by_date'])) echo ' selected';?>>Sort By Date</option>
			</select>
			
			Show Deleted: <input type=checkbox name="show_deleted"<? if (!empty($_GET['show_deleted'])) echo ' checked';?>>
			
			<input type=submit value="Filter">
		</form>
	</div>
<? } ?>
<?php

if ($user) {
	$list = DBComments::getList($user->getUserId(), $version);
} else {
	$list = DBComments::getListAll($version);
}

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
	return $cm->created_at;
}, $list);

if (empty($_GET['sort_by_date'])) {
	array_multisort($type, $source1, $source2, $source3, $source4, $source5, $source6, $source7, $date, $list);
}

if (isset($_GET['filter_column'])) {
	$col = intval($_GET['filter_column']);
	$validCols = array_values(SOURCES);
	if (in_array($col, $validCols)) {
		$list = array_filter($list, function($cm) use ($character_cache, $col) {
			$char = $character_cache->get($cm->getSN());
			if (!empty($char->data[$col])) {
				return true;
			}
			return false;
		});
	}
}


function getFriendlyTypeName($type) {
	$friendlyType = ucfirst(strtolower(strtr($type, '_', ' ')));	
	if ($friendlyType === 'No unification') {
		$friendlyType = 'Oppose Unification';
	}
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
	if (empty($_GET['sort_by_date'])) {	
		$category = $item->getCategoryForCommentType();
		if ($category === null) {
			continue;
		}
	} else {
		$category = substr($item->date, 0, 10) . (' (UTC)');
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
		$label = array_map(function($cm) {
			return $cm->comment;
		}, $chunk);
		array_multisort($label, $chunk);
	}
	
	$currentUser = $session->getUser();
	$chunk = array_values(array_filter($chunk, function ($cm) use ($currentUser) {
		if ($cm->isDeleted()) {
			if (!$currentUser || !empty($_REQUEST['meeting_mode']) || !isset($_GET['show_deleted']) || !($currentUser->getUserId() == $cm->created_by || $currentUser->isAdmin())) {
				return false;
			}
		}
		return true;
	}));

	echo '<h3>' . $category . '</h3>';
	echo '<table style="table-layout:fixed;width:100%" border=1 cellpadding=10>';
	echo '<col width=100>';
	echo '<col width=280>';
	echo '<col width=160>';
	echo '<col width=420>';
	echo '<thead><tr><th>Sn</th><th>Image/Source</th><th>Comment Type</th><th>Description</th></tr></thead>';
	$lastSN = null;
	foreach ($chunk as $i => $cm) {

		if ($cm->type === 'COMMENT_IGNORE') {
			continue;
		}

		if ($cm->type === 'OTHER') {
			if (strpos(strtolower($cm->comment), '** note') !== false) {
				continue;
			}
			if (strpos(strtolower($cm->comment), 'private note') !== false) {
				continue;
			}
		}

		$sheet = $character_cache->get(sprintf('%05d', $cm->sn))->sheet;

		$j = $i + 1;
		while (isset($chunk[$j]) && $chunk[$j]->getSN() == $cm->getSN()) {
			$j++;
		}
		$rowSpan = $j - $i;

		if ($cm->isDeleted()) {
			echo '<tr style="text-decoration:line-through;opacity:.3" class=sheet-'.$sheet.'>';
		} else {
			echo '<tr class=sheet-'.$sheet.'>';
		}
		
		if ($lastSN != $cm->getSN()) {
			echo '<td rowspan="'.$rowSpan.'"><b><a href="index.php?id='.htmlspecialchars($cm->getSN()).'" target=_blank>'.htmlspecialchars($cm->getSN()).'</a></b></td>';
			echo '<td rowspan="'.$rowSpan.'">';
			$char = $character_cache->getVersion($cm->getSN(), $version);
			//echo '<div style="width:154px;overflow:hidden">';
			//echo '<div style="width:1054px;margin-left:-'.($char->getSourceIndex()*150).'px">';
			//$char->renderCodeChartCutting();
			//$char->renderPDAM2_2();
			//echo '</div>';
			//echo '</div>';
			$char->renderPart4();

			if ($cm->isDeleted()) {
				echo '<div style="color:red">DELETED</div>';
			}
			echo '</td>';
		}

		echo '<td><b>';
		
		$friendlyType = getFriendlyTypeName($cm->type);
		
		echo htmlspecialchars($friendlyType);
		echo '</b>';
		
		if (!$user) {
			$_user = IRGUser::getById($cm->created_by);
			echo '<div style="margin-top:5px"></div>';
			echo '<div>' . htmlspecialchars($_user->getName()) . '</div>';
			echo '<div style="font-size:13px;color:#999">' . htmlspecialchars($_user->getOrganization()) . '</div>';
		}
		if (!empty($_GET['sort_by_date'])) {
			echo '<div>' . $cm->toLocalTime() . '</div>';
			echo "\n";
		}

		if ($cm->version !== $version) {
			if (!$cm->isResolved($version)) {
				echo '<div style="font-size:13px;color:red"><b>[ Unresolved from v' . $cm->version  . ' ]</b></div>';
			}
		}

		echo '</td>';
		echo '<td>';

		$commentProcessor = new CommentProcessor($cm);
		echo '<div>' . $commentProcessor->renderHTML() . '</div>';
		echo '</td>';
		echo '</tr>';

		$lastSN = $cm->getSN();
	}
	echo '</table>';
	echo '<br>';
	echo '<br>';
}
?>
</section>

<div class=footer>
	<p>Source Code released at <a href="https://github.com/hfhchan/irg-ws2017">https://github.com/hfhchan/irg-ws2017</a>.</p>
</div>

<script>
var finalize = (function() {
	$('body').css('font-family', 'Arial, "Microsoft Jhenghei",sans-serif');
	$('img').each(function() {
		$(this).attr('width', $(this).width()).attr('height', $(this).height());
	});
	$('table').attr('border', '1').css('border-collapse', 'collapse');
});
window.onload = () => {
	$('#loading').hide();
	$('#format').css('display', '').click(() => {
		$('table,h2,h3,p').css('font-family', 'Arial, "Microsoft Jhenghei",sans-serif');
		$('th').css('text-align', 'left');
		$('.ws2017_chart_source_head').remove();
		$('.ws2017_chart_source_block img').each((i, el) => {
			el.insertAdjacentHTML('afterend', '<br>');
		});
		$('.ws2017_chart_source_block').removeClass('ws2017_chart_source_block');
		$('.ws2017_chart_sources').removeClass('ws2017_chart_sources').css('text-align', 'center');
		$('#format').hide();
	});
	finalize();
};
// $('.ws2017_chart_source_head').hide();
</script>