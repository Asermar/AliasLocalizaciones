<?php
/**
 * Copyright (C) 2026 Alexis Serafin <alexis@okodex.com>
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Controller;

use Closure;
use FacturaScripts\Core\Where;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * Extiende EditProvincia para mostrar y gestionar los alias de la provincia.
 *
 * @author Alexis Serafin <alexis@okodex.com>
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
