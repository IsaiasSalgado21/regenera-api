# Pendientes post-deploy

Este documento separa las funciones que no se implementan en la preparación
mínima para Railway. No es necesario añadir Redis, correo ni recuperación de
contraseña para que el servicio arranque.

## Recuperación de contraseña

- **Estado actual:** no existe un flujo de recuperación ni rutas para solicitar
  o completar un restablecimiento.
- **Qué falta:** endpoints, tokens con expiración y almacenamiento compatible
  con MongoDB, correo de recuperación, límites de solicitudes y pruebas.
- **Cómo implementarlo:** diseñar el flujo de API para la web y la app, integrar
  un mailer y cubrir expiración, reutilización de tokens y respuestas seguras.

## Políticas de privacidad y términos

- **Estado actual:** no se encontraron documentos legales ni enlaces desde el
  registro o el perfil. La preferencia para ocultar el perfil no los sustituye.
- **Qué falta:** textos aprobados, rutas o páginas accesibles y enlaces visibles
  en los puntos donde se solicitan datos.
- **Cómo implementarlo:** obtener la redacción legal correspondiente, publicarla
  y registrar la versión y aceptación cuando proceda, con especial cuidado para
  cuentas de menores.

## Correos automáticos

- **Estado actual:** `.env.example` usa `MAIL_MAILER=log`; no se encontraron
  flujos transaccionales de correo.
- **Qué falta:** proveedor SMTP/API, credenciales de producción, remitente,
  plantillas y eventos que disparen los mensajes.
- **Cómo implementarlo:** elegir un proveedor, guardar sus credenciales en
  Railway y probar entrega, fallos y reintentos antes de activar mensajes a
  usuarios.

## Redis y colas

- **Estado actual:** `QUEUE_CONNECTION=database`; no se encontraron jobs en
  `app/Jobs` ni procesos que requieran Redis. Redis es opcional, no requisito
  para el primer despliegue.
- **Qué falta:** una necesidad concreta de cola, el servicio Redis y la
  configuración/cliente PHP correspondiente.
- **Cómo implementarlo:** añadir jobs solo cuando una tarea lo requiera; luego
  configurar Redis y un worker en Railway, incluyendo reintentos y monitoreo.
  La existencia de un servicio Redis en Railway requiere verificación manual.

## Push Web Push / VAPID

- **Estado actual:** el proyecto contempla Web Push, pero
  `WEBPUSH_SUBJECT`, `WEBPUSH_PUBLIC_KEY` y `WEBPUSH_PRIVATE_KEY` están vacías
  en `.env.example`.
- **Qué falta:** generar las claves de producción y cargarlas como variables
  privadas en Railway. El código revisado no demuestra push nativo de Expo.
- **Cómo implementarlo:** generar claves VAPID, configurar las tres variables
  en Railway y probar suscripciones y entregas mediante HTTPS. Nunca publicar
  la clave privada.

## Cron para `schedule:run`

- **Estado actual:** hay recordatorios programados cada minuto en la aplicación
  y el README indica que debe ejecutarse `schedule:run` cada minuto; no se
  documenta un servicio cron de Railway.
- **Qué falta:** configurar y verificar un servicio que invoque el scheduler
  una vez por minuto.
- **Cómo implementarlo:** añadir un cron/servicio en Railway que ejecute
  `php artisan schedule:run` y confirmar en logs que las tareas se ejecutan sin
  duplicarse.

## Tests no verdes

- **Estado actual:** la última ejecución de `php artisan test` tuvo 62 aprobados,
  2 fallos y 32 tests omitidos: un fallo requiere GD; `ExampleTest` usa SQLite
  en memoria en un flujo que consulta MongoDB; los omitidos requieren
  `MONGODB_TEST_URI`.
- **Qué falta:** corregir el entorno/prueba de GD, alinear el test de ejemplo con
  la base usada y ejecutar las integraciones con una base MongoDB exclusiva
  para pruebas.
- **Cómo implementarlo:** habilitar GD en el entorno de pruebas, configurar
  `MONGODB_TEST_URI` apuntando a una base separada de producción, corregir
  `ExampleTest` y volver a correr la suite completa. No apuntar tests a la base
  productiva.

## CORS para frontend en otro dominio

- **Estado actual:** no existe `config/cors.php` ni una lista explícita de
  orígenes permitidos en el repositorio. Puede no afectar a clientes nativos,
  pero sí a la web servida desde otro dominio.
- **Qué falta:** conocer los dominios públicos y definir las rutas, métodos,
  encabezados y credenciales requeridos.
- **Cómo implementarlo:** añadir y revisar la configuración CORS en una
  iteración posterior con una allowlist de los orígenes reales; no habilitar
  orígenes arbitrarios en producción.
