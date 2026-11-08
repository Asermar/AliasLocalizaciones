<?php
/**
 * Copyright (C) 2026 Oko Digital Experts, S.L.L. (Okodex)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Model;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Alias;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Alias de un punto de interés: no se borra si tiene alias propios.
 *
 * @author Alexis Serafín <alexis@okodex.com>
 */
class PuntoInteresCiudad
{
    public function deleteBefore(): Closure
    {
        return function () {
            $refused = false;

            $aliases = Alias::count([
                Where::eq('aliastype', Init::ALIAS_TYPE_POI),
                Where::eq('cod', (string)$this->id),
            ]);
            if ($aliases > 0) {
                Tools::log()->warning('location-has-aliases', ['%count%' => $aliases]);
                $refused = true;
            }

            return $refused ? false : null;
        };
    }
}
