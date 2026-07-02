<?php
/**
 * Copyright (C) 2026 Oko Digital Experts, S.L.L. (Okodex)
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
