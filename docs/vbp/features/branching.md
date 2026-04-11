# Branching (Ramas de Diseno)

Sistema de ramas para trabajo paralelo y experimentacion, similar a Git pero para disenos visuales.

## Descripcion

El sistema de Branching permite crear versiones alternativas de un diseno para experimentar sin afectar el trabajo principal. Puedes crear ramas, trabajar en paralelo, comparar diferencias y fusionar cambios.

## Conceptos

### Branch (Rama)

Una version alternativa del documento. Cada branch tiene:
- Nombre y descripcion
- Padre (branch de origen)
- Estado (active, merged, archived)
- Historial de versiones

### Main Branch

La rama principal, similar a `main` o `master` en Git. Es la version "oficial" del diseno.

### Merge

Fusionar los cambios de una rama en otra.

### Diff

Comparar las diferencias entre dos ramas.

## Abrir Panel de Branching

- Toolbar: Click en el nombre de la rama actual
- Paleta de comandos: "Branches" o "Ramas"
- Menu: View > Branches

## Crear Nueva Rama

1. Abre el panel de Branching
2. Click en "Nueva Rama"
3. Ingresa nombre y descripcion
4. Elige rama base (por defecto: actual)
5. Click en "Crear"

## Cambiar de Rama (Checkout)

1. Abre el panel de Branching
2. Click en la rama destino
3. Confirma el cambio

**Nota**: Si tienes cambios sin guardar, se te pedira guardarlos primero.

## Fusionar Ramas (Merge)

1. Ve a la rama destino (ej: main)
2. Abre panel de Branching
3. Click en "Merge" junto a la rama origen
4. Resuelve conflictos si los hay
5. Confirma la fusion

### Conflictos

Si ambas ramas modificaron el mismo elemento:

1. El sistema detecta el conflicto
2. Te muestra ambas versiones
3. Elige cual mantener (o combina manualmente)
4. Continua con el merge

## Comparar Ramas (Diff)

1. Abre panel de Branching
2. Click en "Comparar"
3. Selecciona las dos ramas
4. Ve las diferencias visuales

### Vista de Diff

- **Added**: Elementos nuevos (verde)
- **Removed**: Elementos eliminados (rojo)
- **Modified**: Elementos cambiados (amarillo)
- **Unchanged**: Sin cambios (gris)

## Estados de Rama

| Estado | Descripcion |
|--------|-------------|
| **active** | Rama activa, se puede editar |
| **merged** | Rama fusionada, solo lectura |
| **archived** | Rama archivada, oculta por defecto |

## API JavaScript

### Acceder al Modulo

```javascript
const branching = window.VBPAppBranching;
```

### Propiedades

```javascript
branching.branches         // Array de ramas
branching.currentBranch    // Rama actual
branching.isLoadingBranches
branching.isCreatingBranch
branching.isCheckingOut
branching.isMerging
branching.hasUnsavedChanges
```

### Cargar Ramas

```javascript
branching.loadBranches().then(() => {
    console.log('Ramas:', branching.branches);
});
```

### Obtener Rama Actual

```javascript
const currentName = branching.getCurrentBranchName();
const isMain = branching.isCurrentBranchMain();
```

### Crear Rama

```javascript
branching.newBranchName = 'nueva-feature';
branching.newBranchDescription = 'Experimentando con nuevo header';
branching.createBranch();
```

### Cambiar de Rama

```javascript
branching.checkoutBranch(branchId);
```

### Fusionar

```javascript
branching.mergeSourceBranch = sourceBranch;
branching.mergeTargetBranch = targetBranch;
branching.startMerge();
```

### Comparar

```javascript
branching.diffBranchA = branchA;
branching.diffBranchB = branchB;
branching.loadDiff().then(diff => {
    console.log('Diferencias:', diff);
});
```

### Eliminar Rama

```javascript
branching.deleteBranch(branchId);
```

### Archivar Rama

```javascript
branching.archiveBranch(branchId);
```

## API REST

### Listar Ramas

```http
GET /wp-json/flavor-vbp/v1/branches/{post_id}
```

### Crear Rama

