<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\FloraFauna;
use App\Models\Lugar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\ObjectId;
use RuntimeException;

class RegeneraCatalogSeeder extends Seeder
{
    private const IMAGE_DIRECTORY = 'catalogo-v1';

    public function run(): void
    {
        $catalog = $this->imageCatalog();
        $imagePaths = $this->storeImages($catalog);
        $places = $this->seedPlaces($catalog, $imagePaths);

        $this->seedSpecies($catalog, $imagePaths, $places);
        $this->syncAdditionalSpeciesPlaces($places);
        $this->seedArticles($catalog, $imagePaths);

        $this->command?->info('Catálogo inicial: 10 lugares, 10 especies y 6 artículos; fichas de especies sincronizadas por nombre científico.');
    }

    private function storeImages(array $catalog): array
    {
        $disk = Storage::disk('gridfs');
        $paths = [];

        foreach ($catalog as $key => $image) {
            $localPath = database_path('seeders/imagenes/catalogo/'.$image['filename']);
            if (! is_file($localPath)) {
                throw new RuntimeException("No se encontró la imagen local {$image['filename']}.");
            }

            $imageInfo = getimagesize($localPath);
            if (! is_array($imageInfo) || ($imageInfo['mime'] ?? null) !== 'image/jpeg') {
                throw new RuntimeException("La imagen {$image['filename']} no es un JPEG válido.");
            }

            if (max($imageInfo[0], $imageInfo[1]) > 1280) {
                throw new RuntimeException("La imagen {$image['filename']} excede el tamaño optimizado de 1280 píxeles.");
            }

            $storedPath = self::IMAGE_DIRECTORY.'/'.$key.'.jpg';
            if (! $disk->exists($storedPath)) {
                $contents = file_get_contents($localPath);
                if ($contents === false) {
                    throw new RuntimeException("No se pudo leer la imagen {$image['filename']}.");
                }

                $stored = $disk->put($storedPath, $contents, [
                    'mimetype' => 'image/jpeg',
                    'metadata' => [
                        'originalFilename' => $image['originalFilename'],
                        'author' => $image['author'],
                        'sourcePage' => $image['sourcePage'],
                        'originalUrl' => $image['originalUrl'],
                        'attributionUrl' => $image['sourcePage'],
                        'license' => $image['license'],
                        'licenseUrl' => $image['licenseUrl'],
                        'modification' => 'Redimensionada y comprimida para uso web.',
                    ],
                ]);

                if (! $stored) {
                    throw new RuntimeException("No se pudo guardar {$image['filename']} en GridFS.");
                }
            }

            $paths[$key] = $storedPath;
        }

        return $paths;
    }

    private function seedPlaces(array $catalog, array $imagePaths): array
    {
        $places = [];

        foreach ($this->places() as $place) {
            $imageKey = $place['image'];
            $description = $place['description']."\n\nFuente: {$place['source']}";
            if ($place['mapNote']) {
                $description .= "\n".$place['mapNote'];
            }
            if ($imageKey !== null) {
                $description .= "\n".$this->imageCredit($catalog[$imageKey]);
            }

            $record = Lugar::query()->firstOrNew(['nombre' => $place['name']]);
            $attributes = [
                'nombre' => $place['name'],
                'descripcion' => $description,
                'municipio' => $place['municipality'],
                'direccion' => $place['address'],
                'tipo' => $place['type'],
            ];

            if (! $record->exists) {
                $attributes['latitud'] = $place['latitude'];
                $attributes['longitud'] = $place['longitude'];
                $attributes['estado'] = 'activo';
                $attributes['creadoPor'] = null;
            }
            if (! $record->exists || empty($record->imagen)) {
                $attributes['imagen'] = $imageKey === null ? null : $imagePaths[$imageKey];
            }

            $record->fill($attributes);
            $record->save();

            $places[$place['name']] = $record;
        }

        return $places;
    }

    private function syncAdditionalSpeciesPlaces(array $places): void
    {
        $relationships = [
            'Rhizophora mangle' => ['Parque Nacional Bahía de Loreto'],
            'Chelonia mydas' => [
                'Parque Nacional Bahía de Loreto',
                'Parque Nacional Cabo Pulmo',
                'Complejo Lagunar Ojo de Liebre',
            ],
        ];

        foreach ($relationships as $scientificName => $placeNames) {
            $record = FloraFauna::query()
                ->where('nombreCientifico', $scientificName)
                ->first();

            if (! $record) {
                continue;
            }

            $relatedPlaces = [];
            foreach ($placeNames as $placeName) {
                if (! isset($places[$placeName])) {
                    throw new RuntimeException("No se encontró el lugar relacionado {$placeName}.");
                }

                $relatedPlaces[] = new ObjectId((string) $places[$placeName]->_id);
            }

            $record->lugaresRelacionados = $relatedPlaces;
            $record->save();
        }
    }

    private function seedSpecies(array $catalog, array $imagePaths, array $places): void
    {
        foreach ($this->species() as $species) {
            $relatedPlaces = [];
            foreach ($species['relatedPlaces'] as $name) {
                if (! isset($places[$name])) {
                    throw new RuntimeException("No se encontró el lugar relacionado {$name}.");
                }

                $relatedPlaces[] = new ObjectId((string) $places[$name]->_id);
            }

            $sources = [
                $species['source'],
                $species['reference'],
            ];
            if ($species['image'] !== null) {
                $sources[] = $this->imageCredit($catalog[$species['image']]);
            }

            $record = FloraFauna::query()->firstOrNew([
                'nombreCientifico' => $species['scientificName'],
            ]);
            $attributes = [
                'nombre' => $species['name'],
                'nombreCientifico' => $species['scientificName'],
                'tipo' => $species['type'],
                'descripcion' => $species['description'],
                'habitat' => $species['habitat'],
                'estadoConservacion' => $species['conservation'],
                'ubicacion' => ['nombre' => $species['location']],
                'lugaresRelacionados' => $relatedPlaces,
                'fuenteInformacion' => $sources,
            ];

            if (! $record->exists) {
                $attributes['creadoPor'] = null;
                $attributes['estado'] = 'publicado';
            }
            if (! $record->exists || empty($record->imagenes)) {
                $attributes['imagenes'] = $species['image'] === null ? [] : [$imagePaths[$species['image']]];
            }

            $record->fill($attributes);
            $record->save();
        }
    }

