/**
 * @file Leaflet maps Admin
 */
wp.domReady(function() {
    runIfElementIsVisible('osec-map').then((map_div) => {
        new GeocoderMap(map_div, osec_leaflet_admin);
    })
})
class GeocoderMap {
    constructor(map_div, options) {
        this.options = options;

        map_div.style.height = this.options.map_height;

        // Interactive Form Elements
        this.lat_input = document.getElementById("osec_latitude");
        this.long_input = document.getElementById("osec_longitude");


        this.map = L.map('osec-map');
        this. marker = L.marker([this.lat_input.value, this.long_input.value]).addTo(this.map);

        const geocoder = L.Control.Geocoder.nominatim({
            geocodingQueryParams: this.options.geocodingQueryParams,
            htmlTemplate: this.suggestionsTemplate,
        })
        // Enable autocomplete after typing
        geocoder.suggest = geocoder.geocode;

        // Add Search form to map.
        new L.Control.Geocoder({
            collapsed: false, // Wide Search input
            placeholder: osec_leaflet_admin.placeholder,
            iconLabel: osec_leaflet_admin.placeholder,
            showUniqueResult: false,
            defaultMarkGeocode: false,
            // Minimum number characters before suggest functionality is used (if available from geocoder)
            suggestMinLength: 3,
            // Number of milliseconds after typing stopped before suggest functionality is used (if available from geocoder)
            suggestTimeout: 500,
            geocoder
        })
        .on('markgeocode', this.onMarkGeoCode).addTo(this.map);


        // Init map.
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: this.options.map_max_zooom,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.map);

        // In case of manual change of coordinates.
        this.lat_input.addEventListener("change", this.renderMap);
        this.long_input.addEventListener("change", this.renderMap);
        this.initToggleChordsVisibility();
        this.renderMap();
    }

    renderMap = () => {
        this.ensureDottedCoordinates();
        this.map.setView([
            this.lat_input.value,
            this.long_input.value,
        ], this.options.map_zoom );
        this.marker.setLatLng([this.lat_input.value, this.long_input.value]).update();
    }

    ensureDottedCoordinates() {
        for (let item of document.getElementsByClassName('osec-js-ensure-dotted')) {
            if (item.value.includes(',')) {
                item.value = item.value.replace(',', '.');
            }
        }
    }
    initToggleChordsVisibility() {
        const checkbox = document.getElementById('osec_input_coordinates');
        let timoutId = undefined;
        checkbox.addEventListener("change", (event) => {
            const table = document.getElementById('osec_table_coordinates');
            const long = document.getElementById('osec_longitude');
            const lat = document.getElementById('osec_latitude');

            if (event.target.checked) {
                if (typeof timoutId === "number") {
                    clearTimeout(timoutId)
                }
                table.classList.remove('is-hidden');
                long.setAttribute('type', 'text');
                lat.setAttribute('type', 'text');
            }
            else {
                table.classList.add('is-hidden');
                timoutId = setTimeout(() => {
                    long.setAttribute('type', 'hidden');
                    lat.setAttribute('type', 'hidden');
                }, 1000);
            }
        });
    }
    onMarkGeoCode = (event) => {
        // Update Search form
        const display_name = this.formatAddress(event.geocode)
        event.sourceTarget._input.value = display_name;
        // Results update WP
        event.geocode.properties.display_name = display_name;

        this.lat_input.value = event.geocode.properties.lat;
        this.long_input.value = event.geocode.properties.lon;


        this.updateWpForm(event.geocode.properties)
        this.renderMap()
    }

    formatAddress = (geocode) => {
        const template = this.options.geocode_to_address_template;

        // Experimental.
        // @see OSEC_GEOCODE_TO_ADDRESS_TEMPLATE
        if (!template || template.length === 0) {
            return geocode.display_name
        }

        const address = geocode.properties.address;
        let value = '';

        template.split(',').forEach(function(part) {
            const current = part.trim();

            // Alternatives:
            //   OR selector; First hit.
            if (current.includes('|')) {
                current.split('|').every(function(alternative) {
                    // Get the first available alternative only.
                    if (Object.hasOwn(address, alternative)) {
                        value = value.concat(address[alternative] + ' ');
                        return true;
                    }
                    return false;
                });
            }
            else if (address[current]) {
                value = value.concat(address[current] + ' ');
            }
            if (current === 'separator') {
                value = value.trim() + ', '
            }
        });
        // Remove subsequent separators.
        value = value.trim()
            .replace(',, ', ', ')
            .replace(/,$/, '')
            .replace(/^, /, '')
        return value
    }

    updateWpForm (geocode){
        function update(id, value) {
            const elm = document.getElementById(id);
            elm.value = value ?? '';
        }

        const venue = document.getElementById('osec_venue');
        if (!venue.value && geocode.name) {
            venue.value = geocode.name;
        }
        update('osec_address', geocode.display_name);
        update('osec_city', geocode.address.city);
        update('osec_province', geocode.address.borough);
        update('osec_postal_code', geocode.address.postcode);
        update('osec_country', geocode.address.country);
        update('osec_country_short', geocode.address.country_code);
    }

    // Search Results template
    suggestionsTemplate = (NominatimResult) => {
        const address = NominatimResult.address;
        let className;
        const parts = [];
        if (address.road || address.building) {
            parts.push('{building} {road} {house_number}');
        }

        if (address.city || address.town || address.village || address.hamlet) {
            className = parts.length > 0 ? 'leaflet-control-geocoder-address-detail' : '';
            parts.push(
                '<span class="'  + className + '">{postcode} {city} {town} {village} {hamlet}</span>'
            );
        }

        if (address.state || address.country) {
            parts.push('<span class="' + className + '">{state} {country}</span>');
        }

        return this.templateRender(parts.join(' '), address);
    }

    templateRender = (str, data) => {
        return str.replace(/\{ *([\w_]+) *\}/g, (str, key) => {
            let value = data[key];
            if (value === undefined) {
                value = '';
            } else if (typeof value === 'function') {
                value = value(data);
            }
            return value;
        });
    }
}
