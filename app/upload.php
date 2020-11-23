<?php

require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

Log::disable();

header('Cache-Control: private, no-cache, no-store');

if (!$session->isLoggedIn()) {
	echo 'Not Logged In!';
	exit;
}

if (isset($_FILES['data'])) {
	if (!isset($_POST['sq_number']) || strlen($_POST['sq_number']) !== 5 || !ctype_digit($_POST['sq_number'])) {
		throw new Exception('invalid $sq_number');
	}

	$sq_number = $_POST['sq_number'];
	$filename = $_POST['sq_number'] . '-' . bin2hex(random_bytes(32)) . '.png';

	move_uploaded_file($_FILES['data']['tmp_name'], '../comments/' . $filename);

	echo $filename;
	exit;
}
