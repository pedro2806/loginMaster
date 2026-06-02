<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
	<title>MESS - Mmodulo de incidencias</title>
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="http://www.datatables.net/rss.xml">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css">

	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.print.min.js"></script>
	<script type="text/javascript" class="init">
	
$(document).ready(function() {
	// Cerrar sesión de loginMaster: borrar TODAS las cookies.
	var expirada = "Thu, 01 Jan 1970 00:00:00 UTC";
	var paths = ['/', '/Tickets', '/loginMaster'];

	// Cookies visibles desde esta página (path=/ y /loginMaster).
	document.cookie.split(';').forEach(function (c) {
		var eq = c.indexOf('=');
		var name = (eq > -1 ? c.substr(0, eq) : c).trim();
		if (!name) return;
		document.cookie = name + "=; expires=" + expirada + ";";
		paths.forEach(function (p) {
			document.cookie = name + "=; expires=" + expirada + "; path=" + p + ";";
		});
	});

	// Cookies *BI: viven en path=/Tickets y no son visibles aquí, se borran por nombre.
	['id_usuarioBI','nombredelusuarioBI','noEmpleadoBI','rolBI','correoBI','fotoBI'].forEach(function (c) {
		paths.forEach(function (p) {
			document.cookie = c + "=; expires=" + expirada + "; path=" + p + ";";
		});
	});

    window.location.assign("index.php")
} );
	</script>
</head>
<body>
</body>