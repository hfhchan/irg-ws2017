<?php

Log::add('DB Start');

$db = new PDO('sqlite:../data/review/current-database.sqlite3');
$db->exec('PRAGMA foreign_keys = ON');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Env::$db = $db;

if (file_exists('.ht.READONLY')) {
	Env::$readonly = true;
}

class Env {
	static $db = null;
	static $readonly = false;
}

Log::add('DB End');

require_once 'Workbook.php';
require_once 'SourcesCache.php';
require_once 'CharacterCache.php';
require_once 'IDSCache.php';
require_once 'WSCharacter.php';
require_once 'DBProcessedInstance.php';
require_once 'DBComments.php';
require_once 'DBActions.php';

function loadWorkbook() {
	return Workbook::loadWorkbook();
}

function html_safe($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


function codepointToChar($codepoint) {
	if (preg_match('@^U\+[0-9A-F]{4,5}$@', $codepoint)) {
		return iconv('UTF-32BE', 'UTF-8', pack("H*", str_pad(substr($codepoint, 2), 8, '0', STR_PAD_LEFT)));
	}
	throw new Exception('Invalid Input');
}

function charToCodepoint($char) {
	if (mb_strlen($char, 'UTF-8') === 1) {
		return 'U+'.strtoupper(ltrim(bin2hex(iconv('UTF-8', 'UTF-32BE', $char)),'0'));
	}
	throw new Exception('Invalid Input');
}

function str_startswith($string, $prefix) {
	if (strpos($string, $prefix) === 0) {
		return true;
	}
	return false;
}

function parseStringIntoCodepointArray($utf8) {
	$result = [];
	for ($i = 0; $i < strlen($utf8); $i++) {
		$char = $utf8[$i];
		$ascii = ord($char);
		if ($ascii < 128) {
			if ($char === '&') {
				$j = $i + 1;
				while (isset($utf8[$j]) && ord($utf8[$j]) < 128) {
					$j++;
				}
				$result[] = substr($utf8, $i, $j - $i);
				$i = $j;
			} else {
				$result[] = $char;
			}
		} else if ($ascii < 192) {
		} else if ($ascii < 224) {
			$ascii1 = ord($utf8[$i+1]);
			if( (192 & $ascii1) === 128 ){
				$result[] = substr($utf8, $i, 2);
				$i++;
			}
		} else if ($ascii < 240) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ){
				$unicode = (15 & $ascii) * 4096 +
						   (63 & $ascii1) * 64 +
						   (63 & $ascii2);
				$result[] = 'U+'.strtoupper(dechex($unicode));
				$i += 2;
			}
		} else if ($ascii < 248) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			$ascii3 = ord($utf8[$i+3]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ||
				(192 & $ascii3) === 128 ){
				$unicode = (15 & $ascii) * 262144 +
						   (63 & $ascii1) * 4096 +
						   (63 & $ascii2) * 64 +
						   (63 & $ascii3);
				$result[] = 'U+'.strtoupper(dechex($unicode));
				$i += 3;
			}
		}
	}
	return $result;
}

function getIdeographForSimpRadical($rad) {
	if ($rad === 120) return ['纟'];
	if ($rad === 147) return ['见'];
	if ($rad === 149) return ['讠'];
	if ($rad === 154) return ['贝'];
	if ($rad === 159) return ['车'];
	if ($rad === 167) return ['钅'];
	if ($rad === 168) return ['长'];
	if ($rad === 169) return ['门'];
	if ($rad === 178) return ['韦'];
	if ($rad === 181) return ['页'];
	if ($rad === 182) return ['风'];
	if ($rad === 184) return ['饣'];
	if ($rad === 187) return ['马'];
	if ($rad === 195) return ['鱼'];
	if ($rad === 196) return ['鸟'];
	if ($rad === 197) return ['卤'];
	if ($rad === 199) return ['麦'];
	if ($rad === 211) return ['齿'];
	if ($rad === 212) return ['龙'];
	if ($rad === 213) return ['龟'];
	return getIdeographForRadical($rad);
}

