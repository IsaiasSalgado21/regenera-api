# Auditoría Fase 0 y pendientes de despliegue

## Alcance

Revisión estática del repositorio y ejecución local de `php artisan test`. No se consultó el panel de Railway ni se inspeccionaron valores de `.env`; por tanto, la existencia de servicios, variables y dominios configurados en Railway requiere verificación manual. No se incluyen valores secretos.

## 1. Resultado de la auditoría Fase 0

| Ítem | Estado | Evidencia revisada | Acción recomendada |
|---|---|---|---|
| `.env` fuera de Git | ✅ OK | [`.gitignore`](../.gitignore); el `.env` local está ignorado. La llave local existe, pero no se expone aquí. | Mantener `.env` fuera de Git. Configurar secretos por separado en Railway. |
| `.env.example` completo y seguro para producción | ⚠️ Parcial | [`.env.example`](../.env.example#L2): usa `APP_ENV=local`, `APP_DEBUG=true`, `DB_CONNECTION=sqlite`; `DB_DATABASE` está comentado y no declara `DB_URI`. | No copiarlo tal cual a Railway. Documentar las variables de producción y la conexión MongoDB. |
| Escucha en `$PORT` | ❌ Falta | No se encontró `Dockerfile`, `Procfile`, `railway.json` ni `railway.toml`. El script `dev` usa `php artisan serve` sin `PORT` ([`composer.json`](../composer.json#L47)). | Configurar en Railway el comando de inicio para escuchar en `0.0.0.0` y en el puerto `$PORT`. Ver sección 2. |
| `APP_KEY` generada y documentada | ⚠️ Parcial | La configuración lee `APP_KEY` ([`config/app.php`](../config/app.php#L100)); `.env.example` la deja vacía ([`.env.example`](../.env.example#L3)). El archivo local ignorado tiene una llave, pero eso no configura Railway. | Generar una llave única para producción y guardarla como variable de Railway. No reutilizar la llave local ni rotarla una vez que haya datos cifrados. |
| Comportamiento si falta `APP_KEY` | ⚠️ Parcial | `config/app.php` consume `APP_KEY`; no se encontró una validación propia de arranque que garantice su presencia antes de recibir tráfico. | Confirmar que está definida antes del primer despliegue y probar el arranque. |
| Cachés de configuración/rutas | ✅ OK | En `bootstrap/cache` solo está rastreado el archivo de exclusión [`.gitignore`](../bootstrap/cache/.gitignore). | Mantener fuera de Git los archivos generados. No ejecutar `route:cache` mientras existan rutas definidas con closures. |
| Endpoint de salud | ⚠️ Parcial | Laravel registra `GET /up` ([`bootstrap/app.php`](../bootstrap/app.php#L16)). También existe `/api/ping` ([`routes/api.php`](../routes/api.php#L36)), que intenta conectarse a MongoDB y devuelve el mensaje de la excepción si falla. | Usar `/up` como healthcheck de vida. Revisar `/api/ping` antes de exponerlo como comprobación pública de disponibilidad de MongoDB; no devolver detalles internos de excepciones. |
| `APP_DEBUG=false` para producción | ❌ Falta | El ejemplo tiene `APP_DEBUG=true` ([`.env.example`](../.env.example#L4)); `config/app.php` usa ese valor si está presente ([`config/app.php`](../config/app.php#L42)). No hay un ejemplo separado de producción. | Fijar `APP_DEBUG=false` explícitamente en Railway. |
| `APP_ENV=production` documentado | ❌ Falta | El ejemplo indica `APP_ENV=local` ([`.env.example`](../.env.example#L2)) y el [README](../README.md) no documenta Railway ni un entorno de producción. | Configurar `APP_ENV=production` en Railway y documentar el proceso de despliegue. |
| MongoDB configurable por entorno | ⚠️ Parcial | La conexión Mongo usa `DB_URI` y `DB_DATABASE` ([`config/database.php`](../config/database.php#L47)); no hay URI fija en el código. El ejemplo activa SQLite y omite `DB_URI` ([`.env.example`](../.env.example#L23)). | Configurar `DB_CONNECTION=mongodb`, `DB_URI` y `DB_DATABASE` en Railway; comprobar credenciales, conectividad y permisos desde el servicio desplegado. |
| Comando de build de producción | ❌ Falta | No se documenta `composer install --no-dev --optimize-autoloader`. El script `setup` instala dependencias, ejecuta migraciones con `--force` y compila assets ([`composer.json`](../composer.json#L39)). | Definir un build de producción explícito; no usar `composer setup` como build automático de Railway. |
| Depuraciones olvidadas (`dd`, `dump`, `var_dump`, `console.log`) | ✅ OK | Búsqueda en el código de aplicación, rutas, vistas y recursos versionados: no se encontraron coincidencias de esas llamadas. | Repetir el escaneo antes de publicar cambios posteriores. |
| Logs fuera de Git | ✅ OK | [`.gitignore`](../storage/logs/.gitignore) excluye los archivos generados en `storage/logs`. | Conservar la exclusión y revisar los logs desde la plataforma, sin subirlos al repositorio. |
| Instrucciones de despliegue Railway en README | ❌ Falta | El [README](../README.md) explica que se ejecute `schedule:run` cada minuto, pero no documenta Railway, variables, build, start command ni migraciones de producción ([`README.md`](../README.md#L31)). | Añadir instrucciones de build, start, migraciones y cron/healthcheck cuando se prepare el despliegue. |
| Tests locales antes del deploy | ❌ No aprobado | `php artisan test`: **60 pasaron, 32 se omitieron y 2 fallaron**. Un fallo necesita la extensión GD; otro ocurre en `ExampleTest`, que usa SQLite en memoria aunque el flujo consultado requiere MongoDB. Varios tests de integración se omiten si no está configurado `MONGODB_TEST_URI` (por ejemplo, [`ComentarioTest.php`](../tests/Feature/ComentarioTest.php#L22)). | Preparar un entorno de prueba con GD y una base MongoDB exclusiva para tests; corregir la incompatibilidad de `ExampleTest` y volver a ejecutar la suite. No apuntar los tests a la base productiva. |

### Observaciones sobre build y caché

- `composer.json` requiere PHP `^8.2` ([`composer.json`](../composer.json#L9)); verificar que el runtime de Railway cumpla esa versión y tenga las extensiones requeridas por MongoDB.
- El repositorio contiene rutas con closures en `routes/api.php` y `routes/web.php`; `route:cache` no debe incorporarse al build hasta refactorizarlas.
- Si se publican también las vistas web, compilar los assets con `npm ci` y `npm run build`. El script `setup` actual no es un sustituto seguro para el proceso de producción.

## 2. Pendientes que afectan al deploy

### Bloqueantes para que el servicio arranque correctamente

1. **Comando de inicio que use el puerto de Railway**
   - **Estado:** ausente en la configuración versionada; no hay Dockerfile, Procfile ni archivo de Railway.
   - **Falta:** configurar un start command que enlace a todas las interfaces y use el `PORT` inyectado por Railway. Por ejemplo, como comando de inicio en Railway: `php artisan serve --host=0.0.0.0 --port=$PORT`.
   - **Verificación:** confirmar en el panel el comando, el puerto asignado y que Railway marque el servicio como saludable.

2. **Variables de producción y conexión MongoDB**
   - **Estado:** el ejemplo está preparado para desarrollo local con SQLite; la conexión Mongo está definida por variables de entorno.
   - **Falta:** en Railway establecer al menos `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` única, `APP_URL`, `DB_CONNECTION=mongodb`, `DB_URI` y `DB_DATABASE`. No pegar secretos en Git ni en el documento.
   - **Verificación:** revisar en el panel que la URI y la base correspondan a producción, que la red permita la conexión desde Railway y que el usuario tenga solo los permisos necesarios. La disponibilidad de MongoDB y su allowlist requieren verificación manual.

3. **Migraciones sobre la base correcta**
   - **Estado:** el script `setup` corre `migrate --force`, pero también instala dependencias y assets; no hay un proceso de release documentado para Railway.
   - **Falta:** después de verificar variables y conexión, ejecutar `php artisan migrate --force` como release/deploy command, con respaldo de la base y revisión del resultado.
   - **Precaución:** no ejecutar `php artisan db:seed` indiscriminadamente en producción. `DatabaseSeeder` llama a `UserSeeder` ([`DatabaseSeeder.php`](../database/seeders/DatabaseSeeder.php#L11)), que contiene cuentas y contraseñas de demostración ([`UserSeeder.php`](../database/seeders/UserSeeder.php#L13)); no se reproducen aquí sus valores.

### Riesgos altos antes de abrir el servicio al público

4. **Pruebas no completamente verdes**
   - **Estado:** 60 aprobadas, 32 omitidas y 2 fallidas en la ejecución auditada.
   - **Falta:** resolver la prueba incompatible con `:memory:`/MongoDB, instalar GD donde corresponda y configurar `MONGODB_TEST_URI` contra una base aislada. Los tests omitidos no equivalen a tests aprobados.
   - **Puede bloquear la entrega:** sí, como criterio de calidad; no se puede afirmar que la suite completa pase hasta repetirla.

5. **Límite de solicitudes para login y registro**
   - **Estado:** no se encontró `throttle` explícito en las rutas públicas de login y registro ([`routes/api.php`](../routes/api.php#L56)); sí hay límites para cambios de correo/contraseña y asistencia.
   - **Falta:** aplicar límites adecuados a autenticación y otros endpoints sensibles, con pruebas de respuesta y configuración por IP/usuario.
   - **Puede documentarse después:** no es una dependencia de arranque, pero se recomienda atender antes de un lanzamiento público.

6. **Errores internos en `/api/ping`**
   - **Estado:** el endpoint captura errores de conexión y los agrega a la respuesta.
   - **Falta:** evitar devolver mensajes internos de MongoDB; registrar el detalle en logs y devolver una respuesta pública genérica.
   - **Puede documentarse después:** no impide arrancar, pero debe resolverse antes de exponer ese endpoint públicamente.

7. **CORS si hay clientes web en otros orígenes**
   - **Estado:** no existe `config/cors.php` ni una lista explícita de orígenes permitidos en el repositorio.
   - **Falta:** definir orígenes, rutas, métodos y credenciales requeridos para la web desplegada. CORS normalmente no aplica a solicitudes nativas de la app móvil, pero sí puede afectar a clientes web.
   - **Verificación:** conocer los dominios reales de frontend y API. No se puede deducir desde el código si un proxy/plataforma añade políticas externas.

### Variables de entorno operativamente relevantes

La comparación de las variables leídas por la configuración con `.env.example` encontró opciones de drivers que no están listadas, muchas de ellas opcionales. Las siguientes requieren atención:

| Variable / grupo | Estado en el repositorio | Uso |
|---|---|---|
| `DB_URI` | Ausente en `.env.example`; se lee en [`config/database.php`](../config/database.php#L47). | Necesaria para proporcionar explícitamente la URI de MongoDB en producción. |
| `DB_DATABASE` | Está comentada en el ejemplo; MongoDB usa un fallback en [`config/database.php`](../config/database.php#L48). | Definir el nombre de base de producción explícitamente. |
| `MONGODB_TEST_URI` | No está en el ejemplo; los tests de integración la consultan, por ejemplo [`ComentarioTest.php`](../tests/Feature/ComentarioTest.php#L22). | Solo pruebas; usar una base separada y nunca la base productiva. |
| `REDIS_URL` y opciones Redis | La configuración las admite ([`config/database.php`](../config/database.php#L140)), pero el ejemplo activa drivers `database` para cola y caché. | Solo si se decide usar Redis; requiere configurar el servicio/proveedor y confirmar el cliente PHP disponible. |
| `PORT` | No lo consume Laravel directamente en la configuración revisada. | Railway lo inyecta; el start command debe expandirlo. |
| `CORS_ALLOWED_ORIGINS` | No está implementada como variable de configuración. | No basta con agregarla al panel: primero se necesita configurar CORS en la aplicación. |
| Variables de correo del proveedor | El ejemplo usa `MAIL_MAILER=log` ([`.env.example`](../.env.example#L50)). | Solo cuando se implemente y configure un proveedor SMTP/API; actualmente no hay envío transaccional en el código. |

No se consideran faltantes bloqueantes las variables opcionales de servicios alternativos que no están seleccionados (por ejemplo, drivers de almacenamiento, correo o cola que no se usan).

## 3. Pendientes que no bloquean el arranque, pero deben documentarse

### Recuperación de contraseña

- **Estado:** no se encontraron rutas `password/email` o `password/reset`, controlador/flujo de recuperación ni vistas de correo. `config/auth.php` tiene la configuración genérica del broker, pero eso por sí solo no implementa el flujo.
- **Siguiente iteración:** agregar endpoints compatibles con la app, emisión y expiración segura de tokens, vistas/mensajes, almacenamiento compatible con MongoDB, límites de solicitudes y pruebas de abuso y expiración. No se recomienda habilitar un broker sin verificar su almacenamiento y compatibilidad con la conexión MongoDB.

### Políticas de privacidad y términos

- **Estado:** no se encontraron documentos, rutas ni enlaces a términos o aviso legal. El formulario incluye una opción para mantener privado el perfil ([`profile-fields.blade.php`](../resources/views/auth/profile-fields.blade.php#L104)); esa preferencia de privacidad no sustituye una política legal ni el consentimiento informado.
- **Siguiente iteración:** redactar y publicar los documentos aplicables, enlazarlos desde registro/perfil y registrar la versión y aceptación cuando proceda, con especial cuidado para cuentas de menores.

### Correos automáticos

- **Estado:** `MAIL_MAILER=log` está activo en el ejemplo; no se encontraron llamadas a `Mail::`, clases de correo ni flujos transaccionales.
- **Siguiente iteración:** elegir proveedor SMTP/API, configurar remitente y secretos en Railway, crear plantillas y eventos necesarios (por ejemplo, recuperación/confirmación) y probar entrega y manejo de errores.

### Redis, colas y jobs

- **Estado:** no se encontró `app/Jobs` ni jobs que implementen `ShouldQueue`. `QUEUE_CONNECTION` y `CACHE_STORE` usan `database` por defecto ([`config/queue.php`](../config/queue.php#L16), [`config/cache.php`](../config/cache.php#L18)); Redis es una alternativa de configuración, no un requisito activo.
- **Verificación:** no se puede determinar desde el repositorio si existe un servicio Redis en Railway. Requiere verificación manual en su panel.
- **Siguiente iteración:** añadir Redis solo si una función lo necesita; configurar `REDIS_URL` y el cliente de PHP compatible. Si se agregan jobs, definir la conexión, un worker de Railway, reintentos, fallos y monitoreo.

### Push y scheduler

- **Estado:** existen notificaciones Web Push y las variables VAPID están contempladas pero vacías en `.env.example` ([`.env.example`](../.env.example#L67)). El comando de recordatorios está programado cada minuto ([`routes/console.php`](../routes/console.php#L43)); el README indica que `schedule:run` debe ejecutarse cada minuto, pero no define un cron de Railway.
- **Siguiente iteración:** generar y configurar VAPID en el entorno de producción y crear/verificar un servicio cron que ejecute `php artisan schedule:run` cada minuto. El código revisado corresponde a Web Push; no demuestra integración con push nativo de Expo.
- **Verificación:** existencia del cron y valores VAPID en Railway requiere revisión manual.

### Auditoría

- **Estado:** implementada mediante [`AuditoriaService.php`](../app/Services/AuditoriaService.php#L10), expuesta con controles administrativos y llamada desde varios flujos. Hay pruebas que comprueban acciones auditadas, como comentarios y eliminación de artículos.
- **Pendiente:** ampliar la matriz de eventos y pruebas para confirmar qué operaciones críticas quedan registradas y verificar retención/acceso en producción. La cobertura actual no demuestra auditoría exhaustiva de todo cambio.

### Otros puntos observados

- **Build de la web:** el repositorio incluye vistas y assets; si se servirán desde Railway, el build debe ejecutar `npm ci` y `npm run build`. Si se despliega solo la API, confirmar que no se espera servir esas páginas.
- **`composer setup`:** no usarlo como rutina de despliegue; corre migraciones con `--force` y compila assets junto con instalación. Separar build y release permite revisar las migraciones antes de aplicarlas.
- **Caché de rutas:** aplazar `route:cache` hasta eliminar las rutas con closures.
- **Servicios externos:** dominio, MongoDB, Redis, SMTP, VAPID, cron, healthcheck, allowlist de red y variables reales requieren verificación manual en Railway; no son inferibles solo por el código.

## 4. Checklist final antes de Railway

- [ ] Confirmar que `.env` y los secretos no están versionados; no usar credenciales de demostración.
- [ ] Configurar `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` y una `APP_KEY` exclusiva.
- [ ] Configurar `DB_CONNECTION=mongodb`, `DB_URI` y `DB_DATABASE`; comprobar acceso desde Railway y respaldar la base.
- [ ] Confirmar runtime PHP `^8.2`, extensión MongoDB y dependencias de Composer necesarias.
- [ ] Configurar un start command enlazado a `0.0.0.0` que use el `PORT` inyectado por Railway.
- [ ] Ejecutar el build previsto para el alcance del servicio; no usar `composer setup` ni `route:cache` sin revisar sus efectos.
- [ ] Ejecutar `php artisan migrate --force` solo después de comprobar el entorno y la base destino; evitar seeders de demostración.
- [ ] Probar el healthcheck `/up` y una solicitud real a un endpoint que requiera MongoDB; revisar logs sin exponer errores internos.
- [ ] Repetir `php artisan test` con GD y MongoDB de pruebas aislada; investigar todo fallo y todo test omitido relevante.
- [ ] Verificar manualmente en Railway dominios/CORS, servicio cron para scheduler, VAPID y cualquier servicio de correo/Redis que se vaya a usar.
