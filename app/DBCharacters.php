<?php

declare(strict_types=1);

const USE_GLYPH_WIKI_FOR_IDS = false;
//const USE_GLYPH_WIKI_FOR_IDS = true;

class DBCharacters {

	public $sn;
	public $radical;
	public $stroke_count;
	public $first_stroke;
	public $trad_simp_flag;
	public $ids;
	public $total_stroke_count;
	public $sources;
	public $version;
	public $discussion_record;
	public $status;
	
	public $g_source;
	public $k_source;
	public $uk_source;
	public $sat_source;
	public $t_source;
	public $utc_source;
	public $v_source;

	public static function toSessionNumber($version) {
		if ($version == '5.2') return 5 + 2 + 49;
		if ($version == '5.1') return 5 + 1 + 49;
		if (strcmp($version, '5.0') <= 0) {
			return intval($version) + 49;
		}
		return intval($version) + 2 + 49;
	}

	public function __construct(object $result, string $version = '4.0') {
		$this->version = $version;

		$this->sn = sprintf('%05d', $result->sn);
		$this->radical            = $result->radical;
		$this->stroke_count       = $result->stroke_count;
		$this->first_stroke       = $result->first_stroke;
		$this->trad_simp_flag     = $result->trad_simp_flag;
		$this->ids                = $result->ids;
		$this->total_stroke_count = $result->total_stroke_count;
		$this->base_version       = $result->version;
		$this->discussion_record  = $result->data;
		$this->status             = (int) $result->status;

		if (!isset($this->discussion_record)) {
			$this->discussion_record = '';
		}
		
		foreach (explode(';', $result->sources) as $source) {
			if ($source[0] === 'G') {
				$this->g_source = $source;
			}
			if ($source[0] === 'K') {
				$this->k_source = $source;
			}
			if ($source[0] === 'U' && $source[1] === 'K') {
				$this->uk_source = $source;
			}
			if ($source[0] === 'U' && $source[1] === 'S') {
				$this->sat_source = $source;
			}
			if ($source[0] === 'T') {
				$this->t_source = $source;
			}
			if ($source[0] === 'U' && $source[1] === 'T') {
				$this->utc_source = $source;
			}
			if ($source[0] === 'V') {
				$this->v_source = $source;
			}
		}
		$this->sources = $result->sources;

		// Remove PUA
		$this->ids = strtr($this->ids, [
			codepointToChar('U+E832') => codepointToChar('U+9FB8'),
			codepointToChar('U+2ED7') => '雨',
			codepointToChar('U+FA5E') => '艹',
			codepointToChar('U+2EBF') => '艹',
			codepointToChar('U+2EBE') => '艹',
			codepointToChar('U+2ECD') => '辶',
			codepointToChar('U+2F73') => '穴',
			codepointToChar('U+F90A') => '金',
			codepointToChar('U+F907') => '龜',
			"\xC2\xA0" => '', // NBSP
			'_xD876_'  => '',
			'_x000D_'  => '',
			'N[51130]' => '⿻二刀',
			'𤣩'       => '王',
			'𥫗'       => '竹',
			'｜'       => '丨',
			'⺮'       => '竹',
			'/⿰亻⿱敖犬' => '',
			'/⿰忄⿱老至' => '',
			'/⿰山⿸厂火' => '',
			' ' => '', // Space
		]);
		
		// Add new line
		if (strlen($this->discussion_record) != 0) {
			$this->discussion_record = trim($this->discussion_record) . "\n";
		}

		// Get Changes
		$skipImportDiscussionRecord = [];
		$changes = DBChanges::getChangesForSNVersion($this->sn, $version);
		foreach ($changes as $change) {
			if ($change->getSN() == $this->sn && $change->type === 'Discussion Record') {
				$skipImportDiscussionRecord[$change->discussion_record_id] = true;
			}
		}
		
		// Get Minutes
		$minutes = [];
		$max_session = self::toSessionNumber($version);
		$actions = DBDiscussionRecord::getAll($this->sn);
		foreach ($actions as $action) {
			if (isset($skipImportDiscussionRecord[$action->id])) {
				continue;
			}
			if ($action->session >= $max_session) {
				continue;
			}
			// Minutes for IRG #50 and #51 are not official record
			if ($action->session <= 51) {
				continue;
			}
			if ($action->type === 'OTHER_DECISION') {
				$action->value = $action->value .  ", IRG " . $action->session . '.';
				$minutes[] = $action;
			}
		}
		
		// Apply Changes
		$this->applyChanges($changes, $minutes);
	}
	
