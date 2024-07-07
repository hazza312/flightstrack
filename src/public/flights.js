const SCHIPHOL = [52.308601,4.76389];

// need to hold reference to call .destroy() when redrawing
var airportMarkers = [];

var map = L.map('map').setView(SCHIPHOL, 2);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

for (let c of Object.values(countries)) {
  let text = `${getFlagEmoji(c.country)} ${c.code} ${c.name} (${c.dist} km)`;
  airportMarkers.push(L.marker([c.lat, c.lon]).bindTooltip(text).addTo(map));
}
airportMarkers.push(L.marker([52.308601,4.76389]).bindTooltip(`${getFlagEmoji('NL')} AMS Amsterdam Schiphol Airport`).addTo(map));
for (let c of Object.values(countries)) {
  new L.Geodesic([[c.lat, c.lon], SCHIPHOL]).addTo(map).bindTooltip(`${c.dist} km`);
}

map.fitBounds(new L.featureGroup(airportMarkers).getBounds());