    private function seedArticles(array $catalog, array $imagePaths): void
    {
        foreach ($this->articles() as $article) {
            $content = $article['content']."\n\nFuente: {$article['source']}";
            if ($article['image'] !== null) {
                $content .= "\n".$this->imageCredit($catalog[$article['image']]);
            }

            $imagePath = $article['image'] === null ? null : $imagePaths[$article['image']];
            Articulo::query()->firstOrCreate(
                ['titulo' => $article['title']],
                [
                    'autorId' => null,
                    'titulo' => $article['title'],
                    'contenido' => $content,
                    'imagen' => $imagePath,
                    'imagenes' => $imagePath === null ? [] : [$imagePath],
                    'categoria' => $article['category'],
                    'estado' => 'publicado',
                    'fechaPublicacion' => now(),
                    'fechaActualizacion' => now(),
                ],
            );
        }
    }

    private function imageCredit(array $image): string
    {
        $license = $image['licenseUrl']
            ? $image['license'].' ('.$image['licenseUrl'].')'
            : $image['license'];
        $note = $image['note'] ? ' Nota: '.$image['note'] : '';

        return "Crédito de imagen: {$image['originalFilename']}; autor: {$image['author']}; licencia: {$license}; página original y atribución: {$image['sourcePage']}.{$note} Adaptación: redimensionada y comprimida para web.";
    }

    private function imageCatalog(): array
    {
        return [
            'cardon' => [
                'filename' => 'cardon.jpg',
                'originalFilename' => 'Pachycereus pringlei 1702p2.jpg',
                'author' => 'Carlos Valenzuela',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Pachycereus_pringlei_1702p2.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/e/eb/Pachycereus_pringlei_1702p2.jpg',
                'note' => 'Fotografía identificada como Pachycereus pringlei en La Paz.',
            ],
            'pitaya-agria' => [
                'filename' => 'pitaya-agria.jpg',
                'originalFilename' => 'Stenocereus gummosus (5781962565).jpg',
                'author' => 'Amante Darmanin',
                'license' => 'CC BY 2.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by/2.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Stenocereus_gummosus_(5781962565).jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Stenocereus_gummosus_%285781962565%29.jpg',
                'note' => 'La descripción de Commons ubica la fotografía en El Vizcaíno, BCS.',
            ],
            'lomboy' => [
                'filename' => 'lomboy.jpg',
                'originalFilename' => 'Jatropha cinerea 2009-07-20 01.jpg',
                'author' => 'Agnieszka Kwiecień, Nova',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Jatropha_cinerea_2009-07-20_01.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Jatropha_cinerea_2009-07-20_01.jpg',
                'note' => 'Ejemplar cultivado en el Jardín Botánico de la Universidad de Wrocław; imagen de referencia, no tomada en BCS.',
            ],
            'torote-colorado' => [
                'filename' => 'torote-colorado.jpg',
                'originalFilename' => 'Bursera microphylla 93916706.jpg',
                'author' => 'Daniela Gámez',
                'license' => 'CC BY 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Bursera_microphylla_93916706.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Bursera_microphylla_93916706.jpg',
                'note' => 'La localidad de la fotografía no está indicada en Commons.',
            ],
            'palo-adan' => [
                'filename' => 'palo-adan.jpg',
                'originalFilename' => 'Fouquieria diguetii 27367456.jpg',
                'author' => 'Ken-ichi Ueda',
                'license' => 'CC BY 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Fouquieria_diguetii_27367456.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/a/a4/Fouquieria_diguetii_27367456.jpg',
                'note' => 'La localidad de la fotografía no está indicada en Commons.',
            ],
            'tiburon-ballena' => [
                'filename' => 'tiburon-ballena.jpg',
                'originalFilename' => 'Tiburón ballena (Rhincodon typus), La Paz, Baja California, México, 2024-12-20, DD 01.jpg',
                'author' => 'Diego Delso',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Tibur%C3%B3n_ballena_(Rhincodon_typus),_La_Paz,_Baja_California,_M%C3%A9xico,_2024-12-20,_DD_01.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/c/c4/Tibur%C3%B3n_ballena_%28Rhincodon_typus%29%2C_La_Paz%2C_Baja_California%2C_M%C3%A9xico%2C_2024-12-20%2C_DD_01.jpg',
                'note' => 'Tiburón ballena fotografiado en La Paz.',
            ],
            'ballena-gris' => [
                'filename' => 'ballena-gris.jpg',
                'originalFilename' => 'Eschrichtius robustus with calf ALM 1702 p1.jpg',
                'author' => 'Carlos Valenzuela',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Eschrichtius_robustus_with_calf_ALM_1702_p1.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/Eschrichtius_robustus_with_calf_ALM_1702_p1.jpg',
                'note' => 'Ballena gris y cría fotografiadas en Puerto Adolfo López Mateos, BCS.',
            ],
            'lobo-marino' => [
                'filename' => 'lobo-marino.jpg',
                'originalFilename' => 'Lobo marino (Zalophus californianus), La Paz, Baja California, México, 2024-12-20, DD 19.jpg',
                'author' => 'Diego Delso',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Lobo_marino_(Zalophus_californianus),_La_Paz,_Baja_California,_M%C3%A9xico,_2024-12-20,_DD_19.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/a/a0/Lobo_marino_%28Zalophus_californianus%29%2C_La_Paz%2C_Baja_California%2C_M%C3%A9xico%2C_2024-12-20%2C_DD_19.jpg',
                'note' => 'El título indica La Paz y la descripción de Commons indica Cabo San Lucas; ambas localidades están en BCS.',
            ],
            'tortuga-golfina' => [
                'filename' => 'tortuga-golfina.jpg',
                'originalFilename' => 'Olive Ridley Sea Turtle Hatchlings.jpg',
                'author' => 'Bethany McCarter',
                'license' => 'CC BY 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Olive_Ridley_Sea_Turtle_Hatchlings.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/4/4c/Olive_Ridley_Sea_Turtle_Hatchlings.jpg',
                'note' => 'Crías liberadas en San José del Cabo.',
            ],
            'ballena-azul' => [
                'filename' => 'ballena-azul.jpg',
                'originalFilename' => 'Balaenoptera musculus NOAA.jpg',
                'author' => 'NOAA / U.S. National Marine Fisheries Service',
                'license' => 'Dominio público',
                'licenseUrl' => null,
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Balaenoptera_musculus_NOAA.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/9/97/Balaenoptera_musculus_NOAA.jpg',
                'note' => 'La fotografía identifica la especie; Commons no documenta una localidad en BCS.',
            ],
            'balandra' => [
                'filename' => 'balandra.jpg',
                'originalFilename' => 'Balandra Beach, La Paz (28902320157).jpg',
                'author' => 'Nathan Dugal',
                'license' => 'CC BY 2.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by/2.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Balandra_Beach,_La_Paz_(28902320157).jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/7/72/Balandra_Beach%2C_La_Paz_%2828902320157%29.jpg',
                'note' => 'Playa Balandra, La Paz.',
            ],
            'espiritu-santo' => [
                'filename' => 'espiritu-santo.jpg',
                'originalFilename' => 'Isla Espíritu Santo and Isla Partida (5378264191).jpg',
                'author' => 'Shawn from Airdrie, Canada',
                'license' => 'CC BY-SA 2.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Isla_Esp%C3%ADritu_Santo_and_Isla_Partida_(5378264191).jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/4/4a/Isla_Esp%C3%ADritu_Santo_and_Isla_Partida_%285378264191%29.jpg',
                'note' => 'Islas Espíritu Santo y Partida, cerca de La Paz.',
            ],
            'sierra-la-laguna' => [
                'filename' => 'sierra-la-laguna.jpg',
                'originalFilename' => 'Sierra de la laguna, zona nucelo.jpg',
                'author' => 'German Ortiz Landa 7',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Sierra_de_la_laguna,_zona_nucelo.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/6/66/Sierra_de_la_laguna%2C_zona_nucelo.jpg',
                'note' => 'Valle de Sierra La Laguna.',
            ],
            'laguna-san-ignacio' => [
                'filename' => 'laguna-san-ignacio.jpg',
                'originalFilename' => 'Big Spy Hop, Laguna San Ignacio.jpg',
                'author' => 'Joe McKenna',
                'license' => 'CC BY 2.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by/2.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Big_Spy_Hop,_Laguna_San_Ignacio.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/8/83/Big_Spy_Hop%2C_Laguna_San_Ignacio.jpg',
                'note' => 'Ballena gris fotografiada en la Laguna San Ignacio, BCS.',
            ],
            'el-vizcaino' => [
                'filename' => 'el-vizcaino.jpg',
                'originalFilename' => 'El Vizcaíno Biosphere Reserve Landsat picture annotated.jpg',
                'author' => 'NASA Earth Observatory; Jesse Allen and Robert Simmon, using USGS Landsat data; caption by Michon Scott',
                'license' => 'Dominio público',
                'licenseUrl' => null,
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:El_Vizca%C3%ADno_Biosphere_Reserve_Landsat_picture_annotated.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/2/29/El_Vizca%C3%ADno_Biosphere_Reserve_Landsat_picture_annotated.jpg',
                'note' => 'Imagen satelital de El Vizcaíno que incluye la Laguna Ojo de Liebre.',
            ],
            'cabo-pulmo' => [
                'filename' => 'cabo-pulmo.jpg',
                'originalFilename' => 'Pez mariposa barbero (Johnrandallia nigrirostris), Cabo Pulmo, Baja California, México, 2024-12-19, DD 19.jpg',
                'author' => 'Diego Delso',
                'license' => 'CC BY-SA 4.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Pez_mariposa_barbero_(Johnrandallia_nigrirostris),_Cabo_Pulmo,_Baja_California,_M%C3%A9xico,_2024-12-19,_DD_19.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Pez_mariposa_barbero_%28Johnrandallia_nigrirostris%29%2C_Cabo_Pulmo%2C_Baja_California%2C_M%C3%A9xico%2C_2024-12-19%2C_DD_19.jpg',
                'note' => 'Pez fotografiado en Cabo Pulmo; la descripción lo identifica como habitante del arrecife.',
            ],
            'ojo-de-liebre' => [
                'filename' => 'ojo-de-liebre.jpg',
                'originalFilename' => 'Laguna Ojo de Liebre, Mexico.jpg',
                'author' => 'NASA',
                'license' => 'Dominio público',
                'licenseUrl' => null,
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Laguna_Ojo_de_Liebre,_Mexico.jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/e/eb/Laguna_Ojo_de_Liebre%2C_Mexico.jpg',
                'note' => 'Imagen satelital de la Laguna Ojo de Liebre.',
            ],
            'islas-golfo-california' => [
                'filename' => 'islas-golfo-california.jpg',
                'originalFilename' => 'Golfo de California, Baja California Sur (21095853975).jpg',
                'author' => 'Comisión Mexicana de Filmaciones',
                'license' => 'CC BY-SA 2.0',
                'licenseUrl' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'sourcePage' => 'https://commons.wikimedia.org/wiki/File:Golfo_de_California,_Baja_California_Sur_(21095853975).jpg',
                'originalUrl' => 'https://upload.wikimedia.org/wikipedia/commons/4/41/Golfo_de_California%2C_Baja_California_Sur_%2821095853975%29.jpg',
                'note' => 'La descripción de Commons identifica la Isla San José, BCS.',
            ],
        ];
    }

