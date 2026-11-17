<dialog id="proposePlaceDialog" class="propose-place-dialog" aria-labelledby="proposePlaceTitle">
    <form id="proposePlaceForm" class="dashboard-form create-form">
        <div class="dashboard-form-heading">
            <div><h2 id="proposePlaceTitle">Agregar un lugar</h2></div>
            <button type="button" class="icon-button" data-close-place aria-label="Cerrar">×</button>
        </div>
        <p>El lugar se publicará y quedará seleccionado para tu voluntariado. El nombre y la dirección se sugieren según la ubicación y puedes modificarlos.</p>
        <label>Nombre <input name="nombre" required maxlength="150"></label>
        <label>Dirección <input name="direccion" required maxlength="255"></label>
        <div class="dashboard-form-row">
            <label>Latitud <input name="latitud" type="number" step="any" min="-90" max="90" required></label>
            <label>Longitud <input name="longitud" type="number" step="any" min="-180" max="180" required></label>
        </div>
        <div class="location-selector">
            <p class="location-selector-help">Haz clic o arrastra el mapa para situar el marcador dentro de Baja California Sur.</p>
            <div class="location-selector-map" data-location-map aria-label="Mapa para seleccionar la ubicación">
                <div class="location-selector-pin" aria-hidden="true"></div>
            </div>
        </div>
        <p id="placeGeocodeStatus" class="form-message" role="status" aria-live="polite"></p>
        <p id="proposePlaceMessage" class="form-message" role="status" aria-live="polite"></p>
        <div class="dashboard-form-actions">
            <button type="submit" class="button button-primary">Agregar lugar</button>
            <button type="button" class="button button-secondary" data-close-place>Cancelar</button>
        </div>
    </form>
</dialog>
