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
		throw new Exception('$username incorrect');
	}
	if (!password_verify($_POST['password'], $password)) {
		throw new Exception('$password incorrect');
	}

	$q = $user_db->prepare('SELECT "id" FROM users WHERE username=?');
	$q->execute([ $_POST['username'] ]);
	$user_id = $q->fetchColumn();

	$session_id = bin2hex(random_bytes(16));
	$expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 3);

	$q = $session_db->prepare('INSERT INTO session ("user_id", "session_id", "expires") VALUES (?, ?, ?)');
	$q->execute([$user_id, $session_id, $expiry]); // 3 hours
	
	setcookie('IRG_SESSION', $session_id, 0, null, null, true, true);
	
	$new_user = IRGUser::getById($user_id);
	if ($new_user->isAdmin()) {
?>
<script>
const bc = new BroadcastChannel('account');
bc.postMessage('login');
window.location.href = 'admin.php?logged_in';
</script>
<?
	} else {
?>
<script>
const bc = new BroadcastChannel('account');
bc.postMessage('login');
window.location.href = 'index.php?logged_in';
</script>
<?
	}
	exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
	if ($session->isLoggedIn()) {
		$q = $session_db->prepare('UPDATE session SET "expires" = DATETIME(\'NOW\') WHERE "session_id" = ?');
		$q->execute([ $_COOKIE['IRG_SESSION'] ]); // 3 hours
		setcookie('IRG_SESSION', 'null', 0, null, null, true, true);
?>
<script>
const bc = new BroadcastChannel('account');
bc.postMessage('logout');
window.location.href = 'index.php?logged_out';
</script>
<?
		exit;
	}
}

if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
	if (!$session->isLoggedIn()) {
		throw new Exception('Not logged in!');
	}
	
	if ($_POST['new_password'] !== $_POST['new_password_confirm']) {
		throw new Exception('Password not matched');
	}
	$pwd_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
	$q = $user_db->prepare('UPDATE users SET "password" = ?, "need_reset" = ? WHERE "username" = ?');
	$q->execute([$pwd_hash, null, $session->getUser()->getUsername()]);
	
	header('Location: admin.php?password_changed');
	exit;
}


if (isset($_POST['action']) && $_POST['action'] === 'register') {

	if (!$session->isLoggedIn() || !$session->getUser()->isAdmin()) {
		throw new Exception('Not logged in as admin!');
	}

	$username = $_POST['new_username'];
	$pwd_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
	$name = $_POST['name'];
	$organization = $_POST['organization'];

	$q = $user_db->query('SELECT "password" FROM users WHERE username="admin"');
	$master_hash = $q->fetchColumn();
	if (!password_verify($_POST['master_pwd'], $master_hash)) {
		throw new Exception('$master_pwd incorrect');
	}

	$q = $user_db->prepare('SELECT COUNT(*) FROM users WHERE username=?');
	$q->execute([ $username ]);
	$count = $q->fetchColumn();
	if ($count) {
		throw new Exception('$username already taken.');
	}
	
	$q = $user_db->prepare('INSERT INTO users ("username", "password", "organization", "name") VALUES (?, ?, ?, ?)');
	$q->execute([ $username, $pwd_hash, $organization, $name ]);
	
	header('Location: admin.php?user_added');
	exit;
}


?>
<!doctype html>
<title>Admin - IRG Online Review Tool</title>
<meta charset=utf-8>
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
<? if ($session->getUser()->isNeedReset()) : ?>
<script>
alert('Please change password.');
</script>
<? endif; ?>
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
		<form method=post id=logout>
			<div class=submit><input type=submit value="Log Out"></div>
			<input name=action value=logout type=hidden>
		</form>
	</div>
	<br>
	<form method=post id=change_password autocomplete=off>
		<h2>Change Password</h2>
		<div class=field><label>Password:</label> <input name=new_password type=password autocomplete=off data-lpignore="true" role="new-password"></div>
		<div class=field><label title="Confirm Password">Type Again:</label> <input name=new_password_confirm type=password autocomplete=off data-lpignore="true"></div>
		<div class=submit><input type=submit value="Change Password"></div>
		<input name=action value=change_password type=hidden>
	</form>
<? endif; ?>
</section>

<? if ($session->isLoggedIn() && $session->getUser()->isAdmin()) : ?>
<hr>
<a href="admin-import-changes.php" target=_blank>Import changes from Excel</a>
<a href="admin-review-changes.php" target=_blank>Review changes</a>
<hr>
<style>
#session_list{border-collapse:collapse;width:100%}
#session_list th,#session_list td{text-align:left;border:1px solid #ccc;padding:5px 10px}
</style>
<section>
	<h1>Currently Logged In</h1>
	<table id=session_list>
		<tr>
			<th>User</th>
			<th>Expires</th>
		</tr>
<?
	$q = $session_db->query("SELECT * FROM session WHERE expires > datetime(\"now\") ORDER BY expires DESC");
	$data = $q->fetchAll();
	foreach ($data as $row) {
		$this_user = IRGUser::getById($row->user_id);
?>
		<tr>
			<th>
				<div><?=nl2br(htmlspecialchars($this_user->getName()))?></div>
				<div style="color:#666;font-size:13px"><?=(htmlspecialchars($this_user->getOrganization()))?></div>
			</th>
			<td><?=htmlspecialchars($row->expires)?></td>
		</tr>
<?
	}
?>
	</table>
</section>
<hr>
<section>
	<form method=post id=register>
		<h1>Register User</h1>
		<div class=field><label>Admin Pwd:</label> <input name=master_pwd type=password autocomplete=off data-lpignore="true"></div>
		<hr>
		<div class=field><label>Name:</label> <input name=name autocomplete=off data-lpignore="true"></div>
		<div class=field><label>Organization:</label> <input name=organization autocomplete=off data-lpignore="true"></div>
		<div class=field><label>Username:</label> <input name=new_username autocomplete=off data-lpignore="true"></div>
		<div class=field><label>Password:</label> <input name=new_password type=password autocomplete=off data-lpignore="true"></div>
		<div class=submit><input type=submit value=Register></div>
		<input name=action value=register type=hidden>
	</form>
</section>
<? endif; ?>

<hr>
<? if (!$session->isLoggedIn() || !$session->getUser()->isNeedReset()) : ?>
<div align=center>
	<a href=".">Return to Index</a>
</div>
<? endif; ?>

</div>
