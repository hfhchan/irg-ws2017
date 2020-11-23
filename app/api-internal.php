<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

Log::disable();

set_exception_handler(function (Throwable $e) {
	header('HTTP/1.1 400 Bad Request');
	header('content-type: application/json');
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
	exit;
});

if (env::$readonly) {
	header('HTTP/1.1 400 Bad Request');
	return;
}

if (empty($_POST['action'])) {
	header('HTTP/1.1 400 Bad Request');
	return;
}

$action = $_POST['action'];

if ($action === 'add_comment') {
	if (!$session->isLoggedIn()) {
		throw new Exception('Not logged in');
	}

	if (!isset($_POST['user_id']) || $_POST['user_id'] != $session->getUser()->getUserId()) {
		throw new Exception('Session expired');
	}

	if (!isset($_POST['comment']) || !isset($_POST['sq_number']) || !isset($_POST['type'])) {
		throw new Exception('Missing $comment, $sq_number or $type');
	}
	
	if ($_POST['type'] !== 'UNCLEAR_EVIDENCE' && empty($_POST['comment'])) {
		throw new Exception('Comment cannot be empty!');
	}

	DBComments::save($_POST['sq_number'], $_POST['type'], $_POST['comment'], $session->getUser()->getUserId());

	header('Content-type:application/json');
	echo '{"success":true}';
	return;
}

if ($action === 'edit_comment') {
	if (!$session->isLoggedIn()) {
		throw new Exception('Not logged in');
	}

	if (!isset($_POST['user_id']) || $_POST['user_id'] != $session->getUser()->getUserId()) {
		throw new Exception('Session expired');
	}

	if (!isset($_POST['comment_id']) || !isset($_POST['comment']) || !isset($_POST['type'])) {
		throw new Exception('Missing $comment_id, $comment or $type');
	}
	
	if ($_POST['type'] !== 'UNCLEAR_EVIDENCE' && empty($_POST['comment'])) {
		throw new Exception('Comment cannot be empty!');
	}
	
	$comment = DBComments::getById($_POST['comment_id']);
	if (!$comment) {
		throw new Exception('Comment not found.');
	}
	
	if (!$comment->canEdit($session->getUser())) {
		throw new Exception('Cannot edit comment');
	}
	
	$comment->edit($_POST['type'], $_POST['comment_id'], $session->getUser()->getUserId());

	header('Content-type:application/json');
	echo '{"success":true}';
	return;
}

if ($action === 'delete_comment') {
	if (!$session->isLoggedIn()) {
		throw new Exception('Not logged in');
	}

	if (!isset($_POST['user_id']) || $_POST['user_id'] != $session->getUser()->getUserId()) {
		throw new Exception('Session expired');
	}

	if (!isset($_POST['comment_id'])) {
		throw new Exception('Missing $comment_id');
	}
	
	$comment = DBComments::getById($_POST['comment_id']);
	if (!$comment) {
		throw new Exception('Comment not found.');
	}
	
	if (!$comment->canDelete($session->getUser())) {
		throw new Exception('Cannot delete comment');
	}
	
	$comment->delete($session->getUser()->getUserId());

	header('Content-type:application/json');
	echo '{"success":true}';
	return;
}

throw new Exception("Route not found");
