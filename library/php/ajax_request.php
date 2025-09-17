<?php
session_start();
error_reporting( E_ALL ^E_NOTICE );
date_default_timezone_set('Europe/Berlin');
// fetch call to $_POST variables
$json = file_get_contents("php://input");
if (!empty($json)) {
    $data = json_decode($json, true);
    foreach ($data as $key => $value) {
        $_POST[$key] = $value;
    }
}
// end fetch
define( "ROOT", "../../"); 
//var_dump( $_POST );
foreach($_POST  as $key => $val ){
  
    // Accessing individual elements
    $i =  $key;
    $j = json_decode( $i );
    if( !is_null( $j ) ) {
        foreach( $j as  $key => $val ) {
            //if( is_numeric( $val ) ) continue;
            $_POST[$key] = $val;
        }        
    }
}

$return = new \stdClass();
$return -> command = $_POST["command"];
if( isset( $_POST["param"] ) ) {
    $return -> param = $_POST["param"];
}
$settings = parse_ini_file('../../ini/settings.ini', TRUE);

$session_timeout = $settings['logout']['automatic_timeout'] * 60;

if (!isset($_SESSION['last_visit'])) {
    $_SESSION['last_visit'] = time();
    // Aktion der Session wird ausgeführt
}
if((time() - $_SESSION['last_visit']) > $session_timeout && $_POST["command"] != "connect" && $_POST["command"] != "sendContactForm" && $_POST["command"] != "sendBcForm" ) {
    session_unset();
    session_destroy();
    $return -> command = "timeout";
    $return -> message = "Sie wurden automatisch abgemeldet, da Sie mehr als " . $session_timeout/60 . " Minuten inaktiv waren. Sie werden nun weitergeleitet.";
//    $return -> role_id = $_POST["role_id"];
    print_r( json_encode( $return ));
    die;
    
} else {
    $_SESSION['last_visit'] = time();
}

$dns = $settings['database']['type'] . 
            ':host=' . $settings['database']['host'] . 
            ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
            ';dbname=' . $settings['database']['schema'];
