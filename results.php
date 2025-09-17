<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Unterrichtseinheiten</title>

    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="stylesheet prefetch" href="library/css/Dataform20.css">
    <link rel="stylesheet prefetch" href="library/css/lesson.css">

</head>

<body>
<h1>Unterrichtseinheiten</h1>
<h2>Einheiten</h2>
<div id="Df"></div>
<h2>Details</h2>
<div id="Df_2"></div>
<h2>Zuweisung Themen</h2>
<div id="Df_3"></div>
<div id=dialog_teilnehmer>
    <div id="std_teilnehmer"></div>
    <div id="tln_bewertung"></div>

</div>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>

<script src="library/javascript/const_main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/DialogDR.js"></script>
<script src="library/javascript/Field2.0.1.js"></script>
<script src="library/javascript/RecordSet2.0.1.js"></script>
<script src="library/javascript/DataForm2.0.1.js"></script>
<script src="library/javascript/MessageDR.js"></script>
<script src="library/javascript/lessons.js"></script>
<script>
</script>
<script>
    <?php
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
    $q = "SELECT id as value, concat(tag, ' ', uhrzeit_start) as text from ue_gruppen";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->value . '">' . $r[$i]->text . '</option>';
        $i += 1;
    }
    print_r( "var list_gruppen = '" . $option . "';\n" );
    $q = "SELECT id as value, kurzbezeichnung as text from _mtr_definition_zieltyp";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->value . '">' . $r[$i]->text . '</option>';
        $i += 1;
    }
    print_r( "var list_zieltyp = '" . $option . "';\n" );
    $q = "SELECT * FROM `_mtr_definition_lernmethode`";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->id . '">' . $r[$i]->bezeichnung . '</option>';
        $i += 1;
    }
    print_r( "var list_lernmethode = '" . $option . "';\n" );
    $q = "SELECT * FROM `_std_schulform`";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->id . '">' . $r[$i]->schulform . '</option>';
        $i += 1;
    }
    print_r( "var list_schulform = '" . $option . "';\n" );
    $q = "SELECT * FROM `std_teilnehmer` order by Vorname";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->id . '">' . $r[$i]->Vorname . ' ' . $r[$i]->Nachname . '</option>';
        $i += 1;
    }
    print_r( "var list_teilnehmer = '" . $option . "';\n" );
   ?>
// Df;
var Df = new DataForm( { 
    dVar: "Df", 
    id: "#Df", 
    table: "ue_unterrichtseinheit", 
//    fields: "id,val_varchar,val_dec,val_int,val_select,val_select_multi,val_img,val_checkbox,val_stars",
    fields: "id,gruppe_id,Beschreibung",
    addPraefix: "df1_",
    formType: "html", 
    boundForm: ["Df_2"] ,
    boundFields: [{"from": "id", "to": "ue_id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
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
            field: "gruppe_id",
            label: "Gruppe",
            title: "Gruppe auswählen",
            type: "select",
            addClasses: "",
            options: list_gruppen,
        },
        {
            field: "Beschreibung",
            label: "Beschreibung",
            type: "input_text",
            valid: ["not empty", "minlength 3"],
            title: "Bemerkungen",
        },

    ],
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    //filter: "id = '1'",
    orderArray: [],
    searchArray: [
        ],
} );

