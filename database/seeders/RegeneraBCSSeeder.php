<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\FloraFauna;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\ObjectId;

class RegeneraBCSSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Usuarios de prueba
        |--------------------------------------------------------------------------
        | Se crean únicamente si el correo todavía no existe.
        | La contraseña de estos usuarios de prueba es: Regenera123!
        */

        $organizador = User::firstOrCreate(
            ['correo' => 'organizador@regenerabcs.com'],
            [
                'nombre' => 'Organizador Regenera BCS',
                'password' => Hash::make('Regenera123!'),
                'foto' => null,
                'rol' => 'organizador',
                'organizadorVerificado' => true,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        $usuario1 = User::firstOrCreate(
            ['correo' => 'voluntario1@regenerabcs.com'],
            [
                'nombre' => 'María López',
                'password' => Hash::make('Regenera123!'),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        $usuario2 = User::firstOrCreate(
            ['correo' => 'voluntario2@regenerabcs.com'],
            [
                'nombre' => 'Carlos García',
                'password' => Hash::make('Regenera123!'),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Lugares
        |--------------------------------------------------------------------------
        */

        $playaBalandra = Lugar::firstOrCreate(
            ['nombre' => 'Playa Balandra'],
            [
                'descripcion' => 'Área costera para actividades de conservación y limpieza.',
                'municipio' => 'La Paz',
                'direccion' => 'Zona de Playa Balandra, La Paz, Baja California Sur',
                'latitud' => 24.3217,
                'longitud' => -110.3248,
                'tipo' => 'playa',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ]
        );

        $malecon = Lugar::firstOrCreate(
            ['nombre' => 'Malecón de La Paz'],
            [
                'descripcion' => 'Zona costera urbana para jornadas comunitarias de limpieza.',
                'municipio' => 'La Paz',
                'direccion' => 'Malecón, La Paz, Baja California Sur',
                'latitud' => 24.1615,
                'longitud' => -110.3158,
                'tipo' => 'zona_urbana',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ]
        );

        $parque = Lugar::firstOrCreate(
            ['nombre' => 'Parque Revolución'],
            [
                'descripcion' => 'Espacio público para actividades de educación ambiental y reforestación.',
                'municipio' => 'La Paz',
                'direccion' => 'Zona centro, La Paz, Baja California Sur',
                'latitud' => 24.1435,
                'longitud' => -110.3120,
                'tipo' => 'parque',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Voluntariados
        |--------------------------------------------------------------------------
        | Las fechas se generan a partir del día en que se ejecuta el seeder.
        */

        $fechaLimpieza = now()->addDays(7)->startOfDay();
        $fechaReforestacion = now()->addDays(14)->startOfDay();
        $fechaEducacion = now()->addDays(21)->startOfDay();

        $limpieza = Voluntariado::query()->firstOrNew(
            ['titulo' => 'Jornada de limpieza de Playa Balandra'],
        );
        if (! $limpieza->exists) {
            $limpieza->fill([
                'lugarId' => (string) $playaBalandra->_id,
                'organizadorId' => (string) $organizador->_id,
                'cupo' => 30,
                'imagen' => null,
                'estado' => 'publicado',
            ]);
        }
        $limpieza->fill([
            'descripcion' => <<<'CONTENT'
Balandra es un Área de Protección de Flora y Fauna que alberga manglares, dunas costeras, playas, arrecifes rocosos y vegetación del desierto. La acumulación de residuos puede afectar la calidad del entorno, la fauna y los ecosistemas costeros.

En esta jornada, las personas voluntarias participarán en la recolección manual de residuos sólidos en las zonas autorizadas para la actividad. Se busca fomentar la responsabilidad ambiental, reducir la presencia de basura y promover hábitos que ayuden a conservar las playas.

Los residuos recolectados deberán concentrarse en el punto de acopio previamente establecido para su disposición adecuada. La actividad se realizará sin extraer plantas, alterar dunas, ingresar a zonas restringidas ni molestar a la fauna silvestre. No se deben manipular residuos peligrosos, objetos punzocortantes ni animales muertos.

Objetivo: contribuir a mantener limpias las zonas costeras y fomentar la participación ciudadana en la conservación ambiental. La realización de esta propuesta dentro del área protegida requiere la coordinación y autorización correspondientes; este registro no implica que Regenera BCS cuente con ellas.
CONTENT,
            'fechaInicio' => $fechaLimpieza->copy()->setTime(8, 0),
            'fechaFin' => $fechaLimpieza->copy()->setTime(12, 0),
            'horaInicio' => '08:00',
            'horaFin' => '12:00',
            'requisitos' => [
                'Llevar agua suficiente para mantenerse hidratado',
                'Usar ropa cómoda y calzado cerrado adecuado',
                'Llevar gorra o sombrero y protección solar',
                'Seguir las indicaciones de los responsables',
                'Respetar las zonas delimitadas para la actividad',
            ],
            'materiales' => [
                'Guantes reutilizables',
                'Bolsas resistentes para residuos',
                'Pinzas recolectoras',
                'Agua para hidratación',
            ],
        ]);
        $limpieza->save();

        $reforestacion = Voluntariado::query()
            ->where('titulo', 'Jornada de reforestación comunitaria')
            ->first();
        if (! $reforestacion) {
            $reforestacion = Voluntariado::query()->firstOrNew(
                ['titulo' => 'Jornada de restauración de vegetación urbana'],
            );
        }
        if (! $reforestacion->exists) {
            $reforestacion->fill([
                'lugarId' => (string) $parque->_id,
                'organizadorId' => (string) $organizador->_id,
                'cupo' => 25,
                'imagen' => null,
                'estado' => 'publicado',
            ]);
        }
        $reforestacion->titulo = 'Jornada de restauración de vegetación urbana';
        $reforestacion->fill([
            'descripcion' => <<<'CONTENT'
Los espacios verdes urbanos contribuyen al bienestar de la comunidad, ofrecen refugio a distintas especies y ayudan a mejorar las condiciones ambientales de las ciudades.

En esta actividad, las personas voluntarias participarán en labores comunitarias de mantenimiento y cuidado de áreas verdes, con actividades como limpieza del espacio, retiro de residuos y apoyo en el acondicionamiento de zonas destinadas a vegetación.

Cualquier plantación deberá realizarse únicamente cuando exista autorización del responsable del espacio y se cuente con especies adecuadas para las condiciones locales. Se dará prioridad a la vegetación nativa y adaptada al clima árido de Baja California Sur. No se plantarán mangles en este contexto urbano, no se introducirán especies exóticas ni se recolectarán plantas silvestres; esta actividad urbana no constituye restauración ecológica de un ecosistema natural.

Objetivo: promover la participación comunitaria en el cuidado de espacios verdes urbanos y fomentar el conocimiento de la vegetación regional.
CONTENT,
            'fechaInicio' => $fechaReforestacion->copy()->setTime(7, 30),
            'fechaFin' => $fechaReforestacion->copy()->setTime(11, 30),
            'horaInicio' => '07:30',
            'horaFin' => '11:30',
            'requisitos' => [
                'Llevar agua suficiente',
                'Usar ropa cómoda y calzado cerrado',
                'Llevar gorra y protección solar',
                'Seguir las indicaciones del responsable',
            ],
            'materiales' => [
                'Guantes de trabajo',
                'Bolsas para residuos',
                'Herramientas manuales, únicamente si son proporcionadas por los organizadores',
                'Agua para hidratación',
            ],
        ]);
        $reforestacion->save();

        $educacion = Voluntariado::query()->firstOrNew(
            ['titulo' => 'Taller de educación ambiental costera'],
        );
        if (! $educacion->exists) {
            $educacion->fill([
                'lugarId' => (string) $malecon->_id,
                'organizadorId' => (string) $organizador->_id,
                'cupo' => 40,
                'imagen' => null,
                'estado' => 'publicado',
            ]);
        }
        $educacion->fill([
            'descripcion' => <<<'CONTENT'
Las costas de Baja California Sur albergan ecosistemas de gran importancia ecológica, como manglares, playas, humedales y ambientes marinos que sostienen una amplia diversidad de especies.

En este taller, las personas participantes conocerán las características de los ecosistemas costeros, la importancia de la biodiversidad regional y los principales problemas ambientales que afectan a las playas y zonas marinas.

Se abordarán temas como la contaminación por residuos, la conservación de manglares, la protección de la fauna marina y las prácticas de turismo responsable. También se compartirán acciones cotidianas que pueden contribuir a reducir el impacto ambiental de las actividades humanas. Es un taller comunitario y no se presenta como capacitación oficial de CONANP ni de otra institución.

Objetivo: fortalecer los conocimientos ambientales y promover hábitos responsables para la conservación de los ecosistemas costeros.
CONTENT,
            'fechaInicio' => $fechaEducacion->copy()->setTime(9, 0),
            'fechaFin' => $fechaEducacion->copy()->setTime(11, 0),
            'horaInicio' => '09:00',
            'horaFin' => '11:00',
            'requisitos' => [
                'No se requieren conocimientos previos',
                'Llevar libreta y lápiz si se desea tomar apuntes',
                'Llegar puntualmente',
                'Participar respetando las indicaciones del taller',
            ],
            'materiales' => [
                'Material educativo',
                'Libreta',
                'Lápiz',
                'Recursos visuales de apoyo',
            ],
        ]);
        $educacion->save();

        $fechaCupoLleno = now()->addDays(28)->startOfDay();
        $cupoLleno = Voluntariado::firstOrCreate(
            ['titulo' => 'Prueba de inscripción con cupo lleno'],
            [
                'descripcion' => 'Evento de prueba con el único lugar ocupado para verificar el rechazo de nuevas inscripciones.',
                'lugarId' => (string) $playaBalandra->_id,
                'organizadorId' => (string) $organizador->_id,
                'fechaInicio' => $fechaCupoLleno->copy()->setTime(8, 0),
                'fechaFin' => $fechaCupoLleno->copy()->setTime(10, 0),
                'horaInicio' => '08:00',
                'horaFin' => '10:00',
                'cupo' => 1,
                'requisitos' => ['Inscripción de prueba para validar el límite de cupo.'],
                'materiales' => [],
                'imagen' => null,
                'estado' => 'publicado',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Inscripciones
        |--------------------------------------------------------------------------
        | Cada inscripción apunta a un usuario y un voluntariado existentes.
        */

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $limpieza->_id,
            ],
            [
                'claveRegistroActiva' => (string) $usuario1->_id.'|'.(string) $limpieza->_id,
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Inscripción de prueba para la jornada de limpieza.',
            ]
        );

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario2->_id,
                'voluntariadoId' => (string) $limpieza->_id,
            ],
            [
                'claveRegistroActiva' => (string) $usuario2->_id.'|'.(string) $limpieza->_id,
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Participación de prueba en la jornada de limpieza.',
            ]
        );

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $reforestacion->_id,
            ],
            [
                'claveRegistroActiva' => (string) $usuario1->_id.'|'.(string) $reforestacion->_id,
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Inscripción de prueba para reforestación.',
            ]
        );

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $cupoLleno->_id,
            ],
            [
                'claveRegistroActiva' => (string) $usuario1->_id.'|'.(string) $cupoLleno->_id,
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Lugar ocupado para probar el rechazo por cupo lleno.',
            ]
        );

        $this->syncActiveCupo($limpieza);
        $this->syncActiveCupo($reforestacion);
        $this->syncActiveCupo($educacion);
        $this->syncActiveCupo($cupoLleno);

        DB::purge('mongodb');
        DB::reconnect('mongodb');

        if (empty($limpieza->imagen) && empty($limpieza->imagenes)) {
            $this->replaceImage($limpieza, 'limpieza-playa.png', 'voluntariados');
        }
        if (empty($reforestacion->imagen) && empty($reforestacion->imagenes)) {
            $this->replaceImage($reforestacion, 'reforestacion-manglar.png', 'voluntariados');
        }
        if (empty($educacion->imagen) && empty($educacion->imagenes)) {
            $this->replaceImage($educacion, 'limpieza-playa.png', 'voluntariados');
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Flora y Fauna
        |--------------------------------------------------------------------------
        */

        $mangleRojo = FloraFauna::query()->firstOrNew([
            'nombreCientifico' => 'Rhizophora mangle',
        ]);
        if (! $mangleRojo->exists) {
            $mangleRojo->creadoPor = new ObjectId((string) $organizador->_id);
            $mangleRojo->estado = 'publicado';
            $mangleRojo->imagenes = [];
        }
        $mangleRojo->fill([
            'nombre' => 'Mangle rojo',
            'nombreCientifico' => 'Rhizophora mangle',
            'tipo' => 'flora',
            'descripcion' => 'El mangle rojo es un árbol de manglar adaptado a ambientes salinos e intermareales. Sus raíces aéreas ayudan a retener sedimentos y forman una estructura compleja en la franja costera.'."\n\n".
                'En Baja California Sur, los manglares conectan ambientes terrestres y marinos y ofrecen refugio y zonas de crianza para organismos costeros. Su conservación depende de mantener íntegros los humedales y la calidad del agua.',
            'habitat' => 'Manglares, esteros y zonas intermareales salobres o salinas de las costas.',
            'estadoConservacion' => 'Amenazada (A)',
            'ubicacion' => [
                'nombre' => 'Distribución costera tropical y subtropical; presencia documentada en Baja California Sur. No se asigna un sitio puntual.',
            ],
            'lugaresRelacionados' => [],
            'fuenteInformacion' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/166804-rhizophora-mangle; DOF, NOM-059 (14-11-2019): https://dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
        ]);
        $mangleRojo->save();

        $tortugasNegras = FloraFauna::query()
            ->whereIn('nombreCientifico', ['Chelonia agassizii', 'Chelonia mydas'])
            ->get();
        if ($tortugasNegras->count() > 1) {
            throw new \RuntimeException('Hay más de un registro para la tortuga negra; resuelve la duplicidad antes de sembrar.');
        }

        $tortugaNegra = $tortugasNegras->first()
            ?? FloraFauna::query()->firstOrNew(['nombre' => 'Tortuga Negra']);
        if (! $tortugaNegra->exists) {
            $tortugaNegra->creadoPor = new ObjectId((string) $organizador->_id);
            $tortugaNegra->estado = 'publicado';
            $tortugaNegra->imagenes = [];
        }
        $tortugaNegra->fill([
            'nombre' => 'Tortuga negra o prieta',
            'nombreCientifico' => 'Chelonia mydas',
            'tipo' => 'fauna',
            'descripcion' => 'La tortuga negra o prieta es la forma del Pacífico oriental de la tortuga verde, una tortuga marina que consume principalmente pastos marinos y algas. El nombre Chelonia agassizii se ha usado para esta forma, pero EncicloVida lo registra como sinónimo de Chelonia mydas; su reconocimiento como especie independiente es controvertido.'."\n\n".
                'En el Pacífico mexicano, la tortuga negra tiene importancia ecológica y cultural. Esta ficha no señala una playa de anidación ni un punto de observación específico en Baja California Sur.',
            'habitat' => 'Aguas marinas tropicales y subtropicales del Pacífico oriental; zonas costeras y áreas con pastos marinos y algas.',
            'estadoConservacion' => 'En peligro de extinción (P)',
            'ubicacion' => [
                'nombre' => 'Distribución marina del Pacífico oriental; no se asigna una playa o localidad puntual de Baja California Sur.',
            ],
            'lugaresRelacionados' => [],
            'fuenteInformacion' => 'EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/27176-chelonia-mydas; CONANP PACE (2019): https://www.gob.mx/conanp/documentos/programa-de-accion-para-la-conservacion-de-la-especie-tortuga-verde-negra-chelonia-mydas',
        ]);
        $tortugaNegra->save();

        if (empty($mangleRojo->imagenes)) {
            $this->replaceImages($mangleRojo, 'manglar-rojo.png', 'flora-fauna');
        }
        if (empty($tortugaNegra->imagenes)) {
            $this->replaceImages($tortugaNegra, 'tortuga-negra.png', 'flora-fauna');
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Artículos Ambientales
        |--------------------------------------------------------------------------
        */

        $articulo1 = Articulo::query()->firstOrNew(
            ['titulo' => 'La importancia de la restauración de manglares en la Bahía de La Paz'],
        );
        if (! $articulo1->exists) {
            $articulo1->fill([
                'autorId' => (string) $organizador->_id,
                'imagen' => null,
                'categoria' => 'Conservación',
                'estado' => 'publicado',
                'fechaPublicacion' => now(),
                'fechaActualizacion' => now(),
            ]);
        }
        $articulo1->contenido = <<<'CONTENT'
En la Bahía de La Paz, los manglares forman una franja viva entre el mar y la tierra. En un estado de clima árido, estos humedales sostienen hábitats costeros y ayudan a amortiguar algunos efectos de la erosión y el oleaje. Restaurarlos significa recuperar las condiciones que permiten que el ecosistema funcione, no simplemente plantar árboles.

Cómo funciona un manglar
Los manglares son comunidades de árboles y arbustos tolerantes a la sal que crecen en la zona de influencia de las mareas. Sus raíces y la vegetación asociada crean una estructura compleja donde se retienen sedimentos y materia orgánica. La mezcla de agua marina, escurrimientos y aportes de nutrientes cambia con las mareas y las lluvias, por lo que el manglar depende de un intercambio de agua y de un suelo apropiado.

Refugio, alimento y protección costera
Las raíces ofrecen refugio y áreas de alimentación a peces juveniles, crustáceos, moluscos y otros organismos costeros; la hojarasca y el material orgánico sostienen redes alimentarias que se extienden a esteros y aguas cercanas. Estos hábitats pueden contribuir a actividades pesqueras al favorecer etapas tempranas de especies aprovechadas, aunque la productividad de una pesquería depende de muchos factores y no se garantiza por la presencia de manglar. La vegetación también retiene sedimentos y ayuda a reducir la energía de las olas y la erosión en determinados sitios.

Qué los degrada
La modificación de canales y escurrimientos, los rellenos, la urbanización y otras obras costeras pueden cambiar la duración o frecuencia de inundación, la salinidad y el movimiento del agua. La contaminación y el pisoteo también deterioran el hábitat. En una zona árida, una alteración local del aporte de agua puede tener efectos importantes sobre un humedal que depende del equilibrio entre mareas, lluvias y escorrentía.

Restaurar es recuperar procesos
Antes de plantar, es necesario identificar por qué se perdió o degradó el manglar y recuperar, cuando corresponda, el flujo de agua y el hidroperiodo propios del sitio. También se deben considerar la elevación del terreno, la salinidad, la disponibilidad de propágulos y la vegetación adecuada para cada ambiente. La plantación aislada, sin corregir las causas hidrológicas, puede tener baja supervivencia y no restablece por sí sola el funcionamiento del ecosistema. El seguimiento posterior permite evaluar si regresan las condiciones y la vegetación esperadas.

Cómo participar responsablemente
La comunidad puede apoyar evitando arrojar residuos o rellenar zonas húmedas, respetando la vegetación y los canales, reportando daños a las autoridades competentes y participando en acciones de restauración organizadas por personal con conocimiento del sitio. En limpiezas o actividades de plantación conviene seguir el plan de trabajo y no abrir canales, podar ni introducir plantas por cuenta propia.

La restauración de los manglares de la Bahía de La Paz requiere proteger tanto el humedal como los escurrimientos que lo conectan con su cuenca. El trabajo sostenido, basado en las condiciones ecológicas locales y acompañado por las comunidades, ofrece una oportunidad más sólida de conservar estos ambientes para la biodiversidad y las generaciones futuras.

Fuentes consultadas
CONANP, “Manglares de México: soluciones naturales al cambio climático” (17-12-2020): https://www.gob.mx/conanp/articulos/manglares-de-mexico-soluciones-naturales-al-cambio-climatico
Convención sobre los Humedales, ficha Ramsar 1816, Humedales Mogote–Ensenada La Paz: https://rsis.ramsar.org/ris/1816
CONTENT;
        $articulo1->save();

        $articulo2 = Articulo::query()->firstOrNew(
            ['titulo' => 'Guía práctica para reducir residuos plásticos en las playas de BCS'],
        );
        if (! $articulo2->exists) {
            $articulo2->fill([
                'autorId' => (string) $organizador->_id,
                'imagen' => null,
                'categoria' => 'Educación Ambiental',
                'estado' => 'publicado',
                'fechaPublicacion' => now(),
                'fechaActualizacion' => now(),
            ]);
        }
        $articulo2->contenido = <<<'CONTENT'
Las playas de Baja California Sur reciben residuos que llegan desde las ciudades, actividades recreativas, pesca y navegación, además de materiales transportados por arroyos, viento y corrientes marinas. Una botella o envoltura abandonada puede persistir en el ambiente y fragmentarse; por eso, reducir lo que llevamos es más eficaz que confiar únicamente en recogerlo después.

Prevenir, reutilizar y reciclar
Prevenir significa evitar generar residuos desde el inicio: elegir productos con menos empaque, rechazar artículos desechables cuando no se necesitan y planear la visita para llevar solo lo indispensable. Reutilizar es dar varios usos a una botella, bolsa o recipiente durable, siempre que se mantenga limpio y en condiciones seguras. Reciclar consiste en recuperar materiales mediante un sistema que los acepte; no todos los plásticos son reciclables en todas las localidades y la posibilidad depende del tipo de material, su limpieza y la infraestructura disponible.

Antes de ir a la playa
Prepara agua en un recipiente rellenable, alimentos en contenedores reutilizables y una bolsa para regresar con tus residuos. Evita comprar productos con envolturas innecesarias y verifica de antemano dónde podrás disponer de la basura. No asumas que habrá contenedores o recolección en una playa remota.

Durante la visita
Mantén juntos tus residuos y no entierres colillas, tapas o envolturas en la arena. Asegura los objetos ligeros para que el viento no los disperse, y no dejes residuos ni restos de comida en la costa. Si encuentras basura peligrosa, como vidrio roto, objetos punzantes o materiales desconocidos, no los manipules sin equipo y orientación adecuados.

Al terminar
Lleva lo recolectado hasta un punto de disposición disponible y separa los materiales solo cuando exista un sistema que efectivamente los reciba. Evita dejar bolsas llenas junto a contenedores desbordados o en sitios sin servicio de recolección; consulta las indicaciones locales para su manejo.

Limpiezas comunitarias seguras
Una jornada organizada puede retirar residuos y documentar problemas, pero no sustituye la prevención ni corrige por sí misma las fuentes que los generan. Las personas organizadoras deben establecer un área y horario de trabajo, contar con guantes y herramientas apropiadas, separar residuos conforme a las posibilidades locales y prever la entrega final a un servicio de manejo. No se recomienda remover vegetación, alterar dunas, manipular fauna ni abrir nidos o mover organismos durante una limpieza.

Las playas saludables dependen de decisiones repetidas antes, durante y después de cada visita, así como de una gestión adecuada de residuos por parte de comunidades e instituciones. Reducir los desechables y participar de manera segura en iniciativas locales ayuda a que menos basura alcance los ecosistemas costeros de Baja California Sur.

Fuentes consultadas
SEMARNAT, “Prevención y gestión integral de los residuos”: https://www.gob.mx/semarnat/acciones-y-programas/prevencion-y-gestion-integral-de-los-residuos
SEMARNAT, Programa Nacional para la Prevención y Gestión Integral de los Residuos 2022–2024: https://dsiappsdev.semarnat.gob.mx/datos/portal/publicaciones/2022/PNPGIR_2022.pdf
CONTENT;
        $articulo2->save();

        if (empty($articulo1->imagen) && empty($articulo1->imagenes)) {
            $this->replaceImage($articulo1, 'manglar.png', 'articulos');
        }
        if (empty($articulo2->imagen) && empty($articulo2->imagenes)) {
            $this->replaceImage($articulo2, 'costas-lapaz.jpg', 'articulos');
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Asistencias
        |--------------------------------------------------------------------------
        */

        Asistencia::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $limpieza->_id,
            ],
            [
                'fecha' => now(),
                'hora' => '08:15:00',
                'metodo' => 'QR',
                'confirmada' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Notificaciones
        |--------------------------------------------------------------------------
        */

        Notificacion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'titulo' => 'Inscripción confirmada',
            ],
            [
                'tipo' => 'inscripcion_confirmada',
                'mensaje' => 'Te has inscrito exitosamente en la Jornada de limpieza de Playa Balandra.',
                'entidad' => [
                    'entidadtipo' => 'voluntariado',
                    'id' => (string) $limpieza->_id,
                ],
                'prioridad' => 'normal',
                'leida' => true,
                'fechaCreacion' => now()->subDays(1),
                'fechaLectura' => now()->subHours(12),
                'fechaExpiracion' => null,
                'datos' => [
                    'voluntariadoTitulo' => $limpieza->titulo,
                ],
            ]
        );

        Notificacion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'titulo' => 'Asistencia registrada',
            ],
            [
                'tipo' => 'asistencia_registrada',
                'mensaje' => 'Tu asistencia ha sido confirmada mediante código QR. ¡Gracias por tu participación!',
                'entidad' => [
                    'entidadtipo' => 'voluntariado',
                    'id' => (string) $limpieza->_id,
                ],
                'prioridad' => 'alta',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => [
                    'metodo' => 'QR',
                ],
            ]
        );

        Notificacion::firstOrCreate(
            [
                'usuarioId' => (string) $organizador->_id,
                'titulo' => 'Artículo publicado',
            ],
            [
                'tipo' => 'articulo_aprobado',
                'mensaje' => 'Tu artículo "La importancia de la restauración de manglares" ha sido aprobado y publicado.',
                'entidad' => [
                    'entidadtipo' => 'articulo',
                    'id' => (string) $articulo1->_id,
                ],
                'prioridad' => 'normal',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => [],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Auditoría
        |--------------------------------------------------------------------------
        */

        Auditoria::firstOrCreate(
            [
                'usuarioId' => (string) $organizador->_id,
                'accion' => 'verificacion_organizador',
            ],
            [
                'entidad' => 'usuarios',
                'entidadId' => (string) $organizador->_id,
                'fecha' => now()->subDays(2),
                'ip' => '127.0.0.1',
                'detalles' => [
                    'motivo' => 'Verificación inicial de cuenta de organizador.',
                    'aprobadoPor' => 'sistema',
                ],
            ]
        );

        Auditoria::firstOrCreate(
            [
                'usuarioId' => (string) $organizador->_id,
                'accion' => 'crear_voluntariado',
            ],
            [
                'entidad' => 'voluntariados',
                'entidadId' => (string) $limpieza->_id,
                'fecha' => now()->subDays(1),
                'ip' => '127.0.0.1',
                'detalles' => [
                    'titulo' => $limpieza->titulo,
                    'lugarId' => (string) $playaBalandra->_id,
                ],
            ]
        );

        $this->command?->info('Seeder de Regenera BCS ejecutado correctamente.');
        $this->command?->info('Se verificaron usuarios, lugares, voluntariados e inscripciones de prueba.');
    }

    private function replaceImage(object $model, string $filename, string $directory): void
    {
        $imagePath = database_path('seeders/imagenes/'.$filename);

        if (! is_file($imagePath)) {
            throw new \RuntimeException("No se encontró la imagen del seeder: {$imagePath}");
        }

        $previousPath = $model->imagen;
        $newPath = $this->uploadImage($imagePath, $filename, $directory);

        $model->imagen = $newPath;
        $model->save();

        if (
            is_string($previousPath)
            && $previousPath !== ''
            && ! filter_var($previousPath, FILTER_VALIDATE_URL)
            && $previousPath !== $newPath
        ) {
            Storage::disk('gridfs')->delete($previousPath);
        }
    }

    private function syncActiveCupo(Voluntariado $voluntariado): void
    {
        $inscripciones = Inscripcion::where('voluntariadoId', (string) $voluntariado->getKey())
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->get();

        $voluntariado->forceFill([
            'inscritosActivos' => $inscripciones->count(),
            'reservasCupo' => $inscripciones
                ->map(fn (Inscripcion $inscripcion) => (string) $inscripcion->getKey())
                ->values()
                ->all(),
        ])->save();
    }

    private function replaceImages(object $model, string $filename, string $directory): void
    {
        $storedImages = $model->imagenes ?? null;
        $previousPaths = is_array($storedImages)
            ? $storedImages
            : (is_iterable($storedImages) ? iterator_to_array($storedImages) : []);
        $previousPaths = array_values(array_filter(
            $previousPaths,
            fn ($path) => is_string($path) && $path !== '',
        ));
        $imagePath = database_path('seeders/imagenes/'.$filename);

        if (! is_file($imagePath)) {
            throw new \RuntimeException("No se encontró la imagen del seeder: {$imagePath}");
        }

        $newPath = $this->uploadImage($imagePath, $filename, $directory);
        $model->imagenes = [$newPath];
        $model->save();

        foreach ($previousPaths as $previousPath) {
            if (! filter_var($previousPath, FILTER_VALIDATE_URL) && $previousPath !== $newPath) {
                Storage::disk('gridfs')->delete($previousPath);
            }
        }
    }

    private function uploadImage(string $imagePath, string $filename, string $directory): string
    {
        $newPath = Storage::disk('gridfs')->putFile($directory, new File($imagePath));

        if (! is_string($newPath)) {
            throw new \RuntimeException("No se pudo subir la imagen del seeder: {$filename}");
        }

        return $newPath;
    }
}
