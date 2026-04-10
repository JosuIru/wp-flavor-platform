part of 'comunidades_screen.dart';

/// Modelo de datos de Comunidad
class _Comunidad {
  final int id;
  final String nombre;
  final String descripcion;
  final String? imagenUrl;
  final int cantidadMiembros;
  final bool esMiembro;
  final bool esAdmin;
  final String? ubicacion;
  final DateTime? fechaCreacion;
  final List<String> categorias;

  _Comunidad({
    required this.id,
    required this.nombre,
    required this.descripcion,
    this.imagenUrl,
    required this.cantidadMiembros,
    this.esMiembro = false,
    this.esAdmin = false,
    this.ubicacion,
    this.fechaCreacion,
    this.categorias = const [],
  });

  factory _Comunidad.fromJson(Map<String, dynamic> json) {
    return _Comunidad(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? json['titulo'] ?? json['title'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      imagenUrl: json['imagen'] ?? json['image'] ?? json['avatar'],
      cantidadMiembros: json['miembros'] ?? json['members'] ?? json['member_count'] ?? 0,
      esMiembro: json['es_miembro'] ?? json['is_member'] ?? false,
      esAdmin: json['es_admin'] ?? json['is_admin'] ?? false,
      ubicacion: json['ubicacion'] ?? json['location'],
      fechaCreacion: json['fecha_creacion'] != null
          ? DateTime.tryParse(json['fecha_creacion'].toString())
          : null,
      categorias: (json['categorias'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
    );
  }
}

/// Modelo de Miembro
class _Miembro {
  final int id;
  final String nombre;
  final String? avatarUrl;
  final String rol;
  final DateTime? fechaUnion;

  _Miembro({
    required this.id,
    required this.nombre,
    this.avatarUrl,
    this.rol = 'miembro',
    this.fechaUnion,
  });

  factory _Miembro.fromJson(Map<String, dynamic> json) {
    return _Miembro(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? json['name'] ?? '',
      avatarUrl: json['avatar'] ?? json['avatar_url'],
      rol: json['rol'] ?? json['role'] ?? 'miembro',
      fechaUnion: json['fecha_union'] != null
          ? DateTime.tryParse(json['fecha_union'].toString())
          : null,
    );
  }
}

/// Tarjeta de comunidad mejorada
class _ComunidadCard extends StatelessWidget {
  final _Comunidad comunidad;
  final VoidCallback onTap;

  const _ComunidadCard({
    required this.comunidad,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen de cabecera
            if (comunidad.imagenUrl != null)
              SizedBox(
                height: 120,
                width: double.infinity,
                child: Image.network(
                  comunidad.imagenUrl!,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    color: Theme.of(context).colorScheme.primaryContainer,
                    child: const Icon(Icons.groups, size: 48),
                  ),
                ),
              )
            else
              Container(
                height: 80,
                color: Theme.of(context).colorScheme.primaryContainer,
                child: Center(
                  child: Icon(
                    Icons.groups,
                    size: 40,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
              ),

            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Título y badge de miembro
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          comunidad.nombre,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                      ),
                      if (comunidad.esMiembro)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.green.shade100,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                comunidad.esAdmin ? Icons.star : Icons.check,
                                size: 14,
                                color: Colors.green.shade700,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                comunidad.esAdmin ? 'Admin' : 'Miembro',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.green.shade700,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),

                  const SizedBox(height: 8),

                  // Descripción
                  if (comunidad.descripcion.isNotEmpty)
                    Text(
                      comunidad.descripcion,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.grey.shade600,
                          ),
                    ),

                  const SizedBox(height: 12),

                  // Info footer
                  Row(
                    children: [
                      Icon(Icons.people_outline, size: 16, color: Colors.grey.shade600),
                      const SizedBox(width: 4),
                      Text(
                        '${comunidad.cantidadMiembros} miembros',
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                      ),
                      if (comunidad.ubicacion != null) ...[
                        const SizedBox(width: 16),
                        Icon(Icons.location_on_outlined,
                            size: 16, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            comunidad.ubicacion!,
                            style:
                                TextStyle(color: Colors.grey.shade600, fontSize: 13),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),

                  // Categorías
                  if (comunidad.categorias.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      runSpacing: 4,
                      children: comunidad.categorias.take(3).map((categoria) {
                        return Chip(
                          label: Text(categoria),
                          labelStyle: const TextStyle(fontSize: 11),
                          padding: EdgeInsets.zero,
                          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          visualDensity: VisualDensity.compact,
                        );
                      }).toList(),
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

/// Pantalla para crear comunidad
class _CrearComunidadScreen extends ConsumerStatefulWidget {
  const _CrearComunidadScreen();

  @override
  ConsumerState<_CrearComunidadScreen> createState() =>
      _CrearComunidadScreenState();
}

class _CrearComunidadScreenState extends ConsumerState<_CrearComunidadScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _ubicacionController = TextEditingController();
  bool _esPrivada = false;
  bool _guardando = false;
  String _categoriaSeleccionada = '';

  final List<String> _categorias = [
    'Vecinal',
    'Cultural',
    'Deportiva',
    'Ecológica',
    'Educativa',
    'Social',
    'Profesional',
    'Otra',
  ];

  @override
  void dispose() {
    _nombreController.dispose();
    _descripcionController.dispose();
    _ubicacionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nueva Comunidad'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Nombre
            TextFormField(
              controller: _nombreController,
              decoration: const InputDecoration(
                labelText: 'Nombre de la comunidad *',
                prefixIcon: Icon(Icons.groups),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'El nombre es obligatorio';
                }
                if (value.length < 3) {
                  return 'El nombre debe tener al menos 3 caracteres';
                }
                return null;
              },
            ),

            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                prefixIcon: Icon(Icons.description),
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
              maxLines: 4,
              maxLength: 500,
            ),

            const SizedBox(height: 16),

            // Ubicación
            TextFormField(
              controller: _ubicacionController,
              decoration: const InputDecoration(
                labelText: 'Ubicación (opcional)',
                prefixIcon: Icon(Icons.location_on),
                border: OutlineInputBorder(),
                hintText: 'Ej: Barrio, Ciudad',
              ),
            ),

            const SizedBox(height: 16),

            // Categoría
            DropdownButtonFormField<String>(
              value: _categoriaSeleccionada.isEmpty ? null : _categoriaSeleccionada,
              decoration: const InputDecoration(
                labelText: 'Categoría',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _categorias.map((categoria) {
                return DropdownMenuItem(
                  value: categoria,
                  child: Text(categoria),
                );
              }).toList(),
              onChanged: (value) {
                setState(() => _categoriaSeleccionada = value ?? '');
              },
            ),

            const SizedBox(height: 16),

            // Privacidad
            SwitchListTile(
              title: const Text('Comunidad privada'),
              subtitle: const Text(
                'Solo miembros aprobados pueden ver el contenido',
              ),
              value: _esPrivada,
              onChanged: (value) => setState(() => _esPrivada = value),
              secondary: Icon(
                _esPrivada ? Icons.lock : Icons.lock_open,
              ),
            ),

            const SizedBox(height: 24),

            // Botón crear
            FilledButton.icon(
              onPressed: _guardando ? null : _crearComunidad,
              icon: _guardando
                  ? const FlavorInlineSpinner(color: Colors.white)
                  : const Icon(Icons.add),
              label: Text(_guardando ? 'Creando...' : 'Crear Comunidad'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _crearComunidad() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _guardando = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/comunidades',
        data: {
          'nombre': _nombreController.text.trim(),
          'descripcion': _descripcionController.text.trim(),
          'ubicacion': _ubicacionController.text.trim(),
          'categoria': _categoriaSeleccionada,
          'privada': _esPrivada,
        },
      );

      if (!mounted) return;

      if (response.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Comunidad creada exitosamente'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context, true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al crear comunidad'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _guardando = false);
      }
    }
  }
}

/// Pantalla de miembros de comunidad
class _MiembrosComunidadScreen extends ConsumerStatefulWidget {
  final int comunidadId;
  final String nombreComunidad;
  final bool esAdmin;

  const _MiembrosComunidadScreen({
    required this.comunidadId,
    required this.nombreComunidad,
    this.esAdmin = false,
  });

  @override
  ConsumerState<_MiembrosComunidadScreen> createState() =>
      _MiembrosComunidadScreenState();
}

class _MiembrosComunidadScreenState
    extends ConsumerState<_MiembrosComunidadScreen> {
  List<_Miembro> _miembros = [];
  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarMiembros();
  }

  Future<void> _cargarMiembros() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/comunidades/${widget.comunidadId}/miembros',
      );

      if (response.success && response.data != null) {
        final items = response.data!['miembros'] ?? response.data!['data'] ?? [];
        setState(() {
          _miembros = (items as List)
              .map((json) => _Miembro.fromJson(json))
              .toList();
          _cargando = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar miembros';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Miembros de ${widget.nombreComunidad}'),
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(message: _error!, onRetry: _cargarMiembros)
              : _miembros.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.people_outline,
                      title: 'No hay miembros',
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarMiembros,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _miembros.length,
                        itemBuilder: (context, index) {
                          return _MiembroTile(
                            miembro: _miembros[index],
                            esAdmin: widget.esAdmin,
                            onRemove: widget.esAdmin
                                ? () => _confirmarEliminar(_miembros[index])
                                : null,
                          );
                        },
                      ),
                    ),
    );
  }

  Future<void> _confirmarEliminar(_Miembro miembro) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar miembro'),
        content: Text('¿Eliminar a ${miembro.nombre} de la comunidad?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmar == true) {
      await _eliminarMiembro(miembro);
    }
  }

