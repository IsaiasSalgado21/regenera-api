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
