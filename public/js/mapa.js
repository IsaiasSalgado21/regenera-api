(function () {
    const mapElement = document.getElementById("environmentMap");
    if (!mapElement) return;

    const status = document.getElementById("mapStatus");
    if (!window.L) {
        status.textContent = "No se pudo cargar Leaflet. Comprueba tu conexión e inténtalo de nuevo.";
        return;
    }

    const markers = Array.isArray(window.regeneraMapMarkers) ? window.regeneraMapMarkers : [];
    const map = L.map(mapElement).setView([24.2, -110.3], 9);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const colors = {
        lugares: "#18794e",
        flora: "#73a942",
        fauna: "#2878b5",
        actividades: "#d97706",
    };
    const markerLayers = new Map();

    function createPopup(marker) {
        const content = document.createElement("div");
        const title = document.createElement("h3");
        title.className = "map-popup-title";
        title.textContent = marker.nombre || "Registro ambiental";
        content.appendChild(title);

        if (marker.detalle) {
            const detail = document.createElement("p");
            detail.className = "map-popup-detail";
            detail.textContent = marker.detalle;
            content.appendChild(detail);
        }

        if (marker.descripcion) {
            const description = document.createElement("p");
            description.className = "map-popup-description";
            description.textContent = marker.descripcion;
            content.appendChild(description);
        }

        if (marker.url) {
            const link = document.createElement("a");
            link.href = marker.url;
            link.textContent = marker.categoria === "actividades" ? "Ver actividad" : "Ver información";
            content.appendChild(link);
        }

        return content;
    }

    markers.forEach(marker => {
        const latitude = Number(marker.latitud);
        const longitude = Number(marker.longitud);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

        const icon = L.circleMarker([latitude, longitude], {
            radius: marker.categoria === "actividades" ? 9 : 7,
            color: colors[marker.categoria] || "#18794e",
            fillColor: colors[marker.categoria] || "#18794e",
            fillOpacity: 0.85,
            weight: 2,
        }).bindPopup(createPopup(marker));

        if (!markerLayers.has(marker.categoria)) markerLayers.set(marker.categoria, L.layerGroup());
        markerLayers.get(marker.categoria).addLayer(icon);
        icon._regeneraMunicipio = marker.municipio || "";
    });

    function applyFilters() {
        const selectedCategories = new Set(
            Array.from(document.querySelectorAll(".map-filters input:checked"))
                .map(input => input.value)
        );
        const municipality = document.getElementById("mapMunicipality").value;
        let visibleCount = 0;

        markerLayers.forEach((layer, category) => {
            const categoryVisible = selectedCategories.has(category);
            if (categoryVisible && !map.hasLayer(layer)) layer.addTo(map);
            if (!categoryVisible && map.hasLayer(layer)) map.removeLayer(layer);

            layer.getLayers().forEach(marker => {
                const municipalityVisible = !municipality || marker._regeneraMunicipio === municipality;
                if (categoryVisible && municipalityVisible) visibleCount++;

                if (municipalityVisible && !layer.hasLayer(marker)) layer.addLayer(marker);
                if (!municipalityVisible && layer.hasLayer(marker)) layer.removeLayer(marker);
            });
        });

        status.textContent = `${visibleCount} ${visibleCount === 1 ? "marcador visible" : "marcadores visibles"}.`;
    }

    document.querySelectorAll(".map-filters input").forEach(input => {
        input.addEventListener("change", applyFilters);
    });
    document.getElementById("mapMunicipality").addEventListener("change", applyFilters);

    const validMarkers = markers.filter(marker =>
        Number.isFinite(Number(marker.latitud)) && Number.isFinite(Number(marker.longitud))
    );
    if (validMarkers.length) {
        map.fitBounds(validMarkers.map(marker => [Number(marker.latitud), Number(marker.longitud)]), {
            padding: [30, 30],
            maxZoom: 12,
        });
    }

    applyFilters();
})();
