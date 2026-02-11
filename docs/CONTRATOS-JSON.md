# Contratos JSON y Versionado

Fecha: 2026-02-08

## Objetivo
- Definir los contratos JSON consumidos por apps y clientes externos.
- Establecer reglas de versionado y compatibilidad.

## Versionado

Regla general
- El versionado principal va en el namespace REST: `.../v1`.
- Cambios incompatibles requieren nueva version de namespace (`v2`).

Mobile API (Flutter)
- Namespace: `chat-ia-mobile/v1`.
- Header de version de contrato: `X-Flavor-API-Version`.
- Campo `api_version` en `GET /site-info`.

App Discovery / Unified API
- Namespaces: `app-discovery/v1` y `unified-api/v1`.
- Cambios incompatibles deben publicar `v2`.

## Fuentes de contrato

OpenAPI
- `docs/api/openapi.yaml` contiene schemas y endpoints documentados para varios modulos.

Esquemas internos
- `includes/api/class-api-documentation.php` define schemas y documenta endpoints propios.

Contrato Apps
- Mobile API: respuestas JSON documentadas por endpoint en `includes/api/class-mobile-api.php`.
- App integration: respuestas de discovery en `includes/app-integration/class-app-integration.php`.

## Compatibilidad

- Se permite agregar campos nuevos opcionales en respuestas.
- No se deben eliminar ni renombrar campos sin aumentar la version.
- Los clientes deben tolerar campos desconocidos.

## Siguientes pasos

- Documentar schemas completos de Mobile API en OpenAPI.
- Publicar `v2` si se cambian nombres de campos o estructuras base.
