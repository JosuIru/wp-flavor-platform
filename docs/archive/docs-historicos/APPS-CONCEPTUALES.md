# Apps Conceptuales del Ecosistema Gailu

Inventario de las aplicaciones, secciones, componentes y páginas
que se quieren componer con el sistema de Page Builder + Themacle.

---

## Fuentes de referencia

- `/home/josu/Gailu Labs/gailu-building-blocks` — 16 apps React + 84 componentes
- `/home/josu/gailu_share/ecosistema` — Prototipos HTML + whitepapers + monorepo NestJS/Next.js

---

## 1. Comunidad Viva (app principal)

**Propósito:** Red social cooperativa local, dashboard central del ecosistema.

### Páginas
| Página | Descripción |
|--------|-------------|
| Feed | Actividad comunitaria en tiempo real |
| Marketplace | Acceso rápido al mercado local |
| Dashboard | Panel con métricas del ecosistema |
| Timeline | Histórico de actividad |
| Directorio | Directorio de miembros y organizaciones |

### Secciones / Componentes necesarios
- Hero con estadísticas de la comunidad (miembros, transacciones, impacto)
- Feed de actividad (ActivityPub)
- Grid de apps integradas (acceso a las demás apps)
- Mapa del ecosistema
- Métricas de impacto (social, ecológico, económico)
- CTA de registro / unirse

---

## 2. Jantoki (Restaurante Cooperativo)

**Propósito:** Cocina comunitaria, provisión local, menú km0.

### Páginas
| Página | Descripción |
|--------|-------------|
| Dashboard | Vista general del restaurante |
| Menú / Carta | Menú diario con filtros dietéticos |
| Reservas | Sistema de reservas |
| Pedidos | Pedidos para llevar / delivery |
| Productores | Perfiles de productores locales |
| Compras colectivas | Pedidos grupales a productores |
| Eventos | Eventos gastronómicos y talleres |
| Sostenibilidad | Métricas de huella de carbono |
| Analíticas | Estadísticas del restaurante |

### Secciones / Componentes necesarios
- Hero con imagen del restaurante + horario
- Card grid de platos del día (con alérgenos, precio, productor)
- Ficha de productor (foto, ubicación, productos, certificaciones)
- Formulario de reserva
- Calendario de eventos gastronómicos
- Highlights de sostenibilidad (kg locales, residuo cero, huella CO2)
- CTA "Hazte socio/a" del restaurante cooperativo

---

## 3. Mercado Espiral (Marketplace km0)

**Propósito:** Marketplace cooperativo local con cashback en SEMILLA.

### Páginas
| Página | Descripción |
|--------|-------------|
| Catálogo | Productos por categoría |
| Producto | Ficha de producto con trazabilidad |
| Productores | Directorio de productores |
| Carrito | Cesta de compra |
| Mi cuenta | Historial de pedidos |

### Categorías de producto
- Frutas y verduras
- Lácteos y huevos
- Carne y pescado
- Pan y repostería
- Conservas y granel
- Semillas y agrícola
- Artesanía

### Secciones / Componentes necesarios
- Hero con buscador de productos
- Barra de filtros por categoría (pills/tabs)
- Card grid de productos (imagen, nombre, precio, productor, km)
- Ficha de productor con mapa
- Highlights de impacto (productores locales, km ahorrados, familias)
- CTA "Vende en el mercado"
- Sección de temporada (productos del mes)

---

## 4. Spiral Bank (Banca Cooperativa)

**Propósito:** Sistema financiero cooperativo con multi-moneda.

### Páginas
| Página | Descripción |
|--------|-------------|
| Dashboard | Balance general multi-moneda |
| Wallet | Cartera (EUR + SEMILLA + Horas + ESTRELLAS) |
| Transferencias | Enviar/recibir pagos P2P |
| Préstamos | Micropréstamos a 0% interés |
| Círculos de ahorro | Grupos rotativos de ahorro |
| Bridge | Conversión entre monedas |
| Objetivos | Planificación financiera |
| Analíticas | Flujo circular de la economía |

