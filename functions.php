<?php 

    /**
     * @return credentials : Les informations de connexion d'un utilisateur qui peut modifier le contenu de l'AD
     */
    function getLoginCredentials() {
        $credentials['username'] = "Administrator";
        $credentials['password'] = "Admlocal1";

        return $credentials;
    }
        
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
    function search($connection, $name) {
        $credentials = getLoginCredentials();

        $ldaprdn = $credentials['username'] . '@M159-Domain.local';

        $bind = @ldap_bind($connection, $ldaprdn, $credentials['password']);

        if ($bind) {
            $filter="(cn=$name)";
            $result = @ldap_search($connection,"DC=M159-Domain,DC=local",$filter);
            @ldap_sort($connection,$result,"sn");
            $info = @ldap_get_entries($connection, $result);
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

        $info = search($connection, $username);

        return ($info && $info['count'] === 1);
    }

    /**
     * Ajoute un utilisateur dans l'AD
     * @param connection : connection vers le LDAP
     * @param name : Prénom de l'utilisateur à créer
     * @param lastname : Nom de l'utilisateur à créer
     * @param password : Mot de passe de l'utilisateur à créer
     */
    function addUser($connection, $name, $lastname, $username, $password){
        
        $loginDomain = '@M159-Domain.local';
        $credentials = getLoginCredentials();

        // Connexion avec une identité qui permet les modifications
        $r = @ldap_bind($connection, $credentials['username'].$loginDomain, $credentials['password']);

        // Prépare les données
        $info["cn"] = "$name $lastname";
        $info['givenname'] = "$name";
        $info["sn"] = "$lastname";
        $info["sAMAccountName"] = "$username";
        $info['displayname'] = "$name $lastname";
        // $info['initials'] = strtoupper($name[0]).strtoupper($lastname[0]);
        $info["userprincipalname"] = "$username$loginDomain";
        // $info["mail"] = "$username@m159.ch";
        $info["UserAccountControl"] = "512"; // 544
        $info["objectclass"] = "user";
        // $info["pwdlastset"] = -1;
        $info['userpassword'] = "$password";

        // $newPassword = '"'.$password.'"';
        // $newPass = iconv('UTF-8', 'UTF-16LE', $newPassword);
        // // $info["unicodepwd"] = $newPass;
        // $info["userpassword"] = $newPass;

        // Ajoute les données au dossier
        if (!alreadyExists($connection, $info["cn"])) {
            // ldap_add($connection, "cn=".$info["cn"].",cn=Users,DC=M159-Domain,DC=local", $info);
            ldap_add($connection, "cn=".$info["cn"].",ou=utilisateurs,DC=M159-Domain,DC=local", $info);
            echo "Utilisateur ajouté : $password";
            // header('Location: index.php');
        } else {
            echo "Cet utilisateur existe déjà, connectez-vous";
        }

        echo "<br><br>";
        @ldap_close($connection);
        
    }
    
    /**
     * Connecte l'utilisateur passé en paramètre
     * @param connection : connection vers le LDAP
     * @param username : Nom d'utilisateur ou mail
     * @param password : Mot de passe de l'utilisateur
     */
    function connectUser($connection, $username, $password) {
        $ldap_username = convertUsernameToLogin($username);
        $ldap_password = $password;
        // $ldap_password = "";
        
        if (@ldap_bind($connection, $ldap_username, $ldap_password)) {
            echo "Connecté";
            header('Location: index.php');
        } else
            echo "Impossible de se connecter";

        echo "<br><br>";
        @ldap_close($connection);
    }

    /**
     * Converti un nom d'utilisateur en login
     * @param username : Nom d'utilisateur à convertir
     */
    function convertUsernameToLogin($username) {

        // Vérifie le type du nom d'utilisateur
        $usernameType = "username";
        if (strpos($username, "@") !== false)
            $usernameType = "mail";

        $loginDomain = "@M159-Domain.local";

        // Rajoute le domaine de connexion au nom d'utilisateur
        if ($usernameType !== "mail")
            $username = $username.$loginDomain;

        return $username;
    }
