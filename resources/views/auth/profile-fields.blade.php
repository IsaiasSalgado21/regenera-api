<div data-profile-fields>
    <label for="{{ $profilePrefix }}-secondary">Correo secundario privado</label>
    <input id="{{ $profilePrefix }}-secondary" name="correoSecundario" type="email" maxlength="255" required>
    <label for="{{ $profilePrefix }}-city">Ciudad</label>
    <input id="{{ $profilePrefix }}-city" name="ciudad" maxlength="150" required>
    @if ($profileBirth ?? true)
        <label for="{{ $profilePrefix }}-birth">Fecha de nacimiento</label>
        <input id="{{ $profilePrefix }}-birth" name="fechaNacimiento" type="date" required data-birth-date>
    @endif
    <label for="{{ $profilePrefix }}-gender">Género</label>
    <select id="{{ $profilePrefix }}-gender" name="genero" required>
        <option value="">Selecciona una opción</option>
        <option value="mujer">Mujer</option>
        <option value="hombre">Hombre</option>
        <option value="otro">Otro</option>
        <option value="prefiero_no_decirlo">Prefiero no decirlo</option>
    </select>
    <label for="{{ $profilePrefix }}-gender-other">Otro género (opcional)</label>
    <input id="{{ $profilePrefix }}-gender-other" name="generoOtro" maxlength="100">
    <label for="{{ $profilePrefix }}-phone">Teléfono (opcional)</label>
    <input id="{{ $profilePrefix }}-phone" name="telefono" type="tel" maxlength="40">
    <label for="{{ $profilePrefix }}-description">Descripción (opcional)</label>
    <textarea id="{{ $profilePrefix }}-description" name="descripcion" maxlength="1000"></textarea>
    <fieldset data-tutor-fields hidden>
        <legend>Datos del tutor</legend>
        <p>Declara que tienes autorización de una persona adulta. No se verificará ni vinculará su cuenta.</p>
        <label for="{{ $profilePrefix }}-guardian-name">Nombre completo</label>
        <input id="{{ $profilePrefix }}-guardian-name" name="tutorNombre" maxlength="150">
        <label for="{{ $profilePrefix }}-guardian-email">Correo</label>
        <input id="{{ $profilePrefix }}-guardian-email" name="tutorCorreo" type="email" maxlength="255">
        <label for="{{ $profilePrefix }}-guardian-phone">Teléfono</label>
        <input id="{{ $profilePrefix }}-guardian-phone" name="tutorTelefono" type="tel" maxlength="40">
        <label for="{{ $profilePrefix }}-guardian-birth">Fecha de nacimiento</label>
        <input id="{{ $profilePrefix }}-guardian-birth" name="tutorFechaNacimiento" type="date">
        <label><input name="tutorAutorizacion" type="checkbox"> Declaro que cuento con autorización de mi tutor.</label>
    </fieldset>
    <label><input name="privacidad" type="checkbox"> Mantener privado mi perfil (obligatorio para menores de edad)</label>
</div>
