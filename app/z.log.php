<?php

error_reporting(-1);

class Log {
	static $start = 0;
	static $log = [];
	static $disabled = false;
	public static function disable() {
		self::$disabled = true;
	}
	public static function add($name, $info = '') {
		self::$log[] = [microtime(true), $name, $info];
	}
	public static function start() {
		self::$start = microtime(true);
	}
	public static function end() {
		$start = self::$start;
		$last = self::$start;
		echo '<table style="font-size:13px;margin:10px"><col width=120><col width=120><col width=200><col>';
		foreach (self::$log as $entry) {
			echo '<tr><td style="text-align:right;padding-right:10px">';
			echo number_format(($entry[0] - $start) * 1000, 2);
			echo ' ms</td><td style="text-align:right;padding-right:10px">+';
			echo number_format(($entry[0] - $last) * 1000, 2);
			echo ' ms</td><td>';
			echo $entry[1];
			echo '</td><td>';
			echo html_safe($entry[2]);
			echo '</td></tr>';
			$last = $entry[0];
		}
		echo '</table>';
	}
}
Log::start();
register_shutdown_function(function() {
	if (empty($_COOKIE['debug'])) {
		return;
	}
	if (Log::$disabled || Env::$readonly) {
		return;
	}
	Log::add('Shutdown');
	Log::end();
});

class FatalException extends Exception{
	public function allowLogin($allowLogin = null) {
		$this->allowLogin = $allowLogin;
	}
	public function title($title = null) {
		$this->title = $title;
	}
}

set_exception_handler(function (Throwable $e){
	echo '<!doctype html><meta charset=utf-8><meta name=viewport content="width=initial-width,initial-scale=1"><link href="common.css" rel=stylesheet type="text/css"><link href="style.css" rel=stylesheet type="text/css"><script src="jquery.js"></script>';
	
	if ($e instanceOf FatalException) {
		if (!empty($e->allowLogin)) {
			require_once 'index.searchbar.php';
		}
		if (!empty($e->title)) {
			echo '<title>' . htmlspecialchars($e->title) . '</title>';
		}
		echo '<div class=center_box>';
		echo htmlspecialchars($e->getMessage());
		echo '</div>';
		exit;
	}

	require_once 'index.searchbar.php';
	echo '<div class=center_box>';
	echo '<p><b>Error</b><br>';
	echo htmlspecialchars($e->getMessage()).'<br>';
	echo '<span style="color:#333;font:13px monospace">@ &nbsp;' . htmlspecialchars($e->getFile()) . '('.$e->getLine().')</span>';
	echo '</p>';
	echo '<div style="margin-top:10px"><b>Stack Trace:</b></div>';
	echo '<pre style="color:#333;margin-top:0;font:13px monospace">';
	echo htmlspecialchars($e->getTraceAsString());
	echo '</pre>';
	echo '</div>';
	exit;
});

function output_error_message($title, $message) {
	echo '<!doctype html><meta charset=utf-8><meta name=viewport content="width=initial-width,initial-scale=1"><link href="style.css" rel=stylesheet type="text/css"><script src="jquery.js"></script>';
	require_once 'index.searchbar.php';
	echo '<div class=center_box>';
	echo '<p><b>' . htmlspecialchars($title) . '</b><br>';
	echo htmlspecialchars($message);
	echo '</p>';
	echo '</div>';
	exit;	
}