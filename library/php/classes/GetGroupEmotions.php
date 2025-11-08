<?php
    class GroupEmotions {
        private     $pdo;
        public      $parm;
        public      $table;
        private     $record;
        public function __construct( $param ) {
            // content
            $this -> pdo = $param -> pdo;
            $this-> record = [];
            $q = "";
            if( !isset( $param -> table )) $param -> table = "frzk_group_semantische_dichte";
            if( isset( $param -> id) ) {
                $q = "select emotions from " . $param -> table . " where id = " . $param -> id;
            } else {
                if( isset( $param -> date) and isset( $param -> group_id ) ) {
                    $q = "select emotions from " . $param -> table . " where zeitpunkt = '" . $param -> date . "' and gruppe_id = " . $param -> group_id;
                    
                }
            }
            if( $q !== "" ) {
                $this-> record = $this -> pdo -> query( $q ) -> fetchAll();
            }
        }
        public function get( $param ) {
            return $this-> record;
        }
        public function aggregate( $param ) {
            
            return $this-> record;
        }
  }
?>
