<dialog class="avatar-editor-dialog" data-avatar-dialog aria-labelledby="avatarEditorTitle">
    <header class="avatar-editor-header">
        <div>
            <span class="profile-eyebrow">REGENERA BCS / PERSONALIZACIÓN</span>
            <h2 id="avatarEditorTitle">Crea tu avatar</h2>
            <p>Tu personaje representa tu recorrido en la comunidad.</p>
        </div>
        <button class="avatar-editor-close" type="button" data-avatar-close aria-label="Cerrar editor">×</button>
    </header>
    <div class="avatar-editor-layout">
        <aside class="avatar-editor-preview-panel" aria-label="Vista previa del avatar">
            <span class="profile-eyebrow">VISTA PREVIA</span>
            <div class="avatar-editor-stage">
                <span class="avatar-editor-spark avatar-editor-spark-one" aria-hidden="true">✦</span>
                <span class="avatar-editor-spark avatar-editor-spark-two" aria-hidden="true">✿</span>
                <div id="avatar-container" class="avatar-editor-character"></div>
                <span class="avatar-editor-shadow" aria-hidden="true"></span>
            </div>
            <div class="avatar-editor-reward" data-avatar-reward hidden>
                <strong data-avatar-reward-title></strong>
                <p data-avatar-reward-copy></p>
                <div>
                    <button type="button" data-avatar-reward-equip>Probar recompensa</button>
                    <button type="button" data-avatar-reward-dismiss>Más tarde</button>
                </div>
            </div>
            <p class="avatar-editor-status" data-avatar-status role="status" aria-live="polite"></p>
        </aside>
        <section class="avatar-editor-controls" data-avatar-controls aria-label="Opciones de personalización"></section>
    </div>
    <footer class="avatar-editor-footer">
        <button class="avatar-editor-cancel" type="button" data-avatar-close>Cancelar</button>
        <button class="avatar-editor-save" type="button" id="save-avatar">Guardar avatar</button>
    </footer>
</dialog>