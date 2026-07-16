# AliasLocalizaciones — guía para Claude Code

Plugin **satélite** de **[Alias](../Alias/CLAUDE.md)** que dota de **alias polimórficos** a las
fichas de **países, provincias, ciudades y puntos de interés** del core de FacturaScripts.
`version 1.00`, `min_version 2025`, `min_php 8`, `require = 'Alias'`. Licencia **GPL v3**.
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
