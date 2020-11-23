<? if (!class_exists('CurrentSession')) return; ?>
<? $_session = CurrentSession::getInstance(); ?>
<div id=menu_account>
<? if (isset($_session) && $_session->getUser()) { ?>
	<div id=menu_account_block>Logged In as <?=$_session->getUser()->getName()?></div>
	<div><a href="admin.php">Admin Panel</a></div>
	<div><a href="list.php" target=_blank>My Review Comments</a></div>
	<div><a href="bookmarks.php" target=_blank>My Labels</a></div>
<? } else { ?>
	<div><a href="admin.php">Login</a></div>
<? } ?>
	<hr>
<? if (defined('MAIN_INDEX')) { ?>
	<div><b>Main Index</b></div>
<? } else { ?>
	<div><a href=".">Main Index</a></div>
<? } ?>
<? if (defined('CHARTS')) { ?>
	<div><b>Charts</b></div>
<? } else { ?>
	<div><a href="chart.php" target=_blank>Charts</a></div>
<? } ?>
	<div><a href="variant.php" target=_blank>Variants</a></div>
	<div><a href="list.php?user=0" target=_blank>Consolidated Comments</a></div>
<? if (isset($_session) && $_session->getUser()) { ?>
	<div><a href="meeting-mode.php" target=_blank>Meeting Mode</a></div>
<? } ?>
<? if (defined('DISCUSSION_RECORD')) { ?>
	<div><b>Discussion Record</b></div>
<? } else { ?>
	<div><a href="discussion-record.php" target=_blank>Discussion Record</a></div>
<? } ?>
</div>