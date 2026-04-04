import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

/// Pantalla para crear/editar anuncios en Marketplace
class MarketplaceFormScreen extends ConsumerStatefulWidget {
  final int? anuncioId; // null = crear, != null = editar
  final Map<String, dynamic>? anuncioData;

  const MarketplaceFormScreen({
    super.key,
    this.anuncioId,
    this.anuncioData,
  });

  @override
  ConsumerState<MarketplaceFormScreen> createState() => _MarketplaceFormScreenState();
}

class _MarketplaceFormScreenState extends ConsumerState<MarketplaceFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _precioController = TextEditingController();
  final _imagePicker = ImagePicker();

  String _categoria = 'otros';
  String _estado = 'disponible';
  List<XFile> _imagenes = [];
  bool _isLoading = false;

  final List<Map<String, String>> _categorias = [
    {'value': 'electronica', 'label': 'Electrónica'},
    {'value': 'ropa', 'label': 'Ropa y Accesorios'},
    {'value': 'hogar', 'label': 'Hogar y Jardín'},
    {'value': 'deportes', 'label': 'Deportes'},
    {'value': 'libros', 'label': 'Libros'},
    {'value': 'vehiculos', 'label': 'Vehículos'},
    {'value': 'muebles', 'label': 'Muebles'},
    {'value': 'servicios', 'label': 'Servicios'},
    {'value': 'otros', 'label': 'Otros'},
  ];

  @override
  void initState() {
    super.initState();
    if (widget.anuncioData != null) {
      _tituloController.text = widget.anuncioData!['titulo'] ?? '';
      _descripcionController.text = widget.anuncioData!['descripcion'] ?? '';
      _precioController.text = (widget.anuncioData!['precio'] ?? '').toString();
      _categoria = widget.anuncioData!['categoria'] ?? 'otros';
      _estado = widget.anuncioData!['estado'] ?? 'disponible';
    }
  }

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _precioController.dispose();
    super.dispose();
  }

  Future<void> _seleccionarImagenes() async {
    try {
      final List<XFile> pickedFiles = await _imagePicker.pickMultiImage(
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 85,
      );

      if (pickedFiles.isNotEmpty) {
        setState(() {
          // Limitar a 5 imágenes
          _imagenes = [..._imagenes, ...pickedFiles].take(5).toList();
        });
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error al seleccionar imágenes: $e');
    }
  }

  Future<void> _tomarFoto() async {
    try {
      final XFile? photo = await _imagePicker.pickImage(
        source: ImageSource.camera,
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 85,
      );

      if (photo != null) {
        setState(() {
          if (_imagenes.length < 5) {
            _imagenes.add(photo);
          }
        });
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error al tomar foto: $e');
    }
  }

  void _eliminarImagen(int index) {
    setState(() {
      _imagenes.removeAt(index);
    });
  }

  Future<void> _guardarAnuncio() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isLoading = true);

    try {
      final api = ref.read(apiClientProvider);

      final data = <String, dynamic>{
        'titulo': _tituloController.text,
        'descripcion': _descripcionController.text,
        'categoria': _categoria,
        'precio': double.tryParse(_precioController.text) ?? 0.0,
        'estado': _estado,
      };

      // Subir imágenes si hay alguna seleccionada
      if (_imagenes.isNotEmpty) {
        final uploadResponse = await api.uploadImages(
          _imagenes,
          context: 'marketplace',
        );

        if (uploadResponse.success && uploadResponse.data != null) {
          final urls = uploadResponse.data!['urls'] as List?;
          if (urls != null && urls.isNotEmpty) {
            data['imagenes'] = urls.map((u) => u['url'] ?? u).toList();
          }
        } else {
          // Mostrar error pero permitir continuar sin imágenes
          if (mounted) {
            FlavorSnackbar.showInfo(
              context,
              'Advertencia: ${uploadResponse.error ?? 'No se pudieron subir las imágenes'}',
            );
          }
        }
      }

      ApiResponse response;

      if (widget.anuncioId == null) {
        // Crear nuevo
        response = await api.post('/marketplace/anuncio', data: data);
      } else {
        // Editar existente
        response = await api.put('/marketplace/anuncio/${widget.anuncioId}', data: data);
      }

      if (mounted) {
        setState(() => _isLoading = false);

        if (response.success) {
          FlavorSnackbar.showSuccess(
            context,
            widget.anuncioId == null
                ? 'Anuncio creado correctamente'
                : 'Anuncio actualizado correctamente',
          );
          Navigator.pop(context, true); // true = cambios realizados
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al guardar');
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        FlavorSnackbar.showError(context, 'Error de conexión: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.anuncioId == null ? 'Nuevo Anuncio' : 'Editar Anuncio'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Título
            TextFormField(
              controller: _tituloController,
              decoration: const InputDecoration(
                labelText: 'Título del anuncio *',
                hintText: 'Ej: Bicicleta montaña seminueva',
                prefixIcon: Icon(Icons.title),
                border: OutlineInputBorder(),
              ),
              maxLength: 100,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El título es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Categoría
            DropdownButtonFormField<String>(
              value: _categoria,
              decoration: const InputDecoration(
                labelText: 'Categoría *',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _categorias.map((cat) {
                return DropdownMenuItem<String>(
                  value: cat['value'],
                  child: Text(cat['label']!),
                );
              }).toList(),
              onChanged: (value) {
                if (value != null) {
                  setState(() => _categoria = value);
                }
              },
            ),
            const SizedBox(height: 16),

            // Precio
            TextFormField(
              controller: _precioController,
              decoration: const InputDecoration(
                labelText: 'Precio (€) *',
                hintText: '0.00',
                prefixIcon: Icon(Icons.euro),
                border: OutlineInputBorder(),
                suffixText: '€',
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El precio es obligatorio';
                }
                final num = double.tryParse(value);
                if (num == null || num < 0) {
                  return 'Ingresa un precio válido';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción *',
                hintText: 'Describe el producto o servicio...',
                prefixIcon: Icon(Icons.description),
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
              maxLines: 5,
              maxLength: 500,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La descripción es obligatoria';
                }
                if (value.trim().length < 20) {
                  return 'La descripción debe tener al menos 20 caracteres';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Estado
            DropdownButtonFormField<String>(
              value: _estado,
              decoration: const InputDecoration(
                labelText: 'Estado *',
                prefixIcon: Icon(Icons.info_outline),
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'disponible', child: Text('Disponible')),
                DropdownMenuItem(value: 'vendido', child: Text('Vendido')),
                DropdownMenuItem(value: 'reservado', child: Text('Reservado')),
              ],
              onChanged: (value) {
                if (value != null) {
                  setState(() => _estado = value);
                }
              },
            ),
            const SizedBox(height: 24),

            // Imágenes
            Text(
              'Imágenes (hasta 5)',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),

            if (_imagenes.isNotEmpty)
              SizedBox(
                height: 120,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: _imagenes.length,
                  itemBuilder: (context, index) {
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Stack(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: Image.file(
                              File(_imagenes[index].path),
                              width: 120,
                              height: 120,
                              fit: BoxFit.cover,
                            ),
                          ),
                          Positioned(
                            top: 4,
                            right: 4,
                            child: CircleAvatar(
                              radius: 16,
                              backgroundColor: Colors.red,
                              child: IconButton(
                                padding: EdgeInsets.zero,
                                icon: const Icon(Icons.close, size: 16),
                                color: Colors.white,
                                onPressed: () => _eliminarImagen(index),
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),

            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _imagenes.length < 5 ? _seleccionarImagenes : null,
                    icon: const Icon(Icons.photo_library),
                    label: const Text('Galería'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _imagenes.length < 5 ? _tomarFoto : null,
                    icon: const Icon(Icons.camera_alt),
                    label: const Text('Cámara'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Botón guardar
            FilledButton.icon(
              onPressed: _isLoading ? null : _guardarAnuncio,
              icon: _isLoading
                  ? const FlavorInlineSpinner(color: Colors.white)
                  : const Icon(Icons.check),
              label: Text(_isLoading
                  ? 'Guardando...'
                  : (widget.anuncioId == null ? 'Publicar Anuncio' : 'Guardar Cambios')),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
