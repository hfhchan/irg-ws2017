<?php

define('MEETING_SESSION', 54);

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
}

$sources_cache   = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache       = new IDSCache();

if (!env::$readonly) {
	// Run once to generate the attribute cache!
	if (isset($_GET['generate_cache']) && $session->isLoggedIn() && $session->getUser()->getUserId() === 2) {
		set_time_limit(60);
		mkdir('../data/attributes-cache/');
		$character_cache->generate();
		$sources_cache->generate();
		$ids_cache->generate();
	}

	if (!file_exists('../data/attributes-cache/')) {
		throw new Exception('Run ?generate_cache first to populate the cache.');
	}

	if (isset($_POST['action']) && $_POST['action'] === 'comment') {
		die('Under maintenance!');
	}

	if (isset($_POST['action']) && $_POST['action'] === 'action' && $session->isLoggedIn()) {
		if ($session->getUser()->isAdmin()) {
			DBDiscussionRecord::save($_POST['sq_number'], $_POST['type'], trim($_POST['value']), $_POST['session'], $session->getUser()->getUserId());
		} else {
			throw new Exception('Not Admin!');
		}
	}
}

if (empty($_GET) || isset($_GET['list_blocks'])) {
	require_once 'index.index.php';
	return;
}
if (isset($_GET['version']) && count($_GET) === 1) {
	require_once 'index.index.php';
	return;
}

$router = new Router($sources_cache, $character_cache, $ids_cache);

// Get Prev Unprocessed
if (isset($_GET['left']) && isset($_GET['find'])) {
	$prev = $router->getLeftOfSource($_GET['find']);
	header('Location: ?find=' . $prev);
	exit;
}

if (isset($_GET['left']) && isset($_GET['id'])) {
	try {
		$prev = $router->getLeftOfSerialNumber($_GET['id']);
		header('Location: ?id=' . $prev);
		exit;
	} catch (Exception $e) {
		output_error_message("Reached start.", "Looks like all characters have been marked as reviewed.");
		exit;
	}
}

// Get Next Unprocessed
if (isset($_GET['right']) && isset($_GET['find'])) {
	$next = $router->getRightOfSource($_GET['find']);
	header('Location: ?find=' . $next);
	exit;
}
if (isset($_GET['right']) && isset($_GET['id'])) {
	try {
		$next = $router->getRightOfSerialNumber($_GET['id']);
		header('Location: ?id=' . $next);
		exit;
	} catch (Exception $e) {
		output_error_message("Reached end.", "Looks like all characters have been marked as reviewed.");
		exit;
	}
}

if (!Env::$readonly && isset($_GET['mark']) && isset($_GET['id']) && $session->isLoggedIn()) {
	if (isset($_GET['label'])) {
		$base = '?label=' . $_GET['label'];
	} else if (isset($_GET['q'])) {
		$base = '?q=' . $_GET['q'];
	} else if (isset($_GET['find'])) {
		$base = '?find=' . $_GET['find'];
	} else if (isset($_GET['ids'])) {
		$base = '?ids=' . $_GET['ids'];
	} else {
		$base = '?id=' . $_GET['id'];
	}
	$char = $character_cache->getVersion($_GET['id'], $version);	
	if ($_GET['mark'] == 3) {
		$char->setReviewedUnification($session->getUser()->getUserId());
		$char->setReviewedAttributes($session->getUser()->getUserId());
	}
	if ($_GET['mark'] == 1) {
		$char->setReviewedUnification($session->getUser()->getUserId());
	}
	if ($_GET['mark'] == 2) {
		$char->setReviewedAttributes($session->getUser()->getUserId());
	}
	header('Location: ' . $base);
	exit;
}

