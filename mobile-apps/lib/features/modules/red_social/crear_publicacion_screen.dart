import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_draft_store.dart';
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class CrearPublicacionScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? publicacionExistente;
  final VoidCallback onPublicacionCreada;

  const CrearPublicacionScreen({
    super.key,
    this.publicacionExistente,
    required this.onPublicacionCreada,
  });

  @override
  ConsumerState<CrearPublicacionScreen> createState() =>
      _CrearPublicacionScreenState();
}

class _CrearPublicacionScreenState
    extends ConsumerState<CrearPublicacionScreen> {
  static const _draftKey = 'red_social_post_draft';
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
    } else {
      _loadDraft();
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
      FlavorSnackbar.showInfo(context, 'Escribe algo para publicar');
      return;
    }

    setState(() => _guardando = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      late ApiResponse<Map<String, dynamic>> response;

      if (_esEdicion) {
        final publicacionId =
            widget.publicacionExistente!['id']?.toString() ?? '';
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

      if (!mounted) return;

      setState(() => _guardando = false);
      await FlavorMutation.runApiResponse(
        context,
        request: () => Future.value(response),
        successMessage:
            _esEdicion ? 'Publicacion actualizada' : 'Publicacion creada',
        fallbackErrorMessage: 'Error al guardar',
        onSuccess: () async {
          await _clearDraft();
          widget.onPublicacionCreada();
          if (mounted) {
            Navigator.pop(context);
          }
        },
      );
    } catch (e) {
      if (mounted) {
        setState(() => _guardando = false);
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  Future<void> _loadDraft() async {
    final draft = await FlavorDraftStore.load(_draftKey);
    if (!mounted || draft.isEmpty || _contenidoController.text.trim().isNotEmpty) {
      return;
    }
    setState(() {
      _contenidoController.text = draft;
    });
  }

  Future<void> _saveDraft(String value) async {
    if (_esEdicion) return;
    await FlavorDraftStore.save(_draftKey, value);
  }

  Future<void> _clearDraft() async {
    await FlavorDraftStore.clear(_draftKey);
  }

  @override
  Widget build(BuildContext context) {
    final currentLength = _contenidoController.text.trim().length;

    return Scaffold(
      appBar: AppBar(
        title: Text(_esEdicion ? 'Editar publicacion' : 'Nueva publicacion'),
        actions: [
          TextButton(
            onPressed: _guardando ? null : _guardarPublicacion,
            child: _guardando
                ? const FlavorInlineSpinner()
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
                decoration: InputDecoration(
                  hintText: '¿Que quieres compartir?',
                  border: InputBorder.none,
                  helperText: 'Comparte algo breve, claro y útil',
                  counterText: '$currentLength/1000',
                ),
                maxLength: 1000,
                maxLines: null,
                expands: true,
                textAlignVertical: TextAlignVertical.top,
                autofocus: true,
                onChanged: (value) {
                  setState(() {});
                  _saveDraft(value);
                },
              ),
            ),
            const Divider(),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.photo_library),
                  onPressed: () {
                    FlavorSnackbar.showInfo(
                      context,
                      'Funcion de imagenes proximamente',
                    );
                  },
                ),
                IconButton(
                  icon: const Icon(Icons.camera_alt),
                  onPressed: () {
                    FlavorSnackbar.showInfo(
                      context,
                      'Funcion de camara proximamente',
                    );
                  },
                ),
                IconButton(
                  icon: const Icon(Icons.location_on),
                  onPressed: () {
                    FlavorSnackbar.showInfo(
                      context,
                      'Funcion de ubicacion proximamente',
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
