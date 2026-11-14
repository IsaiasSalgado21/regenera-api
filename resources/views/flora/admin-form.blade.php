<dialog class="flora-admin-dialog" data-flora-dialog aria-labelledby="floraAdminFormTitle">
    <header class="flora-admin-dialog-heading">
        <div>
            <span class="profile-eyebrow">ADMINISTRACIÓN / BIODIVERSIDAD</span>
            <h2 id="floraAdminFormTitle" data-flora-form-title>Crear registro</h2>
        </div>
        <button class="flora-admin-close" type="button" data-flora-close aria-label="Cerrar formulario">×</button>
    </header>
    <form class="flora-admin-form" data-flora-form>
        <label>Nombre común<input name="nombre" required maxlength="150"></label>
        <label>Nombre científico<input name="nombreCientifico" maxlength="150"></label>
        <label>Clasificación
            <select name="tipo" required>
                <option value="flora">Flora</option>
                <option value="fauna">Fauna</option>
            </select>
        </label>
        <label>Estado de conservación<input name="estadoConservacion" maxlength="100"></label>
        <label class="flora-admin-wide">Descripción<textarea name="descripcion" rows="4" required maxlength="2000"></textarea></label>
        <label class="flora-admin-wide">Hábitat<textarea name="habitat" rows="2" maxlength="500"></textarea></label>
        <label>Ubicación registrada<input name="ubicacion[nombre]" maxlength="255"></label>
        <label>Fuente de información<input name="fuenteInformacion" maxlength="255"></label>
        <label>Latitud<input name="ubicacion[latitud]" type="number" min="-90" max="90" step="any"></label>
        <label>Longitud<input name="ubicacion[longitud]" type="number" min="-180" max="180" step="any"></label>
        <label class="flora-admin-wide">Imágenes (hasta 5)<input name="imagenes[]" type="file" accept="image/*" multiple></label>
        <div class="flora-admin-existing-images flora-admin-wide" data-flora-existing-images></div>
        <p class="flora-admin-status flora-admin-wide" data-flora-status role="status" aria-live="polite"></p>
        <footer class="flora-admin-form-actions flora-admin-wide">
            <button class="button button-secondary" type="button" data-flora-close>Cancelar</button>
            <button class="button button-primary" type="submit">Guardar registro</button>
        </footer>
    </form>
</dialog>