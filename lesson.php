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
<div>Über diesen Link geht es zur Teilnehmerverwaltung <a href="#" id="showTN">Teilnehmerverwaltung</a></div>
<h1>Unterrichtseinheiten</h1>
<h2>Einheiten</h2>
<div id="Df"></div>
<div id="Df_2"></div>
<h2>Zuweisung Themen</h2>
<div id="Df_3"></div>
<h2>Zuweisung Teinehmer</h2>
<div id="Df_4"></div>
<h2>Bewertung Teilnehmer</h2>
<div id="Df_8"></div>
<h2>Bewertung Didaktik</h2>
<div id="Df_9"></div>

<div id=dialog_teilnehmer>
    <div id="std_teilnehmer"></div>
    <div id="tln_bewertung"></div>

</div>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>

<script src="library/javascript/const_main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/Dialog2.0.1.js"></script>
<script src="library/javascript/Field2.0.1.js"></script>
<script src="library/javascript/RecordSet2.0.1.js"></script>
<script src="library/javascript/DataForm2.0.1.js"></script>
<script src="library/javascript/MessageDR.js"></script>
<script src="library/javascript/tippy_core.js"></script>
<script src="library/javascript/tippy.js"></script>
<script src="library/javascript/init_lessons.js"></script>
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
    $q = "SELECT id as value, kurzbezeichnung as text, beschreibung from _mtr_definition_zieltyp order by kurzbezeichnung";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option title="' . $r[$i]->beschreibung . '"  value="' . $r[$i]->value . '">' . $r[$i]->text . '</option>';
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
    $q = "SELECT * FROM `std_teilnehmer` where show_tn=1 order by Vorname";
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
    $q = "SELECT * FROM `_ue_fach`";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->id . '">' . $r[$i]->fach . '</option>';
        $i += 1;
    }
    print_r( "var list_fach = '" . $option . "';\n" );
    $q = "SELECT * FROM `_mtr_emotionen` order by emotion";
    $s = $db_pdo -> query( $q );
    $r = $s -> fetchAll( PDO::FETCH_CLASS );
    $l = count( $r );
    $i = 0;
    $option = "";
    while ($i < $l ) {
        // code...
        $option .= '<option value="' . $r[$i]->id . '">' . $r[$i]->emotion . '</option>';
        $i += 1;
    }
    print_r( "var list_emotions = '" . $option . "';\n" );
   ?>
// Df;
var Df = new DataForm( { 
    dVar: "Df", 
    id: "#Df", 
    table: "ue_unterrichtseinheit", 
//    fields: "id,val_varchar,val_dec,val_int,val_select,val_select_multi,val_img,val_checkbox,val_stars",
    fields: "id,datum,gruppe_id,Beschreibung",
    addPraefix: "df1_",
    formType: "html", 
    boundForm: ["Df_3", "Df_2"] ,
    boundFields: [{"from": "id", "to": "ue_unterrichtseinheit_id"}, {"from": "id", "to": "ue_unterrichtseinheit_id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
    searcharray: [
            {
                field: "datum",
                type: "input_date",
                value: "",
            },
    ],
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
            field: "datum",
            label: "Datum",
            type: "input_date",
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
            Comment: undefined,
        },

    ],
//    ownArray: [{id:"2",type:"text"}],
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    //orderBy: " datum desc",
    //filter: "id = '1'",
    orderArray: [],
    searchArray: [
        ],
} );

