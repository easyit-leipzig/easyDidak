<?php
session_start();
error_reporting(E_ALL ^E_NOTICE);
date_default_timezone_set('Europe/Berlin');

// JSON POST Body einlesen
$json = file_get_contents("php://input");
if (!empty($json)) {
    $data = json_decode($json, true);
    foreach ($data as $key => $value) {
        $_POST[$key] = $value;
    }
}

// Standard-Rückgabeobjekt
$return = new \stdClass();
$return->command = $_POST["command"] ?? null;

// DB-Verbindung
$settings = parse_ini_file('../../ini/settings.ini', TRUE);
$dns = $settings['database']['type'] .
        ':host=' . $settings['database']['host'] .
        ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
        ';dbname=' . $settings['database']['schema'];

try {
    $db_pdo = new \PDO(
        $dns,
        $settings['database']['username'],
        $settings['database']['password'],
        array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
    );
    $db_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
} catch (\PDOException $e) {
    $return->command = "connect_error";
    $return->message = $e->getMessage();
    print_r(json_encode($return));
    die;
}

require_once("classes/DataFormNew.php");

// DataFormNew-Objekt erstellen
$df = new \DataFormNew(
    $db_pdo,
    $_POST["pageSource"],          // z.B. "book_status" oder "select * from book_status"
    $_POST["currentRecord"] ?? 0,  // ID des aktuellen Datensatzes
    $_POST["fieldPraefix"] ?? "",  
    $_POST["classPraefix"] ?? "",
    $_POST["fields"] ?? "*",
    $_POST["currentPage"] ?? 0,
    $_POST["countPerPage"] ?? 10,
    $_POST["isNew"] ?? "true"
);

switch ($_POST["command"]) {
    case "getFieldDefinitions":
        $return->dVar = $_POST["dVar"];
        $return->structure = $df->getTableDef();   // Holt Tabellenstruktur
        $return->primaryKey = $df->getPrimaryKey();
        break;

    case "getRecords":
        $return->dVar = $_POST["dVar"];
        $res = $df->getRecords();  // Müsste in DataFormNew vorhanden sein
        $return->records = $res->records ?? [];
        $return->countRecords = $res->countRecords ?? 0;
        break;

    case "saveRecordset":
        $return->dVar = $_POST["dVar"];
        $res = $df->saveRecordset($_POST["primaryKey"], $_POST["primaryKeyValue"], json_decode($_POST["fields"]));
        $return->newId = $res->newId;
        $return->success = $res->success;
        $return->message = $res->message;
        break;

    case "deleteRecordset":
        $return->dVar = $_POST["dVar"];
        $res = $df->deleteRecordset($_POST["primaryKey"], $_POST["primaryKeyValue"]);
        $return->success = $res->success;
        $return->message = $res->message;
        break;

    default:
        $return->success = false;
        $return->message = "Unbekanntes Kommando: " . $_POST["command"];
}

print(json_encode($return));
