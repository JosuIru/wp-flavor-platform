part of 'recetas_screen.dart';

// =============================================================================
// TARJETA DE RECETA
// =============================================================================

class _RecetaCard extends StatelessWidget {
  final Map<String, dynamic> receta;
  final VoidCallback onTap;

  const _RecetaCard({
    required this.receta,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = receta['titulo'] ?? 'Sin título';
    final imagen = receta['imagen_thumb'] ?? receta['imagen'];
    final tiempoTotal = receta['tiempo_total'];
    final porciones = receta['porciones'];
    final dificultad = receta['dificultad'] ?? '';
    final categorias = receta['categorias'] as List<dynamic>? ?? [];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen
            SizedBox(
              width: 120,
              height: 120,
              child: imagen != null
                  ? Image.network(
                      imagen,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        color: Colors.orange.shade100,
                        child: Icon(Icons.restaurant_menu,
                            size: 40, color: Colors.orange.shade300),
                      ),
                    )
                  : Container(
                      color: Colors.orange.shade100,
                      child: Icon(Icons.restaurant_menu,
                          size: 40, color: Colors.orange.shade300),
                    ),
            ),
            // Contenido
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Título
                    Text(
                      titulo,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    // Dificultad
                    if (dificultad.isNotEmpty) ...[
                      _DificultadBadge(dificultad: dificultad),
                      const SizedBox(height: 8),
                    ],
                    // Metadatos
                    Row(
                      children: [
                        if (tiempoTotal != null && tiempoTotal > 0) ...[
                          Icon(Icons.timer,
                              size: 14, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            '$tiempoTotal min',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          const SizedBox(width: 12),
                        ],
                        if (porciones != null && porciones.toString().isNotEmpty) ...[
                          Icon(Icons.people,
                              size: 14, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            '$porciones',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                      ],
                    ),
                    // Categorías
                    if (categorias.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 4,
                        runSpacing: 4,
                        children: categorias
                            .take(2)
                            .map((cat) => Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: Colors.orange.shade50,
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text(
                                    cat.toString(),
                                    style: TextStyle(
                                      fontSize: 10,
                                      color: Colors.orange.shade700,
                                    ),
                                  ),
                                ))
                            .toList(),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            // Flecha
            Padding(
              padding: const EdgeInsets.all(12),
              child: Icon(Icons.arrow_forward_ios,
                  size: 14, color: Colors.grey.shade400),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// TARJETA DE RECETA DESTACADA
// =============================================================================

class _RecetaCardDestacada extends StatelessWidget {
  final Map<String, dynamic> receta;
  final VoidCallback onTap;

  const _RecetaCardDestacada({
    required this.receta,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = receta['titulo'] ?? 'Sin título';
    final imagen = receta['imagen'];
    final extracto = receta['extracto'] ?? '';
    final tiempoTotal = receta['tiempo_total'];
    final porciones = receta['porciones'];
    final dificultad = receta['dificultad'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen grande
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  imagen != null
                      ? Image.network(
                          imagen,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            color: Colors.orange.shade100,
                            child: Icon(Icons.restaurant_menu,
                                size: 60, color: Colors.orange.shade300),
                          ),
                        )
                      : Container(
                          color: Colors.orange.shade100,
                          child: Icon(Icons.restaurant_menu,
                              size: 60, color: Colors.orange.shade300),
                        ),
                  // Badge destacado
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.amber,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.star, size: 14, color: Colors.white),
                          SizedBox(width: 4),
                          Text(
                            'Destacada',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // Dificultad
                  if (dificultad.isNotEmpty)
                    Positioned(
                      top: 12,
                      right: 12,
                      child: _DificultadBadge(dificultad: dificultad),
                    ),
                ],
              ),
            ),
            // Contenido
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    titulo,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                  ),
                  if (extracto.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      extracto,
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 13,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      if (tiempoTotal != null && tiempoTotal > 0) ...[
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.timer,
                                  size: 16, color: Colors.orange.shade700),
                              const SizedBox(width: 4),
                              Text(
                                '$tiempoTotal min',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.orange.shade700,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                      ],
                      if (porciones != null && porciones.toString().isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.people,
                                  size: 16, color: Colors.grey.shade700),
                              const SizedBox(width: 4),
                              Text(
                                '$porciones porciones',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade700,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// BADGE DE DIFICULTAD
// =============================================================================

class _DificultadBadge extends StatelessWidget {
  final String dificultad;

  const _DificultadBadge({required this.dificultad});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: _getColor().withOpacity(0.9),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        _getLabel(),
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),
    );
  }

  String _getLabel() {
    switch (dificultad) {
      case 'facil':
        return 'Fácil';
      case 'media':
        return 'Media';
      case 'dificil':
        return 'Difícil';
      default:
        return dificultad;
    }
  }

  Color _getColor() {
    switch (dificultad) {
      case 'facil':
        return Colors.green;
      case 'media':
        return Colors.orange;
      case 'dificil':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

// =============================================================================
// TARJETA DE CATEGORÍA
// =============================================================================

class _CategoriaCard extends StatelessWidget {
  final String nombre;
  final int total;
  final Color color;
  final IconData icono;
  final VoidCallback onTap;

  const _CategoriaCard({
    required this.nombre,
    required this.total,
    required this.color,
    required this.icono,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                color.withOpacity(0.1),
                color.withOpacity(0.05),
              ],
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icono, color: color, size: 28),
              const SizedBox(height: 8),
              Text(
                nombre,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  color: color,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              Text(
                '$total recetas',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DE DETALLE
// =============================================================================

class _RecetaDetalleScreen extends ConsumerStatefulWidget {
  final int recetaId;

  const _RecetaDetalleScreen({required this.recetaId});

  @override
  ConsumerState<_RecetaDetalleScreen> createState() =>
      _RecetaDetalleScreenState();
}

class _RecetaDetalleScreenState extends ConsumerState<_RecetaDetalleScreen> {
  Map<String, dynamic>? _receta;
  bool _cargando = true;
  int _porcionesSeleccionadas = 1;

  @override
  void initState() {
    super.initState();
    _cargarReceta();
  }

  Future<void> _cargarReceta() async {
    setState(() => _cargando = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/recetas/${widget.recetaId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _receta = respuesta.data!['receta'];
          final porciones = _receta?['porciones'];
          if (porciones != null && porciones.toString().isNotEmpty) {
            _porcionesSeleccionadas = int.tryParse(porciones.toString()) ?? 1;
          }
        });
      }
    } finally {
      setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_cargando) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Receta'),
          backgroundColor: Colors.orange.shade700,
          foregroundColor: Colors.white,
        ),
        body: const FlavorLoadingState(),
      );
    }

    if (_receta == null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Receta'),
          backgroundColor: Colors.orange.shade700,
          foregroundColor: Colors.white,
        ),
        body: const FlavorEmptyState(
          icon: Icons.error_outline,
          title: 'Receta no encontrada',
        ),
      );
    }

    final titulo = _receta!['titulo'] ?? 'Sin título';
    final imagen = _receta!['imagen'];
    final tiempoPreparacion = _receta!['tiempo_preparacion'];
    final tiempoCoccion = _receta!['tiempo_coccion'];
    final tiempoTotal = _receta!['tiempo_total'];
    final porciones = _receta!['porciones'];
    final dificultad = _receta!['dificultad'] ?? '';
    final calorias = _receta!['calorias'];
    final ingredientes = _receta!['ingredientes'] as List<dynamic>? ?? [];
    final pasos = _receta!['pasos'] as List<dynamic>? ?? [];
    final productosVinculados =
        _receta!['productos_vinculados'] as List<dynamic>? ?? [];
    final autor = _receta!['autor'] as Map<String, dynamic>?;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // AppBar con imagen
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            backgroundColor: Colors.orange.shade700,
            foregroundColor: Colors.white,
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  imagen != null
                      ? Image.network(
                          imagen,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            color: Colors.orange.shade200,
                          ),
                        )
                      : Container(color: Colors.orange.shade200),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withOpacity(0.7),
                        ],
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 16,
                    left: 16,
                    right: 16,
                    child: Text(
                      titulo,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.share),
                onPressed: () {},
              ),
            ],
          ),
          // Contenido
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Info rápida
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          if (tiempoTotal != null && tiempoTotal > 0)
                            _InfoItem(
                              icono: Icons.timer,
                              valor: '$tiempoTotal',
                              label: 'min total',
                              color: Colors.orange,
                            ),
                          if (porciones != null && porciones.toString().isNotEmpty)
                            _InfoItem(
                              icono: Icons.people,
                              valor: '$porciones',
                              label: 'porciones',
                              color: Colors.blue,
                            ),
                          if (dificultad.isNotEmpty)
                            _InfoItem(
                              icono: Icons.signal_cellular_alt,
                              valor: _formatearDificultad(dificultad),
                              label: 'dificultad',
                              color: _getColorDificultad(dificultad),
                            ),
                          if (calorias != null && calorias.toString().isNotEmpty)
                            _InfoItem(
                              icono: Icons.local_fire_department,
                              valor: '$calorias',
                              label: 'kcal',
                              color: Colors.red,
                            ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Tiempos detallados
                  if ((tiempoPreparacion != null && tiempoPreparacion.toString().isNotEmpty) ||
                      (tiempoCoccion != null && tiempoCoccion.toString().isNotEmpty))
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            if (tiempoPreparacion != null &&
                                tiempoPreparacion.toString().isNotEmpty)
                              Expanded(
                                child: Row(
                                  children: [
                                    Icon(Icons.content_cut,
                                        color: Colors.grey.shade600),
                                    const SizedBox(width: 8),
                                    Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Preparación',
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: Colors.grey.shade600,
                                          ),
                                        ),
                                        Text(
                                          '$tiempoPreparacion min',
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            if (tiempoCoccion != null &&
                                tiempoCoccion.toString().isNotEmpty)
                              Expanded(
                                child: Row(
                                  children: [
                                    Icon(Icons.whatshot,
                                        color: Colors.grey.shade600),
                                    const SizedBox(width: 8),
                                    Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Cocción',
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: Colors.grey.shade600,
                                          ),
                                        ),
                                        Text(
                                          '$tiempoCoccion min',
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  const SizedBox(height: 24),

                  // Ingredientes
                  if (ingredientes.isNotEmpty) ...[
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Ingredientes',
                          style: TextStyle(
                              fontWeight: FontWeight.bold, fontSize: 18),
                        ),
                        // Selector de porciones
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.remove_circle_outline),
                              onPressed: _porcionesSeleccionadas > 1
                                  ? () => setState(
                                      () => _porcionesSeleccionadas--)
                                  : null,
                            ),
                            Text(
                              '$_porcionesSeleccionadas',
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            IconButton(
                              icon: const Icon(Icons.add_circle_outline),
                              onPressed: () =>
                                  setState(() => _porcionesSeleccionadas++),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Card(
                      child: ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: ingredientes.length,
                        separatorBuilder: (_, __) =>
                            Divider(height: 1, color: Colors.grey.shade200),
                        itemBuilder: (context, index) {
                          final ing =
                              ingredientes[index] as Map<String, dynamic>;
                          final cantidad = ing['cantidad'] ?? '';
                          final unidad = ing['unidad'] ?? '';
                          final nombre = ing['nombre'] ?? '';

                          // Calcular cantidad ajustada
                          String cantidadMostrar = cantidad.toString();
                          if (cantidad.toString().isNotEmpty &&
                              porciones != null) {
                            final cantidadOriginal =
                                double.tryParse(cantidad.toString()) ?? 0;
                            final porcionesOriginal =
                                int.tryParse(porciones.toString()) ?? 1;
                            final cantidadAjustada = cantidadOriginal *
                                _porcionesSeleccionadas /
                                porcionesOriginal;
                            cantidadMostrar = cantidadAjustada % 1 == 0
                                ? cantidadAjustada.toInt().toString()
                                : cantidadAjustada.toStringAsFixed(1);
                          }

                          return ListTile(
                            leading: CircleAvatar(
                              backgroundColor: Colors.orange.shade100,
                              radius: 16,
                              child: Icon(Icons.check,
                                  size: 16, color: Colors.orange.shade700),
                            ),
                            title: Text(nombre),
                            trailing: Text(
                              '$cantidadMostrar $unidad',
                              style: TextStyle(
                                color: Colors.grey.shade700,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Pasos
                  if (pasos.isNotEmpty) ...[
                    const Text(
                      'Preparación',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                    ),
                    const SizedBox(height: 12),
                    ...pasos.asMap().entries.map((entry) {
                      final index = entry.key;
                      final paso = entry.value.toString();
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CircleAvatar(
                                backgroundColor: Colors.orange.shade700,
                                radius: 14,
                                child: Text(
                                  '${index + 1}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  paso,
                                  style: const TextStyle(height: 1.5),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                    const SizedBox(height: 24),
                  ],

                  // Productos vinculados
                  if (productosVinculados.isNotEmpty) ...[
                    const Text(
                      'Productos utilizados',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 150,
                      child: ListView.builder(
                        scrollDirection: Axis.horizontal,
                        itemCount: productosVinculados.length,
                        itemBuilder: (context, index) {
                          final producto =
                              productosVinculados[index] as Map<String, dynamic>;
                          return _ProductoMiniCard(producto: producto);
                        },
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Autor
                  if (autor != null) ...[
                    Card(
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundColor: Colors.grey.shade200,
                          child:
                              Icon(Icons.person, color: Colors.grey.shade600),
                        ),
                        title: const Text('Receta de'),
                        subtitle: Text(
                          autor['nombre'] ?? 'Anónimo',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatearDificultad(String dificultad) {
    switch (dificultad) {
      case 'facil':
        return 'Fácil';
      case 'media':
        return 'Media';
      case 'dificil':
        return 'Difícil';
      default:
        return dificultad;
    }
  }

  Color _getColorDificultad(String dificultad) {
    switch (dificultad) {
      case 'facil':
        return Colors.green;
      case 'media':
        return Colors.orange;
      case 'dificil':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

// =============================================================================
// ITEM DE INFO
// =============================================================================

class _InfoItem extends StatelessWidget {
  final IconData icono;
  final String valor;
  final String label;
  final Color color;

  const _InfoItem({
    required this.icono,
    required this.valor,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icono, color: color, size: 28),
        const SizedBox(height: 4),
        Text(
          valor,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }
}

// =============================================================================
// TARJETA MINI DE PRODUCTO
// =============================================================================

class _ProductoMiniCard extends StatelessWidget {
  final Map<String, dynamic> producto;

  const _ProductoMiniCard({required this.producto});

  @override
  Widget build(BuildContext context) {
    final nombre = producto['nombre'] ?? '';
    final imagen = producto['imagen'];
    final precioHtml = producto['precio_html'] ?? '';

    return Card(
      margin: const EdgeInsets.only(right: 12),
      child: SizedBox(
        width: 120,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen
            Expanded(
              child: ClipRRect(
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(12)),
                child: imagen != null
                    ? Image.network(
                        imagen,
                        fit: BoxFit.cover,
                        width: double.infinity,
                        errorBuilder: (_, __, ___) => Container(
                          color: Colors.grey.shade200,
                          child: const Icon(Icons.shopping_bag),
                        ),
                      )
                    : Container(
                        color: Colors.grey.shade200,
                        child: const Icon(Icons.shopping_bag),
                      ),
              ),
            ),
            // Info
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    nombre,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (precioHtml.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      precioHtml.replaceAll(RegExp(r'<[^>]*>'), ''),
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.green.shade700,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// DELEGADO DE BÚSQUEDA
// =============================================================================

class _RecetaBusquedaDelegate extends SearchDelegate<String> {
  final Function(String) onBuscar;

  _RecetaBusquedaDelegate({required this.onBuscar});

  @override
  String get searchFieldLabel => 'Buscar recetas...';

  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      IconButton(
        icon: const Icon(Icons.clear),
        onPressed: () => query = '',
      ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () => close(context, ''),
    );
  }

  @override
  Widget buildResults(BuildContext context) {
    if (query.isNotEmpty) {
      onBuscar(query);
      close(context, query);
    }
    return const SizedBox.shrink();
  }

  @override
  Widget buildSuggestions(BuildContext context) {
    final sugerencias = [
      'Pasta',
      'Ensalada',
      'Pollo',
      'Vegetariano',
      'Postre',
      'Sopa',
    ];

    return ListView.builder(
      itemCount: sugerencias.length,
      itemBuilder: (context, index) {
        return ListTile(
          leading: const Icon(Icons.search),
          title: Text(sugerencias[index]),
          onTap: () {
            query = sugerencias[index];
            showResults(context);
          },
        );
      },
    );
  }
}
