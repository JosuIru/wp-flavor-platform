import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/dynamic_form_support.dart';
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../models/module_config.dart';
import '../widgets/icon_helper.dart';

enum FormMode { create, edit }

/// Pantalla de formulario dinámica para crear/editar items
class DynamicFormScreen extends ConsumerStatefulWidget {
  final ModuleConfig config;
  final FormMode mode;
  final Map<String, dynamic>? initialData;
  final String? itemId;

  const DynamicFormScreen({
    super.key,
    required this.config,
    required this.mode,
    this.initialData,
    this.itemId,
  });

  @override
  ConsumerState<DynamicFormScreen> createState() => _DynamicFormScreenState();
}

class _DynamicFormScreenState extends ConsumerState<DynamicFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {};
  final Map<String, dynamic> _formData = {};
  bool _loading = false;
  List<FormFieldConfig>? _fields;

  @override
  void initState() {
    super.initState();
    _loadFormConfig();
    _initializeControllers();
  }

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _loadFormConfig() async {
    // Si hay configuración de campos en el config, usarla
    if (widget.config.formFields != null && widget.config.formFields!.isNotEmpty) {
      setState(() {
        _fields = widget.config.formFields;
      });
      return;
    }

    // Si no, intentar cargar del API
    try {
      final api = ref.read(apiClientProvider);
      final endpoint = '${widget.config.endpoint}/form-config';
      final response = await api.get(endpoint);

      if (response.success && response.data != null) {
        final fieldsData = response.data!['fields'] as List<dynamic>?;
        if (fieldsData != null) {
          setState(() {
            _fields = fieldsData
                .map((f) => FormFieldConfig.fromJson(f as Map<String, dynamic>))
                .toList();
          });
          _initializeControllers();
        }
      }
    } catch (e) {
      // Usar campos por defecto basados en initialData
      _generateFieldsFromData();
    }
  }

  void _generateFieldsFromData() {
    if (widget.initialData == null) return;

    final generatedFields = <FormFieldConfig>[];
    final skipFields = ['id', 'created_at', 'updated_at', 'user_id', 'autor_id'];

    for (final entry in widget.initialData!.entries) {
      if (skipFields.contains(entry.key)) continue;

      generatedFields.add(FormFieldConfig(
        name: entry.key,
        label: _formatLabel(entry.key),
        type: _inferFieldType(entry.key, entry.value),
        required: false,
      ));
    }

    setState(() {
      _fields = generatedFields;
    });
    _initializeControllers();
  }

  String _inferFieldType(String key, dynamic value) {
    if (key.contains('email')) return 'email';
    if (key.contains('telefono') || key.contains('phone')) return 'phone';
    if (key.contains('fecha') || key.contains('date')) return 'date';
    if (key.contains('hora') || key.contains('time')) return 'time';
    if (key.contains('precio') || key.contains('price') || key.contains('importe')) return 'number';
    if (key.contains('descripcion') || key.contains('contenido') || key.contains('content')) return 'textarea';
    if (key.contains('imagen') || key.contains('image') || key.contains('foto')) return 'image';
    if (key.contains('estado') || key.contains('status')) return 'select';
    if (value is bool) return 'checkbox';
    if (value is num) return 'number';
    return 'text';
  }

  void _initializeControllers() {
    // Inicializar con datos existentes si es modo edición
    if (widget.initialData != null) {
      for (final entry in widget.initialData!.entries) {
        _controllers[entry.key] = TextEditingController(
          text: entry.value?.toString() ?? '',
        );
        _formData[entry.key] = entry.value;
      }
    }

    // Inicializar controladores para campos del formulario
    if (_fields != null) {
      for (final field in _fields!) {
        if (!_controllers.containsKey(field.name)) {
          _controllers[field.name] = TextEditingController(
            text: field.defaultValue?.toString() ?? '',
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final titulo = widget.mode == FormMode.create
        ? 'Crear ${widget.config.titulo}'
        : 'Editar ${widget.config.titulo}';

    return Scaffold(
      appBar: AppBar(
        title: Text(titulo),
        actions: [
          TextButton(
            onPressed: _loading ? null : _onSave,
            child: _loading
                ? const FlavorInlineSpinner()
                : const Text('Guardar'),
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_fields == null) {
      return const FlavorLoadingState();
    }

    if (_fields!.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.warning_amber_outlined,
        title: 'No hay campos configurados',
      );
    }

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ..._fields!.map(_buildField),
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _loading ? null : _onSave,
            icon: _loading
                ? const FlavorInlineSpinner(color: Colors.white)
                : const Icon(Icons.save),
            label: Text(widget.mode == FormMode.create ? 'Crear' : 'Guardar cambios'),
          ),
        ],
      ),
    );
  }

  Widget _buildField(FormFieldConfig field) {
    switch (field.type) {
      case 'textarea':
        return _buildTextArea(field);
      case 'number':
        return _buildNumberField(field);
      case 'email':
        return _buildEmailField(field);
      case 'phone':
        return _buildPhoneField(field);
      case 'date':
        return _buildDateField(field);
      case 'time':
        return _buildTimeField(field);
      case 'select':
        return _buildSelectField(field);
      case 'checkbox':
        return _buildCheckbox(field);
      case 'image':
        return _buildImageField(field);
      case 'hidden':
        return const SizedBox.shrink();
      default:
        return _buildTextField(field);
    }
  }

  Widget _buildTextField(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: field.placeholder,
          helperText: field.helperText,
          prefixIcon: field.icon != null
              ? Icon(IconHelper.getIcon(field.icon!))
              : null,
          border: const OutlineInputBorder(),
        ),
        validator: field.required
            ? (value) => value?.isEmpty == true ? 'Campo requerido' : null
            : null,
        onChanged: (value) => _formData[field.name] = value,
        maxLength: field.maxLength,
      ),
    );
  }

  Widget _buildTextArea(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: field.placeholder,
          helperText: field.helperText,
          border: const OutlineInputBorder(),
          alignLabelWithHint: true,
        ),
        maxLines: field.maxLines ?? 5,
        minLines: 3,
        validator: field.required
            ? (value) => value?.isEmpty == true ? 'Campo requerido' : null
            : null,
        onChanged: (value) => _formData[field.name] = value,
        maxLength: field.maxLength,
      ),
    );
  }

  Widget _buildNumberField(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: field.placeholder,
          prefixIcon: Icon(IconHelper.getIcon(field.icon ?? 'numbers')),
          suffixText: field.suffix,
          border: const OutlineInputBorder(),
        ),
        keyboardType: const TextInputType.numberWithOptions(decimal: true),
        validator: (value) {
          if (field.required && (value == null || value.isEmpty)) {
            return 'Campo requerido';
          }
          if (value != null && value.isNotEmpty) {
            final num? parsed = num.tryParse(value);
            if (parsed == null) return 'Número inválido';
            if (field.min != null && parsed < field.min!) {
              return 'Mínimo: ${field.min}';
            }
            if (field.max != null && parsed > field.max!) {
              return 'Máximo: ${field.max}';
            }
          }
          return null;
        },
        onChanged: (value) => _formData[field.name] = num.tryParse(value),
      ),
    );
  }

  Widget _buildEmailField(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: field.placeholder ?? 'ejemplo@email.com',
          prefixIcon: const Icon(Icons.email),
          border: const OutlineInputBorder(),
        ),
        keyboardType: TextInputType.emailAddress,
        validator: (value) {
          if (field.required && (value == null || value.isEmpty)) {
            return 'Campo requerido';
          }
          if (value != null && value.isNotEmpty) {
            final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
            if (!emailRegex.hasMatch(value)) {
              return 'Email inválido';
            }
          }
          return null;
        },
        onChanged: (value) => _formData[field.name] = value,
      ),
    );
  }

  Widget _buildPhoneField(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: field.placeholder ?? '+34 600 000 000',
          prefixIcon: const Icon(Icons.phone),
          border: const OutlineInputBorder(),
        ),
        keyboardType: TextInputType.phone,
        validator: field.required
            ? (value) => value?.isEmpty == true ? 'Campo requerido' : null
            : null,
        onChanged: (value) => _formData[field.name] = value,
      ),
    );
  }

  Widget _buildDateField(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: 'DD/MM/AAAA',
          prefixIcon: const Icon(Icons.calendar_today),
          border: const OutlineInputBorder(),
        ),
        readOnly: true,
        onTap: () async {
          final date = await DynamicFormSupport.pickDate(context);
          if (date != null) {
            _controllers[field.name]?.text = date.displayValue;
            _formData[field.name] = date.submitValue;
          }
        },
        validator: field.required
            ? (value) => value?.isEmpty == true ? 'Campo requerido' : null
            : null,
      ),
    );
  }

  Widget _buildTimeField(FormFieldConfig field) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: _controllers[field.name],
        decoration: InputDecoration(
          labelText: field.label,
          hintText: 'HH:MM',
          prefixIcon: const Icon(Icons.schedule),
          border: const OutlineInputBorder(),
        ),
        readOnly: true,
        onTap: () async {
          final time = await DynamicFormSupport.pickTime(context);
          if (time != null) {
            _controllers[field.name]?.text = time.displayValue;
            _formData[field.name] = time.submitValue;
          }
        },
        validator: field.required
            ? (value) => value?.isEmpty == true ? 'Campo requerido' : null
            : null,
      ),
    );
  }

  Widget _buildSelectField(FormFieldConfig field) {
    final currentValue = _formData[field.name]?.toString() ??
                         _controllers[field.name]?.text ??
                         field.defaultValue?.toString();

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: DropdownButtonFormField<String>(
        value: currentValue?.isNotEmpty == true ? currentValue : null,
        decoration: InputDecoration(
          labelText: field.label,
          prefixIcon: field.icon != null
              ? Icon(IconHelper.getIcon(field.icon!))
              : null,
          border: const OutlineInputBorder(),
        ),
        items: field.options?.map((option) {
          return DropdownMenuItem(
            value: option.value,
            child: Text(option.label),
          );
        }).toList(),
        onChanged: (value) {
          setState(() {
            _formData[field.name] = value;
            _controllers[field.name]?.text = value ?? '';
          });
        },
        validator: field.required
            ? (value) => value == null ? 'Campo requerido' : null
            : null,
      ),
    );
  }

  Widget _buildCheckbox(FormFieldConfig field) {
    final currentValue = _formData[field.name] == true ||
        DynamicFormSupport.parseBool(_formData[field.name]);

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: CheckboxListTile(
        title: Text(field.label),
        subtitle: field.helperText != null ? Text(field.helperText!) : null,
        value: currentValue,
        onChanged: (value) {
          setState(() {
            _formData[field.name] = value;
          });
        },
        controlAffinity: ListTileControlAffinity.leading,
      ),
    );
  }

  Widget _buildImageField(FormFieldConfig field) {
    final currentImage = _formData[field.name]?.toString() ??
                         _controllers[field.name]?.text;

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            field.label,
            style: const TextStyle(fontSize: 16),
          ),
          const SizedBox(height: 8),
          if (currentImage != null && currentImage.isNotEmpty)
            Stack(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    currentImage,
                    height: 150,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      height: 150,
                      color: Colors.grey.shade200,
                      child: const Center(
                        child: Icon(Icons.broken_image, size: 48),
                      ),
                    ),
                  ),
                ),
                Positioned(
                  top: 8,
                  right: 8,
                  child: IconButton.filledTonal(
                    icon: const Icon(Icons.delete),
                    onPressed: () {
                      setState(() {
                        _formData[field.name] = null;
                        _controllers[field.name]?.clear();
                      });
                    },
                  ),
                ),
              ],
            )
          else
            InkWell(
              onTap: _onSelectImage,
              child: Container(
                height: 150,
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.add_photo_alternate,
                          size: 48, color: Colors.grey.shade400),
                      const SizedBox(height: 8),
                      Text(
                        'Seleccionar imagen',
                        style: TextStyle(color: Colors.grey.shade600),
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Future<void> _onSelectImage() async {
    final source = await showModalBottomSheet<String>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 8),
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey.shade300,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 16),
          ListTile(
            leading: const Icon(Icons.camera_alt),
            title: const Text('Tomar foto'),
            onTap: () => Navigator.pop(context, 'camera'),
          ),
          ListTile(
            leading: const Icon(Icons.photo_library),
            title: const Text('Elegir de galeria'),
            onTap: () => Navigator.pop(context, 'gallery'),
          ),
          ListTile(
            leading: const Icon(Icons.link),
            title: const Text('Introducir URL'),
            onTap: () => Navigator.pop(context, 'url'),
          ),
          const SizedBox(height: 16),
        ],
      ),
    );

    if (source == null) return;

    if (source == 'url') {
      _showUrlDialog();
    } else {
      // Para camera y gallery, mostrar mensaje de que requiere image_picker
      if (mounted) {
        FlavorSnackbar.showInfo(context, 'Usa la opcion de URL para agregar una imagen');
      }
    }
  }

  void _showUrlDialog() {
    final urlController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('URL de imagen'),
        content: TextField(
          controller: urlController,
          decoration: const InputDecoration(
            labelText: 'URL',
            hintText: 'https://ejemplo.com/imagen.jpg',
            border: OutlineInputBorder(),
          ),
          keyboardType: TextInputType.url,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () {
              if (urlController.text.isNotEmpty) {
                setState(() {
                  // Buscar el campo de imagen y actualizarlo
                  final imageField = _fields?.firstWhere(
                    (f) => f.type == 'image',
                    orElse: () => const FormFieldConfig(name: '', label: '', type: ''),
                  );
                  if (imageField != null && imageField.name.isNotEmpty) {
                    _formData[imageField.name] = urlController.text;
                    _controllers[imageField.name]?.text = urlController.text;
                  }
                });
              }
              Navigator.pop(context);
            },
            child: const Text('Aceptar'),
          ),
        ],
      ),
    );
  }

  String _formatLabel(String key) {
    return key
        .replaceAll('_', ' ')
        .split(' ')
        .map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '')
        .join(' ');
  }

  Future<void> _onSave() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);

    try {
      final api = ref.read(apiClientProvider);

      // Recopilar datos del formulario
      final data = <String, dynamic>{};
      for (final field in _fields ?? []) {
        final value = _formData[field.name] ?? _controllers[field.name]?.text;
        if (value != null && value.toString().isNotEmpty) {
          data[field.name] = value;
        }
      }

      final saved = await FlavorMutation.runApiResponse(
        context,
        request: () {
          if (widget.mode == FormMode.create) {
            return api.post(widget.config.endpoint, data: data);
          }

          final endpoint =
              widget.config.updateEndpoint ?? '${widget.config.endpoint}/${widget.itemId}';
          return api.put(endpoint, data: data);
        },
        successMessage: widget.mode == FormMode.create
            ? 'Creado correctamente'
            : 'Guardado correctamente',
        fallbackErrorMessage: 'Error al guardar',
      );

      if (saved && mounted) {
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }
}
