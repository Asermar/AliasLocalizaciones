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
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Extiende EditProvincia para mostrar y gestionar los alias de la provincia.
 *
 * @author Alexis Serafín <alexis@okodex.com>
 */
class EditProvincia
{
    public function createViews(): Closure
    {
        return function () {
            $this->addEditListView('EditAliasLocalizacion', 'Alias', 'aliases', 'fa-solid fa-tags');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'EditAliasLocalizacion') {
                return;
            }

            // alias de tipo 'province' de la provincia actual; el where autorrellena
            // aliastype y cod en el formulario de alta (EditListView::loadData)
            $mvn = $this->getMainViewName();
            $where = [
                Where::eq('aliastype', Init::ALIAS_TYPE_PROVINCE),
                Where::eq('cod', $this->views[$mvn]->model->id()),
            ];
            $view->loadData('', $where);
        };
    }
}
