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
<h1>Trends</h1>
<div>

<input type="button" id="setGroup">
</div>
<script src="library/javascript/no_jquery.js"></script>
<script src="library/javascript/easyit_helper_neu.js"></script>
<script src="library/javascript/const_main.js"></script>
<script src="library/javascript/DropResize.js"></script>
<script src="library/javascript/DialogDR.js"></script>
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
