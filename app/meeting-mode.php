<?php

define('MEETING_SESSION', 53);
define('MEETING_MODE', 1);

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

header('Cache-Control: max-age=604800');

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

if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
}

$title = 'IRG #' . ($version + 49) . ' | WS2017v' . $version;
require_once 'index.header.php';
require_once 'index.searchbar.php';

?>
<div class=meeting_bar>

<script>
const tryLoadComment = (commentId) => {
	const currentTarget = document.getElementById(commentId);

	$('.subframe_link').removeClass('selected');
	$(currentTarget).addClass('selected');

	const existingFrames = $('#char-content iframe').toArray();
	const hasFrame = existingFrames.some(iframe => iframe.dataset.commentId == commentId);
	if (hasFrame) {
		existingFrames.forEach(iframe => {
			if (iframe.dataset.commentId == commentId) {
				iframe.parentNode.appendChild(iframe);
				if (iframe.src != currentTarget.href) {
					$('#char-loading').show();
					iframe.src = currentTarget.href;
				} else if ($(iframe).is(':visible')) {
					$('#char-loading').show();
					iframe.src = currentTarget.href;
				}
			}
		});
	} else {
		$('#char-loading').show();
		const iframe = document.createElement('iframe');
		iframe.onload = () => {
			$('#char-loading').hide();
		}
		iframe.src = currentTarget.href;
		iframe.dataset.commentId = commentId;
		$('#char-content').append(iframe);
	}
	const newFrames = $('#char-content iframe');
	if (newFrames.length > 5) {
		newFrames.first().remove();
	}
	$('#char-content iframe').toArray().slice(0, -1).forEach(iframe => iframe.style.display = 'none');
	$('#char-content iframe').toArray().slice(-1).forEach(iframe => iframe.style.display = 'block');
};

let initialState = history.state;

$(() => {
	if (history.state) {
		tryLoadComment(history.state.commentId);
		document.getElementById(history.state.commentId).scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
	} else {
		let params = new URLSearchParams(location.search);
		if (params.get('id')) {
			tryLoadComment(params.get('id'));
			document.getElementById(params.get('id')).scrollIntoView({
				behavior: 'auto',
				block: 'center',
				inline: 'center'
			});
		}
	}
});

window.onpopstate = (e) => {
	if (history.state) {
		tryLoadComment(history.state.commentId);
	} else {
		let params = new URLSearchParams(location.search);
		if (params.get('id')) {
			tryLoadComment(params.get('id'));
		}
	}
}

$('.meeting_bar').on('click', '.subframe_link', e => {
	e.preventDefault();	
	history.pushState({commentId: e.currentTarget.id}, '', '?<?php if ($version !== Workbook::VERSION) echo 'version=' . $version . '&' ?>id=' + e.currentTarget.id);
	tryLoadComment(e.currentTarget.id);
});


let observer = new IntersectionObserver(function handleIntersect(entries, observer) {
	entries.forEach(function(entry) {
		if (entry.intersectionRatio > 0.01) {
			entry.target.src = entry.target.dataset.src;
			entry.onload = () => {
				observer.remove(entry.target);
			};
		}
	});
}, {
	root: $('.meeting_bar')[0],
	rootMargin: "0px",
	threshold: 0.01
});

$(() => {
	$('.meeting_bar img').toArray().forEach(img => observer.observe(img));
});
</script>

<?
const SOURCES = [
	'G_SOURCE' => 8,
	'K_SOURCE' => 17,
	'UK_SOURCE' => 27,
	'SAT_SOURCE' => 38,
	'T_SOURCE' => 43,
	'UTC_SOURCE' => 50,
	'V_SOURCE' => 55,
];

$list = DBComments::getListAll($version);

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
	return $cm->date;
}, $list);
array_multisort($type, $source1, $source2, $source3, $source4, $source5, $source6, $source7, $date, $list);

function getFriendlyTypeName($type) {
	$friendlyType = ucfirst(strtolower(strtr($type, '_', ' ')));
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


$radicals = [];
foreach ($list as $item) {
	$category = $item->getCategoryForCommentType();
	if ($category === 'Attributes' && $item->type === 'ATTRIBUTES_RADICAL') {
		$radicals[$item->id] = true;
	}
}

$chunks = [];
$passed = [];
foreach ($list as $item) {
	if ($item->isDeleted()) {
		continue;
	}
	
	if ($item->type === 'ATTRIBUTES_FS') {
		continue;
	}

	$category = $item->getCategoryForCommentType();
	if ($category === null) {
		continue;
	}
	
	if ($category === 'Attributes' && isset($radicals[$item->id])) {
		$category = 'Radicals';
	}

	if (!isset($chunks[$category])) {
		$chunks[$category] = [];
	}

	$chunks[$category][] = $item;
	$passed[$category . $item->getSN()] = true;
}

foreach ($chunks as $category => $chunk) {
	if (empty($chunk)) {
		continue;
	}

	if ($category === 'Labels') {
		continue;
	}

	if ($category === 'Labels(2)') {
		continue;
	}

	echo '<div>';
	echo '<h3>' . $category . '</h3>';
	echo "\n";
	$lastSN = null;
	foreach ($chunk as $i => $cm) {
		if ($cm->type === 'OTHER') {
			if (strpos(strtolower($cm->comment), '** note') !== false) {
				continue;
			}
			if (strpos(strtolower($cm->comment), 'private note') !== false) {
				continue;
			}
		}
		
		if ($lastSN != $cm->getSN()) {
			echo '<a href="index.php?id='.htmlspecialchars($cm->getSN()).'&amp;meeting_mode=1" id="cm'.$cm->id.'" class=subframe_link>';
			$char = $character_cache->getVersion($cm->getSN(), $version);
			//$char->renderPart4();
			echo '<img src="about:blank" data-src="../data' . WSCharacter::getFileName($char->getSources()).'" width=50 height=50>';
			echo htmlspecialchars($cm->getSN()).'</a>';
			echo "\n";
		}

		$lastSN = $cm->getSN();
	}
	echo '</div>';
	echo '<hr>';
}
?>
</div>

<div id=char-content>
	<div id=char-loading style="display:none">Loading</div>
</div>
