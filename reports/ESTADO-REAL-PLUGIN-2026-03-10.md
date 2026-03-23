# Estado real del plugin (auditoría técnica)

Fecha: 2026-03-10  
Ámbito: plugin, módulos, dashboards, shortcodes, mobile/APK

## Resumen ejecutivo
- Módulos detectados (directorios): **63**
- Clases de módulo detectadas: **62**
- Vistas dashboard por módulo: **41**
- Vistas totales de módulos: **245**
- Registros add_shortcode() en PHP: **813**
- Tags shortcode únicos (literales): **738**

## Semáforo de módulos (criterio estructural)
Criterio:
- **VERDE**: tiene clase módulo + dashboard + frontend
- **AMARILLO**: tiene clase módulo y solo una de (dashboard/frontend)
- **ROJO**: no tiene clase de módulo o no tiene ni dashboard ni frontend

Totales:
- VERDE: **34**
- AMARILLO: **15**
- ROJO: **14**
- Total: **63**

| Módulo | Clase | Dashboard | Frontend | Views | Estado |
|---|---:|---:|---:|---:|---|
| advertising | 1 | 0 | 0 | 0 | ROJO |
| assets | 0 | 0 | 0 | 0 | ROJO |
| avisos-municipales | 1 | 1 | 1 | 2 | VERDE |
| ayuda-vecinal | 1 | 1 | 1 | 5 | VERDE |
| banco-tiempo | 1 | 1 | 2 | 4 | VERDE |
| bares | 1 | 0 | 0 | 0 | ROJO |
| biblioteca | 1 | 1 | 1 | 5 | VERDE |
| bicicletas-compartidas | 1 | 1 | 1 | 6 | VERDE |
| biodiversidad-local | 1 | 0 | 1 | 0 | AMARILLO |
| campanias | 1 | 1 | 1 | 8 | VERDE |
| carpooling | 1 | 1 | 1 | 8 | VERDE |
| chat-estados | 1 | 0 | 3 | 1 | AMARILLO |
| chat-grupos | 1 | 0 | 0 | 3 | ROJO |
| chat-interno | 1 | 0 | 0 | 0 | ROJO |
| circulos-cuidados | 1 | 0 | 1 | 0 | AMARILLO |
| clientes | 1 | 1 | 0 | 1 | AMARILLO |
| colectivos | 1 | 1 | 1 | 9 | VERDE |
| compostaje | 1 | 1 | 1 | 5 | VERDE |
| comunidades | 1 | 1 | 1 | 19 | VERDE |
| crowdfunding | 1 | 1 | 0 | 1 | AMARILLO |
| cursos | 1 | 1 | 1 | 5 | VERDE |
| dex-solana | 1 | 0 | 0 | 0 | ROJO |
| documentacion-legal | 1 | 1 | 1 | 7 | VERDE |
| economia-don | 1 | 1 | 1 | 1 | VERDE |
| economia-suficiencia | 1 | 0 | 0 | 0 | ROJO |
| email-marketing | 1 | 1 | 0 | 8 | AMARILLO |
| empresarial | 1 | 0 | 0 | 3 | ROJO |
| encuestas | 1 | 0 | 0 | 0 | ROJO |
| energia-comunitaria | 1 | 1 | 0 | 6 | AMARILLO |
| espacios-comunes | 1 | 1 | 1 | 5 | VERDE |
| eventos | 1 | 1 | 1 | 5 | VERDE |
| facturas | 1 | 0 | 0 | 0 | ROJO |
| fichaje-empleados | 1 | 0 | 1 | 3 | AMARILLO |
| foros | 1 | 1 | 1 | 4 | VERDE |
| grupos-consumo | 1 | 1 | 2 | 12 | VERDE |
| huella-ecologica | 1 | 0 | 0 | 0 | ROJO |
| huertos-urbanos | 1 | 1 | 1 | 5 | VERDE |
| incidencias | 1 | 1 | 1 | 4 | VERDE |
| justicia-restaurativa | 1 | 0 | 1 | 0 | AMARILLO |
| kulturaka | 1 | 1 | 0 | 5 | AMARILLO |
| mapa-actores | 1 | 1 | 1 | 7 | VERDE |
| marketplace | 1 | 1 | 1 | 6 | VERDE |
| multimedia | 1 | 1 | 1 | 7 | VERDE |
| parkings | 1 | 1 | 1 | 5 | VERDE |
| participacion | 1 | 1 | 1 | 6 | VERDE |
| podcast | 1 | 1 | 1 | 5 | VERDE |
| presupuestos-participativos | 1 | 1 | 1 | 9 | VERDE |
| radio | 1 | 1 | 1 | 7 | VERDE |
| recetas | 1 | 0 | 1 | 0 | AMARILLO |
| reciclaje | 1 | 1 | 1 | 5 | VERDE |
| red-social | 1 | 1 | 0 | 5 | AMARILLO |
| reservas | 1 | 1 | 1 | 5 | VERDE |
| saberes-ancestrales | 1 | 0 | 1 | 0 | AMARILLO |
| seguimiento-denuncias | 1 | 1 | 1 | 7 | VERDE |
| sello-conciencia | 1 | 0 | 0 | 0 | ROJO |
| socios | 1 | 1 | 1 | 8 | VERDE |
| talleres | 1 | 1 | 1 | 4 | VERDE |
| themacle | 1 | 0 | 0 | 0 | ROJO |
| trabajo-digno | 1 | 0 | 1 | 0 | AMARILLO |
| trading-ia | 1 | 0 | 0 | 0 | ROJO |
| tramites | 1 | 1 | 1 | 5 | VERDE |
| transparencia | 1 | 1 | 1 | 1 | VERDE |
| woocommerce | 1 | 1 | 0 | 3 | AMARILLO |

