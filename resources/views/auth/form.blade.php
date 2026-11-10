<section class="auth-card" data-auth-widget data-auth-mode-current="login" aria-label="Acceso a Regenera BCS">
    <aside class="auth-aside">
        <span class="auth-aside-mark" aria-hidden="true">R</span>
        <span class="auth-aside-kicker">REGENERA BCS</span>
        <h2>La naturaleza nos une.</h2>
        <p>Participa en acciones que cuidan Baja California Sur y conecta con una comunidad que transforma su entorno.</p>
        <span class="auth-aside-foot">Comunidad · Conservación · Acción</span>
    </aside>

    <div class="auth-main">
        <div class="auth-heading">
            <span class="auth-eyebrow" data-auth-eyebrow>QUÉ BUENO TENERTE DE VUELTA</span>
            <h1 data-auth-title>Inicia sesión</h1>
            <p data-auth-description>Accede para continuar con tus actividades en Regenera BCS.</p>
        </div>

        <div class="auth-switch" role="tablist" aria-label="Tipo de acceso">
            <button type="button" class="active" data-auth-mode="login" role="tab" aria-selected="true">Iniciar sesión</button>
            <button type="button" data-auth-mode="register" role="tab" aria-selected="false">Crear cuenta</button>
        </div>

        <div class="auth-panels">
            <section class="auth-panel is-active" data-auth-panel="login" role="tabpanel" aria-hidden="false">
                <form data-auth-form="login" novalidate>
                    <label for="{{ $authPrefix }}-login-correo">Correo electrónico</label>
                    <input type="email" id="{{ $authPrefix }}-login-correo" name="correo" autocomplete="email"
                        placeholder="nombre@correo.com" required>

                    <label for="{{ $authPrefix }}-login-password">Contraseña</label>
                    <div class="auth-password-field">
                        <input type="password" id="{{ $authPrefix }}-login-password" name="password"
                            autocomplete="current-password" placeholder="Tu contraseña" required>
                        <button type="button" data-auth-password-toggle aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                    </div>

                    <p class="auth-message" data-auth-message role="status" aria-live="polite"></p>
                    <button class="auth-submit" type="submit" data-auth-submit>
                        <span data-auth-submit-label>Iniciar sesión</span><span aria-hidden="true">→</span>
                    </button>
                </form>
                <p class="auth-panel-note">Al ingresar, volverás a esta misma página.</p>
            </section>

            <section class="auth-panel" data-auth-panel="register" role="tabpanel" aria-hidden="true">
                <form data-auth-form="register" novalidate>
                    <label for="{{ $authPrefix }}-register-nombre">Nombre</label>
                    <input type="text" id="{{ $authPrefix }}-register-nombre" name="nombre" autocomplete="name"
                        placeholder="¿Cómo te llamas?" required maxlength="255">

                    <label for="{{ $authPrefix }}-register-correo">Correo electrónico</label>
                    <input type="email" id="{{ $authPrefix }}-register-correo" name="correo" autocomplete="email"
                        placeholder="nombre@correo.com" required>

                    <div class="auth-password-grid">
                        <div>
                            <label for="{{ $authPrefix }}-register-password">Contraseña</label>
                            <div class="auth-password-field">
                                <input type="password" id="{{ $authPrefix }}-register-password" name="password"
                                    autocomplete="new-password" placeholder="Mínimo 8 caracteres" required minlength="8">
                                <button type="button" data-auth-password-toggle aria-label="Mostrar contraseña" aria-pressed="false">Ver</button>
                            </div>
                        </div>
                        <div>
                            <label for="{{ $authPrefix }}-register-confirmation">Confirma tu contraseña</label>
                            <div class="auth-password-field">
                                <input type="password" id="{{ $authPrefix }}-register-confirmation" name="password_confirmation"
                                    autocomplete="new-password" placeholder="Repite tu contraseña" required minlength="8">
                                <button type="button" data-auth-password-toggle aria-label="Mostrar contraseña" aria-pressed="false">Ver</button>
                            </div>
                        </div>
                    </div>

                    <p class="auth-message" data-auth-message role="status" aria-live="polite"></p>
                    <button class="auth-submit" type="submit" data-auth-submit>
                        <span data-auth-submit-label>Crear mi cuenta</span><span aria-hidden="true">→</span>
                    </button>
                </form>
                <p class="auth-panel-note">Al crear tu cuenta aceptas participar respetando a la comunidad y el entorno natural.</p>
            </section>
        </div>
    </div>
</section>
