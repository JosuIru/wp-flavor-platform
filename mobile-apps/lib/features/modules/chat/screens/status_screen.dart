import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../../core/services/chat_service.dart';

/// Pantalla de estados tipo WhatsApp Stories
class StatusScreen extends ConsumerStatefulWidget {
  const StatusScreen({super.key});

  @override
  ConsumerState<StatusScreen> createState() => _StatusScreenState();
}

class _StatusScreenState extends ConsumerState<StatusScreen> {
  final ChatService _chatService = ChatService();
  List<ChatStatus> _statuses = [];
  List<ChatStatus> _myStatuses = [];
  bool _isLoading = true;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _loadStatuses();
  }

  Future<void> _loadStatuses() async {
    setState(() => _isLoading = true);

    try {
      final statuses = await _chatService.getStatuses();
      final myStatuses = await _chatService.getMyStatuses();

      setState(() {
        _statuses = statuses;
        _myStatuses = myStatuses;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  // Agrupar estados por usuario
  Map<String, List<ChatStatus>> get _groupedStatuses {
    final grouped = <String, List<ChatStatus>>{};
    for (final status in _statuses.where((s) => !s.isExpired)) {
      grouped.putIfAbsent(status.userId, () => []);
      grouped[status.userId]!.add(status);
    }
    return grouped;
  }

  Future<void> _createTextStatus() async {
    final result = await Navigator.push<Map<String, dynamic>>(
      context,
      MaterialPageRoute(
        builder: (_) => const CreateTextStatusScreen(),
      ),
    );

    if (result != null) {
      setState(() => _isLoading = true);

      await _chatService.createStatus(
        type: StatusType.text,
        content: result['text'],
        backgroundColor: result['backgroundColor'],
      );

      await _loadStatuses();
    }
  }

  Future<void> _createImageStatus() async {
    final image = await _imagePicker.pickImage(source: ImageSource.gallery);

    if (image != null) {
      final caption = await _showCaptionDialog();

      setState(() => _isLoading = true);

      // TODO: Subir imagen y obtener URL
      await _chatService.createStatus(
        type: StatusType.image,
        content: caption ?? '',
        mediaUrl: image.path,
      );

      await _loadStatuses();
    }
  }

  Future<String?> _showCaptionDialog() async {
    String caption = '';

    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Añadir descripción'),
        content: TextField(
          autofocus: true,
          onChanged: (value) => caption = value,
          decoration: const InputDecoration(
            hintText: 'Escribe algo...',
            border: OutlineInputBorder(),
          ),
          maxLines: 3,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Omitir'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, caption),
            child: const Text('Añadir'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    return RefreshIndicator(
      onRefresh: _loadStatuses,
      child: ListView(
        children: [
          // Mi estado
          _buildMyStatusSection(),

          const Divider(),

          // Actualizaciones recientes
          if (_groupedStatuses.isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: Text(
                'Actualizaciones recientes',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: colorScheme.primary,
                ),
              ),
            ),
            ..._groupedStatuses.entries.map((entry) => _buildUserStatusTile(
              entry.key,
              entry.value,
            )),
          ] else
            _buildEmptyState(),
        ],
      ),
    );
  }

  Widget _buildMyStatusSection() {
    return ListTile(
      leading: Stack(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: Theme.of(context).colorScheme.primaryContainer,
            backgroundImage: _chatService.currentUser?.avatarUrl != null
                ? CachedNetworkImageProvider(_chatService.currentUser!.avatarUrl!)
                : null,
            child: _chatService.currentUser?.avatarUrl == null
                ? const Icon(Icons.person, size: 28)
                : null,
          ),
          if (_myStatuses.isEmpty)
            Positioned(
              bottom: 0,
              right: 0,
              child: CircleAvatar(
                radius: 12,
                backgroundColor: Theme.of(context).colorScheme.primary,
                child: const Icon(Icons.add, size: 16, color: Colors.white),
              ),
            )
          else
            Positioned(
              bottom: 0,
              right: 0,
              child: Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primary,
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    width: 2,
                  ),
                ),
                child: Center(
                  child: Text(
                    '${_myStatuses.length}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
      title: const Text('Mi estado'),
      subtitle: Text(
        _myStatuses.isEmpty
            ? 'Toca para añadir un estado'
            : 'Toca para ver tu estado',
      ),
      onTap: () {
        if (_myStatuses.isEmpty) {
          _showCreateStatusOptions();
        } else {
          _viewStatuses(_myStatuses, isMyStatus: true);
        }
      },
    );
  }

  Widget _buildUserStatusTile(String odp, List<ChatStatus> statuses) {
    final firstStatus = statuses.first;
    final hasUnviewed = statuses.any((s) => !s.viewerIds.contains(_chatService.currentUser?.id));

    return ListTile(
      leading: CustomPaint(
        painter: StatusRingPainter(
          statusCount: statuses.length,
          viewedCount: statuses.where((s) => s.viewerIds.contains(_chatService.currentUser?.id)).length,
          primaryColor: Theme.of(context).colorScheme.primary,
          viewedColor: Colors.grey,
        ),
        child: Padding(
          padding: const EdgeInsets.all(3),
          child: CircleAvatar(
            radius: 25,
            backgroundImage: firstStatus.userAvatar != null
                ? CachedNetworkImageProvider(firstStatus.userAvatar!)
                : null,
            child: firstStatus.userAvatar == null
                ? Text(firstStatus.userName[0].toUpperCase())
                : null,
          ),
        ),
      ),
      title: Text(
        firstStatus.userName,
        style: TextStyle(
          fontWeight: hasUnviewed ? FontWeight.bold : FontWeight.normal,
        ),
      ),
      subtitle: Text(_formatTime(firstStatus.createdAt)),
      onTap: () => _viewStatuses(statuses),
    );
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        children: [
          Icon(
            Icons.camera_alt_outlined,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          const Text(
            'No hay estados recientes',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'Los estados de tus contactos aparecerán aquí',
            style: TextStyle(color: Colors.grey[600]),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  void _showCreateStatusOptions() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: const CircleAvatar(
              backgroundColor: Colors.green,
              child: Icon(Icons.text_fields, color: Colors.white),
            ),
            title: const Text('Estado de texto'),
            subtitle: const Text('Crea un estado con texto y color'),
            onTap: () {
              Navigator.pop(context);
              _createTextStatus();
            },
          ),
          ListTile(
            leading: const CircleAvatar(
              backgroundColor: Colors.purple,
              child: Icon(Icons.photo, color: Colors.white),
            ),
            title: const Text('Foto'),
            subtitle: const Text('Comparte una foto de tu galería'),
            onTap: () {
              Navigator.pop(context);
              _createImageStatus();
            },
          ),
          ListTile(
            leading: const CircleAvatar(
              backgroundColor: Colors.red,
              child: Icon(Icons.camera_alt, color: Colors.white),
            ),
            title: const Text('Cámara'),
            subtitle: const Text('Toma una foto ahora'),
            onTap: () async {
              Navigator.pop(context);
              final image = await _imagePicker.pickImage(source: ImageSource.camera);
              if (image != null) {
                // TODO: Crear estado con foto
              }
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  void _viewStatuses(List<ChatStatus> statuses, {bool isMyStatus = false}) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => StatusViewerScreen(
          statuses: statuses,
          isMyStatus: isMyStatus,
          onStatusViewed: (statusId) async {
            await _chatService.viewStatus(statusId);
          },
          onStatusDeleted: isMyStatus
              ? (statusId) async {
                  await _chatService.deleteStatus(statusId);
                  await _loadStatuses();
                }
              : null,
        ),
      ),
    );
  }

  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);

    if (diff.inMinutes < 1) return 'Ahora';
    if (diff.inMinutes < 60) return 'Hace ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'Hace ${diff.inHours} h';

    return 'Hace ${diff.inDays} días';
  }
}

/// Painter para el anillo de estados
class StatusRingPainter extends CustomPainter {
  final int statusCount;
  final int viewedCount;
  final Color primaryColor;
  final Color viewedColor;

  StatusRingPainter({
    required this.statusCount,
    required this.viewedCount,
    required this.primaryColor,
    required this.viewedColor,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = min(size.width, size.height) / 2;

    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;

    if (statusCount == 1) {
      paint.color = viewedCount == 1 ? viewedColor : primaryColor;
      canvas.drawCircle(center, radius, paint);
    } else {
      final gapAngle = 0.1;
      final arcAngle = (2 * pi - (statusCount * gapAngle)) / statusCount;

      for (int i = 0; i < statusCount; i++) {
        final startAngle = -pi / 2 + i * (arcAngle + gapAngle);
        paint.color = i < viewedCount ? viewedColor : primaryColor;

        canvas.drawArc(
          Rect.fromCircle(center: center, radius: radius),
          startAngle,
          arcAngle,
          false,
          paint,
        );
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}

/// Pantalla para crear estado de texto
class CreateTextStatusScreen extends StatefulWidget {
  const CreateTextStatusScreen({super.key});

  @override
  State<CreateTextStatusScreen> createState() => _CreateTextStatusScreenState();
}

class _CreateTextStatusScreenState extends State<CreateTextStatusScreen> {
  final TextEditingController _textController = TextEditingController();
  String _backgroundColor = '#128C7E';

  final List<String> _colors = [
    '#128C7E', '#075E54', '#25D366', '#34B7F1',
    '#9C27B0', '#E91E63', '#F44336', '#FF9800',
    '#2196F3', '#607D8B', '#795548', '#000000',
  ];

  @override
  void dispose() {
    _textController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(int.parse(_backgroundColor.replaceFirst('#', '0xFF'))),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.emoji_emotions_outlined, color: Colors.white),
            onPressed: () {
              // TODO: Mostrar selector de emojis
            },
          ),
          IconButton(
            icon: const Icon(Icons.text_fields, color: Colors.white),
            onPressed: () {
              // TODO: Cambiar fuente
            },
          ),
          IconButton(
            icon: const Icon(Icons.palette, color: Colors.white),
            onPressed: _showColorPicker,
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: TextField(
                  controller: _textController,
                  autofocus: true,
                  textAlign: TextAlign.center,
                  maxLines: null,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                  decoration: const InputDecoration(
                    hintText: 'Escribe tu estado...',
                    hintStyle: TextStyle(color: Colors.white54),
                    border: InputBorder.none,
                  ),
                  onChanged: (_) => setState(() {}),
                ),
              ),
            ),
          ),

          // Colores
          Container(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: _colors.map((color) {
                final isSelected = _backgroundColor == color;
                return GestureDetector(
                  onTap: () => setState(() => _backgroundColor = color),
                  child: Container(
                    width: 32,
                    height: 32,
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    decoration: BoxDecoration(
                      color: Color(int.parse(color.replaceFirst('#', '0xFF'))),
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isSelected ? Colors.white : Colors.transparent,
                        width: 2,
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
      floatingActionButton: _textController.text.trim().isNotEmpty
          ? FloatingActionButton(
              onPressed: () {
                Navigator.pop(context, {
                  'text': _textController.text.trim(),
                  'backgroundColor': _backgroundColor,
                });
              },
              child: const Icon(Icons.send),
            )
          : null,
    );
  }

  void _showColorPicker() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Seleccionar color',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 12,
              runSpacing: 12,
              children: _colors.map((color) {
                return GestureDetector(
                  onTap: () {
                    setState(() => _backgroundColor = color);
                    Navigator.pop(context);
                  },
                  child: Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: Color(int.parse(color.replaceFirst('#', '0xFF'))),
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

/// Visor de estados
class StatusViewerScreen extends StatefulWidget {
  final List<ChatStatus> statuses;
  final bool isMyStatus;
  final Future<void> Function(String statusId) onStatusViewed;
  final Future<void> Function(String statusId)? onStatusDeleted;

  const StatusViewerScreen({
    super.key,
    required this.statuses,
    this.isMyStatus = false,
    required this.onStatusViewed,
    this.onStatusDeleted,
  });

  @override
  State<StatusViewerScreen> createState() => _StatusViewerScreenState();
}

class _StatusViewerScreenState extends State<StatusViewerScreen>
    with SingleTickerProviderStateMixin {
  int _currentIndex = 0;
  late AnimationController _progressController;
  bool _isPaused = false;

  @override
  void initState() {
    super.initState();
    _progressController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    );

    _progressController.addStatusListener((status) {
      if (status == AnimationStatus.completed) {
        _nextStatus();
      }
    });

    _startProgress();
    _viewCurrentStatus();
  }

  @override
  void dispose() {
    _progressController.dispose();
    super.dispose();
  }

  void _startProgress() {
    _progressController.forward(from: 0);
  }

  void _viewCurrentStatus() {
    if (!widget.isMyStatus && _currentIndex < widget.statuses.length) {
      widget.onStatusViewed(widget.statuses[_currentIndex].id);
    }
  }

  void _nextStatus() {
    if (_currentIndex < widget.statuses.length - 1) {
      setState(() => _currentIndex++);
      _startProgress();
      _viewCurrentStatus();
    } else {
      Navigator.pop(context);
    }
  }

  void _previousStatus() {
    if (_currentIndex > 0) {
      setState(() => _currentIndex--);
      _startProgress();
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = widget.statuses[_currentIndex];

    return Scaffold(
      backgroundColor: status.type == StatusType.text
          ? Color(int.parse((status.backgroundColor ?? '#128C7E').replaceFirst('#', '0xFF')))
          : Colors.black,
      body: GestureDetector(
        onTapDown: (_) {
          _progressController.stop();
          setState(() => _isPaused = true);
        },
        onTapUp: (_) {
          _progressController.forward();
          setState(() => _isPaused = false);
        },
        onTapCancel: () {
          _progressController.forward();
          setState(() => _isPaused = false);
        },
        onHorizontalDragEnd: (details) {
          if (details.primaryVelocity! < 0) {
            _nextStatus();
          } else if (details.primaryVelocity! > 0) {
            _previousStatus();
          }
        },
        child: Stack(
          children: [
            // Contenido
            _buildStatusContent(status),

            // Barras de progreso
            Positioned(
              top: MediaQuery.of(context).padding.top + 8,
              left: 8,
              right: 8,
              child: Row(
                children: List.generate(widget.statuses.length, (index) {
                  return Expanded(
                    child: Container(
                      height: 3,
                      margin: const EdgeInsets.symmetric(horizontal: 2),
                      child: index == _currentIndex
                          ? AnimatedBuilder(
                              animation: _progressController,
                              builder: (context, child) {
                                return LinearProgressIndicator(
                                  value: _progressController.value,
                                  backgroundColor: Colors.white30,
                                  valueColor: const AlwaysStoppedAnimation(Colors.white),
                                );
                              },
                            )
                          : Container(
                              color: index < _currentIndex
                                  ? Colors.white
                                  : Colors.white30,
                            ),
                    ),
                  );
                }),
              ),
            ),

            // Header
            Positioned(
              top: MediaQuery.of(context).padding.top + 20,
              left: 8,
              right: 8,
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 20,
                    backgroundImage: status.userAvatar != null
                        ? CachedNetworkImageProvider(status.userAvatar!)
                        : null,
                    child: status.userAvatar == null
                        ? Text(status.userName[0])
                        : null,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.isMyStatus ? 'Mi estado' : status.userName,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          _formatTime(status.createdAt),
                          style: const TextStyle(
                            color: Colors.white70,
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
                  if (widget.isMyStatus)
                    PopupMenuButton<String>(
                      icon: const Icon(Icons.more_vert, color: Colors.white),
                      onSelected: (action) async {
                        if (action == 'delete' && widget.onStatusDeleted != null) {
                          await widget.onStatusDeleted!(status.id);
                          if (widget.statuses.length == 1) {
                            if (mounted) Navigator.pop(context);
                          } else {
                            _nextStatus();
                          }
                        }
                      },
                      itemBuilder: (context) => [
                        const PopupMenuItem(
                          value: 'delete',
                          child: Row(
                            children: [
                              Icon(Icons.delete, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Eliminar'),
                            ],
                          ),
                        ),
                      ],
                    ),
                ],
              ),
            ),

            // Footer con viewers
            if (widget.isMyStatus)
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [Colors.transparent, Colors.black54],
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.visibility, color: Colors.white, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        '${status.viewCount} vistas',
                        style: const TextStyle(color: Colors.white),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusContent(ChatStatus status) {
    switch (status.type) {
      case StatusType.text:
        return Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Text(
              status.content,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        );

      case StatusType.image:
        return Center(
          child: status.mediaUrl != null
              ? CachedNetworkImage(
                  imageUrl: status.mediaUrl!,
                  fit: BoxFit.contain,
                )
              : const Icon(Icons.broken_image, size: 64, color: Colors.white54),
        );

      case StatusType.video:
        // TODO: Implementar video player
        return Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.play_circle_outline, size: 64, color: Colors.white),
              const SizedBox(height: 16),
              Text(
                status.content,
                style: const TextStyle(color: Colors.white),
              ),
            ],
          ),
        );
    }
  }

  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);

    if (diff.inMinutes < 1) return 'Ahora';
    if (diff.inMinutes < 60) return 'Hace ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'Hace ${diff.inHours} h';

    return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
  }
}