	public function applyChanges($changes, $minutes = null) {
		$supercededDiscussionRecords = [];
		foreach ($changes as $action) {
			if (preg_match('@^Discussion Record #([0-9]+)$@', $action->type, $matches)) {
				$supercededDiscussionRecords[] = $matches[1];
			}
		}

		$vSourceFixup = false;
		$discussionRecords = [];

		foreach ($changes as $action) {
			if ($action->type === 'Status') {
				if ($action->value === 'Unified' || $action->value === 'Withdrawn') {
					$this->status = 1;
				} else if ($action->value === 'Not Unified' || $action->value === 'Disunified' || $action->value === 'OK') {
					$this->status = 0;
				} else if ($action->value === 'Postponed') {
					$this->status = 2;
				}
			}
			if ($action->type === 'Radical') {
				$this->radical = $action->value;
			}
			if ($action->type === 'Stroke Count') {
				$this->stroke_count = $action->value;
			}
			if ($action->type === 'First Stroke') {
				$this->first_stroke = $action->value;
			}
			if ($action->type === 'Total Stroke Count') {
				$this->total_stroke_count = $action->value;
			}
			if ($action->type === 'IDS') {
				$this->ids = $action->value;
			}
			if ($action->type === 'Trad/Simp Flag') {
				$this->trad_simp_flag = $action->value;
			}
			
			if ($action->type === 'G Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->g_source = $action->value;
			}
			if ($action->type === 'K Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->k_source = $action->value;
			}
			if ($action->type === 'UK Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->uk_source = $action->value;
			}
			if ($action->type === 'USAT Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->sat_source = $action->value;
			}
			if ($action->type === 'T Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->t_source = $action->value;
			}
			if ($action->type === 'UTC Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->utc_source = $action->value;
			}
			if ($action->type === 'V Source') {
				if ($action->value === '(empty)') {
					$action->value = null;
				}
				$this->v_source = $action->value;
			}
			
			if (substr($action->type, 0, 17) === 'Discussion Record' && !isset($supercededDiscussionRecords[$action->id])) {
				if ($action->value !== '(superseded)') {
					$discussionRecords[] = (object) [
						'id' => $action->id,
						'value' => $action->value,
						'session' => $action->getEffectiveSession()
					];
				}
			}
		}

		if (isset($minutes)) {
			foreach ($minutes as $record) {
				$discussionRecords[] = (object) [
					'id' => 0,
					'value' => $record->value,
					'session' => $record->session
				];
			}
		}
		
		usort($discussionRecords, function($a, $b) {
			if ($a->session !== $b->session) {
				return strcmp($b->session, $a->session);
			}

			return $b->id - $a->id;
		});

		if (count($discussionRecords)) {
			$this->discussion_record = implode("\n", array_map(function($action) { return $action->value; }, $discussionRecords)) . "\n" . $this->discussion_record;
		}
	}
	
	public function getStatusText() {
		if ($this->status == 0) return "Main Set";
		if ($this->status == 1) return "Unified&Withdrawn";
		if ($this->status == 2) return "Pending";
		return "-";
	}

	public function getAllSources($vFixup = false) {
		$sources = [];

		if (isset($this->g_source     )) { $sources[] = $this->g_source;   }
		if (isset($this->k_source     )) { $sources[] = $this->k_source;   }
		if (isset($this->uk_source    )) { $sources[] = $this->uk_source;  }
		if (isset($this->sat_source   )) { $sources[] = $this->sat_source; }
		if (isset($this->t_source     )) { $sources[] = $this->t_source;   }
		if (isset($this->utc_source   )) { $sources[] = $this->utc_source; }
		if (isset($this->v_source     )) {
			if ($vFixup) $sources[] = vSourceFixup($this->v_source);
			else $sources[] = $this->v_source;
		}
		
		return $sources;
	}

	public function getSources() {
		$sources = $this->getAllSources();
		if (!empty($sources)) return $sources[0];
		return '';
	}

