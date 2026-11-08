# AliasLocalizaciones

<p align="center">
  <a href="https://github.com/Asermar/AliasLocalizaciones/tags"><img alt="Versión" src="https://img.shields.io/github/v/tag/Asermar/AliasLocalizaciones?style=for-the-badge&label=Versi%C3%B3n&color=2E7D6E"></a>
  <img alt="FacturaScripts" src="https://img.shields.io/badge/FacturaScripts-core_2025%2B-2670c9?style=for-the-badge">
  <img alt="Requiere" src="https://img.shields.io/badge/Requiere-Alias-6C4FD8?style=for-the-badge">
  <a href="LICENSE"><img alt="Licencia" src="https://img.shields.io/github/license/Asermar/AliasLocalizaciones?style=for-the-badge&color=A42E2B"></a>
</p>

Satélite del plugin [Alias](https://github.com/Asermar/Alias) para las **localizaciones**: da nombres
alternativos a **países, provincias, ciudades y puntos de interés**, que es donde más falta hacen —el
mismo sitio se nombra de varias formas según quién lo diga, y hay que reconocerlas todas.

## Funcionalidades

- **Registra cuatro tipos de alias** en el catálogo: `country` (países), `province` (provincias),
  `city` (ciudades) y `poi` (puntos de interés).
- **Pestaña de alias en las cuatro fichas**, con una **única vista compartida** en lugar de cuatro
  copias: lo que se arregla en una vale para todas. Cada pestaña **sólo alcanza los alias de la ficha
  abierta**: lo acota la guarda de `Alias`.
- **Valida que el alias apunte a un registro que existe** y **se niega a borrar** una localización
  que tenga alias, o que tenga algo colgando debajo, diciendo qué lo impide: así no quedan alias
  huérfanos reservando denominaciones, ni desaparece por cascada lo que hubiera debajo.
- **Al cambiar el código** de un país, una provincia o una ciudad, sus alias pasan al código nuevo.
- **Extiende la tabla de puntos de interés** con los campos que necesita la importación de datos
  externos (`bs_parada`, `bs_legacy_idpoi`), de modo que un punto de interés pueda conservar la
  referencia de su origen.

## Requisitos

- [`Alias`](https://github.com/Asermar/Alias) **1.03** o superior (la guarda de la pestaña de alias).
- FacturaScripts con core **2025** o superior, y PHP **8** o superior.

## Changelog

Cambios destacados por versión (la versión es la de `facturascripts.ini`, único punto de verdad).

### 1.0x — Alias de países, provincias, ciudades y PDI

- **1.03** — **La pestaña de alias de una ficha deja de alcanzar los alias de otras fichas.**
  Mostraba sólo los de la localización abierta, pero el formulario manda el tipo y el código y las
  acciones del núcleo cargan por código: con permiso sobre países, provincias, ciudades o puntos de
  interés se podía crear, editar o borrar **cualquier** alias del sistema —incluidos los de clientes
  o los que sirven de puente con sistemas externos—. Ahora las cuatro pestañas pasan por la guarda
  `AliasTabGuard` de `Alias`, que anula con un aviso lo que venga de fuera de la ficha, sin escribir
  nada.

  > **Al actualizar**: esta versión necesita **`Alias` 1.03 o superior**, que es donde vive la guarda.
  > Instala `Alias` **antes o a la vez**. No hay que desactivar nada previamente, y esto está medido
  > en la instalación de pruebas, no supuesto: con `Alias` 1.02 y un satélite al día, la ficha de esa
  > entidad no abre —«Class FacturaScripts\Dinamic\Lib\AliasTabGuard not found»— pero el **gestor de
  > plugins sigue funcionando**, así que se arregla instalando `Alias` desde la propia interfaz, sin
  > tocar `plugins.json`. El `require_min` del `.ini` lo deja escrito, pero el núcleo **no lo lee**:
  > es documentación, no una puerta.
- **1.02** — **Borrar una localización deja de llevarse por delante lo que cuelga de ella.** Un país,
  una provincia, una ciudad o un punto de interés con alias, o con algo debajo, ya no se puede borrar:
  la operación se niega y el aviso dice qué lo impide y en qué orden hay que deshacerlo. Antes la base
  de datos borraba en cascada lo que colgaba debajo —provincias, ciudades, puntos de interés— y los
  alias de todo ello quedaban huérfanos, ocupando denominaciones que ya no se podían reutilizar. Y al
  **cambiar el código** de un país, una provincia o una ciudad, sus alias pasan al código nuevo en vez
  de quedarse apuntando a uno que ya no existe.
- **1.01** — Se añaden `bs_parada` y `bs_legacy_idpoi` a la tabla de puntos de interés, para poder
  conservar la referencia de origen de un PDI importado. Y el campo de alias admite **100 caracteres**,
  acompañando la ampliación del plugin `Alias`: si el widget se quedara en 50, el formulario recortaría
  en silencio un alias que la base de datos sí acepta.
- **1.00** — Primera versión estable: licencia **GPL v3** y compatibilidad declarada de forma explícita
  (core **2025** y PHP **8**).

> Las versiones `0.x` están en el historial de git; no se reconstruyen aquí para no inventar notas que
> nunca se escribieron.

## Licencia

GNU General Public License v3 — ver [LICENSE](LICENSE).

Copyright (C) 2026 Oko Digital Experts, S.L.L. (Okodex)
@author Alexis Serafín <alexis@okodex.com>
