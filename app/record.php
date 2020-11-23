<?php

define('MEETING_SESSION', 51);

require_once 'vendor/autoload.php';
require_once 'z.log.php';
Log::disable();
require_once 'library.php';
require_once 'router.php';
require_once 'user_chk.php';

$sources_cache   = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache       = new IDSCache();

if (!preg_match('@^[0-9]{5}$@', $_GET['id'])) {
	throw new Exception('Invalid ID');
}
$sq_number = trim($_GET['id']);
$char = $character_cache->get($sq_number);

echo '<table class=dicussion_record data-id="'.$char->data[0].'">';
echo '<col width=200>';
echo '<col width=auto>';
echo '<thead><tr><th>Type</th><th>Description</th></tr></thead>';
foreach (DBDiscussionRecord::getAll($char->data[0]) as $cm) {
	echo '<tr>';
	echo '<td><b>'.htmlspecialchars($cm->type).'</b><br>IRG #'.$cm->session.'<br>';
	echo $cm->toLocalDate();
	echo ' ';
	echo $cm->toLocalTime();
	echo ' ';
	echo $cm->toLocalTimezone();
	echo '<br>';
	echo 'As recorded by ';
	$cm_user = IRGUser::getById($cm->user);
	echo htmlspecialchars($cm_user->getName());
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

	$cm->comment = preg_replace_callback('@ to U\+([0-9A-F]{4,5})@', function ($m) {
		return ' to ' . codepointToChar('U+' . $m[1]) . ' (U+' . $m[1] . ')';
	}, $cm->value);
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
		foreach ($matches[1] as $i => $match) {
			if (!empty($matches[2][$i])) {
				$codepoint = charToCodepoint($match);
				echo getImageHTML($codepoint);
			}
			if (!empty($matches[3][$i])) {
				$year = $matches[4][$i];
				$sn = $matches[5][$i];
				if ($year === '2017') {
					$__c = $character_cache->get($sn);
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
	$text = preg_replace_callback('@{{WS2017-(([0-9]){5})}}@', function ($m) use ($character_cache) {
		$char = $character_cache->get($m[1]);
		ob_start();
		echo '<a href="?id=' . $m[1] . '" target=_blank>';
		$char->renderPart4();
		echo '</a>';
		return ob_get_clean();
	}, $text);
	$text = preg_replace_callback('@{{(([0-9]){5})}}@', function ($m) use ($character_cache) {
		$char = $character_cache->get($m[1]);
		ob_start();
		echo '<a href="?id=' . $m[1] . '" target=_blank>';
		$char->renderPart4();
		echo '</a>';
		return ob_get_clean();
	}, $text);

	echo $text;
	echo '</td>';
	echo '</tr>';
}
echo '</table>';
