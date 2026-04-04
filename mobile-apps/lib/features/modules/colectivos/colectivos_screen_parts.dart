part of 'colectivos_screen.dart';

class ColectivoDetalleScreen extends ConsumerStatefulWidget {
  final int colectivoId;
  final String nombreColectivo;

  const ColectivoDetalleScreen({
    super.key,
    required this.colectivoId,
    required this.nombreColectivo,
  });

  @override
  ConsumerState<ColectivoDetalleScreen> createState() => _ColectivoDetalleScreenState();
}

class _ColectivoDetalleScreenState extends ConsumerState<ColectivoDetalleScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _detalleFuture;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  void _cargarDetalle() {
    final api = ref.read(apiClientProvider);
    _detalleFuture = api.get('/colectivos/${widget.colectivoId}');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.nombreColectivo),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _detalleFuture,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'Error al cargar detalles',
              onRetry: () => setState(() => _cargarDetalle()),
              icon: Icons.error_outline,
            );
          }

          final colectivo = res.data!['colectivo'] as Map<String, dynamic>? ?? res.data!;
          final nombre = colectivo['nombre']?.toString() ?? widget.nombreColectivo;
          final descripcion = colectivo['descripcion']?.toString() ?? '';
          final categoria = colectivo['categoria']?.toString() ?? '';
          final ubicacion = colectivo['ubicacion']?.toString() ?? '';
          final imagen = colectivo['imagen']?.toString() ?? '';
          final numeroMiembros = colectivo['num_miembros'] ?? colectivo['miembros'] ?? 0;
          final fechaCreacion = colectivo['fecha_creacion']?.toString() ?? '';
          final contacto = colectivo['contacto']?.toString() ?? '';
          final miembros = (colectivo['lista_miembros'] as List<dynamic>?) ?? [];

          return RefreshIndicator(
            onRefresh: () async {
              setState(() => _cargarDetalle());
            },
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Imagen
                if (imagen.isNotEmpty)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(
                      imagen,
                      height: 200,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        height: 150,
                        color: Colors.purple.shade50,
                        child: const Icon(Icons.groups, size: 64, color: Colors.purple),
                      ),
                    ),
                  )
                else
                  Container(
                    height: 150,
                    decoration: BoxDecoration(
                      color: Colors.purple.shade50,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Center(
                      child: Icon(Icons.groups, size: 64, color: Colors.purple),
                    ),
                  ),
                const SizedBox(height: 16),

                // Nombre y categoría
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        nombre,
                        style: theme.textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    if (categoria.isNotEmpty)
                      Chip(
                        label: Text(categoria),
                        backgroundColor: theme.colorScheme.primaryContainer,
                      ),
                  ],
                ),
                const SizedBox(height: 8),

                // Info rápida
                Wrap(
                  spacing: 16,
                  children: [
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.people, size: 18),
                        const SizedBox(width: 4),
                        Text('$numeroMiembros miembros'),
                      ],
                    ),
                    if (ubicacion.isNotEmpty)
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.location_on, size: 18),
                          const SizedBox(width: 4),
                          Text(ubicacion),
                        ],
                      ),
                  ],
                ),
                const SizedBox(height: 16),

                // Descripción
                if (descripcion.isNotEmpty) ...[
                  Text(
                    'Descripción',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(descripcion),
                  const SizedBox(height: 16),
                ],

                // Info adicional
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Información',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const Divider(),
                        if (fechaCreacion.isNotEmpty)
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.calendar_today),
                            title: const Text('Fecha de creación'),
                            subtitle: Text(_formatDate(fechaCreacion)),
                          ),
                        if (contacto.isNotEmpty)
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.email),
                            title: const Text('Contacto'),
                            subtitle: Text(contacto),
                          ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Miembros
                if (miembros.isNotEmpty) ...[
                  Text(
                    'Miembros (${miembros.length})',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Card(
                    child: ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: miembros.length > 5 ? 5 : miembros.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final miembro = miembros[index] as Map<String, dynamic>;
                        final nombreMiembro = miembro['nombre'] ?? miembro['display_name'] ?? 'Usuario';
                        final rol = miembro['rol'] ?? miembro['role'] ?? '';
                        final avatar = miembro['avatar']?.toString();

                        return ListTile(
                          leading: CircleAvatar(
                            backgroundImage: avatar != null ? NetworkImage(avatar) : null,
                            child: avatar == null ? Text(nombreMiembro[0].toUpperCase()) : null,
                          ),
                          title: Text(nombreMiembro),
                          subtitle: rol.isNotEmpty ? Text(rol) : null,
                        );
                      },
                    ),
                  ),
                  if (miembros.length > 5)
                    TextButton(
                      onPressed: () => _verTodosMiembros(context, miembros, nombre),
                      child: Text('Ver todos (${miembros.length})'),
                    ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  void _verTodosMiembros(BuildContext context, List<dynamic> miembros, String nombreColectivo) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Icon(Icons.people, color: Colors.purple),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Miembros del colectivo',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          nombreColectivo,
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView.separated(
                controller: scrollController,
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: miembros.length,
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final miembro = miembros[index] as Map<String, dynamic>;
                  final nombreMiembro = miembro['nombre'] ?? miembro['display_name'] ?? 'Usuario';
                  final rol = miembro['rol'] ?? miembro['role'] ?? '';
                  final avatar = miembro['avatar']?.toString();
                  final fechaIngreso = miembro['fecha_ingreso'] ?? miembro['joined_at'] ?? '';

                  return ListTile(
                    leading: CircleAvatar(
                      backgroundColor: Colors.purple.shade100,
                      backgroundImage: avatar != null && avatar.isNotEmpty ? NetworkImage(avatar) : null,
                      child: avatar == null || avatar.isEmpty
                          ? Text(
                              nombreMiembro.isNotEmpty ? nombreMiembro[0].toUpperCase() : '?',
                              style: TextStyle(color: Colors.purple.shade700),
                            )
                          : null,
                    ),
                    title: Text(nombreMiembro),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (rol.isNotEmpty)
                          Text(
                            rol,
                            style: TextStyle(
                              color: rol.toLowerCase().contains('admin') || rol.toLowerCase().contains('coordinador')
                                  ? Colors.purple
                                  : Colors.grey.shade600,
                              fontWeight: rol.toLowerCase().contains('admin') || rol.toLowerCase().contains('coordinador')
                                  ? FontWeight.bold
                                  : FontWeight.normal,
                            ),
                          ),
                        if (fechaIngreso.toString().isNotEmpty)
                          Text(
                            'Desde: ${_formatDate(fechaIngreso.toString())}',
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                          ),
                      ],
                    ),
                    trailing: rol.toLowerCase().contains('admin') || rol.toLowerCase().contains('coordinador')
                        ? Icon(Icons.star, color: Colors.amber.shade600, size: 20)
                        : null,
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }
}