	public function getRadicalText() {
		if (strpos($this->radical, '.1') !== false) {
			$rad = substr($this->radical, 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->radical;
			$simpRad = 0;
		}
		
		if ($simpRad) {
			return $rad . ($simpRad ? "'" : '') . ' (' . getIdeographForSimpRadical($rad)[0] . ($simpRad ? "'" : '') . ') ';
		}

		return $rad . ($simpRad ? "'" : '') . ' (' . getIdeographForRadical($rad)[0] . ($simpRad ? "'" : '') . ') ';
	}

	public function getRadicalStroke() {
		if (strpos($this->radical, '.1') !== false) {
			$rad = substr($this->radical, 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->radical;
			$simpRad = 0;
		}

		return getIdeographForRadical($rad)[0] . ($simpRad ? "'" : '') . ' ' . $rad . ($simpRad ? "'" : '') . '.' . $this->stroke_count;
	}

	public function getRadicalStrokeFull() {
		if (strpos($this->radical, '.1') !== false) {
			$rad = substr($this->radical, 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->radical;
			$simpRad = 0;
		}

		return $rad . ($simpRad ? ".1" : '.0') . '.' . $this->stroke_count;
	}

	public function getFirstStroke() {
		if ($this->first_stroke == '1') return '㇐ (1)';
		if ($this->first_stroke == '2') return '㇑ (2)';
		if ($this->first_stroke == '3') return '㇒ (3)';
		if ($this->first_stroke == '4') return '㇔ (4)';
		if ($this->first_stroke == '5') return '㇠ (5)';
		return 'N/A';
	}
	
	public function getTotalStrokes() {
		return $this->total_stroke_count;
	}

	public function hasReviewedUnification($user_id) {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance($user_id);
		return $instance->hasReviewedUnification($this->sn);
	}

	public function hasReviewedAttributes($user_id) {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance($user_id);
		return $instance->hasReviewedAttributes($this->sn);
	}

	public function setReviewedUnification($user_id) {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance($user_id);
		return $instance->setReviewedUnification($this->sn);
	}

	public function setReviewedAttributes($user_id) {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance($user_id);
		return $instance->setReviewedAttributes($this->sn);
	}

	public function getMatchedCharacter() {
		$ids = parseStringIntoCodepointArray(str_replace(' ', '', $this->ids));
		$ids = array_values(array_map(function($d) {
			if ($d[0] === 'U') {
				return codepointToChar($d);
			}
			return $d;
		}, $ids));
		
		$matched = \IDS\getCharByIDS($ids);
		return $matched;
	}

	public function getIDSAsHTMLWithHyperLinks() {
		$ids = parseStringIntoCodepointArray($this->ids);
		foreach ($ids as $component) {
			if (!empty(trim($component))) {
				if ($component[0] === 'U') {
					if (!env::$readonly) echo '<a href="https://localhost/unicode/fonts/gen-m.php?name=u'.substr($component, 2).'" target=_blank class=ids_component>';
					else echo '<span>';
					
					if (USE_GLYPH_WIKI_FOR_IDS && hexdec(substr($component, 2)) >= hexdec('2A700')) {
						echo '<img src="https://glyphwiki.org/glyph/u'.strtolower(substr($component, 2)).'.50px.png" alt="' . codepointToChar($component) . '" width=20 height=20 style="vertical-align:-4px">';
					} else {
						echo codepointToChar($component);
					}
					
					if (!env::$readonly) echo '</a>';
					else echo '</span>';
				} else {
					echo html_safe($component);
				}
			}
		}
		if (empty($this->ids)) {
			echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
		}
	}
	
	public function getIDSAsHTML() {
		ob_start();

		$ids = parseStringIntoCodepointArray($this->ids);
		foreach ($ids as $component) {
			if (!empty(trim($component))) {
				if ($component[0] === 'U') {
					echo '<span>';
					if (USE_GLYPH_WIKI_FOR_IDS && hexdec(substr($component, 2)) >= hexdec('2A700')) {
						echo '<img src="https://glyphwiki.org/glyph/u'.strtolower(substr($component, 2)).'.50px.png" alt="' . codepointToChar($component) . '" width=20 height=20 style="vertical-align:-4px">';
					} else {
						echo codepointToChar($component);
					}
					echo '</span>';
				} else {
					echo html_safe($component);
				}
			}
		}
		if (empty($this->ids)) {
			echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
		}
		
		return ob_get_clean();
	}

	public function renderPart1() {
?>
		<div class=ws2017_chart_sn><?=$this->sn?><br><?=$this->trad_simp_flag ? 'Simp' : 'Trad';?></div>
		<div class=ws2017_chart_attributes>
			<div class=ws2017_chart_attributes_rs><?=$this->getRadicalStroke()?></div>
			<div class=ws2017_chart_attributes_ids>
				<div><?php $this->getIDSAsHTMLWithHyperLinks(); ?></div>
			</div>
			<div class=ws2017_chart_attributes_strokes_fs><?=$this->getFirstStroke()?></div>
			<div class=ws2017_chart_attributes_strokes_ts><?=$this->getTotalStrokes()?></div>
		</div>
<?php
	}
	
	public static function getFileName($prefix, $version = '4.0') {
		$files = self::getFileNames($prefix, $version);
		return !empty($files) ? array_values($files)[0] : null;
	}

	public static function getFileNames($prefix, $version = '4.0') {
		if (!is_string($prefix)) {
			throw new Exception("\$prefix should be string");
		}

		$files = [];
		$glyphs = DBCharacterGlyph::getAll($prefix, $version);
		foreach($glyphs as $row) {
			$files['v' . $row->version] = $row->path;
		}
		return $files;
	}
	
	private function renderPart2ImageCell($sourceReference) {
		$files = self::getFileNames($sourceReference, $this->version);
		
		$sourceReferenceTxt = $sourceReference;
		// Update V source
		if (strcmp($this->version, '5.1') >= 0) {
			$sourceReferenceTxt = vSourceFixup($sourceReferenceTxt);
		}
		
		if (count($files) > 1) {
			$i = 0;
			foreach ($files as $v => $file) {
?><img src="<?=EVIDENCE_PATH?><?=html_safe($file)?>" width="32" height="32" style="<? if ($i == 0) echo 'border:1px solid red'; else echo 'border:1px solid #333;opacity:.3'; ?>"><br>(<?=html_safe($v)?>)<br><br><?
				$i++;
			}
		} else {
?><img src="<?=EVIDENCE_PATH?><?=html_safe(array_values($files)[0])?>" width="32" height="32"><br><?
		}
		echo $sourceReferenceTxt;
	}

	private function renderPart4ImageCell($source) {
		$files = self::getFileNames($source, $this->version);
		$files = array_values($files);
		if (isset($files[0])) {
?>
				<img src="<?=EVIDENCE_PATH?><?=html_safe($files[0])?>" width="56" height="56">
<?
		} else {
?>
				<img src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" style="background:red" width="56" height="56">
<?
		}
	}

	public function renderPart2() {
		// 'http://www.koreanhistory.or.kr/newchar/fontimg/KC'.substr($this->k_source, 3, -4).'_48.GIF'
		// 'https://glyphwiki.org/glyph/sat_g9'.substr($this->sat_source, 4, -4).'.svg'
		// 'https://www.cns11643.gov.tw/cgi-bin/ttf2png?page='.hexdec(substr($this->t_source, 1, -5)).'&amp;number='.substr($this->t_source, -4).'&amp;face=sung&amp;fontsize=512';
		
		$sources = ['g_source', 'k_source', 'sat_source', 't_source', 'utc_source', 'uk_source', 'v_source'];

		echo '<table class=ws2017_chart_table_sources><tr>'."\r\n";
		foreach ($sources as $source) {
			echo '<td>';
			if (isset($this->$source)) $this->renderPart2ImageCell($this->$source);
			echo '</td>' . "\r\n";
		}
		echo '</tr></table>' . "\r\n";
	}

	public function renderPart3() {
?>
		<div class=ws2017_chart_table_discussion>
			<div>
<? if ($this->status) echo '<b>'.CharacterCache::getSheetName($this->version, $this->status) . '</b><br>'; ?>
<?=nl2br($this->discussion_record)?>
			</div>
		</div>
<?php
	}


	public function renderPart4($vFixup = false) {
		
		if ($this->version == '1.1') {
			echo '<small>(Data for version 1.1 not available, showing version 2.0)</small>';
		}
		
		if (!defined('EVIDENCE_PATH')) {
			define('EVIDENCE_PATH', '../data');
		}
?>
<a href="./?id=<?=$this->sn?>" target=_blank class=ws2017_chart_sources_block<?
	if ($this->version == '1.1') {
		echo ' style="opacity:.3;outline:4px solid red"';
	}
?>>
<div class="ws2017_chart_sources sheet-<?=$this->status?>">
<div class=ws2017_chart_source_head>
	<div class=ws2017_chart_source_head_1><?=$this->sn?></div>
	<div class=ws2017_chart_source_head_2><?=$this->getRadicalStroke()?></div>
	<div class=ws2017_chart_source_head_2><?=$this->getTotalStrokes()?> <?=$this->getFirstStroke()?></div>
</div>
<div class=ws2017_chart_source_blocks>
<?php
	foreach ($this->getAllSources() as $source) {
		$sourceText = $vFixup ? vSourceFixup($source) : $source;
?>
	<div class=ws2017_chart_source_block>
		<? $this->renderPart4ImageCell($source); ?>
		<?=$sourceText?>
	</div>
<?php
	}
?>
</div>
<div class=ws2017_chart_source_ids><?=$this->trad_simp_flag ? 'Simp' : 'Trad';?> | <?=$this->getIDSAsHTML()?></div>
</div>
</a>
<?php
	}
	
	public static function getCharacter($sq_number, $version = '4.0') {
		if (!CharacterCache::hasVersion($version)) {
			throw new Exception("Unknown version number: " . $version);
		}

		if (strcmp($version, '2.0') <= 0) {
			throw new Exception('Not Implemented');
		}

		$q = Env::$db->prepare('SELECT * FROM "characters" WHERE "sn" = ? AND "version" <= ? ORDER BY "version" DESC LIMIT 1');
		$q->execute([ $sq_number, $version ]);
		$data = $q->fetch();
		if ($data) {
			return new DBCharacters($data, $version);
		}

		throw new Exception('Not Found');
	}
	
	public function getDiscussionRecord() {
		return explode("\n", trim($this->discussion_record));
	}
}
