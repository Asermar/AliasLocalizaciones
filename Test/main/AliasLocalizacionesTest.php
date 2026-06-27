<?php
/**
 * Copyright (C) 2026 Alexis Serafin <alexis@okodex.com>
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
 * @author Alexis Serafin <alexis@okodex.com>
 */
final class AliasLocalizacionesTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

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
