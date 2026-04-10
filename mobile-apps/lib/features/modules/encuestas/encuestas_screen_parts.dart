part of 'encuestas_screen.dart';

/// Modelo de encuesta
class _Encuesta {
  final int id;
  final String titulo;
  final String? descripcion;
  final String estado;
  final String tipo;
  final bool esAnonima;
  final bool permiteMultiples;
  final String mostrarResultados;
  final String? fechaCierre;
  final int totalRespuestas;
  final int totalParticipantes;
  final String fechaCreacion;
  final List<_Campo> campos;
  final _Autor? autor;

  _Encuesta({
    required this.id,
    required this.titulo,
    this.descripcion,
    required this.estado,
    required this.tipo,
    required this.esAnonima,
    required this.permiteMultiples,
    required this.mostrarResultados,
    this.fechaCierre,
    required this.totalRespuestas,
    required this.totalParticipantes,
    required this.fechaCreacion,
    required this.campos,
    this.autor,
  });

  factory _Encuesta.fromJson(Map<String, dynamic> json) {
    return _Encuesta(
      id: json['id'] ?? 0,
      titulo: json['titulo'] ?? '',
      descripcion: json['descripcion'],
      estado: json['estado'] ?? 'activa',
      tipo: json['tipo'] ?? 'encuesta',
      esAnonima: json['es_anonima'] ?? false,
      permiteMultiples: json['permite_multiples'] ?? false,
      mostrarResultados: json['mostrar_resultados'] ?? 'al_votar',
      fechaCierre: json['fecha_cierre'],
      totalRespuestas: json['total_respuestas'] ?? 0,
      totalParticipantes: json['total_participantes'] ?? 0,
      fechaCreacion: json['fecha_creacion'] ?? '',
      campos: (json['campos'] as List<dynamic>?)
              ?.map((c) => _Campo.fromJson(c))
              .toList() ??
          [],
      autor: json['autor'] != null ? _Autor.fromJson(json['autor']) : null,
    );
  }

  bool get estaActiva => estado == 'activa';
  bool get estaCerrada => estado == 'cerrada';
}

class _Campo {
  final int id;
  final String tipo;
  final String etiqueta;
  final String? descripcion;
  final List<String> opciones;
  final bool esRequerido;
  final int orden;

  _Campo({
    required this.id,
    required this.tipo,
    required this.etiqueta,
    this.descripcion,
    required this.opciones,
    required this.esRequerido,
    required this.orden,
  });

  factory _Campo.fromJson(Map<String, dynamic> json) {
    List<String> parseOpciones(dynamic opciones) {
      if (opciones == null) return [];
      if (opciones is List) {
        return opciones.map((e) => e.toString()).toList();
      }
      if (opciones is String) {
        try {
          return opciones.split(',').map((e) => e.trim()).toList();
        } catch (_) {
          return [];
        }
      }
      return [];
    }

    return _Campo(
      id: json['id'] ?? 0,
      tipo: json['tipo'] ?? 'texto',
      etiqueta: json['etiqueta'] ?? '',
      descripcion: json['descripcion'],
      opciones: parseOpciones(json['opciones']),
      esRequerido: json['es_requerido'] ?? true,
      orden: json['orden'] ?? 0,
    );
  }
}

class _Autor {
  final int id;
  final String nombre;
  final String? avatar;

  _Autor({required this.id, required this.nombre, this.avatar});

  factory _Autor.fromJson(Map<String, dynamic> json) {
    return _Autor(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? '',
      avatar: json['avatar'],
    );
  }
}

/// Card de encuesta en la lista
class _EncuestaCard extends StatelessWidget {
  final _Encuesta encuesta;
  final VoidCallback onTap;

