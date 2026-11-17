<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Notificaciones push

Las notificaciones internas se conservan en el centro de notificaciones. Para habilitar también las push del navegador:

1. Genera las claves VAPID con `php artisan notifications:vapid-keys`.
2. Configura `WEBPUSH_SUBJECT` (por ejemplo, `mailto:admin@regenerabcs.com`), `WEBPUSH_PUBLIC_KEY` y `WEBPUSH_PRIVATE_KEY` en el entorno del servidor. No compartas ni subas la clave privada.
3. En producción, sirve el sitio mediante HTTPS. En desarrollo, los navegadores permiten el service worker en `localhost`.
4. Programa `php artisan schedule:run` cada minuto en el servidor. En desarrollo puedes usar `php artisan schedule:work` para que se envíen los recordatorios programados.

Cada usuario activa las push desde su perfil; el permiso del navegador solo se solicita al pulsar el botón de activación. Los usuarios no administradores pueden elegir no recibir recordatorios, recibirlos un día y una hora antes, o recibir uno diario antes del inicio.

## Símbolos de la interfaz

Los pictogramas personalizados se encuentran en `public/img/simbolos/`. Las
vistas usan `asset()` y los scripts toman la ruta base del atributo
`data-symbols-base` del layout, para funcionar también en instalaciones bajo
un subdirectorio. La clase `ui-symbol` mantiene su tamaño y contraste en ambos
temas; los marcadores del mapa conservan su fondo y leyenda.
En el detalle de voluntariado se reconocen materiales habituales por su
nombre; los materiales personalizados sin símbolo conservan una viñeta.
En modo oscuro los símbolos se muestran en blanco sobre fondo transparente.

## Ubicaciones

Las tarjetas y detalles de voluntariados, las fichas de especies, los lugares del panel y el mapa
ambiental enlazan a Google Maps cuando hay coordenadas válidas. Las fichas
también permiten abrir el registro correspondiente en `/mapa` mediante los
parámetros `voluntariado`, `lugar` o `especie`. Los lugares archivados no
aparecen en el mapa público. Para proteger especies sensibles, sus zonas
aproximadas solo se abren en el mapa de Regenera, no en Google Maps; los
lugares relacionados se enlazan por separado.

## Datos de la cuenta

Desde el perfil se puede actualizar el nombre y, proporcionando la contraseña
actual, cambiar el correo principal o la contraseña. El cambio de correo no
envía una verificación. Al cambiar la contraseña se revocan los demás tokens de
acceso, manteniendo el token actual.
En el panel de una actividad, los organizadores no reciben los correos de
otras personas inscritas; los administradores conservan acceso a ellos.

El registro requiere fecha de nacimiento, ciudad, género y correo secundario
distinto del principal. La edad mínima es 13 años. De los 13 a los 17 años se
requieren los datos de un tutor adulto y una declaración de su autorización;
no se verifica ni vincula una cuenta del tutor. Las cuentas anteriores deben
completar estos datos antes de usar las funciones autenticadas. Si una cuenta
existente declara menos de 13 años, se bloquea y debe contactar a
Regenerabcs@mail.com.

Los menores tienen privacidad obligatoria: su perfil público muestra únicamente
nombre, avatar y medallas. Al cumplir 18 años la privacidad permanece activada
hasta que la persona decida cambiarla. Con privacidad desactivada, también
pueden mostrarse la descripción y, para organizadores, organización y hasta
cinco enlaces sociales. Los correos, teléfono, ciudad, género, fecha de
nacimiento y datos del tutor son privados. El titular solicita cambios de fecha
de nacimiento y un administrador los aprueba o rechaza. Los administradores
pueden editar datos y establecer una contraseña nueva; hacerlo revoca los
tokens de la cuenta afectada. El correo secundario no habilita recuperación
de acceso. Tras registrarse se ofrece activar push desde el perfil; depende
del permiso del navegador y de la configuración del servidor.

## Progreso de medallas

En el editor administrativo de cuestionarios, el identificador puede ser nuevo
o elegirse del catálogo existente. El título genera una sugerencia normalizada
con un sufijo libre si ya existe. Elegir uno existente y confirmar el guardado
crea una nueva versión y desactiva la anterior; no crea un cuestionario duplicado.
El identificador de una versión existente permanece fijo; al crear se puede
volver a generar desde el título. El editor de medallas ofrece iconos compatibles
con el perfil y conserva los identificadores antiguos. Las medallas se gestionan
por separado en `/admin/medallas` y se seleccionan desde el cuestionario. Las
medallas anteriores se leen de sus cuestionarios sin modificar sus versiones.
Para desplegar el catálogo ejecuta `php artisan migrate` y crea el índice único
de identificadores antes de permitir su administración.
La medalla se otorga al aprobar el cuestionario, además de los requisitos de
asistencia o verificación que correspondan. Una medalla desactivada no se
otorga en nuevos intentos; las ya obtenidas y sus relaciones se conservan.
Las medallas sin vínculos ni logros pueden eliminarse.

Las listas de cuestionarios del editor, la sección de gamificación y aprendizaje
permiten buscar por nombre y navegar los resultados de seis en seis. El resumen
administrativo incluye la configuración y los estados de intentos por
cuestionario; los títulos de intentos históricos corresponden a su versión.

