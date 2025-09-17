<?php

@date_default_timezone_set("Europe/Berlin");

function encrypt_decrypt($action, $string) {
    /* =================================================
    * ENCRYPTION-DECRYPTION
    * =================================================
    * ENCRYPTION: encrypt_decrypt('encrypt', $string);
    * DECRYPTION: encrypt_decrypt('decrypt', $string) ;
    */
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'WS-SERVICE-KEY';
    $secret_iv = 'WS-SERVICE-VALUE';
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'encrypt') {
        $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    } else {
        if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
    }
    return $output;
}
/* geo coords */
/*

R = Erdradius (ca. unterschiedliche Quellen nennen zwischen 6336 und 6399 km)
dlon = B_lon - A_lon
dlat = B_lat - A_lat

distance = R * (2 * arcsin(min(1,sqrt((sin(dlat/2))^2 + cos(A_lat) * cos(B_lat) * (sin(dlon/2))^2))))

MySQL


CREATE FUNCTION `GoogleDistance_KM`(
geo_breitengrad_p1 double,
geo_laengengrad_p1 double,
geo_breitengrad_p2 double,
geo_laengengrad_p2 double ) RETURNS double
RETURN (6371 * acos( cos( radians(geo_breitengrad_p2) ) * cos( radians( geo_breitengrad_p1 ) )
* cos( radians( geo_laengengrad_p1 ) - radians(geo_laengengrad_p2) )
+ sin( radians(geo_breitengrad_p2) ) * sin( radians( geo_breitengrad_p1 ) ) )
);

bsp:
SELECT opnv_stops.stop_id,street, lat, lon
from opnv_stops
where GoogleDistance_KM(lat, lon, 51.300806, 12.326508) <=1

*/
function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'km') {
    $theta = $longitude1 - $longitude2; 
    $distance = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta))); 
    $distance = acos($distance); 
    $distance = rad2deg($distance); 
    $distance = $distance * 60 * 1.1515; 
    switch($unit) { 
        case 'miles': 
        break; 
        case 'km' : 
            $distance = $distance * 1.609344; 
    } 
    return (round($distance,2)); 
}
/* end geo coords */

