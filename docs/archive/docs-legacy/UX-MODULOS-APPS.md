# UX y UI de módulos en Apps (Cliente + Admin)

Estado: propuesta operativa basada en configuración actual y módulos activos.

## Resumen rápido
- Los módulos activados en `Apps Móviles → Módulos` habilitan la **disponibilidad API**, pero **no generan UI**.
- Para que un módulo exista en app, se requieren:
  - 1) Endpoint(s) para móvil.
  - 2) Navegación en app (tabs o secciones).
  - 3) Pantallas base (listado, detalle, crear/editar si admin).

## Arquitectura de navegación
- Cliente: tabs desde `/client/config`.
- Admin: pantallas fijas + acceso por dashboard.
- Propuesta: habilitar “Módulos” como secciones adicionales con rutas dinámicas y fallback.

## Matriz de módulos (mínimo UX)

### 1) Core (prioridad alta)
- Reservas
  - Cliente: listado/estado, detalle, checkout.
  - Admin: gestión, check-in, cancelación, export.
- Eventos
  - Cliente: listado, detalle, inscripción.
  - Admin: CRUD, aforo, check-in.
- Espacios comunes
  - Cliente: disponibilidad, reserva, historial.
  - Admin: gestión espacios, bloqueo, calendario.
- Biblioteca
  - Cliente: catálogo, detalle, reservas/préstamos.
  - Admin: CRUD, stock, devoluciones.
- Clientes (admin)
  - Admin: listado, detalle, notas, historial.

### 2) Comunidad (prioridad media)
- Foros
  - Cliente: hilos, respuestas.
  - Admin: moderación, reportes.
- Chat grupos
  - Cliente: grupos, chat.
  - Admin: gestión grupos, moderación.
- Avisos municipales
  - Cliente: feed, detalle.
  - Admin: CRUD + programación.
- Participación / Presupuestos
  - Cliente: propuestas, votos, estados.
  - Admin: fases, gestión y resultados.
- Transparencia
  - Cliente: documentos, buscador.
  - Admin: carga, categorías.

### 3) Cultura y contenidos
- Multimedia: galería, detalle. Admin: uploads/gestión.
- Cursos / Talleres: catálogo, detalle, inscripción. Admin: gestión.
- Radio / Podcast: listado, reproductor. Admin: programación/episodios.

### 4) Servicios y movilidad
- Carpooling: ofertas/solicitudes. Admin: moderación.
- Parkings: disponibilidad, reservas. Admin: tarifas, plazas.
- Huertos / Compostaje / Reciclaje: listados, reservas/reportes. Admin: campañas.
- Banco de tiempo: ofertas/demandas. Admin: moderación.

### 5) Extra
- Marketplace: listado, detalle, carrito. Admin: productos.
- Ayuda vecinal / Comunidades / Colectivos: listado, detalle, contacto.
- Trading IA / DEX Solana: panel básico cliente, ajustes admin.

## UI base por módulo (cliente)
- Header + filtros + búsqueda.
- Lista con estados (vacío, error, loading).
- Detalle con CTA principal.
- Favoritos/compartir cuando aplique.

## UI base por módulo (admin)
- Lista + filtros + estados.
- Detalle con acciones.
- Form CRUD (nuevo/editar).
- Export si aplica.

## Gap actual
- Muchos módulos no tienen pantallas Flutter ni rutas.
- Necesario crear scaffolds y wiring de navegación.

## Próximo paso recomendado
- Implementar scaffolds base y routing para Tanda 1.
- Luego añadir módulos de comunidad y contenidos.
