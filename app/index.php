<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

$sources_cache   = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache       = new IDSCache();

if (!env::$readonly) {
	// Run once to generate the attribute cache!
	if (isset($_GET['generate_cache'])) {
		set_time_limit(60);
		mkdir('../data/attributes-cache/');
		$character_cache->generate();
		$sources_cache->generate();
		$ids_cache->generate();
	}

	if (!file_exists('../data/attributes-cache/')) {
		throw new Exception('Run ?generate_cache first to populate the cache.');
	}

	// New Comment
	if (isset($_POST['action']) && $_POST['action'] === 'comment') {
		if (isset($_POST['comment']) && isset($_POST['sq_number']) && isset($_POST['type'])) {
			if (isset($_POST['user_id']) && $_POST['user_id'] == $session->getUser()->getUserId()) {
				if ($_POST['type'] === 'KEYWORD' && empty($_POST['comment'])) {
					throw new Exception('Keyword cannot be empty!');
				}
				DBComments::save($_POST['sq_number'], $_POST['type'], $_POST['comment'], $session->getUser()->getUserId());
				header('Location: ' . $_SERVER['REQUEST_URI']);
				exit;
			} else {
				throw new Exception('Post $user and Logged In user mismatch!');
			}
		}
	}
	if (isset($_POST['action']) && $_POST['action'] === 'action') {
		if ($session->getUser()->isAdmin()) {
			DBActions::save($_POST['sq_number'], $_POST['type'], trim($_POST['value']), $_POST['session']);
		} else {
			throw new Exception('Not Admin!');
		}
	}
}


$router = new Router($sources_cache, $character_cache, $ids_cache);

// Get Prev Unprocessed
if (isset($_GET['left']) && isset($_GET['find'])) {
	$prev = $router->getLeftOfSource($_GET['find']);
	header('Location: ?find=' . $prev);
	exit;
}

if (isset($_GET['left']) && isset($_GET['id'])) {
	$prev = $router->getLeftOfSerialNumber($_GET['id']);
	header('Location: ?id=' . $prev);
	exit;
}

// Get Next Unprocessed
if (isset($_GET['right']) && isset($_GET['find'])) {
	$next = $router->getRightOfSource($_GET['find']);
	header('Location: ?find=' . $next);
	exit;
}
if (isset($_GET['right']) && isset($_GET['id'])) {
	$next = $router->getRightOfSerialNumber($_GET['id']);
	header('Location: ?id=' . $next);
	exit;
}

if (!Env::$readonly && isset($_GET['mark']) && isset($_GET['id'])) {
	if (isset($_GET['keyword'])) {
		$base = '?keyword=' . $_GET['keyword'];
	} else if (isset($_GET['find'])) {
		$base = '?find=' . $_GET['find'];
	} else if (isset($_GET['ids'])) {
		$base = '?ids=' . $_GET['ids'];
	} else {
		$base = '?id=' . $_GET['id'];
	}
	$char = $character_cache->get($_GET['id']);	
	if ($_GET['mark'] == 3) {
		$char->setReviewedUnification();
		$char->setReviewedAttributes();
	}
	if ($_GET['mark'] == 1) {
		$char->setReviewedUnification();
	}
	if ($_GET['mark'] == 2) {
		$char->setReviewedAttributes();
	}
	header('Location: ' . $base);
	exit;
}

if (!Env::$readonly && isset($_GET['add_strokes'])) {
	if (isset($_GET['keyword'])) {
		$base = '?keyword=' . $_GET['keyword'];
	} else if (isset($_GET['find'])) {
		$base = '?find=' . $_GET['find'];
	} else if (isset($_GET['ids'])) {
		$base = '?ids=' . $_GET['ids'];
	} else {
		$base = '?id=' . $_GET['id'];
	}
	if (preg_match('@^U\+[0-9A-F]?[0-9A-F][0-9A-F][0-9A-F][0-9A-F] [0-9]+\|[0-9]+$@', $_GET['add_strokes'])) {
		$fp = fopen('../totalstrokes.txt', 'a');
		fwrite($fp, $_GET['add_strokes'] . "\r\n");
		fclose($fp);
	} else {
		die('Format Mismatch');
	}
	header('Location: ' . $base);
	exit;
}

$firstRow = $character_cache->getColumns();

Log::add('Fetch Char');

$data = [];
if (isset($_GET['ids'])) {
	$_GET['ids'] = trim(strtr($_GET['ids'], [' ' => '']));
	if (!empty($_GET['ids'])) {
		$result = $ids_cache->find($_GET['ids']);
		if (empty($result)) {
			throw new NotFoundException('Not Found');
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->get($sq_number);
			$sources = $char->getAllSources();
			if (count($sources) === 1) {
				$prev = $sources_cache->findPrev($sources[0]);
				$next = $sources_cache->findNext($sources[0]);

				$char->prev2 = '?left&find=' . $prev;;
				$char->prev = [$prev, '?find=' . $prev];
				$char->curr = $sources[0];
				$char->next = [$next, '?find=' . $next];
				$char->next2 = '?right&find=' . $next;
				$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($sources[0]);
			} else {
				$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
				$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

				$char->prev2 = '?left&id=' . $sq_number;
				$char->prev = [$prev, '?id=' . $prev];
				$char->curr = $sq_number;
				$char->next = [$next, '?id=' . $next];
				$char->next2 = '?right&id=' . $sq_number;
				$char->base_path = '?id=' . urlencode($sq_number) . '&ids=' . urlencode($_GET['ids']);
			}

			$data[] = $char;
		}
	}
} else if (isset($_GET['keyword'])) {
	if (!empty($_GET['keyword'])) {
		$result = DBComments::getByKeyword($_GET['keyword']);
		if (empty($result)) {
			throw new NotFoundException('Not Found');
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->get($sq_number);
			$sources = $char->getAllSources();
			if (count($sources) === 1) {
				$prev = $sources_cache->findPrev($sources[0]);
				$next = $sources_cache->findNext($sources[0]);

				$char->prev2 = '?left&find=' . $prev;;
				$char->prev = [$prev, '?find=' . $prev];
				$char->curr = $sources[0];
				$char->next = [$next, '?find=' . $next];
				$char->next2 = '?right&find=' . $next;
				$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($sources[0]);
			} else {
				$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
				$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

				$char->prev2 = '?left&id=' . $sq_number;
				$char->prev = [$prev, '?id=' . $prev];
				$char->curr = $sq_number;
				$char->next = [$next, '?id=' . $next];
				$char->next2 = '?right&id=' . $sq_number;
				$char->base_path = '?id=' . urlencode($sq_number) . '&ids=' . urlencode($_GET['ids']);
			}

			$data[] = $char;
		}
	} else {
		throw new NotFoundException('No Keyword Specified');
	}
} else if (isset($_GET['find']) || !isset($_GET['id'])) {
	if (empty($_GET['find'])) {
		$_GET['find'] = $sources_cache->getFirst();
	}
	$_GET['find'] = trim(strtr($_GET['find'], [' ' => '']));
	if (!empty($_GET['find'])) {
		if (!preg_match('@^[A-Za-z0-9-_\\.]+$@', $_GET['find'])) {
			throw new Exception('Invalid ID');
		}
		$result = $sources_cache->find($_GET['find']);
		if (empty($result)) {
			echo '<div><h1>List of prefixes:</h1>';
			$keys = $sources_cache->getKeys();
			$prefix = 'XXXX';
			foreach ($keys as $key) {
				if (strncmp($key, $prefix, strlen($prefix)) === 0) {
					continue;
				}
				if (strpos($key, '-')) {
					list($prefix, $junk) = explode('-', $key);
				}
				preg_match('@([A-Z_]+)@', $key, $matches);
				$prefix = $matches[1];
				echo '<div><a href="?find=' . htmlspecialchars($key) . '">' . htmlspecialchars($key) . '</a></div>';
			}
			echo '</div>';
			throw new NotFoundException('Not Found');
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->get($sq_number);
			$prev = $sources_cache->findPrev($_GET['find']);
			$next = $sources_cache->findNext($_GET['find']);

			$char->prev2 = '?left&find=' . $prev;;
			$char->prev = [$prev, '?find=' . $prev];
			$char->curr = $_GET['find'];
			$char->next = [$next, '?find=' . $next];
			$char->next2 = '?right&find=' . $next;
			$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($_GET['find']);
			$data[] = $char;
		}
	}
} else if (isset($_GET['id'])) {
	$_GET['id'] = trim($_GET['id']);
	if (!preg_match('@^[0-9]{5}$@', $_GET['id'])) {
		throw new Exception('Invalid ID');
	}
	$sq_number = trim($_GET['id']);
	$char = $character_cache->get($sq_number);

	$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
	$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

	$char->prev2 = '?left&id=' . $sq_number;
	$char->prev = [$prev, '?id=' . $prev];
	$char->curr = $sq_number;
	$char->next = [$next, '?id=' . $next];
	$char->next2 = '?right&id=' . $sq_number;
	$char->base_path = '?id=' . urlencode($sq_number);

	$data[] = $char;
}

