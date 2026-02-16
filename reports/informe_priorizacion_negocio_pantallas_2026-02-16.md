# Priorización de pantallas por impacto de negocio
Fecha: 2026-02-16
Base: `reports/matriz_pantallas_faltantes_audit_2026-02-16.csv`

## Criterio
- Peso mayor a flujos de reserva/checkout, campamentos, panel cliente/admin y operación diaria.
- Se pondera riesgo técnico, incompletitud CRUD, tamaño y estado placeholder/en desarrollo.

## Top 10 recomendado
1. `features/admin/module_placeholder_screen.dart` | prioridad=P1 | score=100 | riesgo=ALTO | CRUD=LECTURA/NA
2. `features/client/camps/camp_detail_screen.dart` | prioridad=P1 | score=100 | riesgo=ALTO | CRUD=LECTURA/NA
3. `features/info/info_screen.dart` | prioridad=P1 | score=100 | riesgo=ALTO | CRUD=PARCIAL
4. `features/modules/woocommerce/woocommerce_screen.dart` | prioridad=P1 | score=100 | riesgo=ALTO | CRUD=PARCIAL
5. `features/admin/admin_reservations_screen.dart` | prioridad=P1 | score=95 | riesgo=MEDIO | CRUD=PARCIAL
6. `features/admin/camps/camp_inscriptions_screen.dart` | prioridad=P1 | score=91 | riesgo=MEDIO | CRUD=LECTURA/NA
7. `features/client/camps/camps_screen.dart` | prioridad=P1 | score=89 | riesgo=BAJO | CRUD=LECTURA/NA
8. `features/reservations/my_reservations_screen.dart` | prioridad=P1 | score=89 | riesgo=BAJO | CRUD=PARCIAL
9. `features/admin/dashboard_screen.dart` | prioridad=P1 | score=88 | riesgo=MEDIO | CRUD=PARCIAL
10. `features/admin/qr_scanner_screen.dart` | prioridad=P1 | score=88 | riesgo=MEDIO | CRUD=PARCIAL

## Resumen de backlog
- P1: 16
- P2: 17
- P3: 6
- P4: 43

## Archivo matriz
- `reports/matriz_priorizada_negocio_pantallas_2026-02-16.csv`
