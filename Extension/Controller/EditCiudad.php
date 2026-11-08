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

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Controller;

use Closure;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\AliasTabGuard;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Extiende EditCiudad para mostrar y gestionar los alias de la ciudad.
 *
 * @author Alexis Serafín <alexis@okodex.com>
 */
class EditCiudad
{
    public function createViews(): Closure
    {
        return function () {
            $this->addEditListView('EditAliasLocalizacion', 'Alias', 'aliases', 'fa-solid fa-tags');

            // la pestaña sólo escribe alias de esta ficha: es lo último antes de las acciones
            AliasTabGuard::protect(
                $this->request,
                $this->active,
                'EditAliasLocalizacion',
                Init::ALIAS_TYPE_CITY,
                $this->views[$this->getMainViewName()]->model
            );
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'EditAliasLocalizacion') {
                return;
            }

            // alias de tipo 'city' de la ciudad actual; el where autorrellena
            // aliastype y cod en el formulario de alta (EditListView::loadData)
            $mvn = $this->getMainViewName();
            $where = [
                Where::eq('aliastype', Init::ALIAS_TYPE_CITY),
                Where::eq('cod', $this->views[$mvn]->model->id()),
            ];
            $view->loadData('', $where);
        };
    }
}
