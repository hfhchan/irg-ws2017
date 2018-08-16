<?php

class WSCharacter {

	public $sheet;
	public $data;

	public function __construct(StdClass $result) {
		$this->sheet = $result->sheet;
		$this->data  = $result->data;
		
		if (!isset($this->data[Workbook::DISCUSSION_RECORD])) {
			$this->data[Workbook::DISCUSSION_RECORD] = '';
		}
		
		// Clear Variants field set to "No"
		foreach (Workbook::SIMILAR as $sim) {
			if ($this->data[$sim] === 'No') {
				$this->data[$sim] = null;
			}
			if ($this->data[$sim] === 'N') {
				$this->data[$sim] = null;
			}
		}

		// Remove PUA
		$this->data[Workbook::IDS] = strtr($this->data[Workbook::IDS], [
			codepointToChar('U+E832') => codepointToChar('U+9FB8'),
			codepointToChar('U+2ED7') => '雨',
			codepointToChar('U+FA5E') => '艹',
			codepointToChar('U+2EBF') => '艹',
			codepointToChar('U+2EBE') => '艹',
			codepointToChar('U+2F73') => '穴',
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
		]);
		
		// Add "Page" to K Page field.
		if (!empty($this->data[Workbook::K_EVIDENCE_NAME[1]])) {
			$this->data[Workbook::K_EVIDENCE_NAME[1]] = 'page ' . $this->data[Workbook::K_EVIDENCE_NAME[1]];
		}
		// Add "Row" to K Page field.
		if (!empty($this->data[Workbook::K_EVIDENCE_NAME[2]])) {
			$this->data[Workbook::K_EVIDENCE_NAME[2]] = 'row ' . $this->data[Workbook::K_EVIDENCE_NAME[2]];
		}
		// Add "Position" to K Page field.
		if (!empty($this->data[Workbook::K_EVIDENCE_NAME[3]])) {
			$this->data[Workbook::K_EVIDENCE_NAME[3]] = 'position ' . $this->data[Workbook::K_EVIDENCE_NAME[3]];
		}
		// Add "Reading" to V Page field.
		if (!empty($this->data[Workbook::V_EVIDENCE_NAME[1]])) {
			$this->data[Workbook::V_EVIDENCE_NAME[1]] = '(Reading: ' . $this->data[Workbook::V_EVIDENCE_NAME[1]] . ')';
		}
		// Add "Reading" to G Page field.
		if (!empty($this->data[Workbook::G_EVIDENCE_NAME[1]])) {
			$this->data[Workbook::G_EVIDENCE_NAME[1]] = '(Reading: ' . $this->data[Workbook::G_EVIDENCE_NAME[1]] . ')';
		}
		
		// Add "Page" to UK Page field.
		if (!empty($this->data[Workbook::UK_EVIDENCE_NAME[1]])/* && ctype_digit($this->data[Workbook::UK_EVIDENCE_NAME[1]])*/) {
			$this->data[Workbook::UK_EVIDENCE_NAME[1]] = 'page ' . $this->data[Workbook::UK_EVIDENCE_NAME[1]];
		}
		
		// Fetch JPEGs for SAT
		if (!empty($this->data[Workbook::SAT_SOURCE])) {
			$sat_source = $this->data[Workbook::SAT_SOURCE];

			$sat = file_get_contents('../data/sat-evidence/part1-mapping.txt');
			foreach (explode("\n", $sat) as $line) {
				if (str_startswith($line, $sat_source)) {
					list($a, $page) = explode("\t", $line);
					$page = trim($page);
					$this->data[Workbook::SAT_EVIDENCE] = 'scan-'.str_pad($page, 6, '0', STR_PAD_LEFT).'.png';
				}
			}
			$sat = file_get_contents('../data/sat-evidence/part2-mapping.txt');
			foreach (explode("\n", $sat) as $i => $line) {
				if (str_startswith($line, $sat_source)) {
					$page = floor($i / 2) + 1;
					$this->data[Workbook::SAT_EVIDENCE] = 'scan2-'.str_pad($page, 6, '0', STR_PAD_LEFT).'.png';
				}
			}
			$sat = file_get_contents('../data/sat-evidence/part3-mapping.txt');
			foreach (explode("\n", $sat) as $i => $line) {
				if (str_startswith($line, $sat_source)) {
					$page = floor($i / 3) + 1;
					$this->data[Workbook::SAT_EVIDENCE] = 'scan3-'.str_pad($page, 6, '0', STR_PAD_LEFT).'.png';
				}
			}
			$sat = file_get_contents('../data/sat-evidence/part4-mapping.txt');
			foreach (explode("\n", $sat) as $i => $line) {
				if (str_startswith($line, $sat_source)) {
					$page = floor($i / 2) + 1;
					$this->data[Workbook::SAT_EVIDENCE] = 'scan4-'.str_pad($page, 6, '0', STR_PAD_LEFT).'.png';
				}
			}
		}
		
		// Fetch JPEGs for UTC:
		if (!empty($this->data[Workbook::UTC_SOURCE])) {
			$utc_source = $this->data[Workbook::UTC_SOURCE];
			$files = glob('../data/utc-evidence/' . $utc_source . '*');
			$files = array_map("basename", $files);
			$this->data[Workbook::UTC_EVIDENCE] = implode(';', $files);
		}
		
		// Fix PDF for G-source
		if ($this->data[Workbook::G_SOURCE] === 'GDM-00137') {
			$this->data[Workbook::G_EVIDENCE] = 'GDM-00137.pdf';
		}
		
		// Apply Actions
		$actions = DBActions::getAll($this->data[0]);
		foreach ($actions as $action) {
			if ($action->type === 'UNIFY') {
				$src = $action->value;
				$character_cache = new CharacterCache();
				$char = $character_cache->get(substr($src, 0, 5));
				foreach (Workbook::SOURCE as $source_index) {
					if (empty($this->data[$source_index]) && empty($this->data[$source_index + 1])) {
						$this->data[$source_index] = $char->data[$source_index];
						$this->data[$source_index + 1] = $char->data[$source_index + 1];
					}
				}
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Unify ' . $src . " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UNIFIED BY (Working Set)') {
				$dest = $action->value;
				$this->sheet = 1;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Unified by ' . $dest . " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UNIFIED BY (Horizontal Extension)') {
				$dest = $action->value;
				$this->sheet = 1;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Unified by ' . $dest . " with Horizontal Extension (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UNIFIED BY (IVD)') {
				$dest = $action->value;
				$this->sheet = 1;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Unified by ' . $dest . " via IVD (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'NOT_UNIFY') {
				$src = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Not unify ' . $src . " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'NOT_UNIFIED_BY') {
				$this->sheet = 0;
				$dest = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Not unified by ' . $dest . " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'WITHDRAWN') {
				$this->sheet = 1;
				$dest = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'WITHDRAWN in ' . $dest . " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'EVIDENCE_ACCEPTED') {
				$this->sheet = 0;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Evidence Accepted ' . " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'PENDING') {
				$this->sheet = 2;
				$dest = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Pending: ' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'PENDING_RESOLVED') {
				$this->sheet = 0;
				$dest = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Pending Resolved: ' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_RADICAL') {
				$dest = $action->value;
				$this->data[Workbook::RADICAL] = $dest;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Radical=' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_SC') {
				$dest = $action->value;
				$this->data[Workbook::STROKE] = $dest;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'SC=' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_FS') {
				$dest = $action->value;
				$this->data[Workbook::FS] = $dest;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'FS=' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_TC') {
				$dest = $action->value;
				$this->data[Workbook::TOTAL_STROKE] = $dest;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'TC=' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_IDS') {
				$dest = $action->value;
				$this->data[Workbook::IDS] = $dest;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'IDS=' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_TRAD_SIMP') {
				$dest = $action->value;
				$this->data[Workbook::TS_FLAG] = $dest;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'T/S=' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'UPDATE_GLYPH_SHAPE') {
				$desc = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Glyph shape to be updated: ' . $desc .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'MODIFY_CODED_CHARACTER') {
				$dest = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Modify Coded Character: ' . $dest .  " (IRG#" . $action->session . ")\n";
			}
			if ($action->type === 'OTHER_DECISION') {
				$dest = $action->value;
				$this->data[Workbook::DISCUSSION_RECORD] .= 'Decision: ' . $dest .  " (IRG#" . $action->session . ")\n";
			}
		}
	}
	
