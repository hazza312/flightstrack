<?php

require_once("init.php");

function get_codes($dbconn)
{
    return $dbconn->query("SELECT DISTINCT code FROM userinterests")->fetchAll(PDO::FETCH_COLUMN);
}

function save($db, $requestBody, $response)
{
    $query = <<<SQL
        INSERT INTO apicalls (url, status, request, response)
        VALUES (?, ?, ?, ?)
        SQL;

    $st = $db->prepare($query);
    $st->execute([
        $response->url,
        $response->status_code,
        $requestBody,
        $response->body
    ]);

}

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

$codes = get_codes($db);
$curr = new DateTimeImmutable();

for ($i = 0; $i < 3; $i++) {
    $d1 = $curr->add(new DateInterval('P' . ($i * 4) . 'M'))->format('Y-m-d') . 'T00:00:00';
    $d2 = $curr->add(new DateInterval('P' . (($i + 1) * 4) . 'M'))->format('Y-m-d') . 'T00:00:00';

    $requestBody = build_request($codes, $d1, $d2);
    $response = send_request($requestBody);
    save($db, $requestBody, $response);

    // rate limit
    sleep(1);
}


?>