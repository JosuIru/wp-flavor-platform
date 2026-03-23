import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class RedSocialScreen extends ConsumerStatefulWidget {
  const RedSocialScreen({super.key});

  @override
  ConsumerState<RedSocialScreen> createState() => _RedSocialScreenState();
}

class _RedSocialScreenState extends ConsumerState<RedSocialScreen> {
  List<dynamic> _publicaciones = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/red-social/feed');
      if (response.success && response.data != null) {
        setState(() {
          _publicaciones = response.data!['publicaciones'] ??
              response.data!['posts'] ??
              response.data!['items'] ??
              response.data!['data'] ??
              [];
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar el feed';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Red Social'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.public, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _publicaciones.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.public,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay publicaciones disponibles'),
                          const SizedBox(height: 16),
                          FilledButton.icon(
                            onPressed: _crearPublicacion,
                            icon: const Icon(Icons.add),
                            label: const Text('Crear publicacion'),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadData,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _publicaciones.length,
                        itemBuilder: (context, index) =>
                            _buildPublicacionCard(_publicaciones[index], index),
                      ),
                    ),
      floatingActionButton: FloatingActionButton(
        onPressed: _crearPublicacion,
        child: const Icon(Icons.add),
      ),
    );
  }

  void _crearPublicacion() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => CrearPublicacionScreen(onPublicacionCreada: _loadData),
      ),
    );
  }

  Widget _buildPublicacionCard(dynamic item, int index) {
    final publicacionMap = item as Map<String, dynamic>;
    final publicacionId = publicacionMap['id']?.toString() ?? '';
    final autor = publicacionMap['autor'] ??
        publicacionMap['author'] ??
        publicacionMap['usuario'] ??
        'Usuario';
    final autorId = publicacionMap['autor_id'] ?? publicacionMap['user_id'];
    final contenido = publicacionMap['contenido'] ??
        publicacionMap['content'] ??
        publicacionMap['texto'] ??
        '';
    final fecha = publicacionMap['fecha'] ??
        publicacionMap['date'] ??
        publicacionMap['created_at'] ??
        '';
    final likes = publicacionMap['likes'] ?? publicacionMap['me_gusta'] ?? 0;
    final comentarios =
        publicacionMap['comentarios'] ?? publicacionMap['comments'] ?? 0;
    final imagen = publicacionMap['imagen'] ?? publicacionMap['image'] ?? '';
    final meGusta = publicacionMap['me_gusta_user'] == true;
    final esMio = publicacionMap['es_mio'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Cabecera
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.blue.shade100,
                  child: Text(
                    autor.toString().isNotEmpty ? autor.toString()[0].toUpperCase() : 'U',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        autor.toString(),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      if (fecha.toString().isNotEmpty)
                        Text(
                          fecha.toString(),
                          style:
                              TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    if (value == 'editar') {
                      _editarPublicacion(publicacionMap);
                    } else if (value == 'eliminar') {
                      _eliminarPublicacion(publicacionId);
                    } else if (value == 'reportar') {
                      _reportarPublicacion(publicacionId);
                    }
                  },
                  itemBuilder: (context) => [
                    if (esMio) ...[
                      const PopupMenuItem(value: 'editar', child: Text('Editar')),
                      const PopupMenuItem(
                        value: 'eliminar',
                        child: Text('Eliminar', style: TextStyle(color: Colors.red)),
                      ),
                    ] else
                      const PopupMenuItem(value: 'reportar', child: Text('Reportar')),
                  ],
                ),
              ],
            ),
          ),

          // Contenido
          if (contenido.toString().isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(contenido.toString()),
            ),

          // Imagen
          if (imagen.toString().isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Image.network(
                imagen.toString(),
                width: double.infinity,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => const SizedBox.shrink(),
              ),
            ),

          const SizedBox(height: 8),

          // Estadísticas
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                if (likes > 0) ...[
                  Icon(Icons.thumb_up, size: 14, color: Colors.blue.shade600),
                  const SizedBox(width: 4),
                  Text('$likes', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                ],
                if (likes > 0 && comentarios > 0)
                  const SizedBox(width: 16),
                if (comentarios > 0) ...[
                  Text(
                    '$comentarios comentarios',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                  ),
                ],
              ],
            ),
          ),

          const Divider(height: 16),

          // Acciones
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Row(
              children: [
                Expanded(
                  child: TextButton.icon(
                    onPressed: () => _toggleLike(publicacionId, index, !meGusta),
                    icon: Icon(
                      meGusta ? Icons.thumb_up : Icons.thumb_up_outlined,
                      size: 20,
                      color: meGusta ? Colors.blue : null,
                    ),
                    label: Text(
                      'Me gusta',
                      style: TextStyle(color: meGusta ? Colors.blue : null),
                    ),
                  ),
                ),
                Expanded(
                  child: TextButton.icon(
                    onPressed: () => _verComentarios(publicacionMap),
                    icon: const Icon(Icons.comment_outlined, size: 20),
                    label: const Text('Comentar'),
                  ),
                ),
                Expanded(
                  child: TextButton.icon(
                    onPressed: () => _compartir(publicacionMap),
                    icon: const Icon(Icons.share_outlined, size: 20),
                    label: const Text('Compartir'),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 4),
        ],
      ),
    );
  }

  Future<void> _toggleLike(String publicacionId, int index, bool like) async {
    try {
      final apiClient = ref.read(apiClientProvider);
      final endpoint = like
          ? '/red-social/publicaciones/$publicacionId/like'
          : '/red-social/publicaciones/$publicacionId/unlike';

      final response = await apiClient.post(endpoint, data: {});

      if (response.success) {
        setState(() {
          final publicacion = _publicaciones[index] as Map<String, dynamic>;
          final currentLikes = publicacion['likes'] ?? publicacion['me_gusta'] ?? 0;
          _publicaciones[index] = {
            ...publicacion,
            'me_gusta_user': like,
            'likes': like ? currentLikes + 1 : (currentLikes > 0 ? currentLikes - 1 : 0),
          };
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    }
  }

  void _verComentarios(Map<String, dynamic> publicacion) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ComentariosScreen(
          publicacion: publicacion,
          onComentarioAgregado: _loadData,
        ),
      ),
    );
  }

  void _compartir(Map<String, dynamic> publicacion) {
    final contenido = publicacion['contenido'] ??
        publicacion['content'] ??
        publicacion['texto'] ??
        '';

    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Compartir publicacion',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: const Icon(Icons.copy),
                title: const Text('Copiar enlace'),
                onTap: () async {
                  final publicacionId = publicacion['id']?.toString() ?? '';
                  final enlace = 'https://ejemplo.com/publicacion/$publicacionId';
                  await Clipboard.setData(ClipboardData(text: enlace));
                  if (context.mounted) {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Enlace copiado al portapapeles')),
                    );
                  }
                },
              ),
              ListTile(
                leading: const Icon(Icons.share),
                title: const Text('Compartir externamente'),
                onTap: () {
                  // En una implementación real, usarías share_plus
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Compartiendo...')),
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _editarPublicacion(Map<String, dynamic> publicacion) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => CrearPublicacionScreen(
          publicacionExistente: publicacion,
          onPublicacionCreada: _loadData,
        ),
      ),
    );
  }

  Future<void> _eliminarPublicacion(String publicacionId) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar publicacion'),
        content: const Text('¿Estas seguro de que quieres eliminar esta publicacion?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.delete('/red-social/publicaciones/$publicacionId');

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Publicacion eliminada'), backgroundColor: Colors.green),
          );
          _loadData();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(response.error ?? 'Error al eliminar'), backgroundColor: Colors.red),
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

  Future<void> _reportarPublicacion(String publicacionId) async {
    final motivo = await showDialog<String>(
      context: context,
      builder: (context) {
        String motivoSeleccionado = 'spam';
        return StatefulBuilder(
          builder: (context, setState) => AlertDialog(
            title: const Text('Reportar publicacion'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('Selecciona el motivo del reporte:'),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: motivoSeleccionado,
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'spam', child: Text('Spam')),
                    DropdownMenuItem(value: 'ofensivo', child: Text('Contenido ofensivo')),
                    DropdownMenuItem(value: 'falso', child: Text('Informacion falsa')),
                    DropdownMenuItem(value: 'otro', child: Text('Otro')),
                  ],
                  onChanged: (value) {
                    if (value != null) {
                      setState(() => motivoSeleccionado = value);
                    }
                  },
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Cancelar'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, motivoSeleccionado),
                child: const Text('Reportar'),
              ),
            ],
          ),
        );
      },
    );

    if (motivo == null) return;

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/red-social/publicaciones/$publicacionId/reportar',
        data: {'motivo': motivo},
      );

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Publicacion reportada'), backgroundColor: Colors.green),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(response.error ?? 'Error al reportar'), backgroundColor: Colors.red),
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

