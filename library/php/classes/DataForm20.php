<?php
define( "COUNT_PAGINATION", 5 );
class DataForm {
    private     $pdo;
    public      $fieldDefs;
    private     $table;
    private     $fields;
    public      $primaryKey;
    public      $countRecords;
    public function __construct( $pdo, $table ) {
        // content
        $this -> pdo = $pdo;
        $this -> table = $table;
    }
    public function getFieldDefs( $fields, $fieldDefs = [] ) {
        if ( count( $fieldDefs ) === 0 ) {
            if( $fields === "" ) {
                $q = "SHOW FULL COLUMNS FROM " .  $this -> table;                
            } else {

                $tmp = explode( ",", $fields );
                $i = 0;
                $l = count( $tmp );
                while( $i < $l ) {
                    $tmp[$i] = "'" . $tmp[$i] . "'";
                    $i += 1;
                }
                $fields = implode(",", $tmp );

                $q = "SHOW FULL COLUMNS FROM " .  $this -> table . " where Field in (" . $fields . ")"; 
            }
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $this -> fieldDefs = $r;
            $q = "SHOW KEYS FROM " . $this -> table . " WHERE Key_name = 'PRIMARY'";
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $this -> primaryKey = $r[0]["Column_name"];
        } else {
            // code...
            $this -> $fieldDefs = $fieldDefs;
        }
    }
    public function getFields(  ) {
            $q = "SHOW FULL COLUMNS FROM " .  $this -> table;                
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $fieldDefs = [];
            $fields = "";
            $l = count( $r );
            $i = 0;
            while( $i < $l ) {
                $res = new \stdClass();
                $res -> field =  $r[$i]["Field"];
                $res -> label = $res -> field;
                $res -> type = "input_text";
                $fieldDefs[] = $res;
                $fields .= $res -> field . ",";
                $i += 1;
            }
            $fields = substr( $fields, 0, strlen( $fields ) - 1 );
            $return = new \stdClass();
            $return -> fields = $fields;
            $return -> fieldDefs = $fieldDefs;
            return $return;
    }
    public function getRecords( $fields, $whereClausel, $orderBy, $limit, $hasNew, $primaryKey = "" ) {
        $return = new \stdClass();
        if( $whereClausel === "where " ) $whereClausel = "";
        if( $fields === "" ) {
            $q = "select * from " . $this -> table;
        } else {
            $q = "select $fields, $primaryKey as primaryKey from " . $this -> table;
        }
        if( $whereClausel !== "" ) {
            $q .= " $whereClausel"; 
        }
        if( $orderBy !== "") {
            $q .= " ORDER BY $orderBy";
        }
        $c = "select count( $primaryKey ) as cRecords from " . $this -> table . " $whereClausel";
        $c = str_replace( "\\", "", $c );
        $s = $this -> pdo -> query( $c );
        $r = $s -> fetchAll( PDO::FETCH_ASSOC );
        $return -> countRecords = $r[0]["cRecords"];
        $q = str_replace( "\\", "", $q );
        $q .= $limit;
        $s = $this -> pdo -> query( $q );
        $r = $s -> fetchAll( PDO::FETCH_CLASS );
        if( $hasNew == "true" ) {
            if( $fields === "" ) {
                $q = "SHOW FULL COLUMNS FROM " .  $this -> table;
            } else {
                
            }
            $s = $this -> pdo -> query( $q );                
        }
        $return -> records = $r;
        return $return;        
    }
    public function saveRecordset( $primaryKey, $primaryKeyValue, $fields ) {
        $return = new \stdClass();
        try {
            $l = count( $fields );
            $i = 0;
            $q = "";
            while( $i < $l ) {
                if( $fields[$i] -> value === FALSE ) $fields[$i] -> value = 0;
                $q .= $fields[$i] -> field . " = '" . $fields[$i] -> value . "', ";
                $i += 1;
            }
            $q = "update " . $this -> table . " set " . $q;
            $q = substr( $q, 0, strlen( $q ) - 2 ) . " where $primaryKey = '$primaryKeyValue'";
            $this -> pdo -> query( $q );
            $return -> success = true;
            $return -> message = "Der Datensatz wurde erfolgreich gespeichert.";
            $return -> newId = $primaryKeyValue;
        } catch (Exception $e ) {
            $return -> success = false;
            $return -> newId = false;
            $return -> message = "Beim Speichern des Datensatzes ist folgender Fehler aufgetreten: " . $e -> getMessage();            
        }
        return $return;
    }       
    public function newRecordset( $primaryKey, $primaryKeyValue, $fields ) {
        $return = new \stdClass();
        try {
            $q = "SHOW FULL COLUMNS FROM " .  $this -> table . " where Field in ('" . $primaryKey . "')";
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $isAuto = false;
            if( str_contains( $r[0]["Extra"], "auto_increment" ) ) $isAuto = true;
            $l = count( $fields );
            $i = 0;
            $tabFields = "";
            $values = "";
            while( $i < $l ) {
                if( $fields[$i]->field === $primaryKey && $isAuto ) {
                    
                } else {
                    $tabFields .= $fields[$i]->field . ",";
                    $values .= "'" . $fields[$i]->value . "',";
                }
                $i += 1;
            }
            $tabFields = " (" . substr( $tabFields, 0, strlen( $tabFields ) - 1 ) . ") values "; 
            $values = "(" . substr( $values, 0, strlen( $values ) - 1 ) . ")";
            $q = "insert into " . $this -> table . "$tabFields$values";
            $this -> pdo -> query( $q );
            $return -> newId = $this -> pdo -> lastInsertId(); 
            $return -> success = true;
            $return -> message = "Der Datensatz wurde erfolgreich angelegt.";
            
        } catch (Exception $e ) {
            $return -> success = false;
            $return -> newId = false;
            $a = $e -> getCode();
            if( $e -> getCode() === "23000" ) {
                $return -> message = "Der Datensatzes kann so nicht angelegt werden, da eine Schlüsselverletzung vorliegt.";
            } else {
                $return -> message = "Beim Anlegen des Datensatzes ist folgender Fehler aufgetreten: " . $e -> getMessage();                
            }
        }
        return $return;
    }       
}
