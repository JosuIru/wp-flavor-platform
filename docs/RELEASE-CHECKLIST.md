# Checklist de Release - Flavor Platform

Este documento describe todos los pasos necesarios antes, durante y despues de publicar una nueva version.

---

## Pre-Release Checklist

### Codigo

- [ ] **Todos los tests pasan**
  ```bash
  npm test
  composer test
  ```

- [ ] **Lint sin errores**
  ```bash
  npm run lint
  ```

- [ ] **Build de produccion exitoso**
  ```bash
  npm run build:prod
  ```

- [ ] **Sin errores de sintaxis PHP**
  ```bash
  find includes -name "*.php" -exec php -l {} \;
  ```

- [ ] **Sin errores de sintaxis JS**
  ```bash
  find assets -name "*.js" ! -name "*.min.js" -exec node --check {} \;
  ```

### Documentacion

- [ ] **CHANGELOG.md actualizado**
  - Nueva seccion con fecha
  - Todas las features documentadas
  - Breaking changes destacados
  - Links a PRs/issues

- [ ] **Release notes escritas** (para releases mayores)
  - `docs/releases/vX.Y.Z.md`
  - Highlights con capturas
  - Guia de migracion
  - Breaking changes explicados

- [ ] **README.md actualizado** (si aplica)
  - Badges de version
  - Nuevas instrucciones

- [ ] **Documentacion API actualizada** (si hay cambios)
  - Nuevos endpoints
  - Parametros modificados
  - Ejemplos actualizados

### Version

- [ ] **Version bumped en todos los archivos**
  ```bash
  # Verificar que coincidan:
  grep "Version:" flavor-platform.php
  grep '"version"' package.json
  grep "FLAVOR_PLATFORM_VERSION" flavor-platform.php
  ```

- [ ] **Fecha de release en CHANGELOG.md**
  - Formato: `## [X.Y.Z] - YYYY-MM-DD`

### Testing Manual

- [ ] **Editor VBP funciona**
  - Abrir una pagina en el editor
  - Crear/editar elementos
  - Guardar cambios
  - Preview frontend

- [ ] **Funcionalidades nuevas probadas**
  - Cada feature de la version
  - Happy path completo
  - Edge cases principales

- [ ] **Migracion probada** (si hay cambios de datos)
  - Desde version anterior
  - Datos se migran correctamente

- [ ] **Sin regresiones**
  - Funcionalidades existentes siguen funcionando
  - Tests de humo en modulos principales

### Seguridad

- [ ] **Auditoria de dependencias**
  ```bash
  npm audit
  composer audit
  ```

- [ ] **Sin credenciales expuestas**
  ```bash
  git grep -i "password\|secret\|api_key" -- "*.php" "*.js"
  ```

- [ ] **Sin debug code en produccion**
  ```bash
  git grep -i "console\.log\|var_dump\|print_r" -- "*.php" "*.js"
  ```

### Git

- [ ] **Branch limpio**
  ```bash
  git status  # Sin cambios pendientes
  ```

- [ ] **Sincronizado con origin**
  ```bash
  git fetch origin
  git status  # No adelantado/atrasado
  ```

- [ ] **Commits siguen Conventional Commits**
  ```bash
  git log --oneline -20  # Revisar formato
  ```

---

## Release Process

### 1. Preparar Release

```bash
# Crear branch de release (opcional para releases grandes)
git checkout -b release/v3.6.0

# O directamente en main para patches
git checkout main
git pull origin main
```

### 2. Bump Version

```bash
# Automatico con script
npm run release:patch   # 3.5.0 -> 3.5.1
npm run release:minor   # 3.5.0 -> 3.6.0
npm run release:major   # 3.5.0 -> 4.0.0

# O manual
bash scripts/bump-version.sh 3.6.0
```

### 3. Actualizar CHANGELOG

Mover items de `[Unreleased]` a nueva seccion con fecha.

### 4. Build Final

```bash
npm run build:prod
npm run build:vbp
```

### 5. Commit de Release

