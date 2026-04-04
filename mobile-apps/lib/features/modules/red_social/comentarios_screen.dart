import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_draft_store.dart';
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/widgets/flavor_initials_avatar.dart';
import '../../../core/widgets/flavor_search_field.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

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
  String _filtro = '';

  String get _draftKey =>
      'red_social_comment_draft_${widget.publicacion['id']?.toString() ?? 'unknown'}';

  @override
  void initState() {
    super.initState();
    _loadDraft();
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
      final response =
          await apiClient.get('/red-social/publicaciones/$publicacionId/comentarios');

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

      if (!mounted) return;
      setState(() => _enviando = false);
      await FlavorMutation.runApiResponse(
        context,
        request: () => Future.value(response),
        successMessage: 'Comentario enviado',
        fallbackErrorMessage: 'Error al enviar',
        onSuccess: () async {
          _comentarioController.clear();
          await _clearDraft();
          await _cargarComentarios();
          widget.onComentarioAgregado();
        },
      );
    } catch (e) {
      if (mounted) {
        setState(() => _enviando = false);
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  Future<void> _loadDraft() async {
    final draft = await FlavorDraftStore.load(_draftKey);
    if (!mounted || draft.isEmpty) return;
    setState(() {
      _comentarioController.text = draft;
    });
  }

  Future<void> _saveDraft(String value) async {
    await FlavorDraftStore.save(_draftKey, value);
  }

  Future<void> _clearDraft() async {
    await FlavorDraftStore.clear(_draftKey);
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
    final comentariosFiltrados = _comentarios.where((comentario) {
      if (_filtro.trim().isEmpty) return true;
      final item = comentario as Map<String, dynamic>;
      final haystack = [
        item['autor'],
        item['author'],
        item['usuario'],
        item['contenido'],
        item['content'],
        item['texto'],
      ].whereType<Object>().map((e) => e.toString().toLowerCase()).join(' ');
      return haystack.contains(_filtro.toLowerCase());
    }).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Comentarios'),
      ),
      body: Column(
        children: [
          Card(
            margin: const EdgeInsets.all(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      FlavorInitialsAvatar(
                        name: autor.toString(),
                        radius: 16,
                        backgroundColor: Colors.blue.shade100,
                        textStyle: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        autor.toString(),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
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
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: FlavorSearchField(
              hintText: 'Buscar en comentarios',
              value: _filtro,
              onChanged: (value) {
                setState(() {
                  _filtro = value.trim();
                });
              },
            ),
          ),
          Expanded(
            child: _cargando
                ? const FlavorLoadingState()
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
                    : comentariosFiltrados.isEmpty
                        ? const Center(child: Text('No hay comentarios'))
                        : RefreshIndicator(
                            onRefresh: _cargarComentarios,
                            child: ListView.builder(
                              padding:
                                  const EdgeInsets.symmetric(horizontal: 16),
                              itemCount: comentariosFiltrados.length,
                              itemBuilder: (context, index) {
                                final comentario =
                                    comentariosFiltrados[index] as Map<String, dynamic>;
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
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      FlavorInitialsAvatar(
                                        name: autorComentario.toString(),
                                        radius: 16,
                                        backgroundColor: Colors.grey.shade200,
                                        textStyle:
                                            const TextStyle(fontSize: 12),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Container(
                                          padding: const EdgeInsets.all(14),
                                          decoration: BoxDecoration(
                                            color: Theme.of(context)
                                                .colorScheme
                                                .surfaceContainerHighest,
                                            borderRadius:
                                                BorderRadius.circular(16),
                                          ),
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                children: [
                                                  Text(
                                                    autorComentario.toString(),
                                                    style: const TextStyle(
                                                      fontWeight:
                                                          FontWeight.bold,
                                                      fontSize: 13,
                                                    ),
                                                  ),
                                                  if (fechaComentario
                                                      .toString()
                                                      .isNotEmpty) ...[
                                                    const SizedBox(width: 8),
                                                    Text(
                                                      fechaComentario
                                                          .toString(),
                                                      style: TextStyle(
                                                        fontSize: 11,
                                                        color: Colors
                                                            .grey.shade600,
                                                      ),
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
                      decoration: InputDecoration(
                        hintText: 'Escribe un comentario...',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(24),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                        suffixText:
                            '${_comentarioController.text.trim().length}/500',
                      ),
                      maxLength: 500,
                      textInputAction: TextInputAction.send,
                      onChanged: (value) {
                        setState(() {});
                        _saveDraft(value);
                      },
                      onSubmitted: (_) => _enviarComentario(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _enviando ? null : _enviarComentario,
                    icon: _enviando
                        ? const FlavorInlineSpinner()
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
