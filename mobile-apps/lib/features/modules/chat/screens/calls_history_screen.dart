import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../../core/widgets/flavor_initials_avatar.dart';
import '../../../../core/widgets/flavor_state_widgets.dart';
import '../../../../core/services/chat_service.dart';
import 'call_screen.dart';
import 'group_info_screen.dart';
import 'create_group_screen.dart';
import '../chat_main_screen.dart';

/// Modelo de registro de llamada
class CallRecord {
  final String id;
  final String recipientId;
  final String recipientName;
  final String? recipientAvatar;
  final bool isVideo;
  final bool isOutgoing;
  final bool isMissed;
  final DateTime timestamp;
  final Duration? duration;

  const CallRecord({
    required this.id,
    required this.recipientId,
    required this.recipientName,
    this.recipientAvatar,
    required this.isVideo,
    required this.isOutgoing,
    required this.isMissed,
    required this.timestamp,
    this.duration,
  });
}

/// Pantalla de historial de llamadas
class CallsHistoryScreen extends ConsumerStatefulWidget {
  const CallsHistoryScreen({super.key});

  @override
  ConsumerState<CallsHistoryScreen> createState() => _CallsHistoryScreenState();
}

class _CallsHistoryScreenState extends ConsumerState<CallsHistoryScreen> {
  List<CallRecord> _calls = [];
  bool _isLoading = true;
  bool _showOnlyMissed = false;

  @override
  void initState() {
    super.initState();
    _loadCalls();
  }

