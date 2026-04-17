<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db_config_value(string $key, string $default = ""): string
{
    static $localConfig = null;

    if ($localConfig === null) {
        $localConfigPath = __DIR__ . "/db.local.php";
        if (is_file($localConfigPath)) {
            $loaded = require $localConfigPath;
            $localConfig = is_array($loaded) ? $loaded : [];
        } else {
            $localConfig = [];
        }
    }

    if (array_key_exists($key, $localConfig) && $localConfig[$key] !== "") {
        return (string)$localConfig[$key];
    }

    $envValue = getenv($key);
    if ($envValue !== false && $envValue !== "") {
        return (string)$envValue;
    }

    return $default;
}

$host = db_config_value("DB_HOST", "localhost");
$user = db_config_value("DB_USER", "root");
$pass = db_config_value("DB_PASS", "");
$db = db_config_value("DB_NAME", "studyspot");
$port = (int)db_config_value("DB_PORT", "3306");

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $exception) {
    http_response_code(500);
    exit("Die Datenbankverbindung konnte nicht aufgebaut werden.");
}
