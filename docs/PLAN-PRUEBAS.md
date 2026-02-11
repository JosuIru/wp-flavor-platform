# Plan de Pruebas (Exhaustivo)

## 1. Pruebas de API (REST + AJAX)

- Autenticacion (login, token)
- Permisos (admin vs usuario vs anonimo)
- Validaciones (inputs invalidos, faltantes)
- Errores (500/400/403)
- Rate limit en endpoints publicos
- Versionado de payloads en apps

## 2. Flujos Criticos

- Reservas: buscar disponibilidad -> crear -> pago -> confirmacion
- Chat: iniciar sesion -> enviar -> recibir -> adjuntos
- Campamentos: listar -> detalle -> inscripcion -> admin gestionar
- Newsletter: suscribir -> confirmar -> baja
- Notificaciones: registro push -> envio -> recepcion
- Addon restaurante: pedido -> pago -> estado -> cancelacion
- Marketplace: crear anuncio -> aprobar -> publicar -> compra
- Grupos consumo: unirse -> pedido -> cierre -> entrega
- WooCommerce: carrito -> checkout -> pedido

## 3. UI/UX

- Estados vacios
- Mensajes de error
- Responsive
- Accesibilidad basica (focus, labels, contraste)

## 4. Rendimiento

- Listados grandes
- Paginacion
- Cache
- Cargas iniciales de apps

## 5. Seguridad

- CSRF (nonce en AJAX)
- XSS en formularios
- Inyeccion SQL en consultas
- Abuso de endpoints publicos (tracking)

## 6. Compatibilidad

- PHP versions y WordPress min version
- Navegadores principales
- Android/iOS
- WPML y traducciones visibles

## 7. Smoke tests (release)

- Activar plugin + addons esenciales sin errores.
- Crear app basica y verificar landing.
- Login admin + acceso dashboard + stats.
