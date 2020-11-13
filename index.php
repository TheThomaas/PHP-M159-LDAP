<?php
	session_start();
	// if(isset($_SESSION['errors'])){
    //     unset($_SESSION['errors']);
    // }
	
	function getLoginCredentials() {
		$credentials['username'] = "Administrator";
		$credentials['password'] = "Admlocal1";

		return $credentials;
	}
?>

<!DOCTYPE html>
<html>
<head>
	<title>M159 - LDAP</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<div class="signup">
		<form action="index.php" method="post" autocomplete="off">
			<h1>Créer un compte</h1><br>
			
			<?php 
				if(!empty($_SESSION['errors']['userCreation'])){
					echo '<p class="msg">'.$_SESSION['errors']['userCreation'].'</p>';
				}
			?>

			<br><div class="signup__field">
				<input class="signup__input" type="text" name="name" id="name" required />
				<label class="signup__label" for="name">Prénom</label>
			</div>
			
			<div class="signup__field">
				<input class="signup__input" type="text" name="lastname" id="lastname" required />
				<label class="signup__label" for="lastname">Nom de famille</label>
			</div>

			<div class="signup__field">
				<input class="signup__input" type="password" name="password" id="password" required />
				<label class="signup__label" for="password">Mot de passe</label>
			</div>

			<button class="createUser" type="submit" name="createUser" id="createUser" value="Créer un compte">Créer un compte</button>
		</form>

		<a class="login" href="#">Se connecter</a>
	</div>

	<?php

		/**
		 * Se connecte au serveur LDAP
		 * @return ldap : Si la connexion a été établie
		 */
		function setupConnection() {
			$adServer = "ldap://localhost";

			$ldap = ldap_connect($adServer);
			ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

			if ($ldap) {
				return $ldap;
			} else {
				echo "Impossible de se connecter au serveur LDAP";
			}			
		}

		/**
		 * Recherche dans l'Active Directory
		 * @param connection : Connexion vers l'AD
		 * @param name : Utilisateur recherché
		 */
		function searchInAD($connection, $name) {
			$credentials = getLoginCredentials();

			$ldaprdn = $credentials['username'] . '@M159-Domain.local';

			$bind = @ldap_bind($connection, $ldaprdn, $credentials['password']);

			if ($bind) {
				$filter="(sAMAccountName=$name)";
				$result = ldap_search($connection,"DC=M159-Domain,DC=local",$filter);
				ldap_sort($connection,$result,"sn");
				$info = ldap_get_entries($connection, $result);
				return $info;
			} else {
				$msg = "Invalid lastname address / password";
				echo $msg;
			}
		}

		/**
		 * Vérifie si l'utilisateur existe déjà
		 * @param connection : Connexion vers l'AD
		 * @param username : Utilisateur recherché
		 */
		function alreadyExists($connection, $username) {
			$credentials = getLoginCredentials();

			$searchUser = $credentials['username']."@M159-Domain.local";
			$searchPass = $credentials['password'];

			$info = searchInAD($connection, $username);

			return ($info && $info['count'] === 1);
		}

		/**
		 * Ajoute un utilisateur dans l'AD
		 * @param connection : connection vers le LDAP
		 * @param name : Prénom de l'utilisateur à créer
		 * @param lastname : Nom de l'utilisateur à créer
		 * @param password : Mot de passe de l'utilisateur à créer
		 */
		function addToAD($connection, $name, $lastname, $password){
			
			$loginDomain = '@M159-Domain.local';
			$credentials = getLoginCredentials();

			// Connexion avec une identité qui permet les modifications
			$r = ldap_bind($connection, $credentials['username'].$loginDomain, $credentials['password']);

			// Prépare les données
			$info["cn"] = "$name $lastname";
			$info['givenname'] = "$name";
			$info["sn"] = "$lastname";
			$info["sAMAccountName"] = "$lastname.$name";
			$info['displayname'] = "$name $lastname";
			$info['initials'] = strtoupper($name[0]).strtoupper($lastname[0]);
			$info['userpassword'] = "$password";
			$info["userprincipalname"] = "$lastname$name$loginDomain";
			$info["mail"] = "$lastname.$name@m159.ch";
			$info["UserAccountControl"] = "544";
			$info["objectclass"] = "user";

			// Ajoute les données au dossier
			if (!alreadyExists($connection, $info["sAMAccountName"])) {
				ldap_add($connection, "cn=".$info["cn"].",ou=utilisateurs,DC=M159-Domain,DC=local", $info);
				$_SESSION['errors']["userCreation"] = "Utilisateur ajouté";
				header('Location:index.php');
			} else {
				$_SESSION['errors']["userCreation"] = "Cet utilisateur existe déjà, connectez-vous";
				header('Location:index.php');
			}

			// addError("Utilisateur ajouté");
			
			// echo getErrors();
			@ldap_close($connection);
			
		}
		

		if(array_key_exists('createUser', $_POST)){
			$ldapConnection = setupConnection();

			
			addToAD($ldapConnection, $_POST['name'], $_POST['lastname'], $_POST['password']);
			
		}
		
		
	?>
	
</body>
</html>