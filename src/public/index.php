<?php
require_once("../init.php");
require_once("../queries.php");

function flagEmoji($countryCode) {
    return htmlentities(mb_chr(127397 + mb_ord($countryCode[0])) . mb_chr(127397 + mb_ord($countryCode[1])));
}

function shortAirportName($airportName) {
    return str_replace(['International Airport', 'Airport'], '', $airportName);
}


$loader = new \Twig\Loader\FilesystemLoader('../templates');
$twig = new \Twig\Environment($loader);
$twig->addFilter(new Twig\TwigFilter('flagemoji', 'flagEmoji'));
$twig->addFilter(new Twig\TwigFilter('shortAirportName', 'shortAirportName'));

echo $twig->render('report.html.twig', [
    'rows' => get_current_offers($db),
    'trends' => get_offer_trends($db)
]);
?>

