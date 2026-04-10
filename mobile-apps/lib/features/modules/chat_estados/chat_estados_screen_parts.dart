part of 'chat_estados_screen.dart';

// =============================================================================
// MODELOS
// =============================================================================

/// Datos completos de estados
class _EstadosData {
  final _ContactoEstados? misEstados;
  final List<_ContactoEstados> contactos;

  _EstadosData({
    this.misEstados,
    required this.contactos,
  });

  factory _EstadosData.fromJson(Map<String, dynamic> json) {
    return _EstadosData(
      misEstados: json['mis_estados'] != null
          ? _ContactoEstados.fromJson(json['mis_estados'])
          : null,
      contactos: (json['contactos'] as List<dynamic>? ?? [])
          .map((contactoJson) => _ContactoEstados.fromJson(contactoJson))
          .toList(),
    );
  }
}

/// Contacto con sus estados
class _ContactoEstados {
  final int usuarioId;
  final String nombre;
  final String avatar;
  final List<_Estado> estados;
  final int sinVer;
  final String? ultimoEstado;

  _ContactoEstados({
    required this.usuarioId,
    required this.nombre,
    required this.avatar,
    required this.estados,
    required this.sinVer,
    this.ultimoEstado,
  });

  factory _ContactoEstados.fromJson(Map<String, dynamic> json) {
    return _ContactoEstados(
      usuarioId: json['usuario_id'] ?? 0,
      nombre: json['autor_nombre'] ?? 'Usuario',
      avatar: json['autor_avatar'] ?? '',
      estados: (json['estados'] as List<dynamic>? ?? [])
          .map((estadoJson) => _Estado.fromJson(estadoJson))
          .toList(),
      sinVer: json['sin_ver'] ?? 0,
      ultimoEstado: json['ultimo_estado'],
    );
  }
}

/// Estado individual
class _Estado {
  final int id;
  final int usuarioId;
  final String tipo;
  final String? contenido;
  final String? mediaUrl;
  final String? mediaThumbnail;
  final int duracionMedia;
  final String colorFondo;
  final String colorTexto;
  final String fuente;
  final double? ubicacionLat;
  final double? ubicacionLng;
  final String? ubicacionNombre;
  final String privacidad;
  final int visualizacionesCount;
  final int reaccionesCount;
  final int respuestasCount;
  final String fechaCreacion;
  final String fechaExpiracion;
  final String? tiempoRelativo;
  final double progreso;
  final bool visto;

  _Estado({
    required this.id,
    required this.usuarioId,
    required this.tipo,
    this.contenido,
    this.mediaUrl,
    this.mediaThumbnail,
    this.duracionMedia = 0,
    this.colorFondo = '#128C7E',
    this.colorTexto = '#FFFFFF',
    this.fuente = 'default',
    this.ubicacionLat,
    this.ubicacionLng,
    this.ubicacionNombre,
    this.privacidad = 'todos',
    this.visualizacionesCount = 0,
    this.reaccionesCount = 0,
    this.respuestasCount = 0,
    required this.fechaCreacion,
    required this.fechaExpiracion,
    this.tiempoRelativo,
    this.progreso = 0,
    this.visto = false,
  });

  factory _Estado.fromJson(Map<String, dynamic> json) {
    return _Estado(
      id: json['id'] ?? 0,
      usuarioId: json['usuario_id'] ?? 0,
      tipo: json['tipo'] ?? 'texto',
      contenido: json['contenido'],
      mediaUrl: json['media_url'],
      mediaThumbnail: json['media_thumbnail'],
      duracionMedia: json['duracion_media'] ?? 0,
      colorFondo: json['color_fondo'] ?? '#128C7E',
      colorTexto: json['color_texto'] ?? '#FFFFFF',
      fuente: json['fuente'] ?? 'default',
      ubicacionLat: (json['ubicacion_lat'] as num?)?.toDouble(),
      ubicacionLng: (json['ubicacion_lng'] as num?)?.toDouble(),
      ubicacionNombre: json['ubicacion_nombre'],
      privacidad: json['privacidad'] ?? 'todos',
      visualizacionesCount: json['visualizaciones_count'] ?? 0,
      reaccionesCount: json['reacciones_count'] ?? 0,
      respuestasCount: json['respuestas_count'] ?? 0,
      fechaCreacion: json['fecha_creacion'] ?? '',
      fechaExpiracion: json['fecha_expiracion'] ?? '',
      tiempoRelativo: json['tiempo_relativo'],
      progreso: (json['progreso'] as num?)?.toDouble() ?? 0,
      visto: (json['visto'] ?? 0) == 1,
    );
  }

