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
        // $adServer = "ldaps://localhost:636";
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
    function search($connection, $username) {
        $credentials = getLoginCredentials();

        $ldaprdn = $credentials['username'] . '@M159-Domain.local';

        $bind = @ldap_bind($connection, $ldaprdn, $credentials['password']);
        
        if ($bind) {
            $filter="(cn=$username)";
            $result = @ldap_search($connection,"DC=M159-Domain,DC=local",$filter);
            @ldap_sort($connection,$result,"sn");
            $info = @ldap_get_entries($connection, $result);
            return $info;
        } else {
            $msg = "Impossible de trouver cet utilisateur";
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
     * @param username : Nom d'utilisateur de l'utilisateur à créer
     * @param password : Mot de passe de l'utilisateur à créer
     */
    function addUser($connection, $name, $lastname, $username, $password){
        
        $loginDomain = '@M159-Domain.local';
        $credentials = getLoginCredentials();

        // Connexion avec une identité qui permet les modifications
        $r = @ldap_bind($connection, $credentials['username'].$loginDomain, $credentials['password']);

        // Prépare les données
        $info["cn"] = "$username";
        $info["givenname"] = "$name";
        $info["sn"] = "$lastname";
        $info["sAMAccountName"] = "$username";
        $info["displayname"] = "$name $lastname";
        $info["userprincipalname"] = "$username$loginDomain";
        $info["UserAccountControl"] = "544";
        $info["objectclass"] = "user";
        $info["userpassword"] = "$password";

        // Ajoute les données au dossier
        if (!alreadyExists($connection, $info["cn"])) {
            if(@ldap_add($connection, "cn=".$info["cn"].",ou=utilisateurs,DC=M159-Domain,DC=local", $info)) {
                echo "Utilisateur ajouté";
            } else {
                echo "Imposible de créer l'utilisateur";
            }
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
        // $ldap_password = $password;
        $ldap_password = "";
        
        if (@ldap_bind($connection, $ldap_username, $ldap_password)) {
            // // Mot de passe correct, on ouvre une nouvelle session
            // session_start();
                            
            // Enregistre les données dans la variable de session
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $ldap_username;                            
            
            header('Location: account.php');
            exit;
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

    /**
     * Déconnecte l'utilisateur actuel
     */
    function logout() {
        // Initialise la session
        session_start();
        
        // Vide la variable de session
        $_SESSION = array();
        
        // Détruit la session
        session_destroy();
        
        // Redirige à la page de connexion
        header("location: login.php");
        exit;
    }

    /**
     * Affiche les informations d'un utilisateur
     * @param connection : connection vers le LDAP
     * @param username : Nom d'utilisateur de l'utilisateur à afficher
     */
    function showUserInfo($connection, $username) {
        $credentials = getLoginCredentials();
        if (TRUE !== ldap_bind($connection, $credentials['username'] . '@M159-Domain.local', $credentials['password'])){
            die('<p>Failed to bind to LDAP server.</p>');
        }

        $ldap_base_dn = "ou=utilisateurs,DC=M159-Domain,DC=local";
        $search_filter = "(&(objectCategory=user))";
        $result = ldap_search($connection, $ldap_base_dn, $search_filter);
        if (FALSE !== $result){
            $entries = ldap_get_entries($connection, $result);
            if ($entries['count'] > 0){
                $odd = 0;
                foreach ($entries[0] AS $key => $value){
                    if (0 === $odd%2){
                        $ldap_columns[] = $key;
                    }
                    $odd++;
                }
                echo '<table style="width:100%;">';
                for ($i = 0; $i < $entries['count']; $i++){
                    foreach ($ldap_columns AS $col_name){
                        if ($entries[$i]["cn"][0]."@M159-Domain.local" === "$username") {
                            if ('givenname' === $col_name ||
                                'sn' === $col_name ||
                                'samaccountname' === $col_name ||
                                'displayname' === $col_name ||
                                'userprincipalname' === $col_name) {

                                $displayColName = NULL;
                                switch ($col_name){
                                    case 'givenname':
                                        $displayColName = "Prénom";
                                        break;
                                    case 'sn':
                                        $displayColName = "Nom  de famille";
                                        break;
                                    case 'samaccountname':
                                        $displayColName = "Nom d'utilisateur";
                                        break;
                                    case 'displayname':
                                        $displayColName = "Nom complet";
                                        break;
                                    case 'userprincipalname':
                                        $displayColName = "Identifiant Windows";
                                        break;
                                }
                                echo "<tr>
                                <th>$displayColName</th>
                                <td>";
                                if (isset($entries[$i][$col_name])){
                                    $output = $entries[$i][$col_name][0];
                                    
                                    echo $output .'</td>';
                                }
                                echo '</tr>';
                            }
                        }
                    }
                }
                echo '</table>';
            }
        }
    }