```http
POST /wp-json/flavor-vbp/v1/branches
Content-Type: application/json

{
    "post_id": 123,
    "name": "Nueva Feature",
    "description": "Experimentando con header",
    "from_branch": 1
}
```

### Checkout

```http
POST /wp-json/flavor-vbp/v1/branches/{branch_id}/checkout
```

### Merge

```http
POST /wp-json/flavor-vbp/v1/branches/merge
Content-Type: application/json

{
    "source_branch": 2,
    "target_branch": 1,
    "resolutions": {...}
}
```

### Diff

```http
GET /wp-json/flavor-vbp/v1/branches/diff?branch_a=1&branch_b=2
```

### Eliminar

```http
DELETE /wp-json/flavor-vbp/v1/branches/{branch_id}
```

## Base de Datos

### Tabla: wp_vbp_branches

```sql
CREATE TABLE wp_vbp_branches (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    post_id bigint(20) unsigned NOT NULL,
    branch_name varchar(255) NOT NULL,
    branch_slug varchar(255) NOT NULL,
    parent_branch_id bigint(20) unsigned DEFAULT NULL,
    created_by bigint(20) unsigned NOT NULL,
    created_at datetime NOT NULL,
    description text,
    status varchar(20) DEFAULT 'active',
    merged_into_branch_id bigint(20) unsigned DEFAULT NULL,
    merged_at datetime DEFAULT NULL,
    PRIMARY KEY (id)
);
```

### Tabla: wp_vbp_branch_versions

```sql
CREATE TABLE wp_vbp_branch_versions (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    branch_id bigint(20) unsigned NOT NULL,
    version_data longtext NOT NULL,
    version_hash varchar(64) NOT NULL,
    created_at datetime NOT NULL,
    created_by bigint(20) unsigned NOT NULL,
    message varchar(500),
    PRIMARY KEY (id)
);
```

## Eventos

```javascript
// Ramas cargadas
document.addEventListener('vbp:branches:loaded', (e) => {
    console.log('Ramas:', e.detail.branches);
});

// Rama creada
document.addEventListener('vbp:branch:created', (e) => {
    console.log('Nueva rama:', e.detail.branch);
});

// Checkout realizado
document.addEventListener('vbp:branch:checkout', (e) => {
    console.log('Ahora en:', e.detail.branch);
});

// Merge completado
document.addEventListener('vbp:branch:merged', (e) => {
    console.log('Fusionado:', e.detail.source, 'en', e.detail.target);
});

// Conflictos detectados
document.addEventListener('vbp:merge:conflicts', (e) => {
    console.log('Conflictos:', e.detail.conflicts);
});
```

## Flujo de Trabajo Tipico

### Experimentar con Diseno

1. Crear rama: "experimento-header"
2. Hacer cambios
3. Si funciona: merge a main
4. Si no: archivar rama

### Revision de Cliente

1. Crear rama: "revision-cliente-v2"
2. Aplicar feedback
3. Mostrar al cliente
4. Si aprueba: merge a main
5. Si no: iterar en la rama

### Trabajo en Paralelo

1. Desarrollador A: rama "feature-hero"
2. Desarrollador B: rama "feature-footer"
3. Cada uno trabaja independientemente
4. Merge ambas a main cuando esten listas

## Consideraciones

- Cada rama es una copia completa del documento
- El merge automatico funciona para la mayoria de casos
- Los conflictos requieren resolucion manual
- Las ramas archivadas se pueden restaurar
- El historial de versiones se mantiene por rama

## Limitaciones

- No soporta merge de 3 vias (como Git)
- Los conflictos son a nivel de elemento completo
- No hay rebase (solo merge)
- No hay cherry-pick (aun)

## Solucionar Problemas

### No puedo cambiar de rama

1. Guarda los cambios actuales
2. Verifica que la rama destino existe
3. Comprueba permisos de usuario

### Merge falla

1. Revisa los conflictos detalladamente
2. Asegurate de resolver todos los conflictos
3. Intenta merge en la direccion opuesta

### Rama no aparece

1. Recarga la lista de ramas
2. Verifica que no esta archivada
3. Comprueba que pertenece al mismo post
