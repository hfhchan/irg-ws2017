<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title><?=html_safe(isset($title) ? $title : '')?></title>
<link href="common.css" rel=stylesheet type="text/css">
<link href="style.css" rel=stylesheet type="text/css">
<script src="jquery.js"></script>
<body>
<script>
(function() {
	var el = document.createElement('div');
	var supports_grid = typeof el.style.grid === 'string';
	if (!supports_grid) {
		document.write('<div style="background:yellow;font-size:24px;border:5px solid red;padding:80px">Your browser is not supported.  Please use Google Chrome 72 or above, or Firefox 65 or above, or equivalent browser.</div>');
	}
	
	if (!window.customElements) {
		document.write('<div style="background:yellow;font-size:24px;border:5px solid red;padding:80px">Your browser is not supported.  Please use Google Chrome 72 or above, or Firefox 65 or above, or equivalent browser.</div>');
	}
})();
</script>