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
use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Lib\MultiRequestProtection;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Alias;
use FacturaScripts\Dinamic\Model\AliasType;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\AliasLocalizaciones\Init;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Alexis Serafín <alexis@okodex.com>
 *
 * @description
 * ## La pestaña de alias de cada ficha geográfica sólo toca los alias de esa ficha
 *
 * Se ejecuta el controlador real (`privateCore`) con la petición en `$_GET`/`$_POST`. Con la ficha del
 * **país** se prueba todo (alta, edición, borrado uno a uno y en lote, acción por GET, caso normal, y que
 * la pestaña sigue usable tras un rechazo); con las de **provincia**, **ciudad** y **punto de interés**,
 * que no editan ni borran un alias ajeno del mismo tipo ni de otro.
 */
final class AliasLocalizacionesPestanaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    const PREFIX = 'LOCTAB-';
    const TAB = 'EditAliasLocalizacion';

    /** @var User */
    private $admin;

    /** @var array alias por clave: country, countryB, province, province2, city, city2, poi, poi2 */
    private $aliases = [];

    /** @var array entidades por clave, en orden de creación */
    private $entities = [];

    /** @var array */
    private $globals = [];

    /** @var mixed el usuario de sesión de antes, que se restaura: los alias guardan su nick con FK */
    private $sessionUser;

    /**
     * @description La acción anulada no deja la pestaña rota: el borrado legítimo siguiente funciona.
     */
    public function testAfterRejectionTheTabStillWorks(): void
    {
        $this->runCountry(['action' => 'delete', 'code' => $this->aliases['countryB']->id]);
        $this->assertTrue($this->aliases['countryB']->exists());

        $this->runCountry(['action' => 'delete', 'code' => $this->aliases['country']->id]);
        $this->assertFalse($this->aliases['country']->exists(), 'tras un rechazo, el borrado legítimo debe funcionar');
    }

    /**
     * @description El borrado en lote con un alias propio y uno ajeno se rechaza entero.
     */
    public function testBulkDeleteWithForeignAliasIsRejected(): void
    {
        $controller = $this->runCountry(['action' => 'delete', 'codes' => [$this->aliases['country']->id, $this->aliases['countryB']->id]]);
        $this->assertTrue($this->aliases['country']->exists(), 'no debe borrarse ninguno');
        $this->assertTrue($this->aliases['countryB']->exists(), 'no debe borrarse ninguno');
        $this->assertRejected($controller);
    }

    /**
     * @description La ficha de la **ciudad** no edita ni borra alias ajenos.
     */
    public function testCityTabRejectsForeignAliases(): void
    {
        $this->assertFichaRejects('EditCiudad', $this->entities['city']->idciudad, Init::ALIAS_TYPE_CITY, ['city2', 'country']);
    }

    /**
     * @description La ficha del **país** no edita ni borra un alias de otro país ni de otro tipo.
     */
    public function testCountryTabRejectsForeignAliases(): void
    {
        $this->assertFichaRejects('EditPais', $this->entities['country']->codpais, Init::ALIAS_TYPE_COUNTRY, ['countryB', 'city']);
    }

    /**
     * @description La acción también se anula si llega por la query string (GET) con los datos en POST.
     */
    public function testDeleteForeignAliasWithActionInQueryIsRejected(): void
    {
        $controller = $this->runCountry(['code' => $this->aliases['countryB']->id], ['action' => 'delete']);
        $this->assertTrue($this->aliases['countryB']->exists(), 'el alias ajeno no debe borrarse');
        $this->assertRejected($controller);
    }

    /**
     * @description No crea un alias con un tipo o un código que no sean los del país abierto.
     */
    public function testInsertForeignAliasIsRejected(): void
    {
        $cases = [
            [Init::ALIAS_TYPE_COUNTRY, $this->entities['countryB']->codpais],
            [Init::ALIAS_TYPE_CITY, (string)$this->entities['city']->idciudad],
        ];
        foreach ($cases as [$type, $cod]) {
            $text = self::PREFIX . 'NEW-' . mt_rand(1, 99999);
            $controller = $this->runCountry(['action' => 'insert', 'aliastype' => $type, 'cod' => $cod, 'alias' => $text]);
            $this->assertSame(0, Alias::table()->whereEq('alias', $text)->count(), 'no debe crearse el alias ajeno');
            $this->assertRejected($controller);
        }
    }

    /**
     * @description Una ficha **nueva** de país (sin código) sigue funcionando: se puede crear el país.
     */
    public function testNewCountryFormStillWorks(): void
    {
        $code = $this->freeCountryCode();
        $this->runController('EditPais', 'EditPais', ['action' => 'insert', 'codpais' => $code, 'nombre' => 'País pestaña ' . $code], [], '');

        $created = new Pais();
        $this->assertTrue($created->load($code), 'la ficha nueva debe poder crear el país');
        $this->assertTrue($created->delete());
    }

    /**
     * @description El caso normal: crear, editar y borrar un alias del país abierto.
     */
    public function testOwnAliasesStillWork(): void
    {
        $cod = $this->entities['country']->codpais;
        $text = self::PREFIX . 'MINE-' . mt_rand(1, 99999);
        $this->runCountry(['action' => 'insert', 'aliastype' => Init::ALIAS_TYPE_COUNTRY, 'cod' => $cod, 'alias' => $text]);
        $created = new Alias();
        $this->assertTrue($created->loadWhere([Where::eq('alias', $text)]), 'debe crearse el alias propio');

        $edited = $text . '-B';
        $this->runCountry([
            'action' => 'edit',
            'code' => $created->id,
            'id' => $created->id,
            'aliastype' => Init::ALIAS_TYPE_COUNTRY,
            'cod' => $cod,
            'alias' => $edited,
        ]);
        $created->load($created->id);
        $this->assertSame($edited, $created->alias, 'debe editarse el alias propio');

        $this->runCountry(['action' => 'delete', 'code' => $created->id]);
        $this->assertFalse($created->exists(), 'debe borrarse el alias propio');
    }

    /**
     * @description La ficha del **punto de interés** no edita ni borra alias ajenos.
     */
    public function testPoiTabRejectsForeignAliases(): void
    {
        $this->assertFichaRejects('EditPuntoInteresCiudad', $this->entities['poi']->id, Init::ALIAS_TYPE_POI, ['poi2', 'city']);
    }

    /**
     * @description La ficha de la **provincia** no edita ni borra alias ajenos.
     */
    public function testProvinceTabRejectsForeignAliases(): void
    {
        $this->assertFichaRejects('EditProvincia', $this->entities['province']->idprovincia, Init::ALIAS_TYPE_PROVINCE, ['province2', 'country']);
    }

    protected function setUp(): void
    {
        $this->globals = [$_GET, $_POST];
        $this->sessionUser = Session::get('user');
        Alias::table()->whereLike('alias', self::PREFIX . '%')->delete();
        AliasType::ensure(Init::ALIAS_TYPE_COUNTRY, 'País', Init::PLUGIN_NAME);
        AliasType::ensure(Init::ALIAS_TYPE_PROVINCE, 'Provincia', Init::PLUGIN_NAME);
        AliasType::ensure(Init::ALIAS_TYPE_CITY, 'Ciudad', Init::PLUGIN_NAME);
        AliasType::ensure(Init::ALIAS_TYPE_POI, 'Punto de interés', Init::PLUGIN_NAME);

        $this->admin = new User();
        $this->admin->nick = 'loctab' . mt_rand(1, 999999);
        $this->admin->email = $this->admin->nick . '@example.test';
        $this->admin->setPassword(Tools::password());
        $this->admin->admin = true;
        $this->assertTrue($this->admin->save());
        Session::set('user', $this->admin);

        $this->entities['country'] = $this->makeCountry();
        $this->entities['countryB'] = $this->makeCountry();
        $this->entities['province'] = $this->makeProvince($this->entities['country']->codpais);
        $this->entities['province2'] = $this->makeProvince($this->entities['country']->codpais);
        $this->entities['city'] = $this->makeCity($this->entities['province']->idprovincia);
        $this->entities['city2'] = $this->makeCity($this->entities['province']->idprovincia);
        $this->entities['poi'] = $this->makePoi($this->entities['city']->idciudad);
        $this->entities['poi2'] = $this->makePoi($this->entities['city']->idciudad);

        $types = [
            'country' => Init::ALIAS_TYPE_COUNTRY,
            'countryB' => Init::ALIAS_TYPE_COUNTRY,
            'province' => Init::ALIAS_TYPE_PROVINCE,
            'province2' => Init::ALIAS_TYPE_PROVINCE,
            'city' => Init::ALIAS_TYPE_CITY,
            'city2' => Init::ALIAS_TYPE_CITY,
            'poi' => Init::ALIAS_TYPE_POI,
            'poi2' => Init::ALIAS_TYPE_POI,
        ];
        foreach ($types as $key => $type) {
            $this->aliases[$key] = $this->makeAlias($type, (string)$this->entities[$key]->id(), strtoupper($key));
        }
    }

    protected function tearDown(): void
    {
        [$_GET, $_POST] = $this->globals;

        // de abajo arriba: una localización con alias o con hijos no se borra
        Alias::table()->whereLike('alias', self::PREFIX . '%')->delete();
        foreach (array_reverse($this->entities) as $entity) {
            $entity->delete();
        }

        Session::set('user', $this->sessionUser);
        $this->admin->delete();
        $this->logErrors();
    }

    /** con la ficha dada abierta, ni borrar ni editar los alias ajenos indicados */
    private function assertFichaRejects(string $page, $code, string $type, array $foreignKeys): void
    {
        foreach ($foreignKeys as $key) {
            $foreign = $this->aliases[$key];

            $controller = $this->runController($page, self::TAB, ['action' => 'delete', 'code' => $foreign->id], [], (string)$code);
            $this->assertTrue($foreign->exists(), $page . ' no debe borrar el alias ' . $key);
            $this->assertRejected($controller);

            $controller = $this->runController($page, self::TAB, [
                'action' => 'edit',
                'code' => $foreign->id,
                'id' => $foreign->id,
                'aliastype' => $type,
                'cod' => (string)$code,
                'alias' => self::PREFIX . 'HACK-' . mt_rand(1, 99999),
            ], [], (string)$code);
            $this->assertUnchanged($foreign);
            $this->assertRejected($controller);
        }
    }

    private function assertRejected($controller): void
    {
        $this->assertContains('alias-tab-out-of-scope', array_column(MiniLog::read('', ['warning']), 'original'));
        $this->assertSame('', (string)$controller->request->input('action', ''), 'la acción debe quedar vacía en el cuerpo');
        $this->assertSame('', (string)$controller->request->query('action', ''), 'la acción debe quedar vacía en la query');
        MiniLog::clear();
    }

    private function assertUnchanged(Alias $alias): void
    {
        $reload = new Alias();
        $this->assertTrue($reload->load($alias->id), 'el alias debe seguir existiendo');
        $this->assertSame($alias->alias, $reload->alias, 'el alias no debe cambiar');
        $this->assertSame($alias->aliastype, $reload->aliastype, 'el tipo no debe cambiar');
        $this->assertSame((string)$alias->cod, (string)$reload->cod, 'el código no debe cambiar');
    }

    private function freeCountryCode(): string
    {
        do {
            $code = 'TB' . mt_rand(1000, 9999);
        } while ((new Pais())->load($code));
        return $code;
    }

    private function makeAlias(string $type, string $cod, string $text): Alias
    {
        $alias = new Alias();
        $alias->aliastype = $type;
        $alias->cod = $cod;
        $alias->alias = self::PREFIX . $text . '-' . mt_rand(1, 99999);
        $this->assertTrue($alias->save(), 'no se pudo guardar el alias ' . $text);
        return $alias;
    }

    private function makeCity(int $idprovincia): Ciudad
    {
        $city = $this->getRandomCity($idprovincia);
        $this->assertTrue($city->save());
        return $city;
    }

    private function makeCountry(): Pais
    {
        $country = $this->getRandomCountry();
        $country->codpais = $this->freeCountryCode();
        $this->assertTrue($country->save());
        return $country;
    }

    private function makePoi(int $idciudad): PuntoInteresCiudad
    {
        $poi = new PuntoInteresCiudad();
        $poi->idciudad = $idciudad;
        $poi->name = 'Test POI ' . mt_rand(1, 99999);
        $this->assertTrue($poi->save());
        return $poi;
    }

    private function makeProvince(string $codpais): Provincia
    {
        $province = $this->getRandomProvince($codpais);
        $this->assertTrue($province->save());
        return $province;
    }

    private function runCountry(array $post, array $query = [])
    {
        return $this->runController('EditPais', self::TAB, $post, $query, $this->entities['country']->codpais);
    }

    /**
     * Ejecuta el controlador como lo haría el navegador: petición en $_GET/$_POST antes de
     * construirlo, token válido para la semilla que pondrá privateCore, y privateCore real.
     */
    private function runController(string $page, string $tab, array $post, array $query, string $code)
    {
        $_GET = array_merge($code === '' ? [] : ['code' => $code], $query);

        $protection = new MultiRequestProtection();
        $protection->clearSeed();
        $protection->addSeed($this->admin->nick);
        $post['multireqtoken'] = $protection->newToken();
        $protection->clearSeed();

        $_POST = array_merge(['activetab' => $tab], $post);

        MiniLog::clear();
        $class = '\\FacturaScripts\\Dinamic\\Controller\\' . $page;
        $controller = new $class($page);
        $response = new Response();
        $controller->privateCore($response, $this->admin, new ControllerPermissions($this->admin, $page));
        return $controller;
    }
}