if (!Env::$readonly && isset($_GET['add_strokes']) && $session->isLoggedIn() && $session->getUser()->getUserId() === 2) {
	if (preg_match('@^U\+[0-9A-F]?[0-9A-F][0-9A-F][0-9A-F][0-9A-F] [0-9]+\|[0-9]+$@', $_GET['add_strokes'])) {
		$fp = fopen('../totalstrokes.txt', 'a');
		fwrite($fp, $_GET['add_strokes'] . "\r\n");
		fclose($fp);
	} else {
		die('Format Mismatch');
	}
	echo 'Save success';
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
			require_once 'index.header.php';
			require_once 'index.searchbar.php';
			echo '<div class=center_box><b>Not Found.</b></div>';
			exit;
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->getVersion($sq_number, $version);
			$sources = $char->getAllSources();
			if (count($sources) === 1) {
				$prev = $sources_cache->findPrev($sources[0]);
				$next = $sources_cache->findNext($sources[0]);

				if ($prev) {
					$char->prev2 = '?left&find=' . $prev;;
					$char->prev = [$prev, '?find=' . $prev];
				}
				$char->curr = $sources[0];
				if ($next) {
					$char->next = [$next, '?find=' . $next];
					$char->next2 = '?right&find=' . $next;
				}
				$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($sources[0]);
			} else {
				$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
				$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

				if ($prev) {
					$char->prev2 = '?left&id=' . $sq_number;
					$char->prev = [$prev, '?id=' . $prev];
				}
				$char->curr = $sq_number;
				if ($next) {
					$char->next = [$next, '?id=' . $next];
					$char->next2 = '?right&id=' . $sq_number;
				}
				$char->base_path = '?id=' . urlencode($sq_number) . '&ids=' . urlencode($_GET['ids']);
			}

			$data[] = $char;
		}
	}
} else if (isset($_GET['q'])) {
	if (!empty($_GET['q'])) {
		$result1 = DBComments::getByQuery($_GET['q']);
		$result2 = DBDiscussionRecord::getByQuery($_GET['q']);
		if (empty($result1) && empty($result2)) {
			require_once 'index.header.php';
			require_once 'index.searchbar.php';
			echo '<div class=center_box><b>Not Found.</b></div>';
			exit;
		}
		$result = array_unique(array_merge($result1, $result2));
		foreach ($result as $sq_number) {
			$char = $character_cache->getVersion($sq_number, $version);
			$sources = $char->getAllSources();
			if (count($sources) === 1) {
				$prev = $sources_cache->findPrev($sources[0]);
				$next = $sources_cache->findNext($sources[0]);

				if ($prev) {
					$char->prev2 = '?left&find=' . $prev;;
					$char->prev = [$prev, '?find=' . $prev];
				}
				$char->curr = $sources[0];
				if ($next) {
					$char->next = [$next, '?find=' . $next];
					$char->next2 = '?right&find=' . $next;
				}
				$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($sources[0]);
			} else {
				$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
				$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

				if ($prev) {
					$char->prev2 = '?left&id=' . $sq_number;
					$char->prev = [$prev, '?id=' . $prev];
				}
				$char->curr = $sq_number;
				if ($next) {
					$char->next = [$next, '?id=' . $next];
					$char->next2 = '?right&id=' . $sq_number;
				}
				$char->base_path = '?id=' . urlencode($sq_number) . '&ids=' . urlencode($_GET['ids']);
			}

			$data[] = $char;
		}
	} else {
		throw new NotFoundException('No Label Specified');
	}
} else  if (isset($_GET['label'])) {
	if (!empty($_GET['label'])) {
		$result = DBComments::getByLabel($_GET['label']);
		if (empty($result)) {
			require_once 'index.header.php';
			require_once 'index.searchbar.php';
			echo '<div class=center_box><b>Not Found.</b></div>';
			exit;
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->getVersion($sq_number, $version);
			$sources = $char->getAllSources();
			if (count($sources) === 1) {
				$prev = $sources_cache->findPrev($sources[0]);
				$next = $sources_cache->findNext($sources[0]);

				if ($prev) {
					$char->prev2 = '?left&find=' . $prev;;
					$char->prev = [$prev, '?find=' . $prev];
				}
				$char->curr = $sources[0];
				if ($next) {
					$char->next = [$next, '?find=' . $next];
					$char->next2 = '?right&find=' . $next;
				}
				$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($sources[0]);
			} else {
				$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
				$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

				if ($prev) {
					$char->prev2 = '?left&id=' . $sq_number;
					$char->prev = [$prev, '?id=' . $prev];
				}
				$char->curr = $sq_number;
				if ($next) {
					$char->next = [$next, '?id=' . $next];
					$char->next2 = '?right&id=' . $sq_number;
				}
				$char->base_path = '?id=' . urlencode($sq_number) . '&ids=' . urlencode($_GET['ids']);
			}

			$data[] = $char;
		}
	} else {
		throw new NotFoundException('No Label Specified');
	}
} else if (isset($_GET['find']) || !isset($_GET['id'])) {
	if (empty($_GET['find'])) {
		$_GET['find'] = $sources_cache->getFirst();
	}
	$_GET['find'] = vSourceReverse(trim(strtr($_GET['find'], [' ' => ''])));
	if (!empty($_GET['find'])) {
		if (!preg_match('@^[A-Za-z0-9-_\\.]+$@', $_GET['find'])) {
			throw new Exception('Invalid ID');
		}
		$result = $sources_cache->find($_GET['find']);
		if (empty($result)) {
			require_once 'index.header.php';
			require_once 'index.searchbar.php';
			echo '<div class=center_box><b>Not Found.</b></div>';
			exit;
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->getVersion($sq_number, $version);
			$prev = $sources_cache->findPrev($_GET['find']);
			$next = $sources_cache->findNext($_GET['find']);

			if ($prev) {
				$char->prev2 = '?left&find=' . $prev;;
				$char->prev = [$prev, '?find=' . $prev];
			}
			$char->curr = $_GET['find'];
			if ($next) {
				$char->next = [$next, '?find=' . $next];
				$char->next2 = '?right&find=' . $next;
			}
			$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($_GET['find']);
			$data[] = $char;
		}
	}
} else if (isset($_GET['id'])) {
	$_GET['id'] = trim($_GET['id']);
	if (!preg_match('@^[0-9]{1,5}$@', $_GET['id'])) {
		throw new Exception('Invalid ID');
	}
	$sq_number = trim($_GET['id']);
	$sq_number = str_pad($sq_number, 5, '0', STR_PAD_LEFT);
	try {
		$char = $character_cache->getVersion($sq_number, $version);
	} catch (Exception $e) {
		throw $e;
		require_once 'index.header.php';
		require_once 'index.searchbar.php';
		echo '<div class=center_box><b>Not Found.</b></div>';
		exit;
	}

	$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
	$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

	if ($prev) {
		$char->prev2 = '?left&id=' . $sq_number;
		$char->prev = [$prev, '?id=' . $prev];
	}
	$char->curr = $sq_number;
	if ($next) {
		$char->next = [$next, '?id=' . $next];
		$char->next2 = '?right&id=' . $sq_number;
	}
	$char->base_path = '?id=' . urlencode($sq_number);

	$data[] = $char;
}