function splitUrl( $url = "" ) {
    $return = new \stdClass();
    if( $url == "" ) {
        $url = (empty($_SERVER['HTTPS'])) ? 'http://' : 'https://';
        $url .= $_SERVER['HTTP_HOST'];
        $url .= $_SERVER['REQUEST_URI']; // $url enthält jetzt die komplette URL
    }
    $pieces = parse_url($url);
    $return -> scheme = $pieces['scheme']; // enthält "http"
    $return -> host = $pieces['host']; // enthält "www.example.com"
    $return -> path = $pieces['path']; // enthält "/dir/dir/file.php"
    $tmp = explode( "/", $return -> path );
    $return -> fileName = $tmp[ count( $tmp ) - 1 ];
    $l = count( $tmp ) - 1;
    $i = 0;
    $return -> path = "";
    while( $i < $l ) {
        $return -> path .= "/" . $tmp[$i];
        $i += 1;
    }
    $return -> path = substr( $return -> path, 1 );
    if( isset( $pieces['query'] )  ) {
        $return -> query = $pieces['query']; // enthält "arg1=foo&arg2=bar"
    }
    if( isset( $pieces['fragment'] )  ) {
        $return -> fragment = $pieces['fragment']; // ist leer, da getCurrentUrl() diesen Wert nicht zurückgibt        
    }
    return $return;
}
function urlExists( $url= NULL ) {
    
        if($url == NULL) return false;
        $tmp = splitUrl( $url );
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {   
            $urlSelf = "https://";
        } else {
            $urlSelf = "http://";    
        } 
        $urlSelf.= $_SERVER['HTTP_HOST'];
        $urlSelf.= $_SERVER['REQUEST_URI'];   
        $tmpUrl = splitUrl( $urlSelf );
        //if( )
        $headers = @get_headers($url);
  
// Use condition to check the existence of URL
/*
if($headers && ( strpos( $headers[0], '200') || strpos( $headers[0], '302') ) ) {
    return true;
}
else {
    return false;
}
*/
    //return curl_init($url) !== false;

    if( $tmp -> host == "suchtselbsthilfe-regenbogen.de" || $tmp -> host == "www.suchtselbsthilfe-regenbogen.de" ) {
        if( $tmp -> fileName != "" ) {
            return linkExists( $tmp -> fileName, "../../" );    
        }  
    }   
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 
        if($httpcode>=200 && $httpcode<300){
            return true;
        } else {
            return false;
        }
    
}
function linkExists( $link, $praefix = "" ) {
    if( $praefix != "" ) {
        $link = $praefix . $link;    
        if($c = @file_get_contents( $link ) ) {
            return true;
        }
        else {
            return false;
        }
    } else {
        return urlExists( $link );
    }
}
function chkLinkExists( $page ) {
    $tmp = explode( ":", $page );
    if( $tmp[0] != "http" && $tmp[0] != "https" ) {
        $tmpPage = explode( "?", $page );
        return linkExists( $tmpPage[0], "../../" );
    } else {
        return linkExists( $page );
    }
}
/* end file functions */
function formatFilesizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}
function deleteDirectory( $dir ) {
    if (!file_exists( $dir )) {
        return true;
    }
    if ( !is_dir( $dir ) ) {
        return unlink( $dir );
    }
    foreach ( scandir( $dir ) as $item ) {
        if ( $item == '.' || $item == '..') {
            continue;
        }
        if ( !deleteDirectory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
            return false;
        }
    }
    return rmdir($dir);
}
function checkDirIsEmpty( $baseDir, $dir ) {
    $content = scandir( $baseDir . $dir );
    if( count($content) > 2 ) {
        return true;
    } else {
        return false;
    }
}
function copy_dir($sSourcePath, $sTargetPath) {  
    if (is_dir($sSourcePath) && !is_dir($sTargetPath))
    {
        mkdir($sTargetPath, 0755);
        foreach ($oIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sSourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $oItem)
        {
            if ($oItem->isDir())
                mkdir($sTargetPath.DIRECTORY_SEPARATOR.$oIterator->getSubPathName());
            else
                copy($oItem, $sTargetPath.DIRECTORY_SEPARATOR.$oIterator->getSubPathName());
        }
        return true;
    }
    return false;
}
function getFileWithoutExt( $fn ) {
    $tmp = explode( ".", $fn );
    $l = count( $tmp ) - 1;
    $i = 0;
    $fn = "";
    while ( $i < $l ){
        $fn .=  $tmp[ $i ] . "." ;
        $i += 1;
    }
    $fn = substr( $fn, 0, -1 );
    return $fn;    
}
function getFileExt( $fn ) {
    $tmp = explode( ".", $fn );
    $l = count( $tmp );
    return $tmp[ $l - 1 ];
}
function getNextFileName( $path, $name, $type, $pattern = " - Kopie" ) {
    switch( $type ) {
        case "file":
            $ext = getFileExt( $name );
            $fn = getFileWithoutExt( $name );
            if( !file_exists( $path . $fn . $pattern . "." . $ext ) ) {
                return $path . $fn . $pattern . "." . $ext;
            } else {
                // file exists
                $counter = 1;
                $i = 1;
                do {
                    $fn = $path . $fn . $pattern . "($counter)" . "." . $ext;
                    if( !file_exists( $fn ) ) {
                       $x = 0; 
                   } else {
                       $counter += 1;
                   }
                }  while( $x!=0 ); 
            }  
        break;
        case "dir":
        $fn = $name;
            if( !file_exists( $path . $fn . $pattern . "." . $ext ) ) {
                return $path . $fn . $pattern;
            } else {
                // file exists
                $counter = 1;
                $i = 1;
                do {
                    $fn = $path . $name . $pattern . "($counter)" . "." . $ext;
                    if( !file_exists( $fn ) ) {
                       $x = 0; 
                   } else {
                       $counter += 1;
                   }
                }  while( $x!=0 ); 
            }  
        
        break;
    }
    return $fn;
}
/* end file functions */
function getOnceNthOfMonth( $m, $y, $w, $n ) {
    switch( $w ) {
        case "0": $day = "Sunday";
        break;
        case "1": $day = "Monday";
        break;
        case "2": $day = "Tuesday";
        break;
        case "3": $day = "Wednesday";
        break;
        case "4": $day = "Thirsday";
        break;
        case "5": $day = "Friday";
        break;
        case "6": $day = "Saturday";
        break;        
    }
    switch( $n ) {
        case 1: $nth = "First";
        break; 
        case 2: $nth = "Second";
        break; 
        case 3: $nth = "Third";
        break; 
        case 4: $nth = "Fourth";
        break; 
        case 5: $nth = "Fifth";
        break; 
    }
    switch( $m ) {
        case 1: $mth = "January";
        break; 
        case 2: $mth = "February";
        break; 
        case 3: $mth = "March";
        break; 
        case 4: $mth = "April";
        break; 
        case 5: $mth = "May";
        break; 
        case 6: $mth = "June";
        break; 
        case 7: $mth = "July";
        break; 
        case 8: $mth = "August";
        break; 
        case 9: $mth = "September";
        break; 
        case 10: $mth = "October";
        break; 
        case 11: $mth = "November";
        break; 
        case 12: $mth = "December";
        break; 
    }
    $tmp = date_create("$nth $day of $mth $y") -> format("Y-m-d");
    $month = date("m", strtotime( $tmp ) );
    if( $month != $m ) return;
    return $tmp;
/*
    switch( $w ) {
        case "0": $phpDate = new DateTime( $y . "-" . $m . "-" . ( ( $n - 1 ) * 7 + 1 ) );
        break;
        case "1": $phpDate = new DateTime( $y . "-" . $m . "-01" );
        break;
        default:  $phpDate = new DateTime( $y . "-" . $m . "-" . ( ( $n - 1 ) * 7 ) );
        break;
    }
    $tmpMonth = $phpDate->format("m");
    $l = 7;
    $i = 0;
    while( $i < $l ) {
        if( $phpDate->format("w") == $w ) {
            if( $phpDate->format("m") != $tmpMonth ) {
                return null;
            } else {
                return  $phpDate->format("Y-m-d");            
            }
        }
        $phpDate->modify(' +1 day');
        $i += 1;
    }    
*/
}
function getNthOfMonth( $start_date, $end_date, $week_day, $nth ) {
    $date1 = $start_date;
    $date2 = $end_date;

    $ts1 = strtotime($date1);
    $ts2 = strtotime($date2);

    $year1 = date('Y', $ts1);
    $year2 = date('Y', $ts2);

    $month1 = date('m', $ts1);
    $month2 = date('m', $ts2);

    $diff = ( ( $year2 - $year1 ) * 12 ) + ( $month2 - $month1 ) + 1;

    $i = 0;
    $start_month = intval( $month1 );
    $start_year = intval( $year1 );
    $result = [];
    while( $i < $diff ) {
        $date = getOnceNthOfMonth( $start_month, $start_year, $week_day, $nth ); // month, year, weekday (0-Su, 1-Mo...), nth-day of month
        if( strtotime( $date ) <= strtotime( $start_date ) || strtotime( $date ) > strtotime( $end_date ) ) {
        } else {
            $result[] = $date;
        }
        $start_month += 1;
        if( $start_month >= 12 ) {
            $start_month = 1;
            $start_year += 1;
        }
        $i += 1;
    }
    return $result;
}
function handleForApendix( $adId ) {
    $ext = "";
    $glob = glob( "../images/ad/new_" . $_SESSION["user_id"] . "*.*" );
    for( $i = 0; $i < count( $glob ); $i++ ) {
        $tmp = explode( ".", $glob[$i] );
        $ext = $tmp[ count( $tmp ) - 1 ];
        $fname = $glob[$i]; 
    }
    if( $ext != "" ) {
        rename( $fname, "../images/ad/ad_" . $adId . "_" . time() . "." . $ext );    
    }
}
function getForApendix( $adId ) {
    $ext = "";
    $glob = glob( "../images/ad/ad_" . $adId . "*.*" );
    for( $i = 0; $i < count( $glob ); $i++ ) {
        $tmp = explode( ".", $glob[$i] );
        $ext = $tmp[ count( $tmp ) - 1 ];
        $fname = $glob[$i];
    }
    $result = new stdClass();
    $result -> filename = "";
    $result -> ext = "";
    if( $ext != "" ) {
        $fname = "library" . substr( $fname, 2 );
        $result -> filename = $fname;
        $result -> ext = $ext;
    }
    return $result;    
}
function deleteTmpApendix() {
    $glob = glob("../images/ad/tmp_*.*");
    for( $i = 0; $i < count( $glob ); $i++ ) {
        unlink( $glob[$i] );
    }
}
function getEmailSignature() {
    // start email signature
    $email_signature = "../inc/email_signature.inc"; // Name der Datei
    $handler = fopen($email_signature , "r");
    $email_signature_content = fread( $handler, filesize( $email_signature ) );
    fclose( $handler );
    // end email signature
    return $email_signature_content;
}
/**
* put your comment there...
* 
* @param mixed $titleEMail
* @param mixed $headlineEMail
* @param mixed $contentEmail
* @param mixed $fromEmail
* @param mixed $fromEmailName
* @param mixed $toUserEmail
* @param mixed $toUserName
* @param mixed $isIntern
*/
function sendEMail( $titleEMail, $contentEmail, $fromEmail, $fromEmailName, $toUserEmail, $toUserName, $isIntern ){
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( $fromEmail, $fromEmailName );
    $mail -> addAddress( $toUserEmail, $toUserName );
    $mail->Subject = $titleEMail;

    $mail->isHtml(true);
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    $content = '<html>
                            <body>
                                <img src="cid:TBP" alt="Logo" style="width: 150px">
                                <h4>' . $titleEMail . '</h4>
                                <h3>Hinweis</h3>
                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>
                                <h3>Inahlt</h3>
                ' . $contentEmail . '
                                    <p>&nbsp;</p>
                                    ';
                                    if( $isIntern ) {
                                        $content .= '    
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 222 1</dt>
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
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
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

    $mail->Body = $content;    
    return $mail -> send();
}
/**
* put your comment there...
* 
* @param mixed $titleEMail                 string
* @param mixed $titleMessage               string
* @param mixed $headlineEMail              string
* @param mixed $contentEmail               string
* @param mixed $contentMessage             string
* @param mixed $fromRole                   int      0 or role id
* @param mixed $fromUser                   int      0 or user id
* @param mixed $toRole                     int      0 or role id
* @param mixed $toUser                     int      0 or user id
* @param mixed $messageBehavior            string   both, meessage, email, intellegent
* @param mixed $isIntern                   bool     intern or extern
*/
function informUser( $pdo, $titleEMail, $titleMessage, $headlineEMail, $contentEmail, $contentMessage, $fromRole, $fromUser, $toRole, $toUser, $messageBehavior, $isIntern  ) {
    if( $fromRole !== 0 ) {
        $q = "select email, name from role where id = $fromRole";
    } else {
        $q = "select email, concat( firstname, ' ', lastname ) as name from user where id = $fromUser";
    }
    $s = $pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_ASSOC );
    $fromEmail = $r[0]["email"];
    $fromName = $r[0]["name"];
    if( $toRole !== 0 ) {
        $q = "select id, email, concat( firstname, ' ', lastname ) as name, opt_in from user, role  where role.user_id = user.id and role.id = $toRole";
        $s = $pdo -> query( $q );
        $r_to_role = $s -> fetchAll( PDO::FETCH_ASSOC );
    }
    if( $toUser !== 0 ) {
        $q = "select id, email, concat( firstname, ' ', lastname ) as name, opt_in from user where id = $toUser";        
        $s = $pdo -> query( $q );
        $r_to_user = $s -> fetchAll( PDO::FETCH_ASSOC );
    }
    $r_to_user = array_merge( $r_to_role, $r_to_user );
    $r_to_user = array_unique( $r_to_user );
    switch( $messageBehavior ) {
        case "both":        
            require_once( "classes/Message.php");
            $m = new \Message();
            $l = count( $r_to_user );
            $i = 0;
            while( $i < $l ) {
                // TODO: send email
                sendEMail( $titleEMail, $headlineEMail, $contentEmail, $fromEmail, $fromName, $r_to_user[$i]["email"], $r_to_user[$i]["name"], $isIntern );
                //
                $m -> newMessage( $pdo, $titleMessage, $contentMessage, $fromRole, $fromUser, 0, $r_to_user[$i]["id"] );        
                $i += 1;
            }
        break;
        case "message":
            require_once( "classes/Message.php");
            $m = new \Message();
            $l = count( $r_to_user );
            $i = 0;
            while( $i < $l ) {
                $args[$i];
                $m -> newMessage( $pdo, $titleMessage, $contentMessage, $fromRole, $fromUser, 0, $r_to_user[$i]["id"] );        
                $i += 1;
            }
        break;
        case "email":
            while( $i < $l ) {
                sendEMail( $titleEMail, $headlineEMail, $contentEmail, $fromEmail, $fromName, $r_to_user[$i]["email"], $r_to_user[$i]["name"], $isIntern );
                $i += 1;
            }
        break;
        case "intelligent":
            while( $i < $l ) {
                if( $r_to_user[$i]["opt_in"] === "1" ) {
                    sendEMail( $titleEMail, $headlineEMail, $contentEmail, $fromEmail, $fromName, $r_to_user[$i]["email"], $r_to_user[$i]["name"], $isIntern );
                } else {
                    require_once( "classes/Message.php");
                    $m -> newMessage( $pdo, $titleMessage, $contentMessage, $fromRole, $fromUser, 0, $r_to_user[$i]["id"] );        
                }
                $i += 1;
            }
        
        break;
    }
}
/* send wh remind email */
function sendWHRemindEMail( $pdo, $mail, $fromEmail, $fromName, $monthName, $email_signature ) {
    $query = "select concat( firstname, ' ', lastname ) as name, email from user, account where account.user_id = user.id and account.role_id = 13";
    $stm = $pdo -> query( $query );
    $res = $stm -> fetchAll( PDO::FETCH_ASSOC );
    $mail->CharSet = "UTF-8";
    $mail->setFrom( $fromEmail, $fromName);
    $l = count( $res );
    $i = 0;
    while ( $i < $l ){
            $mail->addAddress( $res[$i]["email"], $res[$i]["name"] );    
        $i += 1;
    }
    $mail->Subject = 'Erinnerungs-E-Mail des Proketteams „Wandelhalle-Regenbogen”';
    
    $mail->isHtml(true);
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    $content = "<img src='cid:TBP' alt='Logo' style='width:150px'>
    <h3>Erinnerungs-E-Mail des Proketteams „Wandelhalle-Regenbogen”</h3>
    <p>
        Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
    </p>
    <p>
        Die Terine für die „Wandelhalle-Regenbogen” des Monats $monthName wurden aktualisiert.
    </p>
    <p>
        Bitte Trage Dich mit derAngabe, als was Du teilnehmen möchstst, bis spätesten zum 20. dieses Monats im Veranstaltungskalender ein.
    </p>
    <p>&nbsp;</p>
    <p>$fromName</p>
    <address>
        <dl>
            <dt>E-Mail: $fromEmail/dt>
            <dt>Telefon: +49 341 444 232 2</dt>
            <dt>Adresse:</dt>
            <dd>Demmeringstr. 47-49</dd>
            <dd>D-04177 Leipzig</dd>
            <dd>Germany</dd>
        </dl>
    </address>
    $email_signature_content";
    $mail->Body = $content;  
    if ($mail->Send()) {
        return true;
    }
    else {
        return false;
    }
            
    
}

