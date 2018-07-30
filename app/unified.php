<?php

if (isset($_GET['null'])) {
	header('HTTP/1.1 204 No Content');
	return '';
}

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';

if (!env::$readonly) {
	if (isset($_POST['store'])) {
		if (!preg_match('@^[a-z0-9-_.]+$@', $_POST['store'])) {
			throw new Exception('Invalid filename');
		}
		$data = substr($_POST['data'], strlen('data:image/png;base64,'));
		$data = base64_decode($data);
		file_put_contents('cache/' . $_POST['store'], $data);
	}
}

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>Unified | WS2015v4.0</title>
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
</style>
<script src="jquery.js"></script>
<body>
<section class=ws2015_comments>
	<h2>Unified</h2>
	<table style="table-layout:fixed" border=1>
	<col width=100>
	<col width=280>
	<col width=160>
	<col width=420>
<?

$override = [
	'03345' => 'unified by U+2E28C (JMJ-058343), irg46.',
	'02218' => 'unified to U+24261, irg47.',
	'03863' => 'U+279FF, add to ivd, irg48.',
	'03531' => 'postponed for IVS research, irg47. unified by U+2E39B (JMJ-058438), irg46.',
	
];

$list = $sources_cache->getAll();
foreach ($list as $source) {
	$char = $character_cache->get($source);
	if ($char->sheet !== 1) {
		continue;
	}
	if (strpos(strtolower($char->data[1]), 'withdraw') !== false) {
		continue;
	}

	$record = $char->data[1];
	if (isset($override[$char->data[0]])) {
		$record = $override[$char->data[0]];
	}
	$unify = preg_match_all('@(^|[^0-9-])((U\\+[0-9A-F]{4,5})|([0-9]{5}))[^0-9]@', strtoupper($record), $matches);

	echo '<tr>';
	echo '<td>' . $char->data[0] . '</td>';
	echo '<td>';
	echo '<a href="./?id=' . $char->data[0] . '" target=_blank>';
	$char->renderCodeChartCutting();
	echo '</a>';
	echo '</td>';
	echo '<td>';
	echo htmlspecialchars($record);
	echo '</td>';
	echo '<td>';
	echo '<pre>';
	foreach ($matches[2] as $match) {
		if (ctype_digit($match)) {
			$char = $character_cache->get($match);
			$char->renderCodeChartCutting();
		} else {
			$codepoint = 'U+' . ltrim(substr($match, 2), '0');
			if ($codepoint[2] === '2' && $codepoint[3] === 'F') {
				echo '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
			} else {
				echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
			}
		}
	}
	echo '</pre>';
	echo '</td>';
	echo '</tr>';
}
?>
	</table>
</section>

<div class=footer>
	<p>Source Code released at <a href="https://github.com/hfhchan/irg-ws2015">https://github.com/hfhchan/irg-ws2015</a>.</p>
</div>

<script>
var finalize = (function() {
	$('img').each(function() {
		$(this).attr('width', $(this).width());
	});
});
</script>