if (strcmp($version, '5.1') >= 0) {
	if (isset($char->prev)) $char->prev[0] = vSourceFixup($char->prev[0]);
	if (isset($char->next)) $char->next[0] = vSourceFixup($char->next[0]);
	if (isset($char->curr)) $char->curr = vSourceFixup($char->curr);
}

Log::add('Fetch Char End');


if (isset($_GET['ids'])) {
	$title = 'IDS Lookup ' . htmlspecialchars($_GET['ids']);
} else if (isset($_GET['find'])) {
	$title = htmlspecialchars(trim($_GET['find']));
	if (strcmp($version, '5.1') >= 0) {
		$title = vSourceFixup($title);
	}
} else {
	$title = $data[0]->data[0] . ' | ' . $data[0]->data[Workbook::IDS];
}
$title .= ' | WS2017v' . $version;

require_once 'index.header.php';
if ($session->isLoggedIn() && $session->getUser()->isNeedReset()) : ?>
<script>
window.location.href = 'admin.php';
</script>
<? endif; ?>

<? if (isset($_REQUEST['meeting_mode'])) : ?>
<script>
document.body.classList.add('meeting_mode');
$(() => {
	let version = +<?=$version?>;
	$('.ws2017_comments tbody>tr[data-version="' + version + '"]').first()[0].scrollIntoView();
	$('.ws2017_comments tbody>tr[data-version!="' + version + '"]').css('opacity', '.1').css('transition', 'all 200ms').hover(e => {
		$(e.currentTarget).css('opacity', .8).css('background', '#eee');
	}, e => {
		$(e.currentTarget).css('opacity', .1).css('background', '');
	});
})
</script>
<? endif; ?>
<?php
$time = 1606125105;
?>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script src="buildHTML.js?<?=$time?>"></script>
<script src="component-ws-evidences.js?<?=$time?>"></script>
<script src="component-ws-labels.js?<?=$time?>"></script>
<script src="component-ws-comment-delete.js?<?=$time?>"></script>
<script src="component-ws-comment-editor.js?<?=$time?>"></script>
<? require_once 'index.searchbar.php'; ?>

<?php
define('EVIDENCE_PATH', '../data');

