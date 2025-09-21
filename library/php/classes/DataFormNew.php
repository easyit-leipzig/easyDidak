<?php
define( "COUNT_PAGINATION", 5 );
class DataFormNew {
    private $pdo;
    private $pageSource;
    private $currentRecord;
    private $internalPageSource;
    public $table;
    public $countPages;
    private $data;
    private $structure;
    private $primaryKey;
    private $fields;
    private $labelDef;
    private $addFieldAttr;
    public $countRecords;
    public $isNew;
    private $currentPage;
    private $countPerPage;
    private $fieldPraefix;
    private $classPraefix;
    public $firstRecord;
    public function standardFunktion(  ) {
        $return = new \stdClass();
        try{
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    /**
    * put your comment there...
    * 
    * @param mixed $pdo
    * @param mixed $pageSource  SQL-String lowercase without where, order e.g. "select * from book_status"
    * @param mixed $fields      table fields as string including "'" e.g. "'id', 'status'"
    */
    public function __construct( $pdo, $pageSource, $currentRecord, $fieldPraefix = "", $classPraefix = "", $fields = "*", $currentPage = "0", $countPerPage = "", $isNew = "true" ) {
        $this -> pdo = $pdo;
        if( substr( $pageSource, 0, 6 ) === "select" || substr( $pageSource, 0, 6 ) === "SELECT" ) {
            $t = explode( "from", $pageSource );
                if( isset( $t[1] ) ) {
                    $this -> table = trim( explode( "from", $pageSource )[1] );
                    $this -> pageSource = $pageSource;
                   
                } else {
                    $this -> table = trim( explode( "FROM", $pageSource )[1] );
                    $this -> pageSource = $pageSource;                

                }
        } else {
            $this -> table = $pageSource;
            $this -> pageSource = "select * from " . $pageSource;
        }
        
        $this -> fields = $fields;
        $this -> fieldPraefix = $fieldPraefix;
        $this -> classPraefix = $classPraefix;
        $this -> currentRecord = $currentRecord;
        $this -> currentPage = intval( $currentPage );
        $this -> countPerPage = intval( $countPerPage );
        $this -> structure = $this -> getTableDef();
        $this -> isNew = $isNew;
        $tmp = $this -> getPrimaryKey();
        $tmp = $this -> getInternalPageSource();
        $this -> addFieldAttr = "";
        $s = $this -> pdo -> query( $this -> pageSource );
        $r = $s -> fetchAll( PDO::FETCH_ASSOC );
        
        $this -> firstRecord = $r[0]["id"];
    }
    /**
    * put your comment there...
    * 
    */
    public function setCurrentRecordId( $praefix, $id = 0 ) {
        $return = new \stdClass();
        $return -> id = $id;
        try{
            $_SESSION["DataFormCurrentRecord"] = $praefix . "_" . $id;
                $return -> success = true;
                $return -> message = "Der aktuelle Datensatz wurde erfolgreich gesetzt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    public function getCountAllRecords( $searchString = "" ) {
        $return = new \stdClass();
        try{
            $q = "select count(*) as count from " . $this -> table;
            if( $searchString !== "" ) {
                $q = $q . " WHERE " . str_replace( '\\', "",  $searchString );
            }
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $this -> countRecords = intval( $r[0]["count"] );
            if( $this -> isNew === "true" ) $this -> countRecords += 1;
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function sortTableDef( $structure ) {
        if( $this -> fields !== "*" ) {
            $tmpFields = explode( ",", $this -> fields );
            $b = $structure;
            $l = count( $tmpFields );
            $d = [];
            $i = 0;
            $m = count( $structure );
            while( $i < $l ) {
                $j = 0;
                while( $j < $m ) {
                    $v = $structure[$j]["Field"];
                    if( $v === $tmpFields[$i] ) $d[] = $structure[$j];
                    $j += 1;
                }
                $i += 1;
            }
            $structure = $d;
        }
        return $structure;
    }
    private function getTableDef() {
        $return = new \stdClass();
        try{
            if( $this -> fields === "*" ) {
                $query = "SHOW FULL COLUMNS FROM " .  $this -> table;
                
            } else {
                $t = explode( ",", $this -> fields );
                $l = count( $t);
                $i = 0;
                $s = "";
                while( $i < $l ) {
                    $s .= "'" . $t[$i] . "',";
                    $i += 1; 
                }
                $s = substr( $s, 0, strlen( $s ) - 1 );
                $query = "SHOW FULL COLUMNS FROM " .  $this -> table . " WHERE Field IN ($s);";
            }
            $stm = $this -> pdo -> query( $query );
            $return -> data = $stm -> fetchAll(PDO::FETCH_ASSOC);
            $l = count( $return -> data ); 
            $i = 0;
            while( $i < $l ) {
                $tmp = explode("(", $return -> data[$i]["Type"] );
                if( count( $tmp ) === 2 ) {
                    $return -> data[$i]["Type"] = $tmp[0];
                    $tmpC = explode(")", $tmp[1] ); 
                    $return -> data[$i]["length"] = $tmpC[0]; 
                } else {
                    $return -> data[$i]["length"] = null;
                }
                $i += 1;
            }
            $return -> data = $this -> sortTableDef( $return -> data );
            // insert as fields
            $t = explode( ",", $this -> fields );
            $l = count( $t );
            $i = 0;
            while( $i < $l ) {
                if( str_contains( $t[$i], " as " ) ) {
                    $fieldName = explode( " ", $t[$i] );
                    $tmp = [];
                    $tmp[$i]["Field"] = trim( $fieldName[2] );
                    $tmp[$i]["Type"] = "varchar";
                    $tmp[$i]["Collation: "] = "utf8mb4_general_ci";
                    $tmp[$i]["Null"] = "YES";
                    $tmp[$i]["Key"] = "";
                    $tmp[$i]["Default"] = "";
                    $tmp[$i]["Extra"] = "";
                    $tmp[$i]["Privileges"] = "";
                    $tmp[$i]["Comment"] = "Zusatzfeld";
                    $tmp[$i]["length"] = "1024";
                    //$tmp[] =  $t[$i];
                    $r1 = array_slice( $return -> data, 0, $i);
                    $r2 = array_slice( $return -> data, $i, count( $return -> data ) );
                    $return -> data = array_merge( $r1, $tmp, $r2 );
                            
                }
                $i += 1;
            }
            
            
            // end insert as fields
            $return -> success = true;
            $return -> message = "Daten erfolgreich gelesen.";

        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
        }        
        return $return;                            
    }
    private function getPrimaryKey() {
        $return = new \stdClass();
        $this -> primaryKey = new \stdClass();
        try{
            $q = "SHOW FULL COLUMNS FROM " .  $this -> table . " WHERE  `Key` = 'PRI'";                
            $s = $this -> pdo -> query( $q );
            $data = $s -> fetchAll(PDO::FETCH_ASSOC);
            $this -> primaryKey -> field = $data[0]["Field"];
            $this -> primaryKey -> type = $data[0]["Type"];
            $t = explode( "(", $this -> primaryKey -> type );
            if( count( $t ) === 2 ) {
                $this -> primaryKey -> type = $t[0];
                $this -> primaryKey -> length = trim(  str_replace( ")", "", $t[1] ) );    
            }
            $this -> primaryKey -> default = $data[0]["Default"];
            $this -> primaryKey -> comment = $data[0]["Comment"];
            $return -> success = true;
            $return -> message = "Der Primärschlüssel wurde erfolgreich gelesen";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
        
    }
    public function getInternalPageSource(  ) {
        $return = new \stdClass();
        try{
            $s = "select " . $this -> primaryKey -> field . " as primaryKey, ";
            if( $this -> fields === "*" ) {
                $l = count( $this -> structure -> data );
                $i = 0;
                while( $i < $l ) {
                    $s .= $this -> structure -> data[$i]["Field"] . ", ";
                    $i += 1; 
                }
            } else {
                $s .= str_replace( '\\', "", $this -> fields )  . ", ";
            }
            $this -> internalPageSource = substr( $s, 0, strlen( $s ) - 2 ) . " from " . $this -> table;
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getLabelDef( $widthLabels = "true", $labels = "" ) {
        $return = new \stdClass();
        try{
            if( $widthLabels === "true" ) {
                $t = explode( ";", $labels );
                $l = count( $this -> structure -> data );
                $i = 0;
                while( $i < $l ) {
                    if( $i == 0 && $t[$i] === "" ) {
                        $this ->labelDef[$i] = $this -> structure -> data[$i]["Field"];    
                    } else {
                        if( !isset( $t[$i] ) || $t[$i] === "null"  ) {
                            $this ->labelDef[$i] = $this -> structure -> data[$i]["Field"];
                        } else {
                            $this ->labelDef[$i] = $t[$i];
                        }
                    }
                    $i+= 1;
                }                
            } else {
                $this -> labelDef = [];
            }
            $return -> success = true;
            $return -> message = "Die Labeldefinitionen wurden erfolgreich erstellt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }
        //var_dump( $this ->labelDef );       
        return $return;                            
    }
    public function getListHeadLine( $recPraefix ) {
        $return = new \stdClass();
        try{
            $h = '<div id="' . $recPraefix . '_listHeadline" class="listHeadLine">';
            $l = count( $this -> labelDef );
            $i = 0;
            while( $i < $l ) {
                $h .= '<div id="' . $this -> fieldPraefix . '_o_' . $this -> structure -> data[$i]["Field"] . '">' . $this -> labelDef[$i] . '</div>';
                $i+= 1;
            }
            $h .= '</div>';
            $return -> html = $h;
            $return -> success = true;
            $return -> message = "Die Headline wurde erfolgreich erstellt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
        
    }
    public function getDataList( $searchString = "", $orderString = "" ) {
        $return = new \stdClass();
        try{
            
            if( $searchString === "" ) {
                $q = $this -> internalPageSource;
            } else {
                $q = $this -> internalPageSource . " WHERE " . str_replace( '\\', "",  $searchString );
            }
            if( $orderString !== "" && $searchString === "" ) {
                $q = $this -> internalPageSource . " ORDER BY " . $orderString;
            } else {
                if( $orderString !== "" ) {
                    $q .= " ORDER BY " . $orderString;
                }
            }
            if( $this -> countPerPage !== 0 ) {
                $q .= " LIMIT " . $this -> currentPage * $this -> countPerPage . ", " . $this -> countPerPage;
            }
            $s = $this -> pdo -> query( $q );
            $return -> data = $s -> fetchAll(PDO::FETCH_ASSOC);
            $this -> data = $return -> data;
            $this -> getCountAllRecords( $searchString );
            if( $this -> countPerPage !== 0 ) {
                $this -> countPages = $this -> countRecords / $this -> countPerPage;
                if( $this -> countPages !== intval( $this -> countRecords / $this -> countPerPage ) ) {
                    $this -> countPages = intval( $this -> countPages ) + 1;    
                }                
            } else {
                $this -> countPages = 1;
            }
            $return -> success = true;
            $return -> message = "Die Daten wurden erfolgreich gelesen.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    /**
    * get the html for a result width type "html"
    * 
    * @param mixed $fieldDefs            string - field definitions divide by ";" e.g. "input_text;button;..."
    * @param mixed $currentRecord
    * @param mixed $currentPage
    * @param mixed $countPerPage
    * @param mixed $searchString
    * @param mixed $orderString
    * @param mixed $hasSave
    * @param mixed $hasdelete
    * @param mixed $labels
    * @return stdClass
    */
    /**/
    public function getHTMLDataHtml( $searchString = "", $orderString = "", $fieldDefs = "", $recPraefix = "", $widthLabels = "true", $fieldsWidthLabel = "false", $labels ="", $currentRecord = "", $currentPage = "", $countPerPage = 10, $widthSave = "true", $widthDel = "true" ) {
    /*    $return = new \stdClass();
        try{
            if( $searchString === "" ) {
                $s = $this -> pdo -> query( $this -> internalPageSource );    
            } else {
                $q = $this -> internalPageSource . " WHERE " . str_replace( '\\', "",  $searchString );
                $s = $this -> pdo -> query( $q );
            }
            $return -> data = $s -> fetchAll(PDO::FETCH_ASSOC);
            $this -> data = $return -> data;
            $this -> countRecords = count( $this -> data );
            $return -> success = true;
            $return -> message = "Die Daten wurden erfolgreich gelesen.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;
        */                            
    }
    /**/
    /**
    * get the html for a dataform width type "list"
    * 
    * @param mixed $fieldDefs            string - field definitions divide by ";" e.g. "input_text;button;..."
    * @param mixed $currentRecord
    * @param mixed $currentPage
    * @param mixed $countPerPage
    * @param mixed $searchString
    * @param mixed $orderString
    * @param mixed $hasSave
    * @param mixed $hasdelete
    * @param mixed $labels
    * @return stdClass
    */
    public function getHTMLDataList( $searchString = "", $orderString = "", $fieldDefs = "", $recPraefix = "", $labels = "", $widthSave = "true", $widthDel = "true", $fieldsWidthDiv = "false", $fieldsWidthLabel = "false", $additionalButtons = [] ) {
        $return = new \stdClass();
        try{
/*
            if( $searchString !== "" ) {
                $this -> internalPageSource = $this -> internalPageSource . " WHERE " . str_replace( '\\', "",  $searchString );
            }
            if( $orderString !== "" ) {
                $this -> internalPageSource = $this -> internalPageSource . " ORDER BY " . $orderString;
            }
            if( $this -> countPerPage > 0 ) {
                $this -> internalPageSource = $this -> internalPageSource . " LIMIT " . $this -> currentPage * $this -> countPerPage . ", " . $this -> countPerPage;
            } else {
                //$this -> internalPageSource = $this -> internalPageSource . " LIMIT 0, " . $this -> countPerPage;
                
            }
            $s = $this -> pdo -> query( $this -> internalPageSource );            
            $return -> data = $s -> fetchAll(PDO::FETCH_ASSOC);
            //$return -> data = $this -> getDataList( $searchString, $orderString );
*/
            $return -> data = $this -> data;
            // $this -> getLabelDef( $widthLabels, $labels );
            $this -> getLabelDef( "true", $labels );
            $return -> labelLine = $this -> getListHeadLine( $recPraefix );
            if( isset( $return -> data ) ) {
                $l = count( $return -> data );
                $i = 0;
                $return -> html = "";
                while( $i < $l ) {
                    $return -> html .= $this -> getHTMLineList( $return -> data[$i], $fieldDefs, $recPraefix, $additionalButtons, $widthSave, $widthDel, $fieldsWidthDiv, $fieldsWidthLabel );
                    $i+= 1;
                }
            }
            $return -> success = true;
            $return -> message = "Die Daten wurden erfolgreich gelesen.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    public function setFieldaddAtr( $attr = "" ) {
        $this -> addFieldAttr = $attr;
    }
    /**
    * put your comment there...
    * 
    * @param mixed $data
    * @param mixed $fieldDefs              string, field definitions divide by ";"  (input_text, input_number, button...) see getHTML-functions
    * @param mixed $recPraefix
    * @param mixed $fieldPraefix
    * @param mixed $classPraefix
    * @param mixed $fieldWidthDiv          if "true", fields will have div tag
    * @param mixed $fieldsWidthLabel       if "true", fields will have label tag on field
    */
    public function getHTMLineList( $data, $fieldDefs, $recPraefix, $additionalButtons, $widthSave, $widthDel, $fieldsWidthDiv = "true", $fieldsWidthLabel = "false" ) {
        // set standard fielddef to input_text if fielddef not set
        $dFieldDefs = explode( ";", $fieldDefs );
        $l = count( $this -> structure -> data );
        $i = 0;
        while( $i < $l ) {
            //var_dump( $dFieldDefs[$i] );
            if( !isset( $dFieldDefs[$i] ) || is_null( $dFieldDefs[$i] ) || $dFieldDefs[$i] === ""  ) $dFieldDefs[$i] = "input_text";
            $i += 1; 
        }
        $dFieldaddAtr = explode( ";", $this -> addFieldAttr );
        $l = count( $this -> structure -> data );
        $i = 0;
        while( $i < $l ) {
            //var_dump( $dFieldDefs[$i] );
            if( !isset( $dFieldaddAtr[$i] ) || is_null( $dFieldaddAtr[$i] ) ) $dFieldaddAtr[$i] = "";
            $i += 1; 
        }
        
        // end set standard fielddef
        $h = '<div id="' . $recPraefix . '_rec_' . $data["primaryKey"] . '">';
        $l = count( $data ) - 1;
        $i = 0;
        while( $i < $l ) {
            if( str_contains( $this->structure->data[$i]["Field"], "as" ) ) {
                
            }
            $v = $data[ $this->structure->data[$i]["Field"]]; // value
            $t = $dFieldDefs[$i];                             // type
            $a = $dFieldaddAtr[$i];                          // additional attributes
            $lab = $this -> labelDef[$i];                     // label        
            if( substr( $t, 0, 6 ) == "select" || substr( $t, 0, 7 ) == "<option"|| substr( $t, 0, 4 ) == "list" ) {
                if( substr( $t, 0, 4 ) == "list" ) {
                    $tmp = $this -> getListSelect( 
                        $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                        $v, 
                        "cSelect " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                        $t,
                        $a,
                        $dFieldDefs[$i] 
                    );                    
                } else {
                    $tmp = $this -> getHTMLSelect( 
                        $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                        $v, 
                        "cSelect " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                        $t,
                        $a 
                    );
                }
                $tmp = $tmp -> html;
            } else {
                switch( $t ) {
                    case "recordPointer":
                        if( $data["primaryKey"] == $this -> currentRecord ) {
                            $tmp = $this -> getHTMLButton( 
                                $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"],
                                "<img src='library/css/icons/cTriangleRightBlack.png'>",
                                "cRecPointer " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                                $this->structure->data[$i]["Comment"]
                            );                            
                        } else {
                            $tmp = $this -> getHTMLButton( 
                                $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"],
                                "<img src='library/css/icons/cEmptyButton.png'>",
                                "cRecPointer " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                                $this->structure->data[$i]["Comment"]
                        );

                        }
                        if( $fieldsWidthDiv === "true" ) {
                            $tmp = "<div>" . $tmp -> html . "</div>";    
                        } else {
                            $tmp = $tmp -> html;    
                        }
                    break;
                    case "button":
                        $tmp = $this -> getHTMLButton( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cButton " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"], 
                            $this -> structure-> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "input_text":
                    case "input_number":
                    case "input_date":
                    case "input_time":
                    case "input_month":
                    case "input_week":
                    case "input_datetime":
                    case "input_datetime-local":
                    case "input_button":
                    case "input_password":
                    case "input_color":
                    case "input_email":
                    case "input_tel":
                    case "input_url":
                    case "input_range":
                        if( $t === "input_date" ) {
                            if( !is_null( $v ) ) {
                                $tmpVal = explode( " ", $v );
                                $v = $tmpVal[0];
                            } else {
                                
                            }
                        }
                        $tmp_type = ucfirst( explode( "_", $t )[1] );
                        $tmp = $this -> getHTMLInput(
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "c$tmp_type " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            explode( "_", $t )[1], 
                            $this -> structure -> data[$i]["length"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "checkbox":
                        $tmp = $this -> getHTMLCheckbox( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cCheckbox " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "div":
                        $tmp = $this -> getHTMLDiv( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cDiv " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "div":
                        $tmp = $this -> getHTMLLabel( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cLabel " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "textarea":
                        $tmp = $this -> getHTMLTextarea( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cTextarea " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["length"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "stars":
                        $tmp = $this -> getHTMLStars( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cStars " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "img":
                        $tmp = $this -> getHTMLImg( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cImg " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "bckg":
                        $tmp = $this -> getHTMLBckg( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_" . $data["primaryKey"], 
                            $v, 
                            "cImg " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                }
            }
            if( $fieldsWidthLabel === "true" ) {
                $tmp = "<label id='" . $this -> fieldPraefix . "_lab_" . $this -> structure->data[$i]["Field"] . "_" . $data["primaryKey"] . "' class='" . $this -> classPraefix . "_lab_" . $this -> structure->data[$i]["Field"] . "'>$lab</label>" . $tmp;    
            }
            if( $fieldsWidthDiv === "true" ) {
                $h .= "<div class='noValue'>$tmp</div>";    
            } else {
                $h .= $tmp;
            }
            $i+= 1;
        }
        $l = count( $additionalButtons );
        $i = 0;
        while( $i < $l ) {
            $tmp = $this -> getHTMLButton( 
                $this -> fieldPraefix . $additionalButtons[$i] -> id . "_" . $data["primaryKey"], 
                $additionalButtons[$i] -> value, 
                $additionalButtons[$i] -> class, 
                $additionalButtons[$i] -> title
            );
            $h .= $tmp -> html;
            $i += 1;
        }
        
        if( $widthSave === "true" ) {
            $tmp = $this -> getHTMLButton( 
                $this -> fieldPraefix . "_save_button_" . $data["primaryKey"], 
                "<img src='library/css/icons/cSave.png' id='" . $this -> fieldPraefix . "_img_save_button_" . $data["primaryKey"] . "'>", 
                "cButton " . $this -> classPraefix . "_save_button", 
                ""
            );
            $h .= $tmp -> html;
        }
        if( $widthDel === "true" ) {
            $tmp = $this -> getHTMLButton( 
                $this -> fieldPraefix . "_delete_button_" . $data["primaryKey"], 
                "<img src='library/css/icons/cDelete.png' id='" . $this -> fieldPraefix . "_img_delete_button_" . $data["primaryKey"] . "'>", 
                "cButton " . $this -> classPraefix . "_delete_button", 
                ""
            );
            $h .= $tmp -> html;
        }
        $h .= "</div>";
        
        return $h;
    }
    public function getHTMLineListNewRecord( $fieldDefs, $recPraefix, $labels = "", $widthSave = "true", $fieldsWidthLabel = "false", $fieldsWidthDiv="false", $additionalButtons = [] ) {
    
        $l = count( $this -> structure -> data );
        $i = 0;
        $d = [];
        while( $i < $l ) {
            if( isset( $this -> structure -> data[$i]["Default"] ) ) {
                $d[] = $this -> structure -> data[$i]["Default"];
            } else {
                $d[] = "";
            }
            $i += 1;
        }
        // set standard fielddef to input_text if fielddef not set
        $dFieldDefs = explode( ";", $fieldDefs );
        $l = count( $this -> structure -> data );
        $i = 0;
        while( $i < $l ) {
            //var_dump( $dFieldDefs[$i] );
            if( !isset( $dFieldDefs[$i] ) || is_null( $dFieldDefs[$i] ) || $dFieldDefs[$i] === ""  ) $dFieldDefs[$i] = "input_text";
            $i += 1; 
        }
        $dFieldaddAtr = explode( ";", $this -> addFieldAttr );
        $l = count( $this -> structure -> data );
        $i = 0;
        while( $i < $l ) {
            //var_dump( $dFieldDefs[$i] );
            if( !isset( $dFieldaddAtr[$i] ) || is_null( $dFieldaddAtr[$i] ) ) $dFieldaddAtr[$i] = "";
            $i += 1; 
        }
        
        // end set standard fielddef
        $this -> getLabelDef( $fieldsWidthLabel, $labels );
        $h = '<div id="' . $recPraefix . '_rec_new">';
        $l = count( $d );
        $i = 0;
        while( $i < $l ) {
            $v = $d[$i]; // value
            $t = $dFieldDefs[$i];                             // type
            $a = $dFieldaddAtr[$i];
            if( $this -> labelDef === [] ) {                  // label
                $lab = $this->structure->data[$i]["Field"];    
            } else {
                $lab = $this -> labelDef[$i];
            }
                                         
            if( substr( $t, 0, 6 ) == "select" || substr( $t, 0, 7 ) == "<option" || substr( $t, 0, 4 ) == "list" ) {
                if( substr( $t, 0, 4 ) == "list" ) {
                   $tmp = $this -> getListSelect( 
                        $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                        $v, 
                        "cSelect " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                        $t,
                        $a,
                        $dFieldDefs[$i] 
                    );                                        
                } else {
                    $tmp = $this -> getHTMLSelect( 
                        $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                        $v, 
                        "cSelect " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                        $t,
                        $a 
                    );
                }
                $tmp = $tmp -> html;
            } else {
                switch( $t ) {
                    case "recordPointer":
                        $tmp = $this -> getHTMLButton( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new",
                            "<img src='library/css/icons/cStarBlack.png'>",
                            "cRecPointer " . $this -> classPraefix . "_" . $this->structure->data[$i]["Field"],
                            $this->structure->data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "button":
                        $tmp = $this -> getHTMLButton( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "cButton " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"], 
                            $this -> structure-> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "input_text":
                    case "input_number":
                    case "input_date":
                    case "input_time":
                    case "input_month":
                    case "input_week":
                    case "input_datetime":
                    case "input_datetime-local":
                    case "input_button":
                    case "input_password":
                    case "input_color":
                    case "input_email":
                    case "input_tel":
                    case "input_url":
                    case "input_range":
                        if( $t === "input_date" ) {
                            $tmpVal = explode( " ", $v );
                            $v = $tmpVal[0];
                        }
                        $tmp_type = ucfirst( explode( "_", $t )[1] );
                        $tmp = $this -> getHTMLInput(
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "c$tmp_type " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            explode( "_", $t )[1], 
                            $this -> structure -> data[$i]["length"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "checkbox":
                        $tmp = $this -> getHTMLCheckbox( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "cCheckbox " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "div":
                        $tmp = $this -> getHTMLDiv( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "cDiv " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "label":
                        $tmp = $this -> getHTMLLabel( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "cLabel " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "img":
                        $tmp = $this -> getHTMLImg( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "cImg " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                    case "bckg":
                        $tmp = $this -> getHTMLBckg( 
                            $this -> fieldPraefix . "_" . $this->structure->data[$i]["Field"] . "_new", 
                            $v, 
                            "cImg " . $this -> classPraefix . "_" . $this -> structure->data[$i]["Field"],
                            $this -> structure -> data[$i]["Comment"]
                        );
                        $tmp = $tmp -> html;
                    break;
                }
            }
            if( $fieldsWidthLabel === "true" ) {
                $tmp = "<label id='" . $this -> fieldPraefix . "_lab_" . $this -> structure->data[$i]["Field"] . "_new' class='" . $this -> classPraefix . "_lab_" . $this -> structure->data[$i]["Field"] . "'>$lab</label>" . $tmp;    
            }
            //$h .= $tmp;
            if( $fieldsWidthDiv === "true" ) {
                $h .= "<div class='noValue'>$tmp</div>";    
            } else {
                $h .= $tmp;
            }
            $i+= 1;
        }
        $l = count( $additionalButtons );
        $i = 0;
        while( $i < $l ) {
            $tmp = $this -> getHTMLButton( 
                $this -> fieldPraefix . $additionalButtons[$i] -> id . "_new", 
                $additionalButtons[$i] -> value, 
                $additionalButtons[$i] -> class, 
                $additionalButtons[$i] -> title
            );
            $h .= $tmp -> html;
            $i += 1;
        }
        if( $widthSave === "true" ) {
            $tmp = $this -> getHTMLButton( 
                $this -> fieldPraefix . "_save_button_new", 
                '<img src="library/css/icons/cSave.png" id="' . $this -> fieldPraefix . '_img_save_button_new">', 
                "cButton " . $this -> classPraefix . "_save_button", 
                ""
            );
            $h .= $tmp -> html;
        }
        $h .= "</div>";
        
        return $h;
    
    
    }
    public function getCountRecords() {
        return $this -> countRecords;
    }
    public function getCountPages( $countPerPage ) {
        $return = new \stdClass();
        try{
            if( $countPerPage === "" || $countPerPage === "0" ) {
                return 1;
            } else {
                if( $this -> getCountRecords() / intval( $countPerPage ) === intval( $this -> getCountRecords() / intval( $countPerPage ) ) ) {
                    return intval( $this -> getCountRecords() / intval( $countPerPage ) );    
                } else {
                    return intval( $this -> getCountRecords() / intval( $countPerPage ) ) + 1;
                }
            }
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }

    private function getHTMLButton( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html = '<button id="' . $id . '" class="' . $class . '" title="' . $title . '">' . $value . '</button>';    
            $return -> success = true;
            $return -> message = "Der Button wurde erfolgreich erstellt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLInput( $id, $value, $class, $type, $maxlength = 0, $title = "" ) {
        $return = new \stdClass();
        try{
            $t = explode( "_", $type );
            if( isset( $value ) && strrpos($value, '"' ) ) {
            //if( strrpos($value, '\"')  ) {
                $return -> html = '<input id="' . $id . '" class="' . $class . '" type="' . $type . '" value=\'' . $value . '\' maxlength="' . $maxlength . '" title="' . $title . '">';
            } else {
            //$value = add_slashes_recursive( $value );
                /*
                if( str_contains($value, "'") ) {
                    $value = str_replace( "'", "&apos;", $value);
                }
                */
                $return -> html = '<input id="' . $id . '" class="' . $class . '" type="' . $type . '" value="' . $value . '" maxlength="' . $maxlength . '" title="' . $title . '">';                
            }
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLCheckbox( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            if( $value == 1 ) {
                $return -> html =  '<input id="' . $id . '" class="' . $class . '" type="checkbox" checked title="' . $title . '">';
            } else {
                $return -> html =  '<input id="' . $id . '" class="' . $class . '" type="checkbox" title="' . $title . '">';
            }
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLDiv( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html =  '<div id="' . $id . '" class="' . $class . '" title="' . $title . '">' . $value . '</div>';
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLLabel( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html =  '<label id="' . $id . '" class="' . $class . '" title="' . $title . '">' . $value . '</label>';
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLImg( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html =  '<img id="' . $id . '" class="' . $class . '" title="' . $title . '" src="' . $value . '">';
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLBckg( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html =  '<div id="' . $id . '" class="' . $class . '" title="' . $title . '" style="background-image: url(' . $value . ');"></div>';
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLTextarea( $id, $value, $class, $maxlength = 0, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html = '<textarea id="' . $id . '" class="' . $class . '" maxlength="' . $maxlength . '" title="' . $title . '">' . $value . '</textarea>';
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLStars( $id, $value, $class, $title = "" ) {
        $return = new \stdClass();
        try{
            $return -> html = '<div id="' . $id . '">';
                $return -> html .= '<div style="position: relative">';
                $return -> html .= '<div class="' . $class . '"><img  style="position: relative; z-index: 1;" src="library/css/icons/star_bar.png"><div style="position: relative; background-color: yellow; height: 20px; top: -27px; width: ' . ( 2 + ( $value * 23.4 ) ) . 'px"></div></div>';
                $return -> html .= '</div>';
            $return -> html .= '</div>';
            $return -> success = true;
            $return -> message = "";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getSelectField( $source, $value ) {
        $return = new \stdClass();
        if( substr( $source, 0, 6) == "select" ) {
            try{
                $source = str_replace('\\', "", $source );
                $stm = $this -> pdo -> query( $source );
                $return -> data = $stm -> fetchAll();
                $l = count( $return -> data );
                $i = 0;
                $fieldStr = "";
                while( $i < $l ) {
                    if( $return -> data[$i][0] == $value ) {
                        $fieldStr .= '<option value="' . $return -> data[$i][0] . '" selected>' . $return -> data[$i][1] . "</option>\n"    ;
                    } else {
                        $fieldStr .= '<option value="' . $return -> data[$i][0] . '">' . $return -> data[$i][1] . "</option>\n"    ;
                    }
                    $i += 1;
                }
                
                $return -> message = "Daten erfolgreich gelesen.";
            } catch( Exception $e ) {
                    $return -> success = false;
                    $return -> message = "Fehler aufgetreten:" . $e -> getMessage() . ".";
            }        
                
        } else {
            $tmp = explode( "</option>", $source );
            $l = count( $tmp );
            $i = 0;
            $fieldStr = "";
            while ( $i < $l ) {
                $tmpVal = explode( "'", $tmp[$i] );
                if( isset( $tmpVal[1] ) ) {
                    if( str_replace( '\\', "", $tmpVal[1] ) == $value ) {
                        $fieldStr .= str_replace( '\\', "", $tmpVal[0] )  . '"' . str_replace( '\\', "", $tmpVal[1] ) . '" selected' . $tmpVal[2] . "</option>\n"    ;
                    } else {
                        $fieldStr .= str_replace( '\\', "", $tmpVal[0] )  . '"' . str_replace( '\\', "", $tmpVal[1] ) . '"' . $tmpVal[2] . "</option>\n"    ;                
                    }
                }
                $i += 1;
            }
        }
        return $fieldStr;                            
    }
    private function getListSelect( $id, $value, $class, $source, $addAtr = "", $listVar = "" ) {
        $return = new \stdClass();
        try{
            $tmpHtml = $this -> getSelectField( $source, $value );
            $return -> html = '<select id="' . $id . '" class="' . $class . '" ' . $addAtr . ' data-value="' . $value . '" data-list="' . $listVar . '"></select>';
            $return -> success = true;
            $return -> message = "Das Select-Feld wurde erfolgreich erstellt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    private function getHTMLSelect( $id, $value, $class, $source, $addAtr = "", $prevOption = "", $afterOption = "" ) {
        $return = new \stdClass();
        try{
            $tmpHtml = $this -> getSelectField( $source, $value );
            $return -> html = '<select id="' . $id . '" class="' . $class . '" ' . $addAtr . '>' . "\n" . $prevOption . $tmpHtml . $afterOption . "\n</select>";
            $return -> success = true;
            $return -> message = "Das Select-Feld wurde erfolgreich erstellt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;                            
    }
    public function saveRecord( $data, $currentRecord ) {
        $return = new \stdClass();
        try {
            $l = count( $data );
            $i = 0;
            $q = "";
            while( $i < $l ) {
                if( $data[$i] -> id !== $this -> primaryKey -> field ) {
                    if( is_array( $data[$i] -> value ) ) {
                        $m = count( $data[$i] -> value );
                        $j = 0;
                        $str = "";
                        while( $j < $m ) {
                            $str .= $data[$i] -> value[$j] . ", ";
                            $j += 1;
                        }
                        $str = substr( $str, 0, strlen( $str ) - 2 );
                        $q .= "`" . $data[$i] -> id . "` = '" . $str . "', ";
                    } else {
                        $q .= "`" . $data[$i] -> id . "` = '" . $data[$i] -> value . "', ";    
                    }  
                }
                $i += 1;
            }
            $q = substr( $q, 0, strlen( $q ) - 2 );
            $q = "update " . $this -> table . " set " . $q . " where " . $this -> primaryKey -> field . " = " . $currentRecord;
            $this -> pdo -> query( $q );
            $return -> success = true;
            $return -> message = "Der Datensatz wurde erfolgreich gespeichert.";
        } catch( Exception $e ) {
            $return -> success = false;
            if( $e -> getCode() === "23000" ) {
                $return -> message = "Dieser Datensatz kann nicht gespeichert werden, da er bereits existiert.";
            } else {
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
            }
        }        
        return $return;
    }
    public function newRecord( $data ) {
        $return = new \stdClass();
        try {
            $l = count( $data );
            $i = 0;
            $q = "(";
            while( $i < $l ) {
                if( $data[$i] -> id === $this -> primaryKey -> field ) {
                    
                } else {
                    $q .= "`" . $data[$i] -> id . "`, ";  
                }
                $i += 1;
            }
            $q = substr( $q, 0, strlen( $q ) - 2 ) .  ") VALUES( ";
            $l = count( $data );
            $i = 0;
            while( $i < $l ) {
                if( $data[$i] -> id === $this -> primaryKey -> field ) {
                    
                } else {
                    if( is_array( $data[$i] -> value ) ) {
                        $m = count( $data[$i] -> value );
                        $j = 0;
                        $str = "";
                        while( $j < $m ) {
                            $str .= $data[$i] -> value[$j] . ", ";
                            $j += 1;
                        }
                        $str = substr( $str, 0, strlen( $str ) - 2 );
                        $q .= "'" . $str . "', ";    
                    } else {
                        $q .= "'" . $data[$i] -> value . "', ";    
                    }      
                }
                $i += 1;
            }
            $q = "insert into " . $this -> table . " " . substr( $q, 0, strlen( $q ) - 2 ) .  ")";
            
            $this -> pdo -> query( $q );
            $return -> success = true;
            $return -> message = "Der Datensatz wurde erfolgreich angelegt.";
            $return -> newId = $this -> pdo -> lastInsertId();
        } catch( Exception $e ) {
            $return -> success = false;
            if( $e -> getCode() === "23000" ) {
                $return -> message = "Dieser Datensatz kann nicht angelegt werden, da er bereits existiert.";
            } else {
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
            }
        }        
        return $return;
    }
    public function deleteRecord( $currentRecord ) {
        $return = new \stdClass();
        try {
            $q = "select " . $this -> fields . " from " . $this -> table . " where " . $this -> primaryKey -> field . " = " . $currentRecord;
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $return -> oldData = $r[0];
            $q = "delete from " . $this -> table . " where " . $this -> primaryKey -> field . " = " . $currentRecord;
            $this -> pdo -> query( $q );
            $return -> success = true;
            $return -> message = "Der Datensatz wurde erfolgreich gelöscht.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;
    }
    public function deleteBoundRecords( $whereClausel ) {
        $return = new \stdClass();
        try {
            $q = "delete from " . $this -> table . " where " . $whereClausel;
            $this -> pdo -> query( $q );
            $return -> success = true;
            $return -> message = "Die Datensätze wurde erfolgreich gelöscht.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;
    }
    public function getPagination( $searchString ) {
        $return = new \stdClass();
        try {
            /* get count records for searchstr */
            if( $searchString === "" ) {
                $q = "select * from " . $this -> table;
            } else {
                $q = "select * from " . $this -> table . " WHERE " . str_replace( '\\', "",  $searchString );
            }
            $s = $this -> pdo -> query( $q );
            $r = $s -> fetchAll( PDO::FETCH_ASSOC );
            $c = count( $r );
            if( $this -> countPerPage !== 0 ) {
                if( $c / $this -> countPerPage < 1 ) {
                    $l = 1;
                    $this -> currentPage = 0;
                } else {
                    if( $c / $this -> countPerPage === intval( $c / $this -> countPerPage ) ) {
                        $l = $c / $this -> countPerPage;    
                    } else {
                        $l = intval( $c / $this -> countPerPage ) + 1;
                    }
                   
                }
            }
            $i = 0;
            $h = "<div id='" . $this -> fieldPraefix . "_Pagination' class='" . $this -> classPraefix . "_Pagination'><a href='#'  id='" . $this -> fieldPraefix . "_pag_firstPage" . "'>«</a><a href='#'  id='" . $this -> fieldPraefix . "_pag_prevPage" . "'>‹</a><span>...</span>";
/*
            $l_1 = 5 - $this -> currentPage;
            if( $l_1 < 0 ) {
                $l = $this -> currentPage - 5;        
            }
*/
            $l = 5;
            while( $i < $l ) {
                if( $i === $this -> currentPage ) {
                    $h .= '<a href="#" id="' . $this -> fieldPraefix . "_pag_" . $i + 1 . '" class="' . $this -> classPraefix . '_activePage">' . ( $i + 1 ) . '</a>';                    
                } else {
                    $h .= '<a href="#" id="' . $this -> fieldPraefix . "_pag_" . $i + 1 . '">' . ( $i + 1 ) . '</a>';                    
                }
                $i += 1;
                if( $i === COUNT_PAGINATION ) break;
            }
            $h .= "<span>...</span><a href='#'  id='" . $this -> fieldPraefix . "_pag_nextPage" . "'>›</a><a href='#'  id='" . $this -> fieldPraefix . "_pag_lastPage" . "'>»</a></div>";
            $return -> html = $h;
            //$return -> countPages = $l;
            $return -> success = true;
            $return -> message = "Die Paginierung wurde erfolgreich erstellt.";
        } catch( Exception $e ) {
                $return -> success = false;
                $return -> message = "Fehler aufgetreten: '" . $e -> getMessage() . "'.";
        }        
        return $return;
        
    }
}
?>