	public function getAllSources() {
		$src = [];
		foreach (Workbook::SOURCE as $source) {
			if (!empty($this->data[$source])) {
				$src[] = $this->data[$source];
			}
		}
		return $src;
	}
	
	public function getSourceIndex() {
		$fields = array_values(Workbook::getFields());
		foreach ($fields as $i => $source) {
			if (!empty($this->data[$source[0]])) {
				return $i;
			}
		}
		return 0;
	}

	public function getSources() {
		foreach (Workbook::SOURCE as $source) {
			if (!empty($this->data[$source])) {
				return $this->data[$source];
			}
		}
		return '';
	}

	public function getRadicalStroke() {
		if (strpos($this->data[Workbook::RADICAL], '.1') !== false) {
			$rad = substr($this->data[Workbook::RADICAL], 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->data[Workbook::RADICAL];
			$simpRad = 0;
		}

		return getIdeographForRadical($rad)[0] . ($simpRad ? "'" : '') . ' ' . $rad . ($simpRad ? "'" : '') . '.' . $this->data[Workbook::STROKE];
	}

	public function getRadicalStrokeFull() {
		if (strpos($this->data[Workbook::RADICAL], '.1') !== false) {
			$rad = substr($this->data[Workbook::RADICAL], 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->data[Workbook::RADICAL];
			$simpRad = 0;
		}

		return $rad . ($simpRad ? ".1" : '.0') . '.' . $this->data[Workbook::STROKE];
	}

	public function getFirstStroke() {
		if ($this->data[Workbook::FS] == '1') return '橫';
		if ($this->data[Workbook::FS] == '2') return '豎';
		if ($this->data[Workbook::FS] == '3') return '撇';
		if ($this->data[Workbook::FS] == '4') return '點';
		if ($this->data[Workbook::FS] == '5') return '折';
		return 'N/A';
	}
	
	public function getTotalStrokes() {
		return $this->data[Workbook::TOTAL_STROKE];
		// $strokes = [];
		// foreach (Workbook::TOTAL_STROKES as $col) {
		// 	if (!empty($this->data[$col])) {
		// 		$s = explode(',', $this->data[$col]);
		// 		$s = array_map("trim", $s);
		// 		$strokes = array_merge($strokes, $s);
		// 	}
		// }
		// return implode(', ', $strokes);
	}

	public function getCodeChartCutting() {
		$file0 = file_get_contents(__DIR__ . '/../data/charts/map.sheet0.txt');
		$lines = explode("\n", $file0);
		$lines = array_map("trim", $lines);
		foreach ($lines as $i => $line) {
			if ($line === $this->data[0]) {
				$page = floor($i / 9) + 1;
				$row = $i % 9 + 1;
				return [0, $page, $row];
			}
		}
		
		$file1 = file_get_contents(__DIR__ . '/../data/charts/map.sheet1.txt');
		$lines = explode("\n", $file1);
		$lines = array_map("trim", $lines);
		foreach ($lines as $i => $line) {
			if ($line === $this->data[0]) {
				$page = floor($i / 9) + 1;
				$row = $i % 9 + 1;
				return [1, $page, $row];
			}
		}
		
		throw new Exception('Not Found');
	}

	public function hasReviewedUnification() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->hasReviewedUnification($this->data[0]);
	}
	public function hasReviewedAttributes() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->hasReviewedAttributes($this->data[0]);
	}
	public function setReviewedUnification() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->setReviewedUnification($this->data[0]);
	}
	public function setReviewedAttributes() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->setReviewedAttributes($this->data[0]);
	}

	public function renderPDAM2_2() {
		return;
	}

	public function renderCodeChartCutting($class = 'ws2017_cutting', $start=840, $end = 2800, $width=1154) {
		list($pg_sheet, $pg_page, $pg_row) = $this->getCodeChartCutting();
		$filename = 'cache/' . 'canvas' . $this->data[0] . $class . '.png';
		if (file_exists($filename)) {
?>
<div class="<?=htmlspecialchars($class)?>"><img src="<?=html_safe($filename)?>"></div>
<?php
			return;
		}
		$suffix = rand(10000,99999);

		Log::add('Render Char Cutting Start ' . $this->data[0]);

		list($pg_sheet, $pg_page, $pg_row) = $this->getCodeChartCutting();

		if ($pg_sheet === 0) {
			$pg_src = 'sheet0/sheet0-000' . sprintf('%03d', $pg_page) . '.png';
		}
		if ($pg_sheet === 1) {
			$pg_src = 'sheet0/sheet0-000' . sprintf('%03d', ($pg_page + 530)) . '.png';
		}
		if ($pg_sheet === 2) {
			$pg_src = 'sheet2/-000' . sprintf('%03d', $pg_page) . '.png';
		}
?>
<div class="<?=htmlspecialchars($class)?>"><canvas id=canvas<?=$this->data[0]?>-<?=$suffix?> width=577 height=93></canvas></div>
<script>
window.delay = window.delay || 0;
(function(delay) {
	var imagecolorat = function(pix, x, y, width) {
		var offset = y * width + x;
		return [pix[offset * 4], pix[offset * 4 + 1], pix[offset * 4 + 2]];
	}
	var canvas2 = document.getElementById('canvas<?=$this->data[0]?>-<?=$suffix?>');
	var ctx2    = canvas2.getContext('2d');

	window.delay += 50;
	window.setTimeout(function() {
		console.log('Loading from', <?=json_encode($pg_src)?>);
		var image   = new Image();
		image.src = '../data/charts/' + <?=json_encode($pg_src)?>;
		image.onload = function() {
			var canvas = document.createElement('canvas'),
				ctx = canvas.getContext('2d');

			var width = image.naturalWidth;
			var height = image.naturalHeight;
			canvas.width = width;
			canvas.height = height;
			ctx.drawImage(image, 0, 0);
			var imgd = ctx.getImageData(0, 0, width, height);
			var pix = imgd.data;
			
			var offsets = [];
			var offsets2 = [];
			var x = 40;
			for (var y = 200; y < 2400; y++) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 16 && rgb[1] < 16 && rgb[2] < 16) {
					offsets.push(y);
					y += 10;
				}
			}
			for (var y = 2400; y > 200; y--) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 16 && rgb[1] < 16 && rgb[2] < 16) {
					offsets2.unshift(y);
					y -= 10;
				}
			}
			
			var left = <?=$start?>;
			var y = offsets[0] + 4;
			for (var x = <?=$start?>; x < <?=($start+200)?>; x++) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 75 && rgb[1] < 75 && rgb[2] < 75) {
					left = x;
					break;
				}
			}
			var right = <?=$end?>;
			for (var x = <?=$end?>; x > <?=$end-140?>; x--) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 75 && rgb[1] < 75 && rgb[2] < 75) {
					right = x;
					break;
				}
			}

			right += 2;
			console.log('x and y offset for page ', left, right);

			var top = offsets[<?=$pg_row?>];
			var btm = offsets2[<?=($pg_row + 1)?>];
			console.log(offsets[<?=($pg_row + 1)?>]);
			console.log(offsets2[<?=($pg_row + 1)?>]);

			var new_width = <?=$width?>;
			var new_height = Math.ceil((btm - top) * new_width / (right - left));

			canvas2.width = new_width;
			canvas2.height = new_height;
			ctx2.drawImage(image, left, top, right - left, btm - top, 0, 0, new_width, new_height);

			window.setTimeout(function() {
				var imgAsDataURL = canvas2.toDataURL("image/png");
				$.post('list.php', {
					'store': "canvas<?=$this->data[0]?><?=$class?>.png",
					"data": imgAsDataURL
				});
			}, 300);

			image.src = 'about:blank';
			canvas = null;
		}
	}, delay);
})(window.delay);
</script>
<?php
		Log::add('Render Char Cutting End ' . $this->data[0]);
	}

	public function getMatchedCharacter() {
		$ids = parseStringIntoCodepointArray(str_replace(' ', '', $this->data[Workbook::IDS]));
		$ids = array_values(array_map(function($d) {
			if ($d[0] === 'U') {
				return codepointToChar($d);
			}
			return $d;
		}, $ids));
		
		$matched = \IDS\getCharByIDS($ids);
		return $matched;
	}

	public function renderPart1() {
?>
		<div class=ws2017_chart_sn><?=$this->data[0]?><br><?=$this->data[Workbook::TS_FLAG] ? '簡' : '繁';?></div>
		<div class=ws2017_chart_attributes>
			<div><?=$this->getRadicalStroke()?></div>
			<div>
				<div>
<?php
		$ids = parseStringIntoCodepointArray($this->data[Workbook::IDS]);
		foreach ($ids as $component) {
			if (!empty(trim($component))) {
				if ($component[0] === 'U') {
					if (!env::$readonly) echo '<a href="../../../fonts/gen-m.php?name=u'.substr($component, 2).'" target=_blank class=ids_component>';
					else echo '<span>';
					echo codepointToChar($component);
					if (!env::$readonly) echo '</a>';
					else echo '</span>';
				} else {
					echo html_safe($component);
				}
			}
		}
		if (empty($this->data[Workbook::IDS])) {
			echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
		}
?>
				</div>
			</div>
			<div class=ws2017_chart_attributes_strokes>
				<div class=ws2017_chart_attributes_strokes_fs><?=$this->getFirstStroke()?></div>
				<div class=ws2017_chart_attributes_strokes_ts><?=$this->getTotalStrokes()?></div>
			</div>
		</div>
<?php
	}

	public function renderPart2() {
?>
		<table class=ws2017_chart_table_sources>
			<tr>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::G_SOURCE]) || isset($this->data[Workbook::G_SOURCE+1])) {?>
					<img src="<?=EVIDENCE_PATH?>/g-bitmap/<?=substr($this->data[Workbook::G_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
					<?=$this->data[Workbook::G_SOURCE]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::K_SOURCE]) || isset($this->data[Workbook::K_SOURCE+1])) {?>
					<img src="<?=EVIDENCE_PATH?>/k-bitmap/<?=substr($this->data[Workbook::K_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
					<!--img src="http://www.koreanhistory.or.kr/newchar/fontimg/KC<?=substr($this->data[Workbook::K_SOURCE+1], 3, -4)?>_48.GIF" width="32" height="32"><br-->
					<?=$this->data[Workbook::K_SOURCE]?><?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::SAT_SOURCE]) || isset($this->data[Workbook::SAT_SOURCE+1])) {?>
					<img src="<?=EVIDENCE_PATH?>/sat-bitmap/<?=substr($this->data[Workbook::SAT_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
					<!--img src="https://glyphwiki.org/glyph/sat_g9<?=substr($this->data[Workbook::SAT_SOURCE+1], 4, -4)?>.svg" width="32" height="32"><br-->
					<?=$this->data[Workbook::SAT_SOURCE]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::T_SOURCE]) || isset($this->data[Workbook::T_SOURCE + 1])) {?>
					<!--img src="https://www.cns11643.gov.tw/cgi-bin/ttf2png?page=<?=hexdec(substr($this->data[Workbook::T_SOURCE], 1, -5))?>&amp;number=<?=substr($this->data[Workbook::T_SOURCE], -4)?>&amp;face=sung&amp;fontsize=512" width=32 height=32><br-->
					<img src="<?=EVIDENCE_PATH?>/t-bitmap/<?=substr($this->data[Workbook::T_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br>
					<?=$this->data[Workbook::T_SOURCE]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::UTC_SOURCE])) {?>
					<img src="<?=EVIDENCE_PATH?>/utc-bitmap/<?=substr($this->data[Workbook::UTC_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br><?=$this->data[Workbook::UTC_SOURCE]?>
				<? } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::UK_SOURCE])) {?>
					<img src="<?=EVIDENCE_PATH?>/uk-bitmap/<?=$this->data[Workbook::UK_SOURCE]?>.png" width="32" height="32"><br><?=$this->data[Workbook::UK_SOURCE]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($this->data[Workbook::V_SOURCE])) {?>
					<img src="<?=EVIDENCE_PATH?>/v-bitmap/<?=substr($this->data[Workbook::V_SOURCE+1], 0, -4)?>.png" width="32" height="32"><br><?=$this->data[Workbook::V_SOURCE]?>
				<? } ?>
			</td>
			</tr>
		</table>
<?php
	}

	public function renderPart3() {
?>
		<div class=ws2017_chart_table_discussion>
			<div>
				<? if ($this->sheet) echo '<b>'.CharacterCache::SHEETS[$this->sheet] . '</b><br>'; ?>
				<?=$this->data[Workbook::DISCUSSION_RECORD]?>
<?php
		if ((isset($this->data[Workbook::K_SOURCE])) && file_exists('../data/k-bitmap/' . substr($this->data[Workbook::K_SOURCE+1], 0, -4) . '-updated.png')) {
			echo '<br>Glyph Updated: <img src="' . EVIDENCE_PATH . '/k-bitmap/' . substr($this->data[Workbook::K_SOURCE+1], 0, -4) . '-updated.png" width="32" height="32">';
		}
?>
			</div>
		</div>
<?php
	}
}
