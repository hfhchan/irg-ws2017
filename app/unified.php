<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>Unified | WS2017v<?=Workbook::VERSION?></title>
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
	<h2>Unified</h2>
	<table style="table-layout:fixed" border=1>
	<col width=100>
	<col width=280>
	<col width=160>
	<col width=420>
<?php

$override = [
	
];

$list = $sources_cache->getAll();
foreach ($list as $source) {
	$char = DBCharacters::getCharacter($source);
	if ($char->status !== 1) {
		continue;
	}
	if (strpos(strtolower($char->discussion_record), 'withdraw') !== false) {
		continue;
	}

	$record = $char->discussion_record;
	if (isset($override[$char->sn])) {
		$record = $override[$char->sn];
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
	echo htmlspecialchars($record);
	echo '</td>';
	echo '<td>';
	foreach (array_unique($matches[2]) as $match) {
		try {
			if (ctype_digit($match)) {
				$char = DBCharacters::getCharacter($match);
				$char->renderPart4();
			} else {
				$codepoint = 'U+' . trim(ltrim(substr($match, 2), '0'));
				echo getImageHTML($codepoint);
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