    private function places(): array
    {
        return [
            [
                'name' => 'Área de Protección de Flora y Fauna Balandra',
                'municipality' => 'La Paz',
                'address' => 'Bahía de La Paz, municipio de La Paz, Baja California Sur',
                'type' => 'reserva',
                'latitude' => 24.31290,
                'longitude' => -110.32250,
                'image' => 'balandra',
                'source' => 'CONANP, ficha del APFF Balandra: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=9',
                'mapNote' => 'El marcador conservado es una referencia terrestre cercana; no representa el centro ni los límites oficiales del APFF.',
                'description' => 'El Área de Protección de Flora y Fauna Balandra se encuentra en el municipio de La Paz, en la bahía del mismo nombre, sobre el litoral del Golfo de California. CONANP documenta un mosaico de manglares, dunas, playas, arrecifes rocosos y matorral, con transiciones entre ambientes terrestres, costeros y marinos someros.'."\n\n".
                    'La ficha oficial registra 2,512.73 ha y designaciones de sitio Ramsar (2008) y Bien de Patrimonio Mundial (2005). Los manglares y otros humedales costeros ofrecen hábitat a fauna asociada, mientras que las dunas y costas rocosas forman parte del mismo sistema ecológico. La categoría nacional de manejo es Área de Protección de Flora y Fauna.',
            ],
            [
                'name' => 'Parque Nacional Zona Marina del Archipiélago de Espíritu Santo',
                'municipality' => 'La Paz',
                'address' => 'Golfo de California, municipio de La Paz, Baja California Sur',
                'type' => 'parque',
                'latitude' => 24.50778,
                'longitude' => -110.34889,
                'image' => 'espiritu-santo',
                'source' => 'CONANP, Zona marina del Archipiélago de Espíritu Santo: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=183',
                'mapNote' => 'El punto guardado es una referencia cartográfica; no define el polígono oficial del parque marino.',
                'description' => 'El Parque Nacional Zona Marina del Archipiélago de Espíritu Santo protege islas e islotes y las aguas que los rodean en el Golfo de California, frente al municipio de La Paz. El área reúne costas rocosas, playas, ambientes insulares áridos y ecosistemas marinos, por lo que conecta hábitats terrestres y acuáticos.'."\n\n".
                    'CONANP registra una superficie de 48,654.83 ha y documenta una colonia reproductora de lobo marino de California en el complejo insular. Esa colonia y la diversidad de ambientes costeros sustentan su importancia para la conservación. Su categoría federal es Parque Nacional de zona marina.',
            ],
            [
                'name' => 'Humedales El Mogote y Ensenada de La Paz',
                'municipality' => 'La Paz',
                'address' => 'Ensenada de La Paz, Baja California Sur',
                'type' => 'otro',
                'latitude' => 24.15400,
                'longitude' => -110.35200,
                'image' => null,
                'source' => 'Convención Ramsar, Humedales Mogote-Ensenada de La Paz, sitio 1816: https://rsis.ramsar.org/es/ris/1816',
                'mapNote' => 'El marcador se conserva como referencia terrestre cercana; no representa el límite del sitio Ramsar ni del humedal.',
                'description' => 'El sitio Ramsar Humedales Mogote-Ensenada de La Paz comprende la ensenada costera y el sistema de barrera arenosa de El Mogote, en el municipio de La Paz. La ficha Ramsar describe laguna costera, manglares, planicies de marea y otros ambientes intermareales que enlazan la costa con aguas someras.'."\n\n".
                    'El sitio fue designado el 2 de febrero de 2008 y su ficha Ramsar reporta 9,184.1 ha. Esos humedales mantienen hábitat para aves acuáticas y organismos costeros; la ficha identifica al lobo marino de California como el mamífero más común del sitio. Esta cifra corresponde al sitio Ramsar y no a otra área protegida de la región.',
            ],
            [
                'name' => 'Reserva de la Biosfera Sierra La Laguna',
                'municipality' => 'La Paz y Los Cabos',
                'address' => 'Sierra La Laguna, Baja California Sur',
                'type' => 'reserva',
                'latitude' => 23.53029,
                'longitude' => -109.95842,
                'image' => 'sierra-la-laguna',
                'source' => 'CONANP, Reserva de la Biosfera Sierra La Laguna: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=162',
                'mapNote' => 'El punto guardado es una referencia del catálogo; el polígono de CONANP, no este marcador, define el área protegida.',
                'description' => 'La Reserva de la Biosfera Sierra La Laguna se extiende por los municipios de La Paz y Los Cabos, en el extremo sur de la península. El relieve montañoso y sus cambios ambientales permiten la presencia de bosque de pino-encino, selva baja caducifolia y vegetación semidesértica, ecosistemas que contrastan con las planicies áridas cercanas.'."\n\n".
                    'CONANP registra una superficie de 112,437.07 ha y las designaciones internacionales de Reserva de la Biosfera MaB y sitio Ramsar. Sus gradientes de vegetación y elevación sostienen hábitats diferenciados y procesos naturales de captación de agua. La superficie indicada corresponde a la reserva federal.',
            ],
            [
                'name' => 'Reserva de la Biosfera El Vizcaíno',
                'municipality' => 'Mulegé',
                'address' => 'Municipio de Mulegé, Baja California Sur',
                'type' => 'reserva',
                'latitude' => 27.443697,
                'longitude' => -113.617198,
                'image' => 'el-vizcaino',
                'source' => 'CONANP, Reserva de la Biosfera El Vizcaíno: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=64. Coordenada seleccionada dentro del polígono oficial CONANP: https://sig.conanp.gob.mx/storage/descargas/files/kml/232-ANP_ITRF08_19162026.kmz',
                'mapNote' => 'La coordenada existente se conserva dentro del polígono oficial; es un punto de referencia y no representa el centro ni los límites de la reserva.',
                'description' => 'La Reserva de la Biosfera El Vizcaíno ocupa una amplia región del municipio de Mulegé, en el norte de Baja California Sur. Sus ambientes incluyen sierras, planicies desérticas y lagunas costeras del Pacífico; el contraste entre zonas áridas y humedales sostiene ecosistemas terrestres, costeros y marinos. La Reserva es importante para el ciclo de vida de la ballena gris.'."\n\n".
                    'CONANP registra 2,546,790.25 ha y señala designaciones internacionales de Patrimonio Mundial, Reserva de la Biosfera MaB y sitios Ramsar asociados a la región. Las superficies de sitios Ramsar específicos, como Ojo de Liebre, son distintas y no deben confundirse con la extensión total de la reserva.',
            ],
            [
                'name' => 'Laguna San Ignacio',
                'municipality' => 'Mulegé',
                'address' => 'Municipio de Mulegé, Baja California Sur',
                'type' => 'otro',
                'latitude' => 26.75000,
                'longitude' => -113.11667,
                'image' => 'laguna-san-ignacio',
                'source' => 'Convención Ramsar, Laguna San Ignacio, sitio 1341: https://rsis.ramsar.org/es/ris/1341; CONANP, Reserva de la Biosfera El Vizcaíno: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=64',
                'mapNote' => 'El marcador se conserva como referencia del sistema lagunar; no representa el límite del sitio Ramsar ni de El Vizcaíno.',
                'description' => 'Laguna San Ignacio es un sistema costero salobre del Pacífico, en el municipio de Mulegé y dentro de la región de El Vizcaíno. La ficha Ramsar describe aguas lagunares, planicies intermareales y humedales asociados, en un contexto desértico. El conjunto ofrece áreas de alimentación y refugio para fauna costera.'."\n\n".
                    'El sitio Ramsar 1341 fue designado el 2 de febrero de 2004 y tiene una superficie registrada de 17,500 ha. La laguna es un área importante para la reproducción y crianza de la ballena gris; la ficha también registra al lobo marino de California como mamífero residente y la identifica dentro de la Reserva de la Biosfera y el sitio de Patrimonio Mundial El Vizcaíno. Esta superficie corresponde al sitio Ramsar, no a toda la Reserva.',
            ],
            [
                'name' => 'Complejo Lagunar Ojo de Liebre',
                'municipality' => 'Mulegé',
                'address' => 'Mulegé, Baja California Sur y norte de Baja California',
                'type' => 'reserva',
                'latitude' => 27.76518,
                'longitude' => -114.23078,
                'image' => 'ojo-de-liebre',
                'source' => 'CONANP, Complejo Lagunar Ojo de Liebre: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=38; Convención Ramsar, Laguna Ojo de Liebre, sitio 1339: https://rsis.ramsar.org/es/ris/1339',
                'mapNote' => 'El marcador se conserva como referencia terrestre cercana; no representa los límites de la Reserva de la Biosfera ni del sitio Ramsar.',
                'description' => 'El Complejo Lagunar Ojo de Liebre forma parte de la Reserva de la Biosfera del mismo nombre, en Mulegé, Baja California Sur, y se prolonga hacia el norte de Baja California. Sus lagunas costeras, canales, planicies intermareales y humedales se integran en un paisaje árido y son importantes para la ballena gris y las aves acuáticas.'."\n\n".
                    'La ficha CONANP registra 79,328.98 ha para la Reserva de la Biosfera y también documenta a la tortuga prieta. El sitio Ramsar Laguna Ojo de Liebre (1339), designado en 2004, tiene 36,600 ha: son designaciones y superficies diferentes. El complejo también forma parte del Santuario de Ballenas de El Vizcaíno, inscrito como Patrimonio Mundial.',
            ],
            [
                'name' => 'Parque Nacional Bahía de Loreto',
                'municipality' => 'Loreto',
                'address' => 'Bahía de Loreto, Baja California Sur',
                'type' => 'parque',
                'latitude' => 26.01170,
                'longitude' => -111.34700,
                'image' => null,
                'source' => 'CONANP, Parque Nacional Bahía de Loreto: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=7',
                'mapNote' => 'El marcador ubica la localidad de Loreto como referencia terrestre; no representa el límite del parque ni sus islas.',
                'description' => 'El Parque Nacional Bahía de Loreto comprende aguas del Golfo de California y un conjunto de islas, costas rocosas y ambientes marinos y costeros frente al municipio de Loreto. CONANP identifica cinco islas principales y registra una superficie de 206,580.75 ha.'."\n\n".
                    'Sus hábitats sostienen fauna marina y costera; la ficha oficial documenta, entre otras especies, ballena azul, lobo marino de California, tortuga prieta y mangle rojo. CONANP registra designaciones de Patrimonio Mundial y sitio Ramsar, además de la certificación de Lista Verde. La coordenada del catálogo es una referencia próxima a la localidad, no el límite marino del parque.',
            ],
            [
                'name' => 'Parque Nacional Cabo Pulmo',
                'municipality' => 'Los Cabos',
                'address' => 'Cabo Pulmo, Baja California Sur',
                'type' => 'parque',
                'latitude' => 23.44571,
                'longitude' => -109.42451,
                'image' => 'cabo-pulmo',
                'source' => 'CONANP, Parque Nacional Cabo Pulmo: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=18',
                'mapNote' => 'El marcador es una referencia terrestre cercana al área protegida; no representa su límite oficial.',
                'description' => 'El Parque Nacional Cabo Pulmo protege un sector marino del Golfo de California frente al municipio de Los Cabos. Su ecosistema arrecifal rocoso-coralino es una estructura relevante para la vida marina y forma parte de la conectividad ecológica de la costa sudcaliforniana.'."\n\n".
                    'CONANP registra 7,111.01 ha y designaciones de Patrimonio Mundial y sitio Ramsar, además de la certificación de Lista Verde. La ficha oficial documenta tortugas prieta y golfina, además de ballena gris, entre la fauna del área. El punto guardado es una referencia terrestre cercana y no representa el límite oficial del parque.',
            ],
            [
                'name' => 'Área de Protección de Flora y Fauna Islas del Golfo de California',
                'municipality' => 'Varios municipios del Golfo de California',
                'address' => 'Islas e islotes de Baja California, BCS, Sonora y Sinaloa',
                'type' => 'reserva',
                'latitude' => 24.981136,
                'longitude' => -110.621914,
                'image' => 'islas-golfo-california',
                'source' => 'CONANP-SIMEC, ficha del APFF Islas del Golfo de California (ANP 80): https://simec.conanp.gob.mx/informacion/ficha/80?reg=11; CONANP, polígono oficial: https://sig.conanp.gob.mx/storage/descargas/files/kml/232-ANP_ITRF08_19162026.kmz',
                'mapNote' => 'El marcador y la imagen se refieren a Isla San José, BCS; son referencias puntuales. El APFF comprende numerosas islas e islotes en cuatro estados y no se limita a esa isla.',
                'description' => 'El Área de Protección de Flora y Fauna Islas del Golfo de California comprende numerosas islas e islotes distribuidos entre Baja California, Baja California Sur, Sonora y Sinaloa; no es un área limitada a Isla San José. En Baja California Sur, la ficha SIMEC incluye municipios como La Paz, Loreto y Mulegé. El mosaico insular alberga matorral xerófilo, mezquital, selva baja caducifolia y espinosa, dunas costeras, vegetación hidrófila y manglar.'."\n\n".
                    'La ficha SIMEC registra una superficie total de 374,553.63 ha y documenta al cardón (Pachycereus pringlei) entre su flora representativa. También enumera varias designaciones Ramsar, de Patrimonio Mundial y MaB asociadas a sectores o sitios de la región; no significa que toda el área protegida sea un único sitio Ramsar. La foto y el marcador conservados muestran Isla San José como referencia, no la extensión completa del APFF.',
            ],
        ];
    }

