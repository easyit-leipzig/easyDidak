<?php
function mailGmail( $fromUser, $fromName, $toUser, $toName, $subject, $title, $contentEmail, $isIntern = true, $attachments = [] ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $mail -> CharSet = "UTF-8";
    $mail -> setFrom( $fromUser, $fromName );
    $mail -> addAddress( $toUser, $toName );
    $mail -> isHtml(true);
    $mail -> AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    $l = count( $attachments );
    $i = 0;
    while( $i < $l ) {
        $tmp = explode( ",", $attachments[$i]);
        $mail -> addAttachment( $tmp[0], $tmp[1] );
        $i += 1;
    }
    $mail -> Subject = $subject;
    
                $content = '<html>
                                    <body>
                                    <head>
                                        <title>Informations-E-Mail der Domain "Suchtselbsthilfe-Regenbogen"</title>
                                    </head>
                                        <img src="cid:TBP" alt="Logo" style="width: 150px">
                                        <h4>' . $title . '</h4>
                                        <h3>Hinweis</h3>
                                        ';
                                        if(  $isIntern ) {
                                            $content .= '<p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>';
                                        } else {
                                            $content .= '<p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworten Sie nicht darauf.</p>';
                                        }
                                        $content .= '<h3>Inahlt</h3>
                        ' . $contentEmail . '
                                            <p>&nbsp;</p>
                                            ';
                                            if( $isIntern ) {
                                                $content .= '    
                                            <p>Dein "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: ' . $fromUser . '</dt>
                                                    <dt>Telefon: +49 341 444 232 2</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>';
                                            } else {
                                                $content .= '    
                                            <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: ' .  $fromUser . '</dt>
                                                    <dt>Telefon: +49 341 444 222 1</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>';
                                                
                                            }
                                    $content .= getEmailSignature() . '
                               </body>
                                    </html>                                
                                    ';                                        

    $mail -> Body = $content;
    $result = $mail -> send();
    return $result;
    unset( $mail );
}
class InformUser {
    private $pdo;
    private $message_behavior;
    private $fromRole;
    private $fromUser;
    private $fromEmail;
    private $fromName;
    private $toRole;
    private $toUser;
    private $usrArr;
    private $mail;
    private $mailException;
    private $message;
    private $isIntern; 
    private $attachments; 
    private $isMessage; 
    public $failureRecipants;
    public function standardFunktion(  ) {
        $return = new \stdClass();
        try{
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function __construct( $pdo, $message_behavior, $fromRole = 0, $fromUser = 0, $toRole = 0, $toUser = 0, $isIntern = true, $attachments = [], $isMessage = true ) {
        $this -> pdo = $pdo; 
        $this -> message_behavior = $message_behavior;
        $this -> fromRole = $fromRole;
        $this -> fromUser = $fromUser;
        $this -> toRole = $toRole;
        $this -> toUser = $toUser;
        $this -> usrArr = [];
        if( $this -> toRole != 0 && $this -> toRole != "" ) {
            $this -> usrArr = $this -> getUsersForRole( $toRole ) -> data;                
        }
        if( $this -> toUser != 0 && $this -> toUser != "" ) {
            $tmp = $this -> getUserInfo( $toUser ) -> data;
        }
        $usrArr = [];
        if( isset( $tmp ) && count( $tmp ) > 0 ) {
            foreach( $tmp as $entry ) {
                if( !in_array( $entry, $this -> usrArr ) ) {
                    $usrArr[] = $entry;    
                }
            }    
        }
        $this -> usrArr = array_merge( $this -> usrArr, $usrArr );
        $tmp = $this -> getFromNameEmail( $this -> fromRole, $this -> fromUser );
        $this -> fromEmail = $tmp["email"];
        $this -> fromName = $tmp["name"];
        require_once "PHPMailer/PHPMailer/Exception.php";
        require_once "PHPMailer/PHPMailer/PHPMailer.php";
        require_once "Message.php";
        $this -> mail = new \PHPMailer\PHPMailer\PHPMailer();
        $this -> mailException = new \PHPMailer\PHPMailer\Exception();
        $this -> mail -> CharSet = "UTF-8";
        $this -> mail -> setFrom( $this -> fromEmail, $this -> fromName );
        $this -> mail -> isHtml(true);
        $this -> mail -> AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
        $this -> message = new \Message();
        $this -> isIntern = $isIntern;
        $this -> attachments = $attachments;            // ["path1, name1", "path2, name2", ... ]
        $this -> attachments = $attachments;            // ["path1, name1", "path2, name2", ... ]
        $this -> isMessage = $isMessage;
    }
    public function setFromNameEmail(){
        
    }
    public function setAttachments(){
        $l = count( $this -> attachments );
        $i = 0;
        while( $i < $l ) {
            $tmp = explode( ",", $this -> attachments[$i] );
            $this -> mail -> addAttachment( $tmp[0], $tmp[1] );
            $i += 1;
        }
    }
    public function addAttachment( $attachment ){
            array_push( $this -> attachments, $attachment );
    }
    public function getFromNameEmail( $roleId, $userId ){
        $return = new \stdClass();
        if( $roleId != 0 ) {
            $q = "select sender as name, sender_email as email from role where id = $roleId";
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );
        } else {
            $q = "select concat( firstname, ' ', lastname ) as name, email from user where id = $userId";
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );    
        }
        return $return -> data[0];        
    }
    private function getToNameEmail( $roleId, $userId ) {
        $return = new \stdClass();
        if( $roleId != 0 ) {
            $q = "select sender, sender_email as email from role where id = $roleId";
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );
        } else {
            $q = "select concat( firstname, ' ', lastname ) as name, email from user where id = $userId";
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );    
        }
        return $return -> data[0];
    }    
    private function getUsersForRole( $id ) {
        $return = new \stdClass();
        try{
            $q = "select user.id, concat( firstname, ' ', lastname ) as name, email, opt_in from user, account where account.user_id = user.id and account.role_id = $id";
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );
            $return -> success = true;
            $return -> message = "Die Daten wurden erfolgreich gelesen.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    private function getUserInfo( $id ) {
        $return = new \stdClass();
        try{
            $q = "select user.id, concat( firstname, ' ', lastname ) as name, email, opt_in from user where id in ( $id )";
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );
            $return -> success = true;
            $return -> message = "Die Daten wurden erfolgreich gelesen.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function sendEmail( $title, $contentEmail, $toUserEmail, $toUserName, $isIntern, $attachments = [] ) {
        $return = new \stdClass();
        $return -> mailSuccess = true;
        try{
            $email_signature = "../inc/email_signature.inc"; // Name der Datei
            $handler = fopen($email_signature , "r");
            $email_signature_content = fread( $handler, filesize( $email_signature ) );
            fclose( $handler );
            $this -> mail = new \PHPMailer\PHPMailer\PHPMailer();
            $this -> mailException = new \PHPMailer\PHPMailer\Exception();
            $this -> mail -> CharSet = "UTF-8";
            $tmp = explode( "@", $toUserEmail );
            $this -> attachments = [];
            if( count( $attachments ) > 0 ) {
                $this-> attachments = $attachments;    
            }
            if( isset( $tmp[1] ) && ( $tmp[1] === "gmail.com" || $tmp[1] === "googlemail.com" ) ) {
                $return -> mailSuccess = mailGmail( $this -> fromEmail, $this -> fromName, $toUserEmail, $toUserName, $title, $title, $contentEmail, $isIntern, $this -> attachments );
                if( !$return -> mailSuccess ) {
                    $fRec = new \stdClass();
                    $fRec -> name = $toUserName;
                    $fRec -> email = $toUserEmail;
                    $this -> failureRecipants[] = $fRec;
                }
                return $return;
            } else  {
                $this -> mail -> setFrom( $this -> fromEmail, $this -> fromName );            
            }
            $this -> mail -> isHtml(true);
            $this -> mail -> AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
            $l = count( $this -> attachments );
            $i = 0;
            while( $i < $l ) {
                $tmp = explode( ",", $this -> attachments[$i]);
                $this -> mail -> addAttachment( $tmp[0], $tmp[1] );
                $i += 1;
            }        
            $this -> mail -> addAddress( $toUserEmail, $toUserName );
            $this -> mail -> Subject = $title;
            $content = '<html>
                                    <body>
                                    <head>
                                        <title>Informations-E-Mail der Domain "Suchtselbsthilfe-Regenbogen"</title>
                                    </head>
                                        <img src="cid:TBP" alt="Logo" style="width: 150px">
                                        <h4>' . $title . '</h4>
                                        <h3>Hinweis</h3>
                                        ';
                                        if(  $isIntern ) {
                                            $content .= '<p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>';
                                        } else {
                                            $content .= '<p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworten Sie nicht darauf.</p>';
                                        }
                                        $content .= '<h3>Inahlt</h3>
                        ' . $contentEmail . '
                                            <p>&nbsp;</p>
                                            ';
                                            if( $isIntern ) {
                                                $content .= '    
                                            <p>Dein "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: ' . $this -> fromEmail . '</dt>
                                                    <dt>Telefon: +49 341 444 232 2</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>';
                                            } else {
                                                $content .= '    
                                            <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                            <address>
                                                <dl>
                                                    <dt>E-Mail: ' .  $this -> fromEmail . '</dt>
                                                    <dt>Telefon: +49 341 444 222 1</dt>
                                                    <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                                    <dd>D-04177 Leipzig</dd>
                                                    <dd>Germany</dd>
                                                </dl>
                                            </address>';
                                                
                                            }
                                    $content .= getEmailSignature() . '
                               </body>
                                    </html>                                
                                    ';                                        

            $this -> mail -> Body = $content;
            $return -> mailSuccess = $this -> mail -> send();
            if( !$return -> mailSuccess ) {
                $fRec = new \stdClass();
                $fRec -> name = $toUserName;
                $fRec -> email = $toUserEmail;
                $this -> failureRecipants[] = $fRec;
            }
            unset( $this -> mail );    
            return  $return;

        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function addAddress( $email, $name ){
        $this -> mail -> addAddress( $email, $name );    
    }
    public function addBCC( $email, $name ){
        $this -> mail -> addBCC( $email, $name );    
    }
    public function addImg( $path, $pattern, $name ){
        $this -> mail -> AddEmbeddedImage( $path, $pattern, $name );
    }
    public function sendUserInfo( $titleEmail, $titleMessage, $contentEmail, $contentMessage ) {
        $return = new \stdClass();
        $return -> mailSuccess = true;
        $return -> failurePart = [];
        try{
            switch( $this -> message_behavior ) {
                case "email":
                    $l = count( $this -> usrArr );
                    $i = 0;
                    while( $i < $l ) {
                        $tmp = $this -> sendEmail( $titleEmail, $contentEmail, $this -> usrArr[$i]["email"], $this -> usrArr[$i]["name"], $this -> isIntern, $this -> attachments );
                        if( !$tmp -> mailSuccess ) {
                            if( $return -> mailSuccess ) $return -> mailSuccess = false;
                            $fPart = new \stdClass();
                            $fPart -> name = $this -> usrArr[$i]["name"];
                            $fPart -> email = $this -> usrArr[$i]["email"];
                            $return -> failurePart[] = $fPart;
                        }
                        $i += 1;
                    }                    
                break;
                case "message":
                    $tmp = explode( ",", $this -> toUser );
                    $l = count( $tmp );
                    $i = 0;
                    if( !$this -> isMessage ) {
                        require_once( "News.php" );
                        $news = new \News();
                    }
                    if( $this -> isMessage ) {
                        while( $i < $l ) {
                            $this -> message -> newMessage( $this -> pdo, $titleMessage, $contentMessage, $this -> fromRole, $this -> fromUser, $this -> toRole, $tmp[$i] );
                            $i += 1;
                        }
                    } else {
                        while( $i < $l ) {
                            $news -> newNews( $this -> pdo, $titleMessage, $contentMessage, $this -> fromRole, $this -> fromUser, $this -> toRole, $tmp[$i] );
                            $i += 1;
                        }                        
                    }
                break;
                case "both":
                    $l = count( $this -> usrArr );
                    $i = 0;
                    if( !$this -> isMessage ) {
                        require_once( "News.php" );
                        $news = new \News();
                    }
                    while( $i < $l ) {
                        $tmp = $this -> sendEmail( $titleEmail, $contentEmail, $this -> usrArr[$i]["email"], $this -> usrArr[$i]["name"], $this -> isIntern, $this -> attachments );
                        if( !$tmp -> mailSuccess ) {
                            if( $return -> mailSuccess ) $return -> mailSuccess = false;
                            $fPart = new \stdClass();
                            $fPart -> name = $this -> usrArr[$i]["name"];
                            $fPart -> email = $this -> usrArr[$i]["email"];
                            $fPart -> id = $this -> usrArr[$i]["id"];
                            $return -> failurePart[] = $fPart;
                        }
                        if( $this -> isMessage ) {
                            $this -> message -> newMessage( $this -> pdo, $titleMessage, $contentMessage, $this -> fromRole, $this -> fromUser, 0, $this -> usrArr[$i]["id"] );
                        } else {
                            $news -> newNews( $this -> pdo, $titleMessage, $contentMessage, $this -> fromRole, $this -> fromUser, $this -> toRole, $tmp[$i] );
                        }
                        
                        $i += 1;
                    }
                break;
                case "intelligent":
                break;    
            }
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
  
}
?>
