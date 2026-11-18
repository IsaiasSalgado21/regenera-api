# Diagnóstico y plan de paridad: web Regenera BCS → voluntariado-mvp

## Alcance y fuentes revisadas

Este análisis compara el frontend web que vive dentro de `regenera-api` con la
aplicación móvil `voluntariado-mvp`. Se confirmó que no hay un proyecto o
directorio `web` separado disponible en el workspace; el usuario confirmó que
se use como referencia el frontend integrado de Laravel.

Fuentes principales revisadas:

- `regenera-api/README.md`, `routes/web.php`, `routes/api.php`,
  `resources/views/voluntariados*`, `resources/views/dashboard/index.blade.php`,
  `resources/js/voluntariado-detail.js`,
  `resources/js/voluntariado-comments.js`,
  `resources/js/personal-volunteers.js`,
  `resources/js/dashboard/dashboard-api.js` y
  `resources/js/dashboard/dashboard.js`.
- `voluntariado-mvp/README.md`, `package.json`, `src/app/`,
  `src/features/` y las pruebas existentes en `tests/*.test.mjs`.
- Las rutas y controladores Laravel relacionados, incluidos
  `VoluntariadoPageController`, `VoluntariadoController`, `MapApiController` y
  `routes/api.php`, para contrastar cada llamada y sus contratos.

## Resumen ejecutivo

`voluntariado-mvp` no es una PWA ni una app Flutter: es una aplicación Expo
Router sobre React Native 0.88, React 19 y TypeScript, orientada a iOS y Android.
React Native Web está instalado para desarrollo web, pero el README aclara que
esa salida no es un destino de despliegue. El estado global de sesión usa
React Context; el cliente HTTP es `fetch` con validación de respuestas y
errores tipados.

El MVP ya implementa una parte amplia de los flujos: autenticación, lista y
filtros de voluntariados, inscripción/cancelación, notificaciones, panel de
organizador, creación/edición, participantes, asistencia, registro de impacto,
administración de usuarios, moderación, auditoría y resúmenes de impacto.
Conviene completar estas piezas existentes, no reescribirlas.

### Estado de implementación

Las fases 1 a 5 ya están implementadas en `voluntariado-mvp`: detalle e
inscripción, comentarios anidados y privacidad, operación del organizador con
asistencia, CRUD administrativo, bitácora de impacto, estadísticas de
gamificación y preferencias de recordatorios. El siguiente paso es el cierre QA
en iOS/Android y un development build actualizado, que se reservó para el final.

Las brechas identificadas durante el diagnóstico inicial fueron:

1. El detalle móvil carga el voluntariado buscando dentro de toda la lista del
   mapa, en vez de usar `GET /api/voluntariados/{id}`. Por ello, depende de que
   la actividad sea publicable en el mapa y no presenta horario, requisitos ni
   materiales aunque el endpoint de detalle existente devuelve el modelo.
2. No hay integración de comentarios en el MVP: no se encontraron pantallas,
   servicios ni llamadas a endpoints de comentarios en `src/`.
3. La edición móvil no presenta `comentarios_por_defecto`, pese a que la API
   ya ofrece el endpoint dedicado.
4. Los módulos de administración móvil ya cubren cuentas, revisión de contenido
   y auditoría, pero su alcance no equivale a todo el CRUD de web para lugares,
   flora/fauna y contenido.

## Inventario del frontend web

El frontend web es Laravel Blade más JavaScript vanilla compilado con Vite;
no es una SPA de Vue o React. Las páginas Blade se sirven desde
`routes/web.php`, y consumen la API Laravel donde corresponde. El dashboard
comparte la vista `resources/views/dashboard/index.blade.php` y adapta sus
secciones al rol.