var Df_2 = new DataForm( { 
    dVar: "Df_2", 
    id: "#Df_2", 
    table: "mtr_rueckkopplung_lehrkraft_lesson",    
    fields: "id,ue_unterrichtseinheit_id,erfasst_am,mitarbeit,absprachen,selbststaendigkeit,konzentration,fleiss,lernfortschritt,beherrscht_thema,transferdenken,basiswissen,vorbereitet,themenauswahl,materialien,individualisierung,aufforderung,zielgruppen,note,emotions,bemerkungen",
    addPraefix: "df2_",
    formType: "list",
    validOnSave: false,
    formWidth: 800,
    formHeight: 300,
    autoOpen: false,
    classButtonSize: "cButtonMiddle",
    fieldDefinitions: [
        {
            type: "recordPointer",
            value: "&nbsp;",
            field: "recordPointer",
            baseClass: "cButtonMiddle",
            onClick: function(){console.log(this)}
        },
        {
            field: "id",
            label: "Id",
            type: "input_text",

        },
        {
            field: "ue_unterrichtseinheit_id",
            label: "ue_unterrichtseinheit_id",
            type: "input_text",

        },

        {
            field: "erfasst_am",
            label: "Datum",
            type: "input_datetime-local",
            default: new Date().toJSON(),
        },
        {
            field: "mitarbeit",
            label: "Mit.",
            type: "input_number",
            default: 3,
            Comment: "Mitarbeit",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "absprachen",
            label: "Abs.",
            type: "input_number",
            default: 3,
            Comment: "Absprachen",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "selbststaendigkeit",
            label: "Sst.",
            type: "input_number",
            default: 3,
            Comment: "Selbstständigkeit",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "konzentration",
            label: "Kon.",
            type: "input_number",
            default: 3,
            Comment: "Konzentration",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "fleiss",
            label: "Fle.",
            type: "input_number",
            default: 3,
            Comment: "Fleiss",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "lernfortschritt",
            label: "Ler.",
            type: "input_number",
            default: 3,
            Comment: "Lernfortschritt",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "beherrscht_thema",
            label: "Beh.",
            type: "input_number",
            default: 3,
            Comment: "beherrscht Thema",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "transferdenken",
            label: "Tra.",
            type: "input_number",
            default: 3,
            Comment: "Transferdenken",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "vorbereitet",
            label: "Vor.",
            type: "input_number",
            default: 3,
            Comment: "vorbereitet",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "themenauswahl",
            label: "The.",
            type: "input_number",
            default: 3,
            Comment: "Themenauswahl",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "materialien",
            label: "Mat.",
            type: "input_number",
            default: 3,
            Comment: "Materialien",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "individualisierung",
            label: "Ind.",
            type: "input_number",
            default: 3,
            Comment: "Individualisierung",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "aufforderung",
            label: "Auf.",
            type: "input_number",
            default: 3,
            Comment: "Aufforderung",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "zielgruppen",
            label: "Auf.",
            type: "input_number",
            default: 3,
            Comment: "Zielgruppen",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "basiswissen",
            label: "Bas.",
            type: "input_number",
            default: 3,
            Comment: "Basiswissen",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "note",
            label: "Auf.",
            type: "input_number",
            default: 3,
            Comment: "Note",
            minValue: 1,
            maxValue: 6,
            addAtr: "step='0.1'"
        },
        {
            field: "emotions",
            label: "Gefühl",
            type: "select",
            addAtr: "multiple data-clickable",
            options: list_emotions,
            Comment: "Gefühle",
        },
        {
            field: "beschreibung",
            label: "beschreibung",
            type: "input_text",
        },
        {
            field: "getAVG",
            label: "AVG",
            type: "button",
            onClick: function(){},
        },
    ],
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    filter: undefined,
    afterBuild: function(){setTooltipsBewUe();getAVG()},
    afterSuccessSave: function(){},
} );
var Df_3 = new DataForm( { 
    dVar: "Df_3", 
    id: "#Df_3", 
    table: "ue_unterrichtseinheit_zw_thema",
    fields: "id,ue_unterrichtseinheit_id,schulform_id,fach_id,zieltyp_id,lernmethode_id,std_lernthema_id,thema,dauer,teilnehmer_id,beschreibung",
    addPraefix: "df3_",
    formType: "html",
    boundForm: ["Df_4"] ,
    boundFields: [{"from": "id", "to": "ue_unterrichtseinheit_zw_thema_id"},{"from": "teilnehmer_id", "to": "teilnehmer_id"}],
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
            field: "ue_unterrichtseinheit_id",
            label: "ue_unterrichtseinheit_id",
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
            field: "fach_id",
            label: "fach_id",
            type: "select",
            options: "<option value='<-1'>alle</option>" + list_fach,
             /*onChange:function(){changeSchulform(this.id)},*/

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
            addAtr: "multiple data-clickable",
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
    afterBuild: function(){},
    afterSuccessSave: function(){setUeTeinehmer()},
} );
var Df_4 = new DataForm( { 
    dVar: "Df_4", 
    id: "#Df_4", 
    table: "ue_zuweisung_teilnehmer",
    fields: "id,ue_unterrichtseinheit_zw_thema_id,teilnehmer_id",
    addPraefix: "df4_",
    boundForm: ["Df_8", "Df_9"] ,
    boundFields: [{"from": "id", "to": "ue_zuweisung_teilnehmer_id"},{"from": "id", "to": "ue_zuweisung_teilnehmer_id"}],
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
            field: "ue_unterrichtseinheit_zw_thema_id",
            label: "ue_unterrichtseinheit_zw_thema_id",
            type: "input_text",

        },

        {
            field: "teilnehmer_id",
            label: "teilnehmer_id",
            type: "select",
            options: list_teilnehmer,

        },

    ],
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    filter: undefined,
    afterBuild: function(){},
    afterSuccessSave: function(){},
} );
var Df_8 = new DataForm( { 
    dVar: "Df_8", 
    id: "#Df_8", 
    table: "mtr_leistung",
    fields: "id,ue_zuweisung_teilnehmer_id,teilnehmer_id,lernfortschritt,beherrscht_thema,transferdenken,basiswissen,vorbereitet,note,verhaltensbeurteilung_code,reflexionshinweis",
    addPraefix: "df8_",
    formType: "html",
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
    fieldDefinitions: [
        {
            type: "recordPointer",
            value: "&nbsp;",
            field: "recordPointer",
            baseClass: "cButtonMiddle",
            //onClick: function(){setTeilnehmer( this )},
        },
        {
            field: "id",
            label: "Id",
            type: "input_text",

        },
        {
            field: "ue_zuweisung_teilnehmer_id",
            label: "ue_zuweisung_teilnehmer_id",
            type: "input_text",
            
        },
        {
            field: "teilnehmer_id",
            label: "Teilnehmer",
            type: "select",
            options: list_teilnehmer,

        },

        {
            field: "lernfortschritt",
            label: "Ler.",

            type: "input_number",
            default: 4,
            Comment: "Lernfortschritt",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "beherrscht_thema",
            label: "Beh.",

            type: "input_number",
            default: 4,
            Comment: "beherrscht Thema",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "transferdenken",
            label: "Tra.",

            type: "input_number",
            default: 4,
            Comment: "Transferdenken",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "basiswissen",
            label: "Bas.",

            type: "input_number",
            default: 4,
            Comment: "Basiswissen",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "vorbereitet",
            label: "vor.",

            type: "input_number",
            default: 4,
            Comment: "vorbereitet",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "note",
            label: "Not.",

            type: "input_number",
            default: 4,
            Comment: "Note",
            minValue: 1,
            maxValue: 6,
            step: "0.1",
        },
        {
            field: "verhaltensbeurteilung_code",
            label: "Verhalten",

            type: "input_text",
            Comment: "verhaltensbeurteilung_code",
        },
        {
            field: "reflexionshinweis",
            label: "Reflexion",

            type: "input_text",
            Comment: "Reflexionshinweis",
        },

    ],
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    filter: undefined,
    afterBuild: function(){setMtrLeistung(this)},
    afterSuccessSave: function(){},
} );
var Df_9 = new DataForm( { 
    dVar: "Df_9", 
    id: "#Df_9", 
    table: "mtr_didaktik",
/*
        1    id Primärschlüssel    int(11)            Nein    kein(e)        AUTO_INCREMENT    Bearbeiten Bearbeiten    Löschen Löschen    
    2    ue_zuweisung_teilnehmer_id Index    int(11)            Nein    kein(e)            Bearbeiten Bearbeiten    Löschen Löschen    
    3    themenauswahl Index    int(1)            Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    4    methodenvielfalt Index    int(1)            Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    5    individualisierung Index    int(1)            Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    6    aufforderung Index    int(1)            Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    7    materialien Index    int(1)            Ja    NULL            Bearbeiten Bearbeiten    Löschen Löschen    
    8    zielgruppen 
*/
    fields: "id,ue_zuweisung_teilnehmer_id,themenauswahl",
    addPraefix: "df9_",
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
            label: "",
            type: "input_text",

        },
        {
            field: "ue_zuweisung_teilnehmer_id",
            label: "",
            type: "input_text",

        },
        {
            field: "themenauswahl",
            label: "Themen",
            type: "input_text",
        },
   
    ],
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    filter: "id = '0'",
} );
var Df_5 = new DataForm( { 
    dVar: "Df_5", 
    id: "#std_teilnehmer", 
    table: "std_teilnehmer", 
//    fields: "id,val_varchar,val_dec,val_int,val_select,val_select_multi,val_img,val_checkbox,val_stars",
    fields: "id,Vorname,Nachname,geschlecht,geburtstag,Klassenstufe,KlassentypID",
    addPraefix: "df5_",
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
    fields: "id,teilnehmer_id,datum,offenheit_erfahrungen,gewissenhaftigkeit,Extraversion,vertraeglichkeit,zielorientierung,lernfaehigkeit,anpassungsfaehigkeit,soziale_interaktion,metakognition,stressbewaeltigung,bedeutungsbildung,note",
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
            label: "",
            type: "input_text",

        },
        {
            field: "teilnehmer_id",
            label: "",
            type: "input_text",

        },
        {
            field: "datum",
            label: "Datum",
            type: "input_date",
            default: new Date().toJSON().slice(0, 10),

        },
        {
            field: "offenheit_erfahrungen",
            label: "Off.",
            type: "input_number",
            Comment: "Offenheit für Erfahrungen&#10;Hohe Ausprägung: Sucht aktiv nach neuen 'Feldzuständen' (Lerninhalten, Situationen), experimentiert mit verschiedenen 'Akteur-Funktionen' (Lernstrategien, Verhaltensweisen), ist offen für die Transformation symbolischer Meta-Strukturen. Niedrige Ausprägung: Bevorzugt bekannte 'Feldzustände', vermeidet neue Verhaltensmuster, hält an etablierten symbolischen Ordnungen fest.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "gewissenhaftigkeit",
            label: "Gew.",
            type: "input_number",
            Comment: "Gewissenhaftigkeit&#10;Hohe Ausprägung: Richtet seine 'Akteur-Funktion' auf die systematische Verfolgung definierter Ziele aus, zeigt Ausdauer bei der Bearbeitung von Aufgaben im 'Feld', reguliert seine 'Meta-Funktion' zur Selbstüberwachung und -korrektur im Lernprozess. Niedrige Ausprägung: Schwierigkeiten bei der Zielverfolgung, geringe Ausdauer, impulsive oder wenig geplante 'Akteur-Funktionen'.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "Extraversion",
            label: "Ext.",
            type: "input_number",
            Comment: "Extraversion&#10;Hohe Ausprägung: Zeigt eine starke Tendenz zur Bildung gekoppelter 'Akteur-Funktionen' in sozialen Feldern, sucht aktiv soziale 'Feldzustände' auf, zeigt eine hohe 'Handlungsdichte' im sozialen Kontext. Niedrige Ausprägung: Geringere Tendenz zu gekoppelten 'Akteur-Funktionen', vermeidet soziale 'Feldzustände', zeigt weniger 'Handlungen' im sozialen Kontext.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "vertraeglichkeit",
            label: "Ver.",
            type: "input_number",
            Comment: "Verträglichkeit&#10;Hohe Ausprägung: Passt seine 'Akteur-Funktion' an die der Ko-Akteure in sozialen Feldern an, zeigt Kooperationsbereitschaft, ist empfänglich für die semantischen Attraktoren gemeinsamer sozialer Narrative. Niedrige Ausprägung: Zeigt weniger Anpassung, ist weniger kooperativ, neigt zu Konflikten in gekoppelten 'Akteur-Funktionen'.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "zielorientierung",
            label: "Zie.",
            type: "input_number",
            Comment: "Zielorientierung&#10;Hohe Ausprägung: Setzt sich aktiv Lernziele, verfolgt Aufgaben beharrlich, zeigt Eigeninitiative. Niedrige Ausprägung: Schwierigkeiten, Ziele zu formulieren oder zu verfolgen, geringe Eigenmotivation.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "lernfaehigkeit",
            label: "Ler.",
            type: "input_number",
            Comment: "Lernfaehigkeit&#10;Hohe Ausprägung: Passt Lernstrategien an, nutzt Feedback effektiv, lernt aus Fehlern, geht flexibel mit neuen Lerninhalten um. Niedrige Ausprägung: Schwierigkeiten bei der Anpassung, resistent gegen Feedback, wiederholt Fehler. Lernprozesse verändern die Struktur der Akteur-Funktionen. Ausprägungen zeigen sich in der Geschwindigkeit und Effizienz dieser funktionalen Anpassungen.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "anpassungsfaehigkeit",
            label: "Anp.",
            type: "input_number",
            Comment: "Anpassungsfaehigkeit&#10;Hohe Ausprägung: Passt Lernstrategien an, nutzt Feedback effektiv, lernt aus Fehlern, geht flexibel mit neuen Lerninhalten um. Niedrige Ausprägung: Schwierigkeiten bei der Anpassung, resistent gegen Feedback, wiederholt Fehler.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "soziale_interaktion",
            label: "Soz.",
            type: "input_number",
            Comment: "Soziale Interaktion&#10;Hohe Ausprägung: Kooperativ, kommuniziert offen, integriert sich gut in Gruppen, zeigt Empathie. Niedrige Ausprägung: Schwierigkeiten in der Zusammenarbeit, vermeidet Interaktion, soziale Konflikte.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "metakognition",
            label: "Met.",
            type: "input_number",
            Comment: "Metakognition&#10;Hohe Ausprägung: Denkt über eigenes Lernen nach, plant Lernprozesse, überwacht Verständnis, bewertet eigene Leistung realistisch. Niedrige Ausprägung: Wenig Bewusstsein für eigene Lernprozesse, Schwierigkeiten bei der Selbstbewertung.",
            minValue: 1,
            maxValue: 6,

        },

        {
            field: "stressbewaeltigung",
            label: "Bed.",
            type: "input_number",
            Comment: "Stressbewältigung&#10;Hohe Ausprägung: Reagiert ängstlich auf Prüfungen oder neue Situationen, ist besorgt über Leistung, zeigt emotionale Labilität. Niedrige Ausprägung: Bleibt ruhig unter Druck, geht gelassen mit Unsicherheit um. Könnte sich in der erhöhten Reaktivität der Akteur-Funktion auf als bedrohlich interpretierte Feldzustände (z.B. Prüfungsdruck) oder in negativen Mustern der Meta-Funktion (z.B. negative Selbstbewertung) äußern.",
            minValue: 1,
            maxValue: 6,

        },
        {
            field: "bedeutungsbildung",
            label: "Bed.",
            type: "input_number",
            Comment: "Bedeutungsbildung&#10;Hohe Ausprägung: Konstruiert kohärente Bedeutungen aus Lerninhalten, vernetzt Wissen, entwickelt eigene Interpretationen, findet Sinn im Gelernten. Niedrige Ausprägung: Schwierigkeiten bei der Sinnstiftung, isolierte Wissensfragmente, wenig eigene Interpretationen. Integrale Funktionalität (Kohärenzbildung, Kontextualisierung, Narrativierung, Wertschöpfung) synthetisiert lokale Beobachtungen zu globalen Bedeutungen. Ausprägungen zeigen sich in der Qualität und Struktur der konstruierten semantischen Felder und Narrative.",
            minValue: 1,
            maxValue: 6,

        },   
        {
            field: "note",
            label: "Not.",
            type: "input_number",
            Comment: "Durchschnittsnote",
            minValue: 1,
            maxValue: 6,

        },   
    ],
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    filter: "id = '0'",
} );
var myDia;
        myDia = new Dialog({
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
    Df_4.init();
    Df_5.init();
    Df_8.init();
    Df_9.init();
    init();
})();
</script>
</body>
</html>