function getIdeographForRadical($rad) {
	$radicals = [
		['一'],
		['丨'],
		['丶'],
		['丿'],
		['乙','⺄'],
		['亅'],
		['二'],
		['亠'],
		['人','亻','𠆢'],
		['儿'], // RAD 10
		['入'],
		['八'],
		['冂'],
		['冖'],
		['冫'],
		['几','𠘨'],
		['凵'],
		['刀','刂'],
		['力'],
		['勹'], // RAD 20
		['匕'],
		['匚'],
		['匸'],
		['十'],
		['卜'],
		['卩'],
		['厂'],
		['厶'],
		['又'],
		['口'], // RAD 30
		['囗'],
		['土'],
		['士'],
		['夂'],
		['夊'],
		['夕'],
		['大'],
		['女'],
		['子'],
		['宀'], // RAD 40
		['寸'],
		['小'],
		['尢','兀'],
		['尸'],
		['屮'],
		['山'],
		['巛'],
		['工'],
		['己'],
		['巾'], // RAD 50
		['干'],
		['幺'],
		['广'],
		['廴'],
		['廾'],
		['弋'],
		['弓'],
		['彐'],
		['彡'],
		['彳'], // RAD 60
		['心','忄'],
		['戈'],
		['戶','户'],
		['手','扌'],
		['支'],
		['攴','攵'],
		['文'],
		['斗'],
		['斤'],
		['方'], // Rad 70
		['无'],
		['日'],
		['曰'],
		['月'],
		['木'],
		['欠'],
		['止'],
		['歹'],
		['殳'],
		['毋'], // Rad 80
		['比'],
		['毛'],
		['氏'],
		['气'],
		['水','氵'],
		['火','灬'],
		['爪','爫'],
		['父'],
		['爻'],
		['爿'], // Rad 90
		['片'],
		['牙'],
		['牛','牜'],
		['犬','犭'],
		['玄'],
		['玉','𤣩','王'],
		['瓜'],
		['瓦'],
		['甘'],
		['生'], // Rad 100
		['用'],
		['田'],
		['疋'],
		['疒'],
		['癶'],
		['白'],
		['皮'],
		['皿'],
		['目'],
		['矛'], // Rad 110
		['矢'],
		['石'],
		['示','礻'],
		['禸'],
		['禾'],
		['穴'],
		['立'],
		['竹', '𥫗'],
		['米'],
		['糸','糹'], // Rad 120
		['缶'],
		['网','罒'],
		['羊'],
		['羽'],
		['老'],
		['而'],
		['耒'],
		['耳'],
		['聿'],
		['肉','月'], // Rad 130
		['臣'],
		['自'],
		['至'],
		['臼'],
		['舌'],
		['舛'],
		['舟'],
		['艮'],
		['色'],
		['艸','艹'], // Rad 140
		['虍'],
		['虫'],
		['血'],
		['行'],
		['衣','衤'],
		['襾'],
		['見'],
		['角'],
		['言','訁'],
		['谷'], // Rad 150
		['豆'],
		['豕'],
		['豸'],
		['貝'],
		['赤'],
		['走'],
		['足','𧾷','⻊'],
		['身'],
		['車'],
		['辛'], // Rad 160
		['辰'],
		['辵','辶'],
		['邑','阝'],
		['酉'],
		['釆'],
		['里'],
		['金','釒'],
		['長','镸'],
		['門'],
		['阜','阝'], // Rad 170
		['隶'],
		['隹'],
		['雨','⻗'],
		['靑','青'],
		['非'],
		['面'],
		['革'],
		['韋'],
		['韭'],
		['音'], // Rad 180
		['頁'],
		['風'],
		['飛'],
		['食','飠'],
		['首'],
		['香'],
		['馬'],
		['骨'],
		['高','髙'],
		['髟'], // Rad 190
		['鬥'],
		['鬯'],
		['鬲'],
		['鬼'],
		['魚','𩵋'],
		['鳥'],
		['鹵'],
		['鹿'],
		['麥','麦'],
		['麻'], // Rad 200
		['黃','黄'],
		['黍'],
		['黑'],
		['黹'],
		['黽','黾'],
		['鼎'],
		['鼓'],
		['鼠'],
		['鼻'],
		['齊'], // Rad 210
		['齒'],
		['龍', '竜'],
		['龜'],
		['龠', '𠎤']
	];

	return $radicals[($rad - 1)];
}

function getTotalStrokes($codepoint) {
	$totalstrokes = 0;
	$fs = 0;
	foreach (file('../totalstrokes.txt') as $totalstrokesline) {
		if (strpos($totalstrokesline, $codepoint) !== false) {
			$line = trim(substr($totalstrokesline, strlen($codepoint)));
			@list($totalstrokes, $fs) = explode('|', $line);
		}
	}
	return [$totalstrokes, $fs];
}

function codepointIsIDC($codepoint) {
	$idc = [
		'U+2FF0' => true, 'U+2FF1' => true, 'U+2FF2' => true, 'U+2FF3' => true,
		'U+2FF4' => true, 'U+2FF5' => true, 'U+2FF6' => true, 'U+2FF7' => true,
		'U+2FF8' => true, 'U+2FF9' => true, 'U+2FFA' => true, 'U+2FFB' => true,
		'U+303E' => true
	];
	return isset($idc[$codepoint]);
}

require_once '../../../IDS/library.php';


