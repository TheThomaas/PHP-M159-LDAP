<!DOCTYPE html>
<html>
<head>
	<title>M159 - LDAP</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<?php
		header("location: login.php");
		exit;
	?>
	<div class="signup">
		<button class="createUser" onclick="location.href='signup.php'">Créer un compte</button>
		<a class="login" href="login.php">Se connecter</a>
	</div>

</body>
</html>