  Color get backgroundColor => _parseColor(colorFondo, const Color(0xFF128C7E));
  Color get textColor => _parseColor(colorTexto, Colors.white);

  static Color _parseColor(String hex, Color defaultColor) {
    try {
      final colorHex = hex.replaceFirst('#', '');
      return Color(int.parse('FF$colorHex', radix: 16));
    } catch (_) {
      return defaultColor;
    }
  }
}

// =============================================================================
// WIDGETS
// =============================================================================

/// Tile de mi estado (arriba de la lista)
class _MiEstadoTile extends StatelessWidget {
  final _ContactoEstados? misEstados;
  final VoidCallback onTap;
  final VoidCallback onCrear;

  const _MiEstadoTile({
    this.misEstados,
    required this.onTap,
    required this.onCrear,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final tieneEstados = misEstados != null && misEstados!.estados.isNotEmpty;

    return ListTile(
      leading: Stack(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: tieneEstados
                  ? Border.all(color: colorScheme.primary, width: 2)
                  : null,
              color: tieneEstados ? null : colorScheme.surfaceContainerHighest,
            ),
            child: tieneEstados && misEstados!.estados.first.mediaThumbnail != null
                ? ClipOval(
                    child: Image.network(
                      misEstados!.estados.first.mediaThumbnail!,
                      fit: BoxFit.cover,
                    ),
                  )
                : Icon(
                    Icons.person,
                    size: 32,
                    color: colorScheme.onSurfaceVariant,
                  ),
          ),
          Positioned(
            bottom: 0,
            right: 0,
            child: Container(
              width: 20,
              height: 20,
              decoration: BoxDecoration(
                color: colorScheme.primary,
                shape: BoxShape.circle,
                border: Border.all(
                  color: colorScheme.surface,
                  width: 2,
                ),
              ),
              child: const Icon(
                Icons.add,
                size: 14,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
      title: const Text(
        'Mi estado',
        style: TextStyle(fontWeight: FontWeight.w600),
      ),
      subtitle: Text(
        tieneEstados
            ? 'Toca para ver tu estado'
            : 'Toca para añadir actualización de estado',
        style: TextStyle(
          color: colorScheme.onSurfaceVariant,
          fontSize: 13,
        ),
      ),
      onTap: onTap,
      trailing: tieneEstados
          ? IconButton(
              icon: const Icon(Icons.more_horiz),
              onPressed: onCrear,
            )
          : null,
    );
  }
}

/// Tile de contacto con estados
class _ContactoEstadoTile extends StatelessWidget {
  final _ContactoEstados contacto;
  final VoidCallback onTap;

  const _ContactoEstadoTile({
    required this.contacto,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final tieneSinVer = contacto.sinVer > 0;

    return ListTile(
      leading: Container(
        width: 56,
        height: 56,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: tieneSinVer ? colorScheme.primary : Colors.grey.shade400,
            width: tieneSinVer ? 2 : 1.5,
          ),
        ),
        child: ClipOval(
          child: contacto.avatar.isNotEmpty
              ? Image.network(
                  contacto.avatar,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => _buildAvatarPlaceholder(context),
                )
              : _buildAvatarPlaceholder(context),
        ),
      ),
      title: Text(
        contacto.nombre,
        style: const TextStyle(fontWeight: FontWeight.w500),
      ),
      subtitle: Text(
        contacto.estados.isNotEmpty
            ? contacto.estados.first.tiempoRelativo ?? 'Hace poco'
            : '',
        style: TextStyle(
          color: colorScheme.onSurfaceVariant,
          fontSize: 13,
        ),
      ),
      onTap: onTap,
    );
  }

  Widget _buildAvatarPlaceholder(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: Icon(
        Icons.person,
        size: 32,
        color: Theme.of(context).colorScheme.onSurfaceVariant,
      ),
    );
  }
}

// =============================================================================
// VISOR DE ESTADOS (FULLSCREEN)
// =============================================================================

class _EstadoViewerScreen extends ConsumerStatefulWidget {
  final _ContactoEstados contacto;
  final bool esMio;
  final Function(int) onEstadoVisto;

  const _EstadoViewerScreen({
    required this.contacto,
    required this.esMio,
    required this.onEstadoVisto,
  });

