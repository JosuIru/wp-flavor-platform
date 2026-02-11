import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';

/// Pantalla para crear/editar servicios en Banco de Tiempo
class BancoTiempoFormScreen extends ConsumerStatefulWidget {
  final int? servicioId; // null = crear, != null = editar
  final Map<String, dynamic>? servicioData;

  const BancoTiempoFormScreen({
    super.key,
    this.servicioId,
    this.servicioData,
  });

  @override
  ConsumerState<BancoTiempoFormScreen> createState() => _BancoTiempoFormScreenState();
}

class _BancoTiempoFormScreenState extends ConsumerState<BancoTiempoFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _duracionController = TextEditingController();

  String _categoria = 'ayuda_domicilio';
  String _tipo = 'ofrezco';
  bool _isLoading = false;

  final List<Map<String, String>> _categorias = [
    {'value': 'ayuda_domicilio', 'label': 'Ayuda en el hogar'},
    {'value': 'cuidado_personas', 'label': 'Cuidado de personas'},
    {'value': 'reparaciones', 'label': 'Reparaciones'},
    {'value': 'tecnologia', 'label': 'Tecnología'},
    {'value': 'idiomas', 'label': 'Idiomas'},
    {'value': 'transporte', 'label': 'Transporte'},
    {'value': 'formacion', 'label': 'Formación'},
    {'value': 'otros', 'label': 'Otros'},
  ];

  @override
  void initState() {
    super.initState();
    if (widget.servicioData != null) {
      _tituloController.text = widget.servicioData!['titulo'] ?? '';
      _descripcionController.text = widget.servicioData!['descripcion'] ?? '';
      _duracionController.text = (widget.servicioData!['duracion_horas'] ?? '').toString();
      _categoria = widget.servicioData!['categoria'] ?? 'ayuda_domicilio';
      _tipo = widget.servicioData!['tipo'] ?? 'ofrezco';
    }
  }

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _duracionController.dispose();
    super.dispose();
  }

  Future<void> _guardarServicio() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final i18n = AppLocalizations.of(context)!;
    setState(() => _isLoading = true);

    try {
      final api = ref.read(apiClientProvider);

      final data = {
        'titulo': _tituloController.text,
        'descripcion': _descripcionController.text,
        'categoria': _categoria,
        'tipo': _tipo,
        'duracion_horas': double.tryParse(_duracionController.text) ?? 1.0,
      };

      ApiResponse response;

      if (widget.servicioId == null) {
        // Crear nuevo
        response = await api.post('/banco-tiempo/servicio', data: data);
      } else {
        // Editar existente
        response = await api.put('/banco-tiempo/servicio/${widget.servicioId}', data: data);
      }

      if (mounted) {
        setState(() => _isLoading = false);

        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(widget.servicioId == null
                  ? 'Servicio creado correctamente'
                  : 'Servicio actualizado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.pop(context, true); // true = cambios realizados
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
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error de conexión: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.servicioId == null ? 'Nuevo Servicio' : 'Editar Servicio'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Tipo (Ofrezco/Necesito)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Tipo de servicio',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(
                          value: 'ofrezco',
                          label: Text('Ofrezco'),
                          icon: Icon(Icons.volunteer_activism),
                        ),
                        ButtonSegment(
                          value: 'necesito',
                          label: Text('Necesito'),
                          icon: Icon(Icons.help_outline),
                        ),
                      ],
                      selected: {_tipo},
                      onSelectionChanged: (Set<String> selected) {
                        setState(() => _tipo = selected.first);
                      },
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Título
            TextFormField(
              controller: _tituloController,
              decoration: InputDecoration(
                labelText: 'Título del servicio *',
                hintText: 'Ej: Clases de inglés, Ayuda con compras...',
                prefixIcon: const Icon(Icons.title),
                border: const OutlineInputBorder(),
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
                return DropdownMenuItem(
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

            // Duración estimada
            TextFormField(
              controller: _duracionController,
              decoration: const InputDecoration(
                labelText: 'Duración estimada (horas) *',
                hintText: '1.0',
                prefixIcon: Icon(Icons.access_time),
                border: OutlineInputBorder(),
                suffixText: 'horas',
              ),
              keyboardType: TextInputType.numberWithOptions(decimal: true),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La duración es obligatoria';
                }
                final num = double.tryParse(value);
                if (num == null || num <= 0) {
                  return 'Ingresa un número válido mayor a 0';
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
                hintText: 'Describe el servicio con detalle...',
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
            const SizedBox(height: 24),

            // Botón guardar
            FilledButton.icon(
              onPressed: _isLoading ? null : _guardarServicio,
              icon: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Icon(Icons.check),
              label: Text(_isLoading
                  ? 'Guardando...'
                  : (widget.servicioId == null ? 'Crear Servicio' : 'Guardar Cambios')),
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
