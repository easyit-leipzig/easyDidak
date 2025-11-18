<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Lang" content="en">
<meta name="author" content="">
<meta http-equiv="Reply-to" content="@.com">
<meta name="generator" content="PhpED 8.0">
<meta name="description" content="">
<meta name="keywords" content="">
<meta name="creation-date" content="09/06/2012">
<meta name="revisit-after" content="15 days">
<title>Administion</title>
</head>
<body>
<div>
<h1>Setze Gruppen für Zeit</h1><br>
    <button id="setGroupForTime">Setze Gruppen für Zeit</button>
</div>
<div>
<h1>SQL umschreiben</h1><br>
    <button id="rewriteSQL">rewriteSQL</button>
</div>
<div>
<h1>SQL nach easyDidak</h1><br>
    <button id="copySQL">SQL nach easyDidak</button>
    Drop hinzufügen&nbsp;&nbsp;
    <input type="checkbox" id="withDrop" checked>
</div>
<div>
<h1>Lernthemen bearbeiten</h1><br>
    <button id="editLernthemen">rewriteSQL</button>
</div>
<div>
<h1>Werte Datenmaske übertragen</h1>
<p>Zerlegt die Bemerkungswerte der Tabelle mtr_rueckkopplung_datenmaske und trägt diese zerlegt in mtr_rueckkopplung_datenmaske_values ein.</p>
    <button id="migrateDatenmaske">Zerlege Datenmaske</button>
</div>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>
<script src="library/javascript/const_main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/Dialog2.0.1.js"></script>
<script src="library/javascript/Field2.0.1.js"></script>
<script src="library/javascript/MessageDR.js"></script>
<script src="library/javascript/RecordSet2.0.1.js"></script>
<script src="library/javascript/DataForm2.0.1.js"></script>
<script src="library/javascript/tippy_core.js"></script>
<script src="library/javascript/tippy.js"></script>
<script src="library/javascript/init_admin.js"></script>
<script>
    <?php
require_once("library/php/getOS.php");
    $settings = parse_ini_file('ini/settings.ini', TRUE);
    $dns = $settings['database']['type'] . 
                ':host=' . $settings['database']['host'] . 
                ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
                ';dbname='. $settings['database']['schema'];
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
   ?>
var Df_1 = new DataForm( { 
    dVar: "Df_1", 
    id: "#Df_1", 
    table: "std_lernthema",
    /*1    id Primärschlüssel    int(11)            Nein    kein(e)        AUTO_INCREMENT    Bearbeiten Bearbeiten    Löschen Löschen    
    2    quelle_id    int(11)            Nein    kein(e)            Bearbeiten Bearbeiten    Löschen Löschen    
    3    fach_id    int(11)            Nein    1            Bearbeiten Bearbeiten    Löschen Löschen    
    4    klassenstufe    int(11)            Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    5    schulform    varchar(20)    utf8mb4_general_ci        Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    6    lernthema    varchar(
    */
    fields: "id,quelle_id,fach_id,klassenstufe,schulform,lernthema",
    addPraefix: "df1_",
    formType: "form",
    formWidth: 800,
    autoOpen: false,
    boundForm: ["Df_2"] ,
    boundFields: [{"from": "id", "to": "std_lernthema_id"},],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
    hasHelp: false,
    fieldDefinitions: [
        {
            type: "recordPointer",
            value: "&nbsp;",
            field: "recordPointer",
            baseClass: "cButtonMiddle",
        },
        {
            field: "id",
            label: "Id",
            type: "input_text",

        },
        {
            field: "quelle_id",
            label: "quelle_id",
            type: "input_text",

        },
        {
            field: "fach_id",
            label: "fach_id",
            type: "input_text",

        },
        {
            field: "klassenstufe",
            label: "klassenstufe",
            type: "input_number",

        },
        {
            field: "schulform",
            label: "schulform",
            type: "input_text",

        },
        {
            field: "lernthema",
            label: "lernthema",
            type: "input_text",

        },
        {
            field: "openInhalt",
            label: "Inhalt",
            type: "input_button",

        },
        ],
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    //afterBuild: function(){setTooltips()},
    //afterNew: function(){setGroup()},
    //filter: "id=0",
/*
    orderArray: ["val_varchar", "val_int"],
*/
    searchArray: [
 /*
            {
                field: "val_select",
                type: "select",
                options: "<option value='>-1'>alle</option>" + optRole,
            },
            {
                field: "val_select_multi",
                type: "select",
                options: "<option value='>-1'>alle</option>" + optRole,
                addAtr: "multiple",
                value: ">-1",
                sel: "value",
            },
            {
                field: "val_checkbox",
                type: "select",
                options: "<option value='>-1'>alle</option><option value=0>aus</option><option value='1'>an</option>",
                value: ">-1",
                sel: "value",
            },
 */
        ]
    /*additionalFields: additionalFields, */
} );
var Df_2 = new DataForm( { 
    dVar: "Df_1", 
    id: "#Df_1", 
    table: "_std_lernthema_inhalt",
    fields: "id,std_lernthema_id,inhalt",
});
init();
</script>
</body>
</html>
