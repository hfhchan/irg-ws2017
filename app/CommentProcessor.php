<?php

class CommentProcessor {
	public $comment;
	public $version;

	public $normalized_text;

	public function __construct($cm) {
		$this->comment = $cm;
		$this->version = $cm->getVersion();

		$this->normalizeComment();
	}
	
	private function normalizeComment() {
		$content = $this->comment->comment;

		if ($this->comment->getCategoryForCommentType() === 'Unification') {
			$content = preg_replace_callback('@ with U\+([0-9A-F]{4,5})(\s*)(.+)?@', function ($m) {
				$char = codepointToChar('U+' . $m[1]);
				if (empty($m[2])) $m[2] = '';
				if (empty($m[3])) $m[3] = '';
				if (substr($m[3], 0, strlen($char)) === $char) {
					$m[2] = '';
					$m[3] = substr($m[3], strlen($char));
				}
				return ' with ' . $char . ' (U+' . $m[1] . ')' . $m[2] . $m[3];
			}, $content);

			$content = preg_replace_callback('@ to U\+([0-9A-F]{4,5})(\s*)(.+)?@', function ($m) {
				$char = codepointToChar('U+' . $m[1]);
				if (empty($m[2])) $m[2] = '';
				if (empty($m[3])) $m[3] = '';
				if (substr($m[3], 0, strlen($char)) === $char) {
					$m[2] = '';
					$m[3] = substr($m[3], strlen($char));
				}
				return ' to ' . $char . ' (U+' . $m[1] . ')' . $m[2] . $m[3];
			}, $content);
		}

		$this->normalized_text = $content;
	}
	
	public function getUnificationTargets() {
		global $character_cache;

		$firstLine = strtok($this->normalized_text, "\n");
		$firstLine = strtok($firstLine, ";");
		$str = ' ' . trim($firstLine);
		
		$targets = [];

		preg_match_all("/ with ([0-9]{5}) \\(.*\\)/u", $str, $matches);
		foreach ($matches[1] as $i => $match) {
			if (!empty($matches[1][$i])) {
				$targets['WS2017-' . $match] = true;
			}
		}
		
		preg_match_all("/ (([\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}])|(WS(2015|2017))-([0-9]{5}))/u", $str, $matches);


		foreach ($matches[1] as $i => $match) {
			if (!empty($matches[2][$i])) {
				$targets[$match] = true;
			}
			if (!empty($matches[3][$i])) {
				$targets[$match] = true;
			}
		}
		
		return array_keys($targets);
	}

	public function getAttributeChanges() {
		global $character_cache;

		$radChanges = [];
		$scChanges = [];
		$tcChanges = [];
		$fsChanges = [];
		$idsChanges = [];
		$tsChanges = [];

		preg_match_all("/r?R?adical should be changed to\s*([0-9]+)(.(0|1))?('?)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}

		preg_match_all("/r?R?adical should be .* \s*([0-9]+)(.(0|1))?('?)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}

		preg_match_all("/radical (should )?be.*\\(R([0-9]+),.*\\)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[2] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$ideograph = getIdeographForRadical(intval($match))[0];
				$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
			}
		}

		preg_match_all("/radical (should )?be.*?([0-9]+)\.(0|1)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[2] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[3][$i] === '1') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\''. ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}

		preg_match_all("/C?c?hange.*r?R?adical to\s*([0-9]+)(.(0|1))?('?)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}

		preg_match_all("/radical #([0-9]+)(.(0|1))?('?)/", strtolower($this->comment->comment), $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}

		preg_match_all("/radical change to.*\\(([0-9]+)(.(0|1))?('?)\\)/", strtolower($this->comment->comment), $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}
		
		preg_match_all("/RS?\\s*=\\s*([0-9]+)(.(0|1))?('?)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match. ' (' . $ideograph . ')'] = true;
				}
			}
		}
		
		preg_match_all("/Radical\\s*=\\s*([0-9]+)(.(0|1))?('?)/", $this->comment->comment, $radMatches);
		foreach ($radMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				if ($radMatches[2][$i] === '.1' || $radMatches[4][$i] === '\'') {
					$ideograph = getIdeographForSimpRadical(intval($match))[0];
					$radChanges['R=' . $match . '\'' . ' (' . $ideograph . ')'] = true;
				} else {
					$ideograph = getIdeographForRadical(intval($match))[0];
					$radChanges['R=' . $match . ' (' . $ideograph . ')'] = true;
				}
			}
		}

		preg_match_all("/SC\\s*to\\s*(-?[0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$scChanges['SC=' . $match] = true;
			}
		}

