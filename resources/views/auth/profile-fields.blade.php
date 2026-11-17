<div data-profile-fields>
    <label for="{{ $profilePrefix }}-secondary">Correo secundario privado</label>
    <input id="{{ $profilePrefix }}-secondary" name="correoSecundario" type="email" maxlength="255" aria-describedby="{{ $profilePrefix }}-secondary-help" required>
    <small id="{{ $profilePrefix }}-secondary-help" class="profile-field-help">Se reservará para recuperar tu cuenta cuando esta función esté disponible.</small>
    <label for="{{ $profilePrefix }}-city">Ciudad en la que resides</label>
    <div class="profile-city-picker" data-city-picker>
        <input id="{{ $profilePrefix }}-city" name="ciudad" maxlength="150" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $profilePrefix }}-cities" aria-describedby="{{ $profilePrefix }}-city-help" required>
        <button type="button" class="profile-city-toggle" data-city-toggle aria-label="Mostrar localidades" tabindex="-1"><span aria-hidden="true">▾</span></button>
        <div id="{{ $profilePrefix }}-cities" class="profile-city-options" data-city-options role="listbox" aria-label="Localidades de Baja California Sur" hidden>
            @foreach (['La Paz', 'Cabo San Lucas', 'San José del Cabo', 'San José Viejo', 'Ciudad Constitución', 'Ciudad Insurgentes', 'Loreto', 'Santa Rosalía', 'Guerrero Negro', 'Mulegé', 'Vizcaíno', 'Todos Santos', 'El Pescadero', 'Los Barriles', 'La Ribera', 'Santiago', 'Miraflores', 'San Bartolo', 'Bahía Asunción', 'Bahía Tortugas', 'San Ignacio', 'Puerto San Carlos', 'Puerto López Mateos', 'Otra localidad'] as $city)
                <button type="button" id="{{ $profilePrefix }}-city-option-{{ $loop->index }}" class="profile-city-option" data-city-option="{{ $city }}" role="option" aria-selected="false" tabindex="-1">{{ $city }}</button>
            @endforeach
        </div>
    </div>
    <small id="{{ $profilePrefix }}-city-help" class="profile-field-help">Selecciona una localidad de Baja California Sur o elige «Otra localidad».</small>
    <div data-other-city hidden>
        <label for="{{ $profilePrefix }}-city-other">Escribe tu localidad</label>
        <input id="{{ $profilePrefix }}-city-other" name="ciudadOtra" maxlength="150">
    </div>
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
                    <select id="{{ $profilePrefix }}-birth-year" autocomplete="bday-year" data-birth-year required>
                        <option value="">Año</option>
                        @for ($year = now()->year - 13; $year >= 1920; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
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
    <div class="profile-phone-fields">
        <div class="profile-phone-country" data-phone-country>
            <span class="profile-phone-country-label" id="{{ $profilePrefix }}-phone-code-label">Lada</span>
            <input name="telefonoLada" type="hidden" value="+52">
            <button type="button" class="profile-phone-country-toggle" data-phone-country-toggle aria-label="Lada: México +52" aria-haspopup="dialog" aria-expanded="false" aria-controls="{{ $profilePrefix }}-phone-countries">
                <span class="profile-phone-flag" data-phone-country-flag aria-hidden="true"></span>
                <span data-phone-country-value>+52</span>
                <span aria-hidden="true">▾</span>
            </button>
            <div id="{{ $profilePrefix }}-phone-countries" class="profile-phone-country-menu" data-phone-country-menu role="dialog" aria-label="Selecciona una lada" hidden>
                <input type="search" data-phone-country-search aria-label="Buscar país o lada" placeholder="Buscar país o lada" autocomplete="off">
                <div data-phone-country-options role="listbox" aria-label="Países y ladas"></div>
                <p data-phone-country-empty hidden>No se encontraron países.</p>
            </div>
        </div>
        <label for="{{ $profilePrefix }}-phone">Teléfono (opcional)
            <input id="{{ $profilePrefix }}-phone" name="telefono" type="tel" autocomplete="tel-national" maxlength="24" pattern="[0-9\s\(\)\.\-]+" placeholder="612 123 4567" aria-describedby="{{ $profilePrefix }}-phone-help">
        </label>
    </div>
    <small id="{{ $profilePrefix }}-phone-help" class="profile-field-help">Puedes cambiar la lada si tu número es de otro país.</small>
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
