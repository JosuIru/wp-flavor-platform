# Auditoría de Enlaces y Botones Web (PHP)
Fecha: 2026-02-16

## Resumen Ejecutivo

| Categoría | Cantidad |
|-----------|----------|
| Total `href="#"` | 75 |
| Con onclick (OK) | 13 |
| Con data-* (OK) | 22 |
| **Potencialmente rotos** | **40** |

## Módulos con más problemas

| Módulo | Enlaces rotos |
|--------|---------------|
| banco-tiempo | 8 |
| huertos-urbanos | 4 |
| bicicletas-compartidas | 3 |
| bares | 3 |
| reciclaje | 2 |
| foros | 2 |
| eventos | 2 |
| ayuda-vecinal | 2 |
| avisos-municipales | 2 |
| (otros 12 módulos) | 12 |

## Priorización por Impacto

### P1 - Crítico (flujos principales)
- `eventos/class-eventos-module.php:344,461` - Botones 'Ver' y 'Gestionar' en admin de eventos
- `banco-tiempo/class-banco-tiempo-module.php` - 3 botones rotos (Ver, Ver Perfil, Editar)
- `tramites/class-tramites-module.php:2102` - 'Ver mi expediente' roto

### P2 - Alto (admin común)
- `ayuda-vecinal/class-ayuda-vecinal-module.php` - 2 botones (Ver, Ver Perfil)
- `avisos-municipales/class-avisos-municipales-module.php` - 2 botones (Ver, Editar, Republicar)
- `huertos-urbanos/class-huertos-urbanos-module.php` - 4 botones (Editar, Ver, Asignar, Rechazar)
- `foros/class-foros-module.php` - 2 botones (Editar, Ver)

### P3 - Medio
- `bares/class-bares-module.php` - 3 botones
- `bicicletas-compartidas/class-bicicletas-compartidas-module.php` - 3 botones
- `clientes/class-clientes-module.php` - 1 botón Editar
- `reciclaje/class-reciclaje-module.php` - 2 botones

## Detalle Completo por Archivo

| `ayuda-vecinal/class-ayuda-vecinal-module.php:760` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `ayuda-vecinal/class-ayuda-vecinal-module.php:832` | ' . __('Ver Perfil', 'flavor-chat-ia') . ' |
| `avisos-municipales/class-avisos-municipales-module.php:2386` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `avisos-municipales/class-avisos-municipales-module.php:2461` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `banco-tiempo/class-banco-tiempo-module.php:1114` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `banco-tiempo/class-banco-tiempo-module.php:1190` | ' . __('Ver Perfil', 'flavor-chat-ia') . ' |
| `banco-tiempo/class-banco-tiempo-module.php:1249` | ' . __('Editar', 'flavor-chat-ia') . ' |
| `banco-tiempo/views/intercambios.php:266` |  |
| `banco-tiempo/views/servicios.php:145` |  |
| `banco-tiempo/views/servicios.php:255` |  |
| `banco-tiempo/views/servicios.php:257` |  |
| `banco-tiempo/views/usuarios.php:256` |  |
| `bicicletas-compartidas/class-bicicletas-compartidas-module.php:1302` |  |
| `bicicletas-compartidas/class-bicicletas-compartidas-module.php:1373` |  |
| `bicicletas-compartidas/class-bicicletas-compartidas-module.php:1445` |  |
| `colectivos/views/crear-colectivo.php:109` |  |
| `huertos-urbanos/class-huertos-urbanos-module.php:339` | ' . __('Editar', 'flavor-chat-ia') . ' |
| `huertos-urbanos/class-huertos-urbanos-module.php:390` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `huertos-urbanos/class-huertos-urbanos-module.php:441` | ' . __('Asignar', 'flavor-chat-ia') . ' |
| `huertos-urbanos/class-huertos-urbanos-module.php:442` | ' . __('Rechazar', 'flavor-chat-ia') . ' |
| `bares/class-bares-module.php:720` | ' . __('Editar', 'flavor-chat-ia') . ' |
| `bares/class-bares-module.php:781` | ' . __('Confirmar', 'flavor-chat-ia') . ' |
| `bares/class-bares-module.php:783` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `chat-interno/class-chat-interno-module.php:2021` |  |
| `foros/class-foros-module.php:1651` | ' . esc_html__('Editar', 'flavor-chat-ia') . ' |
| `foros/class-foros-module.php:1652` | ' . esc_html__('Ver', 'flavor-chat-ia') . ' |
| `parkings/class-parkings-module.php:658` |  |
| `talleres/views/talleres.php:50` |  |
| `tramites/class-tramites-module.php:2102` |  |
| `grupos-consumo/views/consumidores.php:223` |  |
| `radio/views/dashboard.php:83` |  |
| `cursos/views/cursos.php:80` |  |
| `transparencia/class-transparencia-module.php:2569` |  |
| `compostaje/views/composteras.php:76` |  |
| `biblioteca/views/libros.php:74` |  |
| `eventos/class-eventos-module.php:344` | ' . __('Ver', 'flavor-chat-ia') . ' |
| `eventos/class-eventos-module.php:461` | ' . __('Gestionar', 'flavor-chat-ia') . ' |
| `reciclaje/class-reciclaje-module.php:593` | ' . __('Editar', 'flavor-chat-ia') . ' |
| `reciclaje/class-reciclaje-module.php:786` |  |
| `clientes/class-clientes-module.php:775` | ' . __('Editar', 'flavor-chat-ia') . ' |

## Archivo CSV
- `reports/matriz_enlaces_rotos_web_2026-02-16.csv`
