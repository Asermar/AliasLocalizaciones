<?php
/**
 * Copyright (C) 2026 Oko Digital Experts, S.L.L. (Okodex)
 */

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Alias;
use FacturaScripts\Dinamic\Model\AliasType;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Alexis Serafín <alexis@okodex.com>
 *
 * @description
 * ## Alias de localizaciones
 *
 * Integra el plugin `Alias` en **países, provincias, ciudades y puntos de interés**:
 *
 * - Se puede crear un alias asociado a cada tipo de localización existente.
 * - Al borrar la localización, sus alias se eliminan (**borrado en cascada**).
 * - Se **rechaza** crear un alias para una localización inexistente (FK simulada).
 */
final class AliasLocalizacionesTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /**
     * @description No permite guardar un alias para una localización inexistente.
     */
    public function testAliasRejectsMissingLocation(): void
    {
        $alias = new Alias();
        $alias->aliastype = Init::ALIAS_TYPE_COUNTRY;
        $alias->cod = 'NO-EXISTE';
        $alias->alias = 'X';
        $this->assertFalse($alias->save(), 'no debe guardar alias de una localización inexistente');

        // la validación (FK simulada) registra un warning; limpiamos el log
        MiniLog::clear();
    }

    /**
     * @description Alias de una **ciudad**: se crea y se borra en cascada al borrar la ciudad.
     */
    public function testCityAliasAndCascadeDelete(): void
    {
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save(), 'no se pudo crear el país');

        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');

        $city = $this->getRandomCity($province->idprovincia);
        $this->assertTrue($city->save(), 'no se pudo crear la ciudad');

        $alias = new Alias();
        $alias->aliastype = Init::ALIAS_TYPE_CITY;
        $alias->cod = $city->idciudad;
        $alias->alias = 'CITY-ALIAS';
        $this->assertTrue($alias->save(), 'no se pudo guardar el alias de la ciudad');
        $aliasId = $alias->id;

        $this->assertTrue($city->delete());

        $check = new Alias();
        $this->assertFalse($check->load($aliasId), 'el alias debería borrarse al borrar la ciudad');

        $province->delete();
        $country->delete();
    }

    /**
     * @description Alias de un **país**: se crea y se borra en cascada al borrar el país.
     */
    public function testCountryAliasAndCascadeDelete(): void
    {
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save(), 'no se pudo crear el país');

        $alias = new Alias();
        $alias->aliastype = Init::ALIAS_TYPE_COUNTRY;
        $alias->cod = $country->codpais;
        $alias->alias = 'COUNTRY-ALIAS';
        $this->assertTrue($alias->save(), 'no se pudo guardar el alias del país');
        $aliasId = $alias->id;

        // al borrar el país, sus alias deben eliminarse (cascade simulado)
        $this->assertTrue($country->delete());

        $check = new Alias();
        $this->assertFalse($check->load($aliasId), 'el alias debería borrarse al borrar el país');
    }

    /**
     * @description Alias de un **punto de interés**: se crea y se borra en cascada al borrarlo.
     */
    public function testPoiAliasAndCascadeDelete(): void
    {
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save(), 'no se pudo crear el país');

        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');

        $city = $this->getRandomCity($province->idprovincia);
        $this->assertTrue($city->save(), 'no se pudo crear la ciudad');

        $poi = new PuntoInteresCiudad();
        $poi->idciudad = $city->idciudad;
        $poi->name = 'Test POI ' . mt_rand(1, 99999);
        $this->assertTrue($poi->save(), 'no se pudo crear el punto de interés');

        $alias = new Alias();
        $alias->aliastype = Init::ALIAS_TYPE_POI;
        $alias->cod = $poi->id;
        $alias->alias = 'POI-ALIAS';
        $this->assertTrue($alias->save(), 'no se pudo guardar el alias del punto de interés');
        $aliasId = $alias->id;

        $this->assertTrue($poi->delete());

        $check = new Alias();
        $this->assertFalse($check->load($aliasId), 'el alias debería borrarse al borrar el punto de interés');

        $city->delete();
        $province->delete();
        $country->delete();
    }

    /**
     * @description Alias de una **provincia**: se crea y se borra en cascada al borrar la provincia.
     */
    public function testProvinceAliasAndCascadeDelete(): void
    {
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save(), 'no se pudo crear el país');

        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');

        $alias = new Alias();
        $alias->aliastype = Init::ALIAS_TYPE_PROVINCE;
        $alias->cod = $province->idprovincia;
        $alias->alias = 'PROVINCE-ALIAS';
        $this->assertTrue($alias->save(), 'no se pudo guardar el alias de la provincia');
        $aliasId = $alias->id;

        $this->assertTrue($province->delete());

        $check = new Alias();
        $this->assertFalse($check->load($aliasId), 'el alias debería borrarse al borrar la provincia');

        $country->delete();
    }

    protected function ensureType(string $code, string $description): void
    {
        $type = new AliasType();
        if (false === $type->loadWhere([Where::eq('aliastype', $code)])) {
            $type->aliastype = $code;
            $type->description = $description;
            $type->save();
        }
    }

    protected function setUp(): void
    {
        // aseguramos los tipos del catálogo (normalmente los siembra Init::update)
        $this->ensureType(Init::ALIAS_TYPE_COUNTRY, 'País');
        $this->ensureType(Init::ALIAS_TYPE_PROVINCE, 'Provincia');
        $this->ensureType(Init::ALIAS_TYPE_CITY, 'Ciudad');
        $this->ensureType(Init::ALIAS_TYPE_POI, 'Punto de interés');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