Las preguntas admiten una respuesta, varias respuestas, verdadero o falso y
respuesta corta. Cada pregunta tiene un peso de 1 a 5; las versiones anteriores
sin peso conservan peso 1. Para varias respuestas se aplica el crédito parcial
`max(0, (aciertos - errores) / respuestas correctas)`. En respuestas cortas se
ignoran mayúsculas, acentos y espacios adicionales. Los intentos abiertos se
reanudan con la versión con la que se iniciaron.

`GET /api/gamificacion/progreso` requiere autenticación y conserva `medallas`.
También devuelve `medallasObtenidas`, `medallasBloqueadas` y `siguienteMedalla`.
Cada medalla incluye sus `requisitos` y `disponibleParaRol`; el perfil muestra
las obtenidas recientes y las pendientes del rol, usando la versión activa más
reciente de cada cuestionario y conservando los logros de versiones inactivas.
El historial de voluntariados utiliza las
inscripciones y asistencias del usuario autenticado: solo indica asistencia
confirmada cuando existe ese registro, no por el estado de la inscripción.
La siguiente medalla respeta el rol y prioriza seguridad, manglar, economía
circular y liderazgo; las demás siguen el orden del catálogo por cuestionario.
`siguienteMedalla.progreso` mide únicamente la aprobación binaria del
cuestionario (0/1), sin usar su puntuación. `requisitos` indica por separado
la asistencia o verificación necesarias. Si un intento tiene un estado inválido
y no hay otro aprobado, el progreso es `null` y `observacion` explica el dato
faltante. Cuando no hay medallas pendientes para el rol, `siguienteMedalla` es
`null`.

## Registro de impacto ambiental

Al concluir una actividad, su organizador propietario o un administrador puede
registrar resultados con `POST /api/admin/voluntariados/{id}/impactos`. La API
valida la propiedad y el horario publicado y fija las unidades por métrica:

- `residuos_recolectados`: kg
- `plantas_sembradas`: plantas
- `superficie_restaurada`: m²
- `costa_limpiada`: km

La cantidad debe ser mayor que cero y tener hasta dos decimales; la nota es
opcional y admite hasta 500 caracteres. Los resultados públicos se consultan
por actividad en `GET /api/voluntariados/{id}/impactos` y de forma acumulada en
`GET /api/impactos/resumen`. Cada registro conserva su trazabilidad y queda
anotado en auditoría. El detalle web de cada voluntariado y la app muestran el
total por métrica y una gráfica de los seis registros más recientes de esa
métrica, con escala independiente para no comparar unidades distintas.

Las rutas de gestión bajo `/api/admin/voluntariados` admiten organizadores
autenticados y administradores; la API limita las operaciones a las actividades
propias del organizador. La respuesta incluye `codigoAsistenciaActivo` y el
estado del QR se puede cambiar con
`PATCH /api/admin/voluntariados/{id}/codigo-asistencia`. Los registros antiguos
sin este campo se consideran activos por compatibilidad.

El formulario de creación consulta `GET /api/lugares` con el token del usuario
para incluir sus lugares administrados aunque no tengan coordenadas de mapa.
`GET /api/voluntariados/categorias` devuelve las categorías recomendadas y las
que ya se han utilizado, para que las nuevas categorías puedan reutilizarse.

## Comentarios en voluntariados

Todas las rutas de comentarios requieren `auth:sanctum`. La lista devuelve
comentarios públicos publicados y las dudas privadas del autor; el organizador
propietario y los administradores pueden consultar todos los comentarios del
voluntariado. Las respuestas se guardan en la misma colección, sin relación
padre: una respuesta a una duda privada permanece privada y no publicada.

- `GET /api/voluntariados/{voluntariado}/comentarios`: lista en orden
  cronológico ascendente.
- `POST /api/voluntariados/{voluntariado}/comentarios`: recibe
  `{"contenido":"¿Qué materiales necesito?","es_privado":true}`. Si
  `comentarios_por_defecto` es `privado`, las publicaciones de voluntarios
  siempre se guardan como privadas. No se requiere inscripción previa.
- `POST /api/comentarios/{comentario}/responder`: el organizador propietario o
  un administrador responde con `{"contenido":"Trae guantes y agua."}`.
- `PATCH /api/comentarios/{comentario}/visibilidad`: el organizador propietario
  o un administrador cambia `es_publicado` en comentarios no privados.
- `PATCH /api/comentarios/{comentario}/publicar`: publica una duda privada,
  convirtiéndola en pública y publicada.
- `DELETE /api/comentarios/{comentario}`: permite borrar al autor, al
  organizador propietario o a un administrador. El borrado es físico porque
  los comentarios no usan soft deletes.
- `PATCH /api/voluntariados/{voluntariado}/config-comentarios`: cambia
  `comentarios_por_defecto` a `publico` o `privado`; solo el organizador
  propietario o un administrador.

Las respuestas de creación, lectura y actualización usan `data` y `message`;
la eliminación devuelve `message`. Cada comentario incluye `id`, `contenido`,
`es_privado`, `es_publicado`, `created_at`, `autor` (solo `nombre` y `rol`) y
los permisos calculados `puede_moderar`, `puede_publicar` y `puede_eliminar`.
La API no devuelve correo, teléfono ni coordenadas en estos recursos. La
validación responde `422`; una acción sin permiso responde `403`, una ruta
inexistente `404` y una petición sin sesión `401`. Las acciones de moderación,
publicación, eliminación y configuración quedan registradas en auditoría.

La guía de componentes, carga de datos demo, pruebas manuales y auditorías de
accesibilidad/rendimiento/seguridad está en
[docs/comentarios-frontend.md](docs/comentarios-frontend.md).

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
