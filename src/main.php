<?php

require_once "queries.php";
require_once "util.php";

require_once(__DIR__ . '../../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__. '/../');
$dotenv->load();

$db = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);


function build_request($codes, $start, $end)
{
    return json_encode([
        'bookingFlow' => 'LEISURE',
        'origin' => [
            'type' => "AIRPORT",
            'code' => "AMS"
        ],
        'destinationCities' => $codes,
        'fromDate' => $start,
        'untilDate' => $end,
        'type' => "DAY"
    ]);
}

function send_request($requestBody)
{
    return WpOrg\Requests\Requests::post(
        'https://api.airfranceklm.com/opendata/offers/v3/lowest-fares-by-destination',
        [
            'Content-Type' => 'application/json',
            'api-key' => $_ENV['KLM_API_KEY'],
            'afkl-travel-host' => 'KL',
            'afkl-travel-country' => "NL"
        ],
        $requestBody
    );
}

function fetch_and_store_latest_offers($db) {
    $codes = $db->query("SELECT code FROM trackeddestinations")->fetchAll(PDO::FETCH_COLUMN);
    $curr = new DateTimeImmutable();

    for ($i = 0; $i < 3; $i++) {
        $d1 = $curr->add(new DateInterval('P' . ($i * 4) . 'M'))->format('Y-m-d') . 'T00:00:00';
        $d2 = $curr->add(new DateInterval('P' . (($i + 1) * 4) . 'M'))->format('Y-m-d') . 'T00:00:00';

        $requestBody = build_request($codes, $d1, $d2);
        $response = send_request($requestBody);
        saveApiCallResult($db, $requestBody, $response);

        // rate limit
        sleep(1);
    }
}

function generate_report($db, $output) {
    $loader = new \Twig\Loader\FilesystemLoader('.');
    $twig = new \Twig\Environment($loader);
    $twig->addFilter(new Twig\TwigFilter('flagemoji', 'flagEmoji'));
    $twig->addFilter(new Twig\TwigFilter('shortAirportName', 'shortAirportName'));

    $content = $twig->render('report.html.twig', [
        'rows' => get_current_offers($db),
        'trends' => get_offer_trends($db)
    ]);

    file_put_contents($output, $content);
}


function main($db) {
    $ret = getopt("", ["output:", "nofetch"]);
    $fetch = !isset($ret['nofetch']);
    $output = $ret['output'] ?? "public/report.html";

    if ($fetch) {
        fetch_and_store_latest_offers($db);
    }

    generate_report($db, $output);
}

main($db);
