# E2E Completo (Web + API + Flutter)
Fecha: 16 de febrero de 2026
Entorno objetivo: `http://localhost:10028/`
Credenciales usadas: `@gailu / temporal`

## Resultado general
- Web/API E2E: **13/14 PASS**
- Flutter tests: **FAIL** (errores de compilación y tests desalineados)

## Evidencias generadas
- `reports/e2e_web_api_2026-02-16.md`
- `reports/e2e_web_api_2026-02-16.csv`
- `reports/e2e_flutter_test_2026-02-16.log`

## Web/API (E2E real)
### Casos PASS
- Login web real en `wp-login.php` y acceso a `wp-admin`.
- Endpoints públicos API móviles (`/site-info`) responden correctamente.
- Endpoints admin sin token devuelven `401`.
- Login API con `app_type=admin` devuelve token válido.
- `auth/verify`, `admin/dashboard`, `auth/refresh` y verify post-refresh correctos.
- Token inválido en endpoint admin devuelve `401`.

### Caso FAIL
- `WEB_HOME_NO_PHP_NOTICE`: la home devuelve notices PHP en output.
  - `_load_textdomain_just_in_time` para dominios `flavor-chat-ia` y `woocommerce` cargados demasiado temprano.

## Flutter (apps)
### Estado
- No existe carpeta `integration_test/` en `mobile-apps` (no hay suite de integración de UI E2E móvil declarada).
- Ejecución realizada: `/home/josu/flutter/bin/flutter test`
- Resultado: falla antes de completar por errores de compilación y tests incompatibles con el código actual.

### Fallos principales detectados
1. Duplicación de método en API client:
   - `lib/core/api/api_client.dart:3190` redeclara `getDashboardCharts` ya declarado en `lib/core/api/api_client.dart:414`.
2. Incompatibilidad de parámetros con `flutter_map_marker_cluster`:
   - `lib/features/client/widgets/dashboard_map_widget.dart:375` usa `fitBoundsOptions` no soportado por versión instalada.
3. Tests desalineados con API actual de `TokenManager`:
   - `test/core/security/token_manager_test.dart` llama miembros inexistentes (`extractClaims`, `isTokenExpired`, `needsRefresh`, `refreshThreshold`).
4. Tests desalineados con API actual de `HttpSecurity`:
   - `test/core/security/http_security_test.dart` usa métodos/constantes no existentes (`isSecureUrl`, `isDebugUrl`, `getSecurityHeaders`, `SecurityHeaders`).
5. Tests desalineados con firmas actuales de `Logger`:
   - `test/core/utils/logger_test.dart` usa firmas antiguas (número/tipo de argumentos incorrectos).
6. Tests con binding Flutter no inicializado:
   - `test/core/providers/biometric_provider_test.dart` falla por falta de `TestWidgetsFlutterBinding.ensureInitialized()`.

## Hallazgos runtime adicionales
- Durante login web se observó error de base de datos en respuesta intermedia (tabla faltante):
  - `wp_flavor_chat_estados_usuario` inexistente (aparece en la respuesta HTML del login 302).
  - Esto no bloqueó el acceso final al dashboard, pero indica migración incompleta de DB para ciertos flujos.

## Conclusión
- La parte Web/API del plugin está operativa para autenticación y endpoints clave del namespace móvil.
- El estado Flutter actual no es release-ready: hay deuda de compilación y tests rotos que impiden validar E2E móvil real.
