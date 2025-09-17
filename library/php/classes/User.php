<?php
class User {
    private $userId;
    private $userLastName;
    private $userFirstName;
    private $pdo;
    public function __construct( $pdo ) {
        $this -> pdo = $pdo;
    }
    public static function checkForExistingEmail( $pdo, $email ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) as count_id FROM  `user` WHERE email='$email';";
            //$stm = $pdo -> query("SELECT count(id) as count_id FROM  `user` WHERE email='$email';");
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = $result[0]["count_id"];
            //$return -> count_records = count( (array)$result );
            $return -> message = "Die Prüfung auf vorhandene E-Mail war erfolgreich.";
            return $return;
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }        
    }
    public function getUserLastnameFirstname( $pdo ) {
        $query="SELECT id, CONCAT(`lastname`, ', ', `firstname`) AS name FROM `user` ORDER BY lastname";
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function getUserByLastName( $userName ) {
        $return = new \stdClass();
        try{
            $stm = $this -> pdo -> query("SELECT * FROM  `user` WHERE lastname='" . $userName . "';");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            if( count( (array)$result ) == 1 ) {
                $this -> setUserId( $result[0]["id"] );
                $this -> setUserLastName( $result[0]["lastname"] );
                $this -> setUserFirstName( $result[0]["firstname"] );
                $return -> success = true;
                $return -> result = $result[0];
                $return -> message = "Der User wurde erfolgreich gelesen.";
                return $return;
            } else {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist ein unerwarteter Fehler aufgetreten.";
                return $return;                
            }
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }        
    }
    public function getUserById( $userId ) {
        $return = new \stdClass();
        try{
            $stm = $this -> pdo -> query("SELECT * FROM  `user` WHERE id=$userId;");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            if( count( (array)$result ) == 1 ) {
                $this -> setUserId( $result[0]["id"] );
                $this -> setUserLastName( $result[0]["lastname"] );
                $this -> setUserFirstName( $result[0]["firstname"] );
                $return -> success = true;
                $return -> result = $result[0];
                $return -> message = "Der User wurde erfolgreich gelesen.";
                return $return;
            } else {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist ein unerwarteter Fehler aufgetreten.";
                return $return;                
            }
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }        
    }
    public function getUserByAccountId( $accountId ) {
        $return = new \stdClass();
        try{
            $stm = $this -> pdo -> query("SELECT DISTINCT user.*, account.*, role.role FROM `account`, `user`, `role` WHERE account.role_id = role.id AND account.user_id = user.id and account.id = $accountId;");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            if( count( (array)$result ) == 1 ) {
                $this -> setUserId( $result[0]["id"] );
                $return -> count_records = count( (array)$result );
                $return -> success = true;
                $return -> data = $result[0];
                $return -> message = "Die User-Id wurde erfolgreich gelesen.";
                return $return;
            } else {
                $return -> success = false;
                $return -> count_records = count( (array)$result );
                $return -> message = "Beim Lesen der User-Id ist ein unerwarteter Fehler aufgetreten.";
                return $return;                
            }
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen der User-Id ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }        
    }
    public function getUserByRoleId( $roleId ) {
        $return = new \stdClass();
        try{
            $stm = $this -> pdo -> query("SELECT user.id FROM `account`, `user` WHERE account.user_id = user.id and role_id = $roleId;");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> success = true;
            $return -> data = $result;
            $return -> message = "Die User wurde erfolgreich gelesen.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Lesen der User ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
    return $return;
    }
    public function newUser( $pdo, $salutation, $firstname, $lastname, $email ) {
        $return = new \stdClass();
        $query = "INSERT INTO `user` (`salutation`, `firstname`, `lastname`, `email`) VALUES ('$salutation', '$firstname', '$lastname', '$email')";
        try {
            $result = $pdo -> query( $query );
            $this -> setUserId( $pdo -> lastInsertId() );
            $return -> success = true;
            $return -> message = "Der Nutzer wurde erfolgreich angelegt.";
            return $return;
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen des Nutzers ist folgender Fehler aufgetreten: " . $e -> getMessage();
            return $return;
        }        
    }
    public function newFullUser( $pdo, $salutation, $firstname, $lastname, $email, $phone, $description, $photo, 
                            $newsletter, $opt_in, $street, $house_number, $postal_code, $city, $iban, $institute, 
                            $account_owner, $birthday = "0000-00-00"  ) {
        $return = new \stdClass();
        $query = "INSERT INTO `user` (`salutation`, `firstname`, `lastname`, `email`, `phone`, `description`, `photo`
                            , `newsletter`, `opt_in`, `street`, `house_number`, `postal_code`, `city`, `iban`
                            , `institute`, `account_owner`, `birthday`) VALUES ('$salutation', '$firstname', 
                            '$lastname', '$email', '$phone', '$description', $photo, $newsletter, $opt_in, '$street', '$house_number', 
                            '$postal_code', '$city', '$iban', '$institute', '$account_owner', '$birthday');";
        try {
            $result = $pdo -> query( $query );
            $stm = $pdo -> query("SELECT ROW_COUNT() AS 'rows';");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            if( $result[0]["rows"] == "1" ) {
                $stm = $pdo -> query("SELECT MAX(id) AS `maxid` FROM `user`;");
                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $this -> setUserId( $result[0]["maxid"] );
                $return -> success = true;
                $return -> message = "Der Nutzer wurde erfolgreich angelegt.";
                return $return;
            } else {
                $return -> success = false;
                $return -> message = "Beim Anlegen des Nutzers ist ein unbekannter Fehler aufgetreten.";
                return $return;
            }
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen des Nutzers ist folgender Fehler aufgetreten: " . $e -> getMessage();
            return $return;
        }        
    }
    public function newShortUser( $pdo, $salutation, $firstname, $lastname, $email, $phone, $photo, $opt_in ) {
        $return = new \stdClass();
        $query = "INSERT INTO `user` (`salutation`, `firstname`, `lastname`, `email`, `phone`, `photo`, `opt_in`) VALUES ('$salutation', '$firstname', '$lastname', '$email', '$phone', $photo, $opt_in);";
        try {
            $result = $pdo -> query( $query );
            $stm = $pdo -> query("SELECT ROW_COUNT() AS 'rows';");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            if( $result[0]["rows"] == "1" ) {
                $stm = $pdo -> query("SELECT MAX(id) AS `maxid` FROM `user`;");
                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $this -> setUserId( $result[0]["maxid"] );
                $return -> success = true;
                $return -> message = "Der Nutzer wurde erfolgreich angelegt.";
                return $return;
            } else {
                $return -> success = false;
                $return -> message = "Beim Anlegen des Nutzers ist ein unbekannter Fehler aufgetreten.";
                return $return;
            }
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen des Nutzers ist folgender Fehler aufgetreten: " . $e -> getMessage();
            return $return;
        }        
    }
    
    public function updateUser( $pdo, $user_id, $salutation, $firstname, $lastname, $email, $phone, $description, $photo, 
                            $newsletter, $opt_in, $allow_ga, $allow_tr, $remind_me, $after_days, $after_messages, $street, $house_number, $postal_code, $city, $iban, $institute, 
                            $account_owner, $birthday = '0000-00-00'
                            ) {
        $return = new \stdClass();
        $query = "UPDATE `user` SET salutation=$salutation, firstname='$firstname', lastname='$lastname', email='$email',  
                 phone='$phone', description='$description', photo=$photo, newsletter=$newsletter, opt_in=$opt_in, allow_ga = $allow_ga, allow_tr = $allow_tr, remind_me = $remind_me, after_days = $after_days,
                 after_messages = $after_messages, street='$street', house_number='$house_number', postal_code='$postal_code', city='$city',
                 iban='$iban', institute='$institute', account_owner='$account_owner', birthday='$birthday'
                 WHERE id=$user_id;"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Der User wurde erfolgreich gespeichert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Speichern des Users ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
        
    
    }
    public function updateShortUser( $pdo, $user_id, $salutation, $firstname, $lastname, $email, $phone, $photo, $opt_in) {
        $return = new \stdClass();
        $query = "UPDATE `user` SET salutation=$salutation, firstname='$firstname', lastname='$lastname', email='$email',  
                 phone='$phone', photo=$photo, opt_in=$opt_in WHERE id=$user_id;"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Der User wurde erfolgreich gespeichert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Speichern des Users ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
        
    
    }
    
    public function setOptIn( $pdo, $userId ) {
        $return = new \stdClass();
        $query = "UPDATE `user` SET opt_in=true WHERE id=" . $userId . ";"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Das Opt-In wurde erfolgreich aktiviert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Setzen des Opt-In ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
        
    }
    public function setPhoto( $pdo, $userId ) {
        $return = new \stdClass();
        $query = "UPDATE `user` SET photo=true WHERE id=" . $userId . ";"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Die Photodarstellungsoption wurde erfolgreich aktiviert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Setzen der Photodarstellungsoption ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
        
    }
    public function deleteUser( $pdo, $userId ) {
        $return = new \stdClass();
        $query = "DELETE FROM `user` WHERE id=$userId;"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Der User wurde erfolgreich gelöscht.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Löschen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
        
    }
    public function setNewsletter( $pdo, $userId ) {
        $return = new \stdClass();
        $query = "UPDATE `user` SET newsletter=true WHERE id=" . $userId . ";"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Der Newsletter wurde erfolgreich aktiviert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Setzen der Newsletteroption ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
        
    }

    public function getUsers( $pdo, $orderBy = "ORDER BY id", $limit = "", $where = "" ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT * FROM  `user` $where $orderBy $limit;";
            $stm = $pdo -> query("SELECT * FROM  `user` $where $orderBy $limit;");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = count( (array)$result );
            $return -> data = $result;
            return $return;
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }        
            
    }
    public function getFullUserNames( $pdo, $orderBy = "ORDER BY lastname", $limit = "", $where = "" ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT id, CONCAT( lastname, ', ', firstname) FROM  `user` $where $orderBy $limit;";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = count( (array)$result );
            $return -> data = $result;
            return $return;
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }          
    }
    public static function getCountUsers( $pdo, $where = "" ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) FROM  `user` $where;";
            $stm = $pdo -> query("SELECT count(id) as count_id FROM  `user` $where;");
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = $result[0]["count_id"];
            $return -> data = $result;
            return $return;
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen des Users ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }        
            
    }
    public function setUserLastLogin( $pdo, $id ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "UPDATE `user` SET `last_login` = CURRENT_TIMESTAMP, count_remind_emails = 0 WHERE `user`.`id` = " . $id;
            $pdo->query( $query );
            $query = "SELECT count(id) as count_message FROM message_user WHERE to_user = $id";      
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_messages = $result[0]["count_message"];
            $return -> success = true;    
            $return -> message = "Der Nutzer wurde erfolgreich aktualisiert.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Aktualisieren des Nutzers ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }    
    public function setLastActivity( $pdo ) {
        $return = new \stdClass();
        $return -> success = true;
        $return -> message = "Die letzte Aktivität des Nutzers wurde protokolliert.";
        try {
            $query = "SELECT COUNT(id) as count_id FROM message_user WHERE is_read = false AND to_user = " . $_SESSION["user_id"] . ";";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_messages = $result[0]["count_id"];
            $query = "SELECT COUNT(id) as count_id FROM news_user WHERE is_read = false AND to_user = " . $_SESSION["user_id"] . ";";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_news = $result[0]["count_id"];
            $return -> sum = $return -> count_messages + $return -> count_news;
            $query = "UPDATE user SET last_activity = NOW(), count_messages = " . $return -> sum . " WHERE id = " . $_SESSION["user_id"] . ";";
            $pdo->query( $query );
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Aktualisieren der Nutzeraktivität ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                return $return;                            
        }
        return $return;
    }
    
    
    public function getUserId() {
        return $this->userId;
    }
    public function setUserId( $userId ) {
        $this->userId = $userId;
    }
    public function getUserLastName() {
        return $this->userLastName;
    }
    public function setUserLastName( $userLastName ) {
        $this->userLastName = $userLastName;
    }
    public function setUserFirstName( $userFirstName ) {
        $this->userFirstName = $userFirstName;
    }
    public function getUserFirstName() {
        return $this->userFirstName;
    }    
}
?>
