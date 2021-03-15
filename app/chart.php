<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

Log::disable();

if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = DBVersions::getCurrentVersion()->version;
}

Log::add('Fetch Char');
$character_cache = new CharacterCache();
$chars = $character_cache->getAll($version);
Log::add('Fetch Char End');

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1200">
<title>Charts | WS2017v<?=$version?></title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
[hidden]{display:none}
body{font-family:Arial,IPAMjMincho,"TH-Tshyn-P2","Microsoft Jhenghei","HanaMinA","HanaMinB","HanaMinC","Microsoft Yahei",sans-serif;background:#eee;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.ws2017_chart_header{table-layout:fixed;width:100%}
.ws2017_chart_footer{table-layout:fixed;width:100%}

.ws2017_chart_table_outer{height:16.6cm;margin:.5cm 0}
.ws2017_chart_table{border:.38mm solid #333;border-collapse:collapse;table-layout:fixed;width:100%;text-align:center;box-sizing:border-box}
.ws2017_chart_table td{border:.38mm solid #333;padding:0;font-size:13px;height:2.0cm}
.ws2017_chart_table thead td{height:.6cm}

.ws2017_chart_sn{padding:5px;display:grid;align-items:center;font-size:16px;font-family:Arial Narrow}
.ws2017_chart_sn a{color:#000;text-decoration:none}
.ws2017_chart_sn a:hover{color:blue;text-decoration:underline}
.ws2017_chart_attributes{padding:0!important;height:2.0cm}
.ws2017_chart_attributes{display:grid;grid-template-rows:1fr 1fr 1fr;font-size:16px}
.ws2017_chart_attributes>div:first-child{border-top:none}
.ws2017_chart_attributes>div{border-top:.38mm solid #333}
.ws2017_chart_attributes span+span{margin-left:1px}
.ws2017_chart_source_cell{font-family:Arial Narrow}

.ws2017_chart_table_discussion{display:grid;align-content:center;text-align:left!important;padding:8px;overflow:auto}

.sheet-1{background:#999;opacity:.6}
.sheet-2{background:#ff0}

.ws2017_cutting{margin-left:225px}
.ws2017_cutting img,.ws2017_cutting canvas{width:577px}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.page{width:28cm;height:19.8cm;page-break-inside:avoid;background:#fff;margin:0 auto}
.page::before{content:"";display:table}
.page::after{content:"";display:table}

@media print {
	#page_selector{display:none}
}
@media screen {
	#page_selector{margin:10px auto;width:24cm}
	#page_selector a{display:inline-block}
	.page{margin:10px auto;box-shadow:0 0 0 1px #999, 0 0 5px #999;padding:.5cm}
}
@page {
  size:A4 landscape;
  margin:.5cm;
}
</style>
<script src="jquery.js"></script>
<body>
<?
define("CHARTS", 1);
require_once 'index.searchbar.php';
?>

<?php
if (Env::$readonly) {
	//define('EVIDENCE_PATH', 'https://raw.githubusercontent.com/hfhchan/irg-ws2017/5d22fba4/data');
	define('EVIDENCE_PATH', '../data');
} else {
	define('EVIDENCE_PATH', '../data');
}
?>
<?php

foreach ($chars as $char) {
	$char->db = DBCharacters::getCharacter($char->data[0], $version);
}

usort($chars, function($a, $b) {
	$c = $a->db->getRadicalStrokeFull();
	$d = $b->db->getRadicalStrokeFull();
	if ($c === $d) {
		return strcmp($a->db->ids, $b->db->ids);
	}
	return strnatcmp($c, $d);
});

if (isset($_GET['ids_group'])) {
	$all_ids = [];
	foreach ($chars as $char) {
		if (!isset($all_ids[$char->db->ids])) {
			$all_ids[$char->db->ids] = 0;
		}
		$all_ids[$char->db->ids]++;
	}
	$chars = array_values(array_filter($chars, function($char) use ($all_ids) {
		return ($all_ids[$char->db->ids] >= 2);
	}));
	usort($chars, function($a, $b) {
		$result = strcmp($a->db->ids, $b->db->ids);
		if (!$result) {
			return strcmp($a->db->status, $b->db->status);
		}
		return $result;
	});
}


if (!isset($_GET['ids_group'])) {
	$sheets = [
		0 => [],
		1 => [],
		2 => [],
	];

	foreach ($chars as $char) {
		$sheets[$char->db->status][] = $char;
	} unset($chars);
} else {
	$sheets = [
		0 => []
	];

	foreach ($chars as $char) {
		$sheets[0][] = $char;
	} unset($chars);
}



class Page {
	public $sheet_name;
	public $page_number;
	public $total_pages;
	public $chars;
}

$pages = [];

foreach ($sheets as $sheet_number => $chars) {
	$sheet_name = 'WorkingSet';
	if ($sheet_number == 1) {
		$sheet_name = 'Unified&Withdrawn';
	}
	if ($sheet_number == 2) {
		$sheet_name = 'Pending';
	}
	$chunks = array_chunk($chars, 8);
	$total_pages = count($chunks);
	foreach ($chunks as $index => $chunk_chars) {
		if (isset($_GET['radical'])) {
			$chunk_chars = array_filter($chunk_chars, function($char) {
				if ($char->data[Workbook::RADICAL] == $_GET['radical']) {
					return true;
				}
				if ($char->data[Workbook::RADICAL] == ($_GET['radical'] . '.1')) {
					return true;
				}
				return false;
			});
		}
		if (count($chunk_chars)) {
			$page = new Page();
			if (!isset($_GET['ids_group'])) {
				$page->sheet_name  = CharacterCache::getSheetName($version, $sheet_number);
			} else {
				$page->sheet_name  = substr(CharacterCache::getSheetName($version, 0), 0, -12) . ' Group by same IDS';
			}
			$page->page_number = $index + 1;
			$page->total_pages = $total_pages;
			$page->chars = $chunk_chars;
			$pages[] = $page;
		}
	}
}

$total_pages = count($pages);

if (!isset($_GET['range']) || $_GET['range'] !== 'all') {
	if (!isset($_GET['range']) || !ctype_digit($_GET['range'])) {
		$start = 0;
	} else {
		$start = intval($_GET['range']);
	}
	$pages = array_slice($pages, $start, 50);
} else {
	$start = -1;
}

?>

<div id=page_selector>
	Show:
	<a href="chart.php?ids_group">IDS Identical</a>
	<br>
	<br>
	Show:
<?php
for ($i = 1; $i <= 214; $i += 1) {
?>
	<a href="chart.php?radical=<?=$i?>"><?=getIdeographForRadical($i)[0]?></a>
<?php
}
?>
	<br>
	<br>
	Show: 
<?php
for ($i = 0; $i < $total_pages; $i += 50) {
?>
<? if ($start == $i) {?>
	<b>Pages <?=$i+1?> to <?=$i+50?></b>
<? } else {?>
	<a href="?range=<?=$i?>">Pages <?=$i+1?> to <?=$i+50?></a>
<? }?>
<?php
}
?>
</div>


<?php

foreach ($pages as $page) {

?>
	<div class=page>
		<table class=ws2017_chart_header>
			<tr>
				<td align=left></td>
				<td align=center><b><?=html_safe($page->sheet_name)?></b></td>
				<td align=right></td>
			</tr>
		</table>
		<div class=ws2017_chart_table_outer>
			<table class=ws2017_chart_table>
				<col style="width:1.5cm">
				<col style="width:4cm">
				<col>
				<col>
				<col>
				<col>
				<col>
				<col>
				<col>
				<col style="width:6.8cm">
				<thead>
					<tr>
						<td>SN</td>
						<td>Attributes</td>
						<td>G</td>
						<td>K</td>
						<td>USAT</td>
						<td>T</td>
						<td>UTC</td>
						<td>UK</td>
						<td>V</td>
						<td>Discussion Record</td>
					</tr>
				</thead>
<?php
		for ($i = 0; $i < 8; $i++) {
			if (!isset($page->chars[$i])) {
?>
				<tr>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
<?php
				continue;
			}
			$char = $page->chars[$i];
			Log::add('Render Char Start ' . $char->data[0]);
			$rowData  = $char->data;
			$sq_number = $char->data[0];
	
?>
				<tr class="sheet-<?=$char->db->status?>">
					<td><div class=ws2017_chart_sn style="display:grid;align-items:center">
						<a href="index.php?id=<?=$char->db->sn?>" target=_blank><?=$char->db->sn?></a>
						<div><?=$char->db->trad_simp_flag ? 'Simp' : 'Trad';?></div>
					</div></td>
					<td>
						<div class=ws2017_chart_attributes style="display:grid;grid-template-rows:1fr 1fr 1fr">
							<div style="display:grid;align-items:center"><?=$char->db->getRadicalStroke()?></div>
							<div style="display:grid;align-items:center;white-space:nowrap"><div><?php
									$ids = parseStringIntoCodepointArray($char->db->ids);
									foreach ($ids as $component) {
										if (!empty(trim($component))) {
											if ($component[0] === 'U') {
												echo '<span>' . codepointToChar($component) . '</span>';
											} else {
												echo '<span>' . html_safe($component) . '</span>';
											}
										}
									}
									if (empty($char->db->ids)) {
										echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
									}
							?></div></div>
							<div style="display:grid;grid-template-columns:1fr 1fr">
								<div style="border-right:1px solid #333;display:grid;align-items:center"><?=$char->db->getFirstStroke()?></div>
								<div style="display:grid;align-items:center"><?=$char->db->getTotalStrokes()?></div>
							</div>
						</div>
					</td>
					<td class=ws2017_chart_source_cell>
<?php if (isset($rowData[Workbook::G_SOURCE]) || isset($rowData[Workbook::G_SOURCE+1])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::G_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$rowData[Workbook::G_SOURCE]?>
<?php } ?>
					</td>
					<td class=ws2017_chart_source_cell>
<?php if (isset($rowData[Workbook::K_SOURCE]) || isset($rowData[Workbook::K_SOURCE+1])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::K_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$rowData[Workbook::K_SOURCE]?>
<?php } ?>
					</td>
					<td class=ws2017_chart_source_cell>
<?php if (isset($rowData[Workbook::SAT_SOURCE]) || isset($rowData[Workbook::SAT_SOURCE+1])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::SAT_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$rowData[Workbook::SAT_SOURCE]?>
<?php } ?>
					</td>
					<td class=ws2017_chart_source_cell>
<?php if (isset($rowData[Workbook::T_SOURCE]) || isset($rowData[Workbook::T_SOURCE + 1])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::T_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$rowData[Workbook::T_SOURCE]?>
<?php } ?>
					</td>
					<td class=ws2017_chart_source_cell>
<?php if (isset($rowData[Workbook::UTC_SOURCE])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::UTC_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$rowData[Workbook::UTC_SOURCE]?>
<? } ?>
					</td>
					<td class=ws2017_chart_source_cell>
<?php if (isset($rowData[Workbook::UK_SOURCE])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::UK_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$rowData[Workbook::UK_SOURCE]?>
<? } ?>
					</td>
					<td class=ws2017_chart_source_cell>
<? if (strcmp($version, '5.1') >= 0) { 
	$vSourceText = vSourceFixup($rowData[Workbook::V_SOURCE]);
} else {
	$vSourceText = $rowData[Workbook::V_SOURCE];
} ?>
<?php if (isset($rowData[Workbook::V_SOURCE])) {?>
	<img src="<?=EVIDENCE_PATH?><?=DBCharacters::getFileName($rowData[Workbook::V_SOURCE], $version)?>" width="32" height="32"><br>
	<?=$vSourceText?>
<? } ?>
					</td>
					<td>
						<div class=ws2017_chart_table_discussion>
							<div>
								<? if ($char->db->status) echo '<b>'.CharacterCache::getSheetName($char->version, $char->db->status) . '</b><br>'; ?>
								<?=$char->db->discussion_record?>
							</div>
						</div>
					</td>
				</tr>
<?php
		}
?>
			</table>
		</div>
		<table class=ws2017_chart_footer>
			<tr>
				<td align=left></td>
				<td align=center><?=($page->page_number)?> of <?=$page->total_pages?></td>
				<td align=right></td>
			</tr>
		</table>
	</div>
</div>
<?php
}
?>
