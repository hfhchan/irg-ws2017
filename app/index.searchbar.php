<div id="menu_toggle" tabindex=0>Menu</div>
<div id="menu_scrim"></div>
<div id="menu">
	<div id=menu_find>
		<form method=get autocomplete=off  id=search-char-1 role=search action=index.php>
			<div>Find by Source <u>R</u>ef:</div>
			<input id=search-1 type=text name=find value="<?=html_safe(isset($_GET['find']) ? $_GET['find'] : '')?>" accesskey=r>
			<input id=find-1 type=submit value=Find>
		</form>
		<form method=get autocomplete=off  id=search-char-2 role=search action=index.php>
			<div>Find by <u>S</u>erial No:</div>
			<input id=search-2 name=id value="<?=html_safe(isset($_GET['id']) && $_GET['id'][0] !== 'c' ? $_GET['id'] : '')?>" accesskey=s>
			<input id=find-2 type=submit value=Find>
		</form>
		<form method=get autocomplete=off  id=search-char-3 role=search action=index.php>
			<div>Find by <u>I</u>DS:</div>
			<input id=search-3 name=ids value="<?=html_safe(isset($_GET['ids']) ? $_GET['ids'] : '')?>" accesskey=i>
			<input id=find-3 type=submit value=Find>
		</form>
		<form method=get autocomplete=off  id=search-char-4 role=search action=index.php>
			<div>Find by <u>L</u>abel:</div>
			<input id=search-4 name=label value="<?=html_safe(isset($_GET['label']) ? $_GET['label'] : '')?>" accesskey=l>
			<input id=find-4 type=submit value=Find>
		</form>
	</div>
<?
	require_once 'index.menu-account.php';
?>
</div>

<?
if (defined("MEETING_MODE")) {
?>
<script>
$('#menu_find form').attr('target', '_blank');
</script>
<?
}
?>

<script>
(() => {
	let scrollTop = 0;
	const openMenu = () => {
		document.getElementById('menu').style.transform = 'translateX(0%)';
		document.getElementById('menu_scrim').style.display = 'block';
		scrollTop = document.documentElement.scrollTop;
		document.documentElement.style.overflow = 'hidden';
	}
	const closeMenu = () => {
		document.getElementById('menu').style.transform = 'translateX(-100%)';
		document.getElementById('menu_scrim').style.display = 'none';
		document.documentElement.scrollTop = scrollTop;
		document.documentElement.style.overflow = '';
	}
	document.getElementById('menu_scrim').onclick = e => {
		e.preventDefault();
		closeMenu();
	}
	document.getElementById('menu_toggle').onclick = e => {
		e.preventDefault();
		openMenu();
	}
	closeMenu();

	const bc = new BroadcastChannel('account');
	bc.onmessage = async function (ev) {
		try {
			const resp = await fetch('accountbar.php', {credentials: "same-origin"});
			const html = await resp.text();
			document.getElementById('menu_account').outerHTML = html;
		} catch (e) {
			console.error(e);
		}
	};
})();
</script>