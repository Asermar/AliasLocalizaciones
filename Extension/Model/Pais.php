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
 * Simula el ON DELETE CASCADE: al eliminar un país, borra sus alias.
 *
 * @author Alexis Serafín <alexis@okodex.com>
 */
class Pais
{
    public function delete(): Closure
    {
        return function () {
            $where = [
                Where::eq('aliastype', Init::ALIAS_TYPE_COUNTRY),
                Where::eq('cod', $this->codpais),
            ];
            foreach (Alias::all($where) as $alias) {
                $alias->delete();
            }
        };
    }
}
