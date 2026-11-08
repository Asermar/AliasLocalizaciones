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
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Alias de un país: no se borra si tiene alias propios o provincias debajo (aunque no tengan
 * alias), porque los hijos los borra la base por FK ON DELETE CASCADE sin pasar por el modelo
 * y sus alias quedarían huérfanos; se vacía de abajo arriba.
 * Al renombrar su clave, sus alias pasan al código nuevo.
 *
 * @author Alexis Serafín <alexis@okodex.com>
 */
class Pais
{
    /**
     * Código anterior de cada registro mientras se renombra: el After lo necesita y el core ya
     * no lo conserva (changeId() sobrescribe original con el nuevo antes de llamarlo).
     */
    private $renaming = [];

    public function changePrimaryColumnValueAfter(): Closure
    {
        $renaming = &$this->renaming;
        return function () use (&$renaming) {
            $old = $renaming[spl_object_id($this)] ?? null;
            unset($renaming[spl_object_id($this)]);
            if (null === $old) {
                return;
            }

            $done = Alias::table()
                ->whereEq('aliastype', Init::ALIAS_TYPE_COUNTRY)
                ->whereEq('cod', (string)$old)
                ->update(['cod' => (string)$this->codpais]);
            if (false === $done) {
                Tools::log()->error('alias-rename-error', [
                    '%aliastype%' => Init::ALIAS_TYPE_COUNTRY,
                    '%old%' => $old,
                    '%new%' => $this->codpais,
                ]);
                return false;
            }

            (new Alias())->clearCache();
        };
    }

    public function changePrimaryColumnValueBefore(): Closure
    {
        $renaming = &$this->renaming;
        return function () use (&$renaming) {
            $renaming[spl_object_id($this)] = $this->codpais;
        };
    }

    public function deleteBefore(): Closure
    {
        return function () {
            $refused = false;

            $aliases = Alias::count([
                Where::eq('aliastype', Init::ALIAS_TYPE_COUNTRY),
                Where::eq('cod', (string)$this->codpais),
            ]);
            if ($aliases > 0) {
                Tools::log()->warning('location-has-aliases', ['%count%' => $aliases]);
                $refused = true;
            }

            $children = Provincia::table()->whereEq('codpais', $this->codpais)->count();
            if ($children > 0) {
                Tools::log()->warning('location-has-provinces', ['%count%' => $children]);
                $refused = true;
            }

            return $refused ? false : null;
        };
    }
}