/// Pantalla para crear/editar publicacion
class CrearPublicacionScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? publicacionExistente;
  final VoidCallback onPublicacionCreada;

  const CrearPublicacionScreen({
    super.key,
    this.publicacionExistente,
    required this.onPublicacionCreada,
  });

  @override
  ConsumerState<CrearPublicacionScreen> createState() => _CrearPublicacionScreenState();
}

class _CrearPublicacionScreenState extends ConsumerState<CrearPublicacionScreen> {
  final _contenidoController = TextEditingController();
  bool _guardando = false;

  bool get _esEdicion => widget.publicacionExistente != null;

  @override
  void initState() {
    super.initState();
    if (widget.publicacionExistente != null) {
      _contenidoController.text = widget.publicacionExistente!['contenido'] ??
          widget.publicacionExistente!['content'] ??
          widget.publicacionExistente!['texto'] ??
          '';
    }
  }

  @override
  void dispose() {
    _contenidoController.dispose();
    super.dispose();
  }

  Future<void> _guardarPublicacion() async {
    final contenido = _contenidoController.text.trim();
    if (contenido.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Escribe algo para publicar')),
      );
      return;
    }

    setState(() => _guardando = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      ApiResponse response;

      if (_esEdicion) {
        final publicacionId = widget.publicacionExistente!['id']?.toString() ?? '';
        response = await apiClient.put(
          '/red-social/publicaciones/$publicacionId',
          data: {'contenido': contenido},
        );
      } else {
        response = await apiClient.post(
          '/red-social/publicaciones',
          data: {'contenido': contenido},
        );
      }

      if (mounted) {
        setState(() => _guardando = false);

        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_esEdicion ? 'Publicacion actualizada' : 'Publicacion creada'),
              backgroundColor: Colors.green,
            ),
          );
          widget.onPublicacionCreada();
          Navigator.pop(context);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al guardar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _guardando = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_esEdicion ? 'Editar publicacion' : 'Nueva publicacion'),
        actions: [
          TextButton(
            onPressed: _guardando ? null : _guardarPublicacion,
            child: _guardando
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Publicar'),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Expanded(
              child: TextField(
                controller: _contenidoController,
                decoration: const InputDecoration(
                  hintText: '¿Que quieres compartir?',
                  border: InputBorder.none,
                ),
                maxLines: null,
                expands: true,
                textAlignVertical: TextAlignVertical.top,
                autofocus: true,
              ),
            ),
            const Divider(),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.photo_library),
                  onPressed: () {
                    // En una implementación real, se permitiría añadir imágenes
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Funcion de imagenes proximamente')),
                    );
                  },
                ),
                IconButton(
                  icon: const Icon(Icons.camera_alt),
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Funcion de camara proximamente')),
                    );
                  },
                ),
                IconButton(
                  icon: const Icon(Icons.location_on),
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Funcion de ubicacion proximamente')),
                    );
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Pantalla de comentarios
class ComentariosScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic> publicacion;
  final VoidCallback onComentarioAgregado;

  const ComentariosScreen({
    super.key,
    required this.publicacion,
    required this.onComentarioAgregado,
  });

  @override
  ConsumerState<ComentariosScreen> createState() => _ComentariosScreenState();
}

