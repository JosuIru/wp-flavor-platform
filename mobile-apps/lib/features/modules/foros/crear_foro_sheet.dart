import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/widgets/flavor_snackbar.dart';

class CrearForoSheet extends ConsumerStatefulWidget {
  final Future<void> Function() onCreated;

  const CrearForoSheet({
    super.key,
    required this.onCreated,
  });

  @override
  ConsumerState<CrearForoSheet> createState() => _CrearForoSheetState();
}

class _CrearForoSheetState extends ConsumerState<CrearForoSheet> {
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  String _categoriaSeleccionada = 'general';

  static const _categorias = [
    'general',
    'anuncios',
    'debates',
    'ayuda',
    'sugerencias',
  ];

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    super.dispose();
  }

  Future<void> _crear() async {
    if (_tituloController.text.trim().isEmpty) {
      FlavorSnackbar.showInfo(context, 'El titulo es obligatorio');
      return;
    }

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/foros', data: {
        'titulo': _tituloController.text.trim(),
        'descripcion': _descripcionController.text.trim(),
        'categoria': _categoriaSeleccionada,
      });

      if (!mounted) return;

      final created = await FlavorMutation.runApiResponse(
        context,
        request: () => Future.value(respuesta),
        successMessage: 'Debate creado correctamente',
        fallbackErrorMessage: 'Error al crear el debate',
        onSuccess: widget.onCreated,
      );

      if (created && mounted) {
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        left: 20,
        right: 20,
        top: 20,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.forum, color: Colors.purple.shade400),
                const SizedBox(width: 12),
                const Text(
                  'Nuevo Debate',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context, false),
                ),
              ],
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _tituloController,
              decoration: const InputDecoration(
                labelText: 'Titulo del debate',
                prefixIcon: Icon(Icons.title),
                border: OutlineInputBorder(),
                hintText: 'Escribe un titulo descriptivo',
              ),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _categoriaSeleccionada,
              decoration: const InputDecoration(
                labelText: 'Categoria',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _categorias
                  .map(
                    (categoria) => DropdownMenuItem<String>(
                      value: categoria,
                      child: Text(
                        categoria[0].toUpperCase() + categoria.substring(1),
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (value) {
                if (value != null) {
                  setState(() => _categoriaSeleccionada = value);
                }
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Contenido',
                prefixIcon: Icon(Icons.description),
                border: OutlineInputBorder(),
                hintText: 'Describe el tema que quieres discutir...',
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _crear,
                icon: const Icon(Icons.send),
                label: const Text('Publicar debate'),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }
}
