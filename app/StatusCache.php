<?

class StatusCache {
	public function getGroupBySerial($version) {
		if (!CharacterCache::hasVersion($version)) {
			throw new Exception('Invalid $version');
		}

		$sources_cache   = new SourcesCache();
		$character_cache = new CharacterCache();

		if (file_exists(__DIR__ . '/../data/attributes-cache/ws'.$version.'CurrentStatusSerialNumber.json')) {
			$status = json_decode(file_get_contents(__DIR__ . '/../data/attributes-cache/ws'.$version.'CurrentStatusSerialNumber.json'), true);
		} else {
			$sources = [];
			$allSources = $sources_cache->getSources($version);
			foreach ($allSources as $sourceKey => $sourceVal) {
				foreach ($sourceVal as $sourceID) {
					if (!isset($sources[$sourceID])) {
						$sources[$sourceID] = [];
					}
					$sources[$sourceID][] = $sourceKey;
				}
			}
			
			ksort($sources);
			
			$status = [];
			$image = [];
			foreach ($sources as $sn => $source_list) {
				$char = $character_cache->getVersion($sn, $version);
				$sheet = $char->sheet;
				$images = [];
				foreach ($source_list as $source) {
					$images[] = WSCharacter::getFileName($source, $version);
				}
				$status[$sn] = [
					'sheet' => $sheet,
					'references' => $source_list,
					'images' => $images
				];
			}
			
			file_put_contents(__DIR__ . '/../data/attributes-cache/ws'.$version.'CurrentStatusSerialNumber.json', json_encode($status));
		}
		return $status;
	}
	
	public function getGroupBySourceReference($version) {
		if (!CharacterCache::hasVersion($version)) {
			throw new Exception('Invalid $version');
		}

		$sources_cache   = new SourcesCache();
		$character_cache = new CharacterCache();

		if (file_exists(__DIR__ . '/../data/attributes-cache/ws'.$version.'CurrentStatus.json')) {
			$status = json_decode(file_get_contents(__DIR__ . '/../data/attributes-cache/ws'.$version.'CurrentStatus.json'), true);
		} else {
			$status = [];
			$allSources = $sources_cache->getSources($version);
			foreach ($allSources as $sourceKey => $sourceVal) {
				$sourceSheet = 99;
				foreach ($sourceVal as $id) {
					$char = $character_cache->getVersion($id, $version);
					$sheet = $char->sheet;
					if ($sheet < $sourceSheet) {
						$sourceSheet = $sheet;
					}
				}			
				$status[$sourceKey] = array(
					'sheet' => $sourceSheet,
					'images' => [WSCharacter::getFileName($sourceKey, $version)]
				);
			}
			file_put_contents(__DIR__ . '/../data/attributes-cache/ws'.$version.'CurrentStatus.json', json_encode($status));
		}
		
		return $status;
	}
}