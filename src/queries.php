<?php

function saveApiCallResult($db, $requestBody, $response): void
{
    $query = "INSERT INTO apicalls (url, status, request, response) VALUES (?, ?, ?, ?)";
    $st = $db->prepare($query);
    $st->execute([$response->url, $response->status_code, $requestBody, $response->body]);
}

function fetch($dbconn, $query, $params, $mode = PDO::FETCH_ASSOC)
{
    $st = $dbconn->prepare($query);
    $st->execute($params);
    return $st->fetchAll($mode);
}


function get_current_offers($db): array
{
    $query = <<<SQL
    SELECT 
        code,
        country,
        name,
        departureDate,
        returnDate,
        price,
        ((MAX(price) OVER (PARTITION BY code)) - price) / (MAX(price) OVER (PARTITION BY code)) AS decrease
    FROM lowestfaredestination
    JOIN trackeddestinations USING (code)
    JOIN airports USING (code)
    WHERE showinreport AND requesteddate = (SELECT MAX(requesteddate) FROM lowestfaredestination)
    ORDER BY code, windowstart
    SQL;

    return fetch($db, $query, []);
}

function get_offer_trends($db)
{
    $query = <<<SQL
    WITH best_daily AS (
        SELECT 
        code,
        requesteddate,
        MIN(price) AS price,
        FIRST_VALUE(MIN(price)) OVER (PARTITION BY code ORDER BY requested DESC) AS latestPrice,
        FIRST_VALUE(requesteddate) OVER (PARTITION BY code ORDER BY requested DESC) AS latestDate,
        defaultselected
        
        FROM lowestfaredestination
        JOIN trackeddestinations USING (code) 
        WHERE requesteddate BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY) AND CURRENT_DATE
        AND showinreport
        GROUP BY 1, 2
        ORDER BY 1, 2  
    ), grouped AS (
        SELECT 
        code,
        defaultselected,
        AVG(CASE WHEN requesteddate <> latestDAte THEN price END) AS avgBest,
        100.0 * ( 
            latestPrice - AVG(CASE WHEN requesteddate <> latestDAte THEN price END)) 
            / AVG(CASE WHEN requesteddate <> latestDAte THEN price END) AS percentageDrop,
        JSON_ARRAYAGG(ROUND(price) ORDER BY requesteddate) AS timeSeries,
        MIN(price) AS minPrice,
        MAX(price) AS maxPrice,
        latestPrice
        FROM best_daily
        GROUP BY 1, 2
    )
    
    SELECT 
        *,
        CASE WHEN percentageDrop < 0 THEN 
            percentageDrop / MIN(percentageDrop) OVER ()
        ELSE 
            0
        END AS ratioBestDrop,
        airports.country,
        airports.name
    FROM grouped
    JOIN airports USING (code)
    ORDER BY percentageDrop ASC
    SQL;

    return fetch($db, $query, []);
}