<?php
/**
 * Copyright (C) 2026 Alexis Serafin <alexis@okodex.com>
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones\Extension\Model;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;

/**
 * FK simulada: valida que cod sea un registro existente del modelo asociado al tipo.
 *
 * @author Alexis Serafin <alexis@okodex.com>
 */
class Alias
{
    public function test(): Closure
    {
        return function () {
            $map = [
                Init::ALIAS_TYPE_COUNTRY => Pais::class,
                Init::ALIAS_TYPE_PROVINCE => Provincia::class,
                Init::ALIAS_TYPE_CITY => Ciudad::class,
                Init::ALIAS_TYPE_POI => PuntoInteresCiudad::class,
            ];

            $class = $map[$this->aliastype] ?? null;
            if (null === $class) {
                return true;
            }

            $model = new $class();
            if (false === $model->load($this->cod)) {
                Tools::log()->warning('alias-cod-not-found', [
                    '%aliastype%' => $this->aliastype,
                    '%cod%' => $this->cod,
                ]);
                return false;
            }

            return true;
        };
    }
}