| Funcionalidad | Archivos principales | Endpoints de API | Roles | Estado en web |
|---|---|---|---|---|
| Explorar voluntariados, buscar, filtrar y paginar | `resources/views/voluntariados.blade.php`, `resources/js/personal-volunteers.js`, `VoluntariadoPageController` | `GET /api/voluntariados`; la página también prepara parte de la lista desde MongoDB | Público; voluntario, organizador y admin | Completa |
| Ver detalle de actividad, ubicación, imágenes e impacto | `resources/views/voluntariados/show.blade.php`, `resources/js/voluntariado-detail.js`, `VoluntariadoPageController` | `GET /api/voluntariados/{id}`, `GET /api/voluntariados/{id}/impactos`, `GET /api/auth/me` | Público; acciones autenticadas por rol | Completa |
| Inscribirse y cancelar participación | Vista de detalle y `resources/js/voluntariado-detail.js`; lista personal en `personal-volunteers.js` | `GET /api/inscripciones/voluntariado/{id}/mia`, `POST /api/inscripciones`, `POST /api/inscripciones/{id}/cancelar`, `GET /api/inscripciones` | Voluntario; organizadores según reglas de API | Completa |
| Comentar y responder, privacidad, moderación y configuración predeterminada | `resources/views/voluntariados/show.blade.php`, `resources/js/voluntariado-comments.js`, `public/css/voluntariados.css` | `GET/POST /api/voluntariados/{id}/comentarios`, `POST /api/comentarios/{id}/responder`, `PATCH /api/comentarios/{id}/visibilidad`, `PATCH /api/comentarios/{id}/publicar`, `DELETE /api/comentarios/{id}`, `PATCH /api/voluntariados/{id}/config-comentarios` | Voluntario, organizador propietario y admin | Completa; las respuestas se muestran anidadas |
| Crear y editar voluntariados | `resources/views/voluntariados/crear.blade.php`, `resources/views/dashboard/index.blade.php`, `resources/js/dashboard/dashboard.js`, `resources/js/dashboard/dashboard-api.js` | `POST /api/voluntariados`, `POST/PUT /api/admin/voluntariados`, `PATCH /api/voluntariados/{id}/config-comentarios`, `GET /api/lugares`, `GET /api/voluntariados/categorias` | Organizador; admin puede administrar organizadores/actividades | Completa |
| Panel de organizador: actividades, inscritos y métricas | Dashboard compartido, `resources/js/dashboard/dashboard.js`, `resources/js/voluntariado-detail.js` | `GET /api/admin/voluntariados`, `GET /api/admin/voluntariados/{id}/dashboard`, `GET /api/organizador/bitacora-impacto` | Organizador propietario; admin | Completa |
| Confirmar asistencia, QR y solicitudes de corrección | `resources/views/asistencia/scan.blade.php`, `resources/js/asistencia.js`, `resources/js/voluntariado-detail.js`, dashboard | `POST /api/asistencias`, `POST /api/asistencias/solicitudes`, `PATCH /api/asistencias/solicitudes/{id}/responder`, `PATCH /api/admin/voluntariados/{id}/codigo-asistencia` | Voluntario, organizador y admin según acción | Completa |
| Registrar y consultar impacto | Detalle, dashboard y `resources/js/dashboard/dashboard.js` | `GET /api/voluntariados/{id}/impactos`, `GET /api/impactos/resumen`, `POST /api/admin/voluntariados/{id}/impactos`, `GET /api/admin/bitacora-impacto`, `GET /api/organizador/bitacora-impacto` | Público para consulta; organizador propietario/admin para registrar | Completa |
| CRUD y revisión de lugares | Dashboard compartido, `resources/js/dashboard/dashboard.js`, `resources/js/propose-place.js` | `GET/POST /api/lugares`, `PUT /api/lugares/{id}`, `GET /api/admin/lugares`, `PATCH /api/admin/lugares/{id}/aprobar`, `/rechazar`, `/publicar`, `/archivar` | Organizador y admin con permisos distintos | Completa |
| Gestión y moderación de flora/fauna | `resources/views/flora*`, `resources/views/dashboard/index.blade.php`, `resources/js/flora-admin.js`, dashboard | `GET/POST /api/flora-fauna`, `PUT /api/flora-fauna/{id}`, `GET /api/admin/flora-fauna`, `PATCH /api/admin/flora-fauna/{id}/publicar`, `/archivar` | Público para catálogo; admin para gestión | Completa |
| Usuarios y roles | Sección `users` del dashboard, `resources/js/dashboard/dashboard.js` | `GET/POST /api/admin/usuarios`, `GET/PUT/PATCH/DELETE /api/admin/usuarios/{id}`, `PATCH /api/admin/usuarios/{id}/fecha-nacimiento` | Admin | Completa |
| Moderar voluntariados y artículos | Secciones de dashboard; `resources/js/dashboard/dashboard-api.js` | `GET /api/admin/voluntariados`, `PATCH /api/admin/voluntariados/{id}/aprobar`, `/rechazar`; `GET /api/admin/articulos/pendientes`, `PATCH /api/admin/articulos/{id}/aprobar`, `/rechazar`, `/publicar`, `/despublicar`, `DELETE /api/admin/articulos/{id}` | Admin; propietarios gestionan sus propios artículos/actividades | Completa |
| Estadísticas administrativas y gamificación | Dashboard, sección `gamification`, vistas de cuestionarios/medallas | `GET /api/admin/gamificacion/estadisticas`, `GET /api/admin/gamification/intentos/{attemptId}`, `GET /api/gamificacion/progreso` | Admin para estadísticas; usuario autenticado para progreso | Completa para las vistas expuestas |

