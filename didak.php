<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Dataform-Test ICAS</title>

    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="stylesheet prefetch" href="library/css/DataForm20.css">
    <link rel="stylesheet prefetch" href="library/css/didak.css">

</head>

<body>
    <input type="button" name="" id="showDF" data-dvar="Df">
<div id="target">
    <div id="targetBasis">&nbsp;</div>

</div>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>
<script src="library/javascript/main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/DialogDR.js"></script>
<script src="library/javascript/Field20.js"></script>
<script src="library/javascript/RecordSet20.js"></script>
<script src="library/javascript/Dataform20.js"></script>
<script>
    <?php
    $settings = parse_ini_file('ini/settings.ini', TRUE);
    $dns = $settings['database']['type'] . 
                ':host=' . $settings['database']['host'] . 
                ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') . 
                ';dbname=icas';
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
    $q = "SELECT id as value, Nachname as text from std_teilnehmer order by Nachname asc";
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
   ?>
let fields = [
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
/*        {
            field: "dummy",
            label: "dummy",
            value: new Date().addHours(1).toISOString().replace("T", " ").replace("Z", "").split(" ")[0], // current date without hours
            baseClass: "cDummy",
            type: "input_date",
        },
        {
            field: "val_dec",
            label: "Dec",
            type: "input_text",
            addClasses: "cDec",
        },
*/
        {
            field: "teilnehmer_typ",
            label: "Typ",
            type: "input_text",
            addClasses: "cVal_varchar",
            valid: ["not empty", "minlength 3"],
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
/*
        {
            field: "val_int",
            label: "val_int",
            type: "input_number",
            addClasses: "cVal_val_int",
            minValue: 1,
        },
        {
            field: "val_select",
            label: "val_select",
            type: "select",
            addClasses: "cVal_val_select",
            options: optRole,
        },
        {
            field: "val_select_multi",
            label: "val_select_multi",
            type: "select",
            addClasses: "cVal_val_select_multi",
            addAttr: "multiple",
            options: optRole,
        },
        {
            field: "val_img",
            label: "val_img",
            type: "img",
            addClasses: "cVal_img",
            withDiv: true,
        },
        {
            field: "val_checkbox",
            label: "val_checkbox",
            type: "checkbox",
            addClasses: "cVal_checkbox",
        },
        {
            field: "val_stars",
            label: "val_stars",
            type: "stars",
            addClasses: "cVal_stars",
            onClick: function( event ) {
                console.log( nj().els(this).children[1] );
              var rect = nj().els(this).getBoundingClientRect(); 
              var x = event.clientX - rect.left; 
              var y = event.clientY - rect.top; 
               
              console.log(parseInt(x/20) + 1);
              nj().els(this).children[1].setAttribute("width", (parseInt(x/20) + 1)*20 ) 
            }
        },
        {
            field: "button_addKey",
            type: "button",
            baseClass: "cAddButton",
            addClasses: "cButtonAddKey",
            value: "&nbsp;",
            maxLength: "0",
            onClick: function () {
                // content
                console.log( nj( this ).Dia("dvar", 5 ) );
            }
        },
        {
            field: "button_setValue",
            type: "input_but",
            baseClass: "cAddButton cButtonMiddle",
            addClasses: "cButtonSetValuey",
            value: "&nbsp;",
            maxLength: "0",
            onClick: function () {
                // content
                console.log( nj( this ).Dia().tmpEl );
            }
        },
*/
    ];
// Df;
var Df = new DataForm( { 
    dVar: "Df", 
    id: "#Df", 
    table: "std_teilnehmer", 
//    fields: "id,val_varchar,val_dec,val_int,val_select,val_select_multi,val_img,val_checkbox,val_stars",
    fields: "id,teilnehmer_typ,Vorname,Nachname",
    addPraefix: "df1_",
    formType: "html", 
    boundForm: ["Df_2"] ,
    boundFields: [{"from": "id", "to": "id"}],
    validOnSave: false, 
    classButtonSize: "cButtonMiddle",
    fieldDefinitions: fields,
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    //filter: "id = '1'",
    orderArray: ["val_varchar", "val_int"],
    searchArray: [
        ]
} );
var Df_2 = new DataForm( { 
    dVar: "Df_2", 
    id: "#Df_2", 
    table: "mtr_rueckkopplung_teilnehmer",
    fields: "id,ue_zuweisung_schueler_id,val_mitarbeit,val_absprachen,val_selbststaendigkeit,val_konzentration,val_fleiss,val_lernfortschritt,val_beherrscht_thema,val_transferdenken,val_basiswissen,val_vorbereitet,val_themenauswahl,val_materialien,val_methodenvielfalt,val_individualisierung,val_aufforderung,erfasst_am",
    addPraefix: "df2_",
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
            field: "ue_zuweisung_schueler_id",
            label: "ue_zuweisung_schueler_id",
            type: "select",
            addClasses: "cVal_val_select",
            options: list_teilnehmer,
        },
        {
            field: "val_mitarbeit",
            label: "val_mitarbeit",
            type: "input_number"

        },
        {
            field: "val_absprachen",
            label: "val_absprachen",
            type: "input_number"

        },
        {
            field: "val_selbststaendigkeit",
            label: "val_selbststaendigkeit",
            type: "input_number"

        },
        {
            field: "val_konzentration",
            label: "val_konzentration",
            type: "input_number",
            Comment: "test&#10;test"
        },
        ],
    countPerPage: 0,
    currentPage: 0,
    hasPagination: false,
    countRecords: undefined,
    filter: undefined,
/*
    orderArray: ["val_varchar", "val_int"],
    searchArray: [
            {
                field: "val_varchar",
                type: "input_text",
                value: "",
                sel: "value",
            },
            {
                field: "val_select",
                type: "select",
                options: "<option value='>-1'>alle</option>" + optRole,
                value: ">-1",
                sel: "value",
            },
            {
                field: "val_select_multi",
                type: "select",
                options: "<option value='>-1'>alle</option>" + optRole,
                addAttr: "multiple",
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
        ]
    /*additionalFields: additionalFields, */
} );
(function() {
    Df.init();
    Df_2.init();
})();
</script>
</body>
</html>
