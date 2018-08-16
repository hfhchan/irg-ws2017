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
	if (Log::$disabled || Env::$readonly) {
		return;
	}
	Log::add('Shutdown');
	Log::end();
});

set_exception_handler(function (Throwable $e){
	echo '<div class=center-wrap>';
	echo '<p><b>Uncaught Exception</b><br>';
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
