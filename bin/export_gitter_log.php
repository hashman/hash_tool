<?php

// PHP 7 only
if ((int) explode('.', phpversion())[0] < 7) {
    echo "Must use PHP 7 version\n";
    exit;
}

require __DIR__ . '/../init.php';

use Gitter\Client;
use Carbon\Carbon;

$localConfigFile = sprintf('%s/.hash_tool_env', $_SERVER['HOME']);
$localConfig = is_file($localConfigFile) ? json_decode(file_get_contents($localConfigFile), true) : [];
if (!isset($localConfig['gitter_token'])) {
    $token = \cli\prompt("Please type gitter private token when first time use ( %Yhttps://developer.gitter.im/apps%n )");
    if (!$token) {
        \cli\err("Must input the Gitter token. Bye...");
        die(-1);
    }
    $localConfig['gitter_token'] = $token;
    file_put_contents($localConfigFile, json_encode($localConfig, JSON_PRETTY_PRINT));
    chmod($localConfigFile, 0600);
}

$client = new Client($localConfig['gitter_token']);
$client->connect();

$menu = [
    'list'   => 'List all Gitter room and id',
    'export' => 'Export Gitter Room log with Room ID',
    'quit'   => 'Quit'
];

while (true) {
    $choice = \cli\menu($menu, null, 'Select the function');
    \cli\line();
    try {
        switch ($choice) {
            case 'quit':
                die(-1);
            case 'list':
                listRoom();
                break;
            case 'export':
                export();
                break;
        }
    } catch (\Exception $e) {
        \cli\err('%R' . $e->getMessage() . '%n');
        die(-1);
    }
}

function listRoom()
{
    global $client;
    foreach ($client->rooms as $room) {
        \cli\line(sprintf('Room ID: %s, Room Name: %s', $room['id'], $room['name']));
    }
    \cli\line();
}

function export()
{
    global $client;
    $start_year = \cli\prompt("Please input log fetch start year (EX: 2016)");
    $end_year = \cli\prompt("Please input log fetch end year (EX: 2017)");
    $room_id = \cli\prompt("Please input Room ID");
    $start_year_obj = Carbon::create($start_year, 1, 1, 0, 0, 0);
    $end_year_obj = Carbon::create($end_year, 12, 31, 23, 59, 59);

    $file_name = STORAGE_FOLDER . "/gitter_log_{$start_year}_{$end_year}.log";

    $message_ary = [];
    foreach ($client->messages->all($room_id) as $message) {
        $dt = new \DateTime($message['sent']);
        $carbon = Carbon::instance($dt);
        if (!$carbon->between($start_year_obj, $end_year_obj)) {
            continue;
        }

        $username = isset($message['fromUser']['username']) ? $message['fromUser']['username'] : "Unknown";
        $message_ary[$carbon->timestamp] = sprintf("%s < %s > %s\n", $carbon->toDateTimeString(), $username,
            $message['text']);
    }

    ksort($message_ary);
    foreach ($message_ary as $message) {
        file_put_contents($file_name, $message, FILE_APPEND);
    }
    \cli\out("%GExport done, export to {$file_name}...%n");
    exit;
}