foreach ($data as $char) {
	$wsChar = $char;
	$dbChar = DBCharacters::getCharacter($char->data[0], $version);
	
	Log::add('Render Char Start ' . $dbChar->sn);
	$rowData  = $wsChar->data;

?>
<div class=ws2017_char>
	<div class=ws2017_char_nav>
<? if (isset($char->prev2)) { ?>
		<div><a href="<?=html_safe($char->prev2)?>" id=nav_prev>&laquo;</a></div>
<? } else { ?>
		<div></div>
<? } ?>
<? if (isset($char->prev)) { ?>
		<div align=left><a href="<?=html_safe($char->prev[1])?>" accesskey=p><?=$char->prev[0]?></a></div>
<? } else { ?>
		<div></div>
<? } ?>
		<div align=center><b><?=$char->curr?></b></div>
<? if (isset($char->next)) { ?>
		<div align=right><a href="<?=html_safe($char->next[1])?>" accesskey=n><?=$char->next[0]?></a></div>
<? } else { ?>
		<div></div>
<? } ?>
<? if (isset($char->next2)) { ?>
		<div><a href="<?=html_safe($char->next2)?>" id=nav_next>&raquo;</a></div>
<? } else { ?>
		<div></div>
<? } ?>
	</div>

	<h2 hidden>Character Info</h2>
	<div class="ws2017_chart_table sheet-<?=$dbChar->status?>">
<?php
	$dbChar->renderPart1();
?>
		<div class=ws2017_chart_table_sources_wrapper>
<?
	$dbChar->renderPart2();
?>
		</div>
<?
	$dbChar->renderPart3();
?>
	</div>

	<div class=ws2017_content>
		<section class=ws2017_left>
<?php
	$matched = $dbChar->getMatchedCharacter();
	if ($matched && substr($matched, 0, 1) !== '&') {
		echo '<p style="background:red;font-size:24px;margin:10px 0;padding:10px;color:#fff">Exact Match: <a href="https://localhost/unicode/fonts/gen-m.php?name=' . ($matched) . '" target=_blank style="color:#fff">' . $matched . ' (' . charToCodepoint($matched) . ')</a></p>';
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
		return '<a href="https://localhost/unicode/fonts/gen-m.php?name=u'.$m[1].'" target=_blank>'.codepointToChar($codepoint).' ('.$codepoint.')</a>';
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
			echo '<a href="https://localhost/unicode/fonts/gen-m.php?name=u'.strtolower(substr($codepoint, 2)).'" target=_blank style="margin-right:10px">';
			echo '<img src="https://glyphwiki.org/glyph/hkcs_m'.strtolower(substr($codepoint, 2)).'.svg" alt="'.$codepoint.'" height=72 width=72 style="vertical-align:top">';
			echo '</a>';
			if (strcmp($version, '5.2') >= 0) {
				echo '<img src="https://hc.jsecs.org/Code%20Charts/UCSv13/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
			} else {
				echo '<img src="https://hc.jsecs.org/Code%20Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
			}
			echo '</div>';
		}
	}
?>
<div class=ws2017_evidence>
<?

$evid = [];
$dbSources = $dbChar->getAllSources();
foreach ($dbSources as $dbSource) {
	$evid[$dbSource] = DBCharacterEvidence::get($dbSource, $char->version);
}

?>
	<ws-evidences data-evidence="<?=html_safe(json_encode($evid))?>"></ws-evidences>
</div>
</section>
<section class=ws2017_right>
<?php if (!env::$readonly && $session->isLoggedIn()) { ?>
	<div style="display:grid;grid-template-columns:auto auto">
		<h2>Review</h2>
<?php
if (!$char->hasReviewedUnification($session->getUser()->getUserId()) || !$char->hasReviewedAttributes($session->getUser()->getUserId())) {
?>
		<div><a href="<?=html_safe($char->base_path . '&mark=3')?>" class=review_all>Review All</a></div>
<?php
}
?>
	</div>
	<ws-labels
		data-added-labels="<?=html_safe(json_encode(DBComments::getAllLabelsForChar($dbChar->sn)))?>"
		data-user-labels="<?=html_safe(json_encode(DBComments::getAllLabels($session->getUser()->getUserId())))?>"
		data-user-id="<?=$session->getUser()->getUserId();?>"
		data-sq-number="<?=$dbChar->sn;?>"
	></ws-labels>
	<div>
		<b>Evidence &amp; Unification</b>:<br>
<?php
		if ($char->hasReviewedUnification($session->getUser()->getUserId())) {
			echo '<div>Reviewed.</div>';
		} else {
?>
		<a href="<?=html_safe($char->base_path . '&mark=1')?>" class=review>Review</a>
<?php
		}
?>
	</div>
<?php
	}
?>
	<div style="margin:10px 0">
		<b>Attributes</b>: <br>
<?php

$idsProcessor = new IDSProcessor($dbChar->ids, $dbChar->radical, $dbChar->trad_simp_flag);
$results = $idsProcessor->getResults();
list($stroke_count, $total_count, $first_stroke, $radical_found) = $idsProcessor->getCounts();
?>


<script type="text/x-ids-parse-result" class=ids-parse-result>
<?=json_encode([
	'result' => $results,
	'stroke_count' => $stroke_count,
	'first_stroke' => $first_stroke,
	'total_count' => $total_count,
	'radical_found' => $radical_found,

	'stroke_count_data' => $dbChar->stroke_count,
	'first_stroke_data' => $dbChar->first_stroke,
	'total_count_data' => $dbChar->total_stroke_count,
	
	'is_admin' => (!env::$readonly && $session->isLoggedIn() && $session->getUser()->getUserId() === 2)
], JSON_PRETTY_PRINT ). "\n"?>
</script>

