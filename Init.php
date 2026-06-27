<?php
/**
 * Copyright (C) 2026 Alexis Serafin <alexis@okodex.com>
 */

namespace FacturaScripts\Plugins\AliasLocalizaciones;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AliasType;

/**
 * @author Alexis Serafin <alexis@okodex.com>
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
        // aseguramos que los tipos de alias existan en el catálogo
        $this->ensureAliasType(self::ALIAS_TYPE_COUNTRY, 'País');
        $this->ensureAliasType(self::ALIAS_TYPE_PROVINCE, 'Provincia');
        $this->ensureAliasType(self::ALIAS_TYPE_CITY, 'Ciudad');
        $this->ensureAliasType(self::ALIAS_TYPE_POI, 'Punto de interés');
    }

    protected function ensureAliasType(string $code, string $description): void
    {
        $type = new AliasType();
        if (false === $type->loadWhere([Where::eq('aliastype', $code)])) {
            $type->aliastype = $code;
            $type->description = $description;
            $type->save();
        }
    }
}
