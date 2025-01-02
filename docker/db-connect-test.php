<?php

declare(strict_types=1);

/**
 * Script for testing that the database service is running
 *
 * CLI usage:
 * php -f db-connect-test.php -- -d database -u user -p password -H hostname
 */
$options = getopt('d:u:p:H:');

$dbname = $options['d'];
$dbuser = $options['u'];
$dbpass = $options['p'];
$dbhost = $options['H'];
$scale = [1, 2, 3];
$scale_count = count($scale);
$last_error = null;

fwrite(STDERR, "- Waiting for database service to accept connections...\n");

for ($retry = 0; $retry < 6; $retry++) {
    try {
        $dsn = "mysql:host={$dbhost};dbname={$dbname}";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $dbuser, $dbpass, $opt);

        exit(0);
    } catch (Exception $ex) {
        $time = 1 + ($scale[$retry % $scale_count] ?? 1);

        $last_error = $ex->getMessage();

        fwrite(STDERR, "- Waiting for database service to accept connections [{$time}]...\n");

        sleep($time);
    }
}

fwrite(STDERR, ">>> Database service not reachable.\n");
if ($last_error) {
    fwrite(STDERR, ">>> {$last_error}.\n");
}
exit(127);