  @override
  ConsumerState<_EstadoViewerScreen> createState() => _EstadoViewerScreenState();
}

class _EstadoViewerScreenState extends ConsumerState<_EstadoViewerScreen>
    with SingleTickerProviderStateMixin {
  late PageController _pageController;
  late AnimationController _progressController;
  int _currentIndex = 0;
  // Campo reservado para futuras funcionalidades (indicador visual de pausa)

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    _progressController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    )..addStatusListener(_onProgressComplete);

    // Encontrar primer estado no visto
    if (!widget.esMio) {
      for (int i = 0; i < widget.contacto.estados.length; i++) {
        if (!widget.contacto.estados[i].visto) {
          _currentIndex = i;
          break;
        }
      }
    }

    _startProgress();
  }

  @override
  void dispose() {
    _pageController.dispose();
    _progressController.dispose();
    super.dispose();
  }

  void _onProgressComplete(AnimationStatus status) {
    if (status == AnimationStatus.completed) {
      _nextEstado();
    }
  }

  void _startProgress() {
    _progressController.reset();
    _progressController.forward();

    // Marcar como visto
    if (!widget.esMio && _currentIndex < widget.contacto.estados.length) {
      final estado = widget.contacto.estados[_currentIndex];
      if (!estado.visto) {
        widget.onEstadoVisto(estado.id);
      }
    }
  }

  void _nextEstado() {
    if (_currentIndex < widget.contacto.estados.length - 1) {
      setState(() => _currentIndex++);
      _pageController.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
      _startProgress();
    } else {
      Navigator.pop(context);
    }
  }

  void _prevEstado() {
    if (_currentIndex > 0) {
      setState(() => _currentIndex--);
      _pageController.previousPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
      _startProgress();
    }
  }

  void _pauseProgress() {
    _progressController.stop();
  }

  void _resumeProgress() {
    _progressController.forward();
  }

  @override
  Widget build(BuildContext context) {
    final estados = widget.contacto.estados;
    if (estados.isEmpty) {
      return Scaffold(
        backgroundColor: Colors.black,
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, color: Colors.white, size: 48),
              const SizedBox(height: 16),
              const Text(
                'Sin estados disponibles',
                style: TextStyle(color: Colors.white),
              ),
              const SizedBox(height: 24),
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Volver'),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTapDown: (_) => _pauseProgress(),
        onTapUp: (_) => _resumeProgress(),
        onTapCancel: _resumeProgress,
        onHorizontalDragEnd: (details) {
          if (details.primaryVelocity != null) {
            if (details.primaryVelocity! < -300) {
              _nextEstado();
            } else if (details.primaryVelocity! > 300) {
              _prevEstado();
            }
          }
        },
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Contenido del estado
            PageView.builder(
              controller: _pageController,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: estados.length,
              itemBuilder: (context, index) {
                return _EstadoContent(estado: estados[index]);
              },
            ),

            // Header con progreso y info
            SafeArea(
              child: Column(
                children: [
                  // Barras de progreso
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 8,
                    ),
                    child: Row(
                      children: List.generate(estados.length, (index) {
                        return Expanded(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 2),
                            child: _ProgressBar(
                              progress: index < _currentIndex
                                  ? 1.0
                                  : index == _currentIndex
                                      ? _progressController.value
                                      : 0.0,
                              animation: index == _currentIndex
                                  ? _progressController
                                  : null,
                            ),
                          ),
                        );
                      }),
                    ),
                  ),

                  // Info del usuario
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 18,
                          backgroundImage: widget.contacto.avatar.isNotEmpty
                              ? NetworkImage(widget.contacto.avatar)
                              : null,
                          child: widget.contacto.avatar.isEmpty
                              ? const Icon(Icons.person, size: 20)
                              : null,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                widget.esMio ? 'Mi estado' : widget.contacto.nombre,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              if (_currentIndex < estados.length)
                                Text(
                                  estados[_currentIndex].tiempoRelativo ?? '',
                                  style: TextStyle(
                                    color: Colors.white.withAlpha(179),
                                    fontSize: 12,
                                  ),
                                ),
                            ],
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, color: Colors.white),
                          onPressed: () => Navigator.pop(context),
                        ),
                        if (widget.esMio)
                          IconButton(
                            icon: const Icon(Icons.delete_outline, color: Colors.white),
                            onPressed: () => _eliminarEstado(estados[_currentIndex].id),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Zonas táctiles para navegar
            Positioned.fill(
              child: Row(
                children: [
                  Expanded(
                    child: GestureDetector(
                      onTap: _prevEstado,
                      behavior: HitTestBehavior.opaque,
                      child: const SizedBox.expand(),
                    ),
                  ),
                  Expanded(
                    child: GestureDetector(
                      onTap: _nextEstado,
                      behavior: HitTestBehavior.opaque,
                      child: const SizedBox.expand(),
                    ),
                  ),
                ],
              ),
            ),

            // Footer con acciones (si no es mío)
            if (!widget.esMio)
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: SafeArea(
                  child: _EstadoActions(
                    estado: estados[_currentIndex],
                    onReply: _responder,
                    onReact: _reaccionar,
                  ),
                ),
              ),

            // Mostrar visualizaciones (si es mío)
            if (widget.esMio)
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: SafeArea(
                  child: _VisualizacionesBar(
                    estado: estados[_currentIndex],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _eliminarEstado(int estadoId) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar estado'),
        content: const Text('¿Eliminar este estado?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmar != true || !mounted) return;

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.delete('/flavor/v1/estados/$estadoId');

      if (!mounted) return;
      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, 'Estado eliminado');
        Navigator.pop(context, true);
      } else {
        FlavorSnackbar.showError(context, response.error ?? 'Error');
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error: $e');
    }
  }

  void _responder() {
    _pauseProgress();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => _ResponderSheet(
        estado: widget.contacto.estados[_currentIndex],
        onSend: (mensaje) async {
          try {
            final apiClient = ref.read(apiClientProvider);
            await apiClient.post(
              '/flavor/v1/estados/${widget.contacto.estados[_currentIndex].id}/responder',
              data: {'mensaje': mensaje},
            );
            if (mounted) {
              Haptics.success();
              FlavorSnackbar.showSuccess(context, 'Respuesta enviada');
            }
          } catch (e) {
            if (mounted) {
              FlavorSnackbar.showError(context, 'Error al enviar');
            }
          }
        },
      ),
    ).then((_) => _resumeProgress());
  }

  void _reaccionar() {
    _pauseProgress();
    showModalBottomSheet(
      context: context,
      builder: (context) => _ReaccionesSheet(
        onSelect: (emoji) async {
          Navigator.pop(context);
          try {
            final apiClient = ref.read(apiClientProvider);
            await apiClient.post(
              '/flavor/v1/estados/${widget.contacto.estados[_currentIndex].id}/reaccion',
              data: {'emoji': emoji},
            );
            if (mounted) {
              Haptics.light();
            }
          } catch (e) {
            debugPrint('Error reaccionando: $e');
          }
          _resumeProgress();
        },
      ),
    ).then((_) => _resumeProgress());
  }
}

