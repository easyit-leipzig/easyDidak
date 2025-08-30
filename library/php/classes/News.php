<?php
class News {
    public function getCountNewsPerUser( $pdo, $userId ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) as count_id FROM  `news_user` WHERE to_user = $userId and is_read = false;";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = $result[0]["count_id"];
            $return -> success = true;
            $return -> message = "Die Bestimmung der Anzahl der News war erfolgreich.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Bei der Bestimmung der Anzahl der News ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;
    }
    public function getCountNews( $pdo ) {
        $return = new \stdClass();
        try{
            $return -> success = true;
            $query = "SELECT count(id) as count_id FROM  `news`;";
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> count_records = $result[0]["count_id"];
            $return -> success = true;
            $return -> message = "Die Bestimmung der Anzahl der News war erfolgreich.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Bei der Bestimmung der Anzahl der News ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;
    }
    public function getNew( $pdo, $newsId ) {
        $return = new \stdClass();
        $query="SELECT * FROM `news`, `news_user` WHERE id = $newsId";
        try{        
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> data = $result;
            $return -> success = true;
            $return -> message = "Die News wurde erfolgreich gelesen";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Lesen der News ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function getNews( $pdo, $orderBy = "ORDER BY id", $limit = "", $where = ""  ) {
        $return = new \stdClass();
        $query="SELECT * FROM `news` $where $orderBy $limit";
        try{        
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> data = $result;
            $return -> success = true;
            $return -> message = "Die News wurde erfolgreich gelesen";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Lesen der News ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function getNextNews( $pdo, $userId ) {
        /*
        select message.id, title, content, from_role, from_user, to_role, 
        message_datetime, message_user.to_user, is_read, archive, email from message_user, 
        message, user where message.id = from_message and 
        message_user.to_user = $userId and message_user.to_user=user.id and is_read = false
        ORDER BY id DESC LIMIT 0, 1
        */
        $return = new \stdClass();
        if( $this -> getCountNews( $pdo, $userId ) == 0 ) {
            $return -> success = false;
            $return -> countMessages = 0; 
            $return -> message = "Es liegen keine News vor.";
            return $return;                                         
        }
        $query="select news.id, title, content, from_role, from_user, to_role, 
        curr_datetime, news_user.to_user, is_read, archive, email from 
        news_user, news, user where news.id = from_news and 
        news_user.to_user = $userId and news_user.to_user=user.id and 
        is_read = false ORDER BY id DESC LIMIT 0, 1";
        try{        
            $stm = $pdo -> query( $query );
            $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $return -> data = $result;
            $return -> success = true;
            $return -> countMessages = 1; 
            $return -> message = "Die News wurde erfolgreich gelesen";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Lesen der News ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function setIsRead( $pdo, $id ) {
        $return = new \stdClass();
        try {
            
            $query = "delete from news_user WHERE id = $id";
            $pdo -> query( $query );    
            $return -> success = true;
            $return -> message = "Die gelesene News wurde erfolgreich gelöscht.";
        } catch( Exception $e ) {
            $return -> success = false;
            $return -> message = "Beim Löschen der News ist folgender Fehler aufgetreten:" . $e -> getMessage() . ".";
        }
        return $return;       
    }
    public function getNewsContent( $pdo, $dsPointer, $sort ) {
        $return = new \stdClass();
        $return -> type = 2;
        $query = "SELECT news_user.id, `from_news`, news_user.to_user, news_user.is_read, news.title, news.content, news.curr_datetime, from_role, from_user FROM `news_user`, news WHERE news.id = news_user.from_news AND news_user.is_read = false AND news_user.to_user = " . $_SESSION["user_id"] . " ORDER BY news_user.id $sort LIMIT 0, 1";    
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result ) > 0 ) {
            $return -> newsUserId = $result[0]["id"];
            $return -> newsId = $result[0]["from_news"];
            // nessesary for AJAX evaluate date
            $return -> messageUserId = $result[0]["id"];
            $return -> messageId = $return -> newsId;
            //
            $return -> title = $result[0]["title"];
            $return -> content = $result[0]["content"];
            $return -> isRead = $result[0]["is_read"];
            if( $result[0]["from_role"] != 0 ) {
                $query = "SELECT role FROM role WHERE id = " . $result[0]["from_role"];
                $stm = $pdo -> query( $query );
                $result_role = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $return -> roleName = $result_role[0]["role"];
            } else {
                $return -> roleName = "&nbsp;";
            }
            if( $result[0]["from_user"] != 0 ) {
                $query = "SELECT CONCAT( firstname, ' ', lastname) as name FROM user WHERE id = " . $result[0]["from_user"];
                $stm = $pdo -> query( $query );
                $result_user = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $return -> userName = $result_user[0]["name"];
            } else {
                $return -> userName = "&nbsp;";
            }
        } else {
            $return -> newsUserId = 0;            
            // nessesary for AJAX evaluate date
            $return -> messageUserId = 0;
            //$return -> messageId = $return -> newsId;
            //
        }
        $return -> dsPointer = $dsPointer;
        return $return;
    }
    public function getNewsContentNew( $pdo, $dsPointer, $sort ) {
        $return = new \stdClass();
        $return -> type = 2;
        if( $sort == "ASC" ) {
            $condition = "news_user.id > $dsPointer AND";
        } else {
            $condition = "news_user.id < $dsPointer AND";   
        }
        if( $dsPointer == "&nbsp;" ) {
            $condition = "news_user.id > 0 AND";
        }
        $query = "SELECT news_user.id, `from_news`, news_user.to_user, news_user.is_read, news.title, news.content, news.curr_datetime, from_role, from_user FROM `news_user`, news 
            WHERE $condition news.id = news_user.from_news AND news_user.to_user = " . $_SESSION["user_id"] . " ORDER BY news_user.id $sort LIMIT 0, 1";    
        $stm = $pdo -> query( $query );
        $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
        if( count( $result ) > 0 ) {
            $return -> newsUserId = $result[0]["id"];
            $return -> newsId = $result[0]["from_news"];
            // nessesary for AJAX evaluate date
            $return -> messageUserId = $result[0]["id"];
            $return -> messageId = $return -> newsId;
            //
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
            }
            if( $result[0]["from_user"] != 0 ) {
                $query = "SELECT CONCAT( firstname, ' ', lastname) as name FROM user WHERE id = " . $result[0]["from_user"];
                $stm = $pdo -> query( $query );
                $result_user = $stm -> fetchAll(PDO::FETCH_ASSOC);
                $return -> userName = $result_user[0]["name"];
            }
        } else {
            $return -> newsUserId = 0;            
            // nessesary for AJAX evaluate date
            $return -> messageUserId = 0;
            //$return -> messageId = $return -> newsId;
            //
        }
        $return -> dsPointer = $return -> newsUserId;
        return $return;
    }
    public function newNewsUser( $pdo, $from_news, $to_user, $is_read = false, $archive = false ) {
        $return = new \stdClass();
        $query = "INSERT INTO `news_user` (`from_news`, `to_user`, `is_read`, `archive`) VALUES ('$from_news', $to_user, $is_read, $archive )";
        try {
            $return -> success = true;
            $return -> message = "Das Anlegen der News war erfolgreich.";
            
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen der News ist folgender Fehler aufgetreten: " . $e -> getMessage();
        }        
    return $return;
    }
    public function newNews( $pdo, $title, $content, $from_role, $from_user, $to_role, $to_user, $valid_to = "", $informMyself = true ) {
        $return = new \stdClass();
        if( $valid_to == "" ) {
            $settings = parse_ini_file('../../ini/settings.ini', TRUE);
            $tmpDays = $settings["admin_messages_news"]["diff_max_valid_to_days"];
            $timestamp = time();
            $date = strtotime("+$tmpDays day", $timestamp);
            $valid_to = date('Y-m-d', $date);
        }
        $query = "INSERT INTO `news` (`title`, `content`, `from_role`, `from_user`, `to_role`, `to_user`, `valid_to` ) VALUES ('$title', '$content', $from_role, $from_user, $to_role, $to_user, '$valid_to' )";
        try {
            $result = $pdo -> query( $query );
            $return -> newsId = $pdo -> lastInsertId();
            if( $to_role != 0 ) {
                $query = "SELECT user_id FROM account WHERE role_id = $to_role";
                $stm = $pdo -> query( $query );
                $result = $stm -> fetchAll(PDO::FETCH_ASSOC);
                for( $i = 0; $i < count( $result ); $i++ ) {
                    $query = "INSERT INTO news_user ( from_news, to_user, is_read ) VALUES (" . $return -> newsId . ", " . $result[$i]["user_id"] . ", 0 )";
                    $pdo -> query( $query );
                }                
            } else {
                    $query = "INSERT INTO news_user ( from_news, to_user, is_read ) VALUES (" . $return -> newsId . ", $to_user, 0 )";
                    $pdo -> query( $query );                
            }
            if( $to_role != "" ) {
                require_once( "classes/User.php" );
                $us = new \User( $pdo );
                $users = $us -> getUserByRoleId( $to_role );
                
            } else {
                
            }
            $return -> success = true;
            $return -> message = "Die News wurde erfolgreich angelegt.";
        } catch ( Exception $e ) {
            $return -> success = false;
            $return -> errorNumber = $e -> getCode();
            $return -> message = "Beim Anlegen der News ist folgender Fehler aufgetreten: " . $e -> getMessage();
        }        
    return $return;
    }
    public function updateNews( $pdo, $newsId, $title, $content, $from_role, $from_user, $to_role, $to_user, $isRead = 0 ) {
        $return = new \stdClass();
        $query = "UPDATE `news` SET curr_datetime = NOW(), title = '$title', content = '$content', from_role = $from_role, 
        from_user = $from_user, to_role = $to_role, to_user = $to_user, is_read = $isRead WHERE id = $newsId;"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Die News wurde erfolgreich aktualisiert.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Aktualisieren der News ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
    public function deleteNews( $pdo, $newsId ) {
        $return = new \stdClass();
        $query = "DELETE FROM `news` WHERE id = $newsId;"; 
        try {
            $pdo->query( $query );      
            $return -> success = true;    
            $return -> message = "Die News wurde erfolgreich gelöscht.";
        } catch ( Exception $e ) {
                $return -> success = false;    
                $return -> message = "Beim Löschen der News ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
    public function getList( $pdo ) {
        $return = new \stdClass();
        $q = "select title, content, from_role, from_user, curr_datetime from news, news_user where news.id = news_user.from_news and  news_user.is_read = 0 and news_user.to_user = " . $_SESSION["user_id"]; 
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
        $q = "delete from news_user where to_user = " . $_SESSION["user_id"]; 
        try {
            $pdo -> query( $q );
            $return -> success = true;    
            $return -> message = "Die News wurden erfolgreich gelöscht.";
        } catch ( Exception $e ) {
            $return -> success = false;    
            $return -> message = "Beim Löschen der News ist folgender Fehler aufgetreten:" . $e -> getMessage();
        }
        return $return;
    }
}
?>