Todas las rutas bajo `/api/admin` requieren autenticación y rol admin salvo el
grupo explícito de voluntariados, impactos y dashboard que admite
`admin,organizador`; la API comprueba propiedad para el organizador. El detalle
de visibilidad real depende del middleware/policies, no solo del tab mostrado.

## Inventario de `voluntariado-mvp`

### Stack y convenciones

- Expo SDK 58, Expo Router con rutas por archivo bajo `src/app/`, React Native
  0.88, React 19 y TypeScript.
- Navegación con tabs y stack declarados en `src/app/_layout.tsx` y
  `src/app/(tabs)/_layout.tsx`.
- Features separadas por dominio en `src/features/`; pantallas en
  `src/app/`; componentes y tema compartidos en `src/components/` y
  `src/constants/theme.ts`.
- Estado de sesión global mediante `SessionProvider`/React Context. Los datos
  de pantallas se cargan localmente con hooks y `AbortController`.
- `src/features/api-client.ts` implementa `fetch`, bearer token, normalización
  de errores Laravel y parsers que validan las respuestas.
- Android/iOS guardan el token Sanctum con Expo SecureStore. La variante web
  lo conserva en memoria. `/api/auth/me` valida el usuario y el rol; no hay
  renovación de token en el contrato revisado.
- La suite existente usa `node:test` y `node:assert` desde
  `tests/*.test.mjs`, ejecutada por `npm test`. No se encontró configuración
  E2E de Cypress, Playwright, Maestro o Detox.

### Funcionalidades ya presentes

| Funcionalidad | Pantallas/módulos | Endpoints ya consumidos | Estado |
|---|---|---|---|
| Login, registro, perfil y gates por estado/rol | `src/app/login.tsx`, `src/app/perfil*.tsx`, `src/features/auth/*`, `src/features/auth/profile-gate.tsx` | `POST /api/auth/login`, `POST /api/auth/register`, `GET /api/auth/me`, `POST /api/auth/logout`; además completar perfil, edición de cuenta y avatar | Completa para sesión; token expirado se limpia al recibir 401 |
| Lista pública de voluntariados con búsqueda y filtros | `src/app/(tabs)/voluntariados.tsx`, `src/features/voluntariados/api.ts`, `filters.ts` | `GET /api/v1/mapa/voluntariados`, `GET /api/v1/mapa/lugares` | Completa para mapa/listado, aunque requiere coordenadas válidas |
| Detalle de voluntariado | `src/app/voluntariados/[id].tsx`, `src/features/voluntariados/api.ts` | Consulta la lista `/api/v1/mapa/voluntariados` y busca el ID; lugar por `/api/v1/mapa/lugares`; impactos por `/api/voluntariados/{id}/impactos` | Parcial: no usa el endpoint individual disponible; faltan horario, requisitos y materiales |
| Inscripción, cancelación y lista personal | Detalle, `src/app/inscripciones.tsx`, `src/features/inscripciones/api.ts` | `GET /api/inscripciones`, `GET /api/inscripciones/voluntariado/{id}/mia`, `POST /api/inscripciones`, `POST /api/inscripciones/{id}/cancelar` | Completa |
| Comentarios y dudas del voluntariado | No hay pantalla o cliente en `src/app/` ni `src/features/` | Ninguna llamada a `/api/voluntariados/{id}/comentarios` ni `/api/comentarios/{id}/...` | Ausente |
| Configuración predeterminada de comentarios al crear/editar | `src/app/organizador/voluntariado-editor.tsx`, `src/features/organizador/api.ts` | El formulario actualmente no llama `PATCH /api/voluntariados/{id}/config-comentarios` | Ausente |
| Paneles por rol | `src/app/dashboard.tsx`, `src/features/dashboard.ts` | Resúmenes construidos desde inscripciones, notificaciones, voluntariados, usuarios, moderación y auditoría | Parcial/funcional: hay accesos y contadores, no reproduce todos los módulos de analytics web |
| CRUD/lista de voluntariados para organizador | `src/app/organizador/voluntariados.tsx`, `src/app/organizador/voluntariado-editor.tsx`, `src/features/organizador/api.ts` | `GET/POST/PUT /api/admin/voluntariados`, `GET /api/lugares`, `GET /api/voluntariados/categorias`; admin consulta organizadores | Completa para crear/editar/listar; revisar paridad de eliminación/cancelación desde la UI |
| Participantes, resumen y asistencia por actividad | `src/app/organizador/voluntariado/[id].tsx`, `src/features/organizador/api.ts`, `src/features/asistencia/api.ts` | `GET /api/admin/voluntariados/{id}/dashboard`, `POST /api/asistencias`, `PATCH /api/admin/voluntariados/{id}/codigo-asistencia` | Parcial: cubre QR y registro manual; no se identificó el flujo de solicitudes de corrección de asistencia |
| Impacto por actividad y acumulado | Detalle, panel de actividad y `src/app/impacto.tsx`; `src/features/impacto/*` | `GET /api/voluntariados/{id}/impactos`, `GET /api/impactos/resumen`, `POST /api/admin/voluntariados/{id}/impactos` | Completa para totales/gráficas actuales |
| Gestión de usuarios | `src/app/admin/usuarios.tsx`, `src/app/admin/usuario-editor.tsx`, `src/features/admin/api.ts` | `GET/POST /api/admin/usuarios`, `PUT/DELETE /api/admin/usuarios/{id}` | Completa para alta, edición y eliminación |
| Moderación de contenido | `src/app/admin/moderacion.tsx`, `src/features/admin/api.ts` | `GET /api/admin/voluntariados`, `/articulos/pendientes`, `/flora-fauna`, `/lugares`; acciones `PATCH` de aprobar/rechazar/publicar/archivar | Parcial: cubre revisión de estado; no es el CRUD completo de todos esos recursos |
| Auditoría | `src/app/admin/auditoria.tsx`, `src/features/admin/api.ts` | `GET /api/admin/auditoria`, `GET /api/admin/auditoria/{id}` | Completa para lista y detalle |
| Flora/fauna y artículos públicos | `src/app/(tabs)/flora-fauna.tsx`, `src/app/flora-fauna/[id].tsx`, `src/app/articulos/*`, `src/features/{flora-fauna,articulos}/*` | `GET /api/flora-fauna`, `GET /api/articulos`, `GET /api/articulos/{id}` | Completa para lectura; administración móvil parcial |
| Notificaciones internas | `src/app/notificaciones.tsx`, `src/features/notificaciones/api.ts` | `GET /api/notificaciones`, `PATCH /api/notificaciones/{id}/leida`, `PATCH /api/notificaciones/marcar-todas-leidas` | Completa para inbox; push nativo ausente |

