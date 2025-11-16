<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Schülerfeedback easyDidak</title>

    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="stylesheet prefetch" href="library/css/Dataform20.css">
    <link rel="stylesheet prefetch" href="library/css/didak.css">
    <link rel="stylesheet prefetch" href="library/css/opentip.css">

</head>

<body>
<h1>Rückmeldung Schüler</h1>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>
<script src="library/javascript/const_main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/Dialog2.0.1.js"></script>
<script src="library/javascript/Field2.0.1.js"></script>
<script src="library/javascript/MessageDR.js"></script>
<script src="library/javascript/RecordSet2.0.1.js"></script>
<script src="library/javascript/DataForm2.0.1.js"></script>
<script src="library/javascript/OpenTip_native.js"></script>
<script src="library/javascript/init_didak.js"></script>
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
    $q = "SELECT id as value, Nachname as text from std_teilnehmer where show_tn=1 order by Nachname asc";
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
    print_r( "var list_teilnehmer = '" . $option . "';\n" );
    $q = "SELECT id as value, emotion as text from _mtr_emotionen where show_emotion=1 order by emotion";
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
    print_r( "var list_emotionen = '" . $option . "';\n" );
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
        $option .= '<option value="' . $r[$i]->id . '">' . $r[$i]->Vorname . '</option>';
        $i += 1;
    }
    print_r( "var list_names = '" . $option . "';\n" );
   ?>
let fields = [
        {
            type: "recordPointer",
            value: "&nbsp;",
            field: "recordPointer",
            baseClass: "cButtonMiddle",
            onClick: setTooltips()
        },
        {
            field: "id",
            label: "Id",
            addAtr: " style='display:none'",
            type: "input_text",

        },
        {
            field: "teilnehmer_typ",
            label: "Typ",
            type: "select",
            addClasses: "",
            options: "<option value=0>Lehrkraft</option><option value=1>Teilnehmer</option>"
        },
        {
            field: "Vorname",
            label: "Vorname",
            type: "input_text",
            addClasses: "cVal_varchar",
            valid: ["not empty", "minlength 3"],
        },
        {
            field: "Nachname",
            label: "Nachname",
            type: "input_text",
            addClasses: "cVal_varchar",
            valid: ["not empty", "minlength 3"],
        },
        {
            field: "geschlecht",
            label: "Geschl.",
            type: "select",
            options: "<option value='0'>ohne</option><option value='1'>männlich</option><option value='2'>weiblich</option><option value='3'>divers</option>",
        },
        {
            field: "Klassenstufe",
            label: "Kl.",
            type: "input_number",
            valid: ["not empty"],
        },
        {
            field: "KlassentypID",
            label: "Schulform",
            type: "select",
            options: list_schulform,
        },

    ];
