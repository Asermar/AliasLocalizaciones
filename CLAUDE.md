# AliasLocalizaciones — guía para Claude Code

Plugin **satélite** de **[Alias](../Alias/CLAUDE.md)** que dota de **alias polimórficos** a las
fichas de **países, provincias, ciudades y puntos de interés** del core de FacturaScripts.
`version 1.02`, `min_version 2025`, `min_php 8`, `require = 'Alias'`. Licencia **GPL v3**.
Copyright **Okodex** / autor **Alexis Serafín**. Es submódulo dentro de Mesa_FS.

> **El mecanismo general (alias polimórfico, catálogo `aliastypes`, FK simulada, favorito único,
> patrón de integración de 7 pasos, reparto de responsabilidades) está documentado a fondo en
> `../Alias/CLAUDE.md`.** Este documento solo cubre lo específico de AliasLocalizaciones. No
> re-explicar aquí la teoría; enlazar a la sección correspondiente de `Alias/CLAUDE.md`.

## Qué aporta este satélite

Sigue el molde estándar de satélite (ver `Alias/CLAUDE.md`, sección *"Cómo lo integra un plugin
satélite"*). Concretado aquí:

- **Cuatro tipos registrados** (`Init::update()` → `AliasType::ensure(...)`), constantes en `Init`:
  | Constante | Código de tipo | Modelo destino | Ficha (EditController) | `cod` (en `delete()`) |
  |-----------|----------------|----------------|------------------------|-----------------------|
  | `ALIAS_TYPE_COUNTRY` | `country` | `Pais` | `EditPais` | `codpais` |
  | `ALIAS_TYPE_PROVINCE` | `province` | `Provincia` | `EditProvincia` | `idprovincia` |
  | `ALIAS_TYPE_CITY` | `city` | `Ciudad` | `EditCiudad` | `idciudad` |
  | `ALIAS_TYPE_POI` | `poi` | `PuntoInteresCiudad` | `EditPuntoInteresCiudad` | `id` |
  - `Init::PLUGIN_NAME = 'AliasLocalizaciones'` es el `plugin` responsable de los cuatro tipos.

- **`Extension\Model\Alias::test()`** — FK simulada con el mapa de los 4 tipos a
  `Pais`/`Provincia`/`Ciudad`/`PuntoInteresCiudad`; para cualquier otro `aliastype` devuelve `true`
  (invariante de convivencia; ver `Alias/CLAUDE.md`).

- **Cascada** — una extensión de modelo por entidad (`Extension\Model\{Pais,Provincia,Ciudad,
  PuntoInteresCiudad}::delete()`), cada una borra los `Alias` de su tipo con `cod` = la PK de la
  entidad (columna `cod` de la tabla anterior).

- **UI** — cuatro extensiones de controlador (`Extension\Controller\Edit{Pais,Provincia,Ciudad,
  PuntoInteresCiudad}`), cada una con `createViews()` (añade la EditListView) y `loadData()` (filtra
  por su `aliastype` + `cod = model->id()`; ese `Where` autorrellena el alta).

- **Una única XMLView compartida** (`XMLView/EditAliasLocalizacion.xml`): las cuatro fichas la
  reutilizan porque las cuatro EditListView se llaman igual, `'EditAliasLocalizacion'`
  (`addEditListView('EditAliasLocalizacion', 'Alias', 'aliases', 'fa-solid fa-tags')` en las cuatro).
  **Diferencia clave con AliasClientes/AliasBusCanarias**, que usan un XMLView por ficha. Columnas:
  `id`/`aliastype`/`cod` en `display="none"`, `alias` y `favorite` visibles.

## Extensión de tabla (`Extension/Table/puntos_interes_ciudades.xml`)

Dos columnas añadidas a la tabla del core, que necesita el volcado de POI de la BD de reservas
(`Mesa_FS/bin/migracion-reservas/importar_poi_localizaciones.php`):

| Columna | Tipo | Para qué | ¿Andamiaje? |
|---|---|---|---|
| `bs_parada` | `varchar(250)` | El nombre de parada del POI legacy. Lo consume `getPOIParada()` de Mesa_Reservas al generar los ODS de importación | **No**: dato de negocio |
| `bs_legacy_idpoi` | `integer` | El `idPOI` legacy, para que la reproyección emita un identificador estable mientras Mesa_Reservas siga vivo | **Sí**: se retira cuando la importación viva dentro de FS |

> `bs_parada` **ya existía en la BD como columna huérfana** antes de declararla aquí, de un intento
> anterior a la conversión de los plugins en submódulos. Declararla la volvió legítima sin ningún
> `ALTER`. Es el ejemplo de libro de que `DbUpdater` nunca elimina columnas: lo que se crea, queda.

Se declaran en **este** plugin, y no en BusCanarias, porque es el dueño del `aliastype='poi'` y ya
extiende `PuntoInteresCiudad` por modelo y por controlador: no añade dependencias nuevas.

## Particularidades

- Plugin **más pequeño**: sin `Cron.php` ni `README.md`; solo traducciones **es_ES** y **en_EN**.
  Las claves de tipo (`country`/`province`/`city`/`poi`) coinciden con los códigos de `aliastype`,
  así que el filtro de `ListAlias` (traduce la etiqueta con `Tools::trans`) las resuelve.

## Tests (`Test/main/AliasLocalizacionesTest.php`)

`install-plugins.txt` = `Alias,AliasLocalizaciones`. Cinco tests: alta + **borrado en cascada** para
país, provincia, ciudad y punto de interés (construyendo la jerarquía país→provincia→ciudad→POI con
`RandomDataTrait`), y **rechazo** de `cod` inexistente (`testAliasRejectsMissingLocation`, limpia el
MiniLog con `MiniLog::clear()`). `setUp()` siembra los cuatro tipos con un helper `ensureType()`.
Usa `RandomDataTrait` (`getRandomCountry`/`getRandomProvince`/`getRandomCity`) y `LogErrorsTrait`.

## Convenciones y gotchas

- **Rebuild/deploy** tras tocar `Init.php`, extensiones o el XMLView (regenera `Dinamic`).
  Submódulo: commitear aquí y actualizar el puntero en Mesa_FS.
- Al ser XMLView compartida, cualquier cambio en `EditAliasLocalizacion.xml` afecta a las cuatro
  fichas a la vez.
- Mantener la invariante de `test()` (devolver `true` para tipos ajenos) para no romper la
  convivencia con AliasClientes / AliasBusCanarias sobre el mismo modelo `Alias`.
- No crear `aliastypes` desde la UI; siempre por `AliasType::ensure()` en `update()`.
- Cabeceras de autoría Okodex/Alexis, GPL v3 (ver `Alias/CLAUDE.md`).

## Dudas / notas

- No hay dudas específicas: implementación limpia y homogénea con el resto de satélites.