### Secciones / Componentes necesarios
- Hero con saldo total multi-moneda
- Card grid de cuentas/monedas (saldo, tendencia, icono)
- Historial de transacciones (lista con filtros)
- Formulario de transferencia P2P
- Highlights financieros (ahorro colectivo, préstamos activos, circulación)
- Accordion de FAQ financiera
- CTA "Abrir cuenta cooperativa"

---

## 5. Red de Cuidados

**Propósito:** Red de apoyo mutuo y cuidados comunitarios.

### Páginas
| Página | Descripción |
|--------|-------------|
| Inicio | Vista general de la red |
| Profesionales | Directorio de profesionales de salud |
| Banco de tiempo | Intercambio de horas de cuidado |
| Seguro mutuo | Mutualidad de salud cooperativa |
| Círculos | Círculos de apoyo emocional |
| Mi perfil | Horas dadas/recibidas, reputación |

### Modelo de niveles
1. **Células de cuidado** (5-15 personas)
2. **Círculos de cuidado** (50-150 personas)
3. **Red territorial** (500-2.000 personas)
4. **Red biorregional** (10.000-50.000)

### Secciones / Componentes necesarios
- Hero con mensaje de comunidad de cuidados
- Card grid de profesionales (foto, especialidad, valoración, disponibilidad)
- Feature grid de tipos de cuidado (emergencia, regular, terapéutico, talleres)
- Mapa de círculos de cuidado
- Highlights (horas intercambiadas, personas cubiertas, tiempo respuesta)
- Accordion FAQ sobre el sistema de cuidados
- CTA "Únete a un círculo"

---

## 6. Academia Espiral (Educación P2P)

**Propósito:** Plataforma de aprendizaje entre iguales con recompensas.

### Páginas
| Página | Descripción |
|--------|-------------|
| Inicio | Rutas de aprendizaje destacadas |
| Rutas | Learning paths con progresión |
| Cursos | Catálogo de cursos |
| Habilidades | Mapa de competencias |
| Mentores | Directorio de mentores |
| Mis cursos | Progreso personal |

### Progresión
CURIOSO → ESTUDIANTE → PRACTICANTE → MAESTRO

### Secciones / Componentes necesarios
- Hero con buscador de cursos
- Feature grid de categorías educativas
- Card grid de cursos (imagen, título, nivel, mentor, duración)
- Ficha de mentor (foto, bio, habilidades, valoración)
- Barra de progreso de learning path
- Highlights (cursos completados, horas aprendidas, mentores activos)
- CTA "Enseña lo que sabes"

---

## 7. Democracia Universal (Gobernanza)

**Propósito:** Toma de decisiones colectiva con democracia líquida.

### Páginas
| Página | Descripción |
|--------|-------------|
| Propuestas | Lista de propuestas activas |
| Crear propuesta | Formulario de nueva propuesta |
| Detalle | Vista completa de propuesta con votación |
| Delegaciones | Gestión de delegación de voto |
| Dashboard | Métricas de participación |
| Federación | Decisiones inter-comunidades |
| Decisiones | Historial de decisiones tomadas |

### Sistemas de votación
- Democracia líquida (voto directo + delegación revocable)
- Votación cuadrática (coste = votos²)
- Votación por convicción (peso según duración)

### Secciones / Componentes necesarios
- Hero con propuesta destacada
- Card grid de propuestas (título, estado, votos a favor/contra, plazo)
- Feature grid de sistemas de votación disponibles
- Formulario de propuesta (título, descripción, categoría, presupuesto)
- Highlights de participación (propuestas, votos, delegaciones, tasa participación)
- Accordion FAQ de gobernanza

---

## 8. FLUJO (Red de Vídeo Consciente)

**Propósito:** Alternativa a YouTube que recompensa impacto, no adicción.

