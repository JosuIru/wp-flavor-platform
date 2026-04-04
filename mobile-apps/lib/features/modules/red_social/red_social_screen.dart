import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_share_helper.dart';
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/widgets/flavor_confirm_dialog.dart';
import '../../../core/widgets/flavor_search_field.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import 'comentarios_screen.dart';
import 'crear_publicacion_screen.dart';
import 'widgets/red_social_feed_list.dart';
import 'widgets/red_social_feed_states.dart';
import 'widgets/red_social_post_card.dart';

class RedSocialScreen extends ConsumerStatefulWidget {
  const RedSocialScreen({super.key});

  @override
  ConsumerState<RedSocialScreen> createState() => _RedSocialScreenState();
}

class _RedSocialScreenState extends ConsumerState<RedSocialScreen> {
  List<dynamic> _publicaciones = [];
  bool _loading = true;
  String? _error;
  String _searchQuery = '';

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
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: FlavorSearchField(
              hintText: 'Buscar en publicaciones',
              value: _searchQuery,
              onChanged: (value) {
                setState(() {
                  _searchQuery = value.trim();
                });
              },
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _crearPublicacion,
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const RedSocialLoadingState();
    }

    if (_error != null) {
      return RedSocialErrorState(
        message: _error!,
        onRetry: _loadData,
      );
    }

    if (_publicaciones.isEmpty) {
      return RedSocialEmptyState(onCreate: _crearPublicacion);
    }

    return RedSocialFeedList(
      publicaciones: _filteredPublicaciones,
      onRefresh: _loadData,
      itemBuilder: _buildPublicacionCard,
    );
  }

  List<dynamic> get _filteredPublicaciones {
    if (_searchQuery.trim().isEmpty) {
      return _publicaciones;
    }

    final query = _searchQuery.toLowerCase();
    return _publicaciones.where((item) {
      final post = item as Map<String, dynamic>;
      final haystack = [
        post['autor'],
        post['author'],
        post['usuario'],
        post['contenido'],
        post['content'],
        post['texto'],
      ].whereType<Object>().map((e) => e.toString().toLowerCase()).join(' ');

      return haystack.contains(query);
    }).toList();
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
    final meGusta = publicacionMap['me_gusta_user'] == true;
    return RedSocialPostCard(
      item: publicacionMap,
      onLikeToggle: () => _toggleLike(publicacionId, index, !meGusta),
      onComment: () => _verComentarios(publicacionMap),
      onShare: () => _compartir(publicacionMap),
      onEdit: () => _editarPublicacion(publicacionMap),
      onDelete: () => _eliminarPublicacion(publicacionId),
      onReport: () => _reportarPublicacion(publicacionId),
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
        FlavorSnackbar.showError(context, 'Error: $e');
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
    final publicacionId = publicacion['id']?.toString() ?? '';
    final contenido = (publicacion['contenido'] ??
            publicacion['content'] ??
            publicacion['texto'] ??
            '')
        .toString()
        .trim();
    final enlace = 'https://ejemplo.com/publicacion/$publicacionId';
    final textoCompartir = contenido.isEmpty ? enlace : '$contenido\n\n$enlace';

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
                  if (context.mounted) {
                    Navigator.pop(context);
                    await FlavorShareHelper.copyText(
                      context,
                      enlace,
                      successMessage: 'Enlace copiado al portapapeles',
                    );
                  }
                },
              ),
              ListTile(
                leading: const Icon(Icons.share),
                title: const Text('Compartir externamente'),
                onTap: () async {
                  Navigator.pop(context);
                  await FlavorShareHelper.shareText(
                    textoCompartir,
                    subject: 'Publicacion de red social',
                  );
                },
              ),
              ListTile(
                leading: const Icon(Icons.comment_outlined),
                title: const Text('Abrir comentarios'),
                onTap: () {
                  Navigator.pop(context);
                  _verComentarios(publicacion);
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
    final confirmar = await FlavorConfirmDialog.show(
      context,
      title: 'Eliminar publicacion',
      message: '¿Estas seguro de que quieres eliminar esta publicacion?',
      confirmLabel: 'Eliminar',
      destructive: true,
    );

    if (confirmar != true) return;

    try {
      final apiClient = ref.read(apiClientProvider);
      final response =
          await apiClient.delete('/red-social/publicaciones/$publicacionId');

      if (!mounted) return;

      await FlavorMutation.runApiResponse(
        context,
        request: () => Future.value(response),
        successMessage: 'Publicacion eliminada',
        fallbackErrorMessage: 'Error al eliminar',
        onSuccess: _loadData,
      );
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
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

      if (!mounted) return;

      await FlavorMutation.runApiResponse(
        context,
        request: () => Future.value(response),
        successMessage: 'Publicacion reportada',
        fallbackErrorMessage: 'Error al reportar',
      );
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }
}