/// Pantalla para crear un nuevo colectivo
class CrearColectivoScreen extends ConsumerStatefulWidget {
  const CrearColectivoScreen({super.key});

  @override
  ConsumerState<CrearColectivoScreen> createState() => _CrearColectivoScreenState();
}

class _CrearColectivoScreenState extends ConsumerState<CrearColectivoScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _ubicacionController = TextEditingController();
  final _contactoController = TextEditingController();
  String _categoriaSeleccionada = '';
  bool _guardando = false;

  final List<String> _categorias = [
    'Cultural',
    'Deportivo',
    'Social',
    'Ambiental',
    'Educativo',
    'Vecinal',
    'Profesional',
    'Otro',
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Crear Colectivo'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _nombreController,
              decoration: const InputDecoration(
                labelText: 'Nombre del colectivo *',
                prefixIcon: Icon(Icons.groups),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El nombre es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            DropdownButtonFormField<String>(
              value: _categoriaSeleccionada.isEmpty ? null : _categoriaSeleccionada,
              decoration: const InputDecoration(
                labelText: 'Categoría *',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _categorias.map((cat) => DropdownMenuItem(
                value: cat,
                child: Text(cat),
              )).toList(),
              onChanged: (value) {
                setState(() => _categoriaSeleccionada = value ?? '');
              },
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Selecciona una categoría';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                prefixIcon: Icon(Icons.description),
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _ubicacionController,
              decoration: const InputDecoration(
                labelText: 'Ubicación',
                prefixIcon: Icon(Icons.location_on),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _contactoController,
              decoration: const InputDecoration(
                labelText: 'Email de contacto',
                prefixIcon: Icon(Icons.email),
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 24),

            FilledButton.icon(
              onPressed: _guardando ? null : _crearColectivo,
              icon: _guardando
                  ? const FlavorInlineSpinner()
                  : const Icon(Icons.check),
              label: Text(_guardando ? 'Creando...' : 'Crear Colectivo'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _crearColectivo() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _guardando = true);

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/colectivos',
        data: {
          'nombre': _nombreController.text.trim(),
          'categoria': _categoriaSeleccionada,
          'descripcion': _descripcionController.text.trim(),
          'ubicacion': _ubicacionController.text.trim(),
          'contacto': _contactoController.text.trim(),
        },
      );

      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Colectivo creado correctamente');
          Navigator.of(context).pop(true);
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al crear colectivo');
        }
      }
    } finally {
      if (mounted) {
        setState(() => _guardando = false);
      }
    }
  }

  @override
  void dispose() {
    _nombreController.dispose();
    _descripcionController.dispose();
    _ubicacionController.dispose();
    _contactoController.dispose();
    super.dispose();
  }
}
