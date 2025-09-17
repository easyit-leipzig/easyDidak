<?php
class Message {
    public function getCountMessagesPerUser( $pdo, $userId ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) as count_id FROM  `message_user` WHERE to_user = $userId and is_read = false;";
            $stm = $pdo -> query( $query );
            $return -> count_records = $stm -> fetchAll(PDO::FETCH_ASSOC)[0]["count_id"];
            $return -> success = true;
            $return -> message = "Die Bestimmung der Anzahl der Meldungen war erfolgreich.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Bei der Bestimmung der Anzahl der Meldungen ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;
    }
    public function getCountMessages( $pdo ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) as count_id FROM  `message`";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = $result[0]["count_id"];
            $return -> success = true;
            $return -> message = "Die Bestimmung der Anzahl der Meldungen war erfolgreich.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Bei der Bestimmung der Anzahl der Meldungen ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;
    }
    /*
    public function getCountMessagesUser( $pdo, $userId ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) as count_id FROM  `message_user` WHERE to_user = $userId and is_read = false;";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = $result[0]["count_id"];
            $return -> success = true;
            $return -> message = "Die Bestimmung der Anzahl der Meldungen war erfolgreich.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Bei der Bestimmung der Anzahl der Meldungen ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;
    }
    */
    public function getMessage( $pdo, $messageId ) {
        $return = new \stdClass();
        $query="SELECT * FROM `message`, `message_user` WHERE id = $messageId";
        try{        
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> data = $result;
            $return -> success = true;
            $return -> message = "Die Meldung wurde erfolgreich gelesen";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Lesen der Meldung ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function getMessages( $pdo, $orderBy = "ORDER BY id", $limit = "", $where = "" ) {
        $return = new \stdClass();
        $query="SELECT * FROM  `message` $where $orderBy $limit";
        try{        
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> data = $result;
            $return -> count_records = count( (array)$result );
            $return -> success = true;
            $return -> message = "Die Meldungen wurde erfolgreich gelesen";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Lesen der Meldungen ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function setIsRead( $pdo, $id ) {
        $return = new \stdClass();
        try {
            $query = "delete from message_user WHERE id = $id";
            $pdo -> query( $query );    
            $return -> success = true;
            $return -> message = "Die gelesene Meldung wurde erfolgreich gelöscht.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Löschen der Meldung ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }
        return $return;       
    }
    public function getMessageContent( $pdo, $dsPointer, $sort ) {
        $return = new \stdClass();
        $return -> type = 1;
        $query = "SELECT message_user.id, `from_message`, message_user.to_user, message_user.is_read, message.title, message.content, message.curr_datetime, from_role, from_user FROM `message_user`, message WHERE message.id = message_user.from_message AND message_user.is_read = false AND message_user.to_user = " . $_SESSION["user_id"] . " ORDER BY message_user.id $sort LIMIT 0, 1";
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result ) > 0 ) {
            $return -> messageUserId = $result[0]["id"];
            $return -> messageId = $result[0]["from_message"];
            $return -> title = $result[0]["title"];
            $return -> content = $result[0]["content"];
            $return -> isRead = $result[0]["is_read"];
            $return -> currDateTime = date('d.m.Y H:i', strtotime( $result[0]["curr_datetime"] ) ) . " Uhr";
            if( $result[0]["from_role"] != 0 ) {
                $query = "SELECT role FROM role WHERE id = " . $result[0]["from_role"];
                $stm = $pdo -> query( $query );
                $result_role = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $return -> roleName = $result_role[0]["role"];
            } else {
                $return -> roleName = "&nbsp;";
            }
            if( isset( $result[0]["from_user"] ) && $result[0]["from_user"] != 0 ) {
                $query = "SELECT CONCAT( firstname, ' ', lastname) as name FROM user WHERE id = " . $result[0]["from_user"];
                $stm = $pdo -> query( $query );
                $result_user = $stm -> fetchAll(PDO::FETCH_ASSOC);
                if( isset( $result_user[0]["name"] ) ) {
                    $return -> userName = $result_user[0]["name"];
                } else {
                    $return -> userName = "&nbsp;";    
                }
            }
        } else {
            $return -> messageUserId = 0;            
        }
        $return -> dsPointer = $dsPointer;
        return $return;
    }
    public function getMessageContentNew( $pdo, $dsPointer, $sort ) {
        $return = new \stdClass();
        $return -> type = 1;
        if( $sort == "ASC" ) {
            $condition = "message_user.id > $dsPointer AND";
        } else {
            $condition = "message_user.id < $dsPointer AND";   
        }
        if( $dsPointer == "&nbsp;" || $dsPointer == "" ) {
            $condition = "message_user.id > 0 AND";
        }
        $query = "SELECT message_user.id, `from_message`, message_user.to_user, message_user.is_read, message.title, message.content, message.curr_datetime, from_role, from_user FROM `message_user`, message 
                    WHERE $condition message.id = message_user.from_message AND message_user.to_user = " . $_SESSION["user_id"] . " ORDER BY message_user.id $sort LIMIT 0, 1";
        //var_dump( $dsPointer, $query );
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result ) > 0 ) {
            $return -> messageUserId = $result[0]["id"];
            $return -> messageId = $result[0]["from_message"];
            $return -> title = $result[0]["title"];
            $return -> content = $result[0]["content"];
            $return -> isRead = $result[0]["is_read"];
            $return -> currDateTime = date('d.m.Y H:i', strtotime( $result[0]["curr_datetime"] ) ) . " Uhr";
                $return -> roleName = "";
                    $return -> userName = "";    
            if( $result[0]["from_role"] != 0 ) {
                $query = "SELECT role FROM role WHERE id = " . $result[0]["from_role"];
                $stm = $pdo -> query( $query );
                $result_role = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $return -> roleName = $result_role[0]["role"];
            } else {
            }
            if( isset( $result[0]["from_user"] ) && $result[0]["from_user"] != 0 ) {
                $query = "SELECT CONCAT( firstname, ' ', lastname) as name FROM user WHERE id = " . $result[0]["from_user"];
                $stm = $pdo -> query( $query );
                $result_user = $stm -> fetchAll(PDO::FETCH_ASSOC);
                if( isset( $result_user[0]["name"] ) ) {
                    $return -> userName = $result_user[0]["name"];
                } else {
                }
            }
        } else {
            $return -> messageUserId = 0;            
        }
        $return -> dsPointer = $return -> messageUserId;
        return $return;
    }
    public function newMessageUser( $pdo, $from_message, $to_user, $is_read = false, $archive = false ) {
        $return = new \stdClass();
        $query = "INSERT INTO `message_user` (`from_message`, `to_user`, `is_read`, `archive`) VALUES ('$from_message', $to_user, $is_read, $archive )";
        try {
            $return -> success = true;
            
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen der Meldung ist folgender Fehler aufgetreten: " . $e -> getMessage();
        }        
    return $return;
    }
    public function newMessage( $pdo, $title, $content, $from_role, $from_user, $to_role, $to_user, $valid_to = '', $informMyself = true ) {
        $return = new \stdClass();
        if( $valid_to == "" ) {
            $settings = parse_ini_file('../../ini/settings.ini', TRUE);
            $tmpDays = $settings["admin_messages_news"]["diff_max_valid_to_days"];
            $timestamp = time();
            $date = strtotime("+$tmpDays day", $timestamp);
            $valid_to = date('Y-m-d', $date);
        }
        $query = "INSERT INTO `message` (`title`, `content`, `from_role`, `from_user`, `to_role`, `to_user`, `valid_to` ) VALUES ('$title', '$content', $from_role, $from_user, $to_role, $to_user, '$valid_to' )";
        try {
            $result = $pdo -> query( $query );
            $return -> messageId = $pdo -> lastInsertId();
            if( $to_role != 0 ) {
                $query = "SELECT user_id FROM account WHERE role_id = $to_role";
                $stm = $pdo -> query( $query );
                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                for( $i = 0; $i < count( $result ); $i++ ) {
                    $query = "INSERT INTO message_user ( from_message, to_user, is_read ) VALUES (" . $return -> messageId . ", " . $result[$i]["user_id"] . ", 0 )";
                    if( $result[$i]["user_id"] == $_SESSION["user_id"] && $informMyself ) {
                        $pdo -> query( $query );
                    } else {
                        if( $result[$i]["user_id"] != $_SESSION["user_id"] ) {
                            $pdo -> query( $query );
                        }
                    }
                }                
            } else {
                    $query = "INSERT INTO message_user ( from_message, to_user, is_read ) VALUES (" . $return -> messageId . ", $to_user, 0 )";
                    $pdo -> query( $query );                
            }
            if( $to_role != "" ) {
                require_once( "classes/User.php" );
                $us = new \User( $pdo );
                $users = $us -> getUserByRoleId( $to_role );
                
            } else {
                
            }
            $return -> success = true;
            $return -> message = "Die Meldung wurde erfolgreich angelegt.";
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen der Meldung ist folgender Fehler aufgetreten: " . $e -> getMessage();
        }        
    return $return;
    }
    public function updateMessage( $pdo, $messageId, $title, $content, $from_role, $from_user, $to_role, $to_user, $isRead = 0 ) {
        $return = new \stdClass();
        $query = "UPDATE `message` SET curr_datetime = NOW(), title = '$title', content = '$content', from_role = $from_role, 
        from_user = $from_user, to_role = $to_role, to_user = $to_user, is_read = $isRead WHERE id = $messageId;"; 
        try {
            $pdo->query( $query );
            $query = "DELETE FROM message_user WHERE from_message = $messageId";      
            $pdo->query( $query );
            if( $to_role != 0 ) {
                $query = "SELECT user_id FROM account WHERE role_id = $to_role";
                $stm = $pdo -> query( $query );
                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                for( $i = 0; $i < count( $result ); $i++ ) {
                    $query = "INSERT INTO message_user ( from_message, to_user, is_read ) VALUES ($messageId, " . $result[$i]["user_id"] . ", $isRead )";
                    $pdo -> query( $query );
                }                
            } else {
                    $query = "INSERT INTO message_user ( from_message, to_user, is_read ) VALUES ($messageId, $to_user, $isRead )";
                    $pdo -> query( $query );                
            }
            $return -> success = true;    
            $return -> message = "Die Meldung wurde erfolgreich aktualisiert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Aktualisieren der Meldung ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
    public function deleteMessage( $pdo, $messageId ) {
        $return = new \stdClass();
        $query = "DELETE FROM `message` WHERE id = $messageId;"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Die Meldung wurde erfolgreich gelöscht.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Löschen der Meldung ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
    public function getList( $pdo ) {
        $return = new \stdClass();
        $q = "select title, content, from_role, from_user, curr_datetime from message, message_user where message.id = message_user.from_message and  message_user.is_read = 0 and message_user.to_user = " . $_SESSION["user_id"]; 
        try {
            $s = $pdo -> query( $q );
            $return -> data = $s -> fetchAll( PDO::FETCH_ASSOC );
            $l = count( $return -> data );
            $i = 0;
            while( $i < $l ) {
                $return -> data[$i]["curr_datetime"] = getGermanDateFromMysql( $return -> data[$i]["curr_datetime"], true );
                if( $return -> data[$i]["from_role"] != 0 ) {
                    $q = "select sender as fullname from role where id = " . $return -> data[$i]["from_role"];
                } else {
                    $q = "select concat( firstname, ' ', lastname ) as fullname from user where id = " . $return -> data[$i]["from_user"];
                }
                $s = $pdo -> query( $q );
                $r = $s -> fetchAll( PDO::FETCH_ASSOC );
                $return -> data[$i]["fullname"] = $r[0]["fullname"];
                $i += 1;
            }
            
            $return -> success = true;    
            $return -> message = "Die Meldungen wurden erfolgreich gelesen.";
        } catch ( Exception $e ) {
            $return -> success = false;    
            $return -> message = "Beim Lesen der Meldungen ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
    public function readAll( $pdo ) {
        $return = new \stdClass();
        $q = "delete from message_user where to_user = " . $_SESSION["user_id"]; 
        try {
            $pdo -> query( $q );
            $return -> success = true;    
            $return -> message = "Die Meldungen wurden erfolgreich gelöscht.";
        } catch ( Exception $e ) {
            $return -> success = false;    
            $return -> message = "Beim Löschen der Meldungen ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
}
?>
