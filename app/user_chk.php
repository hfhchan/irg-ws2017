<?php

date_default_timezone_set('UTC');

$user_db = new PDO('sqlite:../data/review/login.sqlite3');
$user_db->exec('PRAGMA foreign_keys = ON');
$user_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$user_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$session_db = new PDO('sqlite:../data/review/session.sqlite3');
$session_db->exec('PRAGMA foreign_keys = ON');
$session_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$session_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

class IRGUser {
	static $user_db;
	static $session_db;
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
	private $need_reset = false;
	
	public function __construct($data) {
		$this->id       = (int) $data->id;
		$this->username = $data->username;
		$this->name     = $data->name;
		$this->is_admin = $this->id === 1 || $this->id === 2 || $this->id === 4 || $this->id === 10 || $this->id === 13;
		$this->organization = $data->organization;
		if (isset($data->need_reset)) {
			$this->need_reset = (bool) $data->need_reset;
		}
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
	
	public function getOrganization() {
		return $this->organization;
	}
	
	public function isNeedReset() {
		return $this->need_reset;
	}
	
	public function isAdmin() {
		return $this->is_admin;
	}
}

IRGUser::$user_db = $user_db;
IRGUser::$session_db = $session_db;

class CurrentSession {
	private $is_logged_in = false;
	private $user = null;
	private $session_id = "";

	private static $instance;

	public static function getInstance() {
		if (isset(self::$instance)) {
			return self::$instance;
		}
		throw new Exception("Not instantiated");
	}

	public function getUser() {
		return $this->user;
	}

	public function isLoggedIn() {
		return $this->is_logged_in;
	}
	
	public function updateExpiry($session_db) {
		$expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 3);
		try {
			$q = $session_db->prepare('UPDATE session SET "expires" = ? WHERE "user_id" = ? AND "session_id" = ?');
			$q->execute([$expiry, $this->user->getUserId(), $this->session_id]); // 3 hours
		} catch (PDOException $e) {
			usleep(20 * 1000); // sleep 20 milliseconds
			// try again
			$q = $session_db->prepare('UPDATE session SET "expires" = ? WHERE "user_id" = ? AND "session_id" = ?');
			$q->execute([$expiry, $this->user->getUserId(), $this->session_id]); // 3 hours
		}
	}
	
	public static function init($session_id, $session_db) {
		$session = new self();
		self::$instance = $session;

		if ($session_id === null) {
			return $session;
		}

		$session_id = $_COOKIE['IRG_SESSION'];

		$q = $session_db->prepare('SELECT "user_id" FROM "session" WHERE "session_id" = ? AND "expires" > datetime("now") LIMIT 1');
		$q->execute([ $session_id ]);
		$user_id = $q->fetchColumn();
		if (!$user_id) {
			return $session;
		}

		$user = IRGUser::getById($user_id);

		$session->session_id = $session_id;
		$session->is_logged_in = true;
		$session->user = $user;
		$session->updateExpiry($session_db);
		return $session;
	}
}

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
	if (strpos($_SERVER['SERVER_NAME'], 'ngrok.io') === false) {
		if (strpos($_SERVER['SERVER_NAME'], 'jsecs.org') !== false) {
			header('Location: https://hc.jsecs.org/' . $_SERVER['REQUEST_URI']);
			exit;
		}
		throw new FatalException('HTTP not supported; please use HTTPS.');
	}
}

if (isset($_COOKIE['IRG_SESSION']) && strlen($_COOKIE['IRG_SESSION']) === 32) {
	$session = CurrentSession::init($_COOKIE['IRG_SESSION'], $session_db);
} else {
	$session = CurrentSession::init(null, $session_db);
}
