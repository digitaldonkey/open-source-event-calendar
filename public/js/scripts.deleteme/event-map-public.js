
document.addEventListener("DOMContentLoaded", function(event) {

    runIfElementIsVisible('osec-map').then((map_div) => {
        const maxZoom = parseInt(map_div.dataset.maxzoom);
        const zoom = parseInt(map_div.dataset.zoom);
        const lat = parseFloat(map_div.dataset.lat)
        const long = parseFloat(map_div.dataset.long)

        let map = L.map('osec-map');
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        let marker = L.marker([lat, long]).addTo(map);
        map.setView([lat, long], zoom);
        marker.setLatLng([lat, long]).update();
    })
});
