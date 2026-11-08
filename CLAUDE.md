# AliasLocalizaciones — guía para Claude Code

Plugin **satélite** de **[Alias](../Alias/CLAUDE.md)** que dota de **alias polimórficos** a las
fichas de **países, provincias, ciudades y puntos de interés** del core de FacturaScripts.
`version 1.03`, `min_version 2025`, `min_php 8`, `require = 'Alias'`,
`require_min = 'Alias:1.03'`. Licencia **GPL v3**.
Copyright **Okodex** / autor **Alexis Serafín**. Es submódulo dentro de Mesa_FS.

> **El mecanismo general (alias polimórfico, catálogo `aliastypes`, FK simulada, favorito único,
> patrón de integración de 7 pasos, reparto de responsabilidades) está documentado a fondo en
> `../Alias/CLAUDE.md`.** Este documento solo cubre lo específico de AliasLocalizaciones. No
> re-explicar aquí la teoría; enlazar a la sección correspondiente de `Alias/CLAUDE.md`.

## Dónde se desarrolla

**Cliente prototipo: `Mesa_FS`.** Aquí se desarrolla este plugin. Las demás instalaciones lo
**consumen** como submódulo fijado a un tag, y son fuente de mejoras y arreglos.

Si lees esto desde otra instalación estás en un consumidor, y eso **no te prohíbe trabajar aquí**:
a veces el fallo solo se reproduce en este entorno y arreglarlo desde el prototipo sería trabajar a
ciegas. Lo que se pide es **preguntarlo antes**, no decidirlo en silencio — ni negarse.

Se trabaje donde se trabaje, el arreglo vive en el repo del plugin y hay que **mover el pin** de
esta instalación para que lo reciba: commitear no basta.

## Qué aporta este satélite