## Gap analysis

Prioridad alta identifica el flujo central que falta o está incompleto para
descubrir y usar actividades. Media cubre operación por rol y moderación. Baja
se reserva para administración avanzada y analítica no esencial al primer uso.

| Funcionalidad | Estado en web | Estado en MVP | Prioridad | Complejidad |
|---|---|---|---|---|
| Autenticación, navegación base y roles | Completa | Implementados; sin refresh de token | Alta como prerequisito, baja brecha | Baja |
| Descubrir voluntariados y filtrar | Completa | Implementado con API de mapa; requiere coordenadas | Alta | Baja |
| Detalle completo de voluntariado | Completo | Parcial: busca dentro de lista de mapa y omite datos existentes | Alta | Media |
| Inscribirse, cancelar y revisar inscripción | Completo | Implementado | Alta, brecha baja | Baja |
| Comentarios públicos/privados, respuestas anidadas y moderación | Completo | Ausente | Alta | Media |
| Configurar `comentarios_por_defecto` | Completo | Ausente en formulario y detalle | Alta junto a comentarios | Baja |
| Panel de organizador y administración de actividad | Completo | Ya hay lista, edición, dashboard e impacto; falta contrastar acciones | Media | Media |
| Asistencia QR/manual y correcciones | Completo | QR/manual presente; solicitudes de corrección no identificadas | Media | Media |
| Moderación de voluntariados | Completa | Cola de revisión presente | Media, brecha baja | Baja |
| CRUD de lugares y flora/fauna | Completo | Catálogo y moderación, CRUD incompleto | Baja | Media |
| Gestión de usuarios y roles | Completa | CRUD móvil presente | Baja, brecha baja | Baja |
| Artículos: edición y administración completa | Completa | Lectura y revisión; edición no identificada en MVP | Baja | Media |
| Estadísticas de impacto | Completa | Totales/gráficas básicas por actividad y globales | Media | Media |
| Estadísticas administrativas/gamificación avanzadas | Completa | Resumen de conteos; no se identificó el dashboard estadístico de admin | Baja | Media |
| Push nativo | Web push disponible en web | No disponible; inbox sí | Baja para paridad core | Alta por nuevo contrato/plataforma |