/* end  wh remind email */
// send contact email
function sendContactEmail( $mailContent, $s ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "„Bitte um Kontakt” Suchtselbsthilfe „Regenbogen”");
    $tmpEM = $s["contact_form"]["contact_form_reciever"];
    $aArrEM = explode(",", $tmpEM );
    $tmpN = $s["contact_form"]["contact_form_reciever_names"];
    $aArrN = explode(",", $tmpN );
    $l = count( $aArrEM );
    $i = 0;
    while( $i < $l ) {
        $mail->addAddress( $aArrEM[ $i ], $aArrN[ $i ] );
        $i += 1;
    }
    $mail->Subject = '„Bitte um Kontakt”-E-Mail - Suchtselbsthilfe „Regenbogen”';

    $mail->isHtml(true);
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    $content = '<html>
                            <head>
                                <title>Bitte um Kontakt</title>
                            </head>
                            <body>
                                <img src="cid:TBP" alt="Logo" style="width: 150px">
                                <h3>„Bitte um Kontakt”-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>
                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>
                                <h3>Kontaktanfrage</h3>
                ' . $mailContent . '
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 222 1</dt>
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
        
    return $mail -> send();
    
}
function sendContactBCEmail( $mailContent, $s ){
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "„Bitte um Rückruf Suchtselbsthilfe „Regenbogen”");
    $tmpEM = $s["contact_form"]["contact_bc_form_reciever"];
    $aArrEM = explode(",", $tmpEM );
    $tmpN = $s["contact_form"]["contact_bc_form_reciever_names"];
    $aArrN = explode(",", $tmpN );
    $l = count( $aArrEM );
    $i = 0;
    while( $i < $l ) {
        $mail->addAddress( $aArrEM[ $i ], $aArrN[ $i ] );
        $i += 1;
    }
    $mail->Subject = '„Bitte um Rückruf”-E-Mail - Suchtselbsthilfe „Regenbogen”';

    $mail->isHtml(true);
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    $content = '<html>
                            <head>
                                <title>Bitte um Rückruf</title>
                            </head>
                            <body>
                                <img src="cid:TBP" alt="Logo" style="width: 150px">
                                <h3>„Bitte um Rückruf”-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>
                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>
                                <h3>Rückrufanfrage</h3>
                ' . $mailContent . '
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
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        

                            $mail->Body = $content;
    if( $mail -> send() ) {
        return true;
    } else {
        return false;
    }   
}
/* start bibo (admin) informations */
    /* start email about reservation */
    /**
    * email about reservation
    * 
    * @param mixed $m       php-mailer-mail
    * @param mixed $cu      current user name (not the reservation user)
    * @param mixed $book    book title
    */
    function sendReservationEMail( $m, $cu, $book ) {
        $m->CharSet = "UTF-8";
        $m->Subject = 'Reservierungs-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';        
        $m->isHtml(true);
        $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
        $c = '
                            <html>
                            <head>
                                <title>Buchreservierung bei der Bibliothek der Suchtselbsthilfe-„Regenbogen”</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Buchreservierung</h3>
        ';
        $c .= "<p>Das Buch „" . $book . "” wurde durch $cu für dich reserviert und kann bis zum " . date( "d.m.Y", strtotime("+7 day", time() ) ) . " in der Bibliothek abgeholt werden.</p>";        
        $c .= '<p>&nbsp;</p>
                                    <p>Dein „Suchtselbsthilfe-Regenbogen”-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $c;
        
    return $m -> send();
    }
    function sendRequestEMail( $m, $cu, $book ) {
        $m->CharSet = "UTF-8";
        $m->Subject = 'Buchanfrage-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';        
        $m->isHtml(true);
        $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
        $c = '
                            <html>
                            <head>
                                <title>Buchanfrage bei der Bibliothek der Suchtselbsthilfe-„Regenbogen”</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Buchanfrage</h3>
        ';
        $c .= "<p>Das Buch „" . $book . "” wurde durch dich erfolgreich angefragt. Du erhälst in Kürze durch die 
                    Bibliotheks-E-Mail-Adresse eine Information darüber, wann und wie Du das Buch in der Bibliothek abholen kannst.</p>";        
        $c .= '<p>&nbsp;</p>
                                    <p>Dein „Suchtselbsthilfe-Regenbogen”-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $c;
        
    return $m -> send();
    }
    /* end email about reservation */

    function sendBibliothekarRequestEMail( $m, $cu, $book ) {
        $m->CharSet = "UTF-8";
        $m->Subject = 'Buchanfrage-E-Mail der Suchtselbsthilfe-„Regenbogen”-Bibliothek';        
        $m->isHtml(true);
        $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
        $c = '
                            <html>
                            <head>
                                <title>Buchanfrage bei der Bibliothek der Suchtselbsthilfe-„Regenbogen”</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Buchreservierung</h3>
        ';
        $c .= "<p>Das Buch „" . $book . "” wurde durch $cu angefragt. Bitte reserviere das Buch für diesen Nutzer im Bibliotheks-Administrations-Formular.</p>";        
        $c .= '<p>&nbsp;</p>
                                    <p>Dein „Suchtselbsthilfe-Regenbogen”-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $c;
        
    return $m -> send();
    }
    /* end email about reservation */

