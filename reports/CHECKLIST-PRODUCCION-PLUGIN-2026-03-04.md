# Checklist de Produccion del Plugin 2026-03-04

## Uso

Esta checklist debe completarse antes de declarar el plugin apto para produccion.

## 1. Base comun

- [ ] bootstrap carga sin fatales
- [ ] loader registra modulos sin errores visibles
- [ ] paginas dinamicas resuelven rutas del portal
- [ ] `redirect_to` de login usa la request real
- [ ] no quedan shortcodes legacy visibles en HTML activo
- [ ] documentacion canonica actualizada

## 2. Datos y esquema

- [ ] tablas requeridas presentes
- [ ] CPT y taxonomias requeridos presentes
- [ ] migraciones de upgrade revisadas
- [ ] modulos dependientes de entorno clasificados
- [ ] dataset minimo disponible para validacion

## 3. Seguridad y permisos

- [ ] rutas privadas protegidas
- [ ] nonces y `permission_callback` revisados
- [ ] AJAX principal probado
- [ ] REST principal probado
- [ ] mensajes de acceso denegado coherentes

## 4. Portal y UX

- [ ] rutas principales del portal responden
- [ ] tabs y widgets llevan a acciones reales
- [ ] estados vacios tienen CTA util
- [ ] assets cargan bien
- [ ] no hay vistas admin incrustadas en frontend

## 5. Modulos criticos

- [ ] `marketplace`
- [ ] `tramites`
- [ ] `reservas`
- [ ] `foros`
- [ ] `colectivos`
- [ ] `socios`
- [ ] `participacion`
- [ ] `eventos`

## 6. Validacion tecnica

- [ ] `php -l` sobre archivos tocados
- [ ] logs PHP revisados
- [ ] errores `403/404/500` revisados
- [ ] consola y red del navegador revisadas
- [ ] sin regresiones visibles en dashboard y portal

## 7. Release

- [ ] matriz de modulos actualizada
- [ ] backlog residual clasificado
- [ ] modulos fuera de release identificados
- [ ] informe de go/no-go preparado
- [ ] decision final documentada
