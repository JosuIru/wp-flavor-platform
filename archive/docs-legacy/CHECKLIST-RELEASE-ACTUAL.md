# Checklist Release Actual

Checklist operativo para validar `flavor-chat-ia` antes de publicar una release del plugin WordPress.

Este documento refleja el estado actual del repositorio, no el historial de `archive/`.

## 1. Higiene del paquete

- [ ] Generar el paquete con `bash scripts/release.sh --version=X.Y.Z` o replicando el flujo con `.distignore`.
- [ ] Verificar que el paquete final solo contiene `admin/`, `assets/`, `includes/`, `templates/`, `languages/`, `flavor-chat-ia.php`, `uninstall.php` y `README.md`.
- [ ] Confirmar que el paquete no incluye `mobile-apps/`, `addons/`, `mcp-server/`, `docs/`, `tests/`, `reports/`, `archive/`, `node_modules/`, `vendor/`, `scripts/` ni `dev-scripts/`.

## 2. Validacion tecnica minima

- [ ] Ejecutar `npm test` en el arbol candidato a release.
- [ ] Ejecutar `php -l flavor-chat-ia.php`.
- [ ] Ejecutar `php -l includes/visual-builder-pro/class-vbp-rest-api.php`.
- [ ] Ejecutar `php -l includes/visual-builder-pro/views/editor-fullscreen.php`.
- [ ] Ejecutar `php -l admin/class-apk-builder.php`.
- [ ] Ejecutar `php -l admin/class-keystore-manager.php`.

## 3. Seguridad y automatizacion

- [ ] Confirmar que la automatizacion externa por `X-VBP-Key` esta activada solo si ese entorno la necesita.
- [ ] Revisar en Ajustes VBP que los scopes permitidos son los minimos necesarios.
- [ ] Probar un endpoint con `X-VBP-Key` y scope permitido.
- [ ] Probar el mismo tipo de endpoint con una key sin scope suficiente y confirmar rechazo.
- [ ] Confirmar que no se esta usando la key legacy como flujo normal.

## 4. Visual Builder Pro

- [ ] Abrir el editor VBP sin errores fatales de PHP o JS.
- [ ] Cargar un documento existente y verificar que el canvas renderiza.
- [ ] Editar un elemento y comprobar que el estado pasa a `dirty`.
- [ ] Guardar el documento y comprobar que el estado vuelve a `saved`.
- [ ] Verificar el autosave y el recovery chip.
- [ ] Probar preview de modulo con contenido.
- [ ] Probar preview de modulo sin contenido y verificar estado vacio.
- [ ] Probar refresco manual de preview.
- [ ] Probar preview o aplicacion de templates desde el editor.

## 5. Core y modulos

- [ ] Activar el plugin en un WordPress limpio o staging controlado.
- [ ] Abrir el admin principal del plugin y confirmar que carga.
- [ ] Verificar que el listado principal de modulos no rompe el admin.
- [ ] Comprobar al menos 3 modulos representativos del sitio real.
- [ ] Revisar que no haya warnings visibles en rutas front principales del proyecto.

## 6. APK y mobile

- [ ] Si la release incluye builder APK, comprobar que el entorno tiene binarios disponibles y que el panel no falla si no existen.
- [ ] Si la release incluye cambios Flutter, ejecutar `flutter analyze` en `mobile-apps/`.
- [ ] Confirmar que `mobile-apps/` no entra en el zip del plugin WordPress salvo release combinada deliberada.

## 7. Red, nodos y webhooks

- [ ] Verificar que las APIs o paneles de red cargan si ese dominio forma parte del despliegue.
- [ ] Confirmar que webhooks o integraciones externas no dependen de claves legacy o configuraciones locales del repositorio.
- [ ] Revisar si hay scripts o herramientas de red en `tools/` o `dev-scripts/` que no deben confundirse con runtime.

## 8. Decision final

- [ ] `Staging`: aprobado si todo lo anterior pasa salvo ajustes menores de contenido.
- [ ] `Produccion`: aprobado solo si el paquete limpio, las pruebas tecnicas y la validacion manual VBP pasan completas.
- [ ] Si quedan cambios locales abiertos en VBP o mobile, registrarlos explicitamente antes de publicar.
