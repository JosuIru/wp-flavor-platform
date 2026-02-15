/// Configuración dinámica de módulos
///
/// Define cómo se renderiza cada módulo sin necesidad de código específico.

class ModuleConfig {
  final String id;
  final String titulo;
  final String icono;
  final String endpoint;
  final LayoutType layout;
  final Map<String, String> campos;
  final List<ModuleAction> acciones;
  final FabConfig? fab;
  final List<FilterConfig> filtros;
  final String? detailEndpoint;
  final String? updateEndpoint;
  final bool requiereAuth;
  final String? emptyMessage;
  final String? emptyIcon;
  final List<FormFieldConfig>? formFields;

  const ModuleConfig({
    required this.id,
    required this.titulo,
    required this.icono,
    required this.endpoint,
    this.layout = LayoutType.card,
    this.campos = const {},
    this.acciones = const [],
    this.fab,
    this.filtros = const [],
    this.detailEndpoint,
    this.updateEndpoint,
    this.requiereAuth = true,
    this.emptyMessage,
    this.emptyIcon,
    this.formFields,
  });

  factory ModuleConfig.fromJson(Map<String, dynamic> json) {
    return ModuleConfig(
      id: json['id'] ?? '',
      titulo: json['titulo'] ?? json['title'] ?? '',
      icono: json['icono'] ?? json['icon'] ?? 'extension',
      endpoint: json['endpoint'] ?? '',
      layout: LayoutType.fromString(json['layout']),
      campos: Map<String, String>.from(json['campos'] ?? {}),
      acciones: (json['acciones'] as List<dynamic>?)
          ?.map((a) => ModuleAction.fromJson(a))
          .toList() ?? [],
      fab: json['fab'] != null ? FabConfig.fromJson(json['fab']) : null,
      filtros: (json['filtros'] as List<dynamic>?)
          ?.map((f) => FilterConfig.fromJson(f))
          .toList() ?? [],
      detailEndpoint: json['detail_endpoint'],
      updateEndpoint: json['update_endpoint'],
      requiereAuth: json['requiere_auth'] ?? true,
      emptyMessage: json['empty_message'],
      emptyIcon: json['empty_icon'],
      formFields: (json['form_fields'] as List<dynamic>?)
          ?.map((f) => FormFieldConfig.fromJson(f))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'titulo': titulo,
    'icono': icono,
    'endpoint': endpoint,
    'layout': layout.name,
    'campos': campos,
    'acciones': acciones.map((a) => a.toJson()).toList(),
    'fab': fab?.toJson(),
    'filtros': filtros.map((f) => f.toJson()).toList(),
    'detail_endpoint': detailEndpoint,
    'update_endpoint': updateEndpoint,
    'requiere_auth': requiereAuth,
    'empty_message': emptyMessage,
    'empty_icon': emptyIcon,
    'form_fields': formFields?.map((f) => f.toJson()).toList(),
  };
}

enum LayoutType {
  list,      // ListTile simple
  card,      // Card con imagen
  grid,      // Grid de 2 columnas
  dashboard, // Dashboard con stats
  chat,      // Estilo chat/mensajes
  map,       // Con mapa
  timeline;  // Timeline vertical

  static LayoutType fromString(String? value) {
    return LayoutType.values.firstWhere(
      (e) => e.name == value,
      orElse: () => LayoutType.card,
    );
  }
}

class ModuleAction {
  final String id;
  final String label;
  final String icono;
  final String tipo; // navigate, api_call, share, call, map
  final String? endpoint;
  final String? route;
  final bool requiereConfirmacion;

  const ModuleAction({
    required this.id,
    required this.label,
    required this.icono,
    required this.tipo,
    this.endpoint,
    this.route,
    this.requiereConfirmacion = false,
  });

  factory ModuleAction.fromJson(Map<String, dynamic> json) {
    return ModuleAction(
      id: json['id'] ?? '',
      label: json['label'] ?? '',
      icono: json['icono'] ?? 'touch_app',
      tipo: json['tipo'] ?? 'navigate',
      endpoint: json['endpoint'],
      route: json['route'],
      requiereConfirmacion: json['requiere_confirmacion'] ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'label': label,
    'icono': icono,
    'tipo': tipo,
    'endpoint': endpoint,
    'route': route,
    'requiere_confirmacion': requiereConfirmacion,
  };
}

class FabConfig {
  final String icono;
  final String label;
  final String accion; // create, navigate
  final String? endpoint;
  final String? route;

