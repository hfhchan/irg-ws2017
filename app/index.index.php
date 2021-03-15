<?
ob_start('ob_gzhandler');

define('MAIN_INDEX', 1);

if (!isset($session)) {
	die();
}

$character_cache = new CharacterCache();
if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
}


?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>IRG Working Set 2017</title>
<link href="common.css" rel=stylesheet type="text/css">
<link href="style.css?20210315" rel=stylesheet type="text/css">
<style>
details{margin:10px 0}
summary{padding:10px;background:#eee;-webkit-user-select:none;-moz-user-select:none}
summary:hover{background:#ccc}
.list_blocks{display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));grid-gap:10px;line-height:1.5}
.list_blocks > div{margin:10px 0}
.list_blocks > div > h2{font-size:20px;margin:0}
.list_block{margin:5px 0;display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));grid-gap:5px}
.list_block a{display:grid;grid-template-columns:1fr 144px;color:#000;text-decoration:none;border:1px solid #ccc}
.list_block a:hover{background:#eee}
.list_block a > span{padding:10px 10px;align-self:center}
.list_block a > div{display:flex;margin-left:auto}
.list_block a > div > img{display:block;width:48px;height:48px;mix-blend-mode:multiply}
</style>
<script>
(function() {
	var el = document.createElement('div');
	var supports_grid = typeof el.style.grid === 'string';
	if (!supports_grid) {
		document.write('<div style="background:yellow;font-size:24px;border:5px solid red;padding:80px">Your browser is not supported.  Please use Google Chrome 72 or above, or Firefox 65 or above, or equivalent browser.</div>');
	}
})();
</script>
<? require 'index.searchbar.php'; ?>
<div style="padding:20px;background:#fff;margin:20px auto;max-width:1200px;border:1px solid #ccc">
<? /*
	<div style="font-size:24px;padding:10px;margin:0 0 10px;background:red;color:#fff">Version 5 is under preparation. Please check after 1/1/2020. Sorry for the inconvenience.</div> */ ?>
	<h1 style="font-size:24px;margin:0">IRG Working Set 2017 (v<?=html_safe($version)?>) - Index of Characters</h1>
	<div class=list_blocks>
		<div>
			<h2>Grouped By Serial Number</h2>
<?
	$status_cache = new StatusCache();
	$status = $status_cache->getGroupBySerial($version);

	$groups = [
		0 => [],
		1 => [],
		2 => [],
		3 => [],
		4 => [],
		5 => [],
		6 => [],
		7 => [],
		8 => [],
		9 => [],
		10 => [],
		11 => [],
	];

	foreach ($status as $sn => $info) {
		$groupID = floor($sn / 500);
		$groups[$groupID][$sn] = $info['references'];
	}

	foreach ($groups as $groupID => $list) {
		$start = $groupID * 500;
		$prefix = $start . ' to ' . ($start + 499);
		echo '<details><summary>' . htmlspecialchars($prefix) . '</summary>'."\n";
		echo '<div class=list_block>'."\n";
		foreach ($list as $sn => $sources) {
			if (isset($status[$sn]['images'])) {
				$images = $status[$sn]['images'];
			} else {
				$images = [];
				foreach ($sources as $source) {
					$images[] = WSCharacter::getFileName($source, $version);
				}
			}

			if ($status[$sn]['sheet'] === 2) {
				echo '<a href="?id=' . htmlspecialchars($sn) . '" style="background:yellow">';
			} else if ($status[$sn]['sheet'] === 1) {
				echo '<a href="?id=' . htmlspecialchars($sn) . '" style="background:#999;opacity:.6">';
			} else {
				echo '<a href="?id=' . htmlspecialchars($sn) . '">';
			}
			echo '<span>' .  htmlspecialchars($sn) . '</span>';
			echo '<div>';
			foreach ($images as $img) {
				if (isset($img)) {
					echo '<img loading=lazy src="../data' . $img.'">';
				}
			}
			echo '</div>';
			echo '</a>';
			echo "\n";
		}
		echo '</div>'."\n";
		echo '</details>'."\n";
	}
?>
		</div>
		<div>
			<h2>Grouped By Source Reference</h2>
<?
	$status = $status_cache->getGroupBySourceReference($version);
	$groups = $sources_cache->getGroupBySourceRef($version);

	foreach ($groups as $prefix => $list) {
		echo '<details><summary>' . htmlspecialchars($prefix) . '</summary>'."\n";
		echo '<div class=list_block>'."\n";
		if (strcmp($version, '5.1') >= 0) {
			usort($list, function($a, $b) {
				return strcmp(vSourceFixup($a), vSourceFixup($b));
			});
		}
		foreach ($list as $list_item) {
			$sheet = $status[$list_item]['sheet'];
			$images = $status[$list_item]['images'];
			$text = $list_item;
			if (strcmp($version, '5.1') >= 0) {
				$text = vSourceFixup($text);
			}
			
			if ($sheet === 2) {
				echo '<a href="?find=' . htmlspecialchars($list_item) . '" style="background:yellow">';
			} else if ($sheet === 1) {
				echo '<a href="?find=' . htmlspecialchars($list_item) . '" style="background:#999;opacity:.6">';
			} else {
				echo '<a href="?find=' . htmlspecialchars($list_item) . '">';
			}
			echo '<span>' .  htmlspecialchars($text) . '</span>';
			echo '<div>';
			foreach ($images as $img) {
				if (isset($img)) {
					echo '<img loading=lazy src="../data' . $img.'">';
				}
			}
			echo '</div>';
			echo '</a>';
			echo "\n";
		}
		echo '</div>'."\n";
		echo '</details>'."\n";
	}
?>
		</div>
	</div>
</div>
