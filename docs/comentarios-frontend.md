# Comentarios de voluntariados: frontend y QA

## Arquitectura

El frontend usa Blade y JavaScript vanilla con Vite, no Vue ni React. La sección vive en `resources/js/voluntariado-comments.js`, importada por `resources/js/voluntariado-detail.js`, y se monta en `resources/views/voluntariados/show.blade.php`. Sus estilos están en `public/css/voluntariados.css`.

El estado es local al módulo. Después de crear, responder, ocultar, publicar o eliminar, la interfaz vuelve a pedir la lista para respetar los permisos y la visibilidad calculados por el servidor; no hace una recarga completa ni una actualización optimista.

La colección MongoDB `comentarios` almacena `voluntariado_id`, `user_id`, `contenido`, `es_privado`, `es_publicado` y timestamps. No hay `parent_id`: las respuestas se guardan como comentarios separados, conservando la privacidad de la duda a la que responden. Los índices definidos son `voluntariado_id`, `user_id` y el compuesto `voluntariado_id + es_publicado`.

La policy permite ver comentarios publicados y públicos, los propios privados, todos al organizador propietario y todos a admins. La lista de API presenta únicamente `autor.nombre` y `autor.rol`; no serializa correo, teléfono ni coordenadas.

## Interfaz, autenticación y permisos

Las peticiones usan Axios, `apiUrl()` y el token Bearer de `sessionStorage` (`regenera_access_token`). El usuario actual se consulta con `GET /api/auth/me`.

- `usuario` (voluntario): crea comentarios y recibe la lista filtrada por la API. Cuando el valor predeterminado es `privado`, el control aparece marcado y deshabilitado.
- `organizador` y `admin`: responden desde cada comentario cuando `puede_moderar` lo permite. Los botones se muestran con `puede_moderar`, `puede_publicar` y `puede_eliminar`.
- La interfaz contempla carga, lista vacía, error y reintento. Los errores 401 invitan a iniciar sesión, los 403 indican falta de permiso, los 422 aparecen junto al campo y los 404 actualizan la lista.
- Tras eliminar se solicita confirmación nativa del navegador; Escape cancela ese diálogo.

### Accesibilidad revisada

- Los campos tienen etiquetas asociadas; el textarea principal vincula el error con `aria-describedby` y los errores se anuncian con `role="alert"`.
- El estado y la lista usan regiones `aria-live`; el nombre del candado es decorativo y el texto comunica “Duda privada”.
- Se añadió un indicador `:focus-visible` uniforme para los controles y enlaces de comentarios, con color adaptado al tema oscuro. Los badges privados/ocultos también tienen colores específicos para tema oscuro.
- Los formularios nativos permiten recorrer con Tab y enviar al activar el botón con Enter. El formulario de respuesta recibe foco al abrirse.

Queda pendiente una revisión manual final con lector de pantalla y medición de contraste en los dispositivos/temas soportados; no se hizo una auditoría automatizada con herramientas externas.

## Endpoints

| Acción | Método y URL | Payload |
| --- | --- | --- |
| Cargar comentarios | `GET /api/voluntariados/{id}/comentarios` | — |
| Crear comentario | `POST /api/voluntariados/{id}/comentarios` | `{ "contenido": "...", "es_privado": false }` |
| Responder | `POST /api/comentarios/{id}/responder` | `{ "contenido": "..." }` |
| Ocultar o mostrar | `PATCH /api/comentarios/{id}/visibilidad` | `{ "es_publicado": false }` |
| Publicar duda privada | `PATCH /api/comentarios/{id}/publicar` | — |
| Eliminar | `DELETE /api/comentarios/{id}` | — |
| Leer configuración | `GET /api/voluntariados/{id}` | — |
| Guardar configuración | `PATCH /api/voluntariados/{id}/config-comentarios` | `{ "comentarios_por_defecto": "publico" }` o `privado` |

Todas las rutas de comentarios requieren `auth:sanctum`. La API del proyecto responde `200` al eliminar y devuelve un mensaje; el frontend acepta esa respuesta.

El formulario público de creación y el editor del dashboard muestran la configuración predeterminada a organizadores/admins. Ambos usan el endpoint dedicado después de guardar el voluntariado, ya que las validaciones de creación/edición general no aceptan actualmente ese campo.

## Datos de demostración

En un entorno no productivo, ejecuta:

```powershell
php artisan db:seed --class=ComentariosDemoSeeder
```

El seeder se protege en `production` y no se incluye en `DatabaseSeeder`. Usa cuentas de voluntarios deterministas, busca primero una actividad publicada (prefiere más inscritos) cuyo propietario sea organizador y conserva su propietario. Si no encuentra una actividad adecuada, crea una actividad demo y un organizador propietario. No cambia la contraseña de un propietario existente.

