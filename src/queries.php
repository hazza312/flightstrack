<?php

function saveApiCallResult($db, $requestBody, $response): void
{
    $st = $db->prepare("INSERT INTO apicalls (url, status, request, response) VALUES (?, ?, ?, ?)");
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
        -- the proportion decrease from the highest price for a window on a given request date 
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
            requesteddate,
            lf.code,
            defaultselected,
            MIN(price) AS price
        FROM lowestfaredestination lf
        JOIN trackeddestinations USING (code)
        WHERE showinreport
        AND requesteddate >= CURRENT_DATE - INTERVAL 90 DAY
        GROUP BY 1, 2, 3  
    )

    , by_destination AS (
        SELECT 
            code,
            defaultselected,
            JSON_ARRAYAGG(price ORDER BY requesteddate) AS timeseries,
            AVG(CASE WHEN requesteddate <> CURRENT_DATE THEN price END) AS precedingAverage,
            MIN(CASE WHEN requesteddate = CURRENT_DATE THEN price END) AS currentPrice,
            MIN(price) AS minPrice,
            MAX(price) AS maxPrice
        FROM best_daily
        GROUP BY 1, 2
    )

    SELECT 
        by_destination.*,
        name,
        country,
        100.0 * (currentPrice - precedingAverage) / precedingAverage AS percentageChangeToAvg,

        -- calculate percentageChangeToAvg as a ratio to the "best" percentage drop from avg over all destinations
        -- for colour visual indication, a means to compare in UI
        GREATEST(
            0,
            ((currentPrice - precedingAverage) / precedingAverage) 
                / 
            MIN((currentPrice - precedingAverage) / precedingAverage) OVER ()
        ) AS ratioBestDrop

        FROM by_destination
        JOIN airports USING (code)
        ORDER BY percentageChangeToAvg
    SQL;

    return fetch($db, $query, []);
}