  Future<void> _loadCalls() async {
    setState(() => _isLoading = true);

    try {
      // Simular carga de llamadas
      await Future.delayed(const Duration(milliseconds: 500));

      setState(() {
        _calls = _getMockCalls();
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  List<CallRecord> get _filteredCalls {
    if (_showOnlyMissed) {
      return _calls.where((c) => c.isMissed).toList();
    }
    return _calls;
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Llamadas'),
        actions: [
          IconButton(
            icon: Icon(
              _showOnlyMissed ? Icons.filter_alt : Icons.filter_alt_outlined,
              color: _showOnlyMissed ? colorScheme.primary : null,
            ),
            onPressed: () {
              setState(() => _showOnlyMissed = !_showOnlyMissed);
            },
            tooltip: _showOnlyMissed ? 'Mostrar todas' : 'Solo perdidas',
          ),
          PopupMenuButton<String>(
            onSelected: _handleMenuAction,
            itemBuilder: (context) => [
              const PopupMenuItem(
                value: 'clear',
                child: ListTile(
                  leading: Icon(Icons.delete_sweep),
                  title: Text('Borrar historial'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ],
          ),
        ],
      ),
      body: _isLoading
          ? const FlavorLoadingState()
          : _filteredCalls.isEmpty
              ? _buildEmptyState()
              : _buildCallsList(),
      floatingActionButton: FloatingActionButton(
        onPressed: _startNewCall,
        child: const Icon(Icons.add_call),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            _showOnlyMissed ? Icons.phone_missed : Icons.call_outlined,
            size: 80,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            _showOnlyMissed
                ? 'No hay llamadas perdidas'
                : 'No hay llamadas recientes',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'Tus llamadas aparecerán aquí',
            style: TextStyle(color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }

  Widget _buildCallsList() {
    // Agrupar por fecha
    final groupedCalls = _groupCallsByDate(_filteredCalls);

    return RefreshIndicator(
      onRefresh: _loadCalls,
      child: ListView.builder(
        itemCount: groupedCalls.length,
        itemBuilder: (context, index) {
          final group = groupedCalls[index];
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header de fecha
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Text(
                  group['date'] as String,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
              ),
              // Llamadas del día
              ...(group['calls'] as List<CallRecord>)
                  .map((call) => _buildCallTile(call)),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCallTile(CallRecord call) {
    final colorScheme = Theme.of(context).colorScheme;

    return ListTile(
      leading: CircleAvatar(
        radius: 24,
        backgroundImage: call.recipientAvatar != null
            ? CachedNetworkImageProvider(call.recipientAvatar!)
            : null,
        child: call.recipientAvatar == null
            ? Text(FlavorInitialsAvatar.initialsFor(call.recipientName))
            : null,
      ),
      title: Text(
        call.recipientName,
        style: TextStyle(
          color: call.isMissed ? Colors.red : null,
        ),
      ),
      subtitle: Row(
        children: [
          // Icono de tipo
          Icon(
            call.isOutgoing
                ? (call.isMissed ? Icons.call_made : Icons.call_made)
                : (call.isMissed ? Icons.call_missed : Icons.call_received),
            size: 16,
            color: call.isMissed
                ? Colors.red
                : call.isOutgoing
                    ? Colors.green
                    : colorScheme.outline,
          ),
          const SizedBox(width: 4),

          // Video o audio
          Icon(
            call.isVideo ? Icons.videocam : Icons.call,
            size: 14,
            color: colorScheme.outline,
          ),
          const SizedBox(width: 8),

          // Hora
          Text(
            _formatTime(call.timestamp),
            style: TextStyle(color: colorScheme.outline),
          ),

          // Duración
          if (call.duration != null && !call.isMissed) ...[
            const SizedBox(width: 8),
            Text(
              _formatDuration(call.duration!),
              style: TextStyle(color: colorScheme.outline),
            ),
          ],
        ],
      ),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            icon: Icon(
              call.isVideo ? Icons.videocam : Icons.call,
              color: colorScheme.primary,
            ),
            onPressed: () => _startCall(call.recipientId, call.recipientName,
                call.recipientAvatar, call.isVideo),
          ),
        ],
      ),
      onTap: () => _showCallDetails(call),
    );
  }

  void _handleMenuAction(String action) {
    switch (action) {
      case 'clear':
        _confirmClearHistory();
        break;
    }
  }

  void _confirmClearHistory() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Borrar historial'),
        content: const Text('¿Eliminar todo el historial de llamadas?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              setState(() => _calls.clear());
            },
            child: const Text('Borrar', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  void _startNewCall() async {
    final result = await Navigator.push<List<ChatUser>>(
      context,
      MaterialPageRoute(
        builder: (context) => const ContactPickerScreen(
          title: 'Seleccionar contacto',
          multiSelect: false,
        ),
      ),
    );

    if (result != null && result.isNotEmpty) {
      final user = result.first;
      // Mostrar opciones de llamada
      if (mounted) {
        _showCallTypeDialog(user);
      }
    }
  }

  void _showCallTypeDialog(ChatUser user) {
    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const CircleAvatar(child: Icon(Icons.call)),
              title: Text('Llamar a ${user.name}'),
              subtitle: const Text('Llamada de voz'),
              onTap: () {
                Navigator.pop(context);
                _startCall(user.id, user.name, user.avatarUrl, false);
              },
            ),
            ListTile(
              leading: const CircleAvatar(child: Icon(Icons.videocam)),
              title: Text('Videollamada a ${user.name}'),
              subtitle: const Text('Llamada con video'),
              onTap: () {
                Navigator.pop(context);
                _startCall(user.id, user.name, user.avatarUrl, true);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  void _startCall(String recipientId, String name, String? avatar, bool isVideo) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CallScreen(
          recipientId: recipientId,
          recipientName: name,
          recipientAvatar: avatar,
          isVideo: isVideo,
        ),
      ),
    );
  }

  void _showCallDetails(CallRecord call) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: CircleAvatar(
              radius: 28,
              backgroundImage: call.recipientAvatar != null
                  ? CachedNetworkImageProvider(call.recipientAvatar!)
                  : null,
              child: call.recipientAvatar == null
                  ? Text(FlavorInitialsAvatar.initialsFor(call.recipientName))
                  : null,
            ),
            title: Text(call.recipientName),
            subtitle: Text(_formatFullDate(call.timestamp)),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.call),
            title: const Text('Llamada de voz'),
            onTap: () {
              Navigator.pop(context);
              _startCall(call.recipientId, call.recipientName,
                  call.recipientAvatar, false);
            },
          ),
          ListTile(
            leading: const Icon(Icons.videocam),
            title: const Text('Videollamada'),
            onTap: () {
              Navigator.pop(context);
              _startCall(call.recipientId, call.recipientName,
                  call.recipientAvatar, true);
            },
          ),
          ListTile(
            leading: const Icon(Icons.chat),
            title: const Text('Enviar mensaje'),
            onTap: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ChatMainScreen(
                    conversationId: 'user_${call.recipientId}',
                    name: call.recipientName,
                    avatarUrl: call.recipientAvatar,
                    isGroup: false,
                  ),
                ),
              );
            },
          ),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: const Text('Ver perfil'),
            onTap: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => UserProfileScreen(userId: call.recipientId),
                ),
              );
            },
          ),
          ListTile(
            leading: const Icon(Icons.delete, color: Colors.red),
            title: const Text('Eliminar', style: TextStyle(color: Colors.red)),
            onTap: () {
              Navigator.pop(context);
              setState(() {
                _calls.removeWhere((c) => c.id == call.id);
              });
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  // Helpers
  List<Map<String, dynamic>> _groupCallsByDate(List<CallRecord> calls) {
    final Map<String, List<CallRecord>> grouped = {};

    for (final call in calls) {
      final dateKey = _getDateKey(call.timestamp);
      grouped.putIfAbsent(dateKey, () => []);
      grouped[dateKey]!.add(call);
    }

    return grouped.entries
        .map((e) => {'date': e.key, 'calls': e.value})
        .toList();
  }

  String _getDateKey(DateTime date) {
    final now = DateTime.now();
    final diff = now.difference(date);

    if (diff.inDays == 0) return 'Hoy';
    if (diff.inDays == 1) return 'Ayer';
    if (diff.inDays < 7) {
      const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      return days[date.weekday % 7];
    }

    return '${date.day}/${date.month}/${date.year}';
  }

  String _formatTime(DateTime time) {
    return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
  }

  String _formatFullDate(DateTime date) {
    const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    return '${days[date.weekday % 7]}, ${date.day} ${months[date.month - 1]} - ${_formatTime(date)}';
  }

  String _formatDuration(Duration duration) {
    final hours = duration.inHours;
    final minutes = (duration.inMinutes % 60);
    final seconds = (duration.inSeconds % 60);

    if (hours > 0) {
      return '${hours}h ${minutes}m';
    } else if (minutes > 0) {
      return '${minutes}m ${seconds}s';
    } else {
      return '${seconds}s';
    }
  }

  // Mock data
  List<CallRecord> _getMockCalls() {
    final now = DateTime.now();

    return [
      CallRecord(
        id: '1',
        recipientId: 'user1',
        recipientName: 'María García',
        isVideo: true,
        isOutgoing: true,
        isMissed: false,
        timestamp: now.subtract(const Duration(hours: 1)),
        duration: const Duration(minutes: 15, seconds: 32),
      ),
      CallRecord(
        id: '2',
        recipientId: 'user2',
        recipientName: 'Juan Pérez',
        isVideo: false,
        isOutgoing: false,
        isMissed: true,
        timestamp: now.subtract(const Duration(hours: 3)),
      ),
      CallRecord(
        id: '3',
        recipientId: 'user3',
        recipientName: 'Ana López',
        isVideo: false,
        isOutgoing: true,
        isMissed: false,
        timestamp: now.subtract(const Duration(days: 1, hours: 2)),
        duration: const Duration(minutes: 5, seconds: 12),
      ),
      CallRecord(
        id: '4',
        recipientId: 'user4',
        recipientName: 'Carlos Ruiz',
        isVideo: true,
        isOutgoing: false,
        isMissed: false,
        timestamp: now.subtract(const Duration(days: 2)),
        duration: const Duration(hours: 1, minutes: 23),
      ),
      CallRecord(
        id: '5',
        recipientId: 'user2',
        recipientName: 'Juan Pérez',
        isVideo: false,
        isOutgoing: false,
        isMissed: true,
        timestamp: now.subtract(const Duration(days: 3)),
      ),
    ];
  }
}

/// Widget embebible de historial de llamadas (sin Scaffold)
/// Para usar dentro de TabBarView u otros contenedores
class CallsHistoryWidget extends ConsumerStatefulWidget {
  const CallsHistoryWidget({super.key});

  @override
  ConsumerState<CallsHistoryWidget> createState() => _CallsHistoryWidgetState();
}

class _CallsHistoryWidgetState extends ConsumerState<CallsHistoryWidget> {
  List<CallRecord> _calls = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadCalls();
  }

  Future<void> _loadCalls() async {
    setState(() => _isLoading = true);

    try {
      await Future.delayed(const Duration(milliseconds: 500));
      setState(() {
        _calls = _getMockCalls();
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const FlavorLoadingState();
    }

    if (_calls.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.call_outlined, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            const Text(
              'No hay llamadas recientes',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 8),
            Text(
              'Tus llamadas aparecerán aquí',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadCalls,
      child: ListView.builder(
        itemCount: _calls.length,
        itemBuilder: (context, index) {
          final call = _calls[index];
          return _CallListItem(
            call: call,
            onTap: () => _startCall(call),
            onLongPress: () => _showCallOptions(call),
          );
        },
      ),
    );
  }

  void _startCall(CallRecord call) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CallScreen(
          recipientId: call.recipientId,
          recipientName: call.recipientName,
          recipientAvatar: call.recipientAvatar,
          isVideo: call.isVideo,
          isIncoming: false,
        ),
      ),
    );
  }

  void _showCallOptions(CallRecord call) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: CircleAvatar(
              backgroundImage: call.recipientAvatar != null
                  ? CachedNetworkImageProvider(call.recipientAvatar!)
                  : null,
              child: call.recipientAvatar == null
                  ? Text(FlavorInitialsAvatar.initialsFor(call.recipientName))
                  : null,
            ),
            title: Text(call.recipientName),
            subtitle: Text(call.isVideo ? 'Videollamada' : 'Llamada de voz'),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.call),
            title: const Text('Llamada de voz'),
            onTap: () {
              Navigator.pop(context);
              _startCallWithType(call, false);
            },
          ),
          ListTile(
            leading: const Icon(Icons.videocam),
            title: const Text('Videollamada'),
            onTap: () {
              Navigator.pop(context);
              _startCallWithType(call, true);
            },
          ),
          ListTile(
            leading: const Icon(Icons.chat),
            title: const Text('Enviar mensaje'),
            onTap: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ChatMainScreen(
                    conversationId: 'user_${call.recipientId}',
                    name: call.recipientName,
                    avatarUrl: call.recipientAvatar,
                    isGroup: false,
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  void _startCallWithType(CallRecord call, bool isVideo) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CallScreen(
          recipientId: call.recipientId,
          recipientName: call.recipientName,
          recipientAvatar: call.recipientAvatar,
          isVideo: isVideo,
          isIncoming: false,
        ),
      ),
    );
  }

  List<CallRecord> _getMockCalls() {
    final now = DateTime.now();
    return [
      CallRecord(
        id: '1',
        recipientId: 'user1',
        recipientName: 'María García',
        isVideo: true,
        isOutgoing: true,
        isMissed: false,
        timestamp: now.subtract(const Duration(hours: 1)),
        duration: const Duration(minutes: 15, seconds: 32),
      ),
      CallRecord(
        id: '2',
        recipientId: 'user2',
        recipientName: 'Juan Pérez',
        isVideo: false,
        isOutgoing: false,
        isMissed: true,
        timestamp: now.subtract(const Duration(hours: 3)),
      ),
      CallRecord(
        id: '3',
        recipientId: 'user3',
        recipientName: 'Ana López',
        isVideo: false,
        isOutgoing: true,
        isMissed: false,
        timestamp: now.subtract(const Duration(days: 1)),
        duration: const Duration(minutes: 5, seconds: 12),
      ),
    ];
  }
}

