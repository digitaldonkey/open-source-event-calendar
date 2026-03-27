
document.addEventListener("DOMContentLoaded", function(event) {
    let mapIsInitialized = false;
    /**
     * Initialize public map.
     */
    const initMap = () => {
        if (mapIsInitialized) {
            return;
        }
        mapIsInitialized = true;

        const map_div = document.getElementById('osec-map');
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
    }
    /**
     * Init, when "Hide Maps until clicked" is off.
     */
    runIfElementIsVisible('osec-map').then((map_div) => {
        if (map_div) {
            initMap();
        }
    })
    /**
     * Init, when Maps placholder is clicked.
     */
    const placeHolders = document.getElementsByClassName('osec-map-placeholder');
    placeHolders[0].addEventListener("click", () =>{
        initMap();
    });
});