		preg_match_all("/SC\\s*=\\s*(-?[0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[1] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$scChanges['SC=' . $match] = true;
			}
		}

		preg_match_all("/(SC|Sc) should be changed to ([0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[2] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$scChanges['SC=' . $match] = true;
			}
		}

		preg_match_all("/(TC|TS)\\s*to\\s*([0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[2] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$tcChanges['TC=' . $match] = true;
			}
		}

		preg_match_all("/(TC|TS)\\s*=\\s*([0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[2] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$tcChanges['TC=' . $match] = true;
			}
		}

		preg_match_all("/(TC|TS|Tc|Ts) should be changed to ([0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[2] as $i => $match) {
			if ($match > 0 && $match < 215) {
				$tcChanges['TC=' . $match] = true;
			}
		}

		preg_match_all("/FS\\s*=\\s*([1-5])/", $this->comment->comment, $fsMatches);
		foreach ($fsMatches[1] as $i => $match) {
			$fsChanges['FS=' . $match] = true;
		}

		preg_match_all("/FS\\s*=\\s*...\(([1-5])\)/", $this->comment->comment, $fsMatches);
		foreach ($fsMatches[1] as $i => $match) {
			$fsChanges['FS=' . $match] = true;
		}
		
		preg_match_all("/(FS|Fs) should be changed to ([0-9]+)/", $this->comment->comment, $scMatches);
		foreach ($scMatches[2] as $i => $match) {
			$fsChanges['FS=' . $match] = true;
		}

		preg_match_all("/ids .* be changed (from.*)?(back )?to\s*([\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)/u", strtolower($this->comment->comment), $idsMatches);
		foreach ($idsMatches[3] as $i => $match) {
			$idsChanges['IDS=' . $match] = true;
		}

		preg_match_all("/ids should be\s*([\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)/u", strtolower($this->comment->comment), $idsMatches);
		foreach ($idsMatches[1] as $i => $match) {
			$idsChanges['IDS=' . $match] = true;
		}

		preg_match_all("/change ids to\s*([\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)/u", strtolower($this->comment->comment), $idsMatches);
		foreach ($idsMatches[1] as $i => $match) {
			$idsChanges['IDS=' . $match] = true;
		}

		if ($this->comment->type === 'ATTRIBUTES_IDS') {
			$firstLine = strtok($this->comment->comment, "\n");
			if (preg_match('/^(Change to\s+)?([\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)(\\.|$| )/u', trim($firstLine), $idsMatch)) {
				$tsChanges['IDS=' . trim($idsMatch[2])] = true;
			}
			if (preg_match('/(Change the IDS to\s+)?([\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)(\\.|$| )/u', trim($firstLine), $idsMatch)) {
				$tsChanges['IDS=' . trim($idsMatch[2])] = true;
			}
			if (preg_match('/(IDS change to\s+)?([\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)(\\.|$| )/u', trim($firstLine), $idsMatch)) {
				$tsChanges['IDS=' . trim($idsMatch[2])] = true;
			}
		}

		preg_match_all("/ids.*suggest.*?([\x{2E80}-\x{2EF3}\x{2FF0}-\x{2FFFF}\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]+)/u", strtolower($this->comment->comment), $idsMatches);
		foreach ($idsMatches[1] as $i => $match) {
			$idsChanges['IDS=' . $match] = true;
		}

		preg_match_all("/T\\/S\\s*=\\s*([0-1])/", $this->comment->comment, $tsMatches);
		foreach ($tsMatches[1] as $i => $match) {
			$tsChanges['T/S=' . $match] = true;
		}
		preg_match_all("/T\\/S.*?be\\s*([0-1])/", $this->comment->comment, $tsMatches);
		foreach ($tsMatches[1] as $i => $match) {
			$tsChanges['T/S=' . $match] = true;
		}

		preg_match_all("/T\\/S.*?to\\s*(0|1|T|S)/", $this->comment->comment, $tsMatches);
		foreach ($tsMatches[1] as $i => $match) {
			if ($match == 'T') $match = '0';
			if ($match == 'S') $match = '1';
			$tsChanges['T/S=' . $match] = true;
		}
		
		if ($this->comment->type === 'ATTRIBUTES_TRAD_SIMP') {
			preg_match_all("/change to simplified/", strtolower($this->comment->comment), $tsMatches);
			foreach ($tsMatches[0] as $str) {
				$tsChanges['T/S=1'] = true;
			}
			preg_match_all("/change to ([0-1])/", strtolower($this->comment->comment), $tsMatches);
			foreach ($tsMatches[1] as $i => $match) {
				$tsChanges['T/S=' . $match] = true;
			}
			
			$firstLine = strtok($this->comment->comment, "\n");
			if (trim($firstLine) === '1' || trim($firstLine) === '1?') {
				$tsChanges['T/S=1'] = true;
			}
			if (trim($firstLine) === '0' || trim($firstLine) === '0?') {
				$tsChanges['T/S=0'] = true;
			}
		}
		
		return array_merge(
			array_keys($radChanges),
			array_keys($scChanges),
			array_keys($tcChanges),
			array_keys($fsChanges),
			array_keys($idsChanges),
			array_keys($tsChanges)
		);
	}
	
	private function renderUnificationTargets() {
		global $character_cache;

		ob_start();

		$targets = $this->getUnificationTargets();

		$outputted = [];

		foreach ($targets as $target) {
			if (preg_match('/[\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}]/u', $target)) {
				$codepoint = charToCodepoint($target);
				if (isset($outputted[$codepoint])) {
					continue;
				}
				$outputted[$codepoint] = true;
				echo getImageHTML($codepoint);
			}
			if (preg_match('/(WS(2015|2017))-([0-9]{5})/u', $target, $matches)) {
				$year = $matches[2];
				$sn = $matches[3];
				if ($year === '2017') {
					$ref_char = $character_cache->getVersion($sn, $this->version);
					$ref_char->renderPart4();
				} else {
					$href = 'https://hc.jsecs.org/irg/ws'.$year.'/app/?id='.$sn;
					$url = 'https://hc.jsecs.org/irg/ws'.$year.'/app/cache/canvas'.$sn.'ws'.$year.'_cutting.png';
					echo '<a href="'.htmlspecialchars($href).'" target=_blank><img src="'.htmlspecialchars($url).'" alt="'.htmlspecialchars($target).'" style="max-width:100%"></a><br>';
				}
			}
		}
		
		return ob_get_clean();
	}
	
	public function getPossibleOutcomes($char) {
		$possibleOutcomes = [];
		
		if ($this->comment->getCategoryForCommentType() === 'Unification' && $this->comment->type !== 'NO_UNIFICATION') {
			$targets = $this->getUnificationTargets();
			foreach ($targets as $target) {
				if (substr($target, 0, 6) === 'WS2017') {
					$possibleOutcomes['Unified to ' . $target] = true;
					$possibleOutcomes['Unify ' . $target . ' (keep ' . $char->data[0] . ')'] = true;
					$possibleOutcomes['Not unified to ' . $target] = true;
				} else {
					if (!preg_match('@^[A-Za-z0-9-]+$@', $target)) {
						try {
							$possibleOutcomes['Unified to ' . $target . ' ' . charToCodepoint($target)] = true;
							$possibleOutcomes['Not unified to ' . $target . ' ' . charToCodepoint($target)] = true;
						} catch (Exception $e) {
							
						}
					} else {
						$possibleOutcomes['Unified to ' . $target] = true;
						$possibleOutcomes['Not unified to ' . $target] = true;
					}
				}
			}
			if ($this->comment->type === 'UCV' || strpos($this->comment->comment, 'UCV') !== false) {
				try {
					$_user = IRGUser::getById($this->comment->created_by);
					$possibleOutcomes['Add new UCV by ' . $_user->getName() . ' in comment #' . $this->comment->id] = true;
				} catch (Exception $e) {
					$possibleOutcomes['Add new UCV in comment #' . $this->comment->id] = true;
				}
			}
		}

		if ($this->comment->getCategoryForCommentType() === 'Attributes') {
			if ($this->comment->type === 'ATTRIBUTES_RADICAL') {
				$possibleOutcomes['Radical kept as ' . $char->getRadicalText()] = true;
			}
			
			$targets = $this->getAttributeChanges();
			foreach ($targets as $target) {
				$possibleOutcomes[$target] = true;
				if ($target[0] === 'R') {
					$possibleOutcomes['Radical kept as ' . $char->getRadicalText()] = true;
				}
				if ($target[0] === 'S' && $target[1] === 'C') {
					$possibleOutcomes['stroke count unchanged (kept as ' . $char->data[Workbook::STROKE] . ')'] = true;
				}
				if ($target[0] === 'T' && $target[1] === 'C') {
					$possibleOutcomes['total strokes unchanged (kept as ' . $char->getTotalStrokes() . ')'] = true;
				}
				if ($target[0] === 'F' && $target[1] === 'S') {
					$possibleOutcomes['first stroke unchanged'] = true;
				}
				if ($target[0] === 'I' && $target[1] === 'D') {
					$possibleOutcomes['IDS unchanged'] = true;
				}
				if ($target[0] === 'T' && $target[1] === '/') {
					$possibleOutcomes['T/S unchanged (kept as ' . $char->data[Workbook::TS_FLAG] . ')'] = true;
				}
			}
		}
		
		if ($this->comment->getCategoryForCommentType() === 'Evidence') {
			if (strpos(strtolower($this->comment->comment), '{{') !== false && strpos(strtolower($this->comment->comment), '}}') !== false) {
				$possibleOutcomes['Evidence accepted'] = true;
			}
			$possibleOutcomes['Pending for more evidence'] = true;
			$possibleOutcomes['Postponed for clearer evidence'] = true;
			$possibleOutcomes['Postponed for regular script evidence'] = true;
			$possibleOutcomes['Postponed for further investigation'] = true;
			if (strpos(strtolower($this->comment->comment), 'withdraw') !== false) {
				$possibleOutcomes['Withdrawn'] = true;
			}
		}

		if ($this->comment->type === 'GLYPH_DESIGN' || $this->comment->type === 'NORMALIZATION') {
			$possibleOutcomes['Postponed for glyph change'] = true;
			$possibleOutcomes['Glyph no change'] = true;
			$possibleOutcomes['Normalization accepted'] = true;
			$possibleOutcomes['Postponed for normalization rule'] = true;
		}	
		
		if ($this->comment->type === 'OTHER' || $this->comment->type === 'WITHDRAW') {
			$possibleOutcomes['Withdrawn'] = true;
		}
		
		return $possibleOutcomes;
	}
	
	public function renderHTML() {
		global $character_cache;

		$shrinkImage = ['cd58f34b590d7c3e9ab9ffd0001baa06733ba604d0339b735408dd9faccbf761' => true];
		$version = $this->comment->getVersion();
	
		$html = nl2br(htmlspecialchars(trim($this->normalized_text)));
		
		if ($this->comment->mayHaveUnificationTargets()) {
			$html = $this->renderUnificationTargets() . $html;
		}
		
		if ($this->comment->getCategoryForCommentType() === 'Attributes' && isset($_COOKIE['debug'])) {
			$prefix = '<div style="font-size:10px">Parse result: ';
			$cmParseResult = implode(', ', $this->getAttributeChanges());
			$prefix .= $cmParseResult;
			if ($this->comment->comment !== 'Agree.' && $this->comment->comment !== 'No Change.' && empty($cmParseResult)) {
				$prefix .= '<span style="background:yellow">Could not parse</a>';
			}
			$prefix .= '</div>';
			$html = $prefix . $html;
		}
		
		$html = preg_replace('@{?{(([0-9]){5}-jpy-unification\\.png)}}?@', '<img src="../comments/jpy/\\1" style="max-width:100%">', $html);
		
		$html = preg_replace_callback('@{?{(([0-9]{5})-([0-9a-f]{3,64})\\.png)}}?@', function($m) use ($shrinkImage) {
			$m1 = $m[1];
			if (isset($shrinkImage[$m[3]])) {
				return '<a href="../comments/'.$m1.'" target=_blank><img src="../comments/'.$m1.'" style="height:24px;vertical-align:middle"></a>';
			} else {
				return '<a href="../comments/'.$m1.'" target=_blank><img src="../comments/'.$m1.'" style="max-width:100%"></a>';
			}
		}, $html);

		$html = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}(\r\n)?@', function ($m) {
			$codepoint = $m[1];
			return getImageHTML($codepoint);
		}, $html);

		$html = preg_replace_callback('@{{CM-([0-9]+)}}@', function ($m) {
			$text_cm = DBComments::getById($m[1]);
			ob_start();
			echo '<blockquote><a href="./?id=' . $text_cm->sn . '" target=_blank style="color:initial;text-decoration:none;display:block">';
			echo nl2br(htmlspecialchars($text_cm->comment));
			echo '</a></blockquote>';
			return ob_get_clean();
		}, $html);

		$html = preg_replace_callback('@{{WS2015-(([0-9]){5})}}@', function ($m) use ($character_cache) {
			return
				'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting1.png" style="max-width:100%">' .
				'<img src="https://hc.jsecs.org/irg/ws2015/app/cache/canvas'.$m[1].'comment_cutting2.png" style="max-width:100%">';
		}, $html);

		$html = preg_replace_callback('@(<br />\\r\\n)?{{WS2017-(([0-9]){5})}}@', function ($m) use ($character_cache, $version) {
			$__c = $character_cache->getVersion($m[2], $version);
			ob_start();
			echo '<blockquote><a href="./?id=' . $m[2] . '" target=_blank style="color:initial;text-decoration:none;display:block">';
			$__c->renderPart4();
			if ($__c->data[1]) {
				$__c->renderPart3();
			}
			echo '</a></blockquote>';
			return ob_get_clean();
		}, $html);

		$html = preg_replace_callback('@{{(([0-9]){5})}}(\r\n)?@', function ($m) use ($character_cache, $version) {
			ob_start();
			echo '<blockquote><a href="./?id=' . $m[1] . '" target=_blank style="color:initial;text-decoration:none;display:block">';
			$__c = $character_cache->getVersion($m[1], $version);
			$__c->renderPart4();
			if ($__c->data[1]) {
				$__c->renderPart3();
			}
			echo '</a></blockquote>';
			return ob_get_clean();
		}, $html);
		
		return $html;
	}
}