function informUserAboutReservation($pdo, $uId, $bookId){
    $return = new \stdClass();
    $set = parse_ini_file('../../ini/settings.ini', TRUE);
    $q = "SELECT CONCAT( firstname, ' ', lastname) AS fullname, opt_in, email FROM user WHERE id = $uId";
    $s = $pdo -> query( $q );
    $ru = $s -> fetchAll( PDO::FETCH_ASSOC );
    $oi = $ru[0]["opt_in"];
    $q = "SELECT CONCAT( firstname, ' ', lastname) AS fullname FROM user WHERE id = " . $_SESSION["user_id"];
    $s = $pdo -> query( $q );
    $rb = $s -> fetchAll( PDO::FETCH_ASSOC );
    $cu = $rb[0]["fullname"];
    $q = "SELECT title FROM book WHERE id = $bookId";
    $s = $pdo -> query( $q );
    $bt = $s -> fetchAll( PDO::FETCH_ASSOC );
    $cbt = $bt[0]["title"];
    switch( $set["admin_book"]["reservation_message_behavior"] ) {
        case "email":
            require_once "PHPMailer/PHPMailer/Exception.php";
            require_once "PHPMailer/PHPMailer/PHPMailer.php";
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $m -> addAddress( $ru[0]["email"], $ru[0]["fullname"] );
            $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
            $return -> mail = sendReservationEMail( $m, $cu, $cbt );
        break;
        case "message":
            require_once( "classes/Message.php");
            $m = new \Message();
            $c = "Das Buch „$cbt” wurde durch $cu für dich reserviert und kann bis zum " . date( "d.m.Y", strtotime("+7 day", time() ) ) . " in der Bibliothek abgeholt werden.";
            $m -> newMessage( $pdo, "Buchreservierung „$bt”", $c, 0, $_SESSION["user_id"], 0, $uId);        
        
        break;
        case "both":
            require_once "PHPMailer/PHPMailer/Exception.php";
            require_once "PHPMailer/PHPMailer/PHPMailer.php";
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $m -> addAddress( $ru[0]["email"], $ru[0]["fullname"] );
            $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
            $return -> mail = sendReservationEMail( $m, $cu, $cbt );
            // build message
            require_once( "classes/Message.php");
            $m = new \Message();
            $c = "Das Buch „" . $cbt . "” wurde durch $cu für dich reserviert und kann bis zum " . date( "d.m.Y", strtotime("+7 day", time() ) ) . " in der Bibliothek abgeholt werden.";
            $m -> newMessage( $pdo, "Buchreservierung „" . $cbt . "”", $c, 0, $_SESSION["user_id"], 0, $uId);        
        break;
        case "intelligent":
            if( $oi === "1") {
                // email
                require_once "PHPMailer/PHPMailer/Exception.php";
                require_once "PHPMailer/PHPMailer/PHPMailer.php";
                $m = new \PHPMailer\PHPMailer\PHPMailer();
                $e = new \PHPMailer\PHPMailer\Exception();
                $m -> addAddress( $ru[0]["email"], $ru[0]["fullname"] );
                $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
                $return -> mail = sendReservationEMail( $m, $cu, $cbt );
            } else {
                // message
                require_once( "classes/Message.php");
                $m = new \Message();
                $c = "Das Buch „$cbt” wurde durch $cu für dich reserviert und kann bis zum " . date( "d.m.Y", strtotime("+7 day", time() ) ) . " in der Bibliothek abgeholt werden.";
                $m -> newMessage( $pdo, "Buchreservierung „$bt”", $c, 0, $_SESSION["user_id"], 0, $uId);        
            }      
        break;
    }
}
/*
function informUserAboutRequest($pdo, $bookId){
    $return = new \stdClass();
    $set = parse_ini_file('../../ini/settings.ini', TRUE);
    $q = "SELECT CONCAT( firstname, ' ', lastname) AS fullname, opt_in, email FROM user WHERE id = " . $_SESSION["user_id"];
    $s = $pdo -> query( $q );
    $ru = $s -> fetchAll( PDO::FETCH_ASSOC );
    $cu = $ru[0]["fullname"];
    $oi = $ru[0]["opt_in"];
    $q = "SELECT title FROM book WHERE id = $bookId";
    $s = $pdo -> query( $q );
    $bt = $s -> fetchAll( PDO::FETCH_ASSOC );
    $cbt = $bt[0]["title"];
    switch( $set["book"]["request_message_behavior"] ) {
        case "email":
            require_once "PHPMailer/PHPMailer/Exception.php";
            require_once "PHPMailer/PHPMailer/PHPMailer.php";
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $m -> addAddress( $ru[0]["email"], $ru[0]["fullname"] );
            $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
            $return -> mail = sendRequestEMail( $m, $cu, $cbt );
        break;
        case "message":
            require_once( "classes/Message.php");
            $m = new \Message();
            $c = "Das Buch „" . $cbt . "” wurde durch durch dich erfolgreich angefordert. Du erhälst in Kürze durch die 
                    Bibliotheks-E-Mail-Adresse eine Information darüber, wann und wie Du das Buch in der Bibliothek abholen kannst.";
            $m -> newMessage( $pdo, "Buchanfrage „" . $cbt . "”", $c, 9, 0, 0, $_SESSION["user_id"] );        
        
        break;
        case "both":
            require_once "PHPMailer/PHPMailer/Exception.php";
            require_once "PHPMailer/PHPMailer/PHPMailer.php";
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $m -> addAddress( $ru[0]["email"], $ru[0]["fullname"] );
            $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
            $return -> mail = sendRequestEMail( $m, $cu, $cbt );
            // build message
            require_once( "classes/Message.php");
            $m = new \Message();
            $c = "Das Buch „" . $cbt . "” wurde durch durch dich erfolgreich angefordert. Du erhälst in Kürze durch die 
                    Bibliotheks-E-Mail-Adresse eine Information darüber, wann und wie Du das Buch in der Bibliothek abholen kannst.";
            $m -> newMessage( $pdo, "Buchanfrage „" . $cbt . "”", $c, 9, 0, 0, $_SESSION["user_id"] );        
        break;
        case "intelligent":
            if( $oi === "1") {
                // email
                require_once "PHPMailer/PHPMailer/Exception.php";
                require_once "PHPMailer/PHPMailer/PHPMailer.php";
                $m = new \PHPMailer\PHPMailer\PHPMailer();
                $e = new \PHPMailer\PHPMailer\Exception();
                $m -> addAddress( $ru[0]["email"], $ru[0]["fullname"] );
                $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
                $return -> mail = sendRequestEMail( $m, $cu, $cbt );
            } else {
                // message
                require_once( "classes/Message.php");
                $m = new \Message();
            $c = "Das Buch „" . $cbt . "” wurde durch durch dich erfolgreich angefordert. Du erhälst in Kürze durch die 
                    Bibliotheks-E-Mail-Adresse eine Information darüber, wann und wie Du das Buch in der Bibliothek abholen kannst.";
                $m -> newMessage( $pdo, "Buchanfrage „" . $cbt . "”", $c, 9, 0, 0, $_SESSION["user_id"] );        
            }      
        break;
    }
}
*/
/*
function informBibliothekarAboutRequest( $pdo, $bookId ){
    $return = new \stdClass();
    $set = parse_ini_file('../../ini/settings.ini', TRUE);
    $q = "SELECT CONCAT( firstname, ' ', lastname) AS fullname FROM user WHERE id = " . $_SESSION["user_id"];
    $s = $pdo -> query( $q );
    $ru = $s -> fetchAll( PDO::FETCH_ASSOC );
    $cu = $ru[0]["fullname"];
    $q = "SELECT title FROM book WHERE id = $bookId";
    $s = $pdo -> query( $q );
    $bt = $s -> fetchAll( PDO::FETCH_ASSOC );
    $cbt = $bt[0]["title"];
    switch( $set["book"]["request_message_behavior"] ) {
        case "email":
            require_once "PHPMailer/PHPMailer/Exception.php";
            require_once "PHPMailer/PHPMailer/PHPMailer.php";
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $m -> addAddress( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”" );
            $m -> setFrom( "info@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
            $return -> mail = sendBibliothekarRequestEMail( $m, $cu, $cbt );
        break;
        case "message":
            require_once( "classes/Message.php");
            $m = new \Message();
            $c = "Das Buch „" . $cbt . "” wurde durch durch $cu angefragt. Bitte reserviere das Buch für diesen Nutzer im Bibliotheks-Administrations-Formular.";
            $m -> newMessage( $pdo, "Buchanfrage „" . $cbt . "”", $c, 1, 0, 9, 0 );        
        
        break;
        case "both":
            require_once "PHPMailer/PHPMailer/Exception.php";
            require_once "PHPMailer/PHPMailer/PHPMailer.php";
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $m -> addAddress( "bibliothek@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”" );
            $m -> setFrom( "info@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
            $return -> mail = sendBibliothekarRequestEMail( $m, $cu, $cbt );
            // build message
            require_once( "classes/Message.php");
            $m = new \Message();
            $c = "Das Buch „" . $cbt . "” wurde durch durch $cu angefragt. Bitte reserviere das Buch für diesen Nutzer im Bibliotheks-Administrations-Formular.";
            $m -> newMessage( $pdo, "Buchanfrage „" . $cbt . "”", $c, 1, 0, 9, 0 );        
        break;
        case "intelligent":
            if( $oi === "1") {
                // email
                require_once "PHPMailer/PHPMailer/Exception.php";
                require_once "PHPMailer/PHPMailer/PHPMailer.php";
                $m = new \PHPMailer\PHPMailer\PHPMailer();
                $e = new \PHPMailer\PHPMailer\Exception();
                $m -> addAddress( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”" );
                $m -> setFrom( "info@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
                $return -> mail = sendBibliothekarRequestEMail( $m, $cu, $cbt );
            } else {
                // message
                require_once( "classes/Message.php");
                $m = new \Message();
                $c = "Das Buch „" . $cbt . "” wurde durch durch $cu angefragt. Bitte reserviere das Buch für diesen Nutzer im Bibliotheks-Administrations-Formular.";
                $m -> newMessage( $pdo, "Buchanfrage „" . $cbt . "”", $c, 1, 0, 9, 0 );        
            }      
        break;
    }
}
*/
// $m = mail, $s = settings, $c = content
function sendResetReservationLibraryMail( $m, $s, $c ) {
    $m->CharSet = "UTF-8";
    $m->Subject = 'Reservierungs-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';

    $m -> addAddress( $_SESSION["email"], $_SESSION["firstname"] . " " . $_SESSION["lastname"]);
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    
    $content = '
                            <html>
                            <head>
                                <title>Zurücksetzung deiner Buchreservierung bei der Bibliothek der Suchtselbsthilfe-„Regenbogen”-</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Zurücksetzen deiner Buchreservierung</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>
    
                            <h3>Zurücksetzen</h3>
                                
                                <p>Aus internen Gründen mussten wir deine Buchreservierung in der Bibliothek der 
                                Suchtselbsthilfe „Regenbogen” zurücksetzen. Du stehst aber jetzt in der Warteliste 
                                ganz oben und bist der Nächste für den das Buch reserviert wird. Sobald dies erfolgt 
                                ist, setzen wir dich automatisch in Kenntnis. Dies wird ungefähr 35 Tage dauern.                               
                                </p>
                                <p>

                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
    
    
}
// $m = mail, $s = settings, $c = content
// $m = mail, $s = settings, $c = content
/* depracated
function sendReservationLibraryMail( $m, $s, $c ) {
    $m->CharSet = "UTF-8";
    $m->Subject = 'Reservierungs-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';

    $m -> addAddress( $_SESSION["email"], $_SESSION["firstname"] . " " . $_SESSION["lastname"]);
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
    
    $content = '
                            <html>
                            <head>
                                <title>Reservierungs-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”-</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Reservierungs-E-Mail</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>Reservierung</h4>
                                <p>' . $c . '</p>
                                <p>

                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
    
    
}
*/
function sendReservationLibraryMailToBibliothekar( $m, $s, $c ) {
    $m->CharSet = "UTF-8";
    $m->Subject = 'Reservierungs-E-Mail an die Suchtselbsthilfe-„Regenbogen”-Bibliothek';
    $tmp = explode( ",", $s["library_form"]["email_reciver"] );
    for( $i = 0; $i < count( $tmp ); $i++ ) {
            $m -> addAddress( $tmp[0], "");        
    }    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
    
    $content = '
                            <html>
                            <head>
                                <title>Reservierungs-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”-</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Reservierungs-E-Mail</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>Reservierung durch Nutzer</h4>
                                <p>' . $c . '</p>
                                <p>

                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;    
    return $m -> send();    
}
// $m = mail, $s = settings, $c = content
function sendWaitlistLibraryMail( $m, $s, $c ) {
    
    $m->CharSet = "UTF-8";
    $m->Subject = 'Warteliste-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';

    $m -> addAddress( $_SESSION["email"], $_SESSION["firstname"] . " " . $_SESSION["lastname"]);
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
    
    $content = '
                            <html>
                            <head>
                                <title>Wartelisten-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”-</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Wartelisten-E-Mail</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>Warteliste</h4>
                                <p>' . $c . '</p>
                                <p>
                                Vorraussichtlich ist das Buch in 35 Tagen für dich verfügbar.
                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
    
}
// $m = mail, $s = settings, $c = content
function sendDeleteReservationLibraryMail( $m, $s, $c ) {
    session_start();
    $m->CharSet = "UTF-8";
    $m->Subject = 'Reservierungs-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';

//    $m -> addAddress( $_SESSION["email"], $_SESSION["firstname"] . " " . $_SESSION["lastname"]);
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
    
    $content = '
                            <html>
                            <head>
                                <title>Reservierungslösch-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”-</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Reservierungslösch-E-Mail</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>Reservierung ist gelöscht</h4>
                                <p>' . $c . '</p>
                                <p>
                                
                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
    
    
}
// $m = mail, $s = settings, $c = content
/*
function sendWaitlistReservationLibraryMail( $m, $s, $c ) {
    session_start();
    $m->CharSet = "UTF-8";
    $m->Subject = 'Reservierungs-E-Mail von der Suchtselbsthilfe-„Regenbogen”-Bibliothek';

//    $m -> addAddress( $_SESSION["email"], $_SESSION["firstname"] . " " . $_SESSION["lastname"]);
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
    
    $content = '
                            <html>
                            <head>
                                <title>Reservierungslösch-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”-</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Statusänderungs-E-Mail</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>Warteliste</h4>
                                <p>' . $c . '</p>
                                <p>
                                
                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
    
    
}
// $m = mail, $s = settings, $c = content
/*
function sendOverdueLibraryMail( $m, $s, $c ) {
    $m->CharSet = "UTF-8";
    $m->Subject = 'Ausleihzeit-überschritten-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”';
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "bibliothek@suchtselbsthilfe-regenbogen.de", "Bibliothek Suchtselbsthilfe „Regenbogen”");
    
    $content = '
                            <html>
                            <head>
                                <title>Ausleihzeit-überschritten-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Ausleihzeit-überschritten-E-Mail von der Bibliothek der Suchtselbsthilfe-„Regenbogen”</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>Ausleihzeit-überschritten</h4>
                                <p>' . $c . '</p>
                                <p>
                                
                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: bibliothek@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
    
    
}
*/
function sendRemindMeMail( $m, $s, $c ) {
    $m->CharSet = "UTF-8";
    $m->Subject = 'Erinnerungs-E-Mail der „Regenbogen”-Domain';    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $m -> setFrom( "info@suchtselbsthilfe-regenbogen.de", "Domain Suchtselbsthilfe „Regenbogen”");
                $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">
                <h3>Erinnerungs-E-Mail</h3>
                <p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                </p>
                <h4>Erinnerungs-E-Mail</h4>
                <p>Du hast in deinem Profil eingestellt, dass du bei längerer Abwesenheit vom Onlineangebot der
                Suchtselbsthilfe „Regenbogen” automatisch erinnert werden willst. Diese E-Mail lädt dich dazu ein,
                wieder einmal auf unserer Webseite vorbeizuschauen, um zu sehen, was es Neues gibt.
                </p>
                <p>&nbsp;</p>
                <p>Ihr Suchtselbsthilfe-„Regenbogen”-Team</p>
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
                ' . getEmailSignature() . '
                
                ';
    $m->Body = $content;    
    return $m -> send();   
}
function sendErrorMail( $c ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "error@suchtselbsthilfe-regenbogen.de", "Fehlermeldung Suchtselbsthilfe „Regenbogen”");
    $mail->addAddress( "easyit.leipzig@gmail.com", "easyit" );    
    $mail->addAddress( "info@suchtselbsthilfe-regenbogen.de", "Regenbogen" );    

    $mail->Subject = 'Fehlermeldungs-E-Mail - Suchtselbsthilfe „Regenbogen”';

    $mail->isHtml(true);
    
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
    $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">
                <h3>Fehlermeldungs-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>
                <p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                </p>
                <h4>Fehlermeldungs-E-Mail</h4>
                <p>
                    Es wurde ein Fehler gemeldet:
                </p>
                <p>' . $c . '</p>
                <p>Ihr Suchtselbsthilfe-„Regenbogen”-Team</p>
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
                ' . getEmailSignature() . '
                
                ';
    $mail->Body = $content;    
    return $mail -> send();   
}
function sendBackanswerEmail( $pdo, $title, $content, $toUser, $fromUser ) {
    $query = "SELECT CONCAT( firstname, ' ', lastname ) AS name, email FROM user WHERE id = " . $toUser;    
    $stm = $pdo -> query( $query );
    $result_toUser = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $query = "SELECT CONCAT( firstname, ' ', lastname ) AS name, email FROM user WHERE id = " . $fromUser;    
    $stm = $pdo -> query( $query );
    $result_fromUser = $stm -> fetchAll(PDO::FETCH_ASSOC);
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $m = new \PHPMailer\PHPMailer\PHPMailer();
    $ex = new \PHPMailer\PHPMailer\Exception();
    $m -> addAddress( $result_toUser[0]["email"], $result_toUser[0]["name"] );
    $m -> setFrom( $result_fromUser[0]["email"], $result_fromUser[0]["name"]);
    $m->CharSet = "UTF-8";
    $m->Subject = 'Rückantwort „Regenbogen”-Kleinanzeigen';
    
    $m->isHtml(true);
    $m->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    
    $content = '
                            <html>
                            <head>
                                <title>Rückantwort „Regenbogen”-Kleinanzeigen</title>
                            </head>

                            <body>

                            <img src="cid:TBP" alt="Logo" style="width: 100px;">
                            
                            <h3>Rückantwort „Regenbogen”-Kleinanzeigen</h3>

                                <p>Dies ist eine automatisch erzeugte E-Mail. Bitte antworte nicht darauf.</p>

                                <h4>' . $title . '</h4>
                                <p>' . $content . '</p>
                                <p>
                                
                                </p>
                                    <p>&nbsp;</p>
                                    <p>Ihr "Suchtselbsthilfe-Regenbogen"-Team</p>
                                    <address>
                                        <dl>
                                            <dt>E-Mail: info@suchtselbsthilfe-regenbogen.de</dt>
                                            <dt>Telefon: +49 341 444 232 2</dt>
                                            <dt>Adresse:</dt><dd>Demmeringstraße 47-49</dd>
                                            <dd>D-04177 Leipzig</dd>
                                            <dd>Germany</dd>
                                        </dl>
                                    </address>
                            ' . getEmailSignature() . '
                       </body>
                            </html>                                
                            ';                                        
                            $m->Body = $content;
        
    return $m -> send();
}

