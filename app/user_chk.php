<?php

date_default_timezone_set('UTC');

$user_db = new PDO('sqlite:../data/review/login.sqlite3');
$user_db->exec('PRAGMA foreign_keys = ON');
$user_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$user_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

class IRGUser {
	static $user_db;
	static function getById($id) {
		static $cache = [];
		if (isset($cache[$id])) {
			return $cache[$id];
		}
		$q = self::$user_db->prepare('SELECT * FROM "users" WHERE "id" = ?');
		$q->execute([ $id ]);
		$user_data = $q->fetch();
		if (!$user_data) {
			throw new Exception('User not found!');
		}
		$user = new IRGUser($user_data);
		$cache[$id] = $user;
		return $cache[$id];
	}

	private $id       = null;
	private $username = null;
	private $name     = null;
	private $is_admin = false;
	
	public function __construct($data) {
		$this->id       = (int) $data->id;
		$this->username = $data->username;
		$this->name     = $data->name;
		$this->is_admin = $this->id === 1 || $this->id === 4 || $this->id === 10;
	}
	
	public function getUserId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}
	
	public function getUsername() {
		return $this->username;
	}
	
	public function isAdmin() {
		return $this->is_admin;
	}
}

IRGUser::$user_db = $user_db;

class CurrentSession {
	private $is_logged_in = false;
	private $user = null;

	public function getUser() {
		return $this->user;
	}

	public function isLoggedIn() {
		return $this->is_logged_in;
	}
	
	public static function init($session_id, $user_db) {
		$session = new self();

		if ($session_id === null) {
			return $session;
		}

		$session_id = $_COOKIE['IRG_SESSION'];
		$q = $user_db->prepare('SELECT * FROM "users" WHERE "id" IN (SELECT "user_id" FROM "session" WHERE "session_id" = ? AND "expiry" > datetime())');
		$q->execute([ $session_id ]);
		$user_data = $q->fetch();
		if (!$user_data) {
			return $session;
		}
		$user = new IRGUser($user_data);
		$session->is_logged_in = true;
		$session->user = $user;
		return $session;
	}
}

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
	if (strpos($_SERVER['SERVER_NAME'], 'ngrok.io') === false) {
		throw new Exception('HTTP not supported; please use HTTPS');
	}
}

if (isset($_COOKIE['IRG_SESSION']) && strlen($_COOKIE['IRG_SESSION']) === 32) {
	$session = CurrentSession::init($_COOKIE['IRG_SESSION'], $user_db);
} else {
	$session = CurrentSession::init(null, $user_db);
}