Sigue el molde estándar de satélite (ver `Alias/CLAUDE.md`, sección *"Cómo lo integra un plugin
satélite"*). Concretado aquí:

- **Cuatro tipos registrados** (`Init::update()` → `AliasType::ensure(...)`), constantes en `Init`:
  | Constante | Código de tipo | Modelo destino | Ficha (EditController) | `cod` |
  |-----------|----------------|----------------|------------------------|-----------------------|
  | `ALIAS_TYPE_COUNTRY` | `country` | `Pais` | `EditPais` | `codpais` |
  | `ALIAS_TYPE_PROVINCE` | `province` | `Provincia` | `EditProvincia` | `idprovincia` |
  | `ALIAS_TYPE_CITY` | `city` | `Ciudad` | `EditCiudad` | `idciudad` |
  | `ALIAS_TYPE_POI` | `poi` | `PuntoInteresCiudad` | `EditPuntoInteresCiudad` | `id` |
  - `Init::PLUGIN_NAME = 'AliasLocalizaciones'` es el `plugin` responsable de los cuatro tipos.

- **`Extension\Model\Alias::test()`** — FK simulada con el mapa de los 4 tipos a
  `Pais`/`Provincia`/`Ciudad`/`PuntoInteresCiudad`; para cualquier otro `aliastype` devuelve `true`
  (invariante de convivencia; ver `Alias/CLAUDE.md`).

- **Negativa a borrar, no cascada** (decisión de Alexis, 8-nov-2026) — una extensión de modelo por
  entidad (`Extension\Model\{Pais,Provincia,Ciudad,PuntoInteresCiudad}::deleteBefore()`). No se borra
  una localización que tenga **alias propios** o **algo debajo** (provincias de un país, ciudades de
  una provincia, puntos de una ciudad), aunque eso no tenga alias: se vacía de abajo arriba, y el aviso
  dice qué lo impide (`location-has-aliases`, `-provinces`, `-cities`, `-pois`, con el recuento). Va en
  `deleteBefore` porque los hijos los borra la base por **FK ON DELETE CASCADE** sin pasar por el
  modelo: la cascada de antes sólo limpiaba los alias del nivel borrado y dejaba huérfanos los de los
  descendientes. Motivo de la decisión: borrar con alias existentes es probablemente un error, y en
  cascada borraría mucho que luego habría que reconstruir; en la interfaz de alias se borran con
  comodidad.
- **Renombrado de clave** — `Pais`, `Provincia` y `Ciudad` (`codpais`, `idprovincia`, `idciudad`, que se
  pueden editar) llevan `changePrimaryColumnValueBefore/After`: el Before guarda el código viejo en
  un almacén privado de la extensión, porque `changeId()` del core sobrescribe `original` con el nuevo
  antes de llamar al After, y el After hace un único UPDATE de `alias.cod` al nuevo. El id del punto
  de interés es serial y no se edita.

- **UI** — cuatro extensiones de controlador (`Extension\Controller\Edit{Pais,Provincia,Ciudad,
  PuntoInteresCiudad}`), cada una con `createViews()` (añade la EditListView) y `loadData()` (filtra
  por su `aliastype` + `cod = model->id()`; ese `Where` autorrellena el alta). `createViews()` llama
  además a `AliasTabGuard::protect()` (Alias): la pestaña sólo crea, edita o borra alias de la ficha
  abierta. Lo prueba `Test/main/AliasLocalizacionesPestanaTest.php` con las cuatro fichas.

- **Una única XMLView compartida** (`XMLView/EditAliasLocalizacion.xml`): las cuatro fichas la
  reutilizan porque las cuatro EditListView se llaman igual, `'EditAliasLocalizacion'`
  (`addEditListView('EditAliasLocalizacion', 'Alias', 'aliases', 'fa-solid fa-tags')` en las cuatro).
  **Diferencia clave con AliasClientes/AliasBusCanarias**, que usan un XMLView por ficha. Columnas:
  `id`/`aliastype`/`cod` en `display="none"`, `alias` y `favorite` visibles.

## Extensión de tabla (`Extension/Table/puntos_interes_ciudades.xml`)

Dos columnas añadidas a la tabla del core, que necesita el volcado de puntos de interés desde un
sistema de reservas anterior:

| Columna | Tipo | Para qué | ¿Andamiaje? |
|---|---|---|---|
| `bs_parada` | `varchar(250)` | El nombre de parada del punto en el sistema anterior, que éste usa al generar sus ficheros de importación | **No**: dato de negocio |
| `bs_legacy_idpoi` | `integer` | El identificador del punto en el sistema anterior, para emitir uno estable mientras ese sistema siga vivo | **Sí**: se retira cuando la importación viva dentro de FS |

> `DbUpdater` nunca elimina columnas: declarar una que ya exista en la base con el mismo tipo la
> vuelve legítima sin ningún `ALTER`.

Se declaran en **este** plugin, y no en BusCanarias, porque es el dueño del `aliastype='poi'` y ya
extiende `PuntoInteresCiudad` por modelo y por controlador: no añade dependencias nuevas.

## Particularidades

- Plugin **más pequeño**: sin `Cron.php` ni `README.md`; solo traducciones **es_ES** y **en_EN**.
  Las claves de tipo (`country`/`province`/`city`/`poi`) coinciden con los códigos de `aliastype`,
  así que el filtro de `ListAlias` (traduce la etiqueta con `Tools::trans`) las resuelve.

## Tests (`Test/main/AliasLocalizacionesTest.php`)

`install-plugins.txt` = `Alias,AliasLocalizaciones`. Once tests: por nivel, la **negativa** a borrar
con alias y con hijos sin alias (y que se borra una vez vacío), el **renombrado** de país, provincia y
ciudad, y el **rechazo** de `cod` inexistente. Las negativas se comprueban por la clave del aviso
(`MiniLog::read()[...]['original']`). `setUp()` asegura los cuatro tipos con `AliasType::ensure()`
(el catálogo no admite altas a mano) y borra los alias de prueba (`LOCTEST-…`) que hubiera dejado una
pasada muerta a medias: si no, uno huérfano bloquea el borrado de la siguiente localización que reciba
su id. El país se crea con un código libre, porque `getRandomCountry()` elige entre sólo 99.

## Convenciones y gotchas

- **Rebuild/deploy** tras tocar `Init.php`, extensiones o el XMLView (regenera `Dinamic`).
  Submódulo: commitear aquí y actualizar el puntero en Mesa_FS.
- Al ser XMLView compartida, cualquier cambio en `EditAliasLocalizacion.xml` afecta a las cuatro
  fichas a la vez.
- Mantener la invariante de `test()` (devolver `true` para tipos ajenos) para no romper la
  convivencia con AliasClientes / AliasBusCanarias sobre el mismo modelo `Alias`.
- Los tipos sólo por `AliasType::ensure()` en `update()`: el modelo rechaza cualquier otra escritura.
- Cabeceras de autoría Okodex/Alexis, GPL v3 (ver `Alias/CLAUDE.md`).

## Dudas / notas

- No hay dudas específicas: implementación limpia y homogénea con el resto de satélites.
