<?php

	include_once "functions.php"
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>M159 - LDAP</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<div class="signup">
		<form action="" method="post" autocomplete="off">
			<h1>Créer un compte</h1><br>
			
			<div class="signup__field">
				<input class="signup__input" type="text" name="name" id="name" required />
				<label class="signup__label" for="name">Prénom</label>
			</div>
			
			<div class="signup__field">
				<input class="signup__input" type="text" name="lastname" id="lastname" required />
				<label class="signup__label" for="lastname">Nom de famille</label>
			</div>

			<div class="signup__field">
				<input class="signup__input" type="text" name="username" id="username" required />
				<label class="signup__label" for="username">Nom d'utilisateur</label>
			</div>

			<div class="signup__field">
				<input class="signup__input" type="password" name="password" id="password" required />
				<label class="signup__label" for="password">Mot de passe</label>
			</div>
			<?php
				if(array_key_exists('createUser', $_POST)){
					$ldapConnection = setupConnection();
					addUser($ldapConnection, $_POST['name'], $_POST['lastname'], $_POST['username'], $_POST['password']);	
				}
			?>
			<button class="createUser" type="submit" name="createUser" id="createUser" value="Créer un compte">Créer un compte</button>
		</form>

		<a class="login" href="login.php">ou se connecter</a>
	</div>

	
	
</body>
</html>