function getGermanDateFromMysql( $date, $widthMinutes = false ) {
    if( $date == null || $date == " " ) {
        return "";
    }
    if( $widthMinutes ) {
        $tmp = explode( " ", $date );
        $tmpMinutes = substr( $tmp[1], 0, 5 );
        $tmpDate = explode( "-", $tmp[0] );
        return $tmpDate[2] . "." . $tmpDate[1] . "." . $tmpDate[0] . " " . $tmpMinutes;
           
    } else {
            $tmp = explode( " ", $date );
            $tmp = explode( "-", $tmp[0] );
            return $tmp[2] . "." . $tmp[1] . "." .$tmp[0];
    }
}

function getRandomPassword( $length = 8, $alphabet= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890' ) {
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $length; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}
function replaceUnwantetChars( $value ) {
    //var_dump($value);
    $result = $value;
    if( !is_array( $value ) ) {
        $result =  str_replace( "\\", "\\\\", $value );
        $result =  str_replace( "'", "\'", $result );
    }
    return $result;    
}
function replaceQuotas( $value ) {
    $result = str_replace('"', "&quot;", $value );
    $result = str_replace("'", "&apos;", $result );
    return $result;
}
function buildExportEventFile( $db, $events, $system, $type, $kind, $userId, $reminder, $reminder_intervall ) {
    $return = new \stdClass();
    if( $system == "Windows" ) {
            $ev_str = "BEGIN:VCALENDAR\r\n";
            $ev_str .= "VERSION:2.0\r\n";
            $ev_str .= "PRODID:-//www.suchtselbsthilfe-regenbogen.de//Suchtselbsthilfe Regenbogen\r\n";
            $ev_str .= "CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Berlin
TZURL:http://tzurl.org/zoneinfo/Europe/Berlin
X-LIC-LOCATION:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19810329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19961027T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:STANDARD
TZOFFSETFROM:+005328
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:18930401T000000
RDATE:18930401T000000
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19160430T230000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19161001T010000
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:CEMT
DTSTART:19450524T010000
RDATE:19450524T010000
RDATE:19470511T020000
END:DAYLIGHT
BEGIN:DAYLIGHT
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19450924T030000
RDATE:19450924T030000
RDATE:19470629T030000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19460101T000000
RDATE:19460101T000000
RDATE:19800101T000000
END:STANDARD
END:VTIMEZONE
";
            for( $i = 0; $i < count( $events -> data ); $i++ ) {
                $ev_str .= "BEGIN:VEVENT" . "\r\n";
                $tmp = date( "Ymd", time() );
                $ev_str .= "DTSTAMP:" . $tmp . "T000000\r\n";
                //$tmp = str_replace(":", "", $events -> data[$i]["start_time"] );
                //$ev_str .= $tmp . "Z\n"; 
                $ev_str .= "UID:" . getRandomPassword( 13, "0123456789abcdef" ) . "-" . getRandomPassword( 5, "0123456789abcdef" ) . "@www.suchtselbsthilfe-regenbogen.de-" . getRandomPassword( 10, "0123456789abcdef" ) . "\r\n";
                $tmpDate = str_replace("-", "", $events -> data[$i]["start_date"] ) . "T";
                $tmp = $tmpDate . str_replace(":", "", $events -> data[$i]["start_time"] ) . "\r\n";
                $ev_str .= "DTSTART:" . $tmp;
                
                $tmp = str_replace("-", "", $events -> data[$i]["end_date"] ) . "T";
                if( $tmp == "00000000T" ) {
                    $tmp = $tmpDate;    
                }
                
                if( str_replace(":", "", $events -> data[$i]["start_time"] ) != "000000" && str_replace(":", "", $events -> data[$i]["end_time"] ) == "000000" ) {
                    $tmpHour = intval( substr( $events -> data[$i]["start_time"], 0, 2 ) ) + 1;
                    $tmpHour = substr( "0" . $tmpHour, -2 );
                    $tmpHoursFromStartDate = substr( str_replace(":", "", $events -> data[$i]["start_time"] ), 2, 4 );
                    $tmpDayFrom = intval( substr( str_replace("-", "", $events -> data[$i]["start_date"] ), 6, 2 ) ) + 1;
                    $tmpDayTo = intval( substr( str_replace("-", "", $events -> data[$i]["end_date"] ), 6, 2 ) );
                    if( $tmpDayFrom == $tmpDayTo ) {
                        $tmp = str_replace("-", "", $events -> data[$i]["start_date"] ) . "T" . $tmpHour . $tmpHoursFromStartDate . "\r\n";
                    } else {
                        $tmp = $tmp . $tmpHour . "\r\n";                        
                    }
                } else {
                    $tmp = $tmp . str_replace(":", "", $events -> data[$i]["end_time"] ) . "\r\n";
                }
                $ev_str .= "DTEND:" . $tmp;
                $ev_str .= "SUMMARY:" . $events -> data[$i]["title"] . "\r\n";
                if( $events -> data[$i]["description"] != "" ) {
                    $firststr = substr( $events -> data[$i]["description"], 0, 61 ) . "\r\n";
                    $laststr = substr( $events -> data[$i]["description"], 62 );
                    $strlen = strlen( $laststr );
                    $int = intval( $strlen / 75 + 1 );
                    $tmp = "";
                    for( $j = 0; $j < $int; $j++ ) {
                        $tmp .= substr( $laststr, $j * 75, 75 ) . "\r\n";        
                    }
                    $tmp = substr( $tmp, 0, -2 );
                    //$ev_str .= "DESCRIPTION:" . $firststr . $tmp . "\r\n";                
                    $ev_str .= "DESCRIPTION:" . $events -> data[$i]["description"] . "\r\n";                
                } else {
                    $ev_str .= "DESCRIPTION:\r\n";
                }
                if( $reminder != "" ) {
                    $ev_str .= "BEGIN:VALARM
ACTION:$reminder\r\n";
                    if( $reminder != "AUDIO" ) {
                    if( $events -> data[$i]["description"] != "" ) {
                        $firststr = substr( $events -> data[$i]["description"], 0, 61 ) . "\r\n";
                        $laststr = substr( $events -> data[$i]["description"], 62 );
                        $strlen = strlen( $laststr );
                        $int = intval( $strlen / 75 + 1 );
                        $tmp = "";
                        for( $j = 0; $j < $int; $j++ ) {
                            $tmp .= substr( $laststr, $j * 75, 75 ) . "\r\n";        
                        }
                        $tmp = substr( $tmp, 0, -2 );
                        //$ev_str .= "DESCRIPTION:" . $firststr . $tmp . "\r\n";                
                        $ev_str .= "DESCRIPTION:" . $events -> data[$i]["description"] . "\r\n";                
                    } else {
                        $ev_str .= "DESCRIPTION:\r\n";
                    }
                    }
                    if( $reminder == "EMAIL" ) {
                        $query = "SELECT email FROM user WHERE id = " . $_SESSION["user_id"];
                        $stm = $db -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $ev_str .= "SUMMARY:" . $events -> data[$i]["title"] . "\r\nATTENDEE:MAILTO:" . $result[0]["email"] . "\r\n";
                    }
                    $ev_str .= "TRIGGER:$reminder_intervall
END:VALARM\r\n";
                }
                
                
                $ev_str .= "END:VEVENT\r\n";
            }


            $ev_str .= "END:VCALENDAR\r\n";
    } else {
        
           $ev_str = "Subject,Start Date,Start Time,End Date,End Time,All Day Event,Description,Location,Private\r\n";
           for( $i = 0; $i < count( $events -> data); $i++ ) {
               $ev_str .= "\"" . $events -> data[$i]["title"] . "\"," . $events -> data[$i]["start_date"] . ","  . $events -> data[$i]["start_time"] . "," . $events -> data[$i]["end_date"] . ","  . $events -> data[$i]["end_time"] . ",False,\"" . $events -> data[$i]["description"] . "\",True\r\n";
               
           } 
           //$ev_str .= 'Test 1,05/05/2022,10:00,05/05/2022,19:00,False,"Dies ist, eine Test",Information,True' . "\r\n";
    }
    if( $system == "Windows" ) {
        copy( "tmp/export_events.ics", "tmp/export_events_" . $userId . ".ics" );
        $evFile = fopen( "tmp/export_events_" . $userId . ".ics", "w+");
        //fwrite($evFile,   chr(239) . chr(187) . chr(191) . $ev_str);
        $return -> fileName = "tmp/export_events_" . $userId . ".ics";
    } else {
        copy( "tmp/export_events.csv", "tmp/export_events_" . $userId . ".csv" );
        $evFile = fopen( "tmp/export_events_" . $userId . ".csv", "w+");
        $return -> fileName = "tmp/export_events_" . $userId . ".csv";
    }    
    fwrite( $evFile,  $ev_str );
    fclose( $evFile );
    $return -> success = true;
    $return -> message = "Der Export wurde erfolgreich erstellt."; 
    return $return;    
}
function sendInformUserDeleteEventEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email ){
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Terminlöschung/-absage Suchtselbsthilfe „Regenbogen”");
    $mail->addAddress( $email, $firstname . " " . $lastname );    

    $mail->Subject = 'Terminlöschungs/-absage-E-Mail - Suchtselbsthilfe „Regenbogen”';

    $mail->isHtml(true);
    
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">
                <h3>Terminlöschungs/-absage-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>
                <p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht darauf.
                </p>
                <h4>Terminlöschungs/-absage-E-Mail</h4>
                <p>
                    Es wurde ein Termin gelöscht:
                </p>
                <p>Der Termin ' . $evTitle . ' vom ' . getGermanDateFromMysql( $dateTime, true ) . ' wurde gelöscht.</p>
                <p>Ihr Suchtselbsthilfe-„Regenbogen”-Team</p>
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
                ' . getEmailSignature() . '
                
                ';
    $mail->Body = $content;    
    $mail -> send();   
    return;    
}
function sendUserAboutNewEventEmail( $pdo, $users, $email_content, $informMyself = true ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $email = "info@suchtselbsthilfe-regenbogen.de";
    $name = "Kalender - Suchtselbsthilfe „Regenbogen”";
    $l = count( $users )        ;
    $i = 0;
    while( $i < $l ) {
        if( !boolval( $informMyself ) && $users[$i]["id"] == $_SESSION["user_id"] ) {
        } else {
            $mail = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $mail->CharSet = "UTF-8";
            $mail->setFrom( $email, $name);
            $mail->Subject = "Termin-E-Mail - Suchtselbsthilfe „Regenbogen”";
            $mail->addAddress( $users[$i]["email"], $users[$i]["name"] );    
            $mail->isHtml(true);
            
            $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                            
            $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
            $content .= '<h3>"Neuer Termin"-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
            $content .= '<p>
                            Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                        </p>';
                $content .= "<h4>\"Neuer Termin\"</h4><p>
                         $email_content   
                        </p>";
            $content .= '                
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
                        ' . getEmailSignature() . '
                        
                        ';
            $mail->Body = $content;
            $mail -> send();
        }
        $i += 1;
    }
    return;
}
function sendUserAboutChangedEventEmail( $pdo, $users, $email_content, $informMyself = true ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $email = "info@suchtselbsthilfe-regenbogen.de";
    $name = "Kalender - Suchtselbsthilfe „Regenbogen”";
    $l = count( $users )        ;
    $i = 0;
    while( $i < $l ) {
        if( !boolval( $informMyself ) && $result[$i]["id"] == $_SESSION["user_id"] ) {
        } else {
            if( boolval( $users[$i][ "opt_in" ] ) ) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer();
                $e = new \PHPMailer\PHPMailer\Exception();
                $mail->CharSet = "UTF-8";
                $mail->setFrom( $email, $name);
                $mail->Subject = "Termin-E-Mail - Suchtselbsthilfe „Regenbogen”";
                $mail->addAddress( $users[$i]["email"], $users[$i]["name"] );    
                $mail->isHtml(true);
                
                $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                                
                $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
                $content .= '<h3>"Geänderter Termin"-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
                $content .= '<p>
                                Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                            </p>';
                    $content .= "<h4>\"Geänderter Termin\"</h4><p>
                             $email_content   
                            </p>";
                $content .= '                
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
                            ' . getEmailSignature() . '
                            
                            ';
                $mail->Body = $content;
                $mail -> send();                
            }
        }
        $i += 1;
    }
}

         
function sendInformUsersAboutNewEventEmail( $pdo, $users, $email_content ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    if( $users == "") return;
    $tmpUsers = explode( ",", $users );
    $l = count( $tmpUsers );
    $i = 0;
    while ( $i < $l ){
        $query = "SELECT user.id, CONCAT( firstname, ' ', lastname ) AS name, email FROM user WHERE id = " . $tmpUsers[$i];
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $mail = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $mail->CharSet = "UTF-8";
            $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
            $mail->Subject = "Termin-E-Mail - Suchtselbsthilfe „Regenbogen”";
            $mail->addAddress( $result[$i]["email"], $result[$i]["name"] );    
            $mail->isHtml(true);
            
            $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                            
            $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
            $content .= '<h3>"Neuer Termin"-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
            $content .= '<p>
                            Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                        </p>';
                $content .= "<h4>\"Neuer Termin\"</h4><p>
                         $email_content   
                        </p>";
            $content .= '                
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
                        ' . getEmailSignature() . '
                        
                        ';
            $mail->Body = $content;
            $mail -> send();
    
        $i += 1;
    } 
    return;
}
/*
function sendInformUsersAboutChangedEventEmail( $pdo, $users, $email_content ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $tmpUsers = explode( ",", $users );
    $l = count( $tmpUsers );
    $i = 0;
    while ( $i < $l ){
        $query = "SELECT user.id, CONCAT( firstname, ' ', lastname ) AS name, email FROM user WHERE id = " . $tmpUsers[$i];
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $mail = new \PHPMailer\PHPMailer\PHPMailer();
            $e = new \PHPMailer\PHPMailer\Exception();
            $mail->CharSet = "UTF-8";
            $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Suchtselbsthilfe „Regenbogen”");
            $mail->Subject = "Termin-E-Mail - Suchtselbsthilfe „Regenbogen”";
            $mail->addAddress( $result[$i]["email"], $result[$i]["name"] );    
            $mail->isHtml(true);
            
            $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                            
            $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
            $content .= '<h3>"Geänderter Termin"-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
            $content .= '<p>
                            Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
                        </p>';
                $content .= "<h4>\"Geänderter Termin\"</h4><p>
                         $email_content   
                        </p>";
            $content .= '                
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
                        ' . getEmailSignature() . '
                        
                        ';
            $mail->Body = $content;
            $mail -> send();
    
        $i += 1;
    } 

}
*/
function sendUserAboutSAndAAnswerEmail( $email, $name, $email_content) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "„Spontan- und Aktiv” Suchtselbsthilfe „Regenbogen”");
    $mail->Subject = "„Spontan- und Aktiv”-E-Mail - Suchtselbsthilfe „Regenbogen”";
    $mail->addAddress( $email, $name );    

    $mail->isHtml(true);
    
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
    $content .= '<h3>"Spontan- und Aktiv"-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
    $content .= '<p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht darauf.
                </p>';
        $content .= "<h4>\"Spontan- und Aktiv\"</h4><p>
                 $email_content   
                </p>";
    $content .= '                
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
                ' . getEmailSignature() . '
                
                ';
    $mail->Body = $content;
    return $mail -> send();    
}
function informUserAboutSAndAAnswer( $pdo, $sAndAId, $answerId, $user_id, $message_behavior ) {
    $query = "SELECT CONCAT(firstname, ' ', lastname) AS name, email FROM user WHERE id = $user_id";
    $stm = $pdo -> query( $query );
    $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $fromUser = $result[0]["name"];
    $fromEmail = $result[0]["email"];
    $query = "SELECT s_and_a.*, CONCAT( firstname, ' ', lastname) AS name, email, opt_in FROM `s_and_a`, user 
                WHERE s_and_a.id = $sAndAId AND `user_id` = user.id";
    $stm = $pdo -> query( $query );
    $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $toUser = $result[0]["name"];
    $toEmail = $result[0]["email"];
    $opt_in = $result[0]["opt_in"]; 
    $toDate = date( "d.m.Y", time());
    $title = $result[0]["title"];
    $content = "Am $toDate hat $fromUser auf deine „Sponatan und Aktiv”-Anzeige „" . $title . "” geantwortet";
    switch( $message_behavior ) {
        case "both":
                    sendUserAboutSAndAAnswerEmail( $toEmail, $toUser, $content );
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Antwort auf „" . $title . "”", $content, 0, $user_id, 0, $result[0]["user_id"]);        
        break;
        case "email":
                    sendUserAboutSAndAAnswerEmail( $toEmail, $toUser, $content );
        break;
        case "message":
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Antwort auf „" . $title . "”", $content, 0, $user_id, 0, $result[0]["user_id"]);        
        break;
        case "intelligent":
                    if( $opt_in == 1 ) {
                        sendUserAboutSAndAAnswerEmail( $toEmail, $toUser, $content );
                    } else {
                        require_once( "classes/Message.php");
                        $m = new \Message();
                        $m -> newMessage( $pdo, "Antwort auf „" . $title . "”", $content, 0, $user_id, 0, $result[0]["user_id"]);        
                    }
        break;
    }
}
function informUserAboutSAndABackanswer( $pdo, $sAndAId, $answerId, $user_id, $message_behavior ) {
    
}
function sendInformCreatorSAndAEmail( $name, $email, $email_content ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "„Spontan- und Aktiv” Suchtselbsthilfe „Regenbogen”");
    $mail->Subject = "„Spontan- und Aktiv”-E-Mail - Suchtselbsthilfe „Regenbogen”";
    $mail->addAddress( $email, $name );    

    $mail->isHtml(true);
    
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
    $content .= '<h3>"Spontan- und Aktiv"-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
    $content .= '<p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht darauf.
                </p>';
        $content .= "<h4>\"Spontan- und Aktiv\"</h4><p>
                 $email_content   
                </p>";
    $content .= '                
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
                ' . getEmailSignature() . '
                
                ';
    $mail->Body = $content;
    return $mail -> send();
}
function sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, $participate ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    if( $participate ) {
        $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Terminzusage Suchtselbsthilfe „Regenbogen”");
        $mail->Subject = "Terminzusage-E-Mail - Suchtselbsthilfe „Regenbogen”";
    } else {
        $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Terminabsage Suchtselbsthilfe „Regenbogen”");
        $mail->Subject = "Terminabsage-E-Mail - Suchtselbsthilfe „Regenbogen”";
    }
    $mail->addAddress( $email, $firstname . " " . $lastname );    

    $mail->isHtml(true);
    
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
    if( $participate ) {
        $content .= '<h3>Terminzusage-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
    } else {
        $content .= '<h3>Termiabsage-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
    }
    $content .= '<p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht darauf.
                </p>';
    if( $participate ) {
        $content .= '<h4>Terminzusage-E-Mail</h4><p>
                    Es wurde eine Terminzusage gemacht.
                </p>';     
    } else {
        $content .= '<h4>Termiabsage-E-Mail</h4><p>
                    Es wurde eine Terminabsage gemacht.
                </p>';     
    }
    $content .= $email_content . '                
                <p>Ihr Suchtselbsthilfe-„Regenbogen”-Team</p>
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
                ' . getEmailSignature() . '
                
                ';
    $mail->Body = $content;    
    $mail -> send();   
    return;    
}
function sendInformParticipantsAboutChangedEvent( $email_content, $name, $email ) {
    require_once "PHPMailer/PHPMailer/Exception.php";
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $e = new \PHPMailer\PHPMailer\Exception();
    $mail->CharSet = "UTF-8";
    $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Terminänderung Suchtselbsthilfe „Regenbogen”");
    $mail->Subject = "Terminänderung-E-Mail - Suchtselbsthilfe „Regenbogen”";
    $mail->addAddress( $email, $name );    

    $mail->isHtml(true);
    
    $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
                    
    $content = '<img src="cid:TBP" alt="Logo" style="width: 150px">';
    $content .= '<h3>Terminänderung-E-Mail der Suchtselbsthilfe „Regenbogen”</h3>';     
    $content .= '<p>
                    Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht darauf.
                </p>';
        $content .= '<h4>Terminänderung</h4><p>
                    Es wurde eine Terminänderung durchgeführt.
                </p>
                <p>';
    $content .= $email_content . '                
                </p>
                <p>Ihr Suchtselbsthilfe-„Regenbogen”-Team</p>
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
                ' . getEmailSignature() . '
                
                ';
    $mail->Body = $content;    
    $mail -> send();   
    return;    
    
}

