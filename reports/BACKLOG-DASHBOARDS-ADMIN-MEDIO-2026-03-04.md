# Backlog Dashboards Admin Medio 2026-03-04

## Criterio

Listado de dashboards con madurez estructural media en la auditoría actual. Se priorizan primero los que ya tienen slug canónico pero fallan en renderer principal, y después los que sólo tienen infraestructura parcial.

## Prioridad inmediata

1. `grupos_consumo`
- slug: `gc-dashboard`
- gap: no se detecta `render_admin_dashboard()` en el módulo principal
- lectura: es la referencia funcional visible, pero su contrato estructural no está tan limpio como otros

2. `reservas`
- slug: `reservas-dashboard`
- gap: tiene vista/tab/widget, pero no renderer admin principal detectado
- lectura: candidato claro a validación runtime y normalización

3. `radio`
- slug: `radio-dashboard`
- gap: el renderer real parece apoyarse en slug `flavor-radio-dashboard`, mientras el mapping canónico quedó en `radio-dashboard`
- lectura: posible desalineación de slug real vs mapping histórico

4. `espacios_comunes`
- slug: `espacios-dashboard`
- gap: tiene vista/tab/widget, pero sin `get_admin_config()` ni `render_admin_dashboard()` detectables en el módulo principal
- lectura: buena candidata a cierre estructural rápido

## Infraestructura parcial sin slug canónico propio en auditoría

5. `biodiversidad_local`
- tiene `render_admin_dashboard()` y dashboard tab
- falta canonización o validación de entrada estable

6. `circulos_cuidados`
- tiene `render_admin_dashboard()` y dashboard tab
- falta canonización o validación de entrada estable

7. `energia_comunitaria`
- tiene tab/widget, pero no dashboard admin principal claro en el módulo
- probablemente depende de otra arquitectura de comunidad/energía

8. `huella_ecologica`
- tiene `render_admin_dashboard()` y dashboard tab
- falta verificar cómo se expone en menú y shell

9. `justicia_restaurativa`
- patrón similar a `huella_ecologica`

10. `sello_conciencia`
- renderer admin detectado, pero no dashboard canónico estable en esta tanda

11. `trabajo_digno`
- renderer admin detectado, pero no dashboard canónico estable en esta tanda

12. `chat_estados`
- sólo señales parciales de tab/widget
- baja prioridad frente a dashboards con slug real

13. `recetas`
- estructura de dashboard mínima o ausente
- priorizar sólo si entra en alcance de release admin

## Orden recomendado de runtime cuando vuelva localhost

1. `flavor-module-dashboards`
2. `gc-dashboard`
3. `reservas-dashboard`
4. `radio-dashboard` y `flavor-radio-dashboard`
5. `espacios-dashboard`
6. `biodiversidad_local`, `circulos_cuidados`, `huella_ecologica`, `justicia_restaurativa`

## Regla

No seguir ampliando mappings si el siguiente cuello de botella ya es runtime. La siguiente tanda debe ser de navegación real con sesión admin.