class _ComentariosScreenState extends ConsumerState<ComentariosScreen> {
  final _comentarioController = TextEditingController();
  List<dynamic> _comentarios = [];
  bool _cargando = true;
  bool _enviando = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarComentarios();
  }

  @override
  void dispose() {
    _comentarioController.dispose();
    super.dispose();
  }

  Future<void> _cargarComentarios() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final publicacionId = widget.publicacion['id']?.toString() ?? '';
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/red-social/publicaciones/$publicacionId/comentarios');

      if (response.success && response.data != null) {
        setState(() {
          _comentarios = response.data!['comentarios'] ??
              response.data!['comments'] ??
              response.data!['items'] ??
              [];
          _cargando = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar comentarios';
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

  Future<void> _enviarComentario() async {
    final texto = _comentarioController.text.trim();
    if (texto.isEmpty) return;

    setState(() => _enviando = true);

    try {
      final publicacionId = widget.publicacion['id']?.toString() ?? '';
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/red-social/publicaciones/$publicacionId/comentarios',
        data: {'contenido': texto},
      );

      if (mounted) {
        setState(() => _enviando = false);

        if (response.success) {
          _comentarioController.clear();
          _cargarComentarios();
          widget.onComentarioAgregado();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(response.error ?? 'Error al enviar'), backgroundColor: Colors.red),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _enviando = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final autor = widget.publicacion['autor'] ??
        widget.publicacion['author'] ??
        widget.publicacion['usuario'] ??
        'Usuario';
    final contenido = widget.publicacion['contenido'] ??
        widget.publicacion['content'] ??
        widget.publicacion['texto'] ??
        '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Comentarios'),
      ),
      body: Column(
        children: [
          // Publicacion original
          Card(
            margin: const EdgeInsets.all(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 16,
                        backgroundColor: Colors.blue.shade100,
                        child: Text(
                          autor.toString().isNotEmpty ? autor.toString()[0].toUpperCase() : 'U',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(autor.toString(), style: const TextStyle(fontWeight: FontWeight.bold)),
                    ],
                  ),
                  if (contenido.toString().isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(contenido.toString()),
                  ],
                ],
              ),
            ),
          ),

          // Lista de comentarios
          Expanded(
            child: _cargando
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_error!),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _cargarComentarios,
                              child: const Text('Reintentar'),
                            ),
                          ],
                        ),
                      )
                    : _comentarios.isEmpty
                        ? const Center(child: Text('No hay comentarios'))
                        : RefreshIndicator(
                            onRefresh: _cargarComentarios,
                            child: ListView.builder(
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                              itemCount: _comentarios.length,
                              itemBuilder: (context, index) {
                                final comentario = _comentarios[index] as Map<String, dynamic>;
                                final autorComentario = comentario['autor'] ??
                                    comentario['author'] ??
                                    comentario['usuario'] ??
                                    'Usuario';
                                final textoComentario = comentario['contenido'] ??
                                    comentario['content'] ??
                                    comentario['texto'] ??
                                    '';
                                final fechaComentario = comentario['fecha'] ??
                                    comentario['date'] ??
                                    '';

                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      CircleAvatar(
                                        radius: 16,
                                        backgroundColor: Colors.grey.shade200,
                                        child: Text(
                                          autorComentario.toString().isNotEmpty
                                              ? autorComentario.toString()[0].toUpperCase()
                                              : 'U',
                                          style: const TextStyle(fontSize: 12),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Container(
                                          padding: const EdgeInsets.all(12),
                                          decoration: BoxDecoration(
                                            color: Colors.grey.shade100,
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                children: [
                                                  Text(
                                                    autorComentario.toString(),
                                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                                  ),
                                                  if (fechaComentario.toString().isNotEmpty) ...[
                                                    const SizedBox(width: 8),
                                                    Text(
                                                      fechaComentario.toString(),
                                                      style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                                                    ),
                                                  ],
                                                ],
                                              ),
                                              const SizedBox(height: 4),
                                              Text(textoComentario.toString()),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
          ),

          // Campo de comentario
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 4,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _comentarioController,
                      decoration: const InputDecoration(
                        hintText: 'Escribe un comentario...',
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      ),
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _enviarComentario(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _enviando ? null : _enviarComentario,
                    icon: _enviando
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
