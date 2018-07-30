<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

Log::disable();

Log::add('Fetch Char');
$character_cache = new CharacterCache();
$chars = $character_cache->getAll();
Log::add('Fetch Char End');

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>WS2017v1.1</title>
<style>
[hidden]{display:none}
body{font-family:Arial,"HKCS","Microsoft Yahei",sans-serif;background:#eee;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.ws2017_chart_header{table-layout:fixed;width:100%}
.ws2017_chart_footer{table-layout:fixed;width:100%}

.ws2017_chart_table_outer{height:16.6cm;margin:.5cm 0}
.ws2017_chart_table{border:.38mm solid #333;border-collapse:collapse;table-layout:fixed;width:100%;text-align:center;box-sizing:border-box}
.ws2017_chart_table td{border:.38mm solid #333;padding:0;font-size:13px;height:2.0cm}
.ws2017_chart_table thead td{height:.6cm}

.ws2017_chart_sn{padding:10px;display:grid;align-items:center;font-size:16px}
.ws2017_chart_sn a{color:#000;text-decoration:none}
.ws2017_chart_sn a:hover{color:blue;text-decoration:underline}
.ws2017_chart_attributes{padding:0!important;height:2.0cm}
.ws2017_chart_attributes{display:grid;grid-template-rows:1fr 1fr 1fr;font-size:16px}
.ws2017_chart_attributes>div:first-child{border-top:none}
.ws2017_chart_attributes>div{border-top:.38mm solid #333}
.ws2017_chart_attributes span+span{margin-left:1px}

.ws2017_chart_table_discussion{display:grid;align-content:center;text-align:left!important;padding:10px;overflow:auto}

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
if (Env::$readonly) {
	//define('EVIDENCE_PATH', 'https://raw.githubusercontent.com/hfhchan/irg-ws2017/5d22fba4/data');
	define('EVIDENCE_PATH', '../data');
} else {
	define('EVIDENCE_PATH', '../data');
}
?>
<?

usort($chars, function($a, $b) {
	$c = $a->getRadicalStrokeFull();
	$d = $b->getRadicalStrokeFull();
	if ($c === $d) {
		return strcmp($a->data[Workbook::IDS], $b->data[Workbook::IDS]);
	}
	return strnatcmp($c, $d);
});

if (isset($_GET['ids_group'])) {
	$all_ids = [];
	foreach ($chars as $char) {
		if (!isset($all_ids[$char->data[Workbook::IDS]])) {
			$all_ids[$char->data[Workbook::IDS]] = 0;
		}
		$all_ids[$char->data[Workbook::IDS]]++;
	}
	$chars = array_values(array_filter($chars, function($char) use ($all_ids) {
		return ($all_ids[$char->data[Workbook::IDS]] >= 2);
	}));
	usort($chars, function($a, $b) {
		return strcmp($a->data[Workbook::IDS], $b->data[Workbook::IDS]);
	});
}


$sheets = [
	0 => [],
	1 => [],
	2 => [],
];

foreach ($chars as $char) {
	$sheets[$char->sheet][] = $char;
} unset($chars);



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
			$page->sheet_name  = 'IRGN2270WS2017v1.1' . $sheet_name;
			$page->page_number = $index + 1;
			$page->total_pages = $total_pages;
			$page->chars = $chunk_chars;
			$pages[] = $page;
		}
	}
}

$total_pages = count($pages);


if (!isset($_GET['range']) || !ctype_digit($_GET['range'])) {
	$start = 0;
} else {
	$start = intval($_GET['range']);
}
$pages = array_slice($pages, $start, 50);

?>

<div id=page_selector>
	Show:
	<a href="chart.php?ids_group">IDS Identical</a>
	<br>
	<br>
	Show:
<?
for ($i = 1; $i <= 214; $i += 1) {
?>
	<a href="chart.php?radical=<?=$i?>"><?=getIdeographForRadical($i)[0]?></a>
<?
}
?>
	<br>
	<br>
	Show: 
<?
for ($i = 0; $i < $total_pages; $i += 50) {
?>
<? if ($start == $i) {?>
	<b>Pages <?=$i+1?> to <?=$i+50?></b>
<? } else {?>
	<a href="?range=<?=$i?>">Pages <?=$i+1?> to <?=$i+50?></a>
<? }?>
<?
}
?>
</div>


<?

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
				<col style="width:2cm">
				<col style="width:4cm">
				<col>
				<col>
				<col>
				<col>
				<col>
				<col>
				<col>
				<col style="width:4cm">
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
<?
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
<?
				continue;
			}
			$char = $page->chars[$i];
			Log::add('Render Char Start ' . $char->data[0]);
			$rowData  = $char->data;
			$sq_number = $char->data[0];
	
?>
				<tr class="sheet-<?=$char->sheet?>">
					<td><div class=ws2017_chart_sn style="padding:10px;display:grid;align-items:center">
						<a href="index.php?id=<?=$rowData[0]?>" target=_blank><?=$rowData[0]?></a>
						<br>
						<?=$rowData[Workbook::TS_FLAG] ? '簡' : '繁';?>
					</div></td>
					<td>
						<div class=ws2017_chart_attributes style="display:grid;grid-template-rows:1fr 1fr 1fr">
							<div style="display:grid;align-items:center"><?=$char->getRadicalStroke()?></div>
							<div style="display:grid;align-items:center;white-space:nowrap"><div><?
									$ids = parseStringIntoCodepointArray($rowData[Workbook::IDS]);
									foreach ($ids as $component) {
										if (!empty(trim($component))) {
											if ($component[0] === 'U') {
												echo '<span>' . codepointToChar($component) . '</span>';
											} else {
												echo '<span>' . html_safe($component) . '</span>';
											}
										}
									}
									if (empty($rowData[Workbook::IDS])) {
										echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
									}
							?></div></div>
							<div style="display:grid;grid-template-columns:1fr 2fr">
								<div style="border-right:1px solid #333;display:grid;align-items:center"><?=$char->getFirstStroke()?></div>
								<div style="display:grid;align-items:center"><?=$char->getTotalStrokes()?></div>
							</div>
						</div>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::G_SOURCE]) || isset($rowData[Workbook::G_SOURCE+1])) {?>
						<? if (substr($rowData[Workbook::G_SOURCE], 0, 3) === 'GXM' || substr($rowData[Workbook::G_SOURCE], 0, 3) === 'GDM' || substr($rowData[Workbook::G_SOURCE], 0, 3) === 'GKJ') {?>
							<img src="<?=EVIDENCE_PATH?>/g-bitmap/<?=substr($rowData[Workbook::G_SOURCE+1], 0, -4)?>.png" width="40" height="32" style="object-fit:cover"><br>
						<? } else { ?>
							<img src="<?=EVIDENCE_PATH?>/g-bitmap/<?=substr($rowData[Workbook::G_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
						<? } ?>
							<?=$rowData[Workbook::G_SOURCE]?>
						<?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::K_SOURCE]) || isset($rowData[Workbook::K_SOURCE+1])) {?>
							<img src="http://www.koreanhistory.or.kr/newchar/fontimg/KC<?=substr($rowData[Workbook::K_SOURCE+1], 3, -4)?>_48.GIF" width="32" height="32"><br>
							<?=$rowData[Workbook::K_SOURCE]?><?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::SAT_SOURCE]) || isset($rowData[Workbook::SAT_SOURCE+1])) {?>
							<img src="https://glyphwiki.org/glyph/sat_g9<?=substr($rowData[Workbook::SAT_SOURCE+1], 4, -4)?>.svg" width="32" height="32"><br>
							<?=$rowData[Workbook::SAT_SOURCE]?>
						<?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::T_SOURCE]) || isset($rowData[Workbook::T_SOURCE + 1])) {?>
							<img src="https://www.cns11643.gov.tw/cgi-bin/ttf2png?page=<?=hexdec(substr($rowData[Workbook::T_SOURCE], 1, -5))?>&amp;number=<?=substr($rowData[Workbook::T_SOURCE], -4)?>&amp;face=sung&amp;fontsize=512" width=32 height=32><br>
							<?=$rowData[Workbook::T_SOURCE]?>
						<?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::UTC_SOURCE])) {?>
							<img src="<?=EVIDENCE_PATH?>/utc-bitmap/<?=substr($rowData[Workbook::UTC_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[Workbook::UTC_SOURCE]?>
						<? } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::UK_SOURCE])) {?>
						<img src="<?=EVIDENCE_PATH?>/uk-bitmap/<?=$rowData[Workbook::UK_SOURCE]?>.png" width="32" height="32"><br><?=$rowData[Workbook::UK_SOURCE]?>
						<?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::V_SOURCE])) {?>
							<img src="<?=EVIDENCE_PATH?>/v-bitmap/<?=substr($rowData[Workbook::V_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[Workbook::V_SOURCE]?>
						<? } ?>
					</td>
					<td>
						<div class=ws2017_chart_table_discussion>
							<div>
								<? if ($char->sheet) echo '<b>'.CharacterCache::SHEETS[$char->sheet] . '</b><br>'; ?>
								<?=$rowData[Workbook::DISCUSSION_RECORD]?>
							</div>
						</div>
					</td>
				</tr>
<?
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
<?
}
?>