### Páginas
| Página | Descripción |
|--------|-------------|
| Feed | Vídeos con métricas de impacto |
| Subir | Formulario de subida con categorización |
| Mis vídeos | Gestión de contenido propio |
| Impacto | Dashboard de impacto personal |
| Perfil | Perfil de creador |
| Habilidades | Vídeos por habilidad/tema |
| Aprendizaje | Rutas de vídeo educativo |

### Categorías de contenido
- Tutoriales y how-to (ALTO VALOR 3x)
- Permacultura y ecología (ALTO VALOR 3x)
- Cooperación y comunidad (ALTO VALOR 3x)
- Innovación social (ALTO VALOR 3x)
- Cultura local (ALTO VALOR 3x)

### Secciones / Componentes necesarios
- Hero con vídeo destacado
- Card grid de vídeos (thumbnail, título, creador, vistas, impacto)
- Filtros por categoría y nivel de impacto
- Perfil de creador con métricas
- Highlights (vídeos subidos, horas vistas, impacto generado)
- CTA "Comparte tu conocimiento"

---

## 9. Kulturaka (Cultura Cooperativa)

**Propósito:** Plataforma de eventos y producción cultural cooperativa.

### Páginas
| Página | Descripción |
|--------|-------------|
| Eventos | Calendario y listado de eventos |
| Detalle evento | Ficha completa del evento |
| Mis entradas | Gestión de tickets |
| Calendario | Vista calendario |
| Organizadores | Perfiles de organizadores |
| Streaming | Eventos en directo |
| Artistas | Directorio de artistas |
| Biblioteca | Archivo cultural |

### Tipos de evento
- Conciertos y música
- Teatro y danza
- Exposiciones y arte
- Talleres creativos
- Cine y documentales
- Poesía y literatura
- Arte callejero

### Secciones / Componentes necesarios
- Hero con próximo evento destacado
- Card grid de eventos (imagen, fecha, título, tipo, precio, aforo)
- Filtros por tipo de evento (tabs/pills)
- Calendario mensual/semanal
- Ficha de artista (foto, bio, disciplina, próximos eventos)
- Highlights (eventos realizados, asistentes, artistas, recaudación cultural)
- CTA "Organiza un evento"

---

## 10. Pueblo Vivo (Revitalización Rural)

**Propósito:** Combatir la despoblación rural con infraestructura digital.

### Páginas
| Página | Descripción |
|--------|-------------|
| Dashboard | Estado del pueblo |
| Casas vacías | Banco de casas con modelo guardián |
| Hub rural | Coworking y servicios compartidos |
| Servicios | Servicios itinerantes (clínica, mercado) |
| Transporte | Transporte compartido |
| Banco de tiempo | Intercambio entre vecinos |
| Comunidad | Red social local |
| Raíces | Gamificación de arraigo rural |
| Federación | Coordinación inter-municipal |
| Salud | Red de salud rural |

### Sistema de gamificación "Raíces" (6 niveles)
Visitante → Explorador → Vecino → Arraigado → Guardián → Sabio

### Secciones / Componentes necesarios
- Hero con imagen del pueblo + cifras clave
- Card grid de casas disponibles (foto, m², estado, precio guardián)
- Mapa interactivo del pueblo (casas, servicios, huertos)
- Feature grid de servicios itinerantes (calendario)
- Highlights (población, casas recuperadas, comercio local, SEMILLA)
- Accordion FAQ del programa guardián
- CTA "Ven a vivir aquí"

---

## 11. Tierra Estella Viva (Ecosistema Regional)

**Propósito:** Plataforma regional para 72 municipios con fondos LEADER.