// Df;
var Df = new DataForm( { 
    dVar: "Df", 
    id: "#Df", 
    table: "std_teilnehmer", 
//    fields: "id,val_varchar,val_dec,val_int,val_select,val_select_multi,val_img,val_checkbox,val_stars",
    fields: "id,teilnehmer_typ,Vorname,Nachname,geschlecht,Klassenstufe,KlassentypID",
    addPraefix: "df1_",
    formType: "html", 
    boundForm: ["Df_2"] ,
    boundFields: [{"from": "id", "to": "teilnehmer_id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
    fieldDefinitions: fields,
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    filter: "show_tn=1",
    //whereClausel: "show_tn=1",
    //orderArray: ["val_varchar", "val_int"],
    searchArray: [
 
            {
                field: "teilnehmer_typ",
                type: "select",
                options: "<option value='>-1'>alle</option><option value='0'>Lehrkräfte</option><option value='1'>Teilnehmer</option>",
                value: ">-1",
                sel: "value",
            },

            {
                field: "id",
                type: "select",
                options: "<option value='>-1'>alle</option>" + list_names,
                value: ">-1",
                sel: "value",
            },
    ]
} );
var Df_2 = new DataForm( { 
    dVar: "Df_2", 
    id: "#Df_2", 
    table: "mtr_rueckkopplung_teilnehmer",
    fields: "id,teilnehmer_id,mitarbeit,absprachen,selbststaendigkeit,konzentration,fleiss,lernfortschritt,beherrscht_thema,transferdenken,basiswissen,vorbereitet,themenauswahl,materialien,methodenvielfalt,individualisierung,aufforderung,zielgruppen,emotions,bemerkungen",
    addPraefix: "df2_",
    formType: "html",
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
            field: "teilnehmer_id",
            label: "tn_id",
            type: "select",
            addClasses: "cselect",
            options: list_teilnehmer,
        },
        {
            field: "mitarbeit",
            label: "MA  ",
            type: "input_number",
            //default: 3,
            Comment: "",
            minValue: 1,
            maxValue: 6,
            addClasses: "elBew"
        },
        {
            field: "absprachen",
            label: "AB  ",
            type: "input_number",
            default: "",
            Comment: "",

            minValue: 1,
            maxValue: 6,
         },
        {
            field: "selbststaendigkeit",
            label: "St  ",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
 
        },
        {
            field: "konzentration",
            label: "Ko  ",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "fleiss",
            label: "Fl",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "lernfortschritt",
            label: "LF",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "beherrscht_thema",
            label: "BT",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "transferdenken",
            label: "Tr",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "basiswissen",
            label: "BW",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "vorbereitet",
            label: "Vo",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "themenauswahl",
            label: "Th",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "materialien",
            label: "Ma",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "methodenvielfalt",
            label: "MV",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },            
        {
            field: "individualisierung",
            label: "In",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "aufforderung",
            label: "Af",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "zielgruppen",
            label: "ZG",
            type: "input_number",
//            default: 3,
            Comment: "",

            minValue: 1,
            maxValue: 6,
        },
        {
            field: "emotions",
            label: "Gefühle",
            type: "select",
            addClasses: "cselect_multi",
            addAtr: "multiple data-clickable", // clickable opens the select dialog
            options: list_emotionen,
            Comment: "",
        },
        {
            field: "bemerkungen",
            label: "Bemerkungen",
            type: "input_text",
            Comment: "",
        },
        ],
    countPerPage: 5,
    currentPage: 0,
    hasPagination: true,
    countRecords: undefined,
    afterBuild: function(){setTooltips()},
    afterNew: function(){setGroup()},
    filter: "id=0",
/*
    orderArray: ["val_varchar", "val_int"],
*/
    searchArray: [
            {
                field: "id",
                type: "select",
                options: "<option value='>0'>alle</option><option value=0>nur Neu</option>",
                value: ">-1",
                sel: "value",
            },
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
(function() {
    Df.init();
    Df_2.init();
//    Df_3.init();
    nj("#df1_search_id").on("change", function(){ let v = nj("#df1_search_id").v();    nj("#df2_teilnehmer_id_new").v( v)})
})();
</script>
<legend>
    <table>
       <tr>
          <td>
             MA
          </td>
          <td>
             Wie aktiv beteiligst du dich am Unterricht &#10;(Fragen stellen, Antworten geben, mitdenken)?
          </td>
          <td>
             AB
          </td>
          <td>
             Hältst du dich an Absprachen mit dem Tutor &#10;(z. B. Formfragen, vereinbarte Ziele, Termine)?
          </td>
          <td>
             St
          </td>
          <td>
             Wie gut kannst du Aufgaben alleine bearbeiten, &#10;ohne ständig Hilfe zu brauchen?
          </td>
       </tr>
       <tr>
          <td>
             Ko
          </td>
          <td>
             Wie aufmerksam und fokussiert arbeitest du während der Stunde?
          </td>
          <td>
             Fl
          </td>
          <td>
             Wie viel Mühe und Einsatz bringst du in die Bearbeitung der Aufgaben ein?
          </td>
          <td>
             LF
          </td>
          <td>
             Hast du das Gefühl, dass du dich in den behandelten Themen verbesserst?
          </td>
       </tr>
       <tr>
          <td>
             BT
          </td>
          <td>
             Kannst du das aktuelle Thema am Ende der Einheit sicher anwenden und erklären?
          </td>
          <td>
             Tr
          </td>
          <td>
             Schaffst du es, das Gelernte auch in neuen Aufgaben oder Situationen zu nutzen?
          </td>
          <td>
             BW
          </td>
          <td>
             Verfügst du über das nötige Grundwissen, um neue Inhalte zu verstehen und darauf aufzubauen?
          </td>
       </tr>
       <tr>
          <td>
             Vo
          </td>
          <td>
             Kommst du vorbereitet in die Stunde (Hausaufgaben, Materialien, Vorwissen)?
          </td>
          <td>
             Th
          </td>
          <td>
             Empfindest du die behandelten Themen als sinnvoll und für dich passend?
          </td>
          <td>
             Ma
          </td>
          <td>
             Helfen dir die eingesetzten Materialien (Arbeitsblätter, Darstellungen, Beispiele) beim Lernen?
          </td>
       </tr>
       <tr>
          <td>
             MV
          </td>
          <td>
             Empfindest du die eingesetzten Methoden (Erklärungen, Übungen, Visualisierungen) als abwechslungsreich und hilfreich?
          </td>
          <td>
             In
          </td>
          <td>
             Hast du das Gefühl, dass der Unterricht auf deine persönlichen Stärken und Schwächen eingeht?
          </td>
          <td>
             Af
          </td>
          <td>
             Wirst du vom Tutor ausreichend ermutigt und aufgefordert, dich aktiv einzubringen?
          </td>
       </tr>
    </table>
</legend>
</body>
</html>
