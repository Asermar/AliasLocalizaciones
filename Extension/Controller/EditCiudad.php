<?php
/**
 * Copyright (C) 2026 Oko Digital Experts, S.L.L. (Okodex)
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Controller;

use Closure;
use FacturaScripts\Core\Where;
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
