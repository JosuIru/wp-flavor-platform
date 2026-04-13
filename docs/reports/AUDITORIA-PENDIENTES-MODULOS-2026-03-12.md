# Auditoría de pantallas pendientes (2026-03-12)

Objetivo: identificar pantallas/módulos con señales reales de desarrollo incompleto (placeholders funcionales, fallbacks, vistas no disponibles o features desactivadas).

## P0 (bloqueantes funcionales / UX rota)

1. Facturas: pago online sin implementar
- Archivo: `includes/modules/facturas/class-facturas-module.php`
- Evidencia: `shortcode_pagar_factura()` devuelve texto "Sistema de pago online proximamente disponible".
- Impacto: flujo de cobro incompleto.

2. Sello Conciencia: envío de solicitud bloqueado
- Archivo: `includes/modules/sello-conciencia/class-sello-conciencia-dashboard-tab.php`
- Evidencia: botón deshabilitado con texto "Envío pendiente de integración".
- Impacto: no se pueden crear solicitudes reales desde dashboard.

3. Transparencia: templates con placeholder funcional
- Archivos:
  - `includes/modules/transparencia/templates/actas.php`
  - `includes/modules/transparencia/templates/presupuestos.php`
  - `includes/modules/transparencia/templates/presupuesto-actual.php`
  - `includes/modules/transparencia/templates/ultimos-gastos.php`
  - `includes/modules/transparencia/templates/contratos.php`
- Evidencia: muestran mensajes de "sistema no disponible".
- Impacto: tabs/páginas aparentan existir pero no tienen contenido real.

4. Campañas: vista no disponible
- Archivo: `includes/modules/campanias/class-campanias-module.php`
- Evidencia: retorno "Esta vista de campañas todavía no está disponible".
- Impacto: navegación rota en módulo.

## P1 (parcialmente implementado con fallback)

5. Email Marketing: render fallback en admin
- Archivo: `includes/modules/email-marketing/class-email-marketing-module.php`
- Evidencia: `render_vista_fallback(...)` para dashboard/campañas/automatizaciones/suscriptores/listas/plantillas/estadísticas/configuración.
- Impacto: experiencia inconsistente y potencial duplicidad de layouts.

6. Red Social: dashboards admin en fallback
- Archivo: `includes/modules/red-social/class-red-social-module.php`
- Evidencia: `render_admin_dashboard_fallback`, `render_admin_publicaciones_fallback`, `render_admin_moderacion_fallback`.
- Impacto: panel admin operativo pero no fully templated.

7. Banco de Tiempo: vistas no disponibles
- Archivos:
  - `includes/modules/banco-tiempo/class-banco-tiempo-module.php`
  - `includes/modules/banco-tiempo/frontend/class-banco-tiempo-frontend-controller.php`
- Evidencia: mensajes "La vista de reputación no está disponible" y "El historial de intercambios aún no está disponible".
- Impacto: funcionalidad clave incompleta.

8. Marketplace: fallback de formulario frontend
- Archivo: `includes/modules/marketplace/frontend/class-marketplace-frontend-controller.php`
- Evidencia: `marketplace-formulario-fallback` cuando no hay vista activa.
- Impacto: creación de anuncios puede quedar degradada.

## P2 (mejoras de completitud)

9. Talleres: sección materiales frecuentes en placeholder
- Archivo: `includes/modules/talleres/views/materiales.php`
- Evidencia: comentario y variable de datos vacía para "Materiales frecuentes".
- Impacto: panel incompleto (no bloqueante).

10. Dashboards dependientes de tablas no creadas
- Archivos:
  - `includes/modules/clientes/views/dashboard.php`
  - `includes/modules/reservas/views/dashboard.php`
  - `includes/modules/socios/views/dashboard.php`
  - `includes/modules/eventos/views/dashboard.php`
- Evidencia: avisos de "tabla principal no está disponible".
- Impacto: instalación no completa o migración pendiente.

## Estado de matriz de cobertura (referencia)

Fuente: `reports/modulos_matriz_actual_2026-03-01.csv` (puede estar parcialmente desactualizada respecto a fixes recientes).

Top módulos con mayor gap en la matriz:
- `bares`, `chat-grupos`, `chat-interno`, `clientes`, `facturas`, `themacle` (4 gaps de 5 ejes)
- `advertising`, `chat-estados`, `dex-solana`, `economia-suficiencia`, `encuestas`, `huella-ecologica`, `sello-conciencia`, `trading-ia`, `woocommerce` (3 gaps)

## Orden de cierre recomendado

1. P0 completo (bloqueantes de negocio).
2. P1 completo (eliminar fallbacks admin/frontend).
3. P2 (acabado funcional + UX).
4. Smoke tests por módulo tras cada tanda.

## Nota operativa

Existe pantalla de seguimiento de gaps en admin:
- slug: `flavor-module-gaps`
- clase: `includes/admin/class-module-gap-admin.php`