  Future<void> _eliminarMiembro(_Miembro miembro) async {
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.delete(
        '/comunidades/${widget.comunidadId}/miembros/${miembro.id}',
      );

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Miembro eliminado')),
          );
          _cargarMiembros();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}

/// Tile de miembro
class _MiembroTile extends StatelessWidget {
  final _Miembro miembro;
  final bool esAdmin;
  final VoidCallback? onRemove;

  const _MiembroTile({
    required this.miembro,
    this.esAdmin = false,
    this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundImage:
              miembro.avatarUrl != null ? NetworkImage(miembro.avatarUrl!) : null,
          child: miembro.avatarUrl == null
              ? Text(miembro.nombre.isNotEmpty ? miembro.nombre[0].toUpperCase() : '?')
              : null,
        ),
        title: Text(miembro.nombre),
        subtitle: Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: miembro.rol == 'admin'
                    ? Colors.amber.shade100
                    : Colors.grey.shade200,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                miembro.rol == 'admin' ? 'Admin' : 'Miembro',
                style: TextStyle(
                  fontSize: 11,
                  color: miembro.rol == 'admin'
                      ? Colors.amber.shade800
                      : Colors.grey.shade700,
                ),
              ),
            ),
            if (miembro.fechaUnion != null) ...[
              const SizedBox(width: 8),
              Text(
                'Desde ${_formatearFecha(miembro.fechaUnion!)}',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
              ),
            ],
          ],
        ),
        trailing: esAdmin && onRemove != null && miembro.rol != 'admin'
            ? IconButton(
                icon: const Icon(Icons.remove_circle_outline, color: Colors.red),
                onPressed: onRemove,
              )
            : null,
      ),
    );
  }

  String _formatearFecha(DateTime fecha) {
    return '${fecha.day}/${fecha.month}/${fecha.year}';
  }
}

