<?php
/**
 * Copyright (C) 2026 Alexis Serafin <alexis@okodex.com>
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Model;

use Closure;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Alias;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Simula el ON DELETE CASCADE: al eliminar un punto de interés, borra sus alias.
 *
 * @author Alexis Serafin <alexis@okodex.com>
 */
class PuntoInteresCiudad
{
    public function delete(): Closure
    {
        return function () {
            $where = [
                Where::eq('aliastype', Init::ALIAS_TYPE_POI),
                Where::eq('cod', $this->id),
            ];
            foreach (Alias::all($where) as $alias) {
                $alias->delete();
            }
        };
    }
}
