<?php

namespace App\Console\Commands;

use App\Models\Articulo;
use App\Models\FloraFauna;
use App\Models\Lugar;
use App\Models\Voluntariado;
use Illuminate\Console\Command;

class CheckDataCommand extends Command
{
    protected $signature = 'regenera:check-data';

    protected $description = 'Muestra un resumen de los registros almacenados en MongoDB.';

    public function handle(): int
    {
        $this->line('Voluntariados: ' . Voluntariado::count());
        foreach (Voluntariado::all() as $v) {
            $this->line('  - ' . $v->titulo . ' => [' . $v->estado . ']');
        }

        $this->newLine();
        $this->line('Articulos: ' . Articulo::count());
        foreach (Articulo::all() as $a) {
            $this->line('  - ' . $a->titulo . ' => [' . $a->estado . ']');
        }

        $this->newLine();
        $this->line('Flora/Fauna: ' . FloraFauna::count());
        foreach (FloraFauna::all() as $f) {
            $this->line('  - ' . $f->nombre . ' (imagenes: ' . (is_array($f->imagenes) ? count($f->imagenes) : 0) . ')');
        }

        $this->newLine();
        $this->line('Lugares: ' . Lugar::count());

        return self::SUCCESS;
    }
}