function informUserDeleteEvent( $pdo, $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $opt_in, $message_behavior ) {
    switch( $message_behavior ) {
        case "both":
                    sendInformUserDeleteEventEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email );
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Terminlöschung/-absage", 'Der Termin ' . $evTitle . ' vom ' . getGermanDateFromMysql( $dateTime, true ) . ' wurde gelöscht/abgesagt.', 1, 0, 0, $user_id);        
        break;
        case "email":
                    sendInformUserDeleteEventEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email );
        break;
        case "message":
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Terminlöschung/-absage", 'Der Termin ' . $evTitle . ' vom ' . getGermanDateFromMysql( $dateTime, true ) . ' wurde gelöscht/abgesagt.', 1, 0, 0, $user_id);        
        break;
        case "intelligent":
                    if( $opt_in == 1 ) {
                        sendInformUserDeleteEventEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email );
                    } else {
                    require_once( "classes/Message.php");
                    $m = new \Message();
                        $m -> newMessage( $pdo, "Terminlöschung/-absage", 'Der Termin ' . $evTitle . ' vom ' . getGermanDateFromMysql( $dateTime, true ) . ' wurde gelöscht/abgesagt.', 1, 0, 0, $user_id);                        
                    }
        break;
    }
}
function sendNewsMessageEmail( $pdo, $user_id, $messNewsId, $table, $title, $contentNewsMess ) {
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    require_once "PHPMailer/PHPMailer/Exception.php";
    
    $query_user = "SELECT to_user, concat(firstname, ' ', lastname) as username, email FROM " . $table . "_user, user WHERE user.id = $user_id AND " . $table . "_user.to_user = user.id AND user.opt_in = 1 AND from_" . $table . " = " . $messNewsId;
    
    
    $stm_email = $pdo -> query( $query_user );
    $result_email = $stm_email -> fetchAll(PDO::FETCH_ASSOC);
    
    if( count( $result_email ) > 0 ) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail -> addAddress( $result_email[0]["email"], $result_email[0]["username"] );
        $mail->CharSet = "UTF-8";
        $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Meldungs-/News-E-Mail „Regenbogen”");
        if( $table == "message" ) {
            $mail->Subject = 'Meldungs-E-Mail Suchtselbsthilfe „Regenbogen”';    
        } else {
            $mail->Subject = 'News-E-Mail Suchtselbsthilfe „Regenbogen”';    
        }
        

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
        <p>Titel: " . $title . "</p>
        <p>Inhalt: " . $contentNewsMess . "</p>
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
        $mail->Send();                                
    }
}
function sendNewPinwandEmail( $pdo, $user_id, $title ) {
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    require_once "PHPMailer/PHPMailer/Exception.php";
    
    $query_user = "SELECT concat(firstname, ' ', lastname) as username, email FROM user WHERE id = " . $user_id;
    
    
    $stm_email = $pdo -> query( $query_user );
    $result_email = $stm_email -> fetchAll(PDO::FETCH_ASSOC);
    
    if( count( $result_email ) > 0 ) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail -> addAddress( $result_email[0]["email"], $result_email[0]["username"] );
        $mail->CharSet = "UTF-8";
        $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Neue Anzeige-E-Mail „Regenbogen”");
        $mail->Subject = 'Neue Anzeige-E-Mail „Regenbogen”';    
        $mail->isHtml(true);
        $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
        $content = "<img src='cid:TBP' alt='Logo' style='width: 150px'>";
        $content .= "
        <h3>Neue oder geänderte Pinnwandanzeige</h3>
        <p>
            Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
        </p>
        <h4>Pinnwand</h4>
        <p>Es wurde die Anzeige „" . $title . "” neu geschaltet oder geändert. </p>
        <p>Vielleicht ist das was für dich.</p>
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
        $mail->Send();                                
    }
}
function informUserAboutDeletion( $pdo, $evPartId ) {
    $return = new \stdClass();
    require_once "PHPMailer/PHPMailer/PHPMailer.php";
    require_once "PHPMailer/PHPMailer/Exception.php";
    
    $query_user = "SELECT user_id, CONCAT( firstname, ' ', lastname ) AS name, email, opt_in FROM event_participate, user WHERE event_participate.user_id = user.id AND event_participate.id = $evPartId";
    $query_event = "SElECT title, start_date FROM event, event_participate WHERE event_participate.event_id = event.id AND event_participate.id = $evPartId";
    
    $stm_email = $pdo -> query( $query_user );
    $result_email = $stm_email -> fetchAll(PDO::FETCH_ASSOC);
    $stm_event = $pdo -> query( $query_event );
    $result_event = $stm_event -> fetchAll(PDO::FETCH_ASSOC);
    if( count( $result_email ) > 0 && boolval( $result_email[0]["opt_in"] ) ) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail -> addAddress( $result_email[0]["email"], $result_email[0]["name"] );
        $mail->CharSet = "UTF-8";
        $mail->setFrom( "info@suchtselbsthilfe-regenbogen.de", "Te3rminteilnahmelöschungs-E-Mail „Regenbogen”");
        $mail->Subject = 'Terminteilnahme gelöscht - Kalender „Regenbogen”';    
        $mail->isHtml(true);
        $mail->AddEmbeddedImage('../images/logo.png', 'TBP', 'logo.png');
        $content = "<img src='cid:TBP' alt='Logo' style='width: 150px'>";
        $content .= "
        <h3>Terminteilnahme gelöscht - Kalender „Regenbogen”</h3>
        <p>
            Dies ist eine automatisch generierte E-Mail. Bitte antworte nicht darauf.
        </p>
        <h4>Terminteilnahme</h4>
        <p>Du wurdest durch " . $_SESSION["firstname"] . " " . $_SESSION["lastname"] . " vom Termin „" . $result_event[0]["title"] . "” am " . getGermanDateFromMysql( $result_event[0]["start_date"] ) . " gelöscht. Bitte aktualisiere Deine Termine in Deiner Termin-App.</p>
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
        $mail->Send();                                
    }
    require_once( "classes/Message.php" );
    $m = new \Message();
    $content = "Du wurdest durch " . $_SESSION["firstname"] . " " . $_SESSION["lastname"] . " vom Termin „" . $result_event[0]["title"] . "” am " . getGermanDateFromMysql( $result_event[0]["start_date"] ) . " gelöscht. Bitte aktualisiere Deine Termine in Deiner Termin-App.";
    $m -> newMessage( $pdo, "Terminteilnahme gelöscht", $content, 0, $_SESSION["user_id"], 0, $result_email[0]["user_id"] );
    $return -> result = true;
    $return -> message = "Die Terminteilnahme wurde erfolgreich gelöscht";
    return $return;
}
function informEventCreatorParticipate( $pdo, $user_id, $event_id, $firstname, $lastname, $email, $opt_in, $message_behavior, $informMyself=true){
    $query = "SELECT CONCAT( firstname, ' ', lastname ) AS name FROM user WHERE id = " . $_SESSION["user_id"];
    $stm = $pdo -> query( $query );
    $result_participator = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $query = "SELECT CONCAT( firstname, ' ', lastname ) AS name FROM user, event_participate WHERE user.id = event_participate.user_id AND event_id = $event_id";
    $stm = $pdo -> query( $query );
    $result_otherParticipators = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $query = "SELECT * FROM event WHERE id = " . $event_id;
    $stm = $pdo -> query( $query );
    $result_event = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $evTitle = "„" . $result_event[0]["title"] . "”";
    $dateTime = getGermanDateFromMysql( $result_event[0]["start_date"] ) . " " . $result_event[0]["start_time"]; 
    
    switch( $message_behavior ) {
        case "both":
                    $email_content = "<p>Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime zugesagt.</p>";
                    $email_content .= "<p>Es nehmen nun teil:</p>";
                    $email_content .= "<ul>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $email_content .= "<li>" . $result_otherParticipators[$i]["name"] . "</li>";                        
                    }
                    $email_content .= "</ul>";
                    sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, true );
                    $message_content = "Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime zugesagt.<br>";
                    $message_content .= "Es nehmen nun teil:<br>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $message_content .= " * " . $result_otherParticipators[$i]["name"] . "<br>";                        
                    }                    
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Terminzusage", $message_content, 0, $_SESSION["user_id"], 0, $user_id, $informMyself);        
        break;
        case "email":
                    $email_content = "<p>Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime zugesagt.</p>";
                    $email_content .= "<p>Es nehmen nun teil:</p>";
                    $email_content .= "<ul>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $email_content .= "<li>" . $result_otherParticipators[$i]["name"] . "</li>";                        
                    }
                    $email_content .= "</ul>";
                    sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, true );
        break;
        case "message":
                    $message_content = "Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime zugesagt.<br>";
                    $message_content .= "Es nehmen nun teil:<br>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $message_content .= " * " . $result_otherParticipators[$i]["name"] . "<br>";                        
                    }                    
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Terminzusage", $message_content, 0, $_SESSION["user_id"], 0, $user_id, $informMyself);        
        break;
        case "intelligent":
                    if( $opt_in == 1 ) {
                        $email_content = "<p>Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime zugesagt.</p>";
                        $email_content .= "<p>Es nehmen nun teil:</p>";
                        $email_content .= "<ul>";
                        for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                            $email_content .= "<li>" . $result_otherParticipators[$i]["name"] . "</li>";                        
                        }
                        $email_content .= "</ul>";
                        sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, true );
                    } else {
                        $message_content = "Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime zugesagt.<br>";
                        $message_content .= "Es nehmen nun teil:<br>";
                        for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                            $message_content .= " * " . $result_otherParticipators[$i]["name"] . "<br>";                        
                        }                    
                        require_once( "classes/Message.php");
                        $m = new \Message();
                    $m -> newMessage( $pdo, "Terminzusage", $message_content, 0, $_SESSION["user_id"], 0, $user_id, $informMyself);        
                    }
        break;
    }   
}
function informEventCreatorDeteteParticipate( $pdo, $user_id, $event_id, $firstname, $lastname, $email, $opt_in, $message_behavior){
    $query = "SELECT CONCAT( firstname, ' ', lastname ) AS name FROM user WHERE id = " . $_SESSION["user_id"];
    $stm = $pdo -> query( $query );
    $result_participator = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $query = "SELECT CONCAT( firstname, ' ', lastname ) AS name FROM user, event_participate WHERE user.id = event_participate.user_id AND event_id = $event_id";
    $stm = $pdo -> query( $query );
    $result_otherParticipators = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $query = "SELECT * FROM event WHERE id = " . $event_id;
    $stm = $pdo -> query( $query );
    $result_event = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $evTitle = "„" . $result_event[0]["title"] . "”";
    $dateTime = getGermanDateFromMysql( $result_event[0]["start_date"] ) . " " . $result_event[0]["start_time"]; 
    
    switch( $message_behavior ) {
        case "both":
                    $email_content = "<p>Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime abgesagt.</p>";
                    $email_content .= "<p>Es nehmen nun teil:</p>";
                    $email_content .= "<ul>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $email_content .= "<li>" . $result_otherParticipators[$i]["name"] . "</li>";                        
                    }
                    $email_content .= "</ul>";
                    sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, false );
                    $message_content = "Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime abgesagt.<br>";
                    $message_content .= "Es nehmen nun teil:<br>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $message_content .= " * " . $result_otherParticipators[$i]["name"] . "<br>";                        
                    }                    
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Terminabsage", $message_content, 0, $_SESSION["user_id"], 0, $user_id );
        break;
        case "email":
                    $email_content = "<p>Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime abgesagt.</p>";
                    $email_content .= "<p>Es nehmen nun teil:</p>";
                    $email_content .= "<ul>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $email_content .= "<li>" . $result_otherParticipators[$i]["name"] . "</li>";                        
                    }
                    $email_content .= "</ul>";
                    sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, false );
        break;
        case "message":
                    $message_content = "Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime abgesagt.<br>";
                    $message_content .= "Es nehmen nun teil:<br>";
                    for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                        $message_content .= " * " . $result_otherParticipators[$i]["name"] . "<br>";                        
                    }                    
                    require_once( "classes/Message.php");
                    $m = new \Message();
                    $m -> newMessage( $pdo, "Terminabsage", $message_content, 0, $_SESSION["user_id"], 0,  $user_id );        
        break;
        case "intelligent":
                    if( $opt_in == 1 ) {
                        $email_content = "<p>Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime abgesagt.</p>";
                        $email_content .= "<p>Es nehmen nun teil:</p>";
                        $email_content .= "<ul>";
                        for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                            $email_content .= "<li>" . $result_otherParticipators[$i]["name"] . "</li>";                        
                        }
                        $email_content .= "</ul>";
                        sendInformCreatorEmail( $evTitle, $dateTime, $user_id, $firstname, $lastname, $email, $email_content, false );
                    } else {
                        $message_content = "Der Nutzer „" . $result_participator[0]["name"] . "” hat dem Termin $evTitle am/um $dateTime abgesagt.<br>";
                        $message_content .= "Es nehmen nun teil:<br>";
                        for( $i = 0; $i < count( $result_otherParticipators ); $i++ ) {
                            $message_content .= " * " . $result_otherParticipators[$i]["name"] . "<br>";                        
                        }                    
                        require_once( "classes/Message.php");
                        $m = new \Message();
                        $m -> newMessage( $pdo, "Terminabsage", $message_content, 0, $_SESSION["user_id"], 0,  $user_id );        
                    }
        break;
    }   
}
function informParticipantsAboutChangedEvent( $pdo, $id, $content, $message_behavior, $valid_to, $informMyself = "true" ) {
    $query = "SELECT user.id, CONCAT( firstname, ' ', lastname ) AS name, email FROM event_participate, user WHERE user_id = user.id AND event_id = $id";
    $stm = $pdo -> query( $query );
    $result_user = $stm -> fetchAll(PDO::FETCH_ASSOC);
    $l = count( $result_user );
    $i = 0;
    while( $i < $l ) {
        switch( $message_behavior ) {
            case "both":
                        sendInformParticipantsAboutChangedEvent( $content, $result_user[$i]["name"], $result_user[$i]["email"] );
                        require_once( "classes/Message.php");
                        $m = new \Message();
                        $m -> newMessage( $pdo, "Terminänderung", $content, 1, 0, 0, $result_user[$i]["id"], $valid_to );        
            break;
            case "email":
                        sendInformParticipantsAboutChangedEvent( $content, $result_user[$i]["name"], $result_user[$i]["email"] );
            break;
            case "message":
                        $m = new \Message();
                        $m -> newMessage( $pdo, "Terminänderung", $content, 1, 0, 0, $result_user[$i]["id"], $valid_to );        
            break;
            case "intelligent":
                        if( $opt_in == 1 ) {
                            sendInformParticipantsAboutChangedEvent( $content, $result_user[$i]["name"], $result_user[$i]["email"] );
                        } else {
                            $m = new \Message();
                            $m -> newMessage( $pdo, "Terminänderung", $content, 1, 0, 0, $result_user[$i]["id"], $valid_to );        
                        }
            break;
        }   
        $i += 1;
    }
    
}
function delteOrphansFromMessage( $pdo ) {
    $query = "DELETE FROM message WHERE id NOT IN ( SELECT from_message FROM message_user );";
    $pdo -> query( $query );
    $query = "DELETE FROM message_user WHERE from_message NOT IN ( SELECT id FROM message );";
    $pdo -> query( $query );
    $query = "SELECT id FROM message";
    $stm = $pdo -> query( $query );
    $result_Messages = $stm -> fetchAll(PDO::FETCH_ASSOC);
    for( $i = 0; $i < count( $result_Messages ); $i++ ) {
        $query = "SELECT id FROM message_user WHERE is_read = 1 AND from_message = " . $result_Messages[$i]["id"];
        $stm = $pdo -> query( $query );
        $result_isReadMessages = $stm -> fetchAll(PDO::FETCH_ASSOC);
        $query = "SELECT id FROM message_user WHERE from_message = " . $result_Messages[$i]["id"];
        $stm = $pdo -> query( $query );
        $result_allMessages = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result_isReadMessages ) == count( $result_allMessages ) ) {
            $query = "DELETE FROM message WHERE id = " . $result_Messages[$i]["id"];
            $pdo -> query( $query );
            $query = "DELETE FROM message_user WHERE from_message = " . $result_Messages[$i]["id"];
            $pdo -> query( $query );
        }        
    }
}
function delteOrphansFromNews( $pdo ) {
    $query = "DELETE FROM news WHERE id NOT IN ( SELECT from_news FROM news_user );";
    $pdo -> query( $query );
    $query = "DELETE FROM news_user WHERE from_news NOT IN ( SELECT id FROM news );";
    $pdo -> query( $query );
    $query = "SELECT id FROM news";
    $stm = $pdo -> query( $query );
    $result_News = $stm -> fetchAll(PDO::FETCH_ASSOC);
    for( $i = 0; $i < count( $result_News ); $i++ ) {
        $query = "SELECT id FROM news_user WHERE from_news = " . $result_News[$i]["id"];
        $stm = $pdo -> query( $query );
        $result_emptyNews = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result_emptyNews ) == 0 ) {
            $query = "DELETE FROM news WHERE id = " . $result_News[$i]["id"];
            $pdo -> query( $query );
        }
    }
    for( $i = 0; $i < count( $result_News ); $i++ ) {
        $query = "SELECT id FROM news_user WHERE is_read = 1 AND from_news = " . $result_News[$i]["id"];
        $stm = $pdo -> query( $query );
        $result_isReadNews = $stm -> fetchAll(PDO::FETCH_ASSOC);
        $query = "SELECT id FROM news_user WHERE from_news = " . $result_News[$i]["id"];
        $stm = $pdo -> query( $query );
        $result_allNews = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result_isReadNews ) == count( $result_allNews ) ) {
            $query = "DELETE FROM news WHERE id = " . $result_News[$i]["id"];
            $pdo -> query( $query );
            $query = "DELETE FROM news_user WHERE from_news = " . $result_News[$i]["id"];
            $pdo -> query( $query );
        }        
    }
}
function getCountMessagesNews( $pdo, $m, $n ) {
    $return = new \stdClass();
    $r = $m -> getCountMessagesPerUser( $pdo, $_SESSION["user_id"] );
    $return -> countMessages = $r -> count_records;
    $r = $n -> getCountNewsPerUser( $pdo, $_SESSION["user_id"] );
    $return -> countNews = $r -> count_records;
    return $return;
}
function getRecordForId( $Id, $data ) {
    $l = count( $data );
    $i = 0;
    while ( $i < $l ){
        if( $data[$i]["id"] == $Id ) {
            return $data[$i];    
        }
        $i += 1;
    }
    return false;
}
function getDirContent($directory, $sorting_order=0) {
    if(!is_dir($directory)) {
        return false; 
    }
    $files = array();
    $handle = opendir($directory);
    while (false !== ($filename = readdir($handle))) {
        if( filetype($filename) != "dir" ) {
            $files[] = $filename; 
        }
    }
    closedir($handle);
    if($sorting_order == 1) {
        rsort($files); 
    } else {
        sort($files); 
    }
    return $files;
} 
function add_slashes_recursive( $variable )
{
    if ( is_string( $variable ) )
        return addslashes( $variable ) ;

    elseif ( is_array( $variable ) )
        foreach( $variable as $i => $value )
            $variable[ $i ] = add_slashes_recursive( $value ) ;

    return $variable ;
}
?>