<br>
<?php
if (!env::$readonly && $session->isLoggedIn()) {
	if ($char->hasReviewedAttributes($session->getUser()->getUserId())) {
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

<? if (!env::$readonly && $session->isLoggedIn()) { ?>
	<p><? $instance = new DBProcessedInstance($session->getUser()->getUserId()); $total = $instance->getTotal(); echo $total .' out of 5039 processed, ' . (5039-$total) . ' remaining.'; ?></p>
<? } ?>
</section>
</div>
<hr>
<section class=ws2017_comments>
	<h2>Review Comments</h2>
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
		foreach (DBComments::getAll($dbChar->sn) as $cm) {
			if ($cm->isDeleted()) {
				$currentUser = $session->getUser();
				if (!$currentUser || !empty($_REQUEST['meeting_mode']) || !($currentUser->getUserId() == $cm->created_by || $currentUser->isAdmin())) {
					continue;
				}
			}
			if ($cm->type === 'LABEL') {
				continue;
			}
			if ($cm->isDeleted()) {
				echo '<tr class=comment_deleted data-version="'.htmlspecialchars($cm->version).'">';
			} else {
				echo '<tr data-version="'.htmlspecialchars($cm->version).'">';
			}
			echo '<td>';
			echo '<div><b>' . $cm->getCategoryForCommentType() . '</b></div>';
			if (substr($cm->type, -strlen('_RESPONSE')) === '_RESPONSE') {
				echo '<div style="font-size:13px">'.htmlspecialchars(substr($cm->type, 0, -strlen('_RESPONSE'))).' (Response)</div>';
			} else {
				echo '<div style="font-size:13px">'.htmlspecialchars($cm->type).'</div>';
			}
			echo '<div style="font-size:13px;margin-top:4px">WS2017 v'.$cm->version.'</div>';

			if ($cm->version !== $version) {
				if (!$cm->isResolved($version)) {
					echo '<div style="font-size:13px;color:red"><b>[ Unresolved ]</b></div>';
				}
			}

			echo '</td>';
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

			if ($cm->type !== 'LABEL') {
				$commentProcessor = new CommentProcessor($cm);
				echo $commentProcessor->renderHTML();
			} else {
				$label = ($cm->comment);
				echo '<span style="font-size:32px"><a href="?label='.urlencode($label).'" target=_blank>'.htmlspecialchars($label).'</a></span>';
			}
			echo '</td>';
			echo '<td>';
			
			$commentCreatedBy = IRGUser::getById($cm->created_by);
			echo '<a href="list.php?user='.html_safe($cm->created_by).'" target=_blank>';
			echo nl2br($commentCreatedBy->getName());
			echo '</a>';
			echo '<div style="font-size:13px;color:#999">';
			echo $commentCreatedBy->getOrganization();
			echo '</div>';
			echo '<div class=comment_date>'.$cm->created_at.'</div>';
			
			if ($cm->isModified()) {
				echo '<div style="color:red;margin-top:10px">Modified</div>';
				if ($cm->modified_by) {
					$commentModifiedBy = IRGUser::getById($cm->modified_by);
					echo nl2br($commentModifiedBy->getName());					
					echo '<div style="font-size:13px;color:#999">';
					echo $commentModifiedBy->getOrganization();
					echo '</div>';
				}
				if ($cm->modified_at) {
					echo '<div class=comment_date>'.$cm->modified_at.'</div>';
				}
			}

			if ($cm->isDeleted()) {
				echo '<div style="color:red;margin-top:10px">DELETED</div>';
				if ($cm->deleted_by) {
					$commentDeletedBy = IRGUser::getById($cm->deleted_by);
					echo nl2br($commentDeletedBy->getName());					
					echo '<div style="font-size:13px;color:#999">';
					echo $commentDeletedBy->getOrganization();
					echo '</div>';
				}
				if ($cm->deleted_at) {
					echo '<div class=comment_date>'.$cm->deleted_at.'</div>';
				}
			}

			if ($session->isLoggedIn() && $session->getUser()->isAdmin()) {
				echo '<div style="font-size:13px;color:#999;margin-top:10px">#' . $cm->id . '</div>';
			}

			if ($session->isLoggedIn() && $cm->canDelete($session->getUser())) {
				echo '<ws-comment-delete data-user-id="'.$session->getUser()->getUserId().'" data-comment-id="'.$cm->id.'"></ws-comment-delete>';
			}

			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
if (!env::$readonly && $session->isLoggedIn()) { ?>
	<hr>
	<details>
		<summary>Add Comment</summary>
<?

$__commentTypes = [];
foreach (DBComments::COMMENT_TYPES as $__type) {
	if ($__type === 'LABEL') {
		continue;
	}

	if ($__type === 'CODEPOINT_CHANGED') {
		continue;
	}

	if ($__type === 'RESPONSE') {
		continue;
	}

	if ($__type === 'WITHDRAW') {
		continue;
	}

	$__commentGroup = DBComments::getCategoryForType($__type);
	$__commentTypes[$__commentGroup][] = $__type;
}

$__suggestedItems = [];
$is_tca = array_filter($char->getAllSources(), function($source) {
	return substr($source, 0, 1) === 'T';
});
if (!empty($is_tca)) {
	$__similar = strip_tags($similar);
	$__similar = str_replace('variant of', '', $__similar);
	$__suggestedItems[] = 'Unify to ' . strip_tags(trim($__similar)) . '';
	//$__suggestedItems[] = 'IVD to ' . strip_tags($similar) . '';
	//$__suggestedItems[] = 'Unify/IVD to ' . strip_tags($similar) . '';
}

$__radicals = [];
for ($i = 1; $i < 215; $i++) {
	$radT = getIdeographForRadical($i)[0];
	$radS = getIdeographForSimpRadical($i)[0];
	if ($radS !== $radT) {
		$__radicals[] = $i . ' ' . $radT;
		$__radicals[] = $i . '\' ' . $radS;
	} else {
		$__radicals[] = $i . ' ' . $radT;
	}
}
?>
		<ws-comment-editor
			data-action="add_comment"
			data-sn="<?=$dbChar->sn;?>"
			data-user-id="<?=$session->getUser()->getUserId();?>"
			data-user-name="<?=html_safe($session->getUser()->getName());?>"
			data-comment-types="<?=html_safe(json_encode($__commentTypes))?>"
			data-suggested-comments="<?=html_safe(json_encode($__suggestedItems))?>"
			data-radicals="<?=html_safe(json_encode($__radicals))?>"
		></ws-comment-editor>
	</details>
<?php
}
?>
</section>
<hr>
<section class=ws2017_actions>
	<h2>Discussion Record</h2>
<?php
		echo '<table class=dicussion_record data-id="'.$dbChar->sn.'">';
		echo '<col width=200>';
		echo '<col width=auto>';
		echo '<thead><tr><th>Date</th><th>Description</th></tr></thead>';
		foreach (DBDiscussionRecord::getAll($dbChar->sn) as $cm) {
			if ($cm->session < 52) {
				echo '<tr style="opacity:.3;background:#ccc">';
			} else {
				echo '<tr>';
			}
			echo '<td><b>IRG #'.$cm->session.'</b><br>';
			echo $cm->toLocalDate();
			echo '<br>';
			echo $cm->toLocalTime();
			echo ' ';
			echo $cm->toLocalTimezone();
			echo '<br>';
			echo '<div style="font-size:13px">Recorded by ';
			$cm_user = IRGUser::getById($cm->user);
			echo htmlspecialchars($cm_user->getName());
			echo '</div>';

			if ($session->isLoggedIn() && $session->getUser()->isAdmin()) {
				echo '<div style="font-size:13px;color:#999;margin-top:10px">#' . $cm->id . '</div>';
			}

			echo '</td>';
			echo '<td>';
			if ($cm->type !== 'OTHER_DECISION') {
				echo '<b>'.htmlspecialchars($cm->type).'</b><br>';
			}

			if ($cm->type === 'SEMANTIC_VARIANT') {
				$arr = parseStringIntoCodepointArray($cm->comment);
				if (count($arr) === 1) {
					try {
						$arr2 = codepointToChar($arr[0]) . ' ('.$arr[0].')';
						$cm->comment = $arr2;
					} catch (Exception $e) {}
				}
			}

			
			$cm->comment = preg_replace_callback('@ to U\+([0-9A-F]{4,5})(\s*)(.+)?@', function ($m) {
				$char = codepointToChar('U+' . $m[1]);
				if (substr($m[3], 0, strlen($char)) == $char) {
					$m[2] = '';
					$m[3] = substr($m[3], strlen($char));
				}
				return ' to ' . $char . ' (U+' . $m[1] . ')' . $m[2] . $m[3];
			}, $cm->value);

			if ($cm->isUnification()) {
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
						echo getImageHTML($codepoint);
					}
					if (!empty($matches[3][$i])) {
						$year = $matches[4][$i];
						$sn = $matches[5][$i];
						if ($year === '2017') {
							$__c = $character_cache->getVersion($sn, $cm->getVersion());
							$__c->renderPart4();
						} else {
							$url = 'https://hc.jsecs.org/irg/ws'.$year.'/app/cache/canvas'.$sn.'ws'.$year.'_cutting.png';
							echo '<img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($match).'" style="max-width:100%"><br>';
						}
					}
				}
				
				if (preg_match('@^U\+([0-9A-F]+)$@', $cm->comment)) {
					$codepoint = $cm->comment;
					echo getImageHTML($codepoint);
				}
			}
			
			$text = nl2br(htmlspecialchars($cm->comment));

			$text = preg_replace('@{?{(([0-9]){5}-jpy-unification\\.png)}}?@', '<img src="../comments/jpy/\\1" style="max-width:100%">', $text);
			$text = preg_replace('@{?{(([0-9]){5}-([0-9a-f]){3,64}\\.png)}}?@', '<img src="../comments/\\1" style="max-width:100%">', $text);
			$text = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}@', function ($m) {
				$codepoint = $m[1];
				return getImageHTML($codepoint);
			}, $text);
			$text = preg_replace_callback('@{{CM-([0-9]+)}}@', function ($m) {
				$text_cm = DBComments::getById($m[1]);
				ob_start();
				echo '<blockquote><a href="./?id=' . $text_cm->sn . '" target=_blank style="color:initial;text-decoration:none;display:block">';
				echo nl2br(htmlspecialchars($text_cm->comment));
				echo '</a></blockquote>';
				return ob_get_clean();
			}, $text);
			$text = preg_replace_callback('@{{WS2015-(([0-9]){5})}}@', function ($m) use ($character_cache) {
				return
					'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting1.png" style="max-width:100%">' .
					'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting2.png" style="max-width:100%">';
			}, $text);
			$text = preg_replace_callback('@{{WS2017-(([0-9]){5})}}@', function ($m) use ($character_cache, $cm) {
				$char = $character_cache->getVersion($m[1], $cm->getVersion());
				ob_start();
				$char->renderPart4();
				return ob_get_clean();
			}, $text);
			$text = preg_replace_callback('@{{(([0-9]){5})}}@', function ($m) use ($character_cache) {
				$char = $character_cache->get($m[1]);
				ob_start();
				$char->renderPart4();
				return ob_get_clean();
			}, $text);
			
			echo $text;
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';

