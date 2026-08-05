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
  copias: lo que se arregla en una vale para todas.
- **Valida que el alias apunte a un registro que existe** y **borra los alias en cascada** al eliminar la
  localización, para que no queden huérfanos reservando denominaciones.
- **Extiende la tabla de puntos de interés** con los campos que necesita la importación de datos
  externos (`bs_parada`, `bs_legacy_idpoi`), de modo que un punto de interés pueda conservar la
  referencia de su origen.

## Requisitos

- [`Alias`](https://github.com/Asermar/Alias)
- FacturaScripts con core **2025** o superior, y PHP **8** o superior.

## Changelog

Cambios destacados por versión (la versión es la de `facturascripts.ini`, único punto de verdad).

### 1.0x — Alias de países, provincias, ciudades y PDI

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
