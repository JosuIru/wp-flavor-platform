# Informe: dependencias de tickets por estado (10003)
Fecha: 2026-02-16
Entorno: `http://localhost:10003`
Alcance: solo auditoría/reporte (sin correcciones)

## Evidencia generada
- Matriz CSV: `reports/matriz_dependencias_tickets_por_estado_10003_2026-02-16.csv`

## Resumen ejecutivo
- Estados analizados: **5** (`abierto`, `festivo-local`, `julio-medio-dia`, `olentzero`, `semana-santa`)
- Casos ejecutados: **20/20**
- `add_to_cart_success=true`: **20/20**
- Casos de ticket hijo (`depends_on`) sin ticket padre: **15/15** también exitosos en add-to-cart

## Hallazgo principal
El sistema **no está forzando** las dependencias de tickets vinculados en el flujo de reserva actual:
- Los tickets hijo con `depends_on` se pueden reservar sin incluir el ticket padre.
- Esto explica la percepción de inconsistencias al trabajar con tipos vinculados y estados.

## Evidencia de arquitectura (código)
- El API devuelve `depends_on` en tickets por estado:
  - `includes/api/class-mobile-api.php:2049`
  - `includes/api/class-mobile-api.php:2077`
- El backend no valida dependencias en reserva:
  - `includes/api/class-mobile-api.php:2303` (`prepare_reservation`)
  - `includes/api/class-mobile-api.php:2353` (`add_to_cart`)
- La app cliente no aplica bloqueo/validación de dependencias en la UI:
  - `mobile-apps/lib/core/widgets/ticket_widgets.dart:25`
  - `mobile-apps/lib/features/reservations/reservations_screen.dart:67`
- Existe lógica de dependencia en modelo, pero no se usa en la pantalla de compra:
  - `mobile-apps/lib/core/models/models.dart:295`
  - `mobile-apps/lib/core/models/models.dart:306`

## Conclusión operativa
- Con la configuración y datos actuales en `10003`, reservar por estado funciona.
- Las dependencias (`depends_on`) se informan, pero no se hacen cumplir en cliente ni servidor durante el checkout.
