<?php

function flagEmoji($countryCode) {
    return htmlentities(mb_chr(127397 + mb_ord($countryCode[0])) . mb_chr(127397 + mb_ord($countryCode[1])));
}

function shortAirportName($airportName) {
    return str_replace(['International Airport', 'Airport'], '', $airportName);
}