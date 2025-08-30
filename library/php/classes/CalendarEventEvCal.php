<?php
class CalendarEvent {
    private function getAppendixForEvent( $id ) {
        $fText = "";
        $files = glob( "../cal/cal_ev_$id*.*" );
        $fText = implode( "|", $files );
        $fText = str_replace( "..", "library", $fText);
        return $fText;
    }    
    private function getInformUser( $pdo, $id, $informRole, $informUser, $groupId ) {
        $return = new \stdClass();
        $q = "select concat( '', user_id) as user_id from account where role_id = $informRole";
        $s = $pdo -> query( $q );
        $r_role = $s -> fetchAll( PDO::FETCH_ASSOC );
        $userForRole = assocArrToFlat( $r_role, "user_id", ",", false );
        if( $userForRole = ["0"]) $userForRole = [];
        $userForRole = array_merge( $userForRole, $informUser );
        $userForRole = array_unique( $userForRole );
        if( $groupId == "0" ) {
            $q = "select user_id from event_participate where event_id = $id";
        } else {
            $q = "select DISTINCT(user_id) from event_participate, event where event.id = event_participate.event_id and group_id = $groupId";            
        }
        $s = $pdo -> query( $q );
        $r_partUser = $s -> fetchAll( PDO::FETCH_ASSOC );
        $r_partUser = assocArrToFlat( $r_partUser, "user_id", ",", false );
        if( count( $userForRole ) !== 0 ) {
            $userForRole = array_diff( $userForRole, $r_partUser );    
        }
        $return -> userForRole = implode( ",", $userForRole );
        $return -> userForPart = implode( ",", $r_partUser );
        if( count( $userForRole ) > 0 ) {
            $return -> userAll = implode( ",", $userForRole ) . "," . $return -> userForPart;
        } else {
            $return -> userAll = $return -> userForPart;
        }
        if( $return -> userAll == "," ) $return -> userAll = "";       
        return $return;
    }    
    public function getEventForId( $pdo, $id ) {
        $query = "SELECT * FROM event where id = $id";
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        $i = 0;
        $ev = new \stdClass();
        $ev -> title = $result[$i]["title"];
        $ev -> start = $result[$i]["start_date"] . "T" . $result[$i]["start_time"];
        $ev -> end = $result[$i]["end_date"] . "T" . $result[$i]["end_time"];
        $ev -> display = "auto";
        $exPro = new \stdClass();
        $exPro -> id = $result[$i]["id"];
        $exPro -> groupId = $result[$i]["group_id"];
        $exPro -> place = $result[$i]["place"];
        $exPro -> registration_deadline = $result[$i]["registration_deadline"];
        $exPro -> url = $result[$i]["url"];
        $exPro -> inner_url = $result[$i]["inner_url"];
        $exPro -> inner_url_text = $result[$i]["inner_url_text"];
        $exPro -> description = $result[$i]["description"];
        $exPro -> notice = $result[$i]["notice"];
        $exPro -> class = $result[$i]["class"];
        $exPro -> creator = $result[$i]["creator"];
        $q = "select * from event_participate where event_id = " . $result[$i]["id"] . " and user_id = $userId";
        $s = $pdo -> query( $q );
        $result_part = $s -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result_part ) > 0 ) {
            if( $result_part[0]["user_id"] > 0 ) $exPro -> participate = true;
            $exPro -> remindMe = $result_part[0]["remind_me"];
            $exPro -> countPart = $result_part[0]["count_part"];
            $exPro -> participateAs = $result_part[0]["count_part"];
        } else {
            $exPro -> participate = null;
            $exPro -> remindMe = null;
            $exPro -> countPart = null;
            $exPro -> participateAs = null;            
        }
        $exPro -> appendix = $this -> getAppendixForEvent( $id );
        $ev -> extendedProps = $exPro;
        return $ev;        

    }
    public function getEventsForView( $pdo, $startDate, $endDate, $userId ) {
        $query = "SELECT * FROM event where start_date >= '$startDate' and end_date <= '$endDate'";
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        $events = [];
        $l = count( $result );
        $i = 0;
        while( $i < $l ) {
            $ev = new \stdClass();
            $ev -> title = $result[$i]["title"];
            $ev -> start = $result[$i]["start_date"] . "T" . $result[$i]["start_time"];
            $ev -> end = $result[$i]["end_date"] . "T" . $result[$i]["end_time"];
            if( $result[$i]["start_time"] === "00:00:00" ) {
                $ev -> allDay = true;
            } else {
                $ev -> allDay = false;
            }
            $ev -> display = "auto";
            $exPro = new \stdClass();
            $exPro -> id = $result[$i]["id"];
            $exPro -> groupId = $result[$i]["group_id"];
            $exPro -> place = $result[$i]["place"];
            $exPro -> registration_deadline = $result[$i]["registration_deadline"];
            $exPro -> url = $result[$i]["url"];
            $exPro -> inner_url = $result[$i]["inner_url"];
            $exPro -> inner_url_text = $result[$i]["inner_url_text"];
            $exPro -> description = $result[$i]["description"];
            $exPro -> notice = $result[$i]["notice"];
            $exPro -> class = "fc-" . $result[$i]["category"];
            $exPro -> creator = $result[$i]["creator"];
            $q = "select * from event_participate where event_id = " . $result[$i]["id"] . " and user_id = $userId";
            $s = $pdo -> query( $q );
            $result_part = $s -> fetchAll(PDO::FETCH_ASSOC);
            if( count( $result_part ) > 0 ) {
                if( $result_part[0]["user_id"] > 0 ) $exPro -> participate = true;
                if( $result_part[0]["remind_me"] == 1 ) {
                    $exPro -> remindMe = true;
                } else {
                    $exPro -> remindMe = false;    
                }
                
                $exPro -> countPart = $result_part[0]["count_part"];
                $exPro -> participateAs = $result_part[0]["role_id"];
            } else {
                $exPro -> participate = false;
                $exPro -> remindMe = false;
                $exPro -> countPart = 1;
                $exPro -> participateAs = 0;
                
            }
            $exPro -> appendixNames = $result[$i]["appendix_names"];
            $exPro -> appendix = $this -> getAppendixForEvent( $exPro -> id );
            $ev -> extendedProps = $exPro;
            $events[] = $ev;
            $i += 1;
        }
        
        return $events;        
    }
    public function saveEvent( $pdo, $id, $group_id, $title, $start_date, $end_date, $start_time, 
                    $end_time, $url, $description, $notice, $place, $format, $deadline, $inner_url = "", $inner_url_text = "", $creator = 0, $count_part = 1 ){
        $return = new \stdClass();
        if( $group_id == "") {
            $group_id = 0;
        }
        $query = "UPDATE event SET group_id = $group_id, title = '$title', start_date = '$start_date', end_date = '$end_date', 
                    start_time = '$start_time', end_time = '$end_time', url = '$url', description = '$description', notice = '$notice', 
                    place = '$place', class = '$format', registration_deadline = '$deadline', inner_url = '$inner_url', 
                    inner_url_text = '$inner_url_text', creator = $creator  WHERE id = $id ";
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            if( $inner_url != "" ) {
                $tmp = explode( "/", $inner_url );
                $fname = $tmp[ count( $tmp ) - 1 ];
                if( file_exists( "../documents/$fname") ) {
                    $tmpExt = explode( ".", $fname );
                    $ext = $tmpExt[ count( $tmpExt) - 1 ];
                    $newFileName = "cal_ev_appendix_edit_" . $id . "_" . time() . "." . $ext;
                    rename( "../documents/$fname", "../documents/$newFileName" );
                    $query = "UPDATE event set inner_url = 'library/documents/$newFileName' WHERE id = " . $id;
                    $pdo->query( $query );
                }                
            } else {
                $query = "UPDATE event set inner_url = '', inner_url_text = '' WHERE id = " . $id;
                $pdo->query( $query );                
            }
            $return -> message = "Der Termin wurde erfolgreich gespeichert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Speichern des Termins ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    
    
    }
    public function saveEventByJson( $pdo, $ev ) {
        
    }
    
    
    public function showDialogParticipate( $pdo, $id ) {
        $result = new \stdClass();
        $q = "select sum(count_part) as count_part from event_participate where event_id = $id";
        $s = $pdo -> query( $q );
        $r = $s -> fetchAll( PDO::FETCH_ASSOC );
        if( count( $r ) > 0 ) {
            $result -> countPart = $r[0]["count_part"];
        } else {
            $result -> countPart = "";
        }
        $q = "select concat( lastname, ', ', firstname) as fullname, count_part from user, event_participate where user.id = event_participate.user_id and event_id = $id";
        $s = $pdo -> query( $q );
        $r = $s -> fetchAll( PDO::FETCH_ASSOC );
        $result -> participants = $r;
        $q = "select title from event where id = $id";
        $s = $pdo -> query( $q );
        $r = $s -> fetchAll( PDO::FETCH_ASSOC );
        $result -> title = $r[0]["title"];
        return $result;
    }  
    public function setParticipate( $pdo, $id, $userId, $participate, $participateAs, $remindMe, $countPart, $elId ) {
        $return = new \stdClass();
        $return -> success = true;
        try {
            $q = "select title, creator, start_date, start_time from event where id = $id";
            $s = $pdo -> query( $q );
            $r_event = $s -> fetchAll( PDO::FETCH_ASSOC );
            $q = "select concat( firstname, ' ', lastname ) as fullname from user where id = $userId";
            $s = $pdo -> query( $q );
            $r_user = $s -> fetchAll( PDO::FETCH_ASSOC );
            require_once( "InformUser.php" );
            $iu = new \InformUser( $pdo, "both", 27, 0, 0, $r_event[0]["creator"] );
            if( $participate === "1" ) {
            // participate
            // check for existing record
                $q = "select id from event_participate where event_id = $id and user_id = $userId";
                $s = $pdo -> query( $q );
                $r = $s -> fetchAll( PDO::FETCH_ASSOC );
                if( count( $r ) > 0 ) {
                    // update record
                    $q = "UPDATE `event_participate` SET `remind_me` = '$remindMe', `role_id` = '$participateAs', `count_part` = '$countPart', current_datetime=Now() WHERE `event_participate`.`event_id` = $id and `event_participate`.`user_id` = $userId";
                    $return -> message = "Die Teilnahme wurde erfolgreich gespeichert.";
                } else {
                    // create new record
                    $q ="INSERT INTO `event_participate` (`event_id`, `user_id`, `remind_me`, `role_id`, `current_datetime`, `count_part`) VALUES ('$id', '$userId', '$remindMe', '$participateAs', current_timestamp(), '$countPart')";
                    // prepare message about participation
                    $title = "Teilnahmeinformation ´" . $r_event[0]["title"] . "´";
                    $content = "Der Nutzer " . $r_user[0]["fullname"] . " hat dem Termin ´" . $r_event[0]["title"] . "´ vom " . getGermanDateFromMysql( $r_event[0]["start_date"], false ) . " um " . $r_event[0]["start_time"] . " Uhr zugesagt.";
                    $return -> message = "Die Teilnahme wurde erfolgreich angelegt.";
                }
            } else {
                // delete part record
                $q = "delete from event_participate  WHERE `event_participate`.`event_id` = $id and `event_participate`.`user_id` = $userId";
                $return -> message = "Die Teilnahme wurde erfolgreich gelöscht.";
                // send message about participation delete
                $title = "Teilnahmelöschung ´" . $r_event[0]["title"] . "´";
                $content = "Der Nutzer " . $r_user[0]["fullname"] . " hat für Termin ´" . $r_event[0]["title"] . "´ vom " . getGermanDateFromMysql( $r_event[0]["start_date"], false ) . " um " . $r_event[0]["start_time"] . " Uhr abgesagt.";
            }
            $pdo -> query( $q );
            if( $elId === "participate" ) $iu -> sendUserInfo( $title, $title, $content, $content );
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Löschen der Teilnahme ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }   
    public function removeSingleEvent( $pdo, $id, $informRole, $informUser ) {
        $return = new \stdClass();
        $return -> success = true;
        try {
            $q = "select title, group_id from event where id = $id";
            $s = $pdo -> query( $q );
            $r_event = $s -> fetchAll( PDO::FETCH_ASSOC );
            $return -> user = $this -> getInformUser( $pdo, $id, $informRole, $informUser, "0" ) -> userAll;
            // 1. delete appendix
            $files = glob( "../cal/cal_ev_$id*.*");
            $l = count( $files );
            $i = 0;
            while( $i < $l ) {
                unlink( $files[$i] );
                $i += 1;
            }
            // 2. delete participants
            $q = "delete from event_participate where event_id = $id";
            $pdo -> query( $q );
            // 3. delete event
            $q = "delete from event where id = $id";
            $pdo -> query( $q );
        } catch ( Exception $e ) {
                $return -> success = false;    
        }
        return $return;    
    }
    public function removeGroupEvent( $pdo, $group_id, $eventId, $informRole, $informUser ) {
        $return = new \stdClass();
        $return -> success = true;
        try {
            
            // 1. delete appendix
            $q = "select id from event where group_id = $group_id and id >= $eventId";
            $s = $pdo -> query( $q );
            $r_events = $s -> fetchAll( PDO::FETCH_ASSOC );
            $l = count( $r_events );
            $i = 0;
            while( $i < $l ) {
                $files = glob( "../cal/cal_ev_" . $r_events[$i]["id"] . "*.*");
                $m = count( $files );
                $j = 0;
                while( $j < $m ) {
                    unlink( $files[$j] );
                    $j += 1;
                }
                $i += 1;
            }
            // 2. delete participants
            $return -> user = $this -> getInformUser( $pdo, "", $informRole, $informUser, $group_id ) -> userAll;
            $ids = assocArrToFlat( $r_events, "id" );
            $q = "delete from event_participate where event_id in($ids)";
            $pdo -> query( $q );
            // 3. delete events
            $q = "delete from event where id in ($ids)";
            $pdo -> query( $q );            
         } catch ( Exception $e ) {
                $return -> success = false;    
        }
        return $return;    
    }
    public function exportEvEMail( $pdo ) {
        $return = new \stdClass();
        try{
            require_once( "classes/InformUser.php" );
            $iu = new \InformUser( $pdo, "email", 27, 0, 0, $_SESSION["user_id"], true, [ "tmp/export_events_" . $_SESSION["user_id"] . ".ics, DeineTerminDatei.ics" ] );
            $content ="Als Anhang zu dieser E-Mail erhälst die die von dir exportierten Termine. Öffne deine Termin-App und lade diese Datei, damit du diese Termine übernehmen kannst.";
            $title = "Deine Termine im Anhang";
            $iu -> sendUserInfo( $title, $title, $content, $content );
            $return -> success = true;    
            $return -> message = "Die E-Mail wurde erfolgreich versand.";
            
        } catch (Exception $e ) {
            $return -> success = false;    
            $return -> message = "Beim Versenden der E-Mail ist folgender Fehler aufgetreten: " . $e -> getMessage();            
        }
        return $return;
    }
    public function newEvent( $pdo, $group_id, $title, $start_date, $end_date, $start_time, $end_time, $url, $description, $notice, $repeat, $repeat_to, $place, $format, $deadline, $inner_url, $inner_url_text, $creator, $appendixNames ){
        $return = new \stdClass();
        if( $repeat != "" && $repeat != "0" ) {
            $group_id = $this -> getMaxGroupId( $pdo ) + 1;
        }
        $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
        `end_date`, `end_time`, `url`, `description`, `notice`, `place`, `category`, `registration_deadline`, `inner_url`, `inner_url_text`, `creator`, `appendix_names` ) VALUES ($group_id, '$title', 
        '$start_date', '$start_time', '$end_date', '$end_time', '$url', '$description', '$notice', '$place', '$format', '$deadline', '$inner_url', '$inner_url_text', $creator, '$appendixNames' );";
        try {
            $pdo->query( $query );
            $return -> lastEventId = $pdo -> lastInsertId();
            $files = glob( "../cal/cal_new_" . $_SESSION["user_id"] . "*.*" );
            $l = count( $files );
            $i = 0;
            $appendixNames = "";
            while( $i < $l ) {
                rename( $files[$i], "../cal/cal_ev_" . $return -> lastEventId . "_$i." . getFileExt( $files[$i] ) );
                $i += 1;
            }
            $files = glob( "../cal/cal_ev_" . $return -> lastEventId . "*.*" );
            
/*
            if( $inner_url != "" ) {
                $tmp = explode( "/", $inner_url );
                $fname = $tmp[ count( $tmp ) - 1 ];
                if( file_exists( "../documents/$fname") ) {
                    $tmpExt = explode( ".", $fname );
                    $ext = $tmpExt[ count( $tmpExt) - 1 ];
                    $newFileName = "cal_ev_appendix_edit_" . $return -> lastEventId . "_" . time() . "." . $ext;
                    rename( "../documents/$fname", "../documents/$newFileName" );
                    $query = "UPDATE event set appendix_names = 'library/documents/$newFileName' WHERE id = " . $return -> lastEventId;
                    $pdo->query( $query );
                }                
*/
            $return -> success = true;    
            $return -> message = "Der Termin wurde erfolgreich gespeichert.";
        } catch ( Exception $e ) {
                $return -> success = false;
                if( $e -> getCode() == "23000" ) {
                    $return -> message = "Dieser Termin existiert bereits und kann so nicht gespeichert werden.";    
                } else {
                    $return -> message = "Beim Speichern des Termins ist folgender Fehler aufgetreten:" . $e -> getMessage();                    
                }  
        }
        $repeat_to = Date( "Y-m-d", strtotime( $repeat_to ) - 24 * 3600 );
        
        switch( $repeat ) {
            case "":
            break;
            case "1":
                        $query = "SELECT DATEDIFF('" . $repeat_to . "', '" . $start_date . "') as ddiff;";
                        $stm = $pdo -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $date_diff = intval( $result[0]["ddiff"]);
                        $date_diff++;
                        for( $i = 1; $i < $date_diff; $i++ ) {
                            $query = "SELECT DATE_ADD( '" . $start_date . "', INTERVAL " . $i . " DAY ) AS repeat_date";
                            $stm = $pdo -> query( $query );
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            $repeat_date = $result[0]["repeat_date"];
                            $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                            `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, '$title',
                            DATE_ADD( '" . $start_date . "', INTERVAL " . $i . " DAY ), '$start_time', DATE_ADD( '" . $end_date . "', INTERVAL " . $i . " DAY ), '$end_time', '$url', '$description', '$place', '$format');";
                            $pdo->query( $query );
                            $eventId = $pdo -> lastInsertId();
                            $m = count( $files );
                            $j = 0;
                            while( $j < $m ) {
                                copy( $files[$j], "../cal/cal_ev_" . $eventId . "_$j." . getFileExt( $files[$j] ) );
                                $i += 1;
                            }
                            
                        }
            break;
            case "5":
                        $query = "SELECT DATEDIFF('" . $repeat_to . "', '" . $start_date . "') as ddiff;";
                        $stm = $pdo -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $date_diff = intval( $result[0]["ddiff"]);
                        $date_diff++;
                        for( $i = 1; $i < $date_diff; $i++ ) {
                            $query = "SELECT WEEKDAY( DATE_ADD( '" . $start_date . "', INTERVAL " . $i . " DAY ) ) AS week_day";
                            $stm = $pdo -> query( $query );
                            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                            if( $result[0]["week_day"] == "5" || $result[0]["week_day"] == "6" ) {
                            } else {
                            $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                            `end_date`, `end_time`, `url`, `description`, `place`, `class`) VALUES ($group_id, '$title',
                            DATE_ADD( '" . $start_date . "', INTERVAL " . $i . " DAY ), '$start_time', DATE_ADD( '" . $end_date . "', INTERVAL " . $i . " DAY ), '$end_time', '$url', '$description', '$place', '$format');";
                                $pdo->query( $query );                                
                            }
                            
                        }                    
            break;
            case "7":
                        $query = "SELECT DATEDIFF('" . $repeat_to . "', '" . $start_date . "') as ddiff;";
                        $stm = $pdo -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $date_diff = intval( $result[0]["ddiff"] ) / 7;                        
                        $date_diff++;
                        for( $i = 1; $i < $date_diff; $i++ ) {
                                $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                                `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, 
                                '$title', DATE_ADD( '" . $start_date . "', INTERVAL " . $i . " WEEK ), 
                                '$start_time', DATE_ADD( '" . $end_date . "', INTERVAL " . $i . " WEEK ), 
                                '$end_time', '$url', '$description', '$place', '$format');";
                                $pdo->query( $query );
                        }                                
            break;
            case "14":
                        $query = "SELECT DATEDIFF('" . $repeat_to . "', '" . $start_date . "') as ddiff;";
                        $stm = $pdo -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $date_diff = intval( $result[0]["ddiff"] ) / 14;                        
                        $date_diff++;
                        for( $i = 1; $i < $date_diff; $i++ ) {
                                $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                                `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, '$title',
                                DATE_ADD( '" . $start_date . "', INTERVAL " . ( $i * 2 ) . " WEEK ), '$start_time', DATE_ADD( '" . $end_date . "', INTERVAL " . ( $i * 2 ) . " WEEK ), '$end_time', '$url', '$description', '$place', '$format');";
                                $pdo->query( $query );
                        }                                
            break;
            case "28":
                        $query = "SELECT DATEDIFF('" . $repeat_to . "', '" . $start_date . "') as ddiff;";
                        $stm = $pdo -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $date_diff = intval( $result[0]["ddiff"] ) / 28;                        
                        $date_diff++;
                        for( $i = 1; $i < $date_diff; $i++ ) {
                                $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                                `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, '$title',
                                DATE_ADD( '" . $start_date . "', INTERVAL " . ( $i * 4 ) . " WEEK ), '$start_time', DATE_ADD( '" . $end_date . "', INTERVAL " . ( $i * 4 ) . " WEEK ), '$end_time', '$url', '$description', '$place', '$format');";
                                $pdo->query( $query );
                        }                                
            break;
            case "31":
                        $query = "SELECT DATEDIFF('" . $repeat_to . "', '" . $start_date . "') as ddiff;";
                        $stm = $pdo -> query( $query );
                        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                        $date_diff = intval( $result[0]["ddiff"] ) / 31;                        
                        $date_diff++;
                        for( $i = 1; $i < $date_diff; $i++ ) {
                                $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                                `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, '$title',
                                DATE_ADD( '" . $start_date . "', INTERVAL " . $i . " MONTH ), '$start_time', DATE_ADD( '" . $end_date . "', INTERVAL " . $i . " MONTH ), '$end_time', '$url', '$description', '$place', '$format');";
                                $pdo->query( $query );
                        }                                
            break;
            case "2":   // 1./3./5. Wochentag des Monats
                        $date_array_1 = getNthOfMonth( $start_date, $repeat_to, intval( date("w", strtotime( $start_date ) ) ), 1 );
                        $date_array_2 = getNthOfMonth( $start_date, $repeat_to, intval( date("w", strtotime( $start_date ) ) ), 3 );
                        $date_array_3 = getNthOfMonth( $start_date, $repeat_to, intval( date("w", strtotime( $start_date ) ) ), 5 );
                        $dA = array_merge( $date_array_1, $date_array_2, $date_array_3 );
                        $dataDiff = strtotime( $end_date ) - strtotime( $start_date );
                        $l = count( $dA );
                        $i = 0;
                        while( $i - $l ) {
                            $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                                `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, '$title', '" . $dA[$i] . "'
                                , '$start_time', '" . date("Y-m-d", strtotime( $dA[$i] ) + $dataDiff ) . "', '$end_time', '$url', '$description', '$place', '$format');";
                            $pdo->query( $query );
                            $i++;
                        }
            break;
            case "3":   // 2./4. Wochentag des Monats
                        $date_array_1 = getNthOfMonth( $start_date, $repeat_to, intval( date("w", strtotime( $start_date ) ) ), 2 );
                        $date_array_2 = getNthOfMonth( $start_date, $repeat_to, intval( date("w", strtotime( $start_date ) ) ), 4 );
                        $dA = array_merge( $date_array_1, $date_array_2 );
                        $dataDiff = strtotime( $end_date ) - strtotime( $start_date );
                        $l = count( $dA );
                        $i = 0;
                        while( $i - $l ) {
                            $query = "INSERT INTO `event` (`group_id`, `title`, `start_date`, `start_time`,
                                `end_date`, `end_time`, `url`, `description`, `place`, `category`) VALUES ($group_id, '$title', '" . $dA[$i] . "'
                                , '$start_time', '" . date("Y-m-d", strtotime( $dA[$i] ) + $dataDiff ) . "', '$end_time', '$url', '$description', '$place', '$format');";
                            $pdo->query( $query );
                            $i++;
                        }
            break;
        }
        return $return;
    }
    public function getParticipants( $pdo, $event_id ) {
        $return = new \stdClass();
        $query = "SELECT user.id as participant_id, CONCAT( `user`.`firstname`, ' ', `user`.`lastname`) AS participant, count_part FROM `event_participate`, `user` WHERE user_id = `user`.`id` AND event_id = $event_id";
        try {
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> data = $result;
            $l = count( $result );
            $i = 0;
            $return -> Ids = [];
            while( $i < $l ) {
                array_push( $return -> Ids, $result[$i]["participant_id"] );
                $i += 1;
            }
            $query = "SELECT SUM( count_part ) as sum FROM `event_participate` WHERE event_id = $event_id";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> sum = $result[0]["sum"];
            
            $return -> success = true;
            $return -> message = "Die Teilnehmer wurden erfolgreich gelesen";
        } catch ( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Lesen der Teilnehmer ist folgender Fehler aufgetreten:" . $e -> getMessage();                    
                   
        }
        return $return;
    }
    public function getMaxGroupId( $pdo ) {
        $query = "SELECT MAX(group_id) AS max_group_id FROM event";
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        return intval( $result[0]["max_group_id"] );        
    }
    public function changeDateTime( $pdo, $eventId, $startDate, $startTime, $endDate, $endTime ) {
        $return = new \stdClass();
        try {
            $query = "UPDATE `event` SET `start_date` = '$startDate', `start_time` = '$startTime', `end_date` = '$endDate', `end_time` = '$endTime' WHERE `event`.`id` = $eventId";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> success = true;
            $return -> message = "Der Termin wurde erfolgreich verschoben";
        } catch ( Exception $e ) {
                $return -> success = false;
                $return -> message = "Bei der Änderung von Termindatum/Terminzeit ist folgender Fehler aufgetreten:" . $e -> getMessage();   
        }
        return $return;
    }
    public function removeUserFromEvent( $pdo, $eventId, $userId ) {
        $return = new \stdClass();
        try {
            $q = "select title, start_date from event where id = $eventId";
            $s = $pdo -> query( $q );
            $r_event = $s -> fetchAll( PDO::FETCH_ASSOC );
            $q = "select concat( firstname, ' ', lastname ) as name from user where id = " . $_SESSION["user_id"];
            $s = $pdo -> query( $q );
            $r_user = $s -> fetchAll( PDO::FETCH_ASSOC );
            $content = "Deine Teilnahme am Termin `" . $r_event[0]["title"] . "` am " . getGermanDateFromMysql( $r_event[0]["start_date"] ) . " wurde durch " . $r_user[0]["name"] . " gelöscht. Bitte Aktualisiere Deine Termin-App.";
            require_once( "classes/InformUser.php" );
            $iu = new \InformUser( $pdo, "both", 27, 0, 0, $userId );
            $iu -> sendUserInfo( "Teilnahmelöschung", "Teilnahmelöschung", $content, $content );
            $return -> success = true;
            $return -> message = "Der Nutzer wurde erfolgreich gelösch.";
        } catch ( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Löschen des Nutzers ist folgender Fehler aufgetreten:" . $e -> getMessage();   
        }
        return $return;
    }
    public function addUserToEvent( $pdo, $partId ) {
        $return = new \stdClass();
        try {
            $q = "select title, start_date, event_participate.user_id from event, event_participate where event_participate.event_id =  event.id and event_participate.id = $partId";
            $s = $pdo -> query( $q );
            $r_event = $s -> fetchAll( PDO::FETCH_ASSOC );
            $q = "select concat( firstname, ' ', lastname ) as name from user where id = " . $_SESSION["user_id"];
            $s = $pdo -> query( $q );
            $r_user = $s -> fetchAll( PDO::FETCH_ASSOC );
            $content = "Du wurdest von " . $r_user[0]["name"] . " zum Termin `" . $r_event[0]["title"] . "` am " . getGermanDateFromMysql( $r_event[0]["start_date"] ) . " als Teilnehmer hinzugefügt. Bitte Aktualisiere Deine Termin-App.";
            require_once( "classes/InformUser.php" );
            $iu = new \InformUser( $pdo, "both", 27, 0, 0, $r_event[0]["user_id"] );
            $iu -> sendUserInfo( "Teilnahmehinzufügung", "Teilnahmehinzufügung", $content, $content );
            $return -> success = true;
            $return -> message = "Der Nutzer wurde erfolgreich hinzugefügt.";
        } catch ( Exception $e ) {
                $return -> success = false;
                $return -> message = "Beim Hinzufügen des Nutzers ist folgender Fehler aufgetreten:" . $e -> getMessage();   
        }
        return $return;
    }
    /* end CalEv */
}  
?>
