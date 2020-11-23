<?php

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';
require_once 'user_chk.php';

define('EVIDENCE_PATH', '../data');

if (!$session->isLoggedIn()) {
	throw new Exception('Not Logged In');
}

if (isset($_GET['version']) && CharacterCache::hasVersion($_GET['version'])) {
	$version = $_GET['version'];
} else {
	$version = Workbook::VERSION;
}

$user = $session->getUser();

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=1100">
<title>Labels | WS2017v<?=$version?></title>
<link href="common.css" rel=stylesheet type="text/css">
<link href="style.css" rel=stylesheet type="text/css">
<script src="jquery.js"></script>
<body>
<? require_once 'index.searchbar.php'; ?>

<section class=center_box>
<h3 style="margin-top:0">Labels</h3>
<div style="display:grid;grid-gap:4px;grid-template-columns:repeat(auto-fit, minmax(100px, 150px));">
<?php

		foreach (DBComments::getAllLabels($session->getUser()->getUserId()) as $label) {
			echo '<div><a target=_blank style="display:block;background:#39f;padding:4px 8px;border-radius:4px;font-weight:bold;text-decoration:none;color:#fff" href="https://hc.jsecs.org/irg/ws2017/app/index.php?label='.html_safe($label).'">' . html_safe($label) . '</a></div>';
		}
		
?>
</div>
</section>
