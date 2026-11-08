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

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Dinamic\Model\Alias;
use FacturaScripts\Dinamic\Model\AliasType;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Provincia;
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
 * - **No se borra** una localización que tenga alias propios o algo debajo (provincias, ciudades o
 *   puntos), aunque eso no tenga alias: se vacía de abajo arriba. El aviso dice qué lo impide.
 * - Al **renombrar** la clave de un país, una provincia o una ciudad, sus alias la siguen.
 * - Se **rechaza** crear un alias para una localización inexistente (FK simulada).
 */
final class AliasLocalizacionesTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** prefijo de los alias de prueba, para limpiar los que deje una pasada muerta a medias */
    const ALIAS_PREFIX = 'LOCTEST-';

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
     * @description Una **ciudad** con alias no se borra; sin el alias, sí.
     */
    public function testCityWithAliasCannotBeDeleted(): void
    {
        [$country, $province, $city] = $this->makeCity();
        $alias = $this->makeAlias(Init::ALIAS_TYPE_CITY, $city->idciudad, 'CITY-ALIAS');

        $this->assertRefused($city, 'location-has-aliases');
        $this->assertTrue($alias->exists(), 'el alias no debe borrarse');

        $this->assertTrue($alias->delete());
        $this->assertTrue($city->delete(), 'sin alias ni puntos, la ciudad se borra');
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Una **ciudad** con puntos de interés, aunque no tengan alias, no se borra.
     */
    public function testCityWithPoisCannotBeDeleted(): void
    {
        [$country, $province, $city] = $this->makeCity();
        $poi = $this->makePoi($city);

        $this->assertRefused($city, 'location-has-pois');
        $this->assertTrue($poi->exists(), 'el punto de interés no debe borrarse');

        $this->assertTrue($poi->delete());
        $this->assertTrue($city->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Al renombrar `idciudad`, los alias de la ciudad pasan al código nuevo.
     */
    public function testCityRenameMovesAliases(): void
    {
        [$country, $province, $city] = $this->makeCity();
        $alias = $this->makeAlias(Init::ALIAS_TYPE_CITY, $city->idciudad, 'CITY-RENAME');

        $newId = $this->freeId(Ciudad::class);
        $this->assertTrue($city->changeId($newId), 'no se pudo renombrar la ciudad');
        $this->assertAliasCod($alias, (string)$newId);

        $this->assertTrue($alias->delete());
        $this->assertTrue($city->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Un **país** con alias no se borra; sin el alias, sí.
     */
    public function testCountryWithAliasCannotBeDeleted(): void
    {
        $country = $this->makeCountry();
        $alias = $this->makeAlias(Init::ALIAS_TYPE_COUNTRY, $country->codpais, 'COUNTRY-ALIAS');

        $this->assertRefused($country, 'location-has-aliases');
        $this->assertTrue($alias->exists(), 'el alias no debe borrarse');

        $this->assertTrue($alias->delete());
        $this->assertTrue($country->delete(), 'sin alias ni provincias, el país se borra');
    }

    /**
     * @description Un **país** con provincias, aunque no tengan alias, no se borra.
     */
    public function testCountryWithProvincesCannotBeDeleted(): void
    {
        $country = $this->makeCountry();
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');

        $this->assertRefused($country, 'location-has-provinces');
        $this->assertTrue($province->exists(), 'la provincia no debe borrarse');

        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Al renombrar `codpais`, los alias del país pasan al código nuevo.
     */
    public function testCountryRenameMovesAliases(): void
    {
        $country = $this->makeCountry();
        $alias = $this->makeAlias(Init::ALIAS_TYPE_COUNTRY, $country->codpais, 'COUNTRY-RENAME');

        $newCode = 'TR' . mt_rand(1000, 9999);
        $this->assertTrue($country->changeId($newCode), 'no se pudo renombrar el país');
        $this->assertAliasCod($alias, $newCode);

        $this->assertTrue($alias->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Un **punto de interés** con alias no se borra; sin el alias, sí.
     */
    public function testPoiWithAliasCannotBeDeleted(): void
    {
        [$country, $province, $city] = $this->makeCity();
        $poi = $this->makePoi($city);
        $alias = $this->makeAlias(Init::ALIAS_TYPE_POI, $poi->id, 'POI-ALIAS');

        $this->assertRefused($poi, 'location-has-aliases');
        $this->assertTrue($alias->exists(), 'el alias no debe borrarse');

        $this->assertTrue($alias->delete());
        $this->assertTrue($poi->delete(), 'sin alias, el punto de interés se borra');
        $this->assertTrue($city->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Una **provincia** con alias no se borra; sin el alias, sí.
     */
    public function testProvinceWithAliasCannotBeDeleted(): void
    {
        $country = $this->makeCountry();
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');
        $alias = $this->makeAlias(Init::ALIAS_TYPE_PROVINCE, $province->idprovincia, 'PROVINCE-ALIAS');

        $this->assertRefused($province, 'location-has-aliases');
        $this->assertTrue($alias->exists(), 'el alias no debe borrarse');

        $this->assertTrue($alias->delete());
        $this->assertTrue($province->delete(), 'sin alias ni ciudades, la provincia se borra');
        $this->assertTrue($country->delete());
    }

    /**
     * @description Una **provincia** con ciudades, aunque no tengan alias, no se borra.
     */
    public function testProvinceWithCitiesCannotBeDeleted(): void
    {
        [$country, $province, $city] = $this->makeCity();

        $this->assertRefused($province, 'location-has-cities');
        $this->assertTrue($city->exists(), 'la ciudad no debe borrarse');

        $this->assertTrue($city->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    /**
     * @description Al renombrar `idprovincia`, los alias de la provincia pasan al código nuevo.
     */
    public function testProvinceRenameMovesAliases(): void
    {
        $country = $this->makeCountry();
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');
        $alias = $this->makeAlias(Init::ALIAS_TYPE_PROVINCE, $province->idprovincia, 'PROVINCE-RENAME');

        $newId = $this->freeId(Provincia::class);
        $this->assertTrue($province->changeId($newId), 'no se pudo renombrar la provincia');
        $this->assertAliasCod($alias, (string)$newId);

        $this->assertTrue($alias->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    protected function setUp(): void
    {
        // un alias de prueba que sobrevive a su localización (test cortado a medias) bloquearía
        // el borrado de la siguiente que reciba su mismo id
        Alias::table()->whereLike('alias', self::ALIAS_PREFIX . '%')->delete();

        // aseguramos los tipos del catálogo, como hace Init::update
        AliasType::ensure(Init::ALIAS_TYPE_COUNTRY, 'País', Init::PLUGIN_NAME);
        AliasType::ensure(Init::ALIAS_TYPE_PROVINCE, 'Provincia', Init::PLUGIN_NAME);
        AliasType::ensure(Init::ALIAS_TYPE_CITY, 'Ciudad', Init::PLUGIN_NAME);
        AliasType::ensure(Init::ALIAS_TYPE_POI, 'Punto de interés', Init::PLUGIN_NAME);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function assertAliasCod(Alias $alias, string $expected): void
    {
        $reload = new Alias();
        $this->assertTrue($reload->load($alias->id));
        $this->assertSame($expected, (string)$reload->cod, 'el alias debe seguir a la clave renombrada');
    }

    /** el borrado se niega, avisa con la clave dada y la localización sigue */
    private function assertRefused($model, string $key): void
    {
        MiniLog::clear();
        $this->assertFalse($model->delete(), 'no debe borrarse: ' . $key);
        $this->assertContains($key, array_column(MiniLog::read('', ['warning']), 'original'));
        $this->assertTrue($model->exists(), 'la localización debe seguir existiendo');
        MiniLog::clear();
    }

    /** un id numérico que no usa nadie en la tabla del modelo */
    private function freeId(string $class): int
    {
        do {
            $id = mt_rand(900000, 999999);
        } while ((new $class())->load($id));
        return $id;
    }

    private function makeAlias(string $type, $cod, string $text): Alias
    {
        $alias = new Alias();
        $alias->aliastype = $type;
        $alias->cod = (string)$cod;
        $alias->alias = self::ALIAS_PREFIX . $text . '-' . mt_rand(1, 99999);
        $this->assertTrue($alias->save(), 'no se pudo guardar el alias ' . $text);
        return $alias;
    }

    private function makeCity(): array
    {
        $country = $this->makeCountry();
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save(), 'no se pudo crear la provincia');
        $city = $this->getRandomCity($province->idprovincia);
        $this->assertTrue($city->save(), 'no se pudo crear la ciudad');
        return [$country, $province, $city];
    }

    private function makeCountry(): Pais
    {
        // getRandomCountry() elige entre 99 códigos: con restos de una pasada muerta a medias,
        // reutilizaría un país que ya tiene provincias. Se busca uno libre.
        $country = $this->getRandomCountry();
        do {
            $country->codpais = 'TA' . mt_rand(1000, 9999);
        } while ((new Pais())->load($country->codpais));
        $this->assertTrue($country->save(), 'no se pudo crear el país');
        return $country;
    }

    private function makePoi(Ciudad $city): PuntoInteresCiudad
    {
        $poi = new PuntoInteresCiudad();
        $poi->idciudad = $city->idciudad;
        $poi->name = 'Test POI ' . mt_rand(1, 99999);
        $this->assertTrue($poi->save(), 'no se pudo crear el punto de interés');
        return $poi;
    }
}