## Shortcodes: riesgos de colisión
Top tags duplicados detectados (>1 registro):


txt
      4 flavor_
      2 socios_mis_cuotas
      2 socios_mi_perfil
      2 reservas_recursos
      2 reservas_mis_reservas
      2 reservas_formulario
      2 reservas_calendario
      2 marketplace_formulario
      2 incidencias_reportar
      2 incidencias_mapa
      2 incidencias_listado
      2 incidencias_estadisticas
      2 incidencias_detalle
      2 gc_suscripciones
      2 gc_mi_cesta
      2 gc_historial
      2 gc_grupos_lista
      2 gc_catalogo
      2 gc_carrito
      2 gc_calendario
      2 flavor_saberes_catalogo
      2 flavor_radio_programacion
      2 flavor_radio_podcasts
      2 flavor_radio_player
      2 flavor_radio_dedicatorias

Observación: hay colisiones reales (mismo tag definido varias veces) que pueden sobrescribir handlers según orden de carga.

## Mobile / APK
Entrypoints Flutter detectados en `mobile-apps/lib`:
- `main_admin.dart`
- `main_client.dart`
- `main_selector.dart`

Artefactos APK/AAB detectados:
- mobile-apps/build/app/outputs/apk/client/debug/app-client-debug.apk
- mobile-apps/build/app/outputs/flutter-apk/app-client-debug.apk

Nota: los artefactos detectados en esta auditoría son mayoritariamente de debug, no evidencia de release firmado listo para distribución.

## Riesgos bloqueantes actuales (confirmados)
- 404 de assets CSS legacy en dashboards (parcialmente mitigado con bridges, pendiente unificación completa de encolado).
- Duplicidad/colisión de shortcodes en varios módulos.
- Cobertura desigual de frontend/dashboard entre módulos.
- Árbol de trabajo con alto volumen de cambios sin consolidar (riesgo de regresión en despliegue).

## Recomendación de cierre (orden)
1. Cerrar incidentes de carga dashboard (assets + JS/AJAX) y validar smoke admin.
2. Resolver colisiones de shortcodes (namespacing + deprecación controlada).
3. Estabilizar módulos en ROJO/AMARILLO por prioridad funcional.
4. Congelar release branch, smoke E2E y empaquetado mobile release.
