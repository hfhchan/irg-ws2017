<?php


class Router {
	private $sources_cache;
	private $character_cache;
	private $ids_cache;

	public function __construct($sources_cache, $character_cache, $ids_cache) {
		$this->sources_cache   = $sources_cache;
		$this->character_cache = $character_cache;
		$this->ids_cache       = $ids_cache;
	}
	
	public function getLeftOfSource($source) {
		global $session;
		$prev = $source;
		while($prev) {
			$result = $this->sources_cache->find($prev);
			foreach ($result as $sq_number) {
				$char = $this->character_cache->get($sq_number);
				if (!$char->hasReviewedUnification( $session->isLoggedIn() ? $session->getUser()->getUserId() : 0 )) {
					break 2;
				}
			}
			$prev = $this->sources_cache->findPrev($prev);
		}
		return $prev;
	}
	
	public function getRightOfSource($source) {
		global $session;
		$next = $source;
		while($next){
			$result = $this->sources_cache->find($next);
			foreach ($result as $sq_number) {
				$char = $this->character_cache->get($sq_number);
				if (!$char->hasReviewedUnification( $session->isLoggedIn() ? $session->getUser()->getUserId() : 0 )) {
					break 2;
				}
			}
			$next = $this->sources_cache->findNext($next);
		}
		return $next;
	}
	
	public function getLeftOfSerialNumber($sn) {
		global $session;
		$sq_number = $_GET['id'];
		$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
		while (true) {
			$char = $this->character_cache->get($prev);
			if (!$char) {
				break;
			}
			if (!$char->hasReviewedUnification( $session->isLoggedIn() ? $session->getUser()->getUserId() : 0 )) {
				break;
			}
			$prev = str_pad(intval(ltrim($prev, '0')) - 1, 5, '0', STR_PAD_LEFT);
		}
		return $prev;
	}
	
	public function getRightOfSerialNumber($sn) {
		global $session;
		$sq_number = $_GET['id'];
		$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);
		while (true) {
			$char = $this->character_cache->get($next);
			if (!$char) {
				break;
			}
			if (!$char->hasReviewedUnification( $session->isLoggedIn() ? $session->getUser()->getUserId() : 0 )) {
				break;
			}
			$next = str_pad(intval(ltrim($next, '0')) + 1, 5, '0', STR_PAD_LEFT);
		}
		return $next;
	}

}
