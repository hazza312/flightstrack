<?php

require_once("init.php");
require_once "queries.php";

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
        $d1 = $curr->add(new DateInterval('P' . ($i * 4) . 'M'))->format('Y-m-dT00:00:00');
        $d2 = $curr->add(new DateInterval('P' . (($i + 1) * 4) . 'M'))->format('Y-m-dT00:00:00');

        $requestBody = build_request($codes, $d1, $d2);
        $response = send_request($requestBody);
        saveApiCallResult($db, $requestBody, $response);

        // rate limit
        sleep(1);
    }
}

function generate_report($db) {

}


function main($db) {
    fetch_and_store_latest_offers($db);
    generate_report($db);
}

main($db);

?>