```bash
git add -A
git commit -m "release: v3.6.0

- Feature A
- Feature B
- Fix C

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

### 6. Crear Tag

```bash
git tag -a v3.6.0 -m "Release v3.6.0

Highlights:
- Feature A
- Feature B

See CHANGELOG.md for full details."
```

### 7. Push

```bash
git push origin main
git push origin v3.6.0
```

### 8. Generar ZIP de Distribucion

```bash
bash scripts/release.sh
# Genera: dist/flavor-platform-3.6.0.zip
```

### 9. Crear GitHub Release

1. Ir a GitHub > Releases > New Release
2. Seleccionar tag `v3.6.0`
3. Titulo: `v3.6.0 - Nombre descriptivo`
4. Descripcion: Copiar de release notes
5. Adjuntar `dist/flavor-platform-3.6.0.zip`
6. Publicar

---

## Post-Release Checklist

### Verificacion

- [ ] **GitHub Release publicado**
  - Tag correcto
  - ZIP adjunto
  - Descripcion completa

- [ ] **ZIP descargable y funcional**
  - Descargar y descomprimir
  - Instalar en WordPress limpio
  - Activar sin errores

- [ ] **Plugin funciona en produccion**
  - Test en sitio de staging
  - Verificar features principales

### Comunicacion

- [ ] **Notificar al equipo**
  - Slack/Discord/Email
  - Enlace a release notes

- [ ] **Actualizar demo** (si existe)
  - Subir nueva version
  - Verificar funcionalidad

- [ ] **Blog post** (para releases mayores)
  - Destacar novedades
  - Guia de actualizacion

### Siguiente Ciclo

- [ ] **Bump a siguiente version dev**
  ```bash
  # En CHANGELOG.md, agregar:
  ## [Unreleased]

  ### Added
  ### Changed
  ### Fixed
  ```

- [ ] **Crear milestone para siguiente version**
  - GitHub > Milestones > New

- [ ] **Revisar issues pendientes**
  - Repriorizar para siguiente version

---

## Rollback (Si es Necesario)

### Revertir Release

```bash
# Eliminar tag local
git tag -d v3.6.0

# Eliminar tag remoto
git push origin :refs/tags/v3.6.0

# Revertir commit
git revert HEAD
git push origin main
```

### Comunicar Rollback

1. Actualizar GitHub Release como Draft o eliminar
2. Notificar al equipo
3. Publicar nota explicando el problema

---

## Checklist Rapido (Copia/Pega)

```markdown
## Release v3.X.X Checklist

### Pre-Release
- [ ] Tests pasan: `npm test`
- [ ] Lint pasa: `npm run lint`
- [ ] Build exitoso: `npm run build:prod`
- [ ] CHANGELOG.md actualizado
- [ ] Version bumped en archivos
- [ ] Testing manual completado
- [ ] Sin credenciales expuestas
- [ ] Auditoria de seguridad

### Release
- [ ] Commit de release
- [ ] Tag creado y pusheado
- [ ] ZIP generado
- [ ] GitHub Release publicado

### Post-Release
- [ ] Verificado en staging
- [ ] Equipo notificado
- [ ] Siguiente version preparada
```

---

## Versionado Semantico

### Cuando incrementar cada numero

| Version | Cuando usar | Ejemplo |
|---------|-------------|---------|
| **MAJOR** (X.0.0) | Breaking changes | API incompatible |
| **MINOR** (0.X.0) | Nuevas features | Nueva funcionalidad |
| **PATCH** (0.0.X) | Bug fixes | Correccion de errores |

### Ejemplos

```
3.5.0 -> 3.5.1  # Fix de bug menor
3.5.0 -> 3.6.0  # Nueva feature sin breaking changes
3.5.0 -> 4.0.0  # Cambio de API o breaking change
```

---

## Herramientas

| Script | Descripcion |
|--------|-------------|
| `scripts/release.sh` | Script principal de release |
| `scripts/bump-version.sh` | Actualiza version en archivos |
| `scripts/generate-changelog.js` | Genera changelog desde commits |

---

## Contacto

Para dudas sobre el proceso de release:
- **Slack:** #releases
- **Email:** releases@gailu.net