  const _EncuestaCard({required this.encuesta, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header con estado y tipo
              Row(
                children: [
                  _EstadoBadge(estado: encuesta.estado),
                  const SizedBox(width: 8),
                  _TipoBadge(tipo: encuesta.tipo),
                  const Spacer(),
                  if (encuesta.esAnonima)
                    Tooltip(
                      message: 'Encuesta anónima',
                      child: Icon(
                        Icons.visibility_off,
                        size: 18,
                        color: colorScheme.outline,
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 12),

              // Título
              Text(
                encuesta.titulo,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),

              // Descripción
              if (encuesta.descripcion != null &&
                  encuesta.descripcion!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  encuesta.descripcion!,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],

              const SizedBox(height: 12),

              // Stats
              Row(
                children: [
                  _StatChip(
                    icon: Icons.people,
                    value: '${encuesta.totalParticipantes}',
                    label: 'participantes',
                  ),
                  const SizedBox(width: 16),
                  _StatChip(
                    icon: Icons.check_circle_outline,
                    value: '${encuesta.totalRespuestas}',
                    label: 'respuestas',
                  ),
                  const Spacer(),
                  Icon(
                    Icons.chevron_right,
                    color: colorScheme.outline,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EstadoBadge extends StatelessWidget {
  final String estado;

  const _EstadoBadge({required this.estado});

  @override
  Widget build(BuildContext context) {
    final (color, icon, label) = switch (estado) {
      'activa' => (Colors.green, Icons.play_circle, 'Activa'),
      'cerrada' => (Colors.orange, Icons.lock, 'Cerrada'),
      'borrador' => (Colors.grey, Icons.edit, 'Borrador'),
      'archivada' => (Colors.brown, Icons.archive, 'Archivada'),
      _ => (Colors.grey, Icons.help, estado),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _TipoBadge extends StatelessWidget {
  final String tipo;

  const _TipoBadge({required this.tipo});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    final (icon, label) = switch (tipo) {
      'encuesta' => (Icons.poll, 'Encuesta'),
      'formulario' => (Icons.description, 'Formulario'),
      'quiz' => (Icons.quiz, 'Quiz'),
      _ => (Icons.help, tipo),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: colorScheme.secondaryContainer,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: colorScheme.onSecondaryContainer),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: colorScheme.onSecondaryContainer,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;

  const _StatChip({
    required this.icon,
    required this.value,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: colorScheme.outline),
        const SizedBox(width: 4),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: colorScheme.onSurface,
          ),
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: colorScheme.outline,
          ),
        ),
      ],
    );
  }
}

/// Pantalla de detalle de encuesta
class _EncuestaDetalleScreen extends ConsumerStatefulWidget {
  final _Encuesta encuesta;

  const _EncuestaDetalleScreen({required this.encuesta});

  @override
  ConsumerState<_EncuestaDetalleScreen> createState() =>
      _EncuestaDetalleScreenState();
}

class _EncuestaDetalleScreenState
    extends ConsumerState<_EncuestaDetalleScreen> {
  late _Encuesta _encuesta;
  bool _isLoading = true;
  bool _yaParticipo = false;
  bool _isSubmitting = false;
  final Map<int, dynamic> _respuestas = {};
  Map<String, dynamic>? _resultados;

  @override
  void initState() {
    super.initState();
    _encuesta = widget.encuesta;
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() => _isLoading = true);

    try {
      final apiClient = ref.read(apiClientProvider);

      // Cargar detalle completo
      final response = await apiClient.get('/flavor/v1/encuestas/${_encuesta.id}');

      if (response.success && response.data != null) {
        final data = response.data!;
        if (data['data'] != null) {
          setState(() {
            _encuesta = _Encuesta.fromJson(data['data']);
          });
        }
      }

      // Verificar participación
      final participacion = await apiClient.get(
        '/flavor/v1/encuestas/${_encuesta.id}/participacion',
      );

      if (participacion.success && participacion.data != null) {
        final participacionData = participacion.data!;
        setState(() {
          _yaParticipo = participacionData['ya_participo'] ?? false;
        });
      }

      // Cargar resultados si corresponde
      if (_encuesta.mostrarResultados == 'siempre' ||
          (_encuesta.mostrarResultados == 'al_votar' && _yaParticipo) ||
          (_encuesta.mostrarResultados == 'al_cerrar' && _encuesta.estaCerrada)) {
        await _cargarResultados();
      }
    } catch (e) {
      debugPrint('Error cargando detalle: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _cargarResultados() async {
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/flavor/v1/encuestas/${_encuesta.id}/resultados',
      );

      if (response.success && response.data != null) {
        final resultadosData = response.data!;
        setState(() {
          _resultados = resultadosData['data'];
        });
      }
    } catch (e) {
      debugPrint('Error cargando resultados: $e');
    }
  }

  Future<void> _enviarRespuestas() async {
    // Validar campos requeridos
    for (final campo in _encuesta.campos) {
      if (campo.esRequerido && !_respuestas.containsKey(campo.id)) {
        FlavorSnackbar.showError(
          context,
          'Por favor completa: ${campo.etiqueta}',
        );
        return;
      }
    }

    setState(() => _isSubmitting = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/encuestas/${_encuesta.id}/responder',
        data: {'respuestas': _respuestas},
      );

      if (!mounted) return;
      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, '¡Gracias por tu respuesta!');
        setState(() {
          _yaParticipo = true;
        });

        // Cargar resultados si se muestran al votar
        if (_encuesta.mostrarResultados == 'al_votar' ||
            _encuesta.mostrarResultados == 'siempre') {
          await _cargarResultados();
        }
      } else {
        Haptics.error();
        FlavorSnackbar.showError(
          context,
          response.error ?? 'Error al enviar respuesta',
        );
      }
    } catch (e) {
      if (!mounted) return;
      Haptics.error();
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(_encuesta.titulo),
        actions: [
          if (_resultados != null)
            IconButton(
              icon: const Icon(Icons.bar_chart),
              onPressed: () => _mostrarResultados(context),
              tooltip: 'Ver resultados',
            ),
        ],
      ),
      body: _isLoading
          ? const FlavorLoadingState()
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header info
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              _EstadoBadge(estado: _encuesta.estado),
                              const SizedBox(width: 8),
                              _TipoBadge(tipo: _encuesta.tipo),
                            ],
                          ),
                          if (_encuesta.descripcion?.isNotEmpty ?? false) ...[
                            const SizedBox(height: 12),
                            Text(
                              _encuesta.descripcion!,
                              style: theme.textTheme.bodyMedium,
                            ),
                          ],
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Icon(Icons.people,
                                  size: 18, color: colorScheme.outline),
                              const SizedBox(width: 4),
                              Text(
                                '${_encuesta.totalParticipantes} participantes',
                                style: theme.textTheme.bodySmall,
                              ),
                              const SizedBox(width: 16),
                              if (_encuesta.esAnonima) ...[
                                Icon(Icons.visibility_off,
                                    size: 18, color: colorScheme.outline),
                                const SizedBox(width: 4),
                                Text(
                                  'Anónima',
                                  style: theme.textTheme.bodySmall,
                                ),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Estado de participación
                  if (_yaParticipo && !_encuesta.permiteMultiples) ...[
                    Card(
                      color: colorScheme.primaryContainer,
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Icon(
                              Icons.check_circle,
                              color: colorScheme.primary,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                'Ya has participado en esta encuesta',
                                style: TextStyle(
                                  color: colorScheme.onPrimaryContainer,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Campos del formulario
                  if (_encuesta.estaActiva &&
                      (!_yaParticipo || _encuesta.permiteMultiples)) ...[
                    Text(
                      'Preguntas',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ..._encuesta.campos.map(
                      (campo) => _CampoWidget(
                        campo: campo,
                        value: _respuestas[campo.id],
                        onChanged: (value) {
                          setState(() {
                            _respuestas[campo.id] = value;
                          });
                        },
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _isSubmitting ? null : _enviarRespuestas,
                        icon: _isSubmitting
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.send),
                        label: Text(
                          _isSubmitting ? 'Enviando...' : 'Enviar respuestas',
                        ),
                      ),
                    ),
                  ],

                  // Mostrar resultados inline si ya participó
                  if (_resultados != null && _yaParticipo) ...[
                    const SizedBox(height: 24),
                    Text(
                      'Resultados',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _ResultadosWidget(
                      resultados: _resultados!,
                      campos: _encuesta.campos,
                    ),
                  ],
                ],
              ),
            ),
    );
  }

  void _mostrarResultados(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Icon(Icons.bar_chart),
                  const SizedBox(width: 8),
                  Text(
                    'Resultados',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: SingleChildScrollView(
                controller: scrollController,
                padding: const EdgeInsets.all(16),
                child: _ResultadosWidget(
                  resultados: _resultados!,
                  campos: _encuesta.campos,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Widget para renderizar un campo de encuesta
class _CampoWidget extends StatelessWidget {
  final _Campo campo;
  final dynamic value;
  final ValueChanged<dynamic> onChanged;

  const _CampoWidget({
    required this.campo,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    campo.etiqueta,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                if (campo.esRequerido)
                  Text(
                    '*',
                    style: TextStyle(
                      color: theme.colorScheme.error,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
              ],
            ),
            if (campo.descripcion?.isNotEmpty ?? false) ...[
              const SizedBox(height: 4),
              Text(
                campo.descripcion!,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.outline,
                ),
              ),
            ],
            const SizedBox(height: 12),
            _buildInput(context),
          ],
        ),
      ),
    );
  }

  Widget _buildInput(BuildContext context) {
    switch (campo.tipo) {
      case 'texto':
        return TextField(
          decoration: const InputDecoration(
            border: OutlineInputBorder(),
            hintText: 'Escribe tu respuesta',
          ),
          onChanged: onChanged,
        );

      case 'textarea':
        return TextField(
          decoration: const InputDecoration(
            border: OutlineInputBorder(),
            hintText: 'Escribe tu respuesta',
          ),
          maxLines: 4,
          onChanged: onChanged,
        );

      case 'numero':
        return TextField(
          decoration: const InputDecoration(
            border: OutlineInputBorder(),
            hintText: '0',
          ),
          keyboardType: TextInputType.number,
          onChanged: (v) => onChanged(int.tryParse(v)),
        );

      case 'opcion':
      case 'radio':
        return Column(
          children: campo.opciones.map((opcion) {
            return RadioListTile<String>(
              title: Text(opcion),
              value: opcion,
              groupValue: value as String?,
              onChanged: (v) => onChanged(v),
              contentPadding: EdgeInsets.zero,
              visualDensity: VisualDensity.compact,
            );
          }).toList(),
        );

      case 'multiple':
      case 'checkbox':
        final selected = (value as List<String>?) ?? [];
        return Column(
          children: campo.opciones.map((opcion) {
            return CheckboxListTile(
              title: Text(opcion),
              value: selected.contains(opcion),
              onChanged: (checked) {
                final newSelected = List<String>.from(selected);
                if (checked == true) {
                  newSelected.add(opcion);
                } else {
                  newSelected.remove(opcion);
                }
                onChanged(newSelected);
              },
              contentPadding: EdgeInsets.zero,
              visualDensity: VisualDensity.compact,
              controlAffinity: ListTileControlAffinity.leading,
            );
          }).toList(),
        );

      case 'escala':
        final currentValue = (value as int?) ?? 5;
        return Column(
          children: [
            Slider(
              value: currentValue.toDouble(),
              min: 1,
              max: 10,
              divisions: 9,
              label: currentValue.toString(),
              onChanged: (v) => onChanged(v.toInt()),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('1', style: Theme.of(context).textTheme.bodySmall),
                Text(
                  'Valor: $currentValue',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                Text('10', style: Theme.of(context).textTheme.bodySmall),
              ],
            ),
          ],
        );

      case 'si_no':
        return Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () => onChanged('si'),
                style: OutlinedButton.styleFrom(
                  backgroundColor: value == 'si'
                      ? Theme.of(context).colorScheme.primaryContainer
                      : null,
                ),
                child: const Text('Sí'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton(
                onPressed: () => onChanged('no'),
                style: OutlinedButton.styleFrom(
                  backgroundColor: value == 'no'
                      ? Theme.of(context).colorScheme.primaryContainer
                      : null,
                ),
                child: const Text('No'),
              ),
            ),
          ],
        );

      default:
        return TextField(
          decoration: InputDecoration(
            border: const OutlineInputBorder(),
            hintText: 'Respuesta (${campo.tipo})',
          ),
          onChanged: onChanged,
        );
    }
  }
}

/// Widget para mostrar resultados
class _ResultadosWidget extends StatelessWidget {
  final Map<String, dynamic> resultados;
  final List<_Campo> campos;

  const _ResultadosWidget({
    required this.resultados,
    required this.campos,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final porCampo = resultados['por_campo'] as Map<String, dynamic>? ?? {};

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: campos.map((campo) {
        final campoResultados = porCampo[campo.id.toString()];
        if (campoResultados == null) return const SizedBox.shrink();

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  campo.etiqueta,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 12),
                if (campo.tipo == 'opcion' ||
                    campo.tipo == 'radio' ||
                    campo.tipo == 'multiple' ||
                    campo.tipo == 'checkbox' ||
                    campo.tipo == 'si_no')
                  _buildBarChart(context, campoResultados)
                else if (campo.tipo == 'escala')
                  _buildScaleResult(context, campoResultados)
                else
                  _buildTextResults(context, campoResultados),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildBarChart(BuildContext context, Map<String, dynamic> data) {
    final conteo = data['conteo'] as Map<String, dynamic>? ?? {};
    final total = data['total'] as int? ?? 1;
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      children: conteo.entries.map((entry) {
        final porcentaje = total > 0 ? (entry.value as int) / total : 0.0;

        return Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      entry.key,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ),
                  Text(
                    '${entry.value} (${(porcentaje * 100).toStringAsFixed(1)}%)',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              LinearProgressIndicator(
                value: porcentaje,
                backgroundColor: colorScheme.surfaceContainerHighest,
                borderRadius: BorderRadius.circular(4),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildScaleResult(BuildContext context, Map<String, dynamic> data) {
    final promedio = (data['promedio'] as num?)?.toDouble() ?? 0;
    final colorScheme = Theme.of(context).colorScheme;

    return Row(
      children: [
        Expanded(
          child: LinearProgressIndicator(
            value: promedio / 10,
            backgroundColor: colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(4),
          ),
        ),
        const SizedBox(width: 12),
        Text(
          promedio.toStringAsFixed(1),
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
                color: colorScheme.primary,
              ),
        ),
        Text(
          ' / 10',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: colorScheme.outline,
              ),
        ),
      ],
    );
  }

  Widget _buildTextResults(BuildContext context, Map<String, dynamic> data) {
    final respuestas = data['respuestas'] as List<dynamic>? ?? [];

    if (respuestas.isEmpty) {
      return Text(
        'Sin respuestas',
        style: TextStyle(
          color: Theme.of(context).colorScheme.outline,
          fontStyle: FontStyle.italic,
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: respuestas.take(5).map((r) {
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(r.toString()),
        );
      }).toList(),
    );
  }
}

/// Pantalla para crear nueva encuesta
class _CrearEncuestaScreen extends ConsumerStatefulWidget {
  const _CrearEncuestaScreen();

  @override
  ConsumerState<_CrearEncuestaScreen> createState() =>
      _CrearEncuestaScreenState();
}

class _CrearEncuestaScreenState extends ConsumerState<_CrearEncuestaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  String _tipo = 'encuesta';
  bool _esAnonima = false;
  bool _permiteMultiples = false;
  String _mostrarResultados = 'al_votar';
  final List<Map<String, dynamic>> _campos = [];
  bool _isSubmitting = false;

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    super.dispose();
  }

  void _agregarCampo() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => _AgregarCampoSheet(
        onAgregar: (campo) {
          setState(() {
            _campos.add(campo);
          });
        },
      ),
    );
  }

  Future<void> _crearEncuesta() async {
    if (!_formKey.currentState!.validate()) return;

    if (_campos.isEmpty) {
      FlavorSnackbar.showError(
        context,
        'Agrega al menos una pregunta',
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/encuestas',
        data: {
          'titulo': _tituloController.text,
          'descripcion': _descripcionController.text,
          'tipo': _tipo,
          'es_anonima': _esAnonima,
          'permite_multiples': _permiteMultiples,
          'mostrar_resultados': _mostrarResultados,
          'campos': _campos,
          'estado': 'activa',
        },
      );

      if (!mounted) return;
      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, 'Encuesta creada');
        Navigator.pop(context, true);
      } else {
        Haptics.error();
        FlavorSnackbar.showError(
          context,
          response.error ?? 'Error al crear encuesta',
        );
      }
    } catch (e) {
      if (!mounted) return;
      Haptics.error();
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nueva encuesta'),
        actions: [
          TextButton.icon(
            onPressed: _isSubmitting ? null : _crearEncuesta,
            icon: _isSubmitting
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.check),
            label: const Text('Crear'),
          ),
        ],
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
                labelText: 'Título *',
                border: OutlineInputBorder(),
              ),
              validator: (v) =>
                  v?.isEmpty ?? true ? 'El título es requerido' : null,
            ),
            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 16),

