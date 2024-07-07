<?php
require_once("../init.php");

function fetch($dbconn, $query, $params, $mode = PDO::FETCH_ASSOC)
{
    $st = $dbconn->prepare($query);
    $st->execute($params);
    return $st->fetchAll($mode);
}


function get_countries($db, $userid): array
{
    $query = <<<SQL
    SELECT 
     code,
     airports.name,
     country,
     lat,
     lon,
     ROUND(ST_DISTANCE_SPHERE(
        POINT(lon,lat),
        POINT(4.7433497, 52.3169189)
     ) / 1000) AS dist
    FROM 
    userinterests interests
    JOIN airports USING (code) 
    WHERE interests.id = ?
    SQL;

    $flat = fetch($db, $query, [$userid]);
    $ret = [];
    foreach ($flat as $airport) {
        $ret[$airport['code']] = $airport;
    }
    return $ret;
}

function get_data($db): array
{
    $query = <<<SQL
    WITH latest AS (
        SELECT *, RANK() OVER (PARTITION BY requesteddate, windowstart ORDER BY requested DESC) r
        FROM lowestfaredestination ORDER BY requested DESC  
    )

    SELECT 
        code,
        windowstart,
        departureDate,
        returndate,
        price,
        ((MAX(price) OVER (PARTITION BY code)) - price) / (MAX(price) OVER (PARTITION BY code)) AS decrease
    FROM latest
    JOIN userinterests USING (code)
    JOIN user ON (userinterests.id = user.id)
    WHERE user.id = ? AND r = 1
    ORDER BY code, windowstart
    SQL;

    $flat = fetch($db, $query, [$_SESSION['flightsid']]);
    $ret = [];
    foreach ($flat as $r) {
        if (!isset($ret[$r['code']])) {
            $ret[$r['code']] = [
                'windows' => []
            ];
        }
        $ret[$r['code']]['windows'][$r['windowstart']] = [
            "departureDate" => $r['departureDate'],
            "returnDate" => $r['returndate'],
            "price" => $r['price'],
            "decrease" => $r['decrease']
        ];
    }
    return $ret;
}
function get_trends($db)
{
    $query = <<<SQL
    WITH best_daily AS (
        SELECT 
        code,
        requesteddate,
        MIN(price) AS price,
        FIRST_VALUE(MIN(price)) OVER (PARTITION BY code ORDER BY requested DESC) AS latestPrice,
        FIRST_VALUE(requesteddate) OVER (PARTITION BY code ORDER BY requested DESC) AS latestDate
        
        FROM lowestfaredestination
        JOIN userinterests USING (code) 
        WHERE requesteddate BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) AND CURRENT_DATE
        AND userinterests.id = ?
        GROUP BY 1, 2
        ORDER BY 1, 2  
    ), grouped AS (
        SELECT 
        code,
        AVG(CASE WHEN requesteddate <> latestDAte THEN price END) AS avgBest,
        100.0 * ( 
            latestPrice - AVG(CASE WHEN requesteddate <> latestDAte THEN price END)) 
            / AVG(CASE WHEN requesteddate <> latestDAte THEN price END) AS percentageDrop,
        JSON_ARRAYAGG(ROUND(price) ORDER BY requesteddate) AS timeSeries,
        MIN(price) AS minPrice,
        MAX(price) AS maxPrice,
        latestPrice
        FROM best_daily
        GROUP BY 1
    )
    
    SELECT 
        *,
        CASE WHEN percentageDrop < 0 THEN 
            percentageDrop / MIN(percentageDrop) OVER ()
        ELSE 
            0
        END AS ratioBestDrop
    FROM grouped
    ORDER BY percentageDrop ASC
    SQL;

    return fetch($db, $query, [$_SESSION['flightsid']], PDO::FETCH_ASSOC);
}

?>


<?php
$loader = new \Twig\Loader\FilesystemLoader('../templates');
$twig = new \Twig\Environment($loader);

$curr = new DateTimeImmutable();


echo $twig->render('first.html.twig', [
    'name' => $_SESSION['flightsuser'],
    'countries' => get_countries($db, $_SESSION['flightsid']),
    'rows' => get_data($db),
    'windowstarts' => [
        $curr->format('Y-m-d'),
        $curr->add(new DateInterval('P4M'))->format('Y-m-d'),
        $curr->add(new DateInterval('P8M'))->format('Y-m-d')
    ],
    'trends' => get_trends($db)
]);
?>

