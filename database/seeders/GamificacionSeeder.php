<?php

namespace Database\Seeders;

use App\Models\Cuestionario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GamificacionSeeder extends Seeder
{
    public function run(): void
    {
        $quizzes = [
            [
                'slug' => 'seguridad-limpieza',
                'version' => 1,
                'titulo' => 'Seguridad y precauciones en limpieza',
                'descripcion' => 'Aprende a reconocer riesgos y protegerte durante actividades costeras y de manglar.',
                'guia' => 'Antes de una limpieza, prepara guantes adecuados, calzado cerrado y protección solar. No manipules jeringas, químicos, tambores ni fauna o nidos; conserva distancia, avisa al organizador y comunica la ubicación del riesgo.',
                'audiencia' => ['usuario'],
                'minimoAprobacion' => 75,
                'puntosAprobacion' => 20,
                'medalla' => ['slug' => 'voluntario-seguro', 'nombre' => 'Voluntario Seguro', 'descripcion' => 'Conoces las precauciones básicas antes de participar en una limpieza.', 'icono' => 'shield-check'],
                'preguntas' => [
                    ['id' => 'equipo', 'texto' => '¿Qué equipo debes utilizar al recoger residuos en una playa o manglar?', 'opciones' => ['Guantes adecuados, calzado cerrado y protección solar.', 'Sandalias y las manos descubiertas para trabajar con rapidez.', 'Solo una gorra, sin importar el tipo de residuo.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Los guantes adecuados, el calzado cerrado y la protección solar reducen riesgos de cortes, exposición y calor.'],
                    ['id' => 'residuo-peligroso', 'texto' => 'Encuentras una jeringa o un envase con químicos. ¿Qué haces?', 'opciones' => ['No tocarlo, mantener distancia, reportarlo al organizador y comunicar su ubicación.', 'Recogerlo con una bolsa común y llevarlo con el resto de residuos.', 'Enjuagarlo en el mar para identificar su contenido.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Los residuos peligrosos no deben manipularse. Aléjate y avisa al organizador para que gestione el reporte correspondiente.'],
                    ['id' => 'tambor', 'texto' => 'Encuentras un tambor pesado varado en la playa. ¿Intentas moverlo?', 'opciones' => ['No. Déjalo en su lugar y repórtalo al organizador.', 'Sí, si varias personas lo levantan juntas.', 'Ruédalo hasta el agua para despejar la playa.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Un tambor puede contener sustancias peligrosas o ser inestable. No lo muevas y reporta su ubicación.'],
                    ['id' => 'nido-tortuga', 'texto' => 'Encuentras un nido de tortuga marina. ¿Qué debes hacer?', 'opciones' => ['No acercarte ni tocarlo y avisar al organizador para proteger la zona.', 'Marcarlo con objetos alrededor y tomarlo para revisarlo.', 'Retirar la arena para confirmar si hay huevos.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Mantener distancia evita perturbar el nido. El organizador puede coordinar medidas de protección sin manipularlo.'],
                ],
            ],
            [
                'slug' => 'restauracion-entorno',
                'version' => 1,
                'titulo' => 'Restauración y conocimiento del entorno',
                'descripcion' => 'Reconoce el valor de los ecosistemas y las especies costeras de Baja California Sur.',
                'guia' => 'Repasa las funciones de manglares y dunas, la protección frente a la erosión y el papel de las playas como áreas de alimentación y descanso para aves migratorias.',
                'audiencia' => ['usuario'],
                'minimoAprobacion' => 66.67,
                'puntosAprobacion' => 20,
                'medalla' => ['slug' => 'guardian-manglar', 'nombre' => 'Guardián del Manglar', 'descripcion' => 'Aprendiste sobre ecosistemas y conservación de BCS.', 'icono' => 'mangrove'],
                'preguntas' => [
                    ['id' => 'carbono-azul', 'texto' => '¿Qué ecosistema de Bahía Magdalena es importante para capturar carbono y proteger la costa?', 'opciones' => ['Los manglares.', 'Los estacionamientos costeros.', 'Las superficies sin vegetación.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Los manglares almacenan carbono y sus raíces ayudan a amortiguar oleaje y estabilizar la costa.'],
                    ['id' => 'dunas', 'texto' => 'Si limpiamos una playa, pero no restauramos la vegetación de dunas, ¿qué problema persiste?', 'opciones' => ['La erosión costera y la pérdida de hábitat para aves.', 'La acumulación de nieve en la playa.', 'La desaparición de las mareas.'], 'respuestaCorrecta' => 0, 'explicacion' => 'La vegetación de dunas estabiliza arena y brinda hábitat; retirar residuos por sí solo no recupera esas funciones.'],
                    ['id' => 'playero-rojizo', 'texto' => '¿Por qué es importante mantener las playas limpias para el playero rojizo?', 'opciones' => ['Utiliza estas zonas para alimentarse y descansar durante su migración.', 'Solo vive en árboles de manglar durante todo el año.', 'La limpieza aumenta la temperatura del agua que necesita.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Las playas forman parte de sus áreas de alimentación y descanso durante sus largos desplazamientos migratorios.'],
                ],
            ],
            [
                'slug' => 'economia-circular',
                'version' => 1,
                'titulo' => 'Economía circular y conducta regenerativa',
                'descripcion' => 'Explora decisiones para prevenir residuos y cerrar ciclos de materiales.',
                'guia' => 'Considera primero la prevención: rechazar, reducir, reutilizar y reparar antes de reciclar. Aprende cómo el compostaje controlado devuelve materia orgánica al suelo y cómo evitar residuos secundarios en limpiezas.',
                'audiencia' => ['usuario', 'organizador', 'admin'],
                'minimoAprobacion' => 66.67,
                'puntosAprobacion' => 20,
                'medalla' => ['slug' => 'agente-circular', 'nombre' => 'Agente Circular', 'descripcion' => 'Conoces principios de prevención y aprovechamiento de residuos.', 'icono' => 'recycle'],
                'preguntas' => [
                    ['id' => 'jerarquia', 'texto' => '¿Cuál es el orden correcto de la jerarquía de residuos?', 'opciones' => ['Rechazar, reducir, reutilizar, reparar y reciclar.', 'Reciclar, desechar, comprar y reemplazar.', 'Enterrar, quemar, mezclar y transportar.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Prevenir residuos primero suele conservar más recursos; después se prioriza reducir, reutilizar, reparar y reciclar.'],
                    ['id' => 'organicos', 'texto' => '¿Qué debe hacerse con los residuos orgánicos?', 'opciones' => ['Compostarlos para reintegrarlos de manera segura al ciclo biológico.', 'Mezclarlos con residuos peligrosos.', 'Dejarlos en la playa para que la marea los distribuya.'], 'respuestaCorrecta' => 0, 'explicacion' => 'El compostaje controlado puede devolver nutrientes al suelo y reducir residuos enviados a disposición final.'],
                    ['id' => 'bolsas', 'texto' => '¿Por qué utilizar bolsas desechables de plástico en una limpieza de playa es contradictorio?', 'opciones' => ['Pueden generar residuos adicionales que terminen en el mar; es preferible usar sacos reutilizables adecuados.', 'Porque el plástico siempre se disuelve en agua salada.', 'Porque las bolsas desechables hacen que los residuos sean reciclables automáticamente.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Los materiales de un solo uso pueden escapar al entorno. Contenedores reutilizables adecuados reducen residuos secundarios.'],
                ],
            ],
            [
                'slug' => 'organizacion-responsable',
                'version' => 1,
                'titulo' => 'Verificación para organizadores',
                'descripcion' => 'Conoce las responsabilidades para coordinar actividades ambientales seguras y respetuosas.',
                'guia' => 'Antes de organizar, identifica autoridades y jurisdicción del sitio, planifica transporte y entrega de residuos a un destino autorizado, e incluye una inducción sobre conservación y actividades comunitarias locales.',
                'audiencia' => ['organizador'],
                'requiereOrganizadorVerificado' => true,
                'minimoAprobacion' => 66.67,
                'puntosAprobacion' => 30,
                'medalla' => ['slug' => 'lider-responsable', 'nombre' => 'Líder Responsable', 'descripcion' => 'Completaste la certificación educativa para organizar actividades.', 'icono' => 'leader'],
                'preguntas' => [
                    ['id' => 'autoridades', 'texto' => '¿Qué autoridades pueden intervenir en la autorización o coordinación de una limpieza de playa en BCS?', 'opciones' => ['ZOFEMAT, autoridades de áreas naturales protegidas cuando corresponda y autoridades municipales competentes.', 'Únicamente el equipo de voluntarios, sin consultar autoridades.', 'Solo comercios cercanos, en todos los casos.'], 'respuestaCorrecta' => 0, 'explicacion' => 'La coordinación depende del sitio y su jurisdicción; deben consultarse las autoridades competentes, incluidas las de áreas protegidas cuando aplique.'],
                    ['id' => 'destino-residuos', 'texto' => '¿Qué debe pasar con los residuos recolectados?', 'opciones' => ['Entregarse al sistema municipal de gestión de residuos o a un centro de acopio autorizado.', 'Dejarse junto a un contenedor lleno para que alguien más los retire.', 'Enterrarse en la zona de limpieza.'], 'respuestaCorrecta' => 0, 'explicacion' => 'Planear el destino y transporte evita que los residuos vuelvan al ambiente y permite una gestión adecuada.'],
                    ['id' => 'refugio-pesquero', 'texto' => '¿Qué se debe explicar a los voluntarios cerca de una zona de refugio pesquero?', 'opciones' => ['La importancia de respetar la zona, sus objetivos de conservación y las actividades pesqueras locales.', 'Que pueden ingresar a cualquier área si llevan equipo de limpieza.', 'Que la actividad pesquera local debe detenerse durante todo el año.'], 'respuestaCorrecta' => 0, 'explicacion' => 'La inducción debe promover respeto por las reglas del refugio, sus objetivos y las comunidades pesqueras locales.'],
                ],
            ],
        ];

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
            $quiz['activo'] = true;
            Cuestionario::updateOrCreate(
                ['slug' => $quiz['slug'], 'version' => $quiz['version']],
                $quiz,
            );
        }

        $this->command?->info('Cuatro cuestionarios educativos e índices únicos de gamificación verificados.');
    }
}