/// Pantalla de eventos de comunidad
class _EventosComunidadScreen extends ConsumerStatefulWidget {
  final int comunidadId;
  final String nombreComunidad;

  const _EventosComunidadScreen({
    required this.comunidadId,
    required this.nombreComunidad,
  });

  @override
  ConsumerState<_EventosComunidadScreen> createState() =>
      _EventosComunidadScreenState();
}

class _EventosComunidadScreenState
    extends ConsumerState<_EventosComunidadScreen> {
  List<Map<String, dynamic>> _eventos = [];
  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarEventos();
  }

  Future<void> _cargarEventos() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/comunidades/${widget.comunidadId}/eventos',
      );

      if (response.success && response.data != null) {
        final items = response.data!['eventos'] ?? response.data!['data'] ?? [];
        setState(() {
          _eventos = List<Map<String, dynamic>>.from(items);
          _cargando = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar eventos';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Eventos de ${widget.nombreComunidad}'),
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(message: _error!, onRetry: _cargarEventos)
              : _eventos.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.event_outlined,
                      title: 'No hay eventos',
                      message: 'Esta comunidad aún no tiene eventos programados',
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarEventos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _eventos.length,
                        itemBuilder: (context, index) {
                          final evento = _eventos[index];
                          return _EventoCard(evento: evento);
                        },
                      ),
                    ),
    );
  }
}

/// Tarjeta de evento
class _EventoCard extends StatelessWidget {
  final Map<String, dynamic> evento;

  const _EventoCard({required this.evento});

  @override
  Widget build(BuildContext context) {
    final titulo = evento['titulo'] ?? evento['title'] ?? 'Evento';
    final fecha = evento['fecha'] ?? evento['date'];
    final ubicacion = evento['ubicacion'] ?? evento['location'];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.primaryContainer,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(
            Icons.event,
            color: Theme.of(context).colorScheme.primary,
          ),
        ),
        title: Text(titulo),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (fecha != null)
              Row(
                children: [
                  const Icon(Icons.schedule, size: 14),
                  const SizedBox(width: 4),
                  Text(fecha.toString(), style: const TextStyle(fontSize: 12)),
                ],
              ),
            if (ubicacion != null)
              Row(
                children: [
                  const Icon(Icons.location_on, size: 14),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      ubicacion,
                      style: const TextStyle(fontSize: 12),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          // Navegar a detalle de evento
        },
      ),
    );
  }
}