### Dependencias entre brechas

- Comentarios requieren sesión Sanctum, rol cargado por `/api/auth/me`, el
  voluntariado seleccionado y su `comentarios_por_defecto`.
- El formulario debe aplicar correctamente el flag de privacidad; el servidor
  sigue siendo la autoridad aunque el cliente deshabilite controles.
- El panel de organizador depende de `GET /api/admin/voluntariados/{id}/dashboard`
  y de las verificaciones de propiedad de la API.
- La asistencia de corrección requiere primero definir la experiencia móvil
  del participante y reutiliza el flujo de solicitudes ya expuesto por la API.
- El CRUD de lugares es dependencia del editor de voluntariados porque este
  selecciona un lugar existente; actualmente obtiene opciones por
  `GET /api/lugares`.
- Push nativo no es una extensión únicamente de UI: el backend actualmente
  almacena suscripciones Web Push, cuyo contrato no equivale al token de Expo.

## Plan de implementación por fases

Los archivos indicados son rutas objetivo tentativas, sujetas a la convención
existente del feature. No implican reescribir archivos completos.

### Fase 0 — Contratos, autenticación y navegación

- **Objetivo:** establecer una base segura y verificar el comportamiento de
  rutas privadas antes de agregar pantallas.
- **Incluye:** revisión de gates de `SessionProvider`, manejo de 401/403,
  navegación por rol y configuración de la URL API. La mayor parte ya existe.
- **Archivos:** `src/app/_layout.tsx`, `src/app/dashboard.tsx`,
  `src/features/auth/session-provider.tsx`, `src/features/auth/profile-gate.tsx`,
  `src/features/api-client.ts`.
- **API:** `POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout`.
- **Criterios de aceptación:** una sesión inválida se limpia; usuario, organizador
  y admin ven solo accesos correspondientes; los permisos se verifican también
  en backend; URL errónea y errores HTTP se muestran con mensajes útiles.
- **Riesgos/dependencias:** ya no hay que inventar un almacenamiento de sesión;
  mantener SecureStore en iOS/Android y no persistir bearer token en web.
- **Esfuerzo:** S.

### Fase 1 — Lista, detalle completo e inscripción

- **Objetivo:** cerrar el camino público desde encontrar una actividad hasta
  inscribirse, manteniendo las pantallas actuales.
- **Incluye:** cambiar la carga del detalle para consultar la actividad por ID
  con el endpoint individual disponible, presentar horario, materiales,
  requisitos, estado, organizador y lugar; mantener inscripción/cancelación y
  estado de cupo.
- **Archivos:** `src/features/voluntariados/api.ts`,
  `src/app/voluntariados/[id].tsx`, `src/features/inscripciones/api.ts`,
  pruebas `tests/voluntariados.test.mjs` y `tests/inscripciones.test.mjs`.
- **API:** `GET /api/voluntariados/{id}`, `GET /api/voluntariados`,
  `GET /api/v1/mapa/voluntariados`, `GET /api/v1/mapa/lugares`,
  `GET /api/inscripciones/voluntariado/{id}/mia`, `POST /api/inscripciones`,
  `POST /api/inscripciones/{id}/cancelar`.
- **Criterios de aceptación:** el detalle funciona para una actividad publicada
  aunque no forme parte de la lista cartográfica; se ven fecha, horario,
  materiales y requisitos cuando existen; un usuario puede inscribirse,
  cancelar y consultar su estado; admin no puede inscribirse.
- **Riesgos/dependencias:** el endpoint individual responde con envoltura
  `success/voluntariado`, distinta del parser del mapa (`success/data`); crear
  un parser separado, no relajar el actual.
- **Esfuerzo:** M.

### Fase 2 — Comentarios y privacidad

- **Objetivo:** portar el sistema web de comentarios al detalle móvil.
- **Incluye:** lista cronológica, respuestas anidadas, avatares, composer,
  duda privada, moderación para organizador/admin, publicar/ocultar/eliminar
  con confirmación y mensajes de error.
- **Archivos:** crear `src/features/comentarios/api.ts` y componentes/presentación
  nativos dentro de `src/features/comentarios/`; modificar
  `src/app/voluntariados/[id].tsx`, `src/features/organizador/api.ts` y
  `src/app/organizador/voluntariado-editor.tsx`; agregar
  `tests/comentarios.test.mjs`.
