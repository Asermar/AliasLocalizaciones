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

namespace FacturaScripts\Plugins\AliasLocalizaciones;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Dinamic\Model\AliasType;

/**
 * @author Alexis Serafín <alexis@okodex.com>
 */
class Init extends InitClass
{
    /** Tipo de alias para ciudades (catálogo aliastypes) */
    const ALIAS_TYPE_CITY = 'city';

    /** Tipo de alias para países (catálogo aliastypes) */
    const ALIAS_TYPE_COUNTRY = 'country';

    /** Tipo de alias para puntos de interés (catálogo aliastypes) */
    const ALIAS_TYPE_POI = 'poi';

    /** Tipo de alias para provincias (catálogo aliastypes) */
    const ALIAS_TYPE_PROVINCE = 'province';

    /** Nombre del plugin, responsable de estos tipos de alias (catálogo aliastypes) */
    const PLUGIN_NAME = 'AliasLocalizaciones';

    public function init(): void
    {
        $this->loadExtension(new Extension\Controller\EditPais());
        $this->loadExtension(new Extension\Controller\EditProvincia());
        $this->loadExtension(new Extension\Controller\EditCiudad());
        $this->loadExtension(new Extension\Controller\EditPuntoInteresCiudad());

        $this->loadExtension(new Extension\Model\Pais());
        $this->loadExtension(new Extension\Model\Provincia());
        $this->loadExtension(new Extension\Model\Ciudad());
        $this->loadExtension(new Extension\Model\PuntoInteresCiudad());

        $this->loadExtension(new Extension\Model\Alias());
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        // aseguramos que los tipos de alias existan en el catálogo, indicando el plugin responsable
        AliasType::ensure(self::ALIAS_TYPE_COUNTRY, 'País', self::PLUGIN_NAME);
        AliasType::ensure(self::ALIAS_TYPE_PROVINCE, 'Provincia', self::PLUGIN_NAME);
        AliasType::ensure(self::ALIAS_TYPE_CITY, 'Ciudad', self::PLUGIN_NAME);
        AliasType::ensure(self::ALIAS_TYPE_POI, 'Punto de interés', self::PLUGIN_NAME);
    }
}