Log::add('Fetch Char End');

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>
<?php
	if (isset($_GET['ids'])) {
		echo 'IDS Lookup ' . htmlspecialchars($_GET['ids']);
	} else if (isset($_GET['find'])) {
		echo htmlspecialchars(trim($_GET['find']));
	} else {
		echo $data[0]->data[0] . ' | ' . $data[0]->data[Workbook::IDS];
	}
?> | WS2017v<?=Workbook::VERSION?></title>
<style>
[hidden]{display:none}
body{font-family:Arial, "Microsoft Jhenghei",sans-serif;background:#eee;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.ws2017_char{width:1160px;padding:20px;margin:10px auto;background:#fff;border:1px solid #ccc}
.ws2017_char_nav{font-size:24px;display:grid;grid-template-columns:auto 1fr 1fr 1fr auto;background:#def;margin:-20px -20px 20px;align-items:center;border-bottom:1px solid #ccc}
.ws2017_char_nav a{display:block;padding:10px 20px;color:#009;text-decoration:none}
.ws2017_char_nav a:hover{background:#cce3ff}

.ws2017_chart_table{border:1px solid #333;display:grid;grid-template-columns:84px 140px 1fr 358px}
.ws2017_chart_table>div{text-align:center;font-size:16px}
.ws2017_chart_table>div:not(:first-child){border-left:1px solid #333}
.ws2017_chart_sn{padding:10px;display:grid;align-items:center}
.ws2017_chart_attributes{display:grid;grid-template-rows:1fr 1fr 1fr}
.ws2017_chart_attributes{padding:0!important}
.ws2017_chart_attributes>div{display:grid;align-items:center}
.ws2017_chart_attributes>div:not(:first-child){border-top:1px solid #333}
.ws2017_chart_attributes_strokes{display:grid;grid-template-columns:1fr 2fr}
.ws2017_chart_attributes_strokes>div{display:grid;align-items:center}
.ws2017_chart_attributes_strokes_fs{border-right:1px solid #333}

.ws2017_chart_table_sources{width:100%;table-layout:auto;border-collapse:collapse;border:hidden;font-size:13px}
.ws2017_chart_table_sources td{border:1px solid #333;padding:10px 5px;text-align:center;min-width:60px}
.ws2017_chart_table_discussion{display:grid;align-content:center;text-align:left!important;padding:10px;overflow:auto}
.ids_component{margin:0 2px}
.sheet-1{background:#999;opacity:.6}
.sheet-2{background:#ff0}

.ws2017_cutting{margin-left:225px}
.ws2017_cutting img,.ws2017_cutting canvas{width:577px}

.ws2017_content{display:grid;grid-template-columns:802px 1fr;grid-column-gap:20px;margin-top:10px}

.ws2017_similar_char{border:1px solid #ccc;padding:10px;margin-bottom:10px}
.ws2017_evidence a.evidence_image{border:1px solid #ccc;display:block;background:#fcfcfc}
.ws2017_evidence img{display:block;max-width:800px;max-height:400px;object-fit:scale-down;margin:10px auto;background:#fff}
.ws2017_evidence img.full{max-height:none}
.ws2017_evidence iframe.full{border:1px solid #ccc}

.ws2017_right{}
.ws2017_right h2{margin:10px 0}

a.review{font-size:16px;border:1px solid #ccc;padding:4px 20px;display:block;margin:10px auto;text-align:center;max-width:200px;background:#eee;text-decoration:none;color:#03c;font-weight:bold}
a.review_all{font-size:16px;border:1px solid #ccc;padding:4px 12px;display:block;margin:10px auto;text-align:center;max-width:200px;background:#eee;text-decoration:none;color:#03c;font-weight:bold}


#accountbar{padding:10px;margin-top:10px;text-align:right}
#accountbar>div{width:1160px;margin:0 auto}

#findbar{padding:10px;background:#fff;border-bottom:1px solid #ccc}
#findbar>div{width:1160px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;justify-items:center}
#findbar form{display:flex}
#findbar form>div{margin-right:5px;flex-shrink:none;flex-grow:none}
#search-1,#search-2,#search-3,#search-4{border:1px solid #999;padding:2px 4px}
#find-1,#find-2,#find-3,#find-4{background:#eee;color:#000;border:1px solid #ccc;padding:2px 12px;line-height:1.2}

.footer{width:1200px;margin:20px auto}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.ws2017_comments{margin:0 auto}
.ws2017_comments table{border-collapse:collapse;width:100%}
.ws2017_comments td,.ws2017_comments th{border-top:1px solid #ccc;padding:10px}
.ws2017_comments td{vertical-align:top}
.ws2017_comments th{text-align:left}
.ws2017_comments form{max-width:720px;margin:0 auto}
.ws2017_comments .ws2017_cutting{margin:0}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}
.comment_submit{font-size:20px;border:none;padding:4px 20px;min-width:200px;text-align:center;background:#9cf;display:block;margin:10px 0;font-family:inherit}


.ws2017_actions{margin:0 auto}
.ws2017_actions table{border-collapse:collapse;width:100%}
.ws2017_actions td,.ws2017_actions th{border-top:1px solid #ccc;padding:10px}
.ws2017_actions td{vertical-align:top}
.ws2017_actions th{text-align:left}
.ws2017_actions form{max-width:720px;margin:0 auto}
.ws2017_actions .ws2017_cutting{margin:0}

.action_block{font-size:24px}
.action_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.action_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}
.action_submit{font-size:20px;border:none;padding:4px 20px;min-width:200px;text-align:center;background:#9cf;display:block;margin:10px 0;font-family:inherit}

@media (min-width:810px) {
	.ws2017_chart_table_sources{border-left:1px solid #333}
}
@media (max-width:800px) {
	#findbar > div{display:block;width:auto}
	.ws2017_char_nav{font-size:24px}
	.ws2017_char_nav a{padding:5px}
	.ws2017_char{width:auto;padding:10px}
	.ws2017_char_nav{margin:-10px -10px 10px}
	.ws2017_chart_table{grid-template-columns:1fr 2fr;grid-template-rows:minmax(60pt, auto) auto auto}
	.ws2017_chart_table_discussion{grid-row:2;grid-column:1 / 3;border-top:1px solid #333;border-left:none!important}
	.ws2017_chart_table_sources{grid-row:3;grid-column:1 / 3;border-top:1px solid #333;table-layout:auto}
	.ws2017_chart_table_sources td{white-space:nowrap;width:20%;min-width:0;font-size:13px}
	.ws2017_cutting{margin-left:0}
	.ws2017_cutting canvas{width:100%;height:auto}
	.ws2017_cutting img{width:100%;height:auto}
	.ws2017_content{display:block}
	.ws2017_evidence img{max-width:calc(100% - 2px)}
	.footer{width:auto;padding:0 20px}
	.ws2017_similar_char>img{width:100%;display:block}
}
@media (max-width:620px) {
	.ws2017_char_nav{font-size:12px;line-height:24px}
	.ws2017_char_nav a{padding:2px 5px}
	#nav_next{text-align:right}
	
	.ws2017_comments table{display:block;border:none}
	.ws2017_comments thead{display:none}
	.ws2017_comments tbody{display:block;border:none}
	.ws2017_comments tr{display:block;border:1px solid #ccc;width:auto;margin:10px 0}
	.ws2017_comments td{display:block}
}
</style>
<script src="jquery.js"></script>
<body>
<div id=findbar>
	<div>
		<div>
			<form method=get autocomplete=off style="display:flex" id=search-char-1 role=search>
				<div style="width:160px">Find by Source (f):</div>
				<div><input id=search-1 type=text name=find value="<?=html_safe(isset($_GET['find']) ? $_GET['find'] : '')?>" accesskey=f></div>
				<div><input id=find-1 type=submit value=Find></div>
			</form>
			<form method=get autocomplete=off style="display:flex" id=search-char-2 role=search>
				<div style="width:160px">Find by Serial No (s):</div>
				<div><input id=search-2 name=id value="<?=html_safe(isset($_GET['id']) ? $_GET['id'] : '')?>" accesskey=s></div>
				<div><input id=find-2 type=submit value=Find></div>
			</form>
		</div>
		<div>
			<form method=get autocomplete=off style="display:flex" id=search-char-3 role=search>
				<div style="width:160px">Find by IDS (i):</div>
				<div><input id=search-3 name=ids value="<?=html_safe(isset($_GET['ids']) ? $_GET['ids'] : '')?>" accesskey=i></div>
				<div><input id=find-3 type=submit value=Find></div>
			</form>
			<form method=get autocomplete=off style="display:flex" id=search-char-4 role=search>
				<div style="width:160px">Find by Keyword (k):</div>
				<div><input id=search-4 name=keyword value="<?=html_safe(isset($_GET['keyword']) ? $_GET['keyword'] : '')?>" accesskey=k></div>
				<div><input id=find-4 type=submit value=Find></div>
			</form>
		</div>
	</div>
</div>

<div id=accountbar>
	<div>
<? if ($session->getUser()) { ?>
		Logged In as <?=$session->getUser()->getName()?> - <a href="admin.php">Admin Panel</a>
<? } else { ?>
		<a href="admin.php">Login</a>
<? } ?>
	</div>
</div>

<?php
if (Env::$readonly) {
	//define('EVIDENCE_PATH', 'https://raw.githubusercontent.com/hfhchan/irg-ws2017/5d22fba4/data');
	define('EVIDENCE_PATH', '../data');
} else {
	define('EVIDENCE_PATH', '../data');
}

foreach ($data as $char) {
	Log::add('Render Char Start ' . $char->data[0]);
	$rowData  = $char->data;
	$sq_number = $char->data[0];
	

?>

<div class=ws2017_char>
	<div class=ws2017_char_nav>
<? if ($char->prev2) { ?>
		<div><a href="<?=$char->prev2?>" id=nav_prev>&laquo;</a></div>
<? } ?>
		<div align=left><a href="<?=$char->prev[1]?>" accesskey=p><?=$char->prev[0]?></a></div>
		<div align=center><b><?=$char->curr?></b></div>
		<div align=right><a href="<?=$char->next[1]?>" accesskey=n><?=$char->next[0]?></a></div>
<? if ($char->next2) { ?>
		<div><a href="<?=$char->next2?>" id=nav_next>&raquo;</a></div>
<? } ?>
	</div>

	<h2 hidden>Character Info</h2>
	<div class="ws2017_chart_table sheet-<?=$char->sheet?>">
<?php
	$char->renderPart1();
	$char->renderPart2();
	$char->renderPart3();
?>
	</div>
	
	<? $char->renderCodeChartCutting(); ?>
	<? $char->renderPDAM2_2(); ?>

	<div class=ws2017_content>
		<section class=ws2017_left>
<?php
	$matched = $char->getMatchedCharacter();
	if ($matched && substr($matched, 0, 1) !== '&') {
		echo '<p style="background:red;font-size:24px;margin:10px 0;padding:10px;color:#fff">Exact Match: <a href="/unicode/fonts/gen-m.php?name=' . ($matched) . '" target=_blank style="color:#fff">' . $matched . ' (' . charToCodepoint($matched) . ')</a></p>';
	}
?>

<?php
	$codepoints = [];
	preg_replace_callback('@U\+0?([0-9A-Fa-f]{4,5})@', function($m) use (&$codepoints) {
		$codepoint = 'U+' . $m[1];
		$codepoints[] = $codepoint;
	}, $rowData[1]);

	$similar = '';
	foreach (Workbook::SIMILAR as $sim) {
		$similar .= html_safe($rowData[$sim]);
	}
	if (!empty($rowData[Workbook::UK_TRAD_SIMP])) {
		if ($rowData[Workbook::TS_FLAG]) {
			$similar .= ' // Simplified Form of '.$rowData[Workbook::UK_TRAD_SIMP];
		} else {
			$similar .= ' // Traditional Form of '.$rowData[Workbook::UK_TRAD_SIMP];
		}
	}

	$similar = str_replace("\xe3\x80\x80", ' ', $similar);

	// Convert Codepoint + Char to Char only
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
	$similar = preg_replace_callback('@U\+0?([0-9A-Fa-f]{4,5})@', function($m) use (&$codepoints) {
		$codepoint = 'U+' . $m[1];
		$codepoints[] = $codepoint;
		return codepointToChar($codepoint);
	}, $similar);

	// Convert Char to link
	$similar = preg_replace_callback('@([\xE0-\xEF][\x80-\xbf][\x80-\xbf])|([\xF0-\xF7][\x80-\xbf][\x80-\xbf][\x80-\xbf])@', function($m) use (&$codepoints) {
		$m = parseStringIntoCodepointArray($m[0]);
		$m[1] = substr($m[0], 2);
		$codepoint = $m[0];
		$codepoints[] = $codepoint;
		return '<a href="../../../fonts/gen-m.php?name=u'.$m[1].'" target=_blank>'.codepointToChar($codepoint).' ('.$codepoint.')</a>';
	}, $similar);


	if (!empty($similar)) {
		echo '<div class=ws2017_similar_char>';
		echo 'Similar To: ';	
		echo $similar;
		echo '</div>';
	}
	if (!empty($codepoints)) {
		$codepoints = array_values(array_unique($codepoints));
		foreach ($codepoints as $codepoint) {
			echo '<div class=ws2017_similar_char>';
			if (!env::$readonly) echo '<a href="../../../fonts/gen-m.php?name=u'.strtolower(substr($codepoint, 2)).'" target=_blank style="margin-right:10px">';
			else echo '<span>';
			echo '<img src="https://glyphwiki.org/glyph/hkcs_m'.strtolower(substr($codepoint, 2)).'.svg" alt="'.$codepoint.'" height=72 width=72 style="vertical-align:top">';
			if (!env::$readonly) echo '</a>';
			else echo '</span>';
			echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
			echo '</div>';
		}
	}
?>
<div class=ws2017_evidence>

<div id=evidence<?=$rowData[0]?>></div>
<script>
(() => {
	let insertion_point = document.getElementById('evidence<?=$rowData[0]?>');
	let evidence_fields = <?=json_encode(Workbook::getFields())?>;
	let char_data = <?=json_encode($rowData)?>;
	Object.keys(evidence_fields).forEach((region) => {
		let indices = evidence_fields[region];
		if (char_data[indices[0]]) {
			let source = char_data[indices[0]];
			let file = char_data[indices[1]];
			let evidence_name = '';
			if (indices[2]) {
				if (typeof indices[2] === 'number') {
					evidence_name = char_data[indices[2]];
				} else {
					evidence_name = indices[2].map((i) => char_data[i]).join(' ');
				}
				let div = document.createElement('div');
				div.innerText = evidence_name;
				div.style.backgroundColor = '#eee';
				div.style.padding = '10px';
				if (region === 'T') {
					let a = document.createElement('a');
					a.href = "https://www.cns11643.gov.tw/AIDB/query_general_view.do?page=" + source.substring(1, source.length - 5) + "&code=" + source.substring(source.length - 4);
					a.target = "_blank";
					a.textContent = "Info on CNS11643.gov.tw";
					a.style.float = 'right';
					a.style.color = 'blue';
					div.appendChild(a);
				}
				if (region === 'K') {
					let code = source.substring(3);
					let a = document.createElement('a');
					a.href = "http://www.koreanhistory.or.kr/newchar/grid_list.jsp?code_type=3&codebase=KC" + code;
					a.target = "_blank";
					a.textContent = "Info on koreanhistory.or.kr";
					a.style.float = 'right';
					a.style.color = 'blue';
					div.appendChild(a);
				}
				insertion_point.appendChild(div);
			}
			if (indices[3]) {
				let additional_info = char_data[indices[3]];
				if (additional_info !== null) {
					let div = document.createElement('div');
					if (additional_info === 'Deleted') {
						div.style.color = 'red';
						div.style.fontWeight = 'bold';
						additional_info = 'Withdrawn in IRGN2229R (WS2017 ROK Revised Submission).';
					}
					div.innerText = additional_info;
					div.style.whiteSpace = 'pre-wrap';
					div.style.backgroundColor = '#ff0';
					div.style.padding = '10px';
					insertion_point.appendChild(div);
				}
			}
			if (indices[3] && char_data[indices[3]] === 'Deleted') {
				file = '';
			}
			let separator = region === 'T' ? "\n" : ';'
			file.split(separator).forEach((file) => {
				file = file.trim();
				if (file === '') {
					return;
				}
				
				if (file.startsWith('TCA_CJK_2015')) {
					let page_number = file.split(' ')[2];
					page_number = page_number.padStart(3, '0');
					file = 'https://raw.githubusercontent.com/hfhchan/irg-ws2015/5d22fba4/data/t-evidence/IRGN2128A4Evidences-' + page_number + '.png';
				}
				if (file.startsWith('1292')) {
					let page_number = file.split(' ')[2];
					file = 'https://raw.githubusercontent.com/hfhchan/irg-ws2015/5d22fba4/data/g-evidence/IRGN2115_Appendix7_1268_Zhuang_Evidences_page1268_image' + page_number + '.jpg';
				}

				if (!file.startsWith('https://')) {
					file = "<?=EVIDENCE_PATH?>/" + region.toLowerCase() + '-evidence/' + file;
				}
				if (file.endsWith('.pdf')) {
					let iframe = document.createElement('iframe');
					iframe.src = file;
					iframe.width = 800;
					iframe.height = 1200;
					iframe.className = 'full';
					insertion_point.appendChild(iframe);
				} else {
					let a = document.createElement('a');
					a.className = 'evidence_image';
					a.href = file;
					a.target = '_blank';
					let img = document.createElement('img');
					img.src = file;
					if (source.startsWith('GHC-') || source.startsWith('GKJ') || source.startsWith('UK-') || source.startsWith('USAT') || source.startsWith('KC') || source.startsWith('V-')) {
						img.className = 'full';
					}
					a.appendChild(img);
					insertion_point.appendChild(a);
				}
			});
		}
	});
})();
</script>


<?php if (!empty($rowData[Workbook::UTC_EVIDENCE])) { ?>
<div style="background:#eee;padding:5px"><?=$rowData[52]; ?></div>
<?php
	$files = glob('../data/utc-evidence/' . $rowData[Workbook::UTC_SOURCE] . '*');
	foreach ($files as $file) {
		$filename = basename($file);
		if (substr($filename, -4) === '.pdf') {
?>
	<iframe src="<?=EVIDENCE_PATH?>/utc-evidence/<?=html_safe($filename)?>" width=800 height=1200 class=full></iframe>
<?php
		} else {
?>
	<a href="<?=EVIDENCE_PATH?>/utc-evidence/<?=html_safe($filename)?>" target=_blank><img src="<?=EVIDENCE_PATH?>/utc-evidence/<?=html_safe($filename)?>" width=800 class=full style="max-height:1200px;object-fit:scale-down;object-position:top"></a>
<?php
		}
	}
	
} ?>
</div>
</section>
<section class=ws2017_right>
<? if (!env::$readonly) { ?>
	<div style="display:grid;grid-template-columns:auto auto">
		<h2>Review</h2>
<?php
if (!$char->hasReviewedUnification() || !$char->hasReviewedAttributes()) {
?>
		<div><a href="<?=html_safe($char->base_path . '&mark=3')?>" class=review_all>Review All</a></div>
<?php
}
?>
	</div>
<? } ?>
<?php
/*
$review_path = ['IRGN2179_UTC-Review', 'IRGN2179_KR-Review', 'IRGN2155_UK_Review', 'IRGN2155_China_Review'];
foreach ($review_path as $path) {
	$review = json_decode(file_get_contents('..\/data/\' . $path . '.json'), true);
	if (isset($review[$sq_number])) {
		if (str_startswith($path, 'IRGN2179')) {
			$name = 'WS2017v3 - ' . strtr($path, ['_' => ' ']);
		} else if (str_startswith($path, 'IRGN2155')) {
			$name = '<span style="color:red"><u>WS2017v2 - ' . strtr($path, ['_' => ' ']) . '</u></span>';
		} else {
			$name = strtr($path, ['_' => ' ', '-' => ' ']).' Review';
		}
		echo '<b>'.$name.'</b><br>' . nl2br(html_safe($review[$sq_number]));
		echo '<br>';
		echo '<br>';
	}
}
*/
?>
<?php
	if (!env::$readonly) { 
?>
	<div>
		<b>Evidence &amp; Unification</b>:<br>
<?php
		if ($char->hasReviewedUnification()) {
			echo '<div>Reviewed.</div>';
		} else {
?>
		<a href="<?=html_safe($char->base_path . '&mark=1')?>" class=review>Review</a>
<?php
		}
?>
	</div>
	<hr>
<?php
	}
?>
	<div>
		<b>Attributes</b>: <br>
<?php

if ($rowData[Workbook::TS_FLAG]) {
	$radicals = getIdeographForSimpRadical(intval($rowData[Workbook::RADICAL]));
} else {
	$radicals = getIdeographForRadical($rowData[Workbook::RADICAL]);
}
$ids = parseStringIntoCodepointArray(trim($rowData[Workbook::IDS]));

$results = array_map(function ($part) use ($radicals) {
	static $radical_found = false;
	if ($part[0] === '&') {
		return null;
	}
	if (codepointIsIDC($part)) {
		return null;
	}
	
	$codepoint = $part;
	$char = codepointToChar($codepoint);

	$response = new StdClass();	
	$response->char = $char;
	$response->codepoint = $codepoint;
	$response->identifier = $char . ' (' . $codepoint . ')';

	if (in_array($char, $radicals) && !$radical_found) {
		$response->isRadical = true;
		$radical_found = true;
	}

	list($stroke_count, $first_stroke) = getTotalStrokes($part);
	if ($stroke_count) {
		$response->strokeCount = $stroke_count;
	} else {
		$response->strokeCount = null;
	}
	if ($first_stroke) {
		$response->firstStroke = $first_stroke;
	} else {
		$response->firstStroke = null;
	}
	
	if ($response->strokeCount) {
		return $response;
	}
	
	// no stroke count...
	$ids_row = \IDS\getIDSforCodepoint($codepoint);
	if (!$ids_row) {
		return $response;
	}
	$parse_children = function($ids_row) use (&$parse_children) {
		$children = [];
		foreach ($ids_row->ids_list as $i => $ids_list) {
			$poisoned = false;
			$components = $ids_list->components;
			if (count($components) === 1 && $components[0] === $ids_row->char) {
				continue;
			}

			$obj2 = [];
			foreach ($components as $component) {
				if ($component === '&CDP-8D50;') {
					$component = '監';
				} else if (substr($component, 0, 5) === '&CDP-') {
					$poisoned = true;
					continue;
				}

				$codepoint = charToCodepoint($component);
				if (codepointIsIDC($codepoint)) {
					continue;
				}

				list($ts1, $fs1) = getTotalStrokes($codepoint);
				$obj3 = new StdClass();
				$obj3->char = $component;
				$obj3->codepoint = $codepoint;
				$obj3->identifier = $component . ' (' . $codepoint . ')';
				$obj3->strokeCount = $ts1;
				$obj3->firstStroke = $fs1;
				if (!$obj3->strokeCount) {
					$ids_row = \IDS\getIDSforCodepoint($codepoint);
					if ($ids_row) {
						$obj3->children = $parse_children($ids_row);
					}
				}
				$obj2[] = $obj3;
			}

			if (!$poisoned) {
				$children[] = $obj2;
			}
		}
		return $children;
	};
	$response->children = $parse_children($ids_row);
	return $response;
}, $ids);

$results = array_values(array_filter($results, function($part) { return $part !== null; }));

$stroke_count = 0;
$total_count  = 0;
$first_stroke = false;
$radical_found = false;
foreach ($results as $part) {
	if (isset($part->isRadical)) {
		$radical_found = true;
		$total_count += $part->strokeCount;
	} else {
		if ($first_stroke === false) {
			$first_stroke = $part->firstStroke;
		}
		$stroke_count += $part->strokeCount;
		$total_count  += $part->strokeCount;
	}
}

?>
<style>
.ids_attribute_table{border-collapse:collapse;width:100%;margin:10px 0;table-layout:fixed}
.ids_attribute_table thead{font-size:13px}
.ids_attribute_table tr{border-width:1px 0;border-style:solid;border-color:#999}
.ids_attribute_table th{text-align:left}
.ids_attribute_table th,.ids_attribute_table td{padding:2px 4px}
</style>

<table style="" cellpadding=4 class=ids_attribute_table>
<col width=140>
<col>
<col>
<thead>
	<tr>
		<th>Char</th>
		<th>SC</th>
		<th>FS</th>
		<th>TC</th>
	</tr>
</thead>
<?php

$calculateTotalStrokeAndFirstStroke = function($sequence) use (&$calculateTotalStrokeAndFirstStroke) {
	$total_strokes = 0;
	$first_stroke = null;
	foreach ($sequence as $part) {
		if (isset($part->children)) {
			$data = array_map(function($sequence) use ($calculateTotalStrokeAndFirstStroke) {
				list ($ts, $fs) = $calculateTotalStrokeAndFirstStroke($sequence);
				if ($ts) return $ts . '|' . $fs;
				return 0;
			}, $part->children);

			$data = array_values(array_filter(array_unique($data)));

			if (count($data) === 1) {
				list ($ts, $fs) = explode('|', $data[0]);
				$total_strokes += $ts;
				if (is_null($first_stroke)) {
					$first_stroke = $fs;
				}
			}
			continue;
		}
		$total_strokes += $part->strokeCount;
		if ($part->char === codepointToChar('U+8FB6')) {
			continue;
		}
		if (is_null($first_stroke)) {
			$first_stroke = $part->firstStroke;
		}
	}
	return [$total_strokes, $first_stroke];
};

$renderRow = function($part, $level) use (&$renderRow, $calculateTotalStrokeAndFirstStroke, $char, $sq_number) {
	if (!$level) {
		$level = 0;
	}
	echo '<tr>';
	echo '<th style="font-family:Consolas,Microsoft Jhenghei">';
	echo str_repeat("&nbsp;", $level * 2);
	echo $part->identifier . '</th>';

	if (isset($part->isRadical)) {
		echo '<td align=right title=Radical>N/A</td>';
	} else if (!$part->strokeCount) {
		echo '<td align=right><span style="color:red">?</span></td>';
	} else {
		echo '<td align=right>' . $part->strokeCount . '</td>';
	}

	if (isset($part->isRadical)) {
		echo '<td align=right title=Radical>N/A</td>';
	} else if (!$part->firstStroke) {
		echo '<td align=right><span style="color:red">?</span></td>';
	} else {
		echo '<td align=right>' . $part->firstStroke . '</td>';
	}

	if (!$part->strokeCount) {
		echo '<td align=right><span style="color:red">?</span></td>';
	} else {
		echo '<td align=right>' . $part->strokeCount . '</td>';
	}
	echo '</tr>';
	
	if (isset($part->children)) {
		foreach ($part->children as $sequence) {
			foreach ($sequence as $component) {
				$renderRow($component, $level + 1);
			}
			list ($ts, $fs) = $calculateTotalStrokeAndFirstStroke($sequence);
			echo '<tr style="color:#00c"><th>' .str_repeat("&nbsp;", $level * 2). 'Total';
			if (!env::$readonly) {
				echo ' (';
				echo '<a href="'.$char->base_path.'&id='.$sq_number.'&amp;add_strokes='.urlencode($part->codepoint . " " . $ts . '|' . $fs).'">Confirm</a>';
				echo ')';
			}
			echo '</th><td align=right>' . $ts .  '</td><td align=right>' . $fs . ' </td></tr>';
		}
		if (empty($part->children)) {
			if (!env::$readonly) {
				echo '<tr style="color:#00c"><th>' .str_repeat("&nbsp;", $level * 2). 'Add </th><td align=right colspan=3>';
				echo '<form method=get action="'.$char->base_path.'&amp;id='.$sq_number.'" style="display:flex">';
				echo '<input name=add_strokes value="'.$part->codepoint . ' 0|0" style="border:1px solid #ccc;background:none;padding:2px 4px;min-width:0;flex:auto" data-lpignore="true">';
				echo '<input type=submit value=Save style="border:1px solid #999;background:#eee;margin:0;margin-left:2px;padding:2px 8px">';
				echo '</form>';
				echo '</td></tr>';
			}
		}
	}
};

array_map($renderRow, $results, array_fill(0, count($results), 0));

?>
</table>
<br>
<table class=ids_attribute_table>
	<col width=40>
	<col width=80>
	<col width=80>
	<col>
	<thead>
		<tr>
			<td></td>
			<th title=Expected align=right>Expected</th>
			<th title=Recorded align=right>Recorded</th>
			<td></td>
		</tr>
	</thead>
	<tr>
<? if (!$radical_found) { ?>
		<th>SC</th>
		<td align=right>/</td>
		<td align=right><?=$rowData[Workbook::STROKE]?></td>
		<td>Radical not found</td>
<? } else { ?>
		<th>SC</th>
		<td align=right><?=$stroke_count?></td>
		<td align=right><?=$rowData[Workbook::STROKE]?></td>
		<td align=right><? if ($stroke_count != $rowData[Workbook::STROKE]) { echo '<span style="color:red">Mismatch</span>'; } else { echo '<span style="color:green">OK</span>'; }?></td>
<? } ?>
	</tr>
	<tr>
<? if (!$radical_found) { ?>
		<th>FS</th>
		<td align=right>/</td>
		<td align=right><?=$rowData[Workbook::FS]?></td>
		<td>Radical not found</td>
<? } else { ?>
		<th>FS</th>
		<td align=right><?=$first_stroke?></td>
		<td align=right><?=$rowData[Workbook::FS]?></td>
		<td align=right><? if ($first_stroke != $rowData[Workbook::FS]) { echo '<span style="color:red">Mismatch</span>'; } else { echo '<span style="color:green">OK</span>'; }?></td>
<? } ?>
	</tr>
	<tr>
		<th>TC</th>
		<td align=right><?=$total_count?></td>
		<td align=right><?=$char->getTotalStrokes()?></td>
		<td align=right><? if ($total_count != $char->getTotalStrokes()) { echo '<span style="color:red">Mismatch</span>'; } else { echo '<span style="color:green">OK</span>'; }?></td>
	</tr>
</table>
<br>
<?php
if (!env::$readonly) {
	if ($char->hasReviewedAttributes()) {
		echo '<div>Reviewed.</div>';
	} else {
?>
		<a href="<?=html_safe($char->base_path . '&mark=2')?>" class=review>Review</a>
<?php
	}
}
?>
	</div>

	<hr>

	<div>
<?php
Log::add('Comments Start');
$has_cm = false;
foreach ([/*'GHZR', 'K', 'SAT', 'TW', 'UK', 'UTC',*/ ] as $cm_n) {
	$cm = file('../' . $cm_n . '.txt');
	foreach ($cm as $c) {
		if (str_startswith($c, $rowData[0])) {
			$has_cm = true;
			echo '<h2>Comments</h2>';
			echo '<div><b>'.$cm_n.'.txt</b></div>';
			echo trim($c);
		}
		if (str_startswith($c, $rowData[Workbook::G_SOURCE]) || str_startswith($c, $rowData[Workbook::T_SOURCE]) || str_startswith($c, $rowData[Workbook::K_SOURCE]) || str_startswith($c, $rowData[Workbook::SAT_SOURCE]) || str_startswith($c, $rowData[Workbook::UTC_SOURCE]) || str_startswith($c, $rowData[Workbook::UK_SOURCE])) {
			$has_cm = true;
			echo '<h2>Comments</h2>';
			echo '<div><b>'.$cm_n.'.txt</b></div>';
			echo trim($c);
		}
	}
}
Log::add('Comments End');

?>
	</div>
<? if (!env::$readonly) { ?>
	<p><? $instance = new DBProcessedInstance(); $total = $instance->getTotal(); echo $total .' out of 4989 processed, ' . (4989-$total) . ' remaining.'; ?></p>
<? } ?>
</section>
</div>
<hr>
<section class=ws2017_comments>
	<h2>Comments</h2>
<?php
		if (!empty($rowData[25])) {
			if ('Deleted' !== ($rowData[25])) {
				echo '<div style="margin:10px 0;color:red;background:yellow;padding:10px"><b>'.($rowData[25]).'</b></div>';
			}
		}
		echo '<table>';
		echo '<col width=200>';
		echo '<col width=auto>';
		echo '<col width=200>';
		echo '<thead><tr><th>Type</th><th>Description</th><th>Submitter</th></tr></thead>';
		foreach (DBComments::getAll($char->data[0]) as $cm) {
			echo '<tr>';
			echo '<td><b>'.htmlspecialchars($cm->type).'</b><br>WS2017 v'.$cm->version.'</td>';
			echo '<td>';

			if ($cm->type === 'SEMANTIC_VARIANT') {
				$arr = parseStringIntoCodepointArray($cm->comment);
				if (count($arr) === 1) {
					try {
						$arr2 = codepointToChar($arr[0]) . ' ('.$arr[0].')';
						$cm->comment = $arr2;
					} catch (Exception $e) {}
				}
			}

			if ($cm->type !== 'KEYWORD') {
				
				$cm->comment = preg_replace_callback('@ to U\+([0-9A-F]{4,5})@', function ($m) {
					return ' to ' . codepointToChar('U+' . $m[1]) . ' (U+' . $m[1] . ')';
				}, $cm->comment);

				if ($cm->type === 'UNIFICATION' || $cm->type === 'UNIFICATION_LOOSE' || $cm->type === 'CODEPOINT_CHANGED') {
					$pos1 = strpos($cm->comment, "\n");
					if ($pos1 === false) {
						$str = $cm->comment;
					} else {
						$str = substr($cm->comment, 0, $pos1);
					}
					$pos2 = strpos($str, ';');
					if ($pos2) {
						$str = substr($str, 0, $pos2);
					}
					$str = ' ' . trim($str);
					preg_match_all("/ (([\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}])|(WS(2015|2017))-([0-9]{5}))/u", $str, $matches);
					if ($cm->type === 'CODEPOINT_CHANGED' && preg_match('@^U\\+[0-9A-F]{4,5}$@', $cm->comment)) {
						$matches = [null, [codepointToChar($cm->comment)]];
					}
					foreach ($matches[1] as $i => $match) {
						if (!empty($matches[2][$i])) {
							$codepoint = charToCodepoint($match);
							if ($codepoint[2] === 'F' || ($codepoint[2] === '2' && $codepoint[3] === 'F')) {
								echo '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
							} else {
								echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
							}
						}
						if (!empty($matches[3][$i])) {
							$year = $matches[4][$i];
							$sn = $matches[5][$i];
							if ($year === '2017') {
								$__c = $character_cache->get($sn);
								$__c->renderCodeChartCutting();
							} else {
								$href = 'https://hc.jsecs.org/irg/ws'.$year.'/app/?id='.$sn;
								$url = 'https://hc.jsecs.org/irg/ws'.$year.'/app/cache/canvas'.$sn.'ws'.$year.'_cutting.png';
								echo '<a href="'.htmlspecialchars($href).'" target=_blank><img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($match).'" style="max-width:100%"></a><br>';
							}
						}
					}
				}
				
				$text = nl2br(htmlspecialchars($cm->comment));

				$text = preg_replace('@{?{(([0-9]){5}-jpy-unification\\.png)}}?@', '<a href="../comments/jpy/\\1" target=_blank><img src="../comments/jpy/\\1" style="max-width:100%"></a>', $text);
				$text = preg_replace('@{?{(([0-9]){5}-([0-9a-f]){3,64}\\.png)}}?@', '<a href="../comments/\\1" target=_blank><img src="../comments/\\1" style="max-width:100%"></a>', $text);
				$text = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}@', function ($m) {
					$codepoint = $m[1];
					if ($codepoint[2] === 'F' || ($codepoint[2] === '2' && $codepoint[3] === 'F')) {
						return '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%">';
					}
					return '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
				}, $text);
				$text = preg_replace_callback('@{{WS2015-(([0-9]){5})}}@', function ($m) use ($character_cache) {
					return
						'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting1.png" style="max-width:100%">' .
						'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting2.png" style="max-width:100%">';
				}, $text);
				$text = preg_replace_callback('@{{WS2017-(([0-9]){5})}}@', function ($m) use ($character_cache) {
					$char = $character_cache->get($m[1]);
					ob_start();
					echo '<a href="?id=' . $m[1] . '" target=_blank>';
					$char->renderCodeChartCutting('comment_cutting1', 80, 2750, 2000);
					if ($char->data[1]) {
						$char->renderCodeChartCutting('comment_cutting2', 2700, 3390, 2000);
					}
					echo '</a>';
					return ob_get_clean();
				}, $text);
				$text = preg_replace_callback('@{{(([0-9]){5})}}@', function ($m) use ($character_cache) {
					$char = $character_cache->get($m[1]);
					ob_start();
					echo '<a href="?id=' . $m[1] . '" target=_blank>';
					$char->renderCodeChartCutting('comment_cutting1', 80, 2750, 2000);
					if ($char->data[1]) {
						$char->renderCodeChartCutting('comment_cutting2', 2700, 3390, 2000);
					}
					echo '</a>';
					return ob_get_clean();
				}, $text);

			} else {
				$keyword = ($cm->comment);
				$text = '<span style="font-size:32px"><a href="?keyword='.urlencode($keyword).'" target=_blank>'.htmlspecialchars($keyword).'</a></span>';
			}
			echo $text;
			echo '</td>';
			echo '<td>';
			echo IRGUser::getById($cm->submitter)->getName();
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
if (!env::$readonly && $session->isLoggedIn()) { ?>
	<hr>
	<form method=post class=comment_block id=comment_block_<?=$sq_number;?>>
		<input type=hidden name=action value="comment">
		<input type=hidden name=sq_number value="<?=$sq_number;?>">
		<input type=hidden name=user_id value="<?=$session->getUser()->getUserId();?>">
		<div style="font-size:16px">Submitting as: <?=html_safe($session->getUser()->getName());?></div>
		<div>
			<select name=type class=comment_type>
<?php
		foreach (DBComments::COMMENT_TYPES as $type) {
			echo '<option value="' . $type . '">' . $type . '</option>'."\r\n";
		}
?>
			</select>
		</div>
		<div>
			<textarea name=comment class=comment_content data-sq-number="<?=$sq_number?>"></textarea>
		</div>
		<div class=comment_keywords>
<?php
		foreach (DBComments::getAllKeywords($session->getUser()->getUserId()) as $keyword) {
			echo '<span>' . $keyword . '</span>';
		}
?>
		</div>
		<div>
			<input type=submit value="Add Comment" class=comment_submit>
		</div>
	</form>
	<script>
	(function() {
		var parent = $('#comment_block_<?=$sq_number;?>');
		var toggleCommentKeywords = function() {
			var val = parent.find('.comment_type').val();
			if (val === 'KEYWORD') {
				parent.find('.comment_keywords').show();
			} else {
				parent.find('.comment_keywords').hide();
			}
		}
		parent.find('.comment_type').on('change', toggleCommentKeywords);
		toggleCommentKeywords();
		$(toggleCommentKeywords);
		parent.find('.comment_keywords').css({
			'display': 'grid',
			gridTemplateColumns: 'repeat(auto-fill, minmax(64px, 1fr))',
			gridGap: '10px',
			'margin': '10px 0'
		});
		parent.find('.comment_keywords span').on('click', function() {
			parent.find('.comment_content').val($(this).text());
		}).css({
			'border': '1px solid #999',
			'padding': '8px 4px',
			textAlign: 'center',
			cursor: 'pointer'
		});
	})();
	</script>
<?php
}
?>
</section>
<hr>
<section class=ws2017_actions>
	<h2>Discussion Record</h2>
<?php
		echo '<table>';
		echo '<col width=200>';
		echo '<col width=auto>';
		echo '<thead><tr><th>Type</th><th>Description</th></tr></thead>';
		foreach (DBActions::getAll($char->data[0]) as $cm) {
			echo '<tr>';
			echo '<td><b>'.htmlspecialchars($cm->type).'</b><br>WS2017 v'.$cm->version.', IRG #'.$cm->session.'</td>';
			echo '<td>';

			if ($cm->type === 'SEMANTIC_VARIANT') {
				$arr = parseStringIntoCodepointArray($cm->comment);
				if (count($arr) === 1) {
					try {
						$arr2 = codepointToChar($arr[0]) . ' ('.$arr[0].')';
						$cm->comment = $arr2;
					} catch (Exception $e) {}
				}
			}

			if ($cm->type !== 'KEYWORD') {
				
				$cm->comment = preg_replace_callback('@ to U\+([0-9A-F]{4,5})@', function ($m) {
					return ' to ' . codepointToChar('U+' . $m[1]) . ' (U+' . $m[1] . ')';
				}, $cm->value);

				if ($cm->type === 'UNIFICATION' || $cm->type === 'UNIFICATION_LOOSE' || $cm->type === 'CODEPOINT_CHANGED') {
					$pos1 = strpos($cm->comment, "\n");
					if ($pos1 === false) {
						$str = $cm->comment;
					} else {
						$str = substr($cm->comment, 0, $pos1);
					}
					$pos2 = strpos($str, ';');
					if ($pos2) {
						$str = substr($str, 0, $pos2);
					}
					$str = ' ' . trim($str);
					preg_match_all("/ (([\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}])|(WS(2015|2017))-([0-9]{5}))/u", $str, $matches);
					if ($cm->type === 'CODEPOINT_CHANGED' && preg_match('@^U\\+[0-9A-F]{4,5}$@', $cm->comment)) {
						$matches = [null, [codepointToChar($cm->comment)]];
					}
					foreach ($matches[1] as $i => $match) {
						if (!empty($matches[2][$i])) {
							$codepoint = charToCodepoint($match);
							if ($codepoint[2] === 'F' || ($codepoint[2] === '2' && $codepoint[3] === 'F')) {
								echo '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
							} else {
								echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
							}
						}
						if (!empty($matches[3][$i])) {
							$year = $matches[4][$i];
							$sn = $matches[5][$i];
							if ($year === '2017') {
								$__c = $character_cache->get($sn);
								$__c->renderCodeChartCutting();
							} else {
								$url = 'https://hc.jsecs.org/irg/ws'.$year.'/app/cache/canvas'.$sn.'ws'.$year.'_cutting.png';
								echo '<img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($match).'" style="max-width:100%"><br>';
							}
						}
					}
				}
				
				$text = nl2br(htmlspecialchars($cm->comment));

				$text = preg_replace('@{?{(([0-9]){5}-jpy-unification\\.png)}}?@', '<img src="../comments/jpy/\\1" style="max-width:100%">', $text);
				$text = preg_replace('@{?{(([0-9]){5}-([0-9a-f]){3,64}\\.png)}}?@', '<img src="../comments/\\1" style="max-width:100%">', $text);
				$text = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}@', function ($m) {
					$codepoint = $m[1];
					if ($codepoint[2] === 'F' || ($codepoint[2] === '2' && $codepoint[3] === 'F')) {
						return '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%">';
					}
					return '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
				}, $text);
				$text = preg_replace_callback('@{{WS2015-(([0-9]){5})}}@', function ($m) use ($character_cache) {
					return
						'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting1.png" style="max-width:100%">' .
						'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting2.png" style="max-width:100%">';
				}, $text);
				$text = preg_replace_callback('@{{WS2017-(([0-9]){5})}}@', function ($m) use ($character_cache) {
					$char = $character_cache->get($m[1]);
					ob_start();
					echo '<a href="?id=' . $m[1] . '" target=_blank>';
					$char->renderCodeChartCutting('comment_cutting1', 80, 2750, 2000);
					if ($char->data[1]) {
						$char->renderCodeChartCutting('comment_cutting2', 2700, 3390, 2000);
					}
					echo '</a>';
					return ob_get_clean();
				}, $text);
				$text = preg_replace_callback('@{{(([0-9]){5})}}@', function ($m) use ($character_cache) {
					$char = $character_cache->get($m[1]);
					ob_start();
					echo '<a href="?id=' . $m[1] . '" target=_blank>';
					$char->renderCodeChartCutting('comment_cutting1', 80, 2750, 2000);
					if ($char->data[1]) {
						$char->renderCodeChartCutting('comment_cutting2', 2700, 3390, 2000);
					}
					echo '</a>';
					return ob_get_clean();
				}, $text);

			} else {
				$keyword = ($cm->comment);
				$text = '<span style="font-size:32px"><a href="?keyword='.urlencode($keyword).'" target=_blank>'.htmlspecialchars($keyword).'</a></span>';
			}
			echo $text;
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

if (!env::$readonly && $session->isLoggedIn() && $session->getUser()->isAdmin()) { ?>
	<hr>
	<form method=post class=action_block id=action_block<?=$sq_number;?>>
		<input type=hidden name=action value="action">
		<input type=hidden name=sq_number value="<?=$sq_number;?>">
		<div>IRG Meeting #50 <input type=hidden name=session value="50"></div>
		<div>
			<select name=type class=action_type>
<?php
		foreach (DBActions::ACTION_TYPES as $type) {
			echo '<option value="' . $type . '">' . $type . '</option>'."\r\n";
		}
?>
			</select>
		</div>
		<div>
			<textarea name=value class=action_value data-sq-number="<?=$sq_number?>"></textarea>
		</div>
		<div>
			<input type=submit value="Add Discussion Record" class=action_submit>
		</div>
	</form>
<?php
}
?>
</section>

<hr>
	<details>
		<summary>Raw Info</summary>
<?php
		echo '<table>';
		foreach ($rowData as $cell => $value) {
			$name = isset($firstRow[$cell]) ? $firstRow[$cell] : '';
			echo '<tr><td>' . $cell . ' - ' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
		}
		echo '</table>';
?>
	</details>
</div>
<?php
}
?>

<div class=footer>
	<p>Source Code released at <a href="https://github.com/hfhchan/irg-ws2017">https://github.com/hfhchan/irg-ws2017</a>.</p>
</div>

<script>
window.addEventListener('paste', function(event) {
	var items = (event.clipboardData || event.originalEvent.clipboardData).items;
	if (event.target.nodeName !== 'TEXTAREA') {
		return;
	}
	if (items.length === 0) {
		return;
	}
	let textarea = event.target;
	let form = textarea.form;
	let sq_number = textarea.dataset.sqNumber;
	for (index in items) {
		var item = items[index];
		if (item.kind === 'file') {
			var blob = item.getAsFile();
			let formData = new FormData();
			formData.append('sq_number', sq_number)
			formData.append('data', blob);
			fetch('upload.php', {method: "POST", body: formData}).then(function(response) {
				return response.text();
			}).then(function(filename) {
				let autosubmit = form.querySelector('select').value === 'KEYWORD' && textarea.value === '';
				if (form.querySelector('select').value === 'KEYWORD') {
					form.querySelector('select').value = 'COMMENT';
				}
				textarea.value += '{{' + filename + '}}';
				if (autosubmit) {
					form.submit();
				}
			});
		}
	}
});
</script>