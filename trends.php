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
    $q = "SELECT id as value, concat(Vorname, ', ', Nachname) as text from std_teilnehmer order by Nachname asc";
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
    print_r( "var list_teilnehmer = '<option value='>0'>alle</option>" . $option . "';\n" );
?>
</script>
<h1>Trends</h1>
<div>
<label>Auswahl TN</label>
<select id="selTN"></select>
<label>Auswahl Startdatum</label>
<input id="startDate" type="date">
<label>Auswahl Enddatum</label>
<input id="endDate" type="date">
<input type="button" id="getAVG">
</div>
<div>
    <div>
        <label>lernfortschritt</label>
        <input id="lernfortschritt" type="number" step="0.1">
        <label>zielwert</label>
        <input id="zielwert_lernfortschritt" type="number" step="0.1">
        <label>streuung</label>
        <input id="streuung_lernfortschritt" type="number" step="0.1">
    </div>
    <div>
        <label>beherrscht_thema</label>
        <input id="beherrscht_thema" type="number" step="0.1">
        <label>zielwert</label>
        <input id="zielwert_beherrscht_thema" type="number" step="0.1">
        <label>streuung</label>
        <input id="streuung_beherrscht_thema" type="number" step="0.1">
    </div>
    <div>
        <label>transferdenken</label>
        <input id="transferdenken" type="number" step="0.1">
        <label>zielwert</label>
        <input id="zielwert_transferdenken" type="number" step="0.1">
        <label>streuung</label>
        <input id="streuung_transferdenken" type="number" step="0.1">
    </div>
    <div>
        <label>basiswissen</label>
        <input id="basiswissen">
        <label>zielwert</label>
        <input id="zielwert_basiswissen" type="number" step="0.1">
        <label>streuung</label>
        <input id="streuung_basiswissen" type="number" step="0.1">
    </div>
    <div>
        <label>vorbereitet</label>
        <input id="vorbereitet">
        <label>zielwert</label>
        <input id="zielwert_vorbereitet" type="number" step="0.1">
        <label>streuung</label>
        <input id="streuung_vorbereitet" type="number" step="0.1">
    </div>
    <div>
        <label>note</label>
        <input id="note" type="number" step="0.1">
        <label>zielwert</label>
        <input id="zielwert_note" type="number" step="0.1">
        <label>streuung</label>
        <input id="streuung_note" type="number" step="0.1">
    </div>
<div>

<input type="button" id="setGroup">
</div>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>
<script src="library/javascript/const_main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/Dialog2.0.1.js"></script>
<script src="library/javascript/Field2.0.1.js"></script>
<script src="library/javascript/RecordSet2.0.1.js"></script>
<script src="library/javascript/DataForm2.0.1.js"></script>
<script src="library/javascript/OpenTip_native.js"></script>
<script src="library/javascript/init_trends.js"></script>
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
</script>
</body>
</html>