try {
    $db_pdo = new \PDO( $dns, $settings['database']['username'], $settings['database']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
    $db_pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_pdo -> setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false );
}
catch( \PDOException $e ) {
    $return -> command = "connect_error";
    $return -> message = $e->getMessage();
    print_r( json_encode( $return ));
    die;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once("functions.php"); 
foreach ( $_POST as &$str) {
    //var_dump($str);
    $str = replaceUnwantetChars($str);
}
switch( $_POST["command"]) {
    // start standard functions
    case "sendContactForm":
                            require_once( "functions.php" );
                                $query = "SELECT role.role from role, account, user where user.email='" . $_POST["email"] . "' AND user.id = account.user_id AND account.role_id = role.id LIMIT 1";
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Daten ist folgender Fehler aufgetreten:" . $e->getMessage();
                                return $return;   
                            }
                            if( count($result) == 0 ) {
                                 $content = "Status: Besucher";
                            } else {
                                  $content = "<p>Status: " . $result[0]["role"] . "</p>";
                            }
                            $content .= "<h4>Thema: ";
                            switch( $_POST["reason"] ) {
                                case "1": 
                                    $content .= "Terminvereinbarung";
                                break;    
                                case "2": 
                                    $content .= "Beratung";
                                break;    
                                case "3": 
                                    $content .= "Selbsthilfegruppen";
                                break;    
                                case "4": 
                                    $content .= "Sonstiges";
                                break;  
                                default:
                                break;  
                            } 
                            $content = $content . "</h4>";
                            switch( $_POST["salutation"] ) {
                                case "m": 
                                    $content .= "<p>Herr " . $_POST["firstname"] . " " .  $_POST["lastname"] . " <a href='mailto:" . $_POST["email"] . "'>" . $_POST["email"] . "</a> fragt an:</p>";
                                break;    
                                case "w": 
                                    $content .= "<p>Frau " . $_POST["firstname"] . " " .  $_POST["lastname"] . " <a href='mailto:" . $_POST["email"] . "'>" . $_POST["email"] . "</a> fragt an:</p>";
                                break;    
                                case "d": 
                                    $content .= "<p>" . $_POST["firstname"] . " " .  $_POST["lastname"] . " <a href='mailto:" . $_POST["email"] . "'>" . $_POST["email"] . "</a> fragt an:</p>";
                                break;    
                            }
                            $content .= "<p>" . $_POST["inquire"] . "</p>";
                            sendContactEmail( $content, $settings );
                            print_r( json_encode( $return ));   
    break;
    case "sendBcForm":
                            require_once( "functions.php" );
                            $content = "<p></p>";
                            switch( $_POST["salutation"] ) {
                                case "m": $content .= "<p>Herr " . $_POST["firstname"] . " " .  $_POST["lastname"] . "</p>";
                                break;    
                                case "w": $content .= "<p>Frau " . $_POST["firstname"] . " " .  $_POST["lastname"] . "</p>" ;
                                break;    
                                case "d": $content .= "<p>" . $_POST["firstname"] . " " .  $_POST["lastname"] . "</p>" ;
                                break;    
                            }

                            $return -> content = $content . "<p>Bitte um Rückruf unter " .  $_POST["bc_phone"] . " am " . $_POST["bc_date"] . " ab " . $_POST["bc_time"] . " Uhr. </p>";
                            $result = sendContactBCEmail( $return -> content, $settings );
                            $return -> result = $result;
                            print_r( json_encode( $return ));                             
    break;
    case "setSessionValue":
                            $tmp = explode( ";", $_POST["sessionValues"] );
                            $l = count( $tmp );
                            $i = 0;
                            while ( $i < $l ){
                                $tmp1 = explode( "=", $tmp[$i] );
                                $_SESSION[ $tmp1[0] ] = $tmp1[1];
                                $i += 1;
                            }
                            print_r( json_encode( $return ));    
    break;
    case "unsetSessionValue":
                            $tmp = explode( ";", $_POST["sessionValues"] );
                            $l = count( $tmp );
                            $i = 0;
                            while ( $i < $l ){
                                unset( $_SESSION[ $tmp1[0] ] );
                                $i += 1;
                            }
                            print_r( json_encode( $return ));    
    break;
    // end standard functions
    case "connect":
                            require_once( "classes/Account.php" );
                            require_once( "classes/User.php" );
                            require_once( "classes/Tracking.php" );
                            $account = new \Account();
                            $user = new \User( $db_pdo );
                            $tr = new \Tracking( $db_pdo );
                            $lTracking = $tr -> setTracking( $_POST["u"], $_POST["p"], 0  );
                            $return -> data = $account -> getAccountByEmailAndPassword( $db_pdo, $_POST["u"], $_POST["p"]);
                            if( $return -> data-> count_result > 0 ) {
                                $tr -> delTracking( $lTracking );
                                $_SESSION["account_id"] = $return -> data -> data["id"];
                                $_SESSION["user_id"] = $return -> data -> data["user_id"];
                                $_SESSION["role_id"] = $return -> data -> data["role_id"];
                                $_SESSION["email"] = $return -> data -> data["email"];
                                $_SESSION["password"] = $return -> data -> data["password"];
                                //$user = new \User();
                                $result = $user -> getUserById( $return -> data -> data["user_id"] );
                                $_SESSION["firstname"] = $result -> result["firstname"];
                                $_SESSION["lastname"] = $result -> result["lastname"];
                                $_SESSION["letter"] = strtoupper( substr( $_SESSION["firstname"], 0, 1 ) ) . strtoupper( substr( $_SESSION["lastname"], 0, 1 ) );
                                $_SESSION["allow_ga"] = $result -> result["allow_ga"];                           
                                $_SESSION["allow_tr"] = $result -> result["allow_tr"];                           
                                $stm = $db_pdo -> query( "SELECT role from role WHERE id=" . $_SESSION["role_id"] );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $_SESSION["role"] = $result[0]["role"];
                                // delete chat user
                                $query = "DELETE FROM chat_user WHERE user_id = " . $_SESSION["user_id"];
                                $db_pdo -> query( $query );
                            } else {
                                //$tr -> setActive();
                            }
                            if( isset( $_SESSION["user_id"] ) ) {
                                $user -> setUserLastLogin( $db_pdo, $_SESSION["user_id"] );
                            }
                            $_SESSION["targetPage"] = $_POST["t"];
                            if( $_SESSION["targetPage"] == "" ) $_SESSION["targetPage"] = "intern.php";
                            print_r( json_encode( $return ));
    break;
    case "checkForExistingEmail":
                            require_once( "classes/User.php" );
                            //$user = new \User();
                            $result = User::checkForExistingEmail( $db_pdo, $_POST["email"] );
                            $return -> count_records = $result -> count_records;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));
    break;
    case "checkForExistingPassword":
                            require_once( "classes/Account.php" );
                            $account = new \Account();
                            $result = $account -> checkForExistingPassword( $db_pdo, $_SESSION["user_id"], $_POST["password"] );
                            $return -> count_records = $result -> count_records;
                            $return -> message = $result -> message;
                            $return -> newPassword = $_POST["password"]; 
                            print_r( json_encode( $return ));
    break;
    case "registerUser":
                            require_once( "classes/Account.php" );
                            require_once( "classes/User.php" );
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            require_once( "functions.php" );
                            $user = new \User( $db_pdo );
                            $result = $user -> newUser( $db_pdo, $_POST["salutation"], $_POST["firstname"], $_POST["lastname"], $_POST["email"] );
                            $resultNewUser = $result -> success;
                            if( !$resultNewUser ) {
                                $return -> success = $resultNewUser;
                                $return -> errorNumber = $result -> errorNumber;
                                $return -> message = $result -> message;
                                print_r( json_encode( $return ));
                                die;
                            }
                            $currentUserId = $user -> getUserId();
                            $account = new \Account();
                            
                            $result = $account -> newAccount( $db_pdo, $currentUserId , '');
                            
                            if( $result && $resultNewUser ) {
                                $content = '
                                    <html>
                                    <head>
                                        <title>Registrierungs-E-Mail - Neuer User</title>
                                    </head>

                                    <body>
                                    <img src="cid:TBP" alt="Logo">

                                    <h3>Registrierungs-E-Mail</h3>
                                    <p>
                                        Es hat sich folgender User am Onlineangebot registriert:<br>
                                        User: ' . $_POST["firstname"] . ' ' . $_POST["lastname"] . '<br>
                                        E-Mail: ' . $_POST["email"] . '<br>
                                    </p>
                                    ' . getEmailSignature() . ' 
                                    </body>
                                    </html>';
                                $mail = new PHPMailer();
                                $mail->CharSet = "UTF-8";
                                $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                                $adresses = explode(",", $settings['register_form']['register_form_email'] );
                                $names = explode(",", $settings['register_form']['register_form_name'] );
                                for( $i = 0; $i < count( $adresses); $i++ ) {
                                    $mail->addAddress( $adresses[ $i ], $names[ $i ]);    
                                }
                                $mail->Subject = "Neuer Nutzer";
                                $mail->isHtml(true);
                                $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                                $mail->Body = $content;
                                if ($mail->Send()) {
                                    $return -> success = true;
                                    $return -> message = "Sie wurden erfolgreich registriert.";    
                                }
                                else {
                                    $return -> success = false;
                                    $return -> message = $mail->ErrorInfo;
                                    print_r( json_encode( $return ));
                                    die;
                                }

                                $content = '
                                    <html>
                                    <head>
                                        <title>Registrierungs-E-Mail Suchtselbsthilfe „Regenbogen”</title>
                                    </head>
                                    <body>
                                    <img src="cid:TBP" alt="Logo">
                                    <h3>Registrierungs-E-Mail</h3>
                                    <p>
                                        Dies ist eine automatisch erzeugte E-Mail. Bitte antworten Sie nicht darauf.
                                        Sie haben sich erfolgreich am Onlineangebot der Suchtselbsthilfe 
                                        „Regenbogen” registriert.
                                    </p>
                                    <p>
                                        Sie erhalten in Kürze eine Aktivierungs-E-Mail mit Ihren Zugangsdaten und einem Aktivierungslink. Mit 
                                        dem Klicken auf den Aktivierungslink, aktivieren Sie Ihr Konto und können sich danach mit den in 
                                        dieser Aktivierungs-E-Mail angegeben Zugangsdaten am Onlineangebot der Suchtselbsthilfe anmelden.
                                    </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 222 1</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>' . getEmailSignature() . '
                                    </body>
                                    </html>';
                                $mail = new PHPMailer();
                                $mail->CharSet = "UTF-8";
                                $mail->setFrom( "account@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                                $mail->addAddress( $_POST["email"], $_POST["firstname"] . ' ' . $_POST["lastname"] );    

                                $mail->Subject = "Registrierungs-E-Mail";

                                $mail->isHtml(true);
                                $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                                $mail->Body = $content;
                                if ($mail->Send()) {
                                    $return -> success = true;
                                    $return -> message = "Die Registrierungs-E-Mail wurde erfolgreich versendet.";    
                                }
                                else {
                                    $return -> success = false;
                                    $return -> message = $mail->ErrorInfo;
                                    print_r( json_encode( $return ));
                                    die;
                                }

                            } else {
                                $return -> success = false;
                                $return -> message = "Bei der Registrierung ist ein Fehler aufgetreten.";                                    
                            }
                            $return -> user_id = $currentUserId;
                            $return -> role_id = $account -> getRoleId();
                            
                            print_r( json_encode( $return ));
    break;
    case "saveProfile":
                            require_once( "classes/Account.php" );
                            require_once( "classes/User.php" );
                            $return -> success = true;
                            $account = new \Account();
                            $user = new \User( $db_pdo );
                            $result = $account -> getAccountByEmailAndPassword( $db_pdo, $_SESSION["email"], $_SESSION["password"]);
                            if( $result -> success ) {
                                $account -> setAccountId( $result -> data["id"] );
                                $userId = $result -> data["user_id"];
                                $result = $user -> updateUser( $db_pdo, $userId, $_POST["salutation"],
                                          $_POST["firstname"], $_POST["lastname"], $_POST["email"], $_POST["phone"], $_POST["description"], 
                                          $_POST["photo"], $_POST["newsletter"], $_POST["opt_in"], $_POST["allow_ga"], $_POST["allow_tr"], $_POST["remind_me"], $_POST["after_days"], $_POST["after_messages"], 
                                          $_POST["street"], $_POST["house_number"], $_POST["postal_code"], $_POST["city"], $_POST["iban"], 
                                          $_POST["institute"], $_POST["account_owner"], $_POST["birthday"] );
                                $return -> success = $result -> success;
                                if( $_POST["allow_ga"] == "true" ) {
                                    $_SESSION["allow_ga"] = 1;    
                                } else {
                                    $_SESSION["allow_ga"] = 0;
                                }
                                if( $_POST["allow_tr"] == "true" ) {
                                    $_SESSION["allow_tr"] = 1;    
                                } else {
                                    $_SESSION["allow_tr"] = 0;
                                }
                                $_SESSION["photo"] = $_POST["photo"];                           
                                $_SESSION["newsletter"] = $_POST["newsletter"];                           
                                $_SESSION["opt_in"] = $_POST["opt_in"];                           
                                $_SESSION["email"] = $_POST["email"];
                                $_SESSION["firstname"] = $_POST["firstname"];
                                $_SESSION["lastname"] = $_POST["lastname"];
                                $_SESSION["letter"] = strtoupper( substr( $_SESSION["firstname"], 0, 1 ) ) . strtoupper( substr( $_SESSION["lastname"], 0, 1 ) );
                                if( $return -> success ) {    
                                    $accountId = $account -> getAccountId();
                                    $result = $account -> updateAccount( $db_pdo, $account -> getAccountId(), $userId,
                                          $account -> getRoleId(), $_POST["password"], $_POST["activated"],
                                          $_POST["activated_on"], $_POST["created_on"]
                                    );
                                    $return -> success = $result -> success;
                                    if( $return -> success ) {
                                        $_SESSION["password"] = $_POST["password"];    
                                    }
                                    $return -> message = $result -> message;    
                                } else {
                                $return -> success = false;
                                $return -> message = $result -> message;
                                $return -> post = $_POST["firstname"];    
                                }
                            } else {
                                $return -> success = false;
                                $return -> message = $result -> message;
                                
                            }
                            
                            print_r( json_encode( $return ));
    
    break;
    case "checkCountAccounts":
                            require_once( "classes/Account.php" );
                            $account = new \Account();
                            if( $_POST["param"] == "formProfile" ) {
                                $result = $account -> getAccountsByUserId( $db_pdo, $_SESSION["user_id"] );
                            } else {
                                $result = $account -> getAccountsByUserId( $db_pdo, $_POST["user_id"] );                                
                            }
                            $return -> success = $result -> success ;
                            if( !$result -> success ) {
                                $return -> message = $result -> message;    
                            } else {
                                $return -> count_records = $result -> count_records;
                            }    
                            print_r( json_encode( $return ));
    break;
    case "deleteProfile":
                            require_once( "classes/Account.php" );
                            $account = new \Account();
                            $result = $account -> getAccountsByUserId( $db_pdo, $_SESSION["user_id"] );
                            $count_records = $result -> count_records;
                            $result_account = $account -> deleteAccount( $db_pdo, $_SESSION["account_id"] );
                            if( !$result_account -> success ) {
                                $return -> success = $result_account -> success ;
                                $return -> message = $result_account -> message;    
                            } else {
                                if( $_POST["deleteProfile"] && $result_account -> success && $count_records == 1 ) {
                                    require_once( "classes/User.php" );
                                    $user = new \User( $db_pdo );
                                    $result = $user -> deleteUser( $db_pdo, $_SESSION["user_id"] );
                                    $return -> success = $result -> success;
                                    if( $return -> success ) {
                                        $return -> message = "Das Konto wurde erfolgreich gelöscht. ";    
                                    } else {
                                        $return -> message = $result -> message;    
                                    }
                                } else {
                                        $return -> success = true;
                                        $return -> message = "Das Konto wurde erfolgreich gelöscht. ";    
                                }
                            }
                            print_r( json_encode( $return ));
    
    
    break;
    case "changePassword":
                            require_once( "classes/Account.php" );
                            $account = new \Account();
                            $result = $account -> getAccountByEmailAndPassword( $db_pdo, $_POST["email"], $_SESSION["password"] );
                            if( $result -> count_result > 0 ) {
                                    $account -> setAccountId( $result -> data["id"] );
                                    $result = $account -> updatePassword( $db_pdo, $result -> data["id"], $_POST["password"] );
                                    if( $result -> success ) {
                                        $return -> success = true;
                                        $return -> message = $result -> message;
                                        $_SESSION["password"] = $_POST["password"];
                                    } else {
                                        $return -> success = false;
                                        $return -> message = $result -> message;                                        
                                    }
                            } else {

                            }
                            $return -> changedPassword = $_POST["password"];
                            print_r( json_encode( $return ));
    
    
    break;
    case "getRoles":
                            require_once( "classes/Account.php" );
                            $account = new \Account();
                            //$return -> result = $account -> getRolesByEmail( $db_pdo, $_POST["email"] );
                            $return -> result = $account -> getUserByEmail( $db_pdo, $_POST["email"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> data = $return -> result -> data;
                            unset( $return -> result );
                            
                            print_r( json_encode( $return ));    
    
    break;
    
    case "resetPassword":
                            require_once( "classes/Account.php" );
                            require_once( "functions.php" );
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            $account = new \Account();
                            try {
                                    $return -> password = getRandomPassword();
                                    $return -> success = true;
                                    $account -> updatePassword( $db_pdo, $_POST["id"], $return -> password );
                                    $return -> message = "Das Passwort wurde erfolgreich  neu gesetzt. Wir haben Ihnen eine E-Mail mit den neuen Anmeldeinformationen gesendet.";
                                    $mail = new PHPMailer();
                                    $mail->CharSet = "UTF-8";
                                    $mail->setFrom( "account@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                                    $mail->addAddress( $_POST["email"], "" );    

                                    $mail->Subject = 'Passwort geändert - "Suchtselbsthilfe Regenbogen"';

                                    $mail->isHtml(true);
                                    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                                        $content = '
                                            <html>
                                            <head>
                                                <title>Neues Passwort für Suchtselbsthilfe „Regenbogen”</title>
                                            </head>

                                            <body>
                                            <img src="cid:TBP" alt="Logo" style="width; 100px; height: auto;">

                                            <h3>Neues Passwort für Suchtselbsthilfe „Regenbogen”</h3>

                                            <p>Wir haben Dein Passwort neu gesetzt. Bitte benutze folgende Anmeldeinformationen für Dein Konto:</p>
                                            <p>Kontotyp: '  . $_POST["role"] . '</p> 
                                            <p>E-Mail: '  . $_POST["email"] . '</p> 
                                            <p>neues Passwort: '  . $return -> password . '</p> 

                                            <h4>A C H T U N G</h4>
                                            <p>Wir empfehlen Dir, nach der Anmeldung über Dein Profil ein eigenes, neues Passwort zu setzen.</p>
                                            <p>&nbsp;</p>
                                            <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                                    <dt>Telefon: +49 444 232 2</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>
                                            ' . getEmailSignature() . '
                                        </body>
                                    </html>                                
                                    ';
                                    $mail->Body = $content;
                                    if ($mail->Send()) {
                                        $return -> success = true;
                                        $return -> message = "Die E-Mail mit Ihren neuen Anmeldeinformationen wurde erfolgreich versendet.";    
                                    }
                                    else {
                                        $return -> success = false;
                                        $return -> message = $mail->ErrorInfo;
                                        print_r( json_encode( $return ));
                                        die;
                                        }
                                    
                            } catch ( Exception $e ) {
                                        $return -> success = false;    
                                        $return -> message = "Beim Setzen des neuen Passworts ist folgender Fehler aufgetreten:" . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));    
    break;
    case "getUserById":
                            require_once( "classes/User.php" );
                            $user = new \User( $db_pdo );
                            if( $_POST["id"] == "new" ) {
                                    $return -> success = true;
                                    $return -> data = null;
                                    $return -> message = "Neue Nutzerdaten";                                    
                            } else {
                                $result = $user -> getUserById( $_POST["id"] );
                                if( $result -> success ) {
                                    $return -> success = true;
                                    $return -> data = $result -> result;
                                    $return -> message = $result -> message;    
                                } else {
                                    $return -> success = false;
                                    $return -> message = $result -> message;                               
                                }                                
                            }
                            print_r( json_encode( $return ));
    break;
    case "saveShortFormUser":
                            require_once( "classes/User.php" );
                            $user = new \User( $db_pdo );
                            $result = $user -> updateShortUser( $db_pdo, $_POST["id"], $_POST["salutation"], $_POST["firstname"],
                                                        $_POST["lastname"], $_POST["email"], $_POST["phone"],
                                                        $_POST["photo"], $_POST["opt_in"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;                               
                            print_r( json_encode( $return ));   
    break;
    case "newShortFormUser":
                            require_once( "classes/User.php" );
                            $user = new \User( $db_pdo );
                            $result = $user -> newShortUser( $db_pdo, $_POST["salutation"], $_POST["firstname"],
                                                        $_POST["lastname"], $_POST["email"], $_POST["phone"],
                                                        $_POST["photo"], $_POST["opt_in"] );
                            $return -> success = $result -> success;
                            $return -> userId = $user -> getUserId();
                            if( isset(  $result -> errorNumber ) ) {
                                $return -> errorNumber = $result -> errorNumber;    
                            } else {
                                $return -> errorNumber = 0;
                            }
                            if( $return -> success ) {
                                require_once( "classes/Account.php" );
                                $account = new \Account();
                                $result_account = $account -> newAccount( $db_pdo, $return -> userId, "", 4 );
                            }
                            $return -> message = $result -> message;                               
                            $return -> id = $_POST["id"];
                            print_r( json_encode( $return ));   
    break;
    case "deleteUserFormUser":
                            require_once( "classes/User.php" );
                            require_once( "classes/Account.php" );
                            $user = new \User( $db_pdo );
                            $account = new \Account();
                            $result = $account -> deleteAccountsByUserId( $db_pdo, $_POST["id"] );
                            if( $result -> success ) {
                                $result_user = $user -> deleteUser( $db_pdo, $_POST["id"] );
                                if( $result_user -> success ) {
                                    $return -> success = $result_user -> success;
                                    $return -> message = $result_user -> message;
                                    $return -> roleId = $_SESSION["role_id"];    
                                }
                            } else {
                                    $return -> success = $result -> success;
                                    $return -> message = $result -> message;    
                                
                            }
                            print_r( json_encode( $return ));
    break;
    case "deleteUserFromAccountAndRole":
                            $q = "delete from account where user_id = " . $_POST["id"];
                            $db_pdo -> query( $q );
                            print_r( json_encode( $return ));
    break;
    case "searchUserFormUser":
                            require_once( "classes/User.php" );
                            $user = new \User( $db_pdo );
                            $searchString = "";
                            if( $_POST["searchValueName"] != "" ) {
                                $searchString = " WHERE lastname LIKE '" . $_POST["searchValueName"] . "%'";
                                if( $_POST["searchValueEmail"] != "" ) {
                                    $searchString .= "AND email LIKE '" . $_POST["searchValueEmail"] . "%'";    
                                }    
                            }
                            if( $_POST["searchValueEmail"] != "" && $_POST["searchValueName"] == "" ) {
                                $searchString = " WHERE email LIKE '" . $_POST["searchValueEmail"] . "%'";                 
                            }
                            $result = $user -> getUsers( $db_pdo, "", "", $searchString );
                            $return -> data = $result -> data;
                            $return -> seaarchString = $searchString;
                            $return -> focusField = $_POST["field"];
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;    
                            print_r( json_encode( $return ));
                            
    break;
    case "getFormAccounts":
                            require_once( "classes/Account.php" );
                            $account = new \Account();
                            if( $_POST["id"] == "new" ) {
                                $return -> success = false;
                                $return -> role = $_SESSION["role_id"];
                                $return -> message = "Dies ist ein neuer Nutzer. Bevor Du die Konten bearbeiten können, musst Du den Nutzer speichern.";                                
                            } else {
                                $result = $account -> getAccountsByUserId( $db_pdo, $_POST["id"] );
                                $return -> success = $result -> success;
                                $return -> data = $result -> data;
                                $return -> role = $result -> role;
                                $return -> message = $result -> message;                                
                            }
                            print_r( json_encode( $return ));
    break;
    case "getRandomPassword":
                            $return -> password = getRandomPassword( $settings['admin_user_form']['password_length'] );
                            $return -> id = $_POST["id"];
                            $return -> elementId = $_POST[ "elementId" ];
                            print_r( json_encode( $return ));
    
    break;
    case "saveAccount":
                            require_once( "classes/Account.php" );
                            $return -> oldId = $_POST["id"];
                            $return -> roleId = $_SESSION["role_id"];
                            $account = new \Account();
                            if( $_POST["id"] != "new" ) {
                                $result = $account -> updateAccount( $db_pdo, $_POST["id"], $_POST["user_id"], $_POST["role_id"], $_POST["password"], $_POST["activated"], $_POST["activated_on"], $_POST["created_on"] );
                                $return -> success = $result -> success;
                                $return -> message = $result -> message;
                            } else {
                                $result = $account -> newFullAccount( $db_pdo , $_POST["user_id"], $_POST["role_id"], $_POST["password"], $_POST["activated"], $_POST["activated_on"], $_POST["created_on"]);
                                $return -> newId = $account -> getAccountId();
                                $return -> success = $result -> success;
                                $return -> message = $result -> message;
                                
                            }
                            $return -> userId = $_POST["user_id"];
                            print_r( json_encode( $return ));
    
    break;
    case "deleteAccount":
                            require_once( "classes/Account.php" );
                            $return -> userId = $_POST["user_id"];
                            $return -> accountId = $_POST["account_id"];
                            $return -> roleId = $_SESSION["role_id"];
                            $return -> deleteUser = $_POST["deleteUser"];
                            $result_user = new \stdClass();
                            $result_user -> success = true;
                            if( $_POST["deleteUser"] == "true" ) {
                                require_once( "classes/User.php" );
                                $user = new \User( $db_pdo );
                                $result_user = $user -> deleteUser( $db_pdo, $_POST["user_id"] );
                                if( !$result_user -> success ) {
                                    $return -> success = $result_user -> success;
                                    $return -> message = $result_user -> message;
                                }
                            }
                            if( $result_user -> success ) {
                                $account = new \Account();
                                $result = $account -> deleteAccount( $db_pdo, $_POST["account_id"] );
                                $return -> success = $result -> success;
                                $return -> message = $result -> message;
                            }
                            print_r( json_encode( $return ));
    
    break;
    case "sendActivationEMail":
                            require_once( "classes/User.php" );
                            require_once( "functions.php" );
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            
                            $query = "SELECT role FROM  role WHERE id  = " . $_POST['id'];
                            $stm = $db_pdo -> query($query);
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $q = "select email, password from user, account where account.user_id = user.id and user_id = " . $_POST["user_id"] . " and role_id = " . $_POST['id'];
                            $stm = $db_pdo -> query($query);
                            $r = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            require_once( "classes/Account.php" );
                            $a = new \Account();
                            
                            $r = $a -> getAccount( $db_pdo, $_POST["id"], $_POST["user_id"] );
                            $role = $r -> data[0]["role"];
                            $email = $r -> data[0]["email"];
                            $fullname = $r -> data[0]["fullname"];
                            $mail = new PHPMailer();
                            $mail->CharSet = "UTF-8";
                            $mail->setFrom( "account@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                            $mail->addAddress( $email, $fullname );    

                            $mail->Subject = 'Aktivierungs-E-Mail - Suchtselbsthilfe „Regenbogen”';

                            $mail->isHtml(true);
                            $mail->AddEmbeddedImage('../images/logo.png', 'TBP');
                            $mail->AddEmbeddedImage('../images/konto_aktivieren.png', 'TBP2');

                            $content = '
                                    <html>
                                    <head>
                                        <title>Aktivierungs-E-Mail der Suchtselbsthilfe „Regenbogen”</title>
                                    </head>

                                    <body>

                                    <img src="cid:TBP" alt="Logo" width="150">
                                    
                                    <h3>Aktivierungs-E-Mail</h3>

                                        <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                        <h4>Aktivierung</h4>
                                        <p>Klicke bitte auf diesen Button: </p>
                                        <p><a target="_blank" href="https://www.suchtselbsthilfe-regenbogen.de/activate.php?c=' . encrypt_decrypt( "encrypt", $_POST["id"] ) . '||activate"><img src="cid:TBP2" alt="Konto aktivieren" style="width: 300px;"></a></p>
                                        <p>um Dein Konto zu aktivieren.</p>
                                        <p>
                                            Nach der Kontoaktivierung kannst Du Dich mit den im folgenden Abschnitt stehenden Anmeldeinformationen am Onlineangebot der 
                                            der Suchtselbsthilfe „Regenbogen” anmelden. Klicke dazu auf den Anmeldebutton oben rechts und gib
                                            dort E-Mail-Adresse und Passwort aus dieser E-Mail ein.
                                            </p>

                                            <h4>Anmeldeinformationen</h4>
                                            <p>
                                                Deine Anmeldeinformationen lauten:
                                            
                                            </p>
                                            <p>
                                                Kontotyp: ' . $role . '<br>
                                                Anmelde-E-Mail-Adresse: ' . $email . '<br>
                                                Dein aktuelles Passwort: ' . $_POST["password"] . '
                                            </p>
                                            <h4>Nach der Anmeldung</h4>
                                            <p>
                                                Wir empfehlen Dir, nach der Anmeldung über Dein Profil, ein eigenes, neues Passwort zu setzen. Klicke dazu
                                                nach der Anmeldung auf die Kopfgrafik oben rechts und klicke dann auf "Profil". In dem erscheinenden Formular kannst Du
                                                Dein eigenes Passwort setzen, indem Du auf den Button "Passwort ändern" neben dem Passwortfeld klickst und dann 
                                                Dein gewünschtes Passwort vergibst.
                                            </p>
                                            <p>
                                                Im Bereich für die Aktivitätsbenachrichtigung kannst Du wählen, ob Du diese Funktion aktivieren möchten. Ist der Schalter
                                                auf "An" gesetzt, selektiere dann einfach nur, nach wie vielen Tagen Inaktivität Du informiert werden willst oder nach 
                                                wie vielen neuen Meldungen des internen Meldungs- und Newssystems für Dich diese Erinnerungs-E-Mail versandt werden soll.
                                            </p>
                                            <p>
                                                Wir sind stets bemüht, unser Angebot zu verbessern. Wenn Du daran mitarbeiten willst, aktiviere bitte im
                                                Profilformular die Häckchen für die Fotodarstellung und die Anschreibeerlaubnis.
                                            </p>
                                            <p>&nbsp;</p>
                                            <p>Dein "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                                    <dt>Telefon: +49 444 232 2</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>
                                            ' . getEmailSignature() . '
                               </body>
                                    </html>                                
                                    ';                                        
                                    $mail->Body = $content;
                                    if ($mail->Send()) {
                                        $return -> success = true;
                                        $return -> message = "Die Aktivierungs-E-Mail wurde erfolgreich versendet.";    
                                    }
                                    else {
                                        $return -> success = false;
                                        $return -> message = $mail->ErrorInfo;
                                        print_r( json_encode( $return ));
                                        die;
                                    }
                                    $return -> Id = $_POST["id"];
                            print_r( json_encode( $return ));
    
    break;
    case "saveAccountAndEMail":
                            require_once( "classes/Account.php" );
                            require_once( "classes/InformUser.php" );
                            $a = new \Account();
                            if( $_POST["id"] === "new" ) {
                                $r = $a -> newAccount( $db_pdo, $_POST["userId"], $_POST["password"], $_POST["roleId"] );
                                $_POST["id"] = $r -> newAccountId;    
                            } else {
                                $r = $a -> updatePassword( $db_pdo, $_POST[ "id"], $_POST["password"] );
                            }
                            $r = $a -> getAccount( $db_pdo, $_POST["id"], $_POST["userId"] );
                            $role = $r -> data[0]["role"];
                            $email = $r -> data[0]["email"];
                            $iu = new \InformUser( $db_pdo, "email", 29, 0, 0, $_POST["userId"] );
                            $iu -> addImg( '../images/konto_aktivieren.png', 'AKT', 'konto_aktivieren.png' );
                            $titleEmail = "Aktivierungs-E-Mail";
                            $titleMessage = "";
                            $contentEmail = '
                                    <html>
                                    <head>
                                        <title>Aktivierungs-E-Mail der Suchtselbsthilfe „Regenbogen”</title>
                                    </head>

                                    <body>

                                    <img src="cid:TBP" alt="Logo" style="width: 150; height: auto">
                                    
                                    <h3>Aktivierungs-E-Mail</h3>

                                        <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                        <h4>Aktivierung</h4>
                                        <p>Klicke bitte auf diesen Button: </p>
                                        <p><a target="_blank" href="https://www.suchtselbsthilfe-regenbogen.de/activate.php?c=' . encrypt_decrypt( "encrypt", $_POST["id"] ) . '||activate"><img src="cid:AKT" alt="Konto aktivieren" style="width: 300px;"></a></p>
                                        <p>um Dein Konto zu aktivieren.</p>
                                        <p>
                                            Nach der Kontoaktivierung kannst Du Dich mit den im folgenden Abschnitt stehenden Anmeldeinformationen am Onlineangebot der 
                                            der Suchtselbsthilfe „Regenbogen” anmelden. Klicke dazu auf den Anmeldebutton oben rechts und gib
                                            dort E-Mail-Adresse und Passwort aus dieser E-Mail ein.
                                            </p>

                                            <h4>Anmeldeinformationen</h4>
                                            <p>
                                                Deine Anmeldeinformationen lauten:
                                            
                                            </p>
                                            <p>
                                                Kontotyp: ' . $r -> data[0]["role"] . '<br>
                                                Anmelde-E-Mail-Adresse: ' . $r -> data[0]["email"] . '<br>
                                                Dein aktuelles Passwort: ' . $_POST["password"] . '
                                            </p>
                                            <h4>Nach der Anmeldung</h4>
                                            <p>
                                                Wir empfehlen Dir, nach der Anmeldung über Dein Profil, ein eigenes, neues Passwort zu setzen. Klicke dazu
                                                nach der Anmeldung auf die Kopfgrafik oben rechts und klicke dann auf "Profil". In dem erscheinenden Formular kannst Du
                                                Dein eigenes Passwort setzen, indem Du auf den Button "Passwort ändern" neben dem Passwortfeld klickst und dann 
                                                Dein gewünschtes Passwort vergibst.
                                            </p>
                                            <p>
                                                Im Bereich für die Aktivitätsbenachrichtigung kannst Du wählen, ob Du diese Funktion aktivieren möchten. Ist der Schalter
                                                auf "An" gesetzt, selektiere dann einfach nur, nach wie vielen Tagen Inaktivität Du informiert werden willst oder nach 
                                                wie vielen neuen Meldungen des internen Meldungs- und Newssystems für Dich diese Erinnerungs-E-Mail versandt werden soll.
                                            </p>
                                            <p>
                                                Wir sind stets bemüht, unser Angebot zu verbessern. Wenn Du daran mitarbeiten willst, aktiviere bitte im
                                                Profilformular die Häckchen für die Fotodarstellung und die Anschreibeerlaubnis.
                                            </p>
                                            <p>&nbsp;</p>
                                            <p>Dein "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                                    <dt>Telefon: +49 444 232 2</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>
                                            ' . getEmailSignature() . '
                               </body>
                                    </html>                                
                                    ';
                            $contentMessage = "";
                            $r = $iu -> sendUserInfo( $titleEmail, $titleMessage, $contentEmail, $contentMessage );
                            
                            
                            
                                
                            $return -> success = true;
                            $return -> message = "Das Konto wurde erfolgreich gespeichert und die Aktivierungs-E-Mail versandt.";
                            print_r( json_encode( $return ));
    
    
    break;
    case "showRoles":
                            require_once( "classes/Role.php" );
                            $role = new \Role();
                            $result = $role -> getRoles( $db_pdo, $_POST[ "order" ] );
                            $return -> success = $result -> success;
                            $return -> data = $result -> data;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));
    break;
    case "saveRole":
                            require_once( "classes/Role.php" );
                            $role = new \Role();
                            $result = $role -> updateRole( $db_pdo, $_POST[ "id" ], $_POST[ "name" ] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> roleId = $_POST[ "id" ];
                            print_r( json_encode( $return ));
    break;
    case "newRole":
                            require_once( "classes/Role.php" );
                            $role = new \Role();
                            $result = $role -> newRole( $db_pdo, $_POST[ "name" ] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> Id = $result -> roleId;
                            print_r( json_encode( $return ));
    break;
    case "deleteRole":
                            require_once( "classes/Role.php" );
                            $role = new \Role();
                            /*
                            1. Anzahl Konten für Nutzer<->Konten prüfen
                            2 ist Anzahl = 1 -> Nutzer löschen
                            3. alle Konten für Rolle löschen                            
                            */
                            $return -> Id = $_POST[ "id" ];
                            $result = $role -> deleteRole( $db_pdo, $_POST[ "id" ] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));
    break;    
    case "saveAccountAndSendEmail":
                            require_once( "classes/Account.php" );
                            $a = new \Account();
                            if( $_POST["id"] === "new") {
                                $r = $a -> newAccount( $db_pdo, $_POST["userId"], $_POST["password"], $_POST["roleId"] );
                                
                            } else {
                                $r = $a -> updateAccount( $db_pdo, $_POST["id"], $_POST["userId"],  $_POST["roleId"], $_POST["password"], false, "", "" );    
                            }
                            $r = $a -> newAccount( $db_pdo, $_POST["userId"], $_POST["password"], $_POST["roleId"] );
                            $return -> eCode = $r -> eCode;
                            $return -> success = $r -> success;
                            $return -> message = $r -> message;
                            if( $return -> eCode == 0 ) {
                                $return -> success = true;
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            $query = "SELECT email FROM  user WHERE id  = " . $_POST["userId"];
                            $stm = $db_pdo -> query($query);
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $email = $result[0]["email"];
                            $query = "SELECT role FROM  role WHERE id  = " . $_POST["roleId"];
                            $stm = $db_pdo -> query($query);
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $content = '
                                    <html>
                                    <head>
                                        <title>Aktivierungs-E-Mail der Suchtselbsthilfe „Regenbogen”</title>
                                    </head>

                                    <body>

                                    <img style="width: 150px; height: auto" src="cid:TBP" alt="Logo">
                                    
                                    <h3>Aktivierungs-E-Mail</h3>

                                        <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                        <h4>Aktivierung</h4>
                                        <p>Klicke bitte auf diesen Button: </p>
                                        <p><a target="_blank" href="https://www.suchtselbsthilfe-regenbogen.de/activate.php?c=' . encrypt_decrypt( "encrypt", $_POST["userId"] ) . '||activate"><img src="cid:AKT" alt="Konto aktivieren" style="width: 300px;"></a></p>
                                        <p>um Ihr Konto zu aktivieren.</p>
                                        <p>
                                            Nach der Kontoaktivierung kannst Du Dich mit den im folgenden Abschnitt stehenden Anmeldeinformationen am Onlineangebot der 
                                            der Suchtselbsthilfe „Regenbogen” anmelden. Klicke dazu auf den Anmeldebutton oben rechts und gib
                                            dort E-Mail-Adresse und Passwort aus dieser E-Mail ein.
                                            </p>

                                            <h4>Anmeldeinformationen</h4>
                                            <p>
                                                Deine Anmeldeinformationen lauten:
                                            
                                            </p>
                                            <p>
                                                Kontotyp: ' . $result[0]['role'] . '<br>
                                                Anmelde-E-Mail-Adresse: ' . $email . '<br>
                                                Dein aktuelles Passwort: ' . $_POST["password"] . '
                                            </p>
                                            <h4>Nach der Anmeldung</h4>
                                            <p>
                                                Wir empfehlen Dir, nach der Anmeldung über Dein Profil, ein eigenes, neues Passwort zu setzen. Klicke dazu
                                                nach der Anmeldung auf die Kopfgrafik oben rechts und klicke dann auf "Profil". In dem erscheinenden Formular kannst Du
                                                Dein eigenes Passwort setzen, indem Du auf den Button "Passwort ändern" neben dem Passwortfeld klickst und dann 
                                                Dein gewünschtes Passwort vergibst.
                                            </p>
                                            <p>
                                                Im Bereich für die Aktivitätsbenachrichtigung kannst Du wählen, ob Du diese Funktion aktivieren möchten. Ist der Schalter
                                                auf "An" gesetzt, selektiere dann einfach nur, nach wie vielen Tagen Inaktivität Du informiert werden willst oder nach 
                                                wie vielen neuen Meldungen des internen Meldungs- und Newssystems für Dich diese Erinnerungs-E-Mail versandt werden soll.
                                            </p>
                                            <p>
                                                Wir sind stets bemüht, unser Angebot zu verbessern. Wenn Du daran mitarbeiten willst, aktiviere bitte im
                                                Profilformular die Häckchen für die Fotodarstellung und die Anschreibeerlaubnis.
                                            </p>
                                            <p>&nbsp;</p>
                                            <p>Dein "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                                    <dt>Telefon: +49 444 232 2</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>
                                            ' . getEmailSignature() . '
                               </body>
                                    </html>                                
                                    ';                                        
                                    $mail = new PHPMailer();
                                    $mail->CharSet = "UTF-8";
                                    $mail->setFrom( "account@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                                    $mail->addAddress( $email, "" );    

                                    $mail->Subject = 'Aktivierungs-E-Mail - Suchtselbsthilfe „Regenbogen”';

                                    $mail->isHtml(true);
                                    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                                    $mail->AddEmbeddedImage('../images/konto_aktivieren.png', 'AKT', 'konto_aktivieren.png');
                                    $mail->Body = $content;
                                    if ($mail->Send()) {
                                        $return -> success = true;
                                        $return -> message = "Die Aktivierungs-E-Mail wurde erfolgreich versendet.";    
                                    }
                                    else {
                                        $return -> success = false;
                                        $return -> message = $mail->ErrorInfo;
                                        print_r( json_encode( $return ));
                                        die;
                                    }
                                
                            }
                            print_r( json_encode( $return ));
                            
    break;                            

 // chat.php
    case "newChat":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> chats = $chat -> newChat( $db_pdo, $_POST["name"], $_POST["nickName"], $_POST["roleId"] );
                            $return -> success = $return -> chats -> success;  
                            $return -> message = $return -> chats -> message;
                            $return -> content = $return -> chats -> content;
                            if( $return -> success ) {
                                $return -> newChatId = $return -> chats -> newChatId;
                                $return -> newRoomId = $return -> chats -> newRoomId;
                            }
                            unset( $return -> chats );  
                            print_r( json_encode( $return ));
    break;
    case "deleteChat":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> chats = $chat -> deleteChat( $db_pdo, $_POST["chatId"] );
                            $return -> success = $return -> chats -> success;  
                            $return -> message = $return -> chats -> message; 
                            print_r( json_encode( $return ));
    break;
    case "getChats":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> chats = $chat -> getChats( $db_pdo );
                            $return -> success = $return -> chats -> success;  
                            print_r( json_encode( $return ));
    break;
    case "enterChat":
                            require_once( "classes/Chat.php" );
                            $result = new \stdClass();
                            $chat = new \Chat();     
                            $_SESSION["nickName"] = $_POST["nickName"];
                            $_SESSION["chatFontColor"] = $_POST["fontColor"];
                            $result -> countNickname = $chat -> checkForExistingNickname( $db_pdo, $_POST["chatId"], $_SESSION["nickName"] );
                            if( $result -> countNickname > 0 ) {
                                $return -> success = false;    
                                $return -> message = "Dieser Nickname kann nicht verwendet werden, da er bereits in Benutzung ist.";    
                            } else {
                                $return -> success = true;    
                                $return -> message = "Der Chat wurde erfolgreich betreten";
                                $return -> nickname = $_POST["nickName"];
                                $return -> result = $chat -> enterChat( $db_pdo, $_POST["chatId"], $_SESSION["nickName"], $_SESSION["chatFontColor"], $settings );                         
                            }
                            print_r( json_encode( $return ));
    break;
    case "switchRoom":
                            require_once( "classes/Chat.php" );
                            $result = new \stdClass();
                            $chat = new \Chat();
                            if( !isset( $_SESSION["nickName"] ) ) {
                                
                                $_SESSION["nickName"] = $_POST["nickName"];
                                // check for existing nickname
                                $result -> countNickname = $chat -> checkForExistingNickname( $db_pdo, $_POST["chatId"],$_POST["nickName"] );
                            } else {
                                
                            }
                            $return -> result = $chat -> switchRoom( $db_pdo, $_POST["userId"], $_POST["nickname"], $_POST["chatId"], $_POST["oldRoomId"], $_POST["newRoomId"], $_POST["fontColor"] );
                            $return -> chatId = $_POST["chatId"];
                            $return -> nickname = $_POST["nickname"];
                            $return -> roomId = $_POST["newRoomId"];
                            $return -> userId = $_POST["userId"];
                            $return -> roomName = $_POST["roomName"];
                            print_r( json_encode( $return ));
    break;
    case "getRoomDetails":
                            require_once( "classes/ChatRoom.php" );
                            $return -> result = ChatRoom::getRoomDetails( $db_pdo, $_POST["roomId"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> data = $return -> result -> data;
                            unset( $return -> result );
                            print_r( json_encode( $return ));
    break;
    case "saveRoomDetails":
                            require_once( "classes/ChatRoom.php" );
                            $return -> result = ChatRoom::saveRoomDetails( $db_pdo, $_POST["roomId"], $_POST["active"], $_POST["public"], $_POST["permanent"], $_POST["description"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            unset( $return -> result );
                            print_r( json_encode( $return ));
    break;
    
    case "leftChat":
                            require_once( "classes/Chat.php" );
                            $result = new \stdClass();
                            $chat = new \Chat();
                            $return -> chatId = $_POST["chatId"];
                            $return -> result = $chat -> leftChat( $db_pdo, $_POST["chatId"], $_POST["roomId"], $_POST["userId"], $_POST["nickName"] );
                            print_r( json_encode( $return ));
    break;
    case "sendChatContent":
                            require_once( "classes/Chat.php" );
                            $result = new \stdClass();
                            $chat = new \Chat();
                            $return -> data = $chat -> setChatContent( $db_pdo, $_POST["chatId"], $_POST["roomId"], $_POST["userId"], $_POST["content"]);
                            print_r( json_encode( $return ));
    break;
    case "refreshContent":
                            require_once( "classes/Chat.php" );
                            $result = new \stdClass();
                            $chat = new \Chat();
                            if( $_POST["chatId"] == "" ) {
                                $return -> data = null;
                            } else {
                                $result = $chat -> refreshChat( $db_pdo, $_POST["chatId"], $_POST["roomId"], $settings);
                                $return -> data = $result;                                
                            }
                            $return -> chats = $chat -> getChats( $db_pdo );
                            require_once( "classes/User.php" );
                            $user = new \User( $db_pdo );
                            if( isset( $_SESSION["user_id"])) {
                                $return -> user = $user -> setLastActivity( $db_pdo );
                            }
                            print_r( json_encode( $return ));
    break;
    case "getChatDetails":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> getChatDetails( $db_pdo, $_POST["chatId"] );

                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  

                            print_r( json_encode( $return ));
    break;
    case "saveChatDetails":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> saveChatDetails( $db_pdo, $_POST["id"],$_POST["active"], $_POST["public"], $_POST["permanent"], $_POST["description"] );

                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  

                            print_r( json_encode( $return ));
    break;
    case "getCountParticipants":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> parameter = $_POST["parameter"];
                            $return -> chatId = $_POST["chatId"];
                            
                            $return -> result = $chat -> getCountParticipants( $db_pdo, $_POST["chatId"] );
                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  

                            print_r( json_encode( $return ));
    break;
    case "getRooms":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> getRooms( $db_pdo, $_POST["chatId"], $_POST["active"], $_POST["public"] );
                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  

                            print_r( json_encode( $return ));
    break;
    case "newRoom":
                            require_once( "classes/ChatRoom.php" );
                            $return -> result = new \ChatRoom( $db_pdo, $_POST["name"], $_SESSION["user_id"], $_POST["roleId"], $_POST["chatId"], 1, 1, 1 );
                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;                   
                            print_r( json_encode( $return ));
    break;
    case "saveRoom":
                            require_once( "classes/ChatRoom.php" );
                            $return -> result = ChatRoom::saveRoom( $db_pdo, $_POST["chat_id"], $_SESSION["user_id"], $_POST["roleId"], $_POST["chatId"], 1, 1, 1 );
                            print_r( json_encode( $return ));
    break;
    case "deleteRoom":
                            $query = "DELETE FROM chat_room WHERE id = " . $_POST["roomId"];
                            try {
                                $return -> result = $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Das Löschen des Raumes war erfolgreich";
                                    
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen des Raumes ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));
    break;
    case "getParticipants":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> getParticipants( $db_pdo, $_POST["chatId"], $_POST["roomId"], $_POST["userId"], $settings["logout"]["automatic_timeout"] );
                            $return -> forRoom = $return -> result -> forRoom;
                            $return -> forChat = $return -> result -> forChat;
                            $return -> forOtherChat = $return -> result -> forOtherChat;
                            $return -> forActiveParticipants = $return -> result -> forActiveParticipants;
                            $return -> forInactiveParticipants = $return -> result -> forInactiveParticipants;
                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  
                            unset( $return -> result );
                            print_r( json_encode( $return ));
    break;
    case "inviteUser":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> inviteUser( $db_pdo,  $_POST["toUser"], $_POST["fromUser"], $_POST["toChat"], $_POST["toRoom"], $settings["chat"]["invitation_until_interval"], $_POST["section"]);
                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  
                            print_r( json_encode( $return ));                                
    break;
    case "acceptInvitation":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> acceptInvitation( $db_pdo, $_POST["param"], $_POST["invitationId"] );
                            $return -> success = $return -> result -> success;  
                            $return -> message = $return -> result -> message;  
                            $return -> roomId = $return -> result -> roomId;
                            $return -> roomName = $return -> result -> roomName;
                            print_r( json_encode( $return ));                                                            
    break;
// admin_portal.php -> Chats
    case "resetChats":
                            try {
                                $query = "TRUNCATE chat";
                                $db_pdo -> query( $query );  
                                $query = "TRUNCATE chat_room";
                                $db_pdo -> query( $query );  
                                $query = "TRUNCATE chat_user";
                                $db_pdo -> query( $query );  
                                $query = "TRUNCATE chat_content";
                                $db_pdo -> query( $query );  
                                $query = "TRUNCATE chat_invitation";
                                $db_pdo -> query( $query );
                                $query = "TRUNCATE game";
                                $db_pdo -> query( $query );
                                $query = "TRUNCATE game_move";
                                $db_pdo -> query( $query );
                                $query = "INSERT INTO `chat` (`name`, `creator`, `active`, `public`, `permanent`, `current_datetime`, `description`) VALUES ('Regenbogen', '1', '1', '1', '1', current_timestamp(), 'Chat der Suchtselbsthilfe \"Regenbogen\"')";  
                                $db_pdo -> query( $query );
                                $query = "INSERT INTO `chat_room` (`chat_id`, `name`, `active`, `public`, `permanent`, `current_datetime`, `creator`, `description`) VALUES ('1', 'Zentrale', '1', '1', '1', current_timestamp(), '1', 'Zentralraum des Regenbogen-Chats')";  
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Die Chats wurden erfolgreich zurückgesetzt.";
                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Zurücksetzen der Chats ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                                
    break;
    case "getUsersForChats":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $return -> result = $chat -> getUsersForChats( $db_pdo );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> data = $return -> result -> data;
                            unset( $return -> result );
                            print_r( json_encode( $return ));                                
    break;
    case "resetUserForChat":
                            try {
                                $query = "DELETE FROM chat_user WHERE id = " . $_POST["userId"];
                                $db_pdo -> query( $query );  
                                $return -> success = true;
                                $return -> message = "Der Chatnutzer wurde erfolgreich zurückgesetzt.";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Zurücksetzen der Chatnutzer ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                                                                
    break; 
    case "adminChats":
                            try {
                                $query = "SELECT chat.id, chat.active, chat.public, chat.permanent, chat.name, chat.description,
                                            CONCAT( user.firstname, ' ', user.lastname ) as creator FROM chat, user WHERE
                                            chat.creator = user.id ORDER BY chat.id";
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC); 
                                $return -> success = true;
                                $return -> message = "Die Chats wurden erfolgreich gelesen.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Chats ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                                                                
    break;
    case "adminRooms":
                            try {
                                $query = "SELECT chat_room.id, chat_room.active, chat_room.public, chat_room.permanent, 
                                            chat_room.name, chat_room.description, chat.name AS chat_name,
                                            CONCAT( user.firstname, ' ', user.lastname ) as creator FROM chat_room, chat, user WHERE
                                            chat_room.chat_id = chat.id AND chat_room.creator = user.id ORDER BY chat_room.id";
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC); 
                                $return -> success = true;
                                $return -> message = "Die Räume wurden erfolgreich gelesen.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Räume ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                                                                
    break;
    case "saveAdminChat":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $result = $chat -> saveAdminChat( $db_pdo, $_POST["chatId"], $_POST["active"], $_POST["public"], $_POST["permanent"], $_POST["name"], $_POST["description"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            unset( $return -> result );
                            print_r( json_encode( $return ));                                                                
    break;
    case "saveAdminRoom":
                            require_once( "classes/Chat.php" );
                            $chat = new \Chat();
                            $result = $chat -> saveAdminRoom( $db_pdo, $_POST["roomId"], $_POST["active"], $_POST["public"], $_POST["permanent"], $_POST["name"], $_POST["description"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            unset( $return -> result );
                            print_r( json_encode( $return ));                                                                
    break;
    
// end admin_portal.php -> Chats
// admin_portal.php -> Calendar
    case "showMember":
                            require_once( "classes/DataForm.php" );
                            try {
                                $df = new \DataForm( $db_pdo, $_POST["pageSource"], true, $_POST["fields"], $_POST["isNew"],$_POST["searchString"] );
                                $return -> html = $df -> getHtmlJson( $_POST["fields"], $_POST["fieldDefs"], false, true, false, $_POST["fieldPraefix"], [], true );
                                $return -> divId = $_POST["divId"];
                                $return -> dVar = $_POST["dVar"];
                                $return -> roleId = $_POST["roleId"];
                                $tmp = explode( "=", $_POST["searchString"] )[1];
                                $query = "SELECT role FROM role WHERE id= " . $tmp;
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $return -> title = $result[0]["role"];
                                $return -> success = true;
                                $return -> message = "Die Mitglieder wurden erfolgreich gelesen.";                                                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Mitglieder ist folgender Fehler aufgetreten: " . $e -> getMessage();
                                
                            }
                            print_r( json_encode( $return ));
    break;
    case "setMembership":
                            require_once( "classes/Account.php" );
                            if( $_POST["userId"] == "" ) return;
                            $a = new \Account();
                            $return -> result = $a -> newAccount( $db_pdo, $_POST["userId"], getRandomPassword(), $_POST["roleId"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> roleId = $_POST["roleId"];
                            print_r( json_encode( $return ));
    break;
    case "deleteMembership":
                            require_once( "classes/Account.php" );
                            $a = new \Account();
                            $return -> result = $a -> deleteAccount( $db_pdo, $_POST["accountId"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> roleId = $_POST["roleId"];
                            print_r( json_encode( $return ));
    break;
// end admin_portal.php -> Calendar
// start pinwand
    case "editAdvertisement": 
    case "getAdvertisement":
                            require_once( "classes/Ad.php" );
                            $ad = new \Ads();
                            $return -> data = $ad -> getAds( $db_pdo, " WHERE advertisement.id = " . $_POST["id"] );
                            $return -> answers = $ad -> getAdAnswers( $db_pdo, " WHERE `advertisement_answer`.`ad_id` = " . $return ->data->data[0]["id"] );
                            $return -> apendix = getForApendix( $_POST["id"] );
                            print_r( json_encode( $return ));
    break;
    case "saveAdvertisement":
                            $query = "UPDATE advertisement SET title = '" . $_POST["title"] . "', content='" . $_POST["content"] . "' WHERE id = " . $_POST["id"];
                            try { 
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Die Anzeige wurde erfolgreich gespeichert.";
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Anzeige ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }
                            $query = "SELECT user.id FROM user, account  WHERE user.opt_in = 1 AND account.user_id = user.id AND role_id = " . $_POST["informRole"];
                            $stm = $db_pdo -> query( $query );
                            $result = $stm -> fetchAll( PDO::FETCH_ASSOC );
                            $l = count( $result );
                            $i = 0;
                            while ( $i < $l ){
                                sendNewPinwandEmail( $db_pdo, $result[$i]["id"], $_POST["title"] );
                                $i += 1;
                            }
                            
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
    case "addAdvertisement":
                            $query = "INSERT INTO advertisement (from_user, title, content, from_date, to_date) VALUES (" . $_POST["user_id"] . ", '" . $_POST["title"] . "', '" . $_POST["content"] . "', '" . $_POST["date"] . "', '" . $_POST["to_date"] . "');";
                            try { 
                                $db_pdo -> query( $query );
                                $newId = $db_pdo -> lastInsertId();
                                handleForApendix( $newId );
                                $return -> success = true;
                                $return -> message = "Die Anzeige wurde erfolgreich angelegt.";
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Anlegen der Anzeige ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }
                            $query = "SELECT user.id FROM user, account  WHERE account.user_id = user.id AND role_id = " . $_POST["informRole"];
                            $stm = $db_pdo -> query( $query );
                            $result = $stm -> fetchAll( PDO::FETCH_ASSOC );
                            $l = count( $result );
                            $i = 0;
                            while ( $i < $l ){
                                sendNewPinwandEmail( $db_pdo, $result[$i]["id"], $_POST["title"] );
                                $i += 1;
                            }
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
    case "addAdvertisementAnswer":
                            $query = "INSERT INTO advertisement_answer (ad_id, user_id, content, from_date) VALUES (" . $_POST["ad_id"] . ", " . $_POST["user_id"] . ", '" . $_POST["content"] . "', '" . $_POST["from_date"] . "');";
                            $db_pdo -> query( $query );
                            // create message
                            require_once( "classes/Message.php" );
                            $m = new \Message();
                                // 1. get ad owner
                                $query = "SELECT from_user from advertisement WHERE id = " . $_POST["ad_id"];
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);                                
                                $query = "SELECT user.id, CONCAT( firstname, ' ', lastname ) AS name, email, title, from_user FROM user, advertisement WHERE user.id = from_user AND advertisement.id = " . $_POST["ad_id"] . "  AND from_user = " . $result[0]["from_user"];
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $datum = getGermanDateFromMysql( $_POST["from_date"] );
                                $from_user = $_SESSION["firstname"] . " " . $_SESSION["lastname"];
                                // end get ad owner
                                $m -> newMessage( $db_pdo, "Antwort auf Kleinanzeige", "Am $datum hat  $from_user auf deine Anzeige „" . $result[0]["title"] . "” geantwortet.", 0, $_SESSION["user_id"], 0, $result[0]["from_user"] );
                            // end create message
                            // create email
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            $mail = new \PHPMailer\PHPMailer\PHPMailer();
                            $exception = new \PHPMailer\PHPMailer\Exception();
                            $mail = new \PHPMailer\PHPMailer\PHPMailer();
                            $mail->CharSet = "UTF-8";
                            $mail->setFrom( "kleinanzeigen@suchtselbsthilfe-regenbogen.de", "Kleinanzeigen Suchtselbsthilfe „Regenbogen”");
                            $mail->addAddress( $result[0]["email"], $result[0]["name"] );    

                            $mail->Subject = 'Antwort auf Deine Kleinanzeige Suchtselbsthilfe „Regenbogen”';

                            $mail->isHtml(true);
                            $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                            $content = "<img src='cid:TBP' alt='Logo' style='width: 150px'>
                <h3>Antwort-E-Mail der Kleinanzeigen</h3>
                <p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                </p>
                <h4>E-Mail auf Deine Kleinanzeige</h4>
                <p>Am $datum hat $from_user auf deine Anzeige „" . $result[0]["title"] . "” geantwortet.</p>
                <p>&nbsp;</p>
                <p>Dein Suchtselbsthilfe-„Regenbogen”-Team</p>
                <address>
                    <dl>
                        <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                        <dt>Telefon: +49 341 444 232 2</dt>
                        <dt>Adresse:</dt>
                        <dd>Demmeringstr. 47-49</dd>
                        <dd>D-04177 Leipzig</dd>
                        <dd>Germany</dd>
                    </dl>
                </address>
                " . getEmailSignature();
                            $mail->Body = $content;
                            if ($mail->Send()) {
                                $return -> success = true;
                                $return -> message = "Die Antwort-E-Mail wurde erfolgreich versendet.";    
                            }
                            else {
                                $return -> success = false;
                                $return -> message = $mail->ErrorInfo;
                            }
                            // end create email
                            $db_pdo -> query( $query );
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
    case "getBackanswerForAnswer":
                            try {
                                $query = "SELECT * FROM advertisement_backanswer WHERE to_answer = " . $_POST["anwerId"];
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $query = "SELECT advertisement_answer.ad_id FROM advertisement_answer WHERE id = " . $_POST["anwerId"];
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $return -> adId = $result[0]["ad_id"];
                                $return -> answerId = $_POST["anwerId"];
                                $return -> success = true;
                                $return -> message = "Die Rückantworten wurden erfolgreich gelesen.";
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Rückantworten ist folgender Fehler aufgetreten: " . $e -> getMessage() . ".";;                   
                            }
                            print_r( json_encode( $return ));
    break;
    case "getBackanswerForInfoDialog":
                            try {
                                $query = "SELECT * FROM advertisement_backanswer WHERE to_answer = " . $_POST["anwerId"];
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $query = "SELECT advertisement_answer.ad_id FROM advertisement_answer WHERE id = " . $_POST["anwerId"];
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $return -> adId = $result[0]["ad_id"];
                                $return -> answerId = $_POST["anwerId"];
                                $return -> success = true;
                                $return -> message = "Die Rückantworten wurden erfolgreich gelesen.";
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Rückantworten ist folgender Fehler aufgetreten: " . $e -> getMessage() . ".";;                   
                            }
                            print_r( json_encode( $return ));
    break;
    case "deleteAd":
                            $query = "SELECT DISTINCT( id ) FROM `advertisement_answer` WHERE ad_id = " . $_POST["id"];
                            $stm = $db_pdo -> query( $query );
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            //$adId = $result[0]["id"];
                            try { 
                                if( isset( $result[0]["id"] ) && $result[0]["id"] != "" ) {

                                //if( $result[0]["id"] != "" ) {
                                    $query = "DELETE FROM `advertisement_backanswer` WHERE to_answer = " . $result[0]["id"];
                                    $db_pdo -> query( $query );                                    
                                }                        
                                $query = "DELETE FROM `advertisement` WHERE id = " . $_POST["id"];
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $glob = glob( "../images/ad/ad_"  . $_POST["id"] . "*.*" );
                                for( $i = 0; $i < count( $glob); $i++ ) {
                                    unlink( $glob[ $i ] );
                                }
                                $return -> message = "Die Anzeige wurde erfolgreich gelöscht.";
                                
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen der Anzeige ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }        
                            print_r( json_encode( $return ));                            
    break;
    case "sendBackanswer":                            
                            require_once( "classes/Message.php" );
                            try {
                                switch( $settings["pinwand"]["message_behavior"] ) {
                                    case "both":
                                                $m = new \Message();
                                                $title = "Rückantwort auf \"" . $_POST["title"] . "\"";
                                                $m -> newMessage( $db_pdo, $title, $_POST["content"], 0, $_POST["fromUser"], 0, $_POST["toUser"] );
                                                sendBackanswerEmail( $db_pdo, $title, $_POST["content"], $_POST["toUser"], $_POST["fromUser"]);
                                    break;                                
                                    case "message":
                                                $m = new \Message();
                                                $title = "Rückantwort auf \"" . $_POST["title"] . "\"";
                                                $m -> newMessage( $db_pdo, $title, $_POST["content"], 0, $_POST["fromUser"], 0, $_POST["toUser"] );
                                    break;                                
                                    case "email":
                                                sendBackanswerEmail( $db_pdo, $title, $_POST["content"], $_POST["toUser"], $_POST["fromUser"]);
                                    break;                                
                                    case "intelligent":
                                                $query = "SELECT opt_in FROM user WHERE id = " . $_POST["toUser"];
                                                $stm = $db_pdo -> query( $query );
                                                $result_toUser = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                                if( $result_toUser[0]["opt_in"] == 1 ) {
                                                    sendBackanswerEmail( $db_pdo, $title, $_POST["content"], $_POST["toUser"], $_POST["fromUser"]);
                                                } else  {
                                                    $m = new \Message();
                                                    $title = "Rückantwort auf \"" . $_POST["title"] . "\"";
                                                $m -> newMessage( $db_pdo, $title, $_POST["content"], 0, $_POST["fromUser"], 0, $_POST["toUser"] );
                                                }
                                    break;                                
                                }
                                $query = "INSERT INTO advertisement_backanswer ( to_answer, content) VALUES (" . $_POST["id"] . ", '" . $_POST["content"] . "')";
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Die Rückantwort wurde erfolgreich durchgeführt";
                                
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Rückantworten auf die Anzeige ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }
                            print_r( json_encode( $return ));                            
    break;
    case "deleteNewApendix":
                            try {
                                $tmp = explode( "/", $_POST["fileName"] );
                                $l = count( $tmp );
                                $i = 2;
                                $fName = "../";
                                for( $i = 1; $i < $l; $i++ ) {
                                    $fName .= $tmp[$i] . "/";
                                }
                                $fName = substr( $fName , 0, strlen( $fName ) - 1 );
                                if( file_exists( $fName ) ) {
                                    unlink( $fName );
                                    $return -> success = true;
                                    $return -> message = "Der Anhang wurde erfolgreich gelöscht";
                                }
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen des Anhangs ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }
                            print_r( json_encode( $return ));                            
    break;
    case "deleteEditApendix":
                            try {
                                $glob = glob( "../images/ad/ad_" . $_POST["id"] . "*.*" );
                                for( $i = 0; $i < count( $glob ); $i++ ) {
                                    if( file_exists( $glob[$i] ) ) {
                                        unlink( $glob[$i] );
                                        $return -> success = true;
                                        $return -> message = "Der Anhang wurde erfolgreich gelöscht";
                                    }                                    
                                }
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen des Anhangs ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }
                            print_r( json_encode( $return ));                            
    break;
// end pinwand
// start calendar
/*    
    case "deleteEvent":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $result = $ev -> deleteEvent( $db_pdo, $_POST["id"], $settings["calendar"]["message_behavior"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));    
    break;
    case "deleteSerieEvent":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $result = $ev -> deleteSerieEvent( $db_pdo, $_POST["id"], $_POST["groupId"], $settings["calendar"]["message_behavior"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));    
    break;
    case "saveEvent":
                            require_once( "classes/CalendarEvent.php");
                            require_once( "classes/InformUser.php" );
                            $ev = new \CalendarEvent();
                            // get old data
                            $q = "select * from event where id = " . $_POST["id"];                           
                            $s = $db_pdo -> query( $q );
                            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
                            //
                            $result = $ev -> saveEvent( $db_pdo, $_POST["id"], $_POST["group_id"], $_POST["title"], $_POST["fromDate"], $_POST["toDate"], $_POST["fromTime"], $_POST["toTime"], $_POST["url"], $_POST["description"], $_POST["notice"], $_POST["place"], $_POST["format"], $_POST["deadline"], $_POST["innerUrl"], $_POST["innerUrlText"], $_POST["creator"], $_POST["countPart"]  );
                            // if success and count participants greater 0 
                            $parts = $ev -> getParticipants( $db_pdo, $_POST["id"] ) -> Ids;
                            $q = "select name from event_format where format = '" . $_POST["format"] . "'";
                            $s = $db_pdo -> query( $q );
                            $r_cat = $s -> fetchAll( PDO::FETCH_ASSOC );
                            if( $result -> success && count( $parts ) > 0 ) {
                            // build change text if save success, $r is old data
                                $cTxt = "";
                                if( count ( $r ) > 0 ) {                                
                                    if( $r[0]["title"] !== $_POST["title"] ) {
                                        $cTxt .= "Der Titel ist jetzt: “" . $_POST["title"] . "“. ";    
                                    }
                                    if( $r[0]["start_date"] !== $_POST["fromDate"] || substr( $r[0]["start_time"], 0, 5 ) !== $_POST["fromTime"] ) {
                                        $cTxt .= "Das Datum/Zeit wurde auf " . getGermanDateFromMysql( $_POST["fromDate"] ) . " um " . $_POST["fromTime"] . " Uhr gesetzt. ";
                                    }
                                    if( $r[0]["end_date"] !== $_POST["toDate"] || substr( $r[0]["end_time"], 0, 5 ) !== $_POST["toTime"] ) {
                                        $cTxt .= "Der Termin endet jetzt am " . getGermanDateFromMysql( $_POST["toDate"] ) . " " . $_POST["toTime"] . " Uhr. ";
                                    }
                                    if( $r[0]["class"] !== $_POST["format"] ) {
                                        $cTxt .= "Die Terminkategorie ist nun “" . $r_cat[0]["name"] . "“. ";
                                    }
                                    if( $r[0]["place"] != $_POST["place"] ) {
                                        $q = "select place from event_place where id = " . $_POST["place"];
                                        $s = $db_pdo -> query( $q );
                                        $r_place = $s -> fetchAll( PDO::FETCH_ASSOC );
                                        $cTxt .= "Der Ort ist nun “" . $r_place["place"] . "“. ";
                                    }
                                    if( $r[0]["creator"] != $_POST["creator"] ) {
                                        $q = "select concat(salutation.salutation, ' ', firstname, ' ', lastname ) as fullname from salutation, user where user.salutation = salutation.id and user.id = " . $_POST["creator"];
                                        $s = $db_pdo -> query( $q );
                                        $r_creator = $s -> fetchAll( PDO::FETCH_ASSOC );
                                        $cTxt .= "Der/die Terminverantwortliche ist nun " . $r_creator[0]["fullname"] . ". ";
                                    }
                                    if( $r[0]["description"] != $_POST["description"] ) {
                                        $cTxt .= "Die Terminbeschreibung lautet nun “" . $_POST["description"] . "“. ";
                                    }
                                    if( $r[0]["inner_url"] !== $_POST["innerUrl"] ) {
                                        $cTxt .= "Der Anhang wurde geändert. ";                                    
                                    }
                                    if( $r[0]["url"] !== $_POST["url"] ) {
                                        $cTxt .= "Der externe Link wurde geändert. ";                                    
                                    }
                                }
                                $l = count( $parts );
                                $i = 0;
                                // only if participants and not empty change text
                                while( $i < $l && $cTxt != "" ) {
                                    $iu = new \InformUser( $db_pdo, $settings["calendar_editable"]["message_behavior"], 27, 0, 0, $parts[$i], true );
                                    $title = "Der Termin “" . $r[0]["title"] . "” vom " . getGermanDateFromMysql( $r[0]["start_date"] ) . " " . substr( $r[0]["start_time"], 0, 5 ) . " Uhr wurde geändert.";
                                    $res = $iu -> sendUserInfo( $title, $title, $cTxt, $cTxt );
                                    unset( $iu );                        
                                    $i += 1;
                                }
                                
                            //    
                            }
                            // inform participants
                            
                            $iUser = $ev -> buildInformUser( $db_pdo, $_POST["informRole"], $_POST["informUser"], $parts );
                            $l = count( $iUser );
                            $i = 0;
                            if( !isset( $r_cat[0] ) ) {
                                $title = "Neuer Termin - ohne";    
                            } else {
                                $title = "Neuer Termin - " . $r_cat[0]["name"];
                            }
                            $content = "Es wurde für den " . getGermanDateFromMysql( $_POST["fromDate"] ) . " " . $_POST["fromTime"] . " Uhr der Termin “" . $_POST["title"] . "” eingestellt. Bitte prüfe, ob Du teilnehmen kannst und bestätige Deine Teilnahme im Veranstaltungskalender.";
                            while( $i < $l ) {
                                $iu = new \InformUser( $db_pdo, $settings["calendar_editable"]["message_behavior"], 27, 0, 0, $iUser[$i], true );
                                $res = $iu -> sendUserInfo( $title, $title, $content, $content );
                                unset( $iu );                        
                                $i += 1;
                            }
                            
                            
                            
                            // end inform participants
                            
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));    
    break;
    case "saveSerieEvent":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $result = $ev -> saveSerieEvent( $db_pdo, $_POST["id"], $_POST["group_id"], $_POST["title"], $_POST["fromDate"], $_POST["toDate"], $_POST["fromTime"], $_POST["toTime"], $_POST["url"], $_POST["description"], $_POST["place"], $_POST["format"], $_POST["deadline"], $_POST["innerUrl"], $_POST["innerUrlText"]  );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));    
    break;
    case "newEvent":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> newEvent( $db_pdo, $_POST["group_id"], $_POST["title"], $_POST["fromDate"], $_POST["toDate"], $_POST["fromTime"], $_POST["toTime"], $_POST["url"], $_POST["description"], $_POST["notice"], $_POST["repeat"], $_POST["repeat_to"], $_POST["place"], $_POST["format"], $_POST["deadline"], $_POST["innerUrl"], $_POST["innerUrlText"], $_POST["creator"]  );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            require_once( "classes/InformUser.php" );
                            $ui = new \InformUser( $db_pdo, $settings["calendar_editable"]["message_behavior"], 27, 0, $_POST["informRole"],$_POST["informUser"], true );
                            $content = "Es wurde für den " . getGermanDateFromMysql( $_POST["fromDate"] ) . " der Termin „" . $_POST["title"] ."” eingestellt. Bitte prüfe, ob Du teilnehmen kannst und bestätige dann deine Teilnahme im Veranstaltungskalender über die „Teilnehmen”-Funktion des Termins.";
                            $q_cat = "select name from event_format where format = '" . $_POST["format"] . "'";
                            $s = $db_pdo -> query( $q_cat );
                            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
                            if( count( $r ) > 0 ) {
                                $titleEmail = "Neuer Termin - " . $r[0]["name"];    
                            } else {
                                $titleEmail = "Neuer Termin - ohne Kategorie";
                            }
                            $ui ->sendUserInfo( $titleEmail, $titleEmail, $content, $content );
                            // participate self is clicked
                            if( $_POST["participate"] == "true" ) {
                                $ev -> participate( $db_pdo, $_SESSION["user_id"], $return -> result -> lastEventId, $settings["calendar_editable"]["message_behavior"], $settings["calendar_editable"]["inform_myself"], $_POST["participate"], $_POST["participateAs"], $_POST["countPart"] );
                            }
                            print_r( json_encode( $return ));    
    break;
    case "participateEvent":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> participate( $db_pdo, $_POST["user_id"], $_POST["event_id"], $settings["calendar_editable"]["message_behavior"], $_POST["remindMe"], true, $_POST["participateAs"], $_POST["countPart"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            print_r( json_encode( $return ));    
    break;
    case "setCountParticipants":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> setCountParticipants( $db_pdo, $_POST["userId"], $_POST["eventId"], $_POST["countPart"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            print_r( json_encode( $return ));    
    
    break;
    case "deleteParticipation":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> deleteParticipation( $db_pdo, $_POST["user_id"], $_POST["event_id"], $settings["calendar_editable"]["message_behavior"]  );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            print_r( json_encode( $return ));    
    break; 
    case "showParticipants":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> getParticipants( $db_pdo, $_POST["event_id"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> data = $return -> result -> data;
                            $return -> sum = $return -> result -> sum;
                            unset( $return -> result );
                            print_r( json_encode( $return ));    
    break;
*/
/*
    case "setRemindMe":
                            $query = "UPDATE event_participate SET remind_me = " . $_POST["value"] . ",  role_id = " . $_POST["participateAs"] . " WHERE event_id = " . $_POST["eventId"] . " AND user_id = " . $_POST["userId"];
                            try {
                                $db_pdo -> query( $query );            
                                $return -> success = true;
                                $return -> message = "Die Erinnerung wurde erfolgreich gespeichert.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Erinnerung ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            
                            print_r( json_encode( $return ));    
    break;
*/
/*
    case "getPlaces":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> getPlaces( $db_pdo);
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            $return -> data = $return -> result -> data;
                            print_r( json_encode( $return ));    
    break;
    case "requestEvent":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> result = $ev -> requestEvent( $db_pdo, $_POST["evId"], $_POST["content"]);
                            print_r( json_encode( $return ));    
    break;
*/
    // TODO: extract calendar functions till here
/*
    case "saveFormat":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();                           
                            $return -> result = $ev -> saveFormat( $db_pdo, $_POST["id"], $_POST["name"], $_POST["background"], $_POST["font"]);
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            //$return -> data = $return -> data;
                            print_r( json_encode( $return ));    
    break;
    case "newFormat":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $result = $ev -> newFormat( $db_pdo, $_POST["name"], $_POST["background"], $_POST["font"]);
                            $return -> newId = $result -> newId;
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));    
    break;
    case "deleteFormat":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> formatId = $_POST["id"];
                            $return -> result = $ev -> deleteFormat( $db_pdo, $_POST["id"]);
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            print_r( json_encode( $return ));    
    break;
*/
/*    
case "savePlace":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();                            
                            $return -> result = $ev -> savePlace( $db_pdo, $_POST["id"], $_POST["place"]);
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            //$return -> data = $return -> data;
                            print_r( json_encode( $return ));    
    break;
    case "newPlace":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();                            
                            $return -> result = $ev -> newPlace( $db_pdo, $_POST["place"]);
                            $return -> newId = $return -> result -> newId;
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            //$return -> data = $return -> data;
                            print_r( json_encode( $return ));    
    
    
    break;
    case "deletePlace":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $return -> placeId = $_POST["id"];
                            $return -> result = $ev -> deletePlace( $db_pdo, $_POST["id"]);
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            //$return -> data = $return -> data;
                            print_r( json_encode( $return ));    
    break;
*/    
/*
    case "checkForFile":
                            if( file_exists( "../../" . $_POST["fileName"] ) ) {
                                $return -> success = true;
                                $return -> message = 'Die Datei "' . $_POST["fileName"] . '" existiert.';
                            } else {
                                $return -> success = false;
                                $return -> message = 'Die Datei "' . $_POST["fileName"] . '" existiert nicht.';                                
                            }
                            $return -> param = $_POST["param"];
                            $return -> filename = $_POST["fileName"];
                            print_r( json_encode( $return ));                               
    break;
*/
/*
    case "exportEvents":
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $dates = new \stdClass();
                            switch( $_POST["zeitraum"] ) {
                                case "all":
                                            $dates -> from_date = date("Y-m-d", time() );     
                                break;
                                case "currWeek":
                                            $dates -> from_date = date("Y-m-d", time()-((date("N")-1)*86400));
                                            $dates -> to_date = date("Y-m-d", time()+((8-date("N"))*86400));    
                                break;
                                case "nextWeek":
                                            $dates -> from_date = date("Y-m-d", time()-((date("N")-1)*86400) );
                                            $dates -> from_date = date("Y-m-d", strtotime($dates -> from_date . '+ 7 days'));

                                            $dates -> to_date = date("Y-m-d", time()+((8-date("N"))*86400));
                                            $dates -> to_date = date("Y-m-d", strtotime($dates -> to_date . '+ 7 days'));    
                                break;
                                case "currMonth":
                                            $dates -> from_date = date("m", time() );
                                            $dates -> from_date = date("Y", time() ) . "-" . $dates -> from_date . "-01";
                                            
                                            $dates -> to_date = date("m", time() );
                                            $tmp = "0" . ( intval( $dates -> to_date ) + 1 );
                                            $tmp = substr( $tmp, strlen( $tmp ) - 2, 2 );
                                            $dates -> to_date = date("Y", time() ) . "-" . $tmp . "-01";
                                            
                                break;
                                case "nextMonth":
                                            $dates -> from_date = date( "m", time() );
                                            $tmp =  "0" . ( intval( $dates -> from_date ) + 1 );
                                            $tmp = substr( $tmp, strlen( $tmp ) - 2, 2 );
                                            $dates -> from_date = date("Y", time() ) . "-" . $tmp . "-01";
                                            
                                            $dates -> to_date = date( "m", time() );
                                            $tmp =  "0" . ( intval( $dates -> to_date ) + 2 );
                                            $tmp = substr( $tmp, strlen( $tmp ) - 2, 2 );
                                            $dates -> to_date = date("Y", time() ) . "-" . $tmp . "-01";
                                break;
                            }
                            $whereStr = "";
                            if( is_string( $_POST["art"] ) ) {
                                $_POST["art"] = explode( ",", $_POST["art"] );    
                            }
                            for( $i = 0; $i < count( $_POST["art"] ); $i++ ) {
                                switch( $_POST["art"][$i] ) {
                                    case "fc-2":
                                            $whereStr .= " OR class = 'fc-2'";
                                    break;
                                    case "fc-3":
                                            $whereStr .= " OR class = 'fc-3'";
                                    break;
                                    case "fc-4":
                                            $whereStr .= " OR class = 'fc-4'";
                                    break;
                                    case "fc-5":
                                            $whereStr .= " OR class = 'fc-5'";
                                    break;
                                    case "fc-6":
                                            $whereStr .= " OR class = 'fc-6'";
                                    break;
                                    case "fc-7":
                                            $whereStr .= " OR class = 'fc-7'";
                                    break;
                                    case "fc-8":
                                            $whereStr .= " OR class = 'fc-8'";
                                    break;
                                }
                            }
                            if( $whereStr != "" ) {
                                $whereStr = substr( $whereStr, 4 );
                                $whereStr = " AND ( " . $whereStr . ")";
                            }
                            if( $_POST["ownEvs"] != "all" ) {
                                $whereStr .= " AND event.id = event_participate.event_id AND user_id = " . $_SESSION["user_id"];
                                if( !isset( $dates  -> to_date ) ) {
                                    $query = "SELECT * FROM event, event_participate WHERE start_date >= '" . $dates -> from_date . "' $whereStr";
                                } else {
                                    $query = "SELECT * FROM event, event_participate WHERE start_date >= '" . $dates -> from_date . "' AND start_date <'" . $dates -> to_date . "' AND end_date <'" . $dates -> to_date . "' $whereStr";
                                }
                            } else {
                                if( !isset( $dates  -> to_date ) ) {
                                    $query = "SELECT * FROM event WHERE start_date >= '" . $dates -> from_date . "' $whereStr";
                                } else {
                                    $query = "SELECT * FROM event WHERE start_date >= '" . $dates -> from_date . "' AND start_date <'" . $dates -> to_date . "' AND end_date <'" . $dates -> to_date . "' $whereStr";
                                }                                
                            }
                            $stm = $db_pdo -> query( $query );
                            $data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $result = new \stdClass();
                            $result -> data = $data;
                            $result -> success = true;
                            //$result = $ev -> getEvents( $db_pdo, $dates, $whereStr );
                            if( count( $result -> data ) != 0 ) {
                                if( $result -> success ) {
                                    $result = buildExportEventFile( $db_pdo, $result, $_POST["system"], $_POST["type"], $_POST["art"], $_SESSION["user_id"], $_POST["reminder"], $_POST["reminder_intervall"] );
                                } else {
                                    $result -> message = "Beim Lesen der Termine ist ein Fehler aufgetreten.";
                                }
                                $return -> success = $result -> success;
                                $return -> fileName = $result -> fileName;
                                $return -> message = $result -> message;
                            } else {
                                $return -> success = false;
                                $return -> message = "Für die gewählten Kriterien sind keine Termine vorhanden.";                                
                            }
                            print_r( json_encode( $return ));                                   
    break;
*/
/*    
    case "deleteEventAppendix":
                            require_once( "functions.php" );
                            $return -> eventType = $_POST["eventType"];
                            $tmpFileName = explode( "/", $_POST["appendix"] );
                            if( $tmpFileName[0] == "http:" || $tmpFileName[0] == "https:" ) {
                                $return -> success = true;
                                $return -> message = "Der Anhang wurde erfolgreich gelöscht.";
                            } else {
                                $l = count( $tmpFileName ) - 1;
                                $fileName = $tmpFileName[ $l ];
                                $tmpExt = explode( ".", $fileName );
                                $ext = $tmpExt[ count( $tmpExt ) - 1 ];
                                $i = 0;
                                $tmpPath = "";
                                while( $i < $l ) {
                                    $tmpPath .= "/" . $tmpFileName[ $i ];
                                    $i += 1;
                                }
                                if( $ext != "php" && $ext != "html" ) {
                                    $tmpPath = "../.." . $tmpPath . "/" . $fileName;
                                    $glob = glob( $tmpPath );
                                    if( count( $glob ) > 0 ) {
                                        unlink( $glob[0] );
                                    }
                                }
                                $return -> success = true;
                                $return -> message = "Der Anhang wurde erfolgreich gelöscht.";
                            }
                            print_r( json_encode( $return ));
    break;
    case "usePattern":
                            $query = "select * from event_pattern where id = " . $_POST["id"];
                            try {
                                $stm = $db_pdo -> query( $query );            
                                $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $return -> success = true;
                                $return -> message = "Die Vorlage wurde erfolgreich gelesen.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Vorlage ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                                                        
    break;
    case "saveAdminEvent":
                            if( $_POST["id"] == "new" ) {
                                $query = "INSERT INTO event (class, title, description, start_date, start_time, end_date, end_time, notice ) VALUES ('" .  $_POST["class"] . "', '" . $_POST["title"] . "', '" . $_POST["description"] . "', '" . $_POST["start_date"] . "', '" . $_POST["start_time"] . "', '" . $_POST["end_date"] . "', '" . $_POST["end_time"] . "', '" . $_POST["notice"] . "')";                                    
                            } else {
                                $query = "UPDATE event SET class = '" . $_POST["class"] . "', title = '" . $_POST["title"] . "', description = '" . $_POST["description"] . "', start_date = '" . $_POST["start_date"] . "', start_time = '" . $_POST["start_time"] . "', end_date = '" . $_POST["end_date"] . "', end_time = '" . $_POST["end_time"] . "', notice = '" . $_POST["notice"] . "' WHERE id = " . $_POST["id"];
                            }                            
                            try {
                                $db_pdo -> query( $query );
                                if( $_POST["id"] == "new" ) {
                                    $return -> Id = $db_pdo -> lastInsertId();
                                } else {
                                    $return -> Id = $_POST["id"];
                                }
                                $return -> success = true;
                                $return -> message = "Der Termin wurde erfolgreich gespeichert.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern des Termins ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                                                        
    break;
    case "sendAddUserToEvent":
                            $i = $_POST;
                            $query = "SELECT concat( firstname, ' ', lastname) as name, email, event_participate.event_id, event_participate.role_id, role.role FROM user, event_participate, role, WHERE event_participate.user_id = user.id AND event_participate.role_id = role.id AND event_participate.id = " . $_POST["Id"];
                            
                            $stm = $db_pdo -> query( $query );
                            $result_user = $stm -> fetchAll( PDO::FETCH_ASSOC );
                            
                            $query = "SELECT * FROM event WHERE id = " . $result_user[0]["event_id"];
                            $stm = $db_pdo -> query( $query );
                            $result_event = $stm -> fetchAll( PDO::FETCH_ASSOC );
                            
                            
                            print_r( json_encode( $return ));                                                        
    break;
    case "sendDelUserToEvent":
                            $i = $_POST;
                            print_r( json_encode( $return ));                                                        
    break;
    case "informUserAboutDeletion":
                            $return = informUserAboutDeletion( $db_pdo, $_POST["Id"] );                          
                            print_r( json_encode( $return ));                                                        
    break;
*/
// end calendar
// start message_news
    case "getMessageNewsContent":
                            if( $_POST["type"] == 1 ) {
                                require_once( "classes/Message.php" );
                                $m = new \Message();
                                $return -> data = $m -> getMessageContent( $db_pdo, 0, "ASC" );
                            } else {
                                require_once( "classes/News.php" );
                                $n = new \News();
                                $return -> data = $n -> getNewsContent( $db_pdo, 0, "ASC" );                                
                            }
                            print_r( json_encode( $return ));                               
    break;
    case "nextMessageNewsContent":
                            if( $_POST["type"] == 1 ) {
                                require_once( "classes/Message.php" );
                                $m = new \Message();
                                if( $_POST["isRead"] == "true" ) {
                                    $return -> isRead = $m -> setIsRead( $db_pdo, $_POST["id"] );
                                }
                                $return -> data = $m -> getMessageContent( $db_pdo, $_POST["dsPointer"], "ASC" );
                            } else {
                                require_once( "classes/News.php" );
                                $n = new \News();
                                if( $_POST["isRead"] == "true" ) {
                                    $return -> isRead = $n -> setIsRead( $db_pdo, $_POST["id"] );
                                }
                                $return -> data = $n -> getNewsContent( $db_pdo, $_POST["dsPointer"], "ASC" );                                
                            }
                            $return -> isRead = $_POST["isRead"];
                            print_r( json_encode( $return ));                               
    break;
    case "prevMessageNewsContent":
                            if( $_POST["type"] == 1 ) {
                                require_once( "classes/Message.php" );
                                
                                $m = new \Message();
                                if( $_POST["isRead"] == "true" ) {
                                    $return -> isRead = $m -> setIsRead( $db_pdo, $_POST["id"] );
                                }
                                $return -> data = $m -> getMessageContent( $db_pdo, $_POST["dsPointer"], "DESC" );
                            } else {
                                require_once( "classes/News.php" );
                                $n = new \News();
                                if( $_POST["isRead"] == "true" ) {
                                    $return -> isRead = $n -> setIsRead( $db_pdo, $_POST["id"] );
                                }
                                $return -> data = $n -> getNewsContent( $db_pdo, $_POST["dsPointer"], "DESC" );                                
                            }
                            $return -> isRead = $_POST["isRead"];
                            print_r( json_encode( $return ));                               
    break;
// end message_news
// start admin_chats.php
    case "getAdminRoomsChats":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> getAdminRoomsChats( $db_pdo );
                            $return -> success = $return -> data -> success;
                            $return -> message = $return -> data -> message;
                            $return -> data = $return -> data -> data;
                            unset( $return -> data -> data );
                            print_r( json_encode( $return ));                               
    break;
    case "showBlacklist":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> showBlacklist( $db_pdo );
                            $return -> success = $return -> data -> success;
                            $return -> message = $return -> data -> message;
                            $return -> data_critical = $return -> data -> data_critical;
                            $return -> data = $return -> data -> data;
                            unset( $return -> data -> data );
                            print_r( json_encode( $return ));                               
    break;
    case "saveAdminBadword":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> saveAdminBadword( $db_pdo, $_POST["id"], $_POST["badword"], $_POST["critical"] );
                            $query = "SELECT * FROM chat_badwords_critical";
                            $stm = $db_pdo -> query( $query );            
                            $return -> data_critical = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $return -> success = $return -> data -> success;
                            $return -> message = $return -> data -> message;
                            $return -> id = $_POST["id"];
                            print_r( json_encode( $return ));                               
    break;
    case "deleteAdminBadword":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> deleteAdminBadword( $db_pdo, $_POST["id"] );
                            $return -> success = $return -> data -> success;
                            $return -> message = $return -> data -> message;
                            print_r( json_encode( $return ));                               
    break;
    case "getAdminChatContent":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> getAdminChatContent( $db_pdo );
                            $return -> success = $return -> data -> success;
                            $return -> message = $return -> data -> message;
                            $return -> data = $return -> data -> data;
                            unset( $return -> data -> data );
                            print_r( json_encode( $return ));                               
    break;
    case "refreshBadwords":
                            require_once( "functions_badword.php" );
                            $return -> badwords = setBadwordContent( $db_pdo );
                            print_r( json_encode( $return ));                               
    break;
    case "getSuspect":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> getSuspect( $db_pdo );
                            $return -> success = $return -> data-> success;
                            $return -> message = $return -> data -> message;
                            $return -> data = $return -> data -> data;
                            unset( $return -> data -> data );
                            print_r( json_encode( $return ));                               
    break;
    case "deleteAdminSuspect":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> deleteAdminSuspect( $db_pdo, $_POST["id"] );
                            $return -> success = $return -> data-> success;
                            $return -> message = $return -> data -> message;
                            unset( $return -> data -> data );
                            print_r( json_encode( $return ));                               
    break;
    case "showAdminSuspectDetails":
                            require_once( "classes/Chat.php" );
                            $c = new \Chat();
                            $return -> data = $c -> showAdminSuspectDetails( $db_pdo, $_POST["id"] );
                            $return -> success = $return -> data-> success;
                            $return -> message = $return -> data -> message;
                            $return -> userId = $return -> data -> userId;
                            $return -> suspectId = $_POST["id"];
                            $return -> data = $return -> data -> data;
                            unset( $return -> data -> data );
                            print_r( json_encode( $return ));                               
    break;
    case "updateAdminRooms":
                            $query = "SELECT chat_room.id, chat_room.name AS roomName, chat.name AS chatName FROM chat_room, chat WHERE chat_room.chat_id = chat.id";
                            try {
                                $stm = $db_pdo -> query( $query );            
                                $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $return -> success = true;
                                $return -> message = "Die RaumId's wurden erfolgreich gelesen.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der RaumId's ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                               
    break;
    case "setWarning":
                            require_once( "classes/Message.php" );
                            $m = new \Message();
                            $result = $m -> newMessage( $db_pdo, "Chatverwarnung", $_POST["content"], 0, 26, 0, $_POST["userId"] );
                            if( $result -> success ) {
                                $return -> success = true;
                                $return -> message = "Der Nutzer wurde erfolgreich verwarnt.";
                            } else {
                                $return -> success = false;
                                $return -> message = "Beim Verwarnen des Nutzers ist ein Fehler aufgetreten.";                                
                            }
                            print_r( json_encode( $return ));                               
    break;
    case "setAvoid":
                            $query = "UPDATE user SET avoid_chat = true WHERE id = " . $_POST["userId"];
                            try {
                                $db_pdo -> query( $query );            
                                $return -> success = true;
                                $return -> data = $result;
                                $return -> message = "Der Nutzer wurde erfolgreich gesperrt.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Sperren des Nutzers ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                               
    break;
    case "getAvoidUsers":
                            $query = "SELECT id, CONCAT( firstname, ' ', lastname ) AS name, avoid_chat FROM user WHERE avoid_chat = 1";
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $return -> success = true;
                                $return -> data = $result;
                                $return -> message = "Die Nutzer wurden erfolgreich gelesen.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der Nutzer ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                               
    break;
    case "removeAvoid":
                            $query = "UPDATE user SET avoid_chat = false WHERE id = " . $_POST["userId"];
                            try {
                                $db_pdo -> query( $query );            
                                $return -> success = true;
                                $return -> message = "Der Nutzer wurde erfolgreich entgesperrt.";                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Entsperren des Nutzers ist folgender Fehler aufgetreten: " . $e -> getMessage();
                            }
                            print_r( json_encode( $return ));                               
    break;
// end admin_chats.php
// admin_message.php
    case "updateAdminMessage":
                            require_once("classes/Message.php"); 
                            $m = new \Message();
                            $result = $m -> updateMessage( $db_pdo, $_POST["id"], $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"], 0 );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));                                                           
    break;
    case "updateShortAdminMessage":
                            require_once("classes/Message.php"); 
                            $m = new \Message();
                            $result = $m -> updateMessage( $db_pdo, $_POST["id"], $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"], $_POST["isRead"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));                                                           
    break;
    case "newAdminMessage":
                            require_once("classes/Message.php"); 
                            $m = new \Message();
                            $result = $m -> newMessage( $db_pdo, $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));
    break;
    case "newShortAdminMessage":
                            require_once("classes/Message.php"); 
                            $m = new \Message();
                            $result = $m -> newMessage( $db_pdo, $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
    case "deleteAdminMessage":
                            require_once("classes/Message.php"); 
                            $m = new \Message();
                            $result = $m -> deleteMessage( $db_pdo, $_POST["id"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
// end admin_message.php
// admin_news.php
    case "updateAdminNews":
                            require_once( "classes/News.php" );
                            $m = new \News();
                            $result = $m -> updateNews( $db_pdo, $_POST["id"], $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"], 0 );
                            $return -> success = $result -> success;
                            $return -> news = $result -> news;
                            print_r( json_encode( $return ));                                                           
    break;
    case "updateShortAdminNews":
                            require_once( "classes/News.php" );
                            $m = new \News();
                            $result = $m -> updateNews( $db_pdo, $_POST["id"], $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"], $_POST["isRead"] );
                            $return -> success = $result -> success;
                            $return -> news = $result -> news;
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));                                                           
    break;
    case "newAdminNews":
                            require_once( "classes/News.php" );
                            $m = new \News();
                            $result = $m -> newNews( $db_pdo, $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            print_r( json_encode( $return ));
    break;
    case "newShortAdminNews":
                            require_once( "classes/News.php" );
                            $m = new \News();
                            $result = $m -> newNews( $db_pdo, $_POST["title"], $_POST["content"], $_POST["fromRole"], $_POST["fromUser"], $_POST["toRole"], $_POST["toUser"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
    case "deleteAdminNews":
                            require_once( "classes/News.php" );
                            $m = new \News();
                            $result = $m -> deleteNews( $db_pdo, $_POST["id"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message;
                            $return -> page = $_POST["page"];
                            print_r( json_encode( $return ));
    break;
// end admin_news.php
    case "getRoleId":
                            $return -> roleId = $_SESSION["role_id"];
                            print_r( json_encode( $return ));
    break;
//
// start s_and_a
    case "getSAndACategories":
                            require_once( "classes/SAndA.php");
                            $s = new \SAndA();
                            $return = $s -> getSAndACategories( $db_pdo );
                            $return -> command = $_POST["command"];    
                            print_r( json_encode( $return ));
    break; 
    case "newEntry":
                            require_once( "classes/SAndA.php");
                            $s = new \SAndA();
                            $result = $s -> newEntry( $db_pdo, $_SESSION["user_id"], $_POST["toRole"], $_POST["creationDate"], $_POST["eventDate"], 
                                        $_POST["deadline"], $_POST["eventTime"], $_POST["eventEndTime"], $_POST["place"], $_POST["category"], 
                                        $_POST["image"], $_POST["title"], $_POST["description"], 
                                        $_POST["longDescription"], $_POST["link"], $_POST["linkText"], $_POST["appendix"], $settings["s_and_a"]["message_behavior"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message; 
                            print_r( json_encode( $return ));
    break;
    case "saveEntry":
                            require_once( "classes/SAndA.php");
                            $s = new \SAndA();
                            $result = $s -> saveEntry( $db_pdo, $_POST["id"], $_POST["toRole"], $_POST["creationDate"], $_POST["eventDate"], 
                                        $_POST["deadline"], $_POST["eventTime"], $_POST["eventEndTime"], $_POST["place"], $_POST["category"], 
                                        $_POST["image"], $_POST["title"], $_POST["description"], 
                                        $_POST["longDescription"], $_POST["link"], $_POST["linkText"], $_POST["appendix"],  $settings["s_and_a"]["message_behavior"] );
                            $return -> success = $result -> success;
                            $return -> message = $result -> message; 
                            print_r( json_encode( $return ));
    break;
    case "deleteEntry":
                            require_once( "classes/SAndA.php");
                            $s = new \SAndA();
                            $result = $s -> deleteEntry( $db_pdo, $_POST["id"], $settings["s_and_a"]["message_behavior"], $settings["calendar"]["message_behavior"] ); 
                            $return -> success = $result -> success;
                            $return -> message = $result -> message; 
                            print_r( json_encode( $return ));
    
    break;
    case "removePartSandA":
                            $return -> sAndAId = $_POST["sAndAId"];
                            $return -> eventId = $_POST["eventId"];
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $result = $ev -> deleteParticipation( $db_pdo, $_SESSION["user_id"], $_POST["eventId"], $settings["calendar"]["message_behavior"] );
                            $return -> success = true;
                            $return -> message = "Der Termin wurde erfolgreich abgesagt."; 
                            print_r( json_encode( $return ));
    break;
    case "takePartSandA":
                            $return -> sAndAId = $_POST["sAndAId"];
                            $return -> eventId = $_POST["eventId"];
                            require_once( "classes/CalendarEvent.php");
                            $ev = new \CalendarEvent();
                            $result = $ev -> participate( $db_pdo, $_SESSION["user_id"], $_POST["eventId"], $settings["calendar"]["message_behavior"], $_POST["remind_me"] );
                            $return -> success = true;
                            $return -> message = "Dem Termin wurde erfolgreich zugesagt."; 
                            print_r( json_encode( $return ));   
    break;
    case "getSAndAEvById":
                            $return -> sAndAId = $_POST["sAndAId"];
                            $query = "SELECT s_and_a.*, CONCAT(firstname, ' ', lastname )  AS name, start_time, end_time, registration_deadline, event.id AS event_id, remind_me FROM s_and_a, user, event WHERE user_id = user.id AND event_id = event.id AND s_and_a.id = " . $_POST["sAndAId"];
                            $stm = $db_pdo -> query( $query );
                            $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $query = "SELECT * FROM event WHERE id = " . $return -> data[0]["event_id"];
                            $stm = $db_pdo -> query( $query );
                            $return -> data_event = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $return -> data[0]["place"] = $return -> data_event[0]["place"]; 
                            $return -> data[0]["deadline"] = $return -> data_event[0]["registration_deadline"]; 
                            $return -> success = true;
                            $return -> message = "Der Termin wurde erfolgreich geladen."; 
                            print_r( json_encode( $return ));
    
    break;
    case "getAnswers":
                            $query = "SELECT s_and_a_answer.*, CONCAT( firstname, ' ', lastname ) AS name FROM s_and_a_answer, user WHERE to_answer_id = 0 AND user.id = user_id AND s_and_a_id = " . $_POST["sAndAId"] . " ORDER BY to_answer_id";
                            $stm = $db_pdo -> query( $query );
                            $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $l = count( $return -> data );
                            $i = 0;
                            while( $i < $l ) {
                                $query = "SELECT s_and_a_answer.*, CONCAT( firstname, ' ', lastname ) AS name FROM s_and_a_answer, user WHERE user.id = user_id AND to_answer_id = " . $return -> data[$i]["id"] . " ORDER BY id";
                                $stm = $db_pdo -> query( $query );
                                $return -> data[$i]["backanswer"] = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $i += 1;
                            }
                            $return -> sAndAId = $_POST["sAndAId"];
                            print_r( json_encode( $return ));                            
    break;
    case "newSAndAAnswer":
                            $query = "INSERT INTO `s_and_a_answer` (`s_and_a_id`, `to_answer_id`, `user_id`, `content`) VALUES (" . $_POST["sAndAId"] . ", " . $_POST["to_answer_id"] . ", " . $_SESSION["user_id"] . ", '" . $_POST["content"]  . "')";
                            try {
                                $db_pdo -> query( $query );
                                $return -> newAnswerId = $db_pdo -> lastInsertId();
                                $return -> sAndAId = $_POST["sAndAId"];
                                $result = informUserAboutSAndAAnswer( $db_pdo, $_POST["sAndAId"], $return -> newAnswerId, $_SESSION["user_id"], $settings["s_and_a"]["message_behavior"] );
                                $return -> success = true;
                                $return -> message = "Die Antwort wurde erfolgreich gespeichert."; 
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Antwort ist folgender Fehler aufgetreten: ." . $e -> getMessage();    
                            }
                            print_r( json_encode( $return ) );
    break;
    case "sAndARemindMe":
                            $query = "UPDATE event_participate SET remind_me = " . $_POST["remind_me"] . " WHERE event_id = "  . $_POST["eventId"] . " AND user_id = " . $_SESSION["user_id"];
                            try {
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Die Erinnerung wurde erfolgreich gespeichert."; 
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Erinnerung ist folgender Fehler aufgetreten: ." . $e -> getMessage(); 
                                    
                            }
                            print_r( json_encode( $return ) );
    break;
    case "sendNewBackanswer":
                            $query = "INSERT INTO `s_and_a_answer` (`s_and_a_id`, `to_answer_id`, `user_id`, `content`) VALUES ( " . $_POST["sAndAId"] . ", " . $_POST["answerId"] . ", " . $_SESSION["user_id"] . ", '" . $_POST["content"] . "')";
                            try {
                                $db_pdo -> query( $query );
                                $return -> newAnswerId = $db_pdo -> lastInsertId();
                                $return -> bSAndAId = $_POST["sAndAId"];
                                $result = informUserAboutSAndABackanswer( $db_pdo, $_POST["sAndAId"], $return -> newAnswerId, $_SESSION["user_id"], $settings["s_and_a"]["message_behavior"] );
                                $return -> success = true;
                                $return -> message = "Die Rückantwort wurde erfolgreich gespeichert."; 
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Rückantwort ist folgender Fehler aufgetreten: ." . $e -> getMessage();                                   
                            }
                            print_r( json_encode( $return ) );
                    
    break;
    case "showSAndAParticipants":
                            require_once( "classes/CalendarEvent.php" );
                            $ev = new \CalendarEvent();
                            $return -> data = $ev -> getParticipants( $db_pdo, $_POST["eventId"] );
                            $return -> success = $return -> data -> success;
                            $return -> message = $return -> data -> message;
                            $return -> data = $return -> data -> data;
                            print_r( json_encode( $return ) );
    break;
    case "saveCat":
                            $query = "UPDATE `s_and_a_category` SET `title`= '" . $_POST["title"] . "' WHERE id = " . $_POST["catId"];
                            try {
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Die Kategorie wurde erfolgreich gespeichert."; 
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Kategorie ist folgender Fehler aufgetreten: ." . $e -> getMessage();                                   
                            }
                            print_r( json_encode( $return ) );   
    break;                  
    case "deleteCat":
                            $query = "DELETE FROM `s_and_a_category` WHERE id = " . $_POST["catId"];
                            try {
                                $db_pdo -> query( $query );
                                $glob = glob( "../images/s_and_a/category_" . $_POST["catId"] . ".*" );
                                unlink( $glob[0] );
                                $return -> success = true;
                                $return -> message = "Die Kategorie wurde erfolgreich gelöscht."; 
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen der Kategorie ist folgender Fehler aufgetreten: ." . $e -> getMessage();                                   
                            }
                            print_r( json_encode( $return ) );   
    break;                  
    case "saveNewCat":
                            $query = "INSERT INTO `s_and_a_category` (`title`) VALUES ( '" . $_POST["title"] . "')";
                            try {
                                $db_pdo -> query( $query );
                                $newCat = $db_pdo -> lastInsertId();
                                $tmpExt = explode( ".", $_POST["imgSource"] );
                                $ext = $tmpExt[1];
                                $query = "UPDATE `s_and_a_category` SET image = 'library/images/s_and_a/category_" . $newCat . ".$ext' WHERE id = " . $newCat;
                                $db_pdo -> query( $query );
                                rename( "../images/s_and_a/" . $_POST["imgSource"], "../images/s_and_a/category_" . $newCat . ".$ext" );
                                $return -> catId = $newCat;
                                $return -> success = true;
                                $return -> message = "Die Kategorie wurde erfolgreich gespeichert."; 
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern der Kategorie ist folgender Fehler aufgetreten: ." . $e -> getMessage();                                   
                            }
                            print_r( json_encode( $return ) );   
    break;
    case "renameChangeCatImage":
                            $fname = $_POST["fileName"];
                            $tmpExt = explode( ".", $fname );
                            $return -> ext = $tmpExt[ 1 ];
                            $return -> fileName = "library/images/s_and_a/category_" . $_POST["category"] . "." . $return -> ext;
                            rename( "../images/s_and_a/$fname", "../images/s_and_a/category_" . $_POST["category"] . "." . $return -> ext );                            
                            print_r( json_encode( $return ) );   
    break;
    case "uploadAppendix":
                            $fname = $_POST["fileName"];
                            $tmpExt = explode( ".", $fname );
                            $return -> ext = $tmpExt[ 1 ];
                            rename( "../images/s_and_a/$fname", "../images/s_and_a/appendix_" . $_POST["category"] . "." . $return -> ext );
                            file_exists( "../documents/" );
                            $return -> fileName = "../documents/s_and_a/appendix_new_" . time() . "." . $return -> ext;
                            copy( "../images/s_and_a/appendix_new." . $return -> ext, $return -> fileName );
                            unlink( "../images/s_and_a/appendix_new." . $return -> ext );
                            $return -> fileName = str_replace( "../documents", "library/documents", $return -> fileName );
                            print_r( json_encode( $return ) );   
    break;
    case "deleteAppendix":
                            try {
                                unlink( "../../" . $_POST["fileName"] );
                                $return -> success = true;
                                $return -> message = "Der Anhang wurde erfolgreich gelöscht.";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen des Anhangs ist folgender Fehler aufgetreten: " . $e -> getMessage();       
                            }
                            print_r( json_encode( $return ) );   
    break;
// end s_and_a
// start admin_search    
    case "newAdminSearch":
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> newShort( $_POST["type"], $_POST["page"], $_POST["title"], $_POST["description"], $_POST["keywords"], $_POST["fullText"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            unset( $return -> result );
                            print_r( json_encode( $return ) );   
    break;
    case "saveAdminSearch":
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> saveShort(  $_POST["id"], $_POST["type"], $_POST["page"], $_POST["title"], $_POST["description"], $_POST["keywords"], $_POST["fullText"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            unset( $return -> result );
                            print_r( json_encode( $return ) );   
    break;
    case "saveDetailAdminSearch":
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> saveDetail(  $_POST["id"], $_POST["type"], $_POST["url"], $_POST["title"], $_POST["description"], $_POST["keywords"], $_POST["full_text"], $_POST["accounts"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            unset( $return -> result );
                            print_r( json_encode( $return ) );   
    break;
    case "getAdminSearchRecordById":
                            $query = "SELECT * FROM search WHERE id = " . $_POST["id"];
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> getPageById( $_POST["id"] );
                            $return -> data = $return -> result -> data;
                            $return -> role = $return -> result -> role -> role; 
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message; 
                            unset( $return -> result );
                            print_r( json_encode( $return ) );   
                            
    break;
    case "deleteAdminSearch":
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> deleteRecord( $_POST["id"] );    
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            print_r( json_encode( $return ) );   
    break;
    case "getAdminSearchRoles":
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> getAccount( $_POST["id"] );
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message; 
                            $return -> data = $return -> result -> role;
                            $return -> Id = $_POST["id"];
                            unset( $return -> result );
                            print_r( json_encode( $return ) );   
    break;
    case "saveAdminSearchRoles":
    
                            require_once( "classes/AdminSearch.php" );
                            $as = new \AdminSearch( $db_pdo );
                            $return -> result = $as -> newAccount( $_POST["id"], $_POST["accounts"], $save = true );    
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message;
                            print_r( json_encode( $return ) );   
    break;
// end admin search
// start admin_sql
    case "sendAdminSql":                           

                            try {
                                $tmp = explode( ";", $_POST["sql"] );
                                $l = count( $tmp );
                                $i = 0;
                                while ( $i < $l ){
                                    $tmp[$i] = str_replace( "\'", '"', $tmp[$i] );
                                    if( $tmp[$i] != "" ) {
                                        $db_pdo -> query( $tmp[$i] );
                                    }
                                    $return -> success = true;
                                    $return -> message = "Anfrage wurde erfolgreich bearbeitet.";       
                                
                                    $i += 1;
                                }
                            } catch (Exception $e) {
                                $return -> success = false;
                                $return -> eCode = $e -> getCode();
                                $return -> message = "Beim Bearbeiten der Anfrage ist folgender Fehler aufgetreten: " . $e -> getMessage();        
                                
                            }
                            print_r( json_encode( $return ));
    break;
    case "getTableDef":     
                            require_once( "classes/DataForm.php" );
                            $return -> table = $_POST["table"];
                            $df = new \DataForm( $db_pdo, "SELECT * FROM " . $_POST["table"] );
                            $return -> html = $df -> getDescribeTableHtml( $_POST["table"] );
                            print_r( json_encode( $return ));    
    break;
// end admin_sql
// start abstinence card
    case "saveCard":     
                            try {
                                $query = "UPDATE `abstinenzcard_user` SET `name` = '" . $_POST["ap_0"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 1";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_user` SET `name` = '" . $_POST["ap_1"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 2";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_user` SET `name` = '" . $_POST["ap_2"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 3";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_reason` SET `reason` = '" . $_POST["reason_0"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 1 AND order_number = 1";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_reason` SET `reason` = '" . $_POST["reason_1"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 1 AND order_number = 2";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_reason` SET `reason` = '" . $_POST["reason_2"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 1 AND order_number = 3";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_reason` SET `reason` = '" . $_POST["reason_3"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 2 AND order_number = 1";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_reason` SET `reason` = '" . $_POST["reason_4"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 2 AND order_number = 2";
                                $db_pdo -> query( $query );
                                $query = "UPDATE `abstinenzcard_reason` SET `reason` = '" . $_POST["reason_5"] . "' WHERE user_id = " . $_SESSION["user_id"] . " AND type = 2 AND order_number = 3";
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Das Speichern der Abstinenzkarte war erfolgreich.";                             
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Speichern des Abstinenzkarte ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }        
                            print_r( json_encode( $return ));    
    break;
    case "deleteCard":     
                            try {
                                $query = "DELETE FROM `abstinence_card` WHERE user_id = " . $_SESSION["user_id"];
                                $db_pdo -> query( $query );
                                $query = "DELETE FROM `abstinenzcard_user` WHERE user_id = " . $_SESSION["user_id"];
                                $db_pdo -> query( $query );
                                $query = "DELETE FROM  `abstinenzcard_reason` WHERE user_id = " . $_SESSION["user_id"];
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Das Löschen der Abstinenzkarte war erfolgreich.";                             
                            } catch( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen des Abstinenzkarte ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
                            }        
                            print_r( json_encode( $return ));    
    break;
// end abstinence card
// start admin settings
    case "saveSettings":

                            $result =  str_replace( "\\\\", "\\", $_POST["json"] );
                            $result =  str_replace( "\'", "'", $result );
                            $tmp = stripslashes( $_POST["json"] );
                            $s = json_decode( $tmp );
                            $i = 0;
                            $l = count( $s );
                            $i = 0;
                            if( $_POST["createBackup"] ) {
                                $tmp =  explode( ".", $_POST["file"] );
                                $l = count( $tmp ) - 1;
                                $i = 0;
                                $fn = "";
                                while ( $i < $l ){
                                    $fn .= $tmp[ $i ] . ".";
                                    $i += 1;
                                }
                                $fn .= "bak";
                                if( file_exists( ROOT . $_POST["path"] . $fn ) ) {
                                    unlink( ROOT . $_POST["path"] . $fn );
                                }
                                copy( ROOT . $_POST["path"] . $_POST["file"],  ROOT . $_POST["path"] . $fn );
                                
                            }
                            $fh = fopen( ROOT . $_POST["path"] . $_POST["file"], "w+" );
                            $l = count( $s );
                            $i = 0;
                            while ( $i < $l ){
                                fwrite( $fh, "[" . $s[$i] -> name . "]\ndescription_" . $s[$i] -> name . "="  . $s[$i] -> description . "\n" );
                                $m = count( $s[$i] -> values );
                                $j = 0;
                                while ( $j < $m ){
                                    fwrite( $fh, "description_" . $s[$i] -> values[$j] -> name . "="  . $s[$i] -> values[$j] -> description . "\n" . $s[$i] -> values[$j] -> name . "=" . $s[$i] -> values[$j] -> value . "\n" );
                                    
                                    $j += 1;
                                }
                                $i += 1;
                            } 
                               
          
                            print_r( json_encode( $return ));
    break;
/* start admin_user */
    case "restoreSettings":
                            $tmp =  explode( ".", $_POST["file"] );
                            $l = count( $tmp ) - 1;
                            $i = 0;
                            $fn = "";
                            while ( $i < $l ){
                                $fn .= $tmp[ $i ] . ".";
                                $i += 1;
                            }
                            $fn .= "bak";
                            if( file_exists( ROOT . $_POST["path"] . $_POST["file"] ) ) {
                                unlink( ROOT . $_POST["path"] . $_POST["file"] );
                                copy( ROOT . $_POST["path"] . $fn,  ROOT . $_POST["path"] . $_POST["file"] );
                                $return -> success = true;
                                $return -> message = "Die Backupdatei wurde erfolgreich wiederhergestellt.";
                            } else {
                                $return -> success = false;
                                $return -> message = "Es ist keine Backupdatei vorhanden!";
                            }
                            copy( ROOT . $_POST["path"] . $_POST["file"],  ROOT . $_POST["path"] . $fn );
                            print_r( json_encode( $return ));
                            
    break;
    case "setPasswordNewAccount":
                            
                            $return -> targetId = $_POST["targetId"];
                            $return -> newPassword = getRandomPassword();
                            print_r( json_encode( $return ));

    break;
// end admin settings
// start admin messages news
    case "refreshMessageInfo":
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            //$mail = new \PHPMailer\PHPMailer\PHPMailer();
                                
                            $query = "SELECT * FROM " . $_POST["table"] . " WHERE id = " . $_POST["id"];
                            try {
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                if( $result[0]["to_role"] == 0 ) {
                                    // only one user
                                    $query_user = "SELECT to_user, concat(firstname, ' ', lastname) as username, email FROM " . $_POST["table"] . "_user, user WHERE " . $_POST["table"] . "_user.to_user = user.id AND user.opt_in = 1 AND from_" . $_POST["table"] . " = " . $result[0]["to_user"];
                                    
                                    
                                } else {
                                    // to role
                                    $query_user = "SELECT user.id, concat(firstname, ' ', lastname) as username, email FROM user, account, " . $_POST["table"] . " WHERE user.id = account.user_id AND account.role_id = " . $_POST["table"] . ".to_role AND user.opt_in = 1 AND " . $_POST["table"] . ".id = " . $_POST["id"];
                                }
                                $stm_email = $db_pdo -> query( $query_user );
                                $result_email = $stm_email -> fetchAll(PDO::FETCH_ASSOC);
                                $l = count( $result_email );
                                $i = 0;
                                while ( $i < $l ){
                                    $mail = new \PHPMailer\PHPMailer\PHPMailer();
                                    $mail -> addAddress( $result_email[$i].email, $result_email[$i].username );
                                    $mail->CharSet = "UTF-8";
                                    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Meldungs-/News-E-Mail „Regenbogen”");

                                    $mail->Subject = 'Meldungs-/News-E-Mail Suchtselbsthilfe „Regenbogen”';

                                    $mail->isHtml(true);
                                     $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                                    if( $_POST["table"] == "message" ) {
                                        $iType = "Meldung";
                                    } else {
                                        $iType = "News";
                                    }
                                    $content = "<img src='cid:TBP' alt='Logo' style='width: 150px'>";
                                    $content .= "
                                    <h3>Neue oder geänderte $iType</h3>
                                    <p>
                                        Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                                    </p>
                                    <h4>$iType</h4>
                                    <p>Titel: " . $result[0]["title"] . "</p>
                                    <p>Inhalt: " . $result[0]["content"] . "</p>
                                    <p>&nbsp;</p>
                                    <p>Dein Suchtselbsthilfe-„Regenbogen”-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt>
                                            <dd>Demmeringstr. 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                                    " . getEmailSignature();
                                    $mail -> Body = $content;
                                    $mail -> Send();                            
                                    $i += 1;
                                }
                                $return -> success = true;
                                $return -> message = "Das Neuversenden der E-Mails war erfolgreich.";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Neuversenden der E-Mails ist folgender Fehler aufgetreten: " .  $e -> getMessage();
                            }
                            print_r( json_encode( $return ));
    break;
    case "afterNewMessageNews":
                            // set valid_to
                            $settings = parse_ini_file('../../ini/settings.ini', TRUE);
                            $tmpDays = $settings["admin_messages_news"]["diff_max_valid_to_days"];
                            $timestamp = time();
                            $date = strtotime("+$tmpDays day", $timestamp);
                            $valid_to = date('Y-m-d', $date);
                            $query = "UPDATE " . $_POST["table"] . " SET valid_to = '$valid_to' WHERE id = " . $_POST["id"] . ";";
                            $db_pdo -> query( $query );
                            //                                                     
                            $query = "SELECT * FROM " . $_POST["table"] . " WHERE id = " .  $_POST["id"];
                            $stm = $db_pdo -> query( $query );
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $title = $result[0]["title"];
                            $content = $result[0]["content"];
                            if( $result[0]["to_role"] == 0 ) {
                                // one user
                                $query_insert = "INSERT INTO " . $_POST["table"] . "_user ( `from_" . $_POST["table"] . "`, `to_user`) VALUES ( " . $_POST["id"] . ", " . $result[0]["to_user"] . ")";
                                $db_pdo -> query( $query_insert );
                                $return -> mail = sendNewsMessageEmail( $db_pdo, $result[0]["to_user"], $_POST["id"], $_POST["table"], $title, $content );
                            } else {
                                $query_users = "SELECT user.id FROM user, account WHERE user.id = account.user_id AND role_id = " . $result[0]["to_role"];
                                $stm = $db_pdo -> query( $query_users );
                                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                                $l = count( $result );
                                $i = 0;
                                while ( $i < $l ){
                                    $query_insert = "INSERT INTO " . $_POST["table"] . "_user ( `from_" . $_POST["table"] . "`, `to_user`) VALUES ( " . $_POST["id"] . ", " . $result[$i]["id"] . ")";
                                    $db_pdo -> query( $query_insert );
                                    $return -> mail = sendNewsMessageEmail( $db_pdo, $result[$i]["id"], $_POST["id"], $_POST["table"], $title, $content );

                                    $i += 1;
                                }                                    
                            }
                            $return -> dVar = $_POST["dVar"];                           
                            print_r( json_encode( $return ));
    break;
    case "resendInfoMessNews":
                            $query = "SELECT user.id, concat( firstname, ' ', lastname ) AS name, email, to_user FROM " . $_POST["table"] . "_user, user WHERE user.id = to_user AND user.opt_in = 1 AND from_" . $_POST["table"] . " = " . $_POST["id"];
                            $stm = $db_pdo -> query( $query );
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $l = count( $result );
                            $i = 0;
                            while ( $i < $l ){
                                sendNewsMessageEmail( $db_pdo, $result[$i]["to_user"], $_POST["id"], $_POST["table"], $_POST["title"], $_POST["content"] );                            
                                $i += 1;
                            }
                            $return -> success = true;
                            $return -> message = "Die E-Mails wurden erfolgreich neu versandt.";
                            print_r( json_encode( $return ));
                                  
    break;
    case "deleteOrphans":
                            $query = "DELETE FROM " . $_POST["table"] . "_user WHERE from_" . $_POST["table"] . " = " . $_POST["id"];
                            try {
                                $db_pdo -> query( $query );
                                $return -> success = true;
                                $return -> message = "Die Nutzer wurden erfolgreich gelöscht.";
                                
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Löschen der Nutzer ist folgender Fehler aufgetreten: " . $e -> getMessage();
                                
                            }
                            print_r( json_encode( $return ));
                            
    break;
// end admin messages news
// start geolocation index extended info
    case "getSBBForPerimeter":
                            switch( $_POST["currentTab"] ) {
                                case "0":
                                case "2":
                                case "4":
                                case "6":
                                    $query = "SELECT id, title, lat, lon from sbb where type = 1 AND GoogleDistance_KM(lat, lon, " . $_POST["latitude"] . ", " . $_POST["longitude"] . ") " . $_POST["distance"];
                                    
                                break;
                                case "1":
                                    $query = "SELECT DISTINCT id, title, lat, lon from sbb where type = 4 AND GoogleDistance_KM(lat, lon, " . $_POST["latitude"] . ", " . $_POST["longitude"] . ") " . $_POST["distance"];
                                
                                break;
                            }
                            //$query = "SELECT id, title, lat, lon from sbb where type = 1 AND GoogleDistance_KM(lat, lon, " . $_POST["latitude"] . ", " . $_POST["longitude"] . ") " . $_POST["distance"];
                            try {
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                $l = count( $return -> data );
                                $i = 0;
                                while ( $i < $l ){
                                    $return -> data[$i]["distance"] = getDistanceBetweenPointsNew($_POST["latitude"], $_POST["longitude"], $return -> data[$i]["lat"], $return -> data[$i]["lon"] );
                                    $query_opnv = "SELECT id, street, lat, lon from opnv_stops where GoogleDistance_KM(lat, lon, " . $return -> data[$i]["lat"] . ", " . $return -> data[$i]["lon"] . ") < 0.5";
                                    $stm_opnv = $db_pdo -> query( $query_opnv );
                                    $return -> data[$i]["opnv"] = $stm_opnv -> fetchAll( PDO::FETCH_ASSOC ); 
                                    $i += 1;
                                }
                                //usort($return -> data, fn($a, $b) => strcmp($a->distance, $b->distance));
                                $data=$return->data;
                                $sort_col =  array_column($data, 'distance');
                                array_multisort($sort_col, SORT_ASC, $data);              
                                $return -> data = $data;
                                $return -> success = true;
                                $return -> message = "Die SBB's wurden erfolgreich gelesen";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der SBB's ist folgender Fehler aufgetreten: " . $e -> getMessage();             
                            }
                            if( $_POST["currentTab"] == "6" ) $_POST["currentTab"] = "0";
                            $return -> tab = $_POST["currentTab"];
                            print_r( json_encode( $return ));    
    break;
    case "showEntryDetails":
                            $query = "select * from sbb where id = " . $_POST["id"];
                            try {
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                $query_opnv = "SELECT id, stop_name, lat, lon from opnv_stops where GoogleDistance_KM(lat, lon, " . $return -> data[0]["lat"] . ", " . $return -> data[0]["lon"] . ") < 0.3";
                                $stm_opnv = $db_pdo -> query( $query_opnv );
                                $return -> data[0]["opnv"] = $stm_opnv -> fetchAll( PDO::FETCH_ASSOC ); 
                                $return -> data[0]["google"] = "https://www.google.de/maps/place/" . $return -> data[0]["street"] . ",+" . $return -> data[0]["city"] . "/";
                                $return -> success = true;
                                $return -> message = "Der Eintrag wurde erfolgreich gelesen";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen des Eintrags ist folgender Fehler aufgetreten: " . $e -> getMessage();             
                            }
                            print_r( json_encode( $return ));    
    break;
    case "getSBBForCityPart":
                            try {
                                $query = "SELECT latitude, longitude FROM city_parts WHERE id = " . $_POST["cityPartId"];
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                switch( $_POST["currentTab"] ) {
                                    case "0":
                                    case "2":
                                    case "4":
                                    case "6":
                                        $query = "SELECT id, title, lat, lon from sbb where type = 1 AND GoogleDistance_KM(lat, lon, " . $result[0]["latitude"] . ", " . $result[0]["longitude"] . ") " . $_POST["distance"];
                                        
                                    break;
                                    case "1":
                                        $query = "SELECT id, title, lat, lon from sbb where type = 4 AND GoogleDistance_KM(lat, lon, " . $result[0]["latitude"] . ", " . $result[0]["longitude"] . ") " . $_POST["distance"];
                                    
                                    break;
                                }
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                $l = count( $return -> data );
                                $i = 0;
                                while ( $i < $l ){
                                        $return -> data[$i]["distance"] = getDistanceBetweenPointsNew($result[0]["latitude"], $result[0]["longitude"], $return -> data[$i]["lat"], $return -> data[$i]["lon"] );
                                        $query_opnv = "SELECT id, street, lat, lon from opnv_stops where GoogleDistance_KM(lat, lon, " . $return -> data[$i]["lat"] . ", " . $return -> data[$i]["lon"] . ") < 0.5";
                                        $stm_opnv = $db_pdo -> query( $query_opnv );
                                        $return -> data[$i]["opnv"] = $stm_opnv -> fetchAll( PDO::FETCH_ASSOC ); 
                                        $i += 1;
                                }
                                $return -> success = true;
                                $return -> message = "Die SBB's wurden erfolgreich gelesen";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der SBB's ist folgender Fehler aufgetreten: " . $e -> getMessage();             
                            }
                            if( $_POST["currentTab"] == "6" ) $_POST["currentTab"] = "0";
                            $return -> tab = $_POST["currentTab"];
                            print_r( json_encode( $return ));    
                            
    
    break;
    case "getSBBForPostalCode":
                            try {
                                $query = "SELECT latitude, longitude FROM city_postalcodes WHERE id = " . $_POST["cityPartId"];
                                $stm = $db_pdo -> query( $query );
                                $result = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                switch( $_POST["currentTab"] ) {
                                    case "0":
                                    case "2":
                                    case "4":
                                    case "6":
                                        $query = "SELECT id, title, lat, lon from sbb where type = 1 AND GoogleDistance_KM(lat, lon, " . $result[0]["latitude"] . ", " . $result[0]["longitude"] . ") " . $_POST["distance"];
                                        
                                    break;
                                    case "1":
                                        $query = "SELECT id, title, lat, lon from sbb where type = 4 AND GoogleDistance_KM(lat, lon, " . $result[0]["latitude"] . ", " . $result[0]["longitude"] . ") " . $_POST["distance"];
                                    
                                    break;
                                }
                                $stm = $db_pdo -> query( $query );
                                $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                $l = count( $return -> data );
                                $i = 0;
                                while ( $i < $l ){
                                        $return -> data[$i]["distance"] = getDistanceBetweenPointsNew($result[0]["latitude"], $result[0]["longitude"], $return -> data[$i]["lat"], $return -> data[$i]["lon"] );
                                        $query_opnv = "SELECT id, street, lat, lon from opnv_stops where GoogleDistance_KM(lat, lon, " . $return -> data[$i]["lat"] . ", " . $return -> data[$i]["lon"] . ") < 0.5";
                                        $stm_opnv = $db_pdo -> query( $query_opnv );
                                        $return -> data[$i]["opnv"] = $stm_opnv -> fetchAll( PDO::FETCH_ASSOC ); 
                                        $i += 1;
                                }
                                $return -> success = true;
                                $return -> message = "Die SBB's wurden erfolgreich gelesen";
                            } catch ( Exception $e ) {
                                $return -> success = false;
                                $return -> message = "Beim Lesen der SBB's ist folgender Fehler aufgetreten: " . $e -> getMessage();             
                            }
                            if( $_POST["currentTab"] == "6" ) $_POST["currentTab"] = "0";
                            $return -> tab = $_POST["currentTab"];
                            print_r( json_encode( $return ));    
    
    break;
// end geo codes
// start reports
    case "getReport":
                            $countAe = 0;
                            switch( $_POST["type"] ) {
                                // wh refs for month
                                case "1":
                                    $return -> mailTo = [];
                                    $query = "SELECT event.id, start_date, start_time, notice FROM event WHERE notice <> '' AND category = 8 AND start_date " . str_replace( '\\',"", $_POST["dates"] . " ORDER BY start_date" );
                                    $stm = $db_pdo -> query( $query );
                                    $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                    $l = count( $return -> data );
                                    $i = 0;
                                    $k = 0;
                                    while ( $i < $l ){
                                        $return -> data[ $i ]["id"];
                                        $query_user = "SELECT user_id, role_id, role, concat(firstname, ' ',lastname) AS name, user.email FROM event_participate, user, role WHERE role_id = role.id AND event_id = " . $return -> data[ $i ]["id"] . " AND user_id = user.id ORDER BY role_id;";
                                        $stm_user = $db_pdo -> query( $query_user );
                                        $return -> data[ $i ]["participants"] = new \stdClass();
                                        $return -> data[ $i ]["participants"] = $stm_user -> fetchAll( PDO::FETCH_ASSOC );
                                        $i += 1;                                        
                                    }
                                break;
                                // wh invoice for month
                                case "2":
                                    $query = "SELECT event.id, start_date, start_time, notice FROM event WHERE notice <> '' AND category = 8 AND start_date " . str_replace( '\\',"", $_POST["dates"] . " ORDER BY start_date" );
                                    $stm = $db_pdo -> query( $query );
                                    $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                    $l = count( $return -> data );
                                    $i = 0;
                                    $k = 0;
                                    $countAe = 0;
                                    while ( $i < $l ){
                                        $tmp = explode( ";", $return -> data[$i]["notice"] );
                                        if( count( $tmp )  === 3 ) {
                                            $query_user = "SELECT user_id, '0,00€' as ae,'" . $_POST["rk"] . "€' as rk, role_id, role, concat(firstname, ' ',lastname) AS name, user.email FROM event_participate, user, role WHERE role_id = role.id AND event_id = " . $return -> data[ $i ]["id"] . " AND (role_id = 13 OR role_id = 14 OR role_id = 22 OR role_id = 24) AND user_id = user.id ORDER BY role_id;";
                                            $tmpNotice = explode( ";", $return -> data[$i]["notice"] );
                                            $m = count( $tmpNotice ) - 1;
                                            $j = 0;
                                            $t = "";
                                            while( $j < $m ) {
                                                $t .= $tmpNotice[$j] . ";";
                                                $j += 1;
                                            }
                                            $return -> data[$i]["notice"] = substr( $t, 0, strlen( $t ) - 1 );
                                            $stm_user = $db_pdo -> query( $query_user );
                                            $return -> data[ $i ]["participants"] = new \stdClass();
                                            $return -> data[ $i ]["participants"] = $stm_user -> fetchAll( PDO::FETCH_ASSOC );
                                            
                                            
                                        }
                                        if( count( $tmp )  === 4 ) {
                                            $query_user = "SELECT user_id, '0,00€' as ae,'" . $_POST["rk"] . "€' as rk, role_id, role, concat(firstname, ' ',lastname) AS name, user.email FROM event_participate, user, role WHERE role_id = role.id AND event_id = " . $return -> data[ $i ]["id"] . " AND (role_id = 13 OR role_id = 14 OR role_id = 22 OR role_id = 24) AND user_id = user.id ORDER BY role_id;";
                                            $tmpNotice = explode( ";", $return -> data[$i]["notice"] );
                                            $m = count( $tmpNotice ) - 2;
                                            $j = 0;
                                            $t = "";
                                            $tmpNotice[0] .= "<br><span style='color:red'>Absage am selben Tag</span>";
                                            while( $j < $m ) {
                                                $t .= $tmpNotice[$j] . ";";
                                                $j += 1;
                                            }
                                            $return -> data[$i]["notice"] = substr( $t, 0, strlen( $t ) - 1 );
                                            $stm_user = $db_pdo -> query( $query_user );
                                            $return -> data[ $i ]["participants"] = new \stdClass();
                                            $return -> data[ $i ]["participants"] = $stm_user -> fetchAll( PDO::FETCH_ASSOC );
                                            
                                            
                                        }
                                        if(count( $tmp ) < 3 ) {
                                            $query_user = "SELECT user_id, '" . $_POST["ae"] . "€' as ae,'" . $_POST["rk"] . "€' as rk, role_id, role, concat(firstname, ' ',lastname) AS name, user.email FROM event_participate, user, role WHERE role_id = role.id AND event_id = " . $return -> data[ $i ]["id"] . " AND (role_id = 13 OR role_id = 14 OR role_id = 22 OR role_id = 24) AND user_id = user.id ORDER BY role_id;";
                                            $stm_user = $db_pdo -> query( $query_user );
                                            $return -> data[ $i ]["participants"] = new \stdClass();
                                            $return -> data[ $i ]["participants"] = $stm_user -> fetchAll( PDO::FETCH_ASSOC );
                                            $countAe += count( $return -> data[ $i ]["participants"] );
                                        }
                                        $i += 1;
                                    }
                                    $ae = intval( $_POST["ae"] );
                                    
                                    //$l = count( $return -> data );
                                    //$i = 0;
                                    //$countAe = 0;
                                    /*
                                    while ( $i < $l ){
                                        $countAe += count( $return -> data[$i]["participants"] );
                                        $i += 1;
                                    }
                                    */
                                    //$q = "SELECT count( notice ) as count FROM event WHERE notice <> '' AND notice NOT LIKE '%;oa' AND class = 'fc-8' AND start_date " . str_replace( '\\',"", $_POST["dates"]  . " ORDER BY start_date" );
                                    //$s = $db_pdo -> query( $q );
                                    //$r = $s -> fetchAll( PDO::FETCH_ASSOC );
                                    //$countAe = $r[0]["count"];
                                    $rk = 0;
                                    $l = count( $return -> data );
                                    $i = 0;
                                    while( $i < $l ) {
                                        $rk += count( $return -> data[$i]["participants"] );
                                        $i += 1;
                                    }
                                    $rk = $rk *  intval( $_POST["rk"] ) . ",00€";
                                break;
                                // user for role
                                case "3":
                                    $query = "SELECT user.*, created_on FROM user, account WHERE account.user_id = user.id AND role_id = " . $_POST["role_id"] . " ORDER BY lastname";                         
                                    $stm = $db_pdo -> query( $query );
                                    $return -> data = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                break;
                                // send report user for role to myself
                                case "4":
                                    require_once( "getReports.php" );
                                    $query = "SELECT user.* FROM user, account WHERE account.user_id = user.id AND role_id = " . $_POST["role_id"] . " ORDER BY lastname";                               
                                    $stm = $db_pdo -> query( $query );
                                    $res = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                    getReportForUser( $db_pdo, $res );
                                    require_once "PHPMailer/PHPMailer/Exception.php";
                                    require_once "PHPMailer/PHPMailer/PHPMailer.php";
                                    $mail = new PHPMailer();
                                    $mail -> addAddress( $_SESSION["email"], $_SESSION["firstname"] . " " . $_SESSION["lastname"] );
                                    $mail->CharSet = "UTF-8";
                                    $mail->isHtml(true);
                                    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                                    $mail->Subject = 'Bericht Nutzer für Rolle';
                                    $mail->Body = "Hallo " . $_SESSION["firstname"] . ",<br><br>Du hast den Bericht 'Nutzer für Rolle' angefordert. Dieser befindet sich im Anhang.<br><br>Dein Projektteam";
                                    $mail->addAttachment("../../reports/user_for_role.html", "Nutzer_für_Rollen.html");
                                    $mail -> send();
                                break;
                                case "5":
                                    $_POST["query"] = str_replace( "\\'", "'", $_POST["query"] );
                                    $query = "SELECT id, CONCAT( lastname, ', ', firstname) AS name, user.email, opt_in, last_login, allow_ga, allow_tr, remind_me FROM user WHERE user.id > 0 AND " . $_POST["query"] . " ORDER BY lastname";
                                    $stm = $db_pdo -> query( $query );
                                    $res = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                    $l = count( $res );
                                    $i = 0;
                                    while ( $i < $l ){
                                        if( $res[$i]["opt_in"] == 1 ) {
                                            $res[$i]["opt_in"] = "classOn";
                                        } else {
                                            $res[$i]["opt_in"] = "classOff";
                                        }
                                        if( $res[$i]["allow_ga"] == 1 ) {
                                            $res[$i]["allow_ga"] = "classOn";
                                        } else {
                                            $res[$i]["allow_ga"] = "classOff";
                                        }
                                        if( $res[$i]["allow_tr"] == 1 ) {
                                            $res[$i]["allow_tr"] = "classOn";
                                        } else {
                                            $res[$i]["allow_tr"] = "classOff";
                                        }
                                        if( $res[$i]["remind_me"] == 1 ) {
                                            $res[$i]["remind_me"] = "classOn";
                                        } else {
                                            $res[$i]["remind_me"] = "classOff";
                                        }
                                        $query = "SELECT COUNT( id ) AS count_meessage FROM message_user WHERE to_user = " .  $res[$i]["id"];
                                        $stm = $db_pdo -> query( $query );
                                        $res_mess = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                        $res[$i]["count_message"] = $res_mess[0]["count_meessage"];
                                        $query = "SELECT COUNT( id ) AS count_news FROM news_user WHERE to_user = " .  $res[$i]["id"];
                                        $stm = $db_pdo -> query( $query );
                                        $res_mess = $stm -> fetchAll( PDO::FETCH_ASSOC );
                                        $res[$i]["count_news"] = $res_mess[0]["count_news"];
                                        $i += 1;
                                    }
                                    $return -> data = $res;                                   
                                break;
                                // report tracking pages
                                case "6":
                                
                                break;
                                // report tracking pages details action
                                case "7":
                                
                                break;
                                // report tracking pages details user
                                case "8":
                                
                                break;
                            }
                            if( isset( $_POST["dates"] ) ) {
                                $return -> dates = substr( str_replace( '\\',"", $_POST["dates"] ), 3, 10 );
                            }
                            $return -> countAe = $countAe;
                            if( isset( $_POST["ae"] ) ) $return -> AE = $return -> countAe * intval( $_POST["ae"] ) . ",00€"; 
                            if( !isset( $rk ) ) {
                                $return -> RK = 0;    
                            } else {
                                if( isset( $_POST["rk"] ) ) $return -> RK = $rk; 
                            }
                            $return -> type = $_POST["type"];
                            print_r( json_encode( $return ));    

    
    break;
// end reports
// start tracking
    case "track":
                            require_once( "classes/Tracking.php" );
                            $tr = new \Tracking( $db_pdo );
                            $return -> result = $tr -> setTrack( $_POST["type"], substr( $_POST["pathname"], 1, strlen( $_POST["pathname"] ) - 1  ), $_POST["currentTrackId"] );
                            $return -> id = $return -> result -> id;
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message; 
                            print_r( json_encode( $return ));
    
    break;
    case "setTrackAction":
                            require_once( "classes/Tracking.php" );
                            $t = new \Tracking( $db_pdo );
                            $return -> result = $t -> setTrackAction( $_POST["trackingPage"], $_POST["pathname"], $_POST["action"] );
                            $return -> id = $return -> result -> id;
                            $return -> success = $return -> result -> success;
                            $return -> message = $return -> result -> message; 
                            print_r( json_encode( $return ));    
    break;

// end tracking
//
/* checkLink for s_and_a*/
    case "checkLink":
                            require_once( "functions.php" );
                            $return -> param = $_POST["param"];
                            $return -> success = chkLinkExists( $_POST["link"] );
                            print_r( json_encode( $return ));                               
                            
    break;
/**/
    case "refresh_timeout":
                            $_SESSION['last_visit'] = time();
                            $return -> lastVisit = $_SESSION['last_visit'];
                            require_once( "classes/User.php" );
                            require_once( "classes/Message.php" );
                            require_once( "classes/News.php" );
                            $user = new \User( $db_pdo );
                            $message = new \Message();
                            $news = new \News();
                            if( isset( $_SESSION["user_id"])) {
                                $return -> data = $user -> setLastActivity( $db_pdo );
                                $return -> countMessages = $message -> getCountMessagesPerUser( $db_pdo, $_SESSION["user_id"] ) -> count_records;
                                $return -> countNews = $news -> getCountNewsPerUser($db_pdo, $_SESSION["user_id"] ) -> count_records;
                            }
                            print_r( json_encode( $return ));
    break;
    case "checkBrowser":    
                            require_once "PHPMailer/PHPMailer/Exception.php";
                            require_once "PHPMailer/PHPMailer/PHPMailer.php";
                            $mail = new PHPMailer();
                            $mail->CharSet = "UTF-8";
                            $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
                            $mail -> addAddress( "info@suchtselbsthilfe-regenbogen.de", "info@suchtselbsthilfe-regenbogen.de" );
                            $mail -> addAddress(  "easyit.leipzig@gmail.com", "easyit.leipzig"  );
                            $mail->Subject = 'Browsertest';
                            $mail->isHtml(true);
                            $mail->Body = $_POST["screenValues"]  . "<br>" . $_POST["deviceClass"] . "<br>" . $_POST["device"] . "<br>";
                            //  . $_POST["deviceClass"] . "<br>"
                            if( $mail->Send() ) {
                                $return -> success = true;
                                $return -> content = $mail->Body;    
                            } else {
                                $return -> success = false;
                            }
                            var_dump( $return );
                            print_r( json_encode( $return ));
    break;
    case "sendError":       
                            //sendErrorMail( $pdo, $_POST["pageError"] . $_POST["whereError"] . $_POST["descError"] );
                            require_once( "classes/InformUser.php");
                            $iu = new \InformUser( $db_pdo, "email", 29, 0, 0, 1 );
                            $title = 'Fehlermeldungs-E-Mail - Suchtselbsthilfe „Regenbogen”';
                            $iu -> sendUserInfo( $title, $title, $_POST["pageError"] . $_POST["whereError"] . $_POST["descError"], $_POST["pageError"] . $_POST["whereError"] . $_POST["descError"] );

                            print_r( json_encode( $return ));
    break;
    default:
                            print_r( json_encode( $return ));
    break;
}
?>
