<?php
	require_once "functions.php"
?>

<!DOCTYPE html>
<html>
<head>
	<title>Votre compte - M159 - LDAP</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<div class="signup">
        <h1>Votre compte</h1><br>
        <?php
            // Initialize the session
            session_start();
            
            // Check if the user is logged in, if not then redirect him to login page
            if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
                header("location: login.php");
                exit;
            }

            $ldapConnection = setupConnection();
            showUserInfo($ldapConnection, $_SESSION["username"]);

            if(array_key_exists('logout', $_POST)){
                logout();
            } 
        ?>
        <br><form action="" method="post" autocomplete="off">
			<button class="createUser" type="submit" name="logout" id="logout" value="Se déconnecter">Se déconnecter</button>
		</form>
	</div>
</body>
</html>