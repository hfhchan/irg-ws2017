<?php
require_once 'user_chk.php';


if (isset($_POST['action']) && $_POST['action'] === 'login') {
	if ($session->isLoggedIn()) {
		throw new Exception('Already Logged In!');
	}
	$q = $user_db->prepare('SELECT "password" FROM users WHERE username=?');
	$q->execute([ $_POST['username'] ]);
	$password = $q->fetchColumn();
	if (!$password) {
		throw new BadParamException('$username incorrect');
	}
	if (!password_verify($_POST['password'], $password)) {
		throw new BadParamException('$password incorrect');
	}

	$q = $user_db->prepare('SELECT "id" FROM users WHERE username=?');
	$q->execute([ $_POST['username'] ]);
	$user_id = $q->fetchColumn();

	$session_id = bin2hex(random_bytes(16));
	$expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 3);

	$q = $user_db->prepare('INSERT INTO session ("user_id", "session_id", "expires") VALUES (?, ?, ?)');
	$q->execute([$user_id, $session_id, $expiry]); // 3 hours
	
	setcookie('IRG_SESSION', $session_id, 0, null, null, true, true);
	header('Location: admin.php?logged_in');
	exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
	if ($session->isLoggedIn()) {
		$q = $user_db->prepare('UPDATE session SET "expires" = DATETIME(\'NOW\') WHERE "session_id" = ?');
		$q->execute([ $_COOKIE['IRG_SESSION'] ]); // 3 hours
		setcookie('IRG_SESSION', 'null', 0, null, null, true, true);
		header('Location: index.php');
		exit;
	}
}

if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
	if (!$session->isLoggedIn()) {
		throw new Exception('Not logged in!');
	}
	
	if ($_POST['password'] !== $_POST['password_confirm']) {
		throw new Exception('Password not matched');
	}
	$pwd_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
	$q = $user_db->prepare('UPDATE users SET "password" = ? WHERE "username" = ?');
	$q->execute([$pwd_hash, $session->getUser()->getUsername()]);
	
	header('Location: admin.php?password_changed');
	exit;
}


if (isset($_POST['action']) && $_POST['action'] === 'register') {

	if (!$session->isLoggedIn() || !$session->getUser()->isAdmin()) {
		throw new Exception('Not logged in as admin!');
	}

	$username = $_POST['username'];
	$pwd_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
	$name = $_POST['name'];

	$q = $user_db->query('SELECT "password" FROM users WHERE username="admin"');
	$master_hash = $q->fetchColumn();
	if (!password_verify($_POST['master_pwd'], $master_hash)) {
		throw new BadParamException('$master_pwd incorrect');
	}

	$q = $user_db->prepare('SELECT COUNT(*) FROM users WHERE username=?');
	$q->execute([$username]);
	$count = $q->fetchColumn();
	if ($count) {
		throw new BadParamException('$username already taken.');
	}
	
	$q = $user_db->prepare('INSERT INTO users ("username", "password", "name") VALUES (?, ?, ?)');
	$q->execute([$username, $pwd_hash, $name]);
	
	header('Location: admin.php?user_added');
	exit;
}


?>
<!doctype html>

<style>
body{margin:0;font-family:Arial, sans-serif}
hr{margin:10px 0;border:1px solid #999;border-width:1px 0 0}
#main{max-width:480px;margin:0 auto;padding:10px}

section{margin:30px 0}
form{margin:0}
h1{margin:10px 0;font-size:36px}
.field{margin:10px 0;display:grid;grid-template-columns:120px 1fr}
.field input{border:1px solid #999;background:none;padding:2px 4px;font-family:inherit}
.field input:focus{border:1px solid #36f}
.submit{display:grid;justify-content:center} 
.submit input{background:#36f;padding:4px 20px;border:none;color:#fff;font-family:inherit}

</style>

<div id=main>


<section>
<? if (!$session->isLoggedIn()) : ?>
	<form method=post id=login>
		<h1>Log In</h1>
		<div class=field><label>Username:</label> <input name=username></div>
		<div class=field><label>Password:</label> <input name=password type=password></div>
		<div class=submit><input type=submit value=Login></div>
		<input name=action value=login type=hidden>
	</form>
<? else: ?>
	<div>
		<h1>Logged In</h1>
		<div class=field>
			<div>Name</div>
			<div><?=htmlspecialchars($session->getUser()->getName())?></div>
		</div>
		<div class=field>
			<div>Username</div>
			<div><?=htmlspecialchars($session->getUser()->getUsername())?></div>
		</div>
<? if ($session->getUser()->isAdmin()) : ?>
		<div class=field>
			<div>Account Type</div>
			<div>Admin</div>
		</div>
<? endif; ?>
	</div>
	<br>
	<form method=post id=change_password>
		<h2>Change Password</h2>
		<div class=field><label>Password:</label> <input name=password type=password></div>
		<div class=field><label title="Confirm Password">Type Again:</label> <input name=password_confirm type=password></div>
		<div class=submit><input type=submit value="Change Password"></div>
		<input name=action value=change_password type=hidden>
	</form>
<? endif; ?>
</section>

<? if ($session->isLoggedIn() && $session->getUser()->isAdmin()) : ?>
<hr>
<section>
	<form method=post id=register>
		<h1>Register User</h1>
		<div class=field><label>Admin Pwd:</label> <input name=master_pwd type=password></div>
		<hr>
		<div class=field><label>Name:</label> <input name=name></div>
		<div class=field><label>Username:</label> <input name=username></div>
		<div class=field><label>Password:</label> <input name=password type=password></div>
		<div class=submit><input type=submit value=Register></div>
		<input name=action value=register type=hidden>
	</form>
</section>
<? endif; ?>

<hr>
<div align=center>
	<a href=".">Return to Index</a>
</div>

</div>