/// Item de lista de llamadas
class _CallListItem extends StatelessWidget {
  final CallRecord call;
  final VoidCallback onTap;
  final VoidCallback onLongPress;

  const _CallListItem({
    required this.call,
    required this.onTap,
    required this.onLongPress,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return ListTile(
      leading: CircleAvatar(
        backgroundImage: call.recipientAvatar != null
            ? CachedNetworkImageProvider(call.recipientAvatar!)
            : null,
        child: call.recipientAvatar == null
            ? Text(FlavorInitialsAvatar.initialsFor(call.recipientName))
            : null,
      ),
      title: Text(
        call.recipientName,
        style: TextStyle(
          fontWeight: call.isMissed ? FontWeight.bold : FontWeight.normal,
        ),
      ),
      subtitle: Row(
        children: [
          Icon(
            call.isOutgoing ? Icons.call_made : Icons.call_received,
            size: 14,
            color: call.isMissed ? Colors.red : Colors.grey,
          ),
          const SizedBox(width: 4),
          Text(
            _formatTime(call.timestamp),
            style: TextStyle(
              color: call.isMissed ? Colors.red : null,
            ),
          ),
          if (call.duration != null) ...[
            const Text(' · '),
            Text(_formatDuration(call.duration!)),
          ],
        ],
      ),
      trailing: IconButton(
        icon: Icon(
          call.isVideo ? Icons.videocam : Icons.call,
          color: colorScheme.primary,
        ),
        onPressed: onTap,
      ),
      onTap: onTap,
      onLongPress: onLongPress,
    );
  }

  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);

    if (diff.inMinutes < 60) {
      return 'Hace ${diff.inMinutes} min';
    } else if (diff.inHours < 24) {
      return 'Hace ${diff.inHours} h';
    } else {
      return '${time.day}/${time.month}';
    }
  }

  String _formatDuration(Duration duration) {
    final minutes = duration.inMinutes;
    final seconds = duration.inSeconds % 60;
    return '${minutes}:${seconds.toString().padLeft(2, '0')}';
  }
}