var Df_2 = new DataForm( { 
    dVar: "Df_2", 
    id: "#Df_2", 
    table: "ue_unterrichtseinheit_zuweisung",
    fields: "id,ue_id,datum,startzeit,dauer,bemerkung",
    addPraefix: "df2_",
    formType: "html",
    boundForm: ["Df_3"] ,
    boundFields: [{"from": "id", "to": "ue_zw_unterrichtseinheit_id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
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
            field: "ue_id",
            label: "ue_id",
            type: "input_text",


        },
        {
            field: "datum",
            label: "datum",
            type: "input_date",
            default: new Date().toJSON().slice(0, 10),
        },
        {
            field: "startzeit",
            label: "startzeit",
            type: "select",
            options: "<option value='14:00:00'>14:00</option><option value='15:35:00'>15:35</option><option value='17:10:00'>17:10</option>",
        },
        {
            field: "dauer",
            label: "dauer",
            type: "input_number",
            default: 90,
            minValue: 1,
            maxValue: 180,
        },
        {
            field: "bemerkung",
            label: "bemerkung",
            type: "input_text",
        },
    ],
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    filter: undefined,
        afterBuild: function(){}

} );
var Df_3 = new DataForm( { 
    dVar: "Df_3", 
    id: "#Df_3", 
    table: "ue_unterrichtseinheit_zw_thema",
    fields: "id,ue_zw_unterrichtseinheit_id,schulform_id,zieltyp_id,lernmethode_id,std_lernthema_id,thema,dauer,teilnehmer_id,beschreibung",
    addPraefix: "df3_",
    formType: "html",
    boundForm: ["Df_4"] ,
    boundFields: [{"from": "id", "to": "ue_unterrichtseinheit_thema_id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
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
            field: "ue_zw_unterrichtseinheit_id",
            label: "Id",
            type: "input_text",

        },

        {
            field: "schulform_id",
            label: "schulform_id",
            type: "select",
            options: "<option value='<-1'>alle</option>" + list_schulform,
            onChange: function(){changeSchulform(this.id)},

        },

        {
            field: "zieltyp_id",
            label: "Ziel",
            type: "select",
            options: list_zieltyp,

        },

        {
            field: "lernmethode_id",
            label: "lernmethode_id",
            type: "select",
            options: list_lernmethode,
            default: 24,

        },
        {
            field: "std_lernthema_id",
            label: "std_lernthema_id",
            type: "input_text",
            onChange: function(){changeLernthema(this.id)},
            valid: ["not empty"],
        },
        {
            field: "thema",
            label: "thema",
            type: "input_text",
        },
        {
            field: "dauer",
            label: "dauer",
            type: "select",
            options: "<option value='15'>15</option><option value='30'>30</option><option value='45'>45</option><option value='60'>60</option><option value='75'>75</option><option value='90'>90</option>"
        },
        {
            field: "teilnehmer_id",
            label: "Teilnehmer",
            type: "select",
            addAttr: "multiple data-clickable",
            options: list_teilnehmer,
        },
        {
            field: "beschreibung",
            label: "beschreibung",
            type: "input_text",
        },
    ],
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    filter: undefined,
    afterBuild: function(){}

} );
var Df_5 = new DataForm( { 
    dVar: "Df_5", 
    id: "#std_teilnehmer", 
    table: "std_teilnehmer", 
//    fields: "id,val_varchar,val_dec,val_int,val_select,val_select_multi,val_img,val_checkbox,val_stars",
    fields: "id,Vorname,Nachname,geschlecht,geburtstag,Klassenstufe,KlassentypID",
    addPraefix: "df1_",
    formType: "html", 
    boundForm: ["Df_6"] ,
    boundFields: [{"from": "id", "to": "teilnehmer_id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
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
            field: "Vorname",
            label: "Vorname",
            type: "input_text",

        },
        {
            field: "Nachname",
            label: "Nachname",
            type: "input_text",

        },
        {
            field: "geschlecht",
            label: "geschlecht",
            type: "select",
            options: "<option value='>-1'>alle</option><option value='1'>männlich</option><option value='2'>weiblich</option><option value='3'>divers</option>"

        },
        {
            field: "geburtstag",
            label: "geburtstag",
            type: "input_date",

        },
        {
            field: "Klassenstufe",
            label: "Klassenstufe",
            type: "input_number",    

        },
        {
            field: "KlassentypID",
            label: "KlassentypID",
            type: "select",    
            options: list_schulform 
        },

    ],

    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    orderArray: ["Vorname", "Nachname"],
    //filter: "id = '1'",
} );
var Df_6 = new DataForm( { 
    dVar: "Df_6", 
    id: "#tln_bewertung", 
    table: "mtr_persoenlichkeit", 
    fields: "id,teilnehmer_id,datum,offenheit_erfahrungen",
    addPraefix: "df6_",
    formType: "html", 
    validOnSave: false,
    classButtonSize: "cButtonMiddle",
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
            field: "teilnehmer_id",
            label: "teilnehmer_id",
            type: "input_text",

        },
        {
            field: "datum",
            label: "datum",
            type: "input_date",
            default: new Date().toJSON().slice(0, 10),

        },
        {
            field: "offenheit_erfahrungen",
            label: "offenheit_erfahrungen",
            type: "input_number",
            Comment: "",
            minValue: 1,
            maxValue: 6,

        },

    ],

    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    //filter: "id = '1'",
} );
var myDia;
        myDia = new DialogDR({
        dVar: "myDia", 
        width: 800,
        height: 600,
        addClassFiles: "DialogNew.css dialog_easyit.css",
        hasClose: true,
        hasMin: true,
        hasMax: true,
        rootPropertyPraefix: 'dialog-',
        canResize: false,
        hasInfo: false,
        hasHelp: false,
    });

(function() {
    Df.init();
    Df_2.init();
    Df_3.init();
    Df_5.init();
    Df_6.init();
    init();
})();
</script>
</body>
</html>
