# Auditoría de Dashboards Admin 2026-03-04

## Alcance

Revisión estática del árbol actual para inventariar dashboards administrativos de módulos y estimar su madurez estructural.

Fuentes usadas:
- `includes/modules/class-module-loader.php`
- `includes/admin/trait-module-admin-pages.php`
- archivos principales de cada módulo en `includes/modules/*/class-*-module.php`
- presencia de `views/dashboard.php`, `*dashboard-tab.php` y `*dashboard-widget.php`

## Lectura ejecutiva

- Módulos registrados en el loader: `60`
- Módulos con `get_admin_config()`: `60`
- Módulos que llaman `registrar_en_panel_unificado()`: `60`
- Módulos con `render_admin_dashboard()`: `59`
- Módulos con `views/dashboard.php`: `35`
- Dashboards canónicos mapeados en `trait-module-admin-pages.php`: `52`
- Dashboards de módulos sin mapping canónico todavía: `0` dentro del conjunto detectable por slug

Importante:
- esta lectura mide sobre todo `madurez estructural`
- no implica paridad funcional con `gc-dashboard`
- la comparación funcional real queda separada en `RECLASIFICACION-FUNCIONAL-DASHBOARDS-ADMIN-2026-03-04.md`

## Hallazgo principal

La brecha principal ya no es de descubrimiento sino de validación y coherencia runtime.

Lectura real del árbol:
- hay muchos dashboards admin implementados o semiimplementados
- el mapping común ya cubre los slugs de dashboard detectables en el árbol actual
- `FlavorShell` ya dispone de una puerta de entrada compacta a ese conjunto

En la práctica, el problema visible deja de ser “no encuentro el dashboard” y pasa a ser “qué dashboards están realmente listos y validados”.

## Estado estimado

Según heurística estructural usada en esta auditoría:
- Alta madurez: `60`
- Madurez media: `0`
- Baja: `0`

Interpretación:
- `alta` significa que existe slug de dashboard detectable y además contrato admin + renderer específico
- `media` significa que existe dashboard o infraestructura asociada, pero todavía faltan validación runtime o cierres secundarios
- esta clasificación es estructural, no prueba runtime por sí sola
- tampoco mide profundidad UX ni riqueza funcional comparada entre dashboards

## Riesgos detectados

1. El acceso ya quedó centralizado, pero la validación runtime dashboard por dashboard sigue pendiente.
2. La accesibilidad por rol depende todavía de capacidades heterogéneas por módulo.
3. La clasificación de madurez es estructural; no sustituye pruebas navegando con sesión admin y gestor.
4. La deuda residual ya no está en el wiring base, sino en validación runtime autenticada y coherencia funcional de cada dashboard.

## Acción aplicada en esta tanda

Se ha añadido una entrada única `Dashboards` al shell/admin para no saturar la barra lateral y, al mismo tiempo, hacer accesibles los dashboards administrativos desde un índice filtrado por vista activa. Además, se ha ampliado el mapping canónico para cubrir los dashboards detectables por slug en el árbol actual y se han corregido estas desalineaciones estructurales:

- `radio`: el slug canónico pasa a `flavor-radio-dashboard`
- `reservas`: el dashboard principal queda declarado explícitamente como `reservas-dashboard`
- `espacios_comunes`: el módulo declara ya `get_admin_config()` y callbacks admin explícitos para dashboard, espacios, reservas, calendario y normas
- `chat_estados`: el módulo declara ya `get_admin_config()`, slug canónico `chat-estados-dashboard` y renderer admin específico

Validación runtime parcial ya hecha:
- las rutas admin revisadas responden y redirigen al login estándar con `redirect_to` correcto
- sigue pendiente validar contenido interno autenticado con sesión admin

Archivos tocados:
- `admin/class-module-dashboards-page.php`
- `admin/class-admin-menu-manager.php`
- `admin/class-admin-shell.php`
- `flavor-chat-ia.php`
- `includes/admin/trait-module-admin-pages.php`
- `includes/modules/reservas/class-reservas-module.php`
- `includes/modules/espacios-comunes/class-espacios-comunes-module.php`

## Criterio operativo recomendado

Para seguir cerrando admin dashboards con criterio de producción:
- usar `grupos_consumo` como referencia de dashboard canónico
- priorizar módulos con estado `medio` en esta auditoría
- validar runtime de los dashboards más usados antes de ampliar navegación
- mantener `FlavorShell` compacto: un índice central es mejor que 30 enlaces laterales

## Evidencia detallada

La matriz completa queda en:
- `reports/AUDITORIA-DASHBOARDS-ADMIN-2026-03-04.csv`
