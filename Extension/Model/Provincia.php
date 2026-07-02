<?php
/**
 * Copyright (C) 2026 Oko Digital Experts, S.L.L. (Okodex)
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Model;

use Closure;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Alias;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Simula el ON DELETE CASCADE: al eliminar una provincia, borra sus alias.
 *
 * @author Alexis Serafín <alexis@okodex.com>
 */
class Provincia
{
    public function delete(): Closure
    {
        return function () {
            $where = [
                Where::eq('aliastype', Init::ALIAS_TYPE_PROVINCE),
                Where::eq('cod', $this->idprovincia),
            ];
            foreach (Alias::all($where) as $alias) {
                $alias->delete();
            }
        };
    }
}