if (!env::$readonly && $session->isLoggedIn() && $session->getUser()->isAdmin()) { ?>
	<hr>
	<form method=post class=action_block id=action_block<?=$dbChar->sn;?>>
		<input type=hidden name=action value="action">
		<input type=hidden name=sq_number value="<?=$dbChar->sn;?>">
		<div>IRG Meeting #<?=MEETING_SESSION?> <input type=hidden name=session value="<?=MEETING_SESSION?>"></div>
		<div style="font-size:16px;margin:10px 0;border:1px solid #ccc;padding:10px">
			Predicted outcomes: (press button to autofill -- remember to press "Add Discussion Record!")
<?

$possibleOutcomes = [];
foreach (DBComments::getAll($dbChar->sn) as $cm) {
	if ($cm->version != $version) {
		continue;
	}

	$commentProcessor = new CommentProcessor($cm);
	$outcomes = $commentProcessor->getPossibleOutcomes($char);
	foreach ($outcomes as $key => $val) {
		$possibleOutcomes[$key] = $val;
	}
}		
if ($char->sheet == 2) {
	$possibleOutcomes['Keep postponed'] = true;
}


foreach (array_keys($possibleOutcomes) as $outcome) {
	echo '<div><button type=button class="action_outcome" style="width:400px">' . html_safe($outcome) . '</button></div>';
}

if (empty($possibleOutcomes)) {
	echo 'Could not predict any outcome.';
}
?>
		</div>
		<div>
			<input type=hidden name=type value="OTHER_DECISION">
			<!--select name=type class=action_type>
<?php
		foreach (DBDiscussionRecord::ACTION_TYPES as $type) {
			echo '<option value="' . $type . '">' . $type . '</option>'."\r\n";
		}
?>
			</select-->
		</div>
		<div>
			<textarea name=value class=action_value data-sq-number="<?=$dbChar->sn?>" style="min-height:3.6em!important"></textarea>
		</div>
		<div>
			<input type=submit value="Add Discussion Record" class=action_submit>
		</div>
		<script>
		$('#action_block<?=$dbChar->sn;?> .action_outcome').on('click', e => {
			const textArea = $('#action_block<?=$dbChar->sn;?> .action_value');
			if (textArea.val()) {
				textArea.val(textArea.val() + ', ' + e.currentTarget.innerText);
			} else {
				textArea.val(e.currentTarget.innerText);
			}
		});
		</script>
	</form>
<?php
}
?>
</section>
<hr>