/// Contenido del estado
class _EstadoContent extends StatelessWidget {
  final _Estado estado;

  const _EstadoContent({required this.estado});

  @override
  Widget build(BuildContext context) {
    switch (estado.tipo) {
      case 'imagen':
        return _buildImagen();
      case 'video':
        return _buildVideo();
      case 'ubicacion':
        return _buildUbicacion(context);
      case 'audio':
        return _buildAudio(context);
      default:
        return _buildTexto();
    }
  }

  Widget _buildTexto() {
    return Container(
      color: estado.backgroundColor,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Text(
            estado.contenido ?? '',
            style: TextStyle(
              color: estado.textColor,
              fontSize: 24,
              fontWeight: FontWeight.w600,
            ),
            textAlign: TextAlign.center,
          ),
        ),
      ),
    );
  }

  Widget _buildImagen() {
    return Stack(
      fit: StackFit.expand,
      children: [
        if (estado.mediaUrl != null)
          Image.network(
            estado.mediaUrl!,
            fit: BoxFit.contain,
            errorBuilder: (_, __, ___) => const Center(
              child: Icon(Icons.broken_image, color: Colors.white54, size: 64),
            ),
          ),
        if (estado.contenido != null && estado.contenido!.isNotEmpty)
          Positioned(
            bottom: 100,
            left: 16,
            right: 16,
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.black54,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                estado.contenido!,
                style: const TextStyle(color: Colors.white, fontSize: 16),
                textAlign: TextAlign.center,
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildVideo() {
    // Mostrar thumbnail con indicador de video
    return Stack(
      fit: StackFit.expand,
      children: [
        if (estado.mediaThumbnail != null)
          Image.network(
            estado.mediaThumbnail!,
            fit: BoxFit.contain,
          ),
        const Center(
          child: Icon(
            Icons.play_circle_outline,
            color: Colors.white,
            size: 72,
          ),
        ),
      ],
    );
  }

  Widget _buildUbicacion(BuildContext context) {
    return Container(
      color: estado.backgroundColor,
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.location_on,
              color: Colors.white,
              size: 64,
            ),
            const SizedBox(height: 16),
            Text(
              estado.ubicacionNombre ?? 'Ubicación compartida',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              '${estado.ubicacionLat?.toStringAsFixed(4) ?? ''}, ${estado.ubicacionLng?.toStringAsFixed(4) ?? ''}',
              style: TextStyle(color: Colors.white.withAlpha(179)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAudio(BuildContext context) {
    return Container(
      color: estado.backgroundColor,
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(50),
              ),
              child: const Icon(
                Icons.mic,
                color: Colors.white,
                size: 48,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              '${estado.duracionMedia}s',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Nota de voz',
              style: TextStyle(color: Colors.white70),
            ),
          ],
        ),
      ),
    );
  }
}

/// Barra de progreso animada
class _ProgressBar extends StatelessWidget {
  final double progress;
  final Animation<double>? animation;

  const _ProgressBar({
    required this.progress,
    this.animation,
  });

  @override
  Widget build(BuildContext context) {
    if (animation != null) {
      return AnimatedBuilder(
        animation: animation!,
        builder: (context, child) {
          return _buildBar(animation!.value);
        },
      );
    }
    return _buildBar(progress);
  }

  Widget _buildBar(double value) {
    return Container(
      height: 2,
      decoration: BoxDecoration(
        color: Colors.white38,
        borderRadius: BorderRadius.circular(1),
      ),
      child: FractionallySizedBox(
        alignment: Alignment.centerLeft,
        widthFactor: value,
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(1),
          ),
        ),
      ),
    );
  }
}

