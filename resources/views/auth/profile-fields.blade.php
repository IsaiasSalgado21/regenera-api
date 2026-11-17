<div data-profile-fields>
    <label for="{{ $profilePrefix }}-secondary">Correo secundario privado</label>
    <input id="{{ $profilePrefix }}-secondary" name="correoSecundario" type="email" maxlength="255" aria-describedby="{{ $profilePrefix }}-secondary-help" required>
    <small id="{{ $profilePrefix }}-secondary-help" class="profile-field-help">Se reservará para recuperar tu cuenta cuando esta función esté disponible.</small>
    <label for="{{ $profilePrefix }}-city">Ciudad en la que resides</label>
    <input id="{{ $profilePrefix }}-city" name="ciudad" maxlength="150" required>
    @if ($profileBirth ?? true)
        @if ($profileBirthParts ?? false)
            <span class="profile-field-label" id="{{ $profilePrefix }}-birth-label">Fecha de nacimiento</span>
            <div class="profile-birth-parts" role="group" aria-labelledby="{{ $profilePrefix }}-birth-label" data-birth-parts>
                <label for="{{ $profilePrefix }}-birth-day">Día
                    <select id="{{ $profilePrefix }}-birth-day" autocomplete="bday-day" data-birth-day required>
                        <option value="">Día</option>
                        @for ($day = 1; $day <= 31; $day++)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endfor
                    </select>
                </label>
                <label for="{{ $profilePrefix }}-birth-month">Mes
                    <select id="{{ $profilePrefix }}-birth-month" autocomplete="bday-month" data-birth-month required>
                        <option value="">Mes</option>
                        @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $month)
                            <option value="{{ $loop->iteration }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </label>
                <label for="{{ $profilePrefix }}-birth-year">Año
                    <input id="{{ $profilePrefix }}-birth-year" type="number" min="1" max="9999" step="1" placeholder="AAAA" autocomplete="bday-year" data-birth-year required>
                </label>
                <input type="hidden" name="fechaNacimiento" data-birth-date>
            </div>
        @else
            <label for="{{ $profilePrefix }}-birth">Fecha de nacimiento</label>
            <input id="{{ $profilePrefix }}-birth" name="fechaNacimiento" type="date" required data-birth-date>
        @endif
    @endif
    <label for="{{ $profilePrefix }}-gender">Género</label>
    <select id="{{ $profilePrefix }}-gender" name="genero" required>
        <option value="">Selecciona una opción</option>
        <option value="mujer">Mujer</option>
        <option value="hombre">Hombre</option>
        <option value="otro">Otro</option>
        <option value="prefiero_no_decirlo">Prefiero no decirlo</option>
    </select>
    <div data-other-gender hidden>
        <label for="{{ $profilePrefix }}-gender-other">Otro género (opcional)</label>
        <input id="{{ $profilePrefix }}-gender-other" name="generoOtro" maxlength="100">
    </div>
    <label for="{{ $profilePrefix }}-phone">Teléfono (opcional)</label>
    <input id="{{ $profilePrefix }}-phone" name="telefono" type="tel" autocomplete="tel" maxlength="40">
    @if ($profileDescription ?? true)
        <label for="{{ $profilePrefix }}-description">Descripción (opcional)</label>
        <textarea id="{{ $profilePrefix }}-description" name="descripcion" maxlength="1000"></textarea>
    @endif
    <fieldset data-tutor-fields hidden>
        <legend>Datos del tutor</legend>
        <p>Declara que tienes autorización de una persona adulta. No se verificará ni vinculará su cuenta.</p>
        <label for="{{ $profilePrefix }}-guardian-name">Nombre completo</label>
        <input id="{{ $profilePrefix }}-guardian-name" name="tutorNombre" autocomplete="section-guardian name" maxlength="150">
        <label for="{{ $profilePrefix }}-guardian-email">Correo</label>
        <input id="{{ $profilePrefix }}-guardian-email" name="tutorCorreo" type="email" autocomplete="section-guardian email" maxlength="255">
        <label for="{{ $profilePrefix }}-guardian-phone">Teléfono</label>
        <input id="{{ $profilePrefix }}-guardian-phone" name="tutorTelefono" type="tel" autocomplete="section-guardian tel" maxlength="40">
        <label for="{{ $profilePrefix }}-guardian-birth">Fecha de nacimiento</label>
        <input id="{{ $profilePrefix }}-guardian-birth" name="tutorFechaNacimiento" type="date" autocomplete="section-guardian bday">
        <label><input name="tutorAutorizacion" type="checkbox"> Declaro que cuento con autorización de mi tutor.</label>
    </fieldset>
    <label><input name="privacidad" type="checkbox"> Mantener privado mi perfil</label>
    <small class="profile-field-help" data-privacy-minor-notice role="status" hidden>Tu perfil privado se activó porque eres menor de edad.</small>
</div>
