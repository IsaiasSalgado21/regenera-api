<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GamificacionSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = [
            [
                'slug' => 'seguridad-limpieza',
                'version' => 2,
                'titulo' => 'Seguridad y precauciones en limpieza',
                'descripcion' => 'Aprende a reconocer riesgos y protegerte durante actividades costeras y de manglar.',
                'guia' => <<<'GUIDE'
## Preparar la jornada
Participa en la inducción y confirma el área, las herramientas y el equipo apropiados para la tarea. Guantes adecuados, calzado cerrado y protección contra el sol ayudan a reducir riesgos; no sustituyen la capacitación ni las indicaciones del responsable.

## Reconocer riesgos
Vidrio roto, jeringas, metal oxidado, objetos punzocortantes y recipientes con sustancias desconocidas requieren precaución. No los manipules directamente ni intentes retirarlos sin capacitación y equipo adecuados. Detén el trabajo cercano y comunica el sitio del hallazgo al responsable. Separa y entrega residuos conforme al plan de la jornada; no todos siguen la misma ruta.

## Flora, fauna y sitios sensibles
Respeta la vegetación y los límites indicados. Si observas fauna, una tortuga o un posible nido, evita tocar, mover o rodear el ejemplar; suspende la intervención cercana y avisa al personal responsable o autorizado. En playas y manglares de BCS, una limpieza debe evitar alterar hábitats.

## Fuentes
- [CONOCER: estándar de competencia para limpieza de playas](https://conocer.gob.mx/contenido/transparencia/Avisos/2026/1SOCT/2_Aplicacion_conservacion_servicios_limpieza_en_playas.pdf)
- [CONANP: Programa de Manejo de Balandra](https://www.conanp.gob.mx/que_hacemos/pdf/programas_manejo/2017/Flora%20y%20Fauna%20Balandra%20%28libro%20completo%29.pdf)
GUIDE,
                'audiencia' => ['usuario'],
                'minimoAprobacion' => 75,
                'puntosAprobacion' => 20,
                'medalla' => ['slug' => 'voluntario-seguro', 'nombre' => 'Voluntario Seguro', 'descripcion' => 'Conoces las precauciones básicas antes de participar en una limpieza.', 'icono' => 'shield-check'],
                'preguntas' => [
                    [
                        'id' => 'equipo',
                        'tipo' => 'unica',
                        'texto' => 'Antes de iniciar una limpieza costera, ¿qué preparación es más adecuada?',
                        'opciones' => [
                            'Comenzar con ropa cómoda y ajustar el equipo después de encontrar residuos.',
                            'Recibir la inducción, usar protección apropiada a la tarea y atender las indicaciones del responsable.',
                            'Confiar en que los guantes comunes permiten manipular cualquier residuo.',
                        ],
                        'respuestaCorrecta' => 1,
                        'explicacion' => 'La preparación combina orientación, equipo adecuado a la tarea y coordinación. Ningún equipo básico vuelve seguro manipular residuos desconocidos o peligrosos.',
                    ],
                    [
                        'id' => 'residuo-peligroso',
                        'tipo' => 'unica',
                        'texto' => 'Durante una jornada encuentras una jeringa y un recipiente con contenido desconocido. ¿Cómo procedes?',
                        'opciones' => [
                            'Colocarlos con los demás residuos para que el equipo los separe al final.',
                            'Usar una herramienta improvisada para llevarlos al punto de acopio.',
                            'Evitar el contacto, detener el trabajo cercano y comunicar su ubicación al responsable.',
                        ],
                        'respuestaCorrecta' => 2,
                        'explicacion' => 'Los objetos punzocortantes y las sustancias desconocidas pueden causar lesiones o exposición. El responsable debe coordinar una respuesta adecuada; no se deben mezclar con residuos comunes.',
                    ],
                    [
                        'id' => 'tambor',
                        'tipo' => 'verdadero_falso',
                        'texto' => 'Si un objeto metálico pesado no muestra una fuga visible, el equipo puede moverlo entre varias personas.',
                        'opciones' => ['Verdadero', 'Falso'],
                        'respuestaCorrecta' => 1,
                        'explicacion' => 'La ausencia de una fuga visible no permite conocer el contenido ni el estado del objeto. No lo muevas y reporta el hallazgo.',
                    ],
                    [
                        'id' => 'nido-tortuga',
                        'tipo' => 'unica',
                        'texto' => 'La jornada encuentra una tortuga o un posible nido en el área de trabajo. ¿Qué principio debe guiar al grupo?',
                        'opciones' => [
                            'Suspender la intervención cercana, mantener distancia y avisar al personal responsable.',
                            'Acercarse para confirmar la especie antes de continuar.',
                            'Reacomodar la arena u objetos alrededor para hacer visible el hallazgo.',
                        ],
                        'respuestaCorrecta' => 0,
                        'explicacion' => 'Tocar, mover o alterar el entorno puede perturbar a la fauna o el sitio de anidación. El personal responsable puede valorar los siguientes pasos.',
                    ],
                ],
            ],
            [
                'slug' => 'restauracion-entorno',
                'version' => 2,
                'titulo' => 'Restauración y conocimiento del entorno',
                'descripcion' => 'Reconoce el valor de los ecosistemas y las especies costeras de Baja California Sur.',
                'guia' => <<<'GUIDE'
## Manglares y costa
Los manglares son humedales costeros. Sus raíces y suelos ofrecen refugio, alimentación y crecimiento a organismos; retienen sedimentos, filtran agua y contribuyen a proteger la costa. Estas funciones dependen de que se mantengan las condiciones del sitio y sus flujos de agua.

## Limpiar, conservar y restaurar
Una limpieza retira residuos; conservar busca mantener ecosistemas y procesos; restaurar procura recuperar condiciones ecológicas alteradas. Reforestar es plantar vegetación y, por sí solo, no garantiza restauración: la especie, el sitio, la hidrología y el tipo de intervención requieren evaluación y las autorizaciones que correspondan.

## Dunas y aves
Las dunas y su vegetación forman parte de la protección costera y proporcionan hábitat. Tránsito fuera de zonas indicadas, extracción de plantas o cambios en la superficie pueden degradarlas. Las playas también son áreas de alimentación y descanso para aves migratorias; observarlas sin aproximarse ni interrumpirlas reduce disturbios.

Bahía Magdalena es un ejemplo regional de mosaicos costeros de BCS. Las acciones de voluntariado deben seguir la orientación del sitio y no sustituir una evaluación ecológica.

## Fuentes
- [CONABIO: manglares](https://www.biodiversidad.gob.mx/ecosistemas/manglares.html)
- [SIMAR+ / CONABIO: ecosistemas costeros](https://simar.conabio.gob.mx/plus/ecosistemas/)
- [SEMARNAT: manglares mexicanos](https://www.gob.mx/semarnat/articulos/manglares-mexicanos)
GUIDE,
                'audiencia' => ['usuario', 'organizador', 'admin'],
                'minimoAprobacion' => 66.67,
                'puntosAprobacion' => 20,
                'medalla' => ['slug' => 'guardian-manglar', 'nombre' => 'Guardián del Manglar', 'descripcion' => 'Aprendiste sobre ecosistemas y conservación de BCS.', 'icono' => 'mangrove'],
                'preguntas' => [
                    [
                        'id' => 'carbono-azul',
                        'tipo' => 'multiple',
                        'texto' => '¿Qué funciones pueden cumplir los manglares dentro de los ecosistemas costeros?',
                        'opciones' => [
                            'Ofrecer refugio y zonas de alimentación y crecimiento para organismos.',
                            'Reemplazar las dunas y evitar cualquier cambio costero.',
                            'Retener sedimentos, filtrar agua y contribuir al almacenamiento de carbono.',
                        ],
                        'respuestasCorrectas' => [0, 2],
                        'explicacion' => 'Los manglares ofrecen hábitat, participan en la retención de sedimentos y filtración biológica, y sus suelos pueden almacenar carbono. No reemplazan otros ecosistemas ni detienen todos los procesos costeros.',
                    ],
                    [
                        'id' => 'dunas',
                        'tipo' => 'unica',
                        'texto' => 'Una playa queda limpia, pero se mantiene el tránsito sobre vegetación de dunas. ¿Qué aspecto ecológico sigue sin atenderse?',
                        'opciones' => [
                            'La limpieza por sí sola ya restableció el hábitat y la protección de la duna.',
                            'La vegetación y la forma de la duna pueden seguir degradándose, aunque se hayan retirado residuos.',
                            'La duna necesita convertirse en una superficie sin vegetación para estabilizarse.',
                        ],
                        'respuestaCorrecta' => 1,
                        'explicacion' => 'Retirar residuos no corrige el tránsito o la pérdida de vegetación. La conservación de la duna requiere mantener sus componentes y procesos, no solo limpiar la playa.',
                    ],
                    [
                        'id' => 'playero-rojizo',
                        'tipo' => 'unica',
                        'texto' => 'Al trabajar en una playa que usan aves migratorias para alimentarse o descansar, ¿qué práctica reduce el disturbio?',
                        'opciones' => [
                            'Acercarse para fotografiarlas y luego despejar el área.',
                            'Mover restos naturales para concentrar a las aves en un solo punto.',
                            'Mantener distancia y seguir las indicaciones sobre las áreas de trabajo y observación.',
                        ],
                        'respuestaCorrecta' => 2,
                        'explicacion' => 'La distancia y el respeto por las áreas indicadas ayudan a evitar que la actividad interrumpa la alimentación o el descanso de las aves.',
                    ],
                ],
            ],
            [
                'slug' => 'economia-circular',
                'version' => 2,
                'titulo' => 'Economía circular y conducta regenerativa',
                'descripcion' => 'Explora decisiones para prevenir residuos y cerrar ciclos de materiales.',
                'guia' => <<<'GUIDE'
## De un modelo lineal a uno circular
El modelo lineal extrae materiales, produce bienes y los desecha. La economía circular busca reducir el uso de recursos y mantener productos y materiales en uso por más tiempo. Para una salida en BCS, llevar recipientes reutilizables y evitar empaques innecesarios previene residuos desde el origen.

## Priorizar la prevención
Primero evita y reduce lo que no necesitas; después considera reutilizar y reparar. El reciclaje puede recuperar materiales cuando están limpios y existe una opción que realmente los recibe. La clasificación o el símbolo de un envase no garantizan que sea aceptado en cada localidad: confirma las instrucciones del servicio disponible y planifica la entrega.

## Orgánicos y limpiezas
El compostaje aprovecha residuos orgánicos biodegradables cuando el proceso tiene condiciones adecuadas; no consiste en dejar restos en una playa o mezclarlos con materiales incompatibles. En una jornada, planea recipientes reutilizables, separación y destino de residuos con el responsable para no generar desechos adicionales.

## Fuente
- [INEGI: presentación sobre problemáticas ambientales y gestión integral de residuos](https://www.inegi.org.mx/contenidos/inegi/ccu/2023/primera_sesion/presentaciones/problematicas_ambientales.pdf)
GUIDE,
                'audiencia' => ['usuario', 'organizador', 'admin'],
                'minimoAprobacion' => 66.67,
                'puntosAprobacion' => 20,
                'medalla' => ['slug' => 'agente-circular', 'nombre' => 'Agente Circular', 'descripcion' => 'Conoces principios de prevención y aprovechamiento de residuos.', 'icono' => 'recycle'],
                'preguntas' => [
                    [
                        'id' => 'jerarquia',
                        'tipo' => 'unica',
                        'texto' => 'Al planear una actividad, ¿qué secuencia refleja mejor la prioridad de la economía circular?',
                        'opciones' => [
                            'Reciclar todo primero y decidir después qué materiales se necesitan.',
                            'Prevenir y reducir; luego reutilizar o reparar, reciclar cuando exista una opción adecuada y disponer al final.',
                            'Separar los residuos al terminar, aunque se hubieran podido evitar desde la compra.',
                        ],
                        'respuestaCorrecta' => 1,
                        'explicacion' => 'Evitar y reducir residuos conserva recursos antes de que se conviertan en desechos. Reutilización y reciclaje son opciones posteriores y dependen de las condiciones y servicios disponibles.',
                    ],
                    [
                        'id' => 'organicos',
                        'tipo' => 'verdadero_falso',
                        'texto' => 'Cualquier residuo orgánico, incluso mezclado o contaminado, puede convertirse en composta útil en cualquier lugar.',
                        'opciones' => ['Verdadero', 'Falso'],
                        'respuestaCorrecta' => 1,
                        'explicacion' => 'El compostaje requiere materiales apropiados y condiciones adecuadas. La contaminación o una mezcla inadecuada puede impedir un aprovechamiento seguro.',
                    ],
                    [
                        'id' => 'bolsas',
                        'tipo' => 'multiple',
                        'texto' => '¿Qué decisiones ayudan a reducir residuos en una jornada sin asumir que todo es reciclable?',
                        'opciones' => [
                            'Prevenir empaques y elegir artículos reutilizables antes del evento.',
                            'Confirmar qué materiales acepta realmente el servicio de recepción disponible.',
                            'Considerar reciclable cualquier envase que tenga un símbolo de resina.',
                        ],
                        'respuestasCorrectas' => [0, 1],
                        'explicacion' => 'Prevenir y reutilizar reduce residuos desde el origen. La separación debe corresponder a las opciones reales de recepción; un símbolo no garantiza aceptación local.',
                    ],
                ],
            ],
            [
                'slug' => 'organizacion-responsable',
                'version' => 2,
                'titulo' => 'Verificación para organizadores',
                'descripcion' => 'Conoce las responsabilidades para coordinar actividades ambientales seguras y respetuosas.',
                'guia' => <<<'GUIDE'
## Instituciones y atribuciones
Las competencias no son intercambiables: CONANP administra y conserva Áreas Naturales Protegidas federales; SEMARNAT conduce la política ambiental y regula dentro de sus atribuciones; PROFEPA inspecciona y vigila el cumplimiento de la legislación ambiental federal; CONAPESCA atiende la administración y regulación del aprovechamiento pesquero dentro de sus atribuciones. La autoridad competente depende del tema y del sitio.

## Zonas de Refugio Pesquero
Son áreas delimitadas para conservar y favorecer el desarrollo de recursos pesqueros y proteger el ambiente que los rodea. Sus disposiciones se establecen para cada zona: no deduzcas restricciones por el nombre ni supongas que todas tienen las mismas reglas. Consulta el acuerdo aplicable y coordina con las autoridades y comunidades pertinentes antes de planear una actividad.

## Planear una jornada
Antes de convocar, confirma responsables y jurisdicción, recibe orientación del sitio, delimita el área de trabajo, informa prácticas para respetar hábitats, y acuerda separación, traslado y recepción de residuos. No anuncies permisos, servicios o destinos hasta confirmarlos con quienes correspondan.

## Fuentes
- Portales institucionales: [CONANP](https://www.gob.mx/conanp), [SEMARNAT](https://www.gob.mx/semarnat), [PROFEPA](https://www.gob.mx/profepa) y [CONAPESCA](https://www.gob.mx/conapesca).
- [CONAPESCA: nuevas Zonas de Refugio Pesquero en BCS, 9 de junio de 2026](https://www.gob.mx/conapesca/articulos/hay-tres-nuevas-zonas-de-refugio-pesquero-para-beneficio-de-comunidades-de-baja-california-sur-428258)
- [Diario Oficial: acuerdo del corredor Boca del Álamo-Punta Pescadero](https://sidof.segob.gob.mx/notas/docFuente/5787029)
GUIDE,
                'audiencia' => ['organizador'],
                'requiereOrganizadorVerificado' => true,
                'minimoAprobacion' => 66.67,
                'puntosAprobacion' => 30,
                'medalla' => ['slug' => 'lider-responsable', 'nombre' => 'Líder Responsable', 'descripcion' => 'Completaste la certificación educativa para organizar actividades.', 'icono' => 'leader'],
                'preguntas' => [
                    [
                        'id' => 'autoridades',
                        'tipo' => 'multiple',
                        'texto' => '¿Qué relaciones entre institución y función son correctas dentro de sus atribuciones?',
                        'opciones' => [
                            'CONANP administra y conserva ANP federales; PROFEPA inspecciona y vigila el cumplimiento ambiental federal.',
                            'SEMARNAT conduce la política ambiental; CONAPESCA atiende el aprovechamiento pesquero.',
                            'PROFEPA administra todas las ANP y CONANP inspecciona cualquier actividad pesquera.',
                            'CONAPESCA autoriza indistintamente toda actividad en playas y SEMARNAT administra las cooperativas.',
                        ],
                        'respuestasCorrectas' => [0, 1],
                        'explicacion' => 'Cada institución tiene atribuciones diferentes. La coordinación debe partir del tema, la jurisdicción y las disposiciones aplicables, sin atribuir a una dependencia funciones de otra.',
                    ],
                    [
                        'id' => 'destino-residuos',
                        'tipo' => 'unica',
                        'texto' => '¿Qué debe confirmar la organización antes de anunciar cómo se entregarán los residuos de una jornada?',
                        'opciones' => [
                            'Que cualquier contenedor cercano recibirá todo lo recolectado.',
                            'Que los materiales se pueden mezclar para facilitar su transporte.',
                            'Qué materiales recibe realmente el servicio o sitio acordado y cómo se coordinarán su separación y traslado.',
                        ],
                        'respuestaCorrecta' => 2,
                        'explicacion' => 'El destino debe confirmarse antes de convocar. La aceptación y el manejo pueden variar; planearlos evita abandonar residuos o prometer una ruta que no existe.',
                    ],
                    [
                        'id' => 'refugio-pesquero',
                        'tipo' => 'verdadero_falso',
                        'texto' => 'Todas las Zonas de Refugio Pesquero tienen las mismas disposiciones, así que basta con conocer las reglas de otra zona.',
                        'opciones' => ['Verdadero', 'Falso'],
                        'respuestaCorrecta' => 1,
                        'explicacion' => 'Las disposiciones corresponden al acuerdo aplicable a cada zona. Se debe consultar la fuente oficial específica y coordinar la actividad según el sitio.',
                    ],
                ],
            ],
        ];

        $existingQuizzes = [];
        foreach ($quizzes as $quiz) {
            $existing = Cuestionario::query()
                ->where('slug', $quiz['slug'])
                ->where('version', $quiz['version'])
                ->first();

            if ($existing && ! $this->matchesSeedContent($existing, $quiz)) {
                throw new RuntimeException(sprintf(
                    'Conflicto en el cuestionario "%s" v%d: el contenido almacenado difiere del seeder. No se sobrescribió. Usa el CRUD administrativo para crear una nueva versión y conservar intentos e historial.',
                    $quiz['slug'],
                    $quiz['version'],
                ));
            }

            if (! $existing && Cuestionario::query()->where('slug', $quiz['slug'])->exists()) {
                throw new RuntimeException(sprintf(
                    'Conflicto en el cuestionario "%s": existe historial de versiones, pero falta la v%d del seeder. No se creó ni activó otra versión; resuelve el cambio mediante el CRUD administrativo.',
                    $quiz['slug'],
                    $quiz['version'],
                ));
            }

            $existingQuizzes[$quiz['slug']] = $existing;
        }

        $database = DB::connection('mongodb')->getMongoDB();
        $database->selectCollection('cuestionarios')->createIndex(
            ['slug' => 1, 'version' => 1],
            ['unique' => true, 'name' => 'cuestionarios_slug_version_unique'],
        );
        $database->selectCollection('intentos_cuestionarios')->createIndex(
            ['usuarioId' => 1, 'cuestionarioSlug' => 1, 'actividadId' => 1, 'created_at' => -1],
            ['name' => 'intentos_usuario_cuestionario_fecha'],
        );
        $database->selectCollection('intentos_cuestionarios')->createIndex(
            ['usuarioId' => 1, 'cuestionarioSlug' => 1, 'actividadId' => 1],
            [
                'unique' => true,
                'name' => 'intento_abierto_usuario_cuestionario_unico',
                'partialFilterExpression' => ['estado' => 'en_progreso'],
            ],
        );
        $database->selectCollection('respuestas_cuestionarios')->createIndex(
            ['claveUnica' => 1],
            ['unique' => true, 'name' => 'respuesta_cuestionario_clave_unique'],
        );
        $database->selectCollection('respuestas_cuestionarios')->createIndex(
            ['intentoId' => 1, 'preguntaId' => 1],
            ['name' => 'respuestas_por_intento_pregunta'],
        );
        $database->selectCollection('eventos_gamificacion')->createIndex(
            ['claveUnica' => 1],
            ['unique' => true, 'name' => 'eventos_gamificacion_clave_unique'],
        );
        $database->selectCollection('eventos_gamificacion')->createIndex(
            ['usuarioId' => 1, 'created_at' => -1],
            ['name' => 'eventos_gamificacion_usuario_fecha'],
        );

        foreach ($quizzes as $quiz) {
            if ($existingQuizzes[$quiz['slug']]) {
                continue;
            }

            $quiz['activo'] = true;
            Cuestionario::create($quiz);
        }

        $this->command?->info('Cuatro cuestionarios educativos e índices únicos de gamificación verificados.');
    }

    private function matchesSeedContent(Cuestionario $existing, array $seed): bool
    {
        foreach ($seed as $key => $expected) {
            if ($key === 'activo') {
                continue;
            }

            if ($this->normalize($existing->getAttribute($key)) !== $this->normalize($expected)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        } elseif (is_object($value)) {
            $value = (array) $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item) => $this->normalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }

        return $value;
    }
}
