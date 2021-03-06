<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
}

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>Unified | WS2017v<?=$version?></title>
<link href="common.css" rel=stylesheet type="text/css">
<style>
[hidden]{display:none}
body{font-family:Arial, "Microsoft Jhenghei",sans-serif;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.footer{width:1200px;margin:20px auto}

.ws2015_cutting>img,.ws2015_cutting>canvas{width:auto!important;height:auto!important;max-width:100%}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.ws2015_comments{width:960px;margin:0 auto}
.ws2015_comments table{border-collapse:collapse;width:100%}
.ws2015_comments td,.ws2015_comments th{border:1px solid #000;padding:10px}
.ws2015_comments td{vertical-align:top}
.ws2015_comments th{text-align:left}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}

.sheet-1{background:#ccc}
.sheet-2{background:#ff0}
</style>
<script src="jquery.js"></script>
<body>
<section class=ws2015_comments>
	<h2>Discussion Log for unified characters in WS2017v<?=$version?></h2>
<?php

$adjustedRecords = [
	// strip WS2017 sequence
	'02192' => [ 'WS2017-02150', '02150'],
	'03062' => [ 'WS2017-03060', '03060'],
	'03396' => [ 'WS2017-03390', '03390'],
	'00047' => [ 'WS2017-00046', '00046'],

	// typo
	'00161' => [ 'U+5052', 'U+50B2'],
	'02844' => [ '02855', '02845'],
	'01520' => [ '10521', '01521'],
	'00712' => [ '0000709', '00709'],

	// fix format
	'00395' => [ '呼', 'U+547C'],

	// Extension G
	'01466' => [ 'USAT06427', 'U+30515'],
	'00669' => [ 'UTC-01178', 'U+3025E'],
	'00797' => [ 'KC-00737', 'U+302B1'],
	'02903' => [ 'UK-02658', 'U+30980'],
	'04071' => [ 'GHZR63806.05', 'U+30D9F'],
	'02132' => [ 'T13-2D2B', 'U+30748'],
	'03210' => [ 'T13-2F77', 'U+30A49'],
	'04625' => [ 'T13-314B(ws2015sn04729)', 'U+31057'],
	'01173' => [ 'WS2015-04663', 'U+31020'],
	'02306' => [ 'USAT07218', 'U+30791'],
	'02953' => [ 'T13-2F45', 'U+309AE'],
	'03070' => [ 'ws2015-UK-02654', 'U+30A02'],
];

$list = $sources_cache->getAll();
$sheets = [
	'G' => [],
	'K' => [],
	'SAT' => [],
	'T' => [],
	'UK' => [],
	'UTC' => [],
	'V' => [],
];
foreach ($list as $source) {
	$char = DBCharacters::getCharacter($source, $version);
	if ($char->status !== 1) {
		continue;
	}
	if (strpos(strtolower($char->discussion_record), 'withdraw') !== false) {
		continue;
	}
	if (strpos(strtolower($char->discussion_record), 'withdawn') !== false) {
		continue;
	}

	if (!empty($char->g_source))   $sheets['G'][$char->sn] = $char;
	if (!empty($char->k_source))   $sheets['K'][$char->sn] = $char;
	if (!empty($char->uk_source))  $sheets['UK'][$char->sn] = $char;
	if (!empty($char->sat_source)) $sheets['SAT'][$char->sn] = $char;
	if (!empty($char->t_source))   $sheets['T'][$char->sn] = $char;
	if (!empty($char->utc_source)) $sheets['UTC'][$char->sn] = $char;
	if (!empty($char->v_source))   $sheets['V'][$char->sn] = $char;
}

foreach ($sheets as $sourceName => $list) {
	echo '<a href="#source' . $sourceName . '">' . $sourceName . ' Source</a> ';
}

echo '<hr>';

foreach ($sheets as $sourceName => $list) {
	echo '<h3 id="source' . $sourceName . '">' . $sourceName . '</h3>';
?>
	<table style="table-layout:fixed" border=1>
	<col width=100>
	<col width=280>
	<col width=160>
	<col width=420>
<?php

sort($list);

foreach ($list as $char) {
	$record = $char->discussion_record;
	if (isset($adjustedRecords[$char->sn])) {
		$record = str_replace($adjustedRecords[$char->sn][0], $adjustedRecords[$char->sn][1], $record);
	}

	$unify = preg_match_all('@(^|[^0-9-])((U\\+ ?[0-9A-F]{4,5})|([0-9]{5}))[^0-9]@', strtoupper($record), $matches);
	echo '<tr>';
	echo '<td>' . $char->sn . '</td>';
	echo '<td>';
	echo '<a href="./?id=' . $char->sn . '" target=_blank>';
	$char->renderPart4();
	echo '</a>';
	echo '</td>';
	echo '<td>';
	echo htmlspecialchars($char->discussion_record);
	echo '</td>';
	echo '<td>';
	foreach (array_unique($matches[2]) as $match) {
		try {
			if (ctype_digit($match)) {
				$char = DBCharacters::getCharacter($match);
				$char->renderPart4();
			} else {
				$codepoint = 'U+' . trim(ltrim(substr($match, 2), '0'));
				echo getImageHTML($codepoint, strcmp($version, '5.2') >= 0 ? 13 : 9);
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	echo '</td>';
	echo '</tr>';
}
?>
	</table>
<?php
}
?>
</section>

<div class=footer>
	<p>Source Code released at <a href="https://github.com/hfhchan/irg-ws2017">https://github.com/hfhchan/irg-ws2017</a>.</p>
</div>

<script>
var finalize = (function() {
	$('img').each(function() {
		$(this).attr('width', $(this).width());
	});
});
</script>