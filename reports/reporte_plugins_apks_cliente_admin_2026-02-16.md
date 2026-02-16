# Reporte de revisión: plugins + APK cliente/admin
Fecha: 2026-02-16
Base URL: `http://localhost:10028`
Alcance: solo auditoría y reporte (sin correcciones)

## 1) Estado de plugins detectados por la app
Fuente: `GET /wp-json/app-discovery/v1/info`

- `calendario-experiencias`: ACTIVO (`version: 3.0.0`, namespace `chat-ia-mobile/v1`)
- `basabere-campamentos`: ACTIVO (`version: 1.0.0`, namespace `camps/v1`)
- `flavor-chat-ia`: ACTIVO (`version: 3.1.1`)

Conclusión: la detección de plugins requerida por las apps está operativa en discovery.

## 2) Inventario de APKs cliente/admin

### Build actual (dist)
- `mobile-apps/dist/FlavorChat-Admin-v3.1.1.apk` (21 MB, fecha FS: 2026-02-12 13:02)
- `mobile-apps/dist/FlavorChat-Cliente-v3.1.1.apk` (21 MB, fecha FS: 2026-02-12 12:44)

Checksums SHA-256:
- Admin v3.1.1: `87fdb3e016d55c2e54904ee278cd703598a047823c566507e8e1c023ef907222`
- Cliente v3.1.1: `dfa2869212b9ee355263638799cc18cad8cac15d230a706b6a40e916b7a1189c`

Validez de artefacto:
- Ambos archivos fueron reconocidos como APK válidos (`Android package (APK)`).

### Artefactos adicionales
- Split APKs por ABI en `mobile-apps/dist/apks/` (admin/client para arm64-v8a, armeabi-v7a, x86_64).
- Releases anteriores en `mobile-apps/release/` (`basabere-admin/client-v1.0.0.apk/.aab`).

## 3) Compatibilidad funcional móvil con Basabere + Calendario
Fuente principal: `reports/compat_mobile_basabere_calendario_2026-02-16.md`
Resultado global: **17/19 PASS**

### PASS relevantes
- Discovery, modules y theme responden 200.
- Confirmada presencia de `basabere-campamentos` y `calendario-experiencias` en sistemas activos.
- Endpoints públicos móviles de disponibilidad, experiencias y tickets responden 200.
- Flujo de reserva móvil responde en `check`, `prepare`, `add-to-cart` y `mobile-checkout-url`.
- Endpoint admin de reservas responde con token válido.

### FAIL / bloqueo de datos
- `CAMPS_DETAIL_200`: no se pudo validar detalle de campamento porque la lista pública de campamentos vino vacía (`total: 0`).
- `AVAILABILITY_HAS_DAY_STATES`: no se pudieron validar estados de calendario porque `availability` retornó arreglo vacío.

## 4) Riesgos y lectura operativa
- Integración API entre apps y plugins: funcional en lo crítico (detección y flujo de reserva).
- Cobertura incompleta en casos dependientes de contenido real (campamentos/días con estado).
- Con el dataset actual no es posible certificar al 100%:
  - consulta de detalle de campamento con ID real,
  - representación de estados de disponibilidad con días cargados.

## 5) Veredicto de auditoría (sin corregir)
- **Plugins detectados**: Sí.
- **APKs cliente/admin presentes y válidos**: Sí.
- **Reserva móvil end-to-end (API)**: Sí, operativa.
- **Validación completa de campamentos + estados calendario con datos reales**: No, pendiente de carga de datos.