- **API:** endpoints existentes `GET/POST /api/voluntariados/{id}/comentarios`,
  `POST /api/comentarios/{id}/responder`,
  `PATCH /api/comentarios/{id}/visibilidad`,
  `PATCH /api/comentarios/{id}/publicar`,
  `DELETE /api/comentarios/{id}` y
  `PATCH /api/voluntariados/{id}/config-comentarios`.
- **Criterios de aceptación:** un voluntario solo ve públicos y sus privados;
  organizador propietario/admin puede responder y moderar; la privacidad se
  representa junto a las respuestas anidadas; `privado` fuerza el control de
  privacidad; cada acción actualiza/refresca el estado; 401, 403, 404 y 422
  tienen feedback diferenciado.
- **Riesgos/dependencias:** la API exige autenticación para listar comentarios.
  No depender de inscripción. Conservar en frontend las decisiones de
  visibilidad devueltas por API y no reconstruir permisos usando solo el rol.
- **Esfuerzo:** M.

### Fase 3 — Operación del organizador y asistencia

- **Objetivo:** completar la gestión diaria de una actividad sin duplicar lo ya
  cubierto por el panel móvil.
- **Incluye:** validar ciclo editar/crear/publicar, configuración de comentarios,
  revisar participantes y estados, flujo de corrección de asistencia, QR y
  manejo de actividad finalizada/cancelada.
- **Archivos:** `src/app/organizador/voluntariados.tsx`,
  `src/app/organizador/voluntariado-editor.tsx`,
  `src/app/organizador/voluntariado/[id].tsx`,
  `src/app/voluntariados/[id].tsx`, `src/app/inscripciones.tsx`,
  `src/features/organizador/api.ts`, `src/features/asistencia/api.ts`,
  `src/features/inscripciones/api.ts`, `src/features/voluntariados/api.ts`,
  `tests/role-management.test.mjs`, `tests/inscripciones.test.mjs` y
  `README.md`.
- **API:** `GET/POST/PUT/DELETE /api/admin/voluntariados`,
  `GET /api/admin/voluntariados/{id}/dashboard`,
  `PATCH /api/admin/voluntariados/{id}/codigo-asistencia`,
  `POST /api/asistencias`, `POST /api/asistencias/solicitudes`,
  `PATCH /api/asistencias/solicitudes/{id}/responder`,
  `GET /api/inscripciones/voluntariado/{id}/mia`,
  `GET /api/lugares`, `GET /api/voluntariados/categorias`.
- **Criterios de aceptación:** organizador solo administra actividades propias;
  admin puede asignar organizador; la edición respeta reglas de fecha/estado;
  participantes con falta pueden pedir revisión y el propietario/admin puede
  resolverla; los cambios vuelven a cargar el resumen; asistencia manual y QR
  respetan estado, horario y cancelación de la actividad.
- **Riesgos/dependencias:** roles y ownership se validan en API. No mostrar
  datos personales de participantes que el backend no autorice.
- **Esfuerzo:** M.

### Fase 4 — CRUD administrativo y moderación ampliada (implementada)

- **Objetivo:** completar la gestión administrativa móvil de lugares,
  flora/fauna y artículos, incluida la edición de imágenes, sin reemplazar la
  cola de moderación ya existente.
- **Incluye:** crear y editar lugares, registros de flora/fauna y artículos;
  seleccionar, reemplazar y retirar imágenes; publicar/archivar lugares y
  flora/fauna; aprobar/rechazar lugares propuestos y artículos pendientes;
  publicar borradores, retirar artículos publicados y eliminar artículos con
  confirmación.
- **Archivos:** `src/app/admin/contenido.tsx`,
  `src/app/admin/moderacion.tsx`, `src/app/dashboard.tsx`,
  `src/app/_layout.tsx`, `src/features/admin/api.ts`,
  `src/features/dashboard.ts`, `tests/role-management.test.mjs`,
  `tests/dashboard.test.mjs`, `app.json`, `package.json` y
  `package-lock.json`.
- **API:** `GET /api/admin/lugares`, `POST /api/lugares`,
  `POST /api/lugares/{id}` con `_method=PUT`,
  `PATCH /api/admin/lugares/{id}/aprobar|rechazar|publicar|archivar`;
  `GET /api/admin/flora-fauna`, `POST /api/flora-fauna`,
  `POST /api/flora-fauna/{id}` con `_method=PUT`,
  `PATCH /api/admin/flora-fauna/{id}/publicar|archivar`;
  `GET /api/articulos` (como admin devuelve todos los estados),
  `POST /api/articulos`, `POST /api/articulos/{id}` con `_method=PUT`,
  `PATCH /api/admin/articulos/{id}/aprobar|rechazar|publicar|despublicar` y
  `DELETE /api/admin/articulos/{id}`.
