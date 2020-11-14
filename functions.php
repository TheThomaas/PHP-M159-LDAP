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
            $filter="(sAMAccountName=$name)";
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
        $info["cn"] = "$username";
        $info['givenname'] = "$name";
        $info["sn"] = "$lastname";
        $info["sAMAccountName"] = "$username";
        $info['displayname'] = "$name $lastname";
        $info['initials'] = strtoupper($name[0]).strtoupper($lastname[0]);
        $info['userpassword'] = "$password";
        $info["userprincipalname"] = "$username$loginDomain";
        $info["mail"] = "$username@m159.ch";
        $info["UserAccountControl"] = "544";
        $info["objectclass"] = "user";

        // Ajoute les données au dossier
        if (!alreadyExists($connection, $info["sAMAccountName"])) {
            @ldap_add($connection, "cn=".$info["cn"].",ou=utilisateurs,DC=M159-Domain,DC=local", $info);
            echo "<br>Utilisateur ajouté<br>";
        } else {
            echo "<br>Impossible de créer l'utilisateur<br>";
        }

        @ldap_close($connection);
        
    }
    
    $isUserAuthenticated = FALSE;

    /**
     * Connecte l'utilisateur passé en paramètre
     * @param connection : connection vers le LDAP
     * @param username : Nom d'utilisateur ou mail
     */
    function connectUser($connection, $username, $password) {
        $loginDomain = "@M159-Domain.local";
        
        $ldap_dn = "cn=".$_POST["username"].",ou=utilisateurs,DC=M159-Domain,DC=local";
        $ldap_username = $_POST["username"].$loginDomain;
        $ldap_password = $_POST["password"];
        
        if (!@ldap_bind($ldapConnection, $ldap_dn, $ldap_password)) {
            echo "Invalid Credential<br><br>";
        } else
            echo "Credentials OK";
    }