# Contrato HTTP: Vaciado — agregar varias fotos

RBAC `vaciado.*` + middleware `modulo:vaciado`.

## (Existente) `POST /vaciado` — crear vaciado
- Ya acepta **varias fotos** al crear: `fotos` array, `fotos.*` image (StoreOrdenVaciadoRequest). Sin cambios.

## (Nuevo) `POST /vaciado/{ordenVaciado}/fotos` — agregar fotos a un vaciado existente
- **Propósito**: Sumar una o más fotografías a un vaciado ya creado/en proceso (FR-013).
- **Permiso**: `vaciado.programar` (o el permiso operativo del vaciado vigente).
- **Request** (`AgregarFotosVaciadoRequest`):

| Campo | Regla |
|---|---|
| `fotos` | required, array, min:1 |
| `fotos.*` | image, mimes:jpg,png,webp, max:5120 |

- **Comportamiento** (`VaciadoService::agregarFotos`): `ordenVaciado->guardarFotos($fotos, "vaciado/{id}/fotos")` — agrega sin reemplazar las existentes.
- **Respuesta**: redirect a `vaciado.show` con mensaje de éxito; las fotos nuevas aparecen junto a las previas.
- **Errores**: 422 (sin fotos o formato inválido); 403; 404 (módulo oculto o vaciado inexistente).

## (Existente) `GET /vaciado/{ordenVaciado}` — detalle
- Muestra **todas** las fotos del vaciado (creación + agregadas) y las de sus novedades. La trazabilidad las incluye (FR-014).