<?

		$changes = DBChanges::getChangesForSN($dbChar->sn);
		if (isset($changes[0])) {
?>
<section class=ws2017_changes>
	<h2>Attribute Changes</h2>
<?php
		echo '<table class=change_record data-id="'.$dbChar->sn.'">';
		echo '<col width=200>';
		echo '<col width=auto>';
		echo '<thead><tr><th>Version</th><th>Description</th></tr></thead>';
		foreach ($changes as $change) {
			echo '<tr>';
			echo '<td>';
			
			echo $change->version2;
			
			echo '</td>';
			echo '<td>';
			
			echo '<div>';
			echo $change->getDescription();
			echo '</div>';

			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
?>
	<small>Note: change record for version 3.0 and before is not available yet.</small>
</section>
<?
		}

		$glyphs = [];
		foreach ($char->getAllSources() as $source) {
			$rows = DBCharacterGlyph::getAll($source, $version);
			$glyphs[$source] = $rows;
		}
?>
<section class=ws2017_changes>
	<h2>Glyph Changes</h2>
<?php
		echo '<table class=change_record data-id="'.$dbChar->sn.'">';
		echo '<col width=200>';
		echo '<col width=auto>';
		echo '<thead><tr><th>Source Reference</th><th>Glyph</th></tr></thead>';
		foreach ($glyphs as $source_identifier => $changes) {
			echo '<tr>';
			echo '<td>';
			echo html_safe($source_identifier);
			echo '</td>';
			echo '<td>';
			echo '<div style="display:flex;align-items:center">';
			$changes = array_values(array_reverse($changes));
			$last = count($changes) - 1;
			foreach ($changes as $i => $change) {
				if ($i != 0) {
					echo '<div style="margin-right:8px;color:#999">🢂</div>';
				}
				echo '<div style="margin-right:8px;text-align:center;display:grid;grid-template-columns:auto auto;grid-gap:8px;align-items:center">';
				echo '<img src="' . EVIDENCE_PATH . html_safe($change->path) . '" width="32" height="32" style="' . ($i == $last ? 'border:1px solid red' : 'border:1px solid #333;opacity:.3') . '">';
				echo '<span>' . $change->version . '</span>';
				echo '</div>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
?>
	<small>Note: glyph data for version 1.1 is not available.</small>
</section>
<?
		
?>
<hr>
	<details>
		<summary>Raw Info</summary>
<?php
		echo '<table>'."\n";
		foreach ($rowData as $cell => $value) {
			$name = isset($firstRow[$cell]) ? $firstRow[$cell] : '';
			echo '<tr><td>' . $cell . ' - ' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($value) . '</td></tr>'."\n";
		}
		echo '</table>'."\n";
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
	console.log(event.clipboardData);
	
	if (items.length === 0) {
		return;
	}
	let textarea = event.target;
	let form = textarea.form;
	let sq_number = textarea.dataset.sqNumber;
	console.log(items);
	for (index in items) { // Work around Excel Bug
		var item = items[index];
		if (item.kind === 'string') {
			return false;
		}
	}
	for (index in items) {
		var item = items[index];
		if (item.kind === 'file') {
			var blob = item.getAsFile();
			let formData = new FormData();
			formData.append('sq_number', sq_number)
			formData.append('data', blob);
			
			form.classList.add('uploading');
			fetch('upload.php', {
				credentials: "same-origin",
				method: "POST",
				body: formData,
				cache: "no-store"
			}).then(function(response) {
				return response.text();
			}).then(function(filename) {
				form.classList.remove('uploading');
				//let autosubmit = form.querySelector('select').value === 'LABEL' && textarea.value === '';
				let autosubmit = false;
				//if (form.querySelector('select').value === 'LABEL') {
				//	form.querySelector('select').value = 'COMMENT';
				//}
				textarea.value += '{{' + filename + '}}';
				if (autosubmit) {
					form.submit();
				}
			});
		}
	}
});

$(document).on('click', '.add_stroke_link', (e) => {
	e.preventDefault();
	fetch(e.target.href).then(_ => window.location.reload());
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<script>
	$('.comment_date').each((i, el) => {
		let content = el.textContent;

		let time = document.createElement('time');
		time.style.fontSize = '13px';
		time.title = content + ' +0000';

		if (content.length === 10) {
			time.textContent = content;
		} else {
			let date = moment(content.replace(' ','T') + 'Z').format('YYYY-MM-DD HH:mm');
			let timezone = moment().format('ZZ');
			//if (timezone.endsWith('00')) {
			//	timezone = timezone.slice(0, -2);
			//}
			//if (timezone.slice(1, 2) === '0') {
			//	timezone = timezone.slice(0, 1) + timezone.slice(2);
			//}
			time.textContent = date + ' ' + timezone;
		}
		el.innerHTML = '';
		el.appendChild(time);
	});
</script>
<script>
const timeout = <?
echo 1000000; // Todo: change before meeting!
//echo 5000;
?>;
$('.dicussion_record').each((i, el) => {
	let id = el.dataset.id;
	let refresh = async () => {
		if (!document.hidden) {
			let resp = await fetch('record.php?id=' + id);
			let html = await resp.text();
			el.insertAdjacentHTML('beforebegin', html);
			let new_el = el.previousSibling;
			el.remove();
			el = new_el;
		}
		window.setTimeout(refresh, timeout);
	}
	window.setTimeout(refresh, timeout);
});
</script>

<? include 'idsProcessor.inc.tmpl'; ?>
<script src="renderIDSCheck.js"></script>