- **Criterios de aceptación:** el acceso es exclusivo para administradores;
  formularios validan campos obligatorios y coordenadas emparejadas; archivos
  se envían como multipart y los índices de imágenes retiradas conservan el
  contrato Laravel; cambios de estado y eliminaciones requieren confirmación;
  listas se recargan desde la API y muestran errores del servidor.
- **Dependencias/riesgos:** se agregó `expo-image-picker` con su permiso de
  biblioteca. Los development builds instalados requieren recompilación para
  incorporar el módulo/configuración nativos. Los archivos deben ser imágenes
  de máximo 5 MB; permisos y autorización siguen siendo responsabilidad de la
  API. La edición de lugares conserva el flujo de propuesta/aprobación del
  backend.
- **Esfuerzo:** M/L; incluye subida y edición de imágenes.

### Fase 5 — Estadísticas, notificaciones y cierre QA (implementada)

- **Objetivo:** completar la paridad de reportes necesaria y endurecer la
  confiabilidad de los reportes y notificaciones móviles.
- **Incluye:** bitácora con periodo, categoría, lugar y estado para organizador
  y admin; KPIs, evolución, categorías e historial con unidades/contexto;
  estadísticas administrativas de cuestionarios e intentos con una vista
  individual de respuestas; selección y guardado de frecuencia de recordatorios
  desde el inbox. Los gráficos indican que las barras usan escala relativa por
  serie y muestran cada valor exacto.
- **Archivos:** `src/app/bitacora-impacto.tsx`,
  `src/features/impacto/bitacora-api.ts`,
  `src/app/admin/gamificacion/index.tsx`,
  `src/app/admin/gamificacion/[attemptId].tsx`,
  `src/features/gamificacion/admin-api.ts`, `src/app/notificaciones.tsx`,
  `src/features/notificaciones/api.ts`, `src/features/dashboard.ts`,
  `src/app/dashboard.tsx` y `tests/fase5-api.test.mjs`.
- **API:** `GET /api/impactos/resumen`,
  `GET /api/admin/bitacora-impacto`,
  `GET /api/organizador/bitacora-impacto`,
  `GET /api/admin/gamificacion/estadisticas`,
  `GET /api/admin/gamification/intentos/{attemptId}`,
  `GET/PATCH /api/notificaciones/preferencias` y el inbox/lectura existentes.
- **Criterios verificados:** parsers rechazan contratos incompletos; las
  llamadas usan los endpoints por rol y bearer token; métricas conservan sus
  unidades; filtros validan fechas; vistas vacías y errores son explícitos;
  intentos enlazan a respuestas de API y preferencias se guardan mediante
  `PATCH`. Pasan `npm test` y `npx tsc --noEmit`.
- **Pendiente de cierre:** probar recorridos de roles y accesibilidad con
  VoiceOver/TalkBack en dispositivos, rebuild de development build y validar
  las respuestas contra una API con datos reales. Se dejó deliberadamente fuera
  el push nativo: la API solo implementa Web Push y no ofrece contrato Expo.
  Las listas consultadas aún no exponen paginación, así que hay que revisar
  volumen y definir cursor/orden antes de escalar.
- **Esfuerzo:** M.

## Dependencias y recomendaciones para la API

### Ya disponibles: no son bloqueos para las fases core

- Detalle de actividad: `GET /api/voluntariados/{id}` ya existe y devuelve
  `success` y `voluntariado`. La pantalla MVP actualmente consulta la lista de
  mapa en su lugar.
- Comentarios: todos los endpoints de listar, crear, responder, publicar,
  cambiar visibilidad, eliminar y configurar el valor por defecto existen en
  `routes/api.php` y requieren bearer Sanctum.
- Inscripciones, dashboard del voluntariado, asistencia, métricas de impacto,
  moderación y administración de usuarios también tienen rutas disponibles.

### Ajustes o decisiones recomendados

1. **Detalle móvil:** usar el endpoint individual existente; si su serialización
   no satisface el contrato de pantalla, definir un recurso móvil/público
   explícito antes de modificar el controlador. Evitar descargar todas las
   actividades para buscar un ID.
2. **Paginación:** el cliente actual recibe listas completas de mapa,
   comentarios, usuarios, auditoría y paneles. No bloquea el MVP demo, pero
   debe acordarse paginación/cursor y orden estable antes de escalar.
