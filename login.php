<?php
	require_once "functions.php"
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
			<h1>Se connecter</h1><br>
			
			<br><div class="signup__field">
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
					connectUser($ldapConnection, $_POST['username'], $_POST['password']);
				} 
			?>
			<button class="createUser" type="submit" name="createUser" id="createUser" value="Créer un compte">Se connecter</button>
		</form>
		<a class="login" href="signup.php">ou créer un compte</a>
	</div>
</body>
</html>