/// Acciones en el footer del visor
class _EstadoActions extends StatelessWidget {
  final _Estado estado;
  final VoidCallback onReply;
  final VoidCallback onReact;

  const _EstadoActions({
    required this.estado,
    required this.onReply,
    required this.onReact,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(24),
              ),
              child: TextField(
                readOnly: true,
                onTap: onReply,
                decoration: const InputDecoration(
                  hintText: 'Responder...',
                  hintStyle: TextStyle(color: Colors.white70),
                  border: InputBorder.none,
                  contentPadding: EdgeInsets.symmetric(vertical: 12),
                ),
                style: const TextStyle(color: Colors.white),
              ),
            ),
          ),
          const SizedBox(width: 12),
          IconButton(
            icon: const Icon(Icons.favorite_border, color: Colors.white),
            onPressed: onReact,
          ),
        ],
      ),
    );
  }
}

/// Barra de visualizaciones (para mis estados)
class _VisualizacionesBar extends StatelessWidget {
  final _Estado estado;

  const _VisualizacionesBar({required this.estado});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.visibility_outlined,
            color: Colors.white.withAlpha(179),
            size: 20,
          ),
          const SizedBox(width: 8),
          Text(
            '${estado.visualizacionesCount} visualizaciones',
            style: TextStyle(
              color: Colors.white.withAlpha(179),
              fontSize: 14,
            ),
          ),
          if (estado.reaccionesCount > 0) ...[
            const SizedBox(width: 16),
            Icon(
              Icons.favorite,
              color: Colors.white.withAlpha(179),
              size: 20,
            ),
            const SizedBox(width: 4),
            Text(
              '${estado.reaccionesCount}',
              style: TextStyle(
                color: Colors.white.withAlpha(179),
                fontSize: 14,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

/// Sheet para responder
class _ResponderSheet extends StatefulWidget {
  final _Estado estado;
  final Function(String) onSend;

  const _ResponderSheet({
    required this.estado,
    required this.onSend,
  });

  @override
  State<_ResponderSheet> createState() => _ResponderSheetState();
}

class _ResponderSheetState extends State<_ResponderSheet> {
  final _controller = TextEditingController();
  bool _isSending = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _controller,
                autofocus: true,
                decoration: InputDecoration(
                  hintText: 'Escribe una respuesta...',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                ),
                textInputAction: TextInputAction.send,
                onSubmitted: _send,
              ),
            ),
            const SizedBox(width: 8),
            IconButton.filled(
              onPressed: _isSending ? null : () => _send(_controller.text),
              icon: _isSending
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
    );
  }

  void _send(String text) {
    if (text.trim().isEmpty || _isSending) return;

    setState(() => _isSending = true);
    widget.onSend(text.trim());
    Navigator.pop(context);
  }
}

/// Sheet de reacciones
class _ReaccionesSheet extends StatelessWidget {
  final Function(String) onSelect;

  const _ReaccionesSheet({required this.onSelect});

  static const emojis = ['❤️', '😂', '😮', '😢', '😡', '👍', '🔥', '🎉'];

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: emojis.map((emoji) {
          return GestureDetector(
            onTap: () => onSelect(emoji),
            child: Text(
              emoji,
              style: const TextStyle(fontSize: 32),
            ),
          );
        }).toList(),
      ),
    );
  }
}

// =============================================================================
// CREAR ESTADO
// =============================================================================

class _CrearEstadoScreen extends ConsumerStatefulWidget {
  const _CrearEstadoScreen();

  @override
  ConsumerState<_CrearEstadoScreen> createState() => _CrearEstadoScreenState();
}

class _CrearEstadoScreenState extends ConsumerState<_CrearEstadoScreen> {
  final _contenidoController = TextEditingController();
  final String _tipo = 'texto';
  String _colorFondo = '#128C7E';
  String _privacidad = 'todos';
  bool _isSubmitting = false;

  static const colores = [
    '#128C7E', '#25D366', '#075E54', '#34B7F1',
    '#E91E63', '#9C27B0', '#673AB7', '#3F51B5',
  ];

  @override
  void dispose() {
    _contenidoController.dispose();
    super.dispose();
  }

  Color _parseColor(String hex) {
    try {
      final colorHex = hex.replaceFirst('#', '');
      return Color(int.parse('FF$colorHex', radix: 16));
    } catch (_) {
      return const Color(0xFF128C7E);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorFondoActual = _parseColor(_colorFondo);

    return Scaffold(
      backgroundColor: colorFondoActual,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        title: const Text('Nuevo estado'),
        actions: [
          TextButton.icon(
            onPressed: _isSubmitting ? null : _publicar,
            icon: _isSubmitting
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.send, color: Colors.white),
            label: const Text(
              'Publicar',
              style: TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          // Contenido
          Expanded(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: TextField(
                  controller: _contenidoController,
                  maxLines: null,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.w600,
                  ),
                  decoration: const InputDecoration(
                    hintText: 'Escribe tu estado...',
                    hintStyle: TextStyle(color: Colors.white54),
                    border: InputBorder.none,
                  ),
                ),
              ),
            ),
          ),

          // Selector de color
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.black26,
            child: Column(
              children: [
                // Colores
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: colores.map((color) {
                    final isSelected = color == _colorFondo;
                    return GestureDetector(
                      onTap: () => setState(() => _colorFondo = color),
                      child: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: _parseColor(color),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: isSelected ? Colors.white : Colors.transparent,
                            width: 3,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),

                const SizedBox(height: 16),

                // Privacidad
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.lock_outline, color: Colors.white70, size: 20),
                    const SizedBox(width: 8),
                    DropdownButton<String>(
                      value: _privacidad,
                      dropdownColor: Colors.grey.shade800,
                      style: const TextStyle(color: Colors.white),
                      underline: const SizedBox(),
                      items: const [
                        DropdownMenuItem(
                          value: 'todos',
                          child: Text('Todos mis contactos'),
                        ),
                        DropdownMenuItem(
                          value: 'contactos_excepto',
                          child: Text('Mis contactos excepto...'),
                        ),
                        DropdownMenuItem(
                          value: 'solo_compartir',
                          child: Text('Solo compartir con...'),
                        ),
                      ],
                      onChanged: (v) {
                        if (v != null) setState(() => _privacidad = v);
                      },
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _publicar() async {
    final contenido = _contenidoController.text.trim();
    if (contenido.isEmpty) {
      FlavorSnackbar.showError(context, 'Escribe algo para tu estado');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/estados',
        data: {
          'tipo': _tipo,
          'contenido': contenido,
          'color_fondo': _colorFondo,
          'color_texto': '#FFFFFF',
          'privacidad': _privacidad,
        },
      );

      if (!mounted) return;

      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, 'Estado publicado');
        Navigator.pop(context, true);
      } else {
        FlavorSnackbar.showError(context, response.error ?? 'Error al publicar');
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }
}