    private function species(): array
    {
        return [
            [
                'name' => 'Cardón',
                'scientificName' => 'Pachycereus pringlei',
                'type' => 'flora',
                'image' => 'cardon',
                'description' => 'El cardón es una cactácea columnar arborescente propia de los ambientes áridos de la península. Sus tallos almacenan agua y su porte forma parte del paisaje del desierto sudcaliforniano.'."\n\n".
                    'Sus flores y frutos aportan recursos a distintos animales, y la estructura de la planta ofrece refugio. EncicloVida registra ejemplares en Baja California Sur, incluidos los municipios de La Paz y Mulegé.',
                'habitat' => 'Matorral sarcocaule, planicies y laderas áridas de Baja California y Baja California Sur.',
                'location' => 'Distribución endémica de México; registros de EncicloVida en La Paz y Mulegé, Baja California Sur.',
                'conservation' => null,
                'relatedPlaces' => ['Área de Protección de Flora y Fauna Islas del Golfo de California'],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/144024-pachycereus-pringlei',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/144024/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Pitaya agria / Pitaya de Baja California',
                'scientificName' => 'Stenocereus gummosus',
                'type' => 'flora',
                'image' => 'pitaya-agria',
                'description' => 'La pitaya agria, también llamada pitaya de Baja California, es una cactácea suculenta endémica de México. Sus tallos carnosos son una adaptación a la disponibilidad limitada de agua en los ambientes áridos.'."\n\n".
                    'Sus flores y frutos participan en las interacciones ecológicas del matorral desértico. EncicloVida registra ejemplares en Baja California Sur, incluidos La Paz y Los Cabos.',
                'habitat' => 'Matorral desértico, planicies y laderas áridas de la península de Baja California.',
                'location' => 'Endémica de México; registros de EncicloVida en La Paz y Los Cabos, Baja California Sur.',
                'conservation' => null,
                'relatedPlaces' => [],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/144034-stenocereus-gummosus',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/144034/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Lomboy',
                'scientificName' => 'Jatropha cinerea',
                'type' => 'flora',
                'image' => 'lomboy',
                'description' => 'El lomboy es un arbusto del paisaje árido peninsular, adaptado a condiciones de poca disponibilidad de agua. Su nombre común se conserva en esta ficha para el taxón Jatropha cinerea.'."\n\n".
                    'Forma parte de la vegetación del matorral desértico de Baja California Sur. Los registros de EncicloVida incluyen localidades en los municipios de La Paz y Los Cabos.',
                'habitat' => 'Matorral desértico, planicies y laderas áridas de la península de Baja California.',
                'location' => 'Distribución peninsular; registros de EncicloVida en La Paz y Los Cabos, Baja California Sur.',
                'conservation' => null,
                'relatedPlaces' => [],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/148914-jatropha-cinerea',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/148914/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Torote colorado',
                'scientificName' => 'Bursera microphylla',
                'type' => 'flora',
                'image' => 'torote-colorado',
                'description' => 'Bursera microphylla es un árbol o arbusto de zonas áridas y forma parte de comunidades de matorral desértico. Esta ficha corresponde a esa especie; no debe confundirse con Bursera exequielii, un taxón distinto descrito en Baja California Sur.'."\n\n".
                    'Los registros de EncicloVida incluyen ejemplares de Bursera microphylla en Baja California Sur, incluido el municipio de Loreto. No se asigna un sitio protegido sin respaldo geográfico específico.',
                'habitat' => 'Matorral desértico, planicies y laderas áridas; registros regionales de Baja California Sur.',
                'location' => 'Registros de EncicloVida para Bursera microphylla en Loreto, Baja California Sur; sin vínculo a un sitio protegido específico.',
                'conservation' => null,
                'relatedPlaces' => [],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/155000-bursera-microphylla',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/155000/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Palo Adán',
                'scientificName' => 'Fouquieria diguetii',
                'type' => 'flora',
                'image' => 'palo-adan',
                'description' => 'El palo Adán es una planta leñosa de ambientes áridos de la península de Baja California. Sus ramas espinosas y su adaptación a periodos secos forman parte de los rasgos descritos para esta especie.'."\n\n".
                    'EncicloVida reconoce el nombre común Palo Adán para Fouquieria diguetii y registra ejemplares en Baja California Sur, incluido el municipio de Comondú. No se atribuye una categoría de riesgo sin respaldo específico.',
                'habitat' => 'Ambientes áridos, matorral desértico, planicies y laderas de la península de Baja California.',
                'location' => 'Registros de EncicloVida en Comondú, Baja California Sur; sin coordenadas de distribución general.',
                'conservation' => null,
                'relatedPlaces' => [],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/172177-fouquieria-diguetii',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/172177/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Tiburón ballena',
                'scientificName' => 'Rhincodon typus',
                'type' => 'fauna',
                'image' => 'tiburon-ballena',
                'description' => 'El tiburón ballena es un pez de gran tamaño que se alimenta por filtración de plancton y otros organismos pequeños. La filtración le permite obtener alimento mientras se desplaza por el agua.'."\n\n".
                    'En Baja California Sur su presencia estacional está documentada en la Bahía de La Paz. La observación debe respetar las disposiciones y medidas de manejo aplicables. La categoría mexicana registrada es Amenazada (A); no equivale a una categoría global de la UICN.',
                'habitat' => 'Aguas tropicales y templadas cálidas, en zonas costeras y oceánicas.',
                'location' => 'Distribución marina amplia; presencia estacional documentada en la Bahía de La Paz. Sin coordenadas puntuales asignadas.',
                'conservation' => 'Amenazada (A)',
                'relatedPlaces' => [],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/30263-rhincodon-typus',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/30263/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Ballena gris',
                'scientificName' => 'Eschrichtius robustus',
                'type' => 'fauna',
                'image' => 'ballena-gris',
                'description' => 'La ballena gris es un cetáceo migratorio que se desplaza entre áreas de alimentación del Pacífico norte y lagunas costeras de la península de Baja California. En Baja California Sur, las lagunas San Ignacio y Ojo de Liebre son importantes para su reproducción y crianza.'."\n\n".
                    'La ficha distingue la categoría mexicana Protección especial (Pr) de cualquier evaluación global o de subpoblación de la UICN. Los lugares relacionados representan ambientes usados en su ciclo reproductivo, no puntos exactos de avistamiento.',
                'habitat' => 'Pacífico norte durante la migración y lagunas costeras de Baja California Sur durante reproducción y crianza.',
                'location' => 'Migratoria del Pacífico norte; registros y ambientes reproductivos en Laguna San Ignacio, Ojo de Liebre y El Vizcaíno.',
                'conservation' => 'Sujeta a protección especial (Pr)',
                'relatedPlaces' => [
                    'Laguna San Ignacio',
                    'Complejo Lagunar Ojo de Liebre',
                    'Reserva de la Biosfera El Vizcaíno',
                    'Parque Nacional Cabo Pulmo',
                ],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/34616-eschrichtius-robustus',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/34616/consulta-registros.json?coleccion=snib&formato=json; CONANP, Reserva de la Biosfera El Vizcaíno: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=64; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Lobo marino de California',
                'scientificName' => 'Zalophus californianus',
                'type' => 'fauna',
                'image' => 'lobo-marino',
                'description' => 'El lobo marino de California es un mamífero marino que forma colonias en costas rocosas e islas. Se alimenta de peces y otros organismos marinos y forma parte de las redes tróficas costeras.'."\n\n".
                    'El programa de conservación del Archipiélago de Espíritu Santo documenta una colonia reproductora en el complejo insular. EncicloVida registra la categoría mexicana Protección especial (Pr).',
                'habitat' => 'Costas rocosas, islas e islotes del Pacífico y del Golfo de California.',
                'location' => 'Distribución en costas e islas del Pacífico y Golfo de California; colonia reproductora documentada en el Archipiélago de Espíritu Santo.',
                'conservation' => 'Sujeta a protección especial (Pr)',
                'relatedPlaces' => [
                    'Parque Nacional Zona Marina del Archipiélago de Espíritu Santo',
                    'Humedales El Mogote y Ensenada de La Paz',
                    'Parque Nacional Bahía de Loreto',
                    'Laguna San Ignacio',
                ],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/33659-zalophus-californianus',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/33659/consulta-registros.json?coleccion=snib&formato=json; CONANP, Zona marina del Archipiélago de Espíritu Santo: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=183; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Tortuga golfina',
                'scientificName' => 'Lepidochelys olivacea',
                'type' => 'fauna',
                'image' => 'tortuga-golfina',
                'description' => 'La tortuga golfina es una tortuga marina que utiliza playas para anidar. La iluminación artificial, la alteración de las playas, la contaminación y la captura incidental son amenazas que pueden afectar a la especie.'."\n\n".
                    'EncicloVida muestra la categoría En peligro de extinción (P) en sus registros asociados. Se deja sin categoría en esta ficha hasta cotejar el registro taxonómico exacto con el anexo oficial vigente de la NOM-059; no se asigna una playa de anidación específica.',
                'habitat' => 'Aguas marinas tropicales y subtropicales; playas utilizadas para anidación.',
                'location' => 'Distribución marina tropical y subtropical; no se asigna una localidad o playa de anidación específica en Baja California Sur.',
                'conservation' => null,
                'relatedPlaces' => ['Parque Nacional Cabo Pulmo'],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/27179-lepidochelys-olivacea',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/27179/consulta-registros.json?coleccion=snib&formato=json; categoría pendiente de cotejo en DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
            [
                'name' => 'Ballena azul',
                'scientificName' => 'Balaenoptera musculus',
                'type' => 'fauna',
                'image' => 'ballena-azul',
                'description' => 'La ballena azul es el animal más grande conocido y se alimenta filtrando agua para capturar kril y otros organismos pequeños. Sus desplazamientos conectan áreas oceánicas de alimentación.'."\n\n".
                    'En México se distribuye en el Pacífico norte y el Golfo de California; EncicloVida incluye registros en el Golfo. La categoría mexicana es Protección especial (Pr), distinta de una evaluación global de la UICN.',
                'habitat' => 'Océano abierto del Pacífico norte y zonas de alimentación estacionales del Golfo de California.',
                'location' => 'Distribución en el Pacífico norte y el Golfo de California; EncicloVida registra ejemplares en Baja California Sur.',
                'conservation' => 'Sujeta a protección especial (Pr)',
                'relatedPlaces' => ['Parque Nacional Bahía de Loreto'],
                'source' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/34367-balaenoptera-musculus',
                'reference' => 'CONABIO, SNIB, registros geográficos y NOM-059 (consulta 2026-11-18): https://enciclovida.mx/especies/34367/consulta-registros.json?coleccion=snib&formato=json; DOF, Modificación del Anexo III NOM-059 (14-11-2019): https://www.dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
            ],
        ];
    }

    private function articles(): array
    {
        return [
            [
                'title' => 'Los manglares de Balandra: guardianes de la costa sudcaliforniana',
                'category' => 'Ecosistemas',
                'image' => 'balandra',
                'source' => 'CONANP, información y programa de manejo de Balandra.',
                'content' => <<<'CONTENT'
Resumen: Los manglares son ecosistemas costeros fundamentales para la biodiversidad y la protección natural de las costas. En Balandra forman parte de un entorno donde se conectan ambientes terrestres, marinos y humedales.

Introducción
Los manglares son comunidades vegetales adaptadas a vivir en zonas costeras donde se mezclan el agua dulce y el agua salada. Sus raíces y estructuras naturales forman ambientes complejos que ofrecen refugio, alimentación y zonas de reproducción para numerosas especies.

En Baja California Sur, estos ecosistemas tienen un valor especial debido a que se desarrollan en una región predominantemente árida. Su presencia permite mantener espacios de biodiversidad que contrastan con el paisaje desértico circundante.

En Balandra, los ambientes de manglar forman parte de un conjunto de ecosistemas costeros que también incluye playas, ensenadas y zonas marinas poco profundas. La conservación de estos ambientes requiere evitar la contaminación, respetar las zonas delimitadas y reducir las alteraciones ocasionadas por actividades humanas.

Visitar un área natural protegida implica comprender que no se trata solamente de un paisaje para fotografiar. Es un ecosistema que necesita condiciones adecuadas para mantener sus procesos naturales.

La protección de los manglares comienza con acciones sencillas: no arrojar residuos, no dañar la vegetación, respetar los senderos y seguir las indicaciones de las autoridades ambientales.
CONTENT,
            ],
            [
                'title' => 'Tiburón ballena en La Paz: cómo observarlo sin ponerlo en riesgo',
                'category' => 'Fauna marina',
                'image' => 'tiburon-ballena',
                'source' => 'CONABIO y autoridades ambientales responsables de la regulación de observación.',
                'content' => <<<'CONTENT'
Resumen: La presencia estacional del tiburón ballena en la Bahía de La Paz representa una oportunidad para conocer la biodiversidad marina y fomentar actividades de observación responsable.

Introducción
El tiburón ballena es el pez más grande del planeta. A pesar de su tamaño, se alimenta principalmente mediante filtración, consumiendo plancton y pequeños organismos presentes en el agua.

La Bahía de La Paz es reconocida por la presencia estacional de esta especie. Su llegada permite desarrollar actividades de observación que, cuando se realizan de manera responsable y conforme a la regulación vigente, pueden contribuir a la educación ambiental.

Sin embargo, observar a un animal silvestre implica respetar su comportamiento. Acercamientos inadecuados, persecuciones, contacto físico o interferencias con su desplazamiento pueden ocasionar estrés y alterar sus actividades naturales.

Los visitantes deben contratar prestadores autorizados, respetar las indicaciones de los guías y cumplir las disposiciones oficiales vigentes para la observación.

La conservación del tiburón ballena no depende solamente de proteger a los ejemplares. También requiere mantener la calidad del agua, reducir los residuos y conservar los ecosistemas marinos que utiliza para alimentarse.
CONTENT,
            ],
            [
                'title' => 'El viaje de la ballena gris: una historia de migración y conservación',
                'category' => 'Fauna marina',
                'image' => 'ballena-gris',
                'source' => 'CONANP, Reserva de la Biosfera El Vizcaíno.',
                'content' => <<<'CONTENT'
Resumen: Las lagunas de Baja California Sur forman parte de una de las regiones más importantes para la reproducción y crianza de la ballena gris en el Pacífico.

Introducción
La ballena gris realiza una migración de larga distancia entre sus áreas de alimentación en el norte del Pacífico y las lagunas de reproducción ubicadas en la península de Baja California.

Durante su estancia en Baja California Sur, las hembras pueden dar a luz y cuidar a sus crías en ambientes costeros protegidos. Estas lagunas ofrecen condiciones que resultan importantes durante las primeras etapas de vida de los ballenatos.

Laguna San Ignacio y el Complejo Lagunar Ojo de Liebre son dos sitios emblemáticos de este proceso natural. Ambos forman parte de la región protegida de El Vizcaíno y tienen gran importancia para la conservación de la especie.

La historia de la ballena gris también demuestra la importancia de la protección ambiental. Después de haber enfrentado una intensa explotación histórica, su recuperación estuvo vinculada a medidas de conservación y cooperación internacional.

Actualmente, mantener la tranquilidad de las zonas de reproducción y regular las actividades turísticas son acciones importantes para proteger a las ballenas y sus crías.
CONTENT,
            ],
            [
                'title' => 'Tortugas marinas en Baja California Sur: proteger las playas donde comienza la vida',
                'category' => 'Conservación',
                'image' => 'tortuga-golfina',
                'source' => 'PROFEPA, CONANP y NOM-059-SEMARNAT.',
                'content' => <<<'CONTENT'
Resumen: Las playas son espacios esenciales para la reproducción de las tortugas marinas. Su protección requiere reducir las alteraciones humanas y mantener condiciones adecuadas para la anidación.

Introducción
Las tortugas marinas pasan gran parte de su vida en el océano, pero necesitan regresar a las playas para completar su ciclo reproductivo. Durante la anidación, las hembras excavan nidos en la arena y depositan sus huevos, que posteriormente se desarrollan bajo condiciones ambientales específicas.

La conservación de las playas es fundamental para que este proceso ocurra correctamente. La iluminación artificial, los residuos, el tránsito de vehículos y la alteración de la arena pueden afectar las condiciones de los nidos y el desplazamiento de las crías.

En Baja California Sur existen esfuerzos de protección y monitoreo de tortugas marinas que involucran a autoridades, organizaciones y comunidades locales.

Las personas que visitan playas pueden contribuir evitando acercarse a tortugas que estén anidando, no manipulando huevos o crías y reportando actividades que puedan ponerlas en riesgo a las autoridades correspondientes.

La protección de estos animales requiere acciones continuas y el respeto a las disposiciones ambientales vigentes.
CONTENT,
            ],
            [
                'title' => 'Sierra La Laguna: el bosque que sostiene la vida en el desierto',
                'category' => 'Ecosistemas terrestres',
                'image' => 'sierra-la-laguna',
                'source' => 'CONANP, ficha y programa de manejo de Sierra La Laguna: https://descubreanp.conanp.gob.mx/es/conanp/anp?suri=162',
                'content' => <<<'CONTENT'
Resumen: La Sierra La Laguna representa un contraste ecológico dentro del paisaje árido de Baja California Sur y tiene importancia para la biodiversidad y el funcionamiento de las cuencas.

Introducción
La Sierra La Laguna es una región montañosa ubicada al sur de Baja California Sur. Su elevación y condiciones ambientales permiten el desarrollo de comunidades vegetales que contrastan con los matorrales desérticos presentes en otras partes de la península.

En sus zonas de mayor altitud se encuentran bosques de pino y encino, mientras que otras áreas presentan selva baja caducifolia y distintos tipos de vegetación adaptados a las condiciones locales.

Esta diversidad de ambientes permite la presencia de especies vegetales y animales con diferentes requerimientos ecológicos.

Además de su biodiversidad, la sierra desempeña un papel importante en los procesos hidrológicos regionales. La vegetación y las condiciones naturales del terreno contribuyen a la captación, infiltración y regulación del agua.

Conservar la Sierra La Laguna significa proteger un sistema natural que beneficia a los ecosistemas y a las comunidades humanas del sur de la península.
CONTENT,
            ],
            [
                'title' => 'Cómo sobrevive la flora del desierto sudcaliforniano',
                'category' => 'Flora',
                'image' => 'cardon',
                'source' => 'CONANP, Programa de Manejo de Balandra; documentación botánica regional de SEMARNAT.',
                'content' => <<<'CONTENT'
Resumen: La vegetación de Baja California Sur presenta adaptaciones que le permiten desarrollarse en condiciones de altas temperaturas, precipitaciones limitadas y suelos áridos.

Introducción
El desierto sudcaliforniano puede parecer un ambiente con poca vida, pero alberga una gran diversidad de plantas adaptadas a condiciones de escasez de agua.

Muchas especies presentan tallos capaces de almacenar humedad, hojas reducidas o estructuras que disminuyen la pérdida de agua. Estas características les permiten aprovechar los periodos de lluvia y sobrevivir durante temporadas secas.

Entre las plantas representativas de la región se encuentran el cardón, la pitaya agria, el lomboy, el torote colorado y el palo Adán.

Estas especies forman parte de comunidades vegetales que proporcionan recursos y espacios de refugio para diferentes organismos. Su importancia no se limita a su apariencia: contribuyen al funcionamiento de los ecosistemas y a las relaciones ecológicas del desierto.

La conservación de la flora nativa requiere evitar la extracción ilegal, prevenir daños a la vegetación y mantener los ambientes donde estas plantas se desarrollan naturalmente.

Conocer las especies del desierto permite comprender que la aridez no significa ausencia de biodiversidad, sino la existencia de ecosistemas con adaptaciones especializadas.
CONTENT,
            ],
        ];
    }
}
