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
<meta name=viewport content="width=1200">
<title>Variants variants | WS2017v<?=Workbook::VERSION?></title>
<style>
[hidden]{display:none}
body{font-family:Arial,"Source Han Serif KR","HanaMinA","HanaMinB","HanaMinC","Microsoft Yahei",sans-serif;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.group{background:#eee;margin:20px auto;padding:20px;max-width:1360px}

.ws2017_variant_header{table-layout:fixed;width:100%;font-size:32px}
.ws2017_variant_footer{table-layout:fixed;width:100%}

.ws2017_variant_header .anchor{font-size:.5em;color:#999;margin-right:4px;text-decoration:none}

.ws2017_variant_table_outer{margin:.5cm 0}
.ws2017_variant_table{border:.38mm solid #333;border-collapse:collapse;table-layout:fixed;width:100%;text-align:center;box-sizing:border-box}
.ws2017_variant_table td{border:.38mm solid #333;padding:0;font-size:13px;height:2.0cm}
.ws2017_variant_table thead td{height:.6cm}

.ws2017_variant_sn{padding:10px;display:grid;align-items:center;font-size:16px}
.ws2017_variant_sn a{color:#000;text-decoration:none}
.ws2017_variant_sn a:hover{color:blue;text-decoration:underline}
.ws2017_variant_attributes{padding:0!important;height:2.0cm}
.ws2017_variant_attributes{display:grid;grid-template-rows:1fr 1fr 1fr;font-size:16px}
.ws2017_variant_attributes>div:first-child{border-top:none}
.ws2017_variant_attributes>div{border-top:.38mm solid #333}
.ws2017_variant_attributes span+span{margin-left:1px}

.ws2017_variant_table_discussion{display:grid;align-content:center;text-align:left!important;padding:10px;overflow:auto}

.sheet-1{background:#999;opacity:.6}
.sheet-2{background:#ff0}

.ws2017_cutting{margin-left:225px}
.ws2017_cutting img,.ws2017_cutting canvas{width:577px}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

#page_selector{margin:10px auto;width:24cm}
#page_selector a{display:inline-block}
.page{margin:20px auto;box-shadow:0 0 0 1px #999, 0 0 5px #999;padding:.5cm;width:1280px;page-break-inside:avoid;background:#fff}
</style>
<script src="jquery.js"></script>
<body>
<div class=page>
	<p><strong>This list of variants is based on information provided by the submitter.  It may not be free of error; or the variant listed may not be the closest match.</strong></p>
</div>
<?php
define('EVIDENCE_PATH', '../data');
?>
<?php


function processGroupName($similar) {
	
	if ($similar === 'none') {
		return '';
	}

	preg_match('#U\\+0([0-9A-F]{4})#', $similar, $matches);
	if ($matches) {
		$similar = str_replace($matches[0], 'U+' . $matches[1], $similar);
	}

	$replace = [];
	$similar = preg_replace_callback('@([\xE0-\xEF][\x80-\xbf][\x80-\xbf])|([\xF0-\xF7][\x80-\xbf][\x80-\xbf][\x80-\xbf])@', function($m) use (&$replace) {
		list($codepoint) = parseStringIntoCodepointArray($m[0]);
		$replace[$codepoint] = '';
		$replace['('.$codepoint.')'] = '';
		if (strlen($codepoint) === 6) {
			$replace['U+0' . substr($codepoint, 2)] = '';
			$replace['(U+0' . substr($codepoint, 2).')'] = '';
		}
		return $m[0];
	}, $similar);
	$similar = strtr($similar, $replace);

	// Convert Codepoint to Char only
	$similar = preg_replace_callback('@U\+0?([0-9A-Fa-f]{4,5})@', function($m) {
		return codepointToChar('U+' . $m[1]);
	}, $similar);

	$similar = preg_replace_callback('@Similar and synonym: ([^/]*)(/.*)?$@', function($m) {
		return $m[1];
	}, $similar);

	$similar = preg_replace_callback('@Non-similar and synonym: ([^/]*)(/.*)?$@', function($m) {
		return $m[1];
	}, $similar);

	$similar = preg_replace_callback('@variant of (.*)@', function($m) {
		return $m[1];
	}, $similar);

	$similar = preg_replace_callback('@varinat of (.*)@', function($m) {
		return $m[1];
	}, $similar);

	$similar = trim($similar);

	return $similar;
}

$trad_simp = [
	'00377',
	'00394',
	'00413',
	'00418',
	'00419',
	'00433',
	'00445',
	'00462',
	'00466',
	'00475',
	'00485',
	'00487',
	'00511',
	'00527',
	'00539',
	'00666',
	'00670',
	'00697',
	'00703',
	'00709',
	'00712',
	'00719',
	'01922',
	'02282',
	'02107',
	'01568',
	'01604',
	'01627',
	'01681',
	'01693',
	'01707',
	'01712',
	'01716',
	'01758',
	'01769',
	'01777',
	'01908',
	'01953',
	'01996',
	'02026',
	'02139',
	'02153',
	'02181',
	'02277',
	'02287',
	'02307',
	'02321',
	'02325',
	'02353',
	'02363',
	'02399',
	'02407',
	'02414',
	'02480',
	'02482',
	'02589',
	'02598',
	'02601',
	'02617',
	'02862',
	'02874',
	'03268',
	'03272',
	'03275',
	'03278',
	'03281',
	'03293',
	'03308',
	'03346',
	'03357',
	'03367',
	'03469',
	'03475',
	'03478',
	'03479',
	'03484',
	'03485',
	'03487',
	'04535',
	'04551',
	'04553',
	'04620',
	'04728',
	'04791',
	'04793',
	'04794',
	'04797',
	'01267',
	'01268',
	'01389',
	'01084',
	'03386',
	'04436',
	'01085',
	'01672',
	'01279',
	'01319',
	'01747',
	'01654',
	'01680',
	'01667',
	'02760',
	'04102',
	'03987',
	'04433',
	'04443',
	'01686',
	'02972',
	'04467',
	'03862',
	'03854',
	'04466',
	'02973',
	'03921',
	'03986',
	'03888',
	'03886',
	'03871',
	'03421',
	'03985',
	'04107',
	'03843',
	'04099',
	'04255',
	'04279',
	'04280',
	'04281',
	'04292',
	'04398',
];
$non_cognate = [
	'02274',
	'03851',
	'00004',
	'02694',
	'01726',
	'01744',
	'01778',
	'01794',
	'01892',
	'01920',
	'02060',
	'02267',
	'02595',
	'02724',
	'03973',
	'02731',
	'02779',
	'02833',
	'02838',
	'03869',
	'04608',
];

$groups = [];

foreach ($chars as $char) {
	foreach (Workbook::SIMILAR as $sim) {
		if (trim($char->data[$sim]) === '') {
			continue;
		}
		$group_name = processGroupName($char->data[$sim]);
		if (trim($group_name) === '') {
			continue;
		}
		if (!isset($groups[$group_name])) {
			$groups[$group_name] = [];
		}
		$groups[$group_name][$char->data[0]] = $char;
	}
} unset($chars);

$big_group = [
	'澹' => 1,
	'瞻' => 1,
	
	'奐' => 2,
	'煥' => 2,
	'渙' => 2,
	
	'氣' => 3,
	'氛' => 3,
	'氳' => 3,
	
	'留' => 4,
	'溜' => 4,

	// 墻
	'穡' => 5,
	// 方
	'旌' => 6,
	'旋' => 6,
	'游' => 6,
	// 隱
	'斷' => 7,
	'匹' => 7,
	'匯' => 7,
	'匣' => 7,
	// 欠
	'歡' => 8,
	'欽' => 8,
	//孟
	'孟' => 9,
	'猛' => 9,
	//登
	'登' => 10,
	'澄' => 10,
	//嬴
	'嬴' => 11,
	'瀛' => 11,
	//𨷶
	'𨷶' => 12,
	'𨷯' => 12,
	//
	'瀉' => 13,
	
	'滯' => 14,
	'沼' => 14,
	'洪' => 14,
	'濾' => 14,
	
	'浚' => 15,
	
	'桃' => 16,
	
	'沷' => 18,

	'疇' => 19,
	'濤' => 19,
	
	'逬' => 20,
	'拼' => 20,
	'栟' => 20,
	'絣' => 20,
	'硑' => 20,
	
];
?>

<?
$big_groups_unique = array_unique(array_values($big_group));
foreach ($big_groups_unique as $g_id) {
	echo '<div id=group_' . $g_id . ' class=group><div class=group_header>Group '.$g_id.'</div></div>' . "\r\n";
}
echo '<div id=group_0 class=group><div class=group_header>Others</div></div>' . "\r\n";
?>

<?
uasort($groups, function($a, $b) {
	return count($b) - count($a);
});


foreach ($groups as $group_name => $group) {
	$filtered_group = array_filter($group, function($char) use ($trad_simp, $non_cognate) {
		return !in_array($char->data[0], $trad_simp) && !in_array($char->data[0], $non_cognate);
	});
	if (count($filtered_group) === 0) {
		continue;
	}

	$big_group_id = isset($big_group[$group_name]) ? $big_group[$group_name] : 0;
	
	ob_start();
?>
	<div class=page data-group-id="<?=$big_group_id?>" id=<?=html_safe($group_name)?>>
		<table class=ws2017_variant_header>
			<tr>
				<td align=left></td>
				<td align=center><a href="#<?=html_safe($group_name)?>" class=anchor>#</a><b><?=html_safe($group_name)?></b> (<?=count($filtered_group)?> variants)</td>
				<td align=right></td>
			</tr>
		</table>
		<div class=ws2017_variant_table_outer>
			<table class=ws2017_variant_table>
				<col style="width:2cm">
				<col style="width:6cm">
				<col>
				<col>
				<col>
				<col>
				<col>
				<col>
				<col>
				<col style="width:8.8cm">
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
		
		foreach ($filtered_group as $char) {
			Log::add('Render Char Start ' . $char->data[0]);
			$rowData  = $char->data;
			$sq_number = $char->data[0];

?>
				<tr class="sheet-<?=$char->sheet?>">
					<td><div class=ws2017_variant_sn style="padding:10px;display:grid;align-items:center">
						<a href="index.php?id=<?=$rowData[0]?>" target=_blank><?=$rowData[0]?></a>
						<br>
						<?=$rowData[Workbook::TS_FLAG] ? '簡' : '繁';?>
					</div></td>
					<td>
						<div class=ws2017_variant_attributes style="display:grid;grid-template-rows:1fr 1fr 1fr">
							<div style="display:grid;align-items:center"><?=$char->getRadicalStroke()?></div>
							<div style="display:grid;align-items:center;white-space:nowrap"><div><?php
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
							<img src="<?=EVIDENCE_PATH?>/g-bitmap/<?=substr($rowData[Workbook::G_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
							<?=$rowData[Workbook::G_SOURCE]?>
						<?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::K_SOURCE]) || isset($rowData[Workbook::K_SOURCE+1])) {?>
							<img src="<?=EVIDENCE_PATH?>/k-bitmap/<?=substr($rowData[Workbook::K_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
							<?=$rowData[Workbook::K_SOURCE]?><?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::SAT_SOURCE]) || isset($rowData[Workbook::SAT_SOURCE+1])) {?>
							<img src="<?=EVIDENCE_PATH?>/sat-bitmap/<?=substr($rowData[Workbook::SAT_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
							<?=$rowData[Workbook::SAT_SOURCE]?>
						<?php } ?>
					</td>
					<td>
						<?php if (isset($rowData[Workbook::T_SOURCE]) || isset($rowData[Workbook::T_SOURCE + 1])) {?>
							<img src="<?=EVIDENCE_PATH?>/t-bitmap/<?=substr($rowData[Workbook::T_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
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
						<div class=ws2017_variant_table_discussion>
							<div>
								<? if ($char->sheet) echo '<b>'.CharacterCache::SHEETS[$char->sheet] . '</b><br>'; ?>
								<?=$rowData[Workbook::DISCUSSION_RECORD]?>
							</div>
						</div>
					</td>
				</tr>
<?php
		}
?>
			</table>
		</div>
	</div>
<?php
	$html = ob_get_clean();
?>
<script>
document.getElementById('group_<?=$big_group_id?>').insertAdjacentHTML('beforeend', <?=json_encode($html);?>);
</script>
<?
}
?>

<div class=page>
	<p>Following are charcters skipped in this list due to:</p>
	<p>Derived Trad/Simp: <?=implode(', ', array_map(function($row) use ($character_cache) {
		$char = $character_cache->get($row);
		return '<a href="index.php?id=' . $row . '">' . $row . '</a>';
	}, $trad_simp));?></p>
	<p>Non-cognate: <?=implode(', ', array_map(function($row) use ($character_cache) {
		$char = $character_cache->get($row);
		return '<a href="index.php?id=' . $row . '">' . $row . '</a>';
	}, $non_cognate));?></p>
</div>