3. **Comentarios:** el endpoint de lista es autenticado aunque el contenido
   público sea legible por cualquier voluntario autenticado. No se requiere
   endpoint adicional para el MVP actual.
4. **Push nativo:** el backend implementa suscripciones Web Push; no se debe
   enviar el token de Expo a `/api/notificaciones/push/suscripcion`. Añadir
   push móvil solo si se acuerda un contrato de token Expo y su envío desde
   backend.
5. **Renovación Sanctum:** no se encontró endpoint de refresh. El MVP debe
   tratar 401 como cierre de sesión y reautenticación; no asumir renovación
   silenciosa.
6. **Contratos:** documentar con ejemplos las envolturas distintas (`data`,
   `success/data`, `success/voluntariado`) y preservar parsers estrictos
   separados en TypeScript.

## Recomendaciones técnicas

### Reutilización entre web y móvil

No compartir directamente vistas, estilos ni componentes de UI: web usa Blade,
DOM y CSS; móvil usa React Native, componentes nativos, `StyleSheet` y APIs de
plataforma. Copiar lógica de negocio solo cuando sea pequeña y claramente
independiente de UI. La mejor inversión común es mantener contratos de API
estables y, si ambos frontends pasan a un workspace de paquetes, compartir
tipos/parsers puros generados o versionados. En la estructura actual, conviene
portar el comportamiento y reimplementar la presentación nativa usando los
patrones de `src/features/*`.

### Autenticación y autorización

- Mantener bearer Sanctum y SecureStore en iOS/Android.
- En web no persistir tokens en `localStorage`; el comportamiento actual de
  memoria evita retención entre recargas.
- Resolver el rol mediante `/api/auth/me`; usarlo para navegación y UX, no
  como sustituto de policy/middleware Laravel.
- Ante 401, limpiar token y solicitar login; ante 403, explicar permiso faltante;
  ante 422, mostrar errores de campo; soportar cancelación de solicitudes al
  cambiar de pantalla.
- Evitar que los dashboards móviles muestren correo u otros datos personales
  si no son necesarios para la tarea y autorizados por API.

### Pruebas mínimas por fase

- **Fase 0:** pruebas Node para parsers de login, expiración 401 y gates por
  rol; comprobación manual de persistencia nativa y web.
- **Fase 1:** pruebas de parseo de detalle, horario opcional, actividad sin
  coordenadas, cupo lleno, inscripción y cancelación; recorrido manual en
  iOS/Android y Expo Web.
- **Fase 2:** pruebas de contrato de comentarios para privacidad, anidación,
  respuestas de moderación, 401/403/404/422 y `comentarios_por_defecto`; manual
  con voluntario autor, otro voluntario, organizador y admin.
- **Fase 3:** pruebas de ownership del dashboard, QR activo/inactivo y
  asistencia/corrección en/ fuera de horario; recorrido con organizador y admin.
- **Fase 4:** parsers y acciones CRUD, errores de validación, confirmación de
  borrado y estados de publicación; verificación de roles contra API.
- **Fase 5:** parsers, rutas por rol y frecuencias de notificación cubiertos por
  `tests/fase5-api.test.mjs`; quedan recorridos de datos reales y revisión de
  VoiceOver/TalkBack en dispositivos.
- El proyecto ya cuenta con pruebas unitarias/de contrato usando `node:test`;
  no se debe afirmar que hay E2E. No añadir Detox/Maestro u otro runner sin
  aprobar la dependencia y definir CI/emuladores.

## Orden recomendado y criterio de cierre

1. Preservar y validar auth/navegación existentes.
2. Completar la carga y los datos del detalle usando el endpoint individual.
3. Integrar comentarios y su configuración por defecto.
4. Completar la operación de organizador y asistencia.
5. Completar la administración CRUD móvil y su gestión de imágenes
   (implementada en la Fase 4).
6. Completar estadísticas, bitácora y preferencias de recordatorio
   (implementada en la Fase 5).
7. Cerrar con development build actualizado y QA manual en iOS/Android,
   incluyendo VoiceOver/TalkBack; mantener push nativo y paginación como
   dependencias futuras.

Se considera paridad funcional para el MVP cuando los flujos de lista, detalle,
inscripción, comentarios, moderación correspondiente al rol y operación del
organizador pasan sus pruebas de contrato y recorridos manuales. Los CRUDs
administrativos amplios, analytics avanzados y push nativo pueden mantenerse
como entregas posteriores si no son requisito de lanzamiento.