Los tres usuarios voluntarios que crea/actualiza son:

- `demo.comentarios.voluntario01@regenerabcs.com`
- `demo.comentarios.voluntario02@regenerabcs.com`
- `demo.comentarios.voluntario03@regenerabcs.com`

Contraseña para las tres cuentas: `ComentariosDemo123!`. Si el seeder necesita crear la actividad, el organizador es `demo.comentarios.organizador@regenerabcs.com`, con la misma contraseña. El comando imprime el correo del propietario elegido; cuando reutiliza una actividad preexistente, su contraseña no se modifica y debe usarse la credencial ya configurada para esa cuenta.

La carga contiene 5 dudas (3 privadas/no publicadas y 2 públicas/publicadas), 5 comentarios positivos públicos y publicados, y 2 respuestas (una privada y otra pública): 12 documentos en total. Las claves de búsqueda de `firstOrCreate` hacen que repetir el comando no duplique esos documentos.

Para quitar solo los ejemplos, elimina de `comentarios` los contenidos definidos en las constantes `DUDAS`, `POSITIVOS` y `RESPUESTAS` del seeder. Eliminar toda la colección borra también comentarios ajenos. `php artisan migrate:fresh --seed` es destructivo: úsalo solo en una base desechable y vuelve a ejecutar el comando demo después.

## Pruebas automatizadas

Las pruebas Feature de comentarios crean un nombre de base MongoDB aleatorio y la eliminan al terminar. No usan la base de desarrollo. Configura `MONGODB_TEST_URI` para apuntar a un servidor/base Mongo exclusiva de pruebas:

```powershell
$env:MONGODB_TEST_URI = '<URI MongoDB exclusiva para pruebas>'
php artisan test --filter=ComentarioTest
```

Sin `MONGODB_TEST_URI`, PHPUnit omite esos casos. Para compilar los assets:

```powershell
npm run build
```

No hay configuración Cypress/Playwright ni pruebas E2E en este proyecto; no se instaló una dependencia nueva.

## Pruebas manuales y cierre de E2E

Usa dos cuentas distintas de voluntario y el propietario de la actividad indicado por el seeder:

1. Inicia sesión como voluntario 01 y crea un comentario público. Confirma que aparece para voluntarios 01 y 02.
2. Crea una duda privada; confirma que la ve el autor y el propietario, pero no voluntario 02.
3. Como propietario organizador, responde a la duda privada; confirma que la respuesta también es privada y la ve el autor.
4. Publica la duda resuelta y confirma que un segundo voluntario ahora la ve.
5. Oculta un comentario público; confirma que desaparece para voluntarios y permanece visible para el propietario.
6. Elimina un comentario y confirma que ya no aparece ni para el autor ni para el propietario.
7. Crea un voluntariado con `comentarios_por_defecto = privado`; confirma en el detalle que privacidad está marcada y deshabilitada.
8. Cambia la configuración desde edición y confirma que el formulario del detalle refleja el nuevo valor.
9. Comprueba sesión expirada, reintento, estado vacío, error 403 y validación 422; repite con tema claro/oscuro y navega con teclado (Tab, Enter y Escape en la confirmación).

Para automatizar estos recorridos en una iteración futura, primero debe elegirse y aprobarse un runner E2E, preparar un entorno web/API y Mongo exclusivo de pruebas, y definir cómo iniciar sesión con las cuentas demo sin reutilizar credenciales de producción.

## Auditoría de rendimiento y seguridad

- Los tres índices de la migración cubren el filtro por voluntariado/publicación y la rama por autor privado. El servicio ordena por `created_at`; el índice actual no incluye ese campo y una operación `$or` podría requerir ordenamiento adicional. El uso real y el coste deben confirmarse en el Mongo de pruebas con `explain("executionStats")`, por ejemplo sobre el filtro por `voluntariado_id` y `es_publicado`; no se pudo medir un plan real en esta inspección.
- No se encontró un `throttle` explícito en las rutas de comentarios (sí hay límites en otras rutas). Se recomienda acordar e implementar límites por usuario/IP para crear y responder antes de exponerlos a tráfico alto.
- El controlador crea el recurso de respuesta con nombre/rol, sin correo/teléfono/coordenadas. Los tests ahora verifican que no aparezcan esos campos en la respuesta.
- Las rutas requieren autenticación Sanctum y las acciones sensibles pasan por policy. El seeder demo retorna con advertencia en producción.

## Siguiente iteración recomendada

Automatizar E2E tras elegir runner, medir la consulta Mongo con datos representativos, finalizar pruebas de accesibilidad con tecnologías de asistencia y evaluar paginación si el volumen de comentarios lo requiere. También podrían añadirse notificaciones al autor cuando el organizador responde y reportes/moderación comunitaria.