            // Tipo
            DropdownButtonFormField<String>(
              value: _tipo,
              decoration: const InputDecoration(
                labelText: 'Tipo',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'encuesta', child: Text('Encuesta')),
                DropdownMenuItem(value: 'formulario', child: Text('Formulario')),
                DropdownMenuItem(value: 'quiz', child: Text('Quiz')),
              ],
              onChanged: (v) => setState(() => _tipo = v!),
            ),
            const SizedBox(height: 16),

            // Opciones
            Card(
              child: Column(
                children: [
                  SwitchListTile(
                    title: const Text('Encuesta anónima'),
                    subtitle: const Text('No se registra quién responde'),
                    value: _esAnonima,
                    onChanged: (v) => setState(() => _esAnonima = v),
                  ),
                  const Divider(height: 1),
                  SwitchListTile(
                    title: const Text('Permitir múltiples respuestas'),
                    subtitle: const Text('El mismo usuario puede responder varias veces'),
                    value: _permiteMultiples,
                    onChanged: (v) => setState(() => _permiteMultiples = v),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Mostrar resultados
            DropdownButtonFormField<String>(
              value: _mostrarResultados,
              decoration: const InputDecoration(
                labelText: 'Mostrar resultados',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'siempre', child: Text('Siempre')),
                DropdownMenuItem(value: 'al_votar', child: Text('Al votar')),
                DropdownMenuItem(value: 'al_cerrar', child: Text('Al cerrar')),
                DropdownMenuItem(value: 'nunca', child: Text('Nunca')),
              ],
              onChanged: (v) => setState(() => _mostrarResultados = v!),
            ),
            const SizedBox(height: 24),

            // Campos
            Row(
              children: [
                Text(
                  'Preguntas (${_campos.length})',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const Spacer(),
                FilledButton.tonalIcon(
                  onPressed: _agregarCampo,
                  icon: const Icon(Icons.add),
                  label: const Text('Agregar'),
                ),
              ],
            ),
            const SizedBox(height: 12),

            if (_campos.isEmpty)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Column(
                    children: [
                      Icon(
                        Icons.quiz_outlined,
                        size: 48,
                        color: Theme.of(context).colorScheme.outline,
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Agrega preguntas a tu encuesta',
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.outline,
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
              ReorderableListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _campos.length,
                onReorder: (oldIndex, newIndex) {
                  setState(() {
                    if (newIndex > oldIndex) newIndex--;
                    final item = _campos.removeAt(oldIndex);
                    _campos.insert(newIndex, item);
                  });
                },
                itemBuilder: (context, index) {
                  final campo = _campos[index];
                  return Card(
                    key: ValueKey(index),
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: const Icon(Icons.drag_handle),
                      title: Text(campo['etiqueta'] ?? ''),
                      subtitle: Text(campo['tipo'] ?? ''),
                      trailing: IconButton(
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () {
                          setState(() {
                            _campos.removeAt(index);
                          });
                        },
                      ),
                    ),
                  );
                },
              ),

            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }
}

/// Sheet para agregar campo
class _AgregarCampoSheet extends StatefulWidget {
  final Function(Map<String, dynamic>) onAgregar;

  const _AgregarCampoSheet({required this.onAgregar});

  @override
  State<_AgregarCampoSheet> createState() => _AgregarCampoSheetState();
}

class _AgregarCampoSheetState extends State<_AgregarCampoSheet> {
  final _etiquetaController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _opcionesController = TextEditingController();
  String _tipo = 'texto';
  bool _esRequerido = true;

  final _tiposConOpciones = ['opcion', 'radio', 'multiple', 'checkbox'];

  @override
  void dispose() {
    _etiquetaController.dispose();
    _descripcionController.dispose();
    _opcionesController.dispose();
    super.dispose();
  }

  void _agregar() {
    if (_etiquetaController.text.isEmpty) {
      FlavorSnackbar.showError(context, 'Ingresa la pregunta');
      return;
    }

    if (_tiposConOpciones.contains(_tipo) &&
        _opcionesController.text.isEmpty) {
      FlavorSnackbar.showError(context, 'Ingresa las opciones');
      return;
    }

    final campo = {
      'tipo': _tipo,
      'etiqueta': _etiquetaController.text,
      'descripcion': _descripcionController.text,
      'es_requerido': _esRequerido,
      if (_tiposConOpciones.contains(_tipo))
        'opciones': _opcionesController.text
            .split('\n')
            .map((e) => e.trim())
            .where((e) => e.isNotEmpty)
            .toList(),
    };

    widget.onAgregar(campo);
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(
                  'Agregar pregunta',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Tipo
            DropdownButtonFormField<String>(
              value: _tipo,
              decoration: const InputDecoration(
                labelText: 'Tipo de pregunta',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'texto', child: Text('Texto corto')),
                DropdownMenuItem(value: 'textarea', child: Text('Texto largo')),
                DropdownMenuItem(value: 'numero', child: Text('Número')),
                DropdownMenuItem(value: 'opcion', child: Text('Opción única')),
                DropdownMenuItem(value: 'multiple', child: Text('Opción múltiple')),
                DropdownMenuItem(value: 'escala', child: Text('Escala (1-10)')),
                DropdownMenuItem(value: 'si_no', child: Text('Sí / No')),
              ],
              onChanged: (v) => setState(() => _tipo = v!),
            ),
            const SizedBox(height: 16),

            // Etiqueta
            TextField(
              controller: _etiquetaController,
              decoration: const InputDecoration(
                labelText: 'Pregunta *',
                border: OutlineInputBorder(),
                hintText: 'Ej: ¿Cuál es tu opinión sobre...?',
              ),
            ),
            const SizedBox(height: 16),

            // Descripción
            TextField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción (opcional)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),

            // Opciones (si aplica)
            if (_tiposConOpciones.contains(_tipo)) ...[
              TextField(
                controller: _opcionesController,
                decoration: const InputDecoration(
                  labelText: 'Opciones (una por línea) *',
                  border: OutlineInputBorder(),
                  hintText: 'Opción 1\nOpción 2\nOpción 3',
                ),
                maxLines: 5,
              ),
              const SizedBox(height: 16),
            ],

            // Requerido
            SwitchListTile(
              title: const Text('Campo requerido'),
              value: _esRequerido,
              onChanged: (v) => setState(() => _esRequerido = v),
              contentPadding: EdgeInsets.zero,
            ),
            const SizedBox(height: 16),

            // Botón agregar
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _agregar,
                icon: const Icon(Icons.add),
                label: const Text('Agregar pregunta'),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}