### Páginas
| Página | Descripción |
|--------|-------------|
| Dashboard | Vista regional con 72 municipios |
| LEADER | Gestión gamificada de fondos LEADER (800K€/año) |
| Marketplace | Mercado regional |
| Vía Verde | Corredor verde de 29km |
| Cuidados | Red de cuidados comarcal |
| Movilidad | Transporte compartido regional |
| Vivienda | Banco de vivienda comarcal |
| Municipios | Directorio de municipios |
| Logros | Sistema de logros |

### Secciones / Componentes necesarios
- Hero con mapa de la comarca
- Card grid de municipios (nombre, población, servicios, estado)
- Feature grid de programas LEADER activos
- Highlights regionales (municipios, población, fondos, proyectos)
- Mapa interactivo de la Vía Verde
- Accordion FAQ sobre el programa LEADER

---

## 12. Ecos Comunitarios (Espacios Compartidos)

**Propósito:** Gestión de espacios comunes y recursos compartidos.

### Páginas
| Página | Descripción |
|--------|-------------|
| Mapa | Mapa de espacios disponibles |
| Espacios | Catálogo de espacios |
| Reservas | Sistema de reservas |
| Recursos | Herramientas y materiales compartidos |
| Impacto | Métricas de uso y ahorro |

### Secciones / Componentes necesarios
- Hero con buscador de espacios
- Card grid de espacios (foto, nombre, capacidad, disponibilidad, equipamiento)
- Calendario de disponibilidad
- Formulario de reserva
- Highlights (espacios, reservas/mes, horas compartidas, ahorro)

---

## Componentes universales necesarios (Themacle)

Basado en las apps anteriores, estos son los componentes reutilizables que cubren todas las necesidades:

| Componente | Uso en apps |
|-----------|-------------|
| `hero_fullscreen` | Todas las páginas de inicio |
| `hero_split` | Páginas interiores, secciones "sobre nosotros" |
| `card_grid` | Productos, eventos, cursos, casas, espacios, propuestas |
| `feature_grid` | Cómo funciona, categorías, pasos, ventajas |
| `text_media` | Secciones descriptivas con imagen |
| `highlights` | Métricas, estadísticas, cifras clave |
| `accordion` | FAQ, información desplegable |
| `cta_banner` | Llamadas a la acción en todas las apps |
| `filters_bar` | Filtros de categoría en catálogos |
| `map_section` | Ubicación, contacto, puntos de interés |
| `gallery` | Galería de fotos en eventos, espacios, productos |
| `newsletter` | Suscripción en todas las apps |
| `pagination` | Navegación en listados |
| `related_items` | Elementos relacionados |
| `post_content` | Contenido de artículo/blog |
| `hero_slider` | Carruseles de destacados |

---

## Patrones comunes entre apps

1. **Todas las apps tienen:** Hero + Card grid + Highlights + CTA + FAQ
2. **Apps con catálogo:** Filtros + Card grid + Paginación + Ficha detalle
3. **Apps con comunidad:** Feed + Perfiles + Mapa + Métricas
4. **Apps con economía:** Dashboard multi-moneda + Historial + Formularios
5. **Apps con eventos:** Calendario + Cards + Filtros + Detalle

---

## Monedas del ecosistema

| Moneda | Símbolo | Uso |
|--------|---------|-----|
| Euro | EUR | Moneda convencional |
| SEMILLA | 🌱 | Token social con demurrage (pierde valor si se acumula) |
| Horas | ⏱️ | Banco de tiempo (1h = 1h, sin importar el servicio) |
| ESTRELLAS | ⭐ | Moneda regional (1:1 con EUR) |

---

## Principios de diseño

1. **Cooperación sobre competición** — No hay rankings extractivos
2. **Multi-valor** — Todo acepta EUR + SEMILLA + Horas
3. **Transparencia radical** — Código abierto, datos abiertos
4. **Regeneración** — Medir y mejorar impacto, no solo mantener
5. **Prueba de Ayuda** — Reputación basada en contribución real
6. **Gobernanza distribuida** — 1 persona = 1 voto
7. **Expansión espiral** — De lo local a lo global, orgánicamente