  const FabConfig({
    required this.icono,
    required this.label,
    required this.accion,
    this.endpoint,
    this.route,
  });

  factory FabConfig.fromJson(Map<String, dynamic> json) {
    return FabConfig(
      icono: json['icono'] ?? 'add',
      label: json['label'] ?? 'Nuevo',
      accion: json['accion'] ?? 'create',
      endpoint: json['endpoint'],
      route: json['route'],
    );
  }

  Map<String, dynamic> toJson() => {
    'icono': icono,
    'label': label,
    'accion': accion,
    'endpoint': endpoint,
    'route': route,
  };
}

class FilterConfig {
  final String id;
  final String label;
  final String tipo; // select, search, date, toggle
  final List<FilterOption> opciones;
  final String? paramName;

  const FilterConfig({
    required this.id,
    required this.label,
    required this.tipo,
    this.opciones = const [],
    this.paramName,
  });

  factory FilterConfig.fromJson(Map<String, dynamic> json) {
    return FilterConfig(
      id: json['id'] ?? '',
      label: json['label'] ?? '',
      tipo: json['tipo'] ?? 'select',
      opciones: (json['opciones'] as List<dynamic>?)
          ?.map((o) => FilterOption.fromJson(o))
          .toList() ?? [],
      paramName: json['param_name'],
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'label': label,
    'tipo': tipo,
    'opciones': opciones.map((o) => o.toJson()).toList(),
    'param_name': paramName,
  };
}

class FilterOption {
  final String value;
  final String label;

  const FilterOption({required this.value, required this.label});

  factory FilterOption.fromJson(Map<String, dynamic> json) {
    return FilterOption(
      value: json['value']?.toString() ?? '',
      label: json['label'] ?? '',
    );
  }

  Map<String, dynamic> toJson() => {'value': value, 'label': label};
}

/// Configuración de campo de formulario
class FormFieldConfig {
  final String name;
  final String label;
  final String type; // text, textarea, number, email, phone, date, time, select, checkbox, image, hidden
  final bool required;
  final String? placeholder;
  final String? helperText;
  final String? icon;
  final dynamic defaultValue;
  final int? maxLength;
  final int? maxLines;
  final num? min;
  final num? max;
  final String? suffix;
  final List<FilterOption>? options;
  final String? validation; // regex pattern

  const FormFieldConfig({
    required this.name,
    required this.label,
    required this.type,
    this.required = false,
    this.placeholder,
    this.helperText,
    this.icon,
    this.defaultValue,
    this.maxLength,
    this.maxLines,
    this.min,
    this.max,
    this.suffix,
    this.options,
    this.validation,
  });

  factory FormFieldConfig.fromJson(Map<String, dynamic> json) {
    return FormFieldConfig(
      name: json['name'] ?? '',
      label: json['label'] ?? '',
      type: json['type'] ?? 'text',
      required: json['required'] ?? false,
      placeholder: json['placeholder'],
      helperText: json['helper_text'],
      icon: json['icon'],
      defaultValue: json['default_value'],
      maxLength: json['max_length'],
      maxLines: json['max_lines'],
      min: json['min'],
      max: json['max'],
      suffix: json['suffix'],
      options: (json['options'] as List<dynamic>?)
          ?.map((o) => FilterOption.fromJson(o))
          .toList(),
      validation: json['validation'],
    );
  }

  Map<String, dynamic> toJson() => {
    'name': name,
    'label': label,
    'type': type,
    'required': required,
    'placeholder': placeholder,
    'helper_text': helperText,
    'icon': icon,
    'default_value': defaultValue,
    'max_length': maxLength,
    'max_lines': maxLines,
    'min': min,
    'max': max,
    'suffix': suffix,
    'options': options?.map((o) => o.toJson()).toList(),
    'validation': validation,
  };
}
