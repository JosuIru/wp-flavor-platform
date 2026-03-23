import 'package:flutter/material.dart';
import '../services/offline_manager.dart';

/// Banner de estado offline
class OfflineBanner extends StatelessWidget {
  final OfflineManager offlineManager;
  final VoidCallback? onSyncTap;
  final VoidCallback? onDismiss;
  final bool showPendingCount;
  final bool showSyncButton;

  const OfflineBanner({
    super.key,
    required this.offlineManager,
    this.onSyncTap,
    this.onDismiss,
    this.showPendingCount = true,
    this.showSyncButton = true,
  });

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<ConnectivityState>(
      stream: offlineManager.stateStream,
      initialData: offlineManager.state,
      builder: (context, stateSnapshot) {
        final state = stateSnapshot.data ?? ConnectivityState.online;

        if (state == ConnectivityState.online && offlineManager.pendingCount == 0) {
          return const SizedBox.shrink();
        }

        return StreamBuilder<List<PendingOperation>>(
          stream: offlineManager.queueStream,
          initialData: offlineManager.pendingQueue,
          builder: (context, queueSnapshot) {
            final pendingCount = queueSnapshot.data?.length ?? 0;

            return AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              child: Material(
                color: _getBackgroundColor(state),
                child: SafeArea(
                  bottom: false,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 10,
                    ),
                    child: Row(
                      children: [
                        // Icono
                        _buildIcon(state),
                        const SizedBox(width: 12),

                        // Texto
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                _getTitle(state),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                ),
                              ),
                              if (showPendingCount && pendingCount > 0)
                                Text(
                                  '$pendingCount ${pendingCount == 1 ? 'cambio pendiente' : 'cambios pendientes'}',
                                  style: TextStyle(
                                    color: Colors.white.withOpacity(0.9),
                                    fontSize: 12,
                                  ),
                                ),
                            ],
                          ),
                        ),

                        // Botón de sincronización
                        if (showSyncButton &&
                            state != ConnectivityState.syncing &&
                            pendingCount > 0)
                          TextButton(
                            onPressed: onSyncTap ?? offlineManager.syncPending,
                            style: TextButton.styleFrom(
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                            ),
                            child: const Text('Sincronizar'),
                          ),

                        // Indicador de sincronización
                        if (state == ConnectivityState.syncing)
                          const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation(Colors.white),
                            ),
                          ),

                        // Botón cerrar
                        if (onDismiss != null && state == ConnectivityState.online)
                          IconButton(
                            icon: const Icon(Icons.close, color: Colors.white),
                            onPressed: onDismiss,
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Color _getBackgroundColor(ConnectivityState state) {
    switch (state) {
      case ConnectivityState.offline:
        return Colors.red.shade700;
      case ConnectivityState.syncing:
        return Colors.blue.shade700;
      case ConnectivityState.online:
        return Colors.orange.shade700;
    }
  }

  Widget _buildIcon(ConnectivityState state) {
    IconData icon;
    switch (state) {
      case ConnectivityState.offline:
        icon = Icons.cloud_off;
        break;
      case ConnectivityState.syncing:
        icon = Icons.sync;
        break;
      case ConnectivityState.online:
        icon = Icons.cloud_queue;
        break;
    }

    return Icon(icon, color: Colors.white, size: 22);
  }

  String _getTitle(ConnectivityState state) {
    switch (state) {
      case ConnectivityState.offline:
        return 'Sin conexión';
      case ConnectivityState.syncing:
        return 'Sincronizando...';
      case ConnectivityState.online:
        return 'Cambios pendientes';
    }
  }
}

/// Widget wrapper que muestra banner offline en la parte superior
class OfflineAwareScaffold extends StatelessWidget {
  final OfflineManager offlineManager;
  final PreferredSizeWidget? appBar;
  final Widget body;
  final Widget? floatingActionButton;
  final Widget? bottomNavigationBar;
  final Widget? drawer;
  final Color? backgroundColor;

  const OfflineAwareScaffold({
    super.key,
    required this.offlineManager,
    this.appBar,
    required this.body,
    this.floatingActionButton,
    this.bottomNavigationBar,
    this.drawer,
    this.backgroundColor,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: appBar,
      drawer: drawer,
      floatingActionButton: floatingActionButton,
      bottomNavigationBar: bottomNavigationBar,
      backgroundColor: backgroundColor,
      body: Column(
        children: [
          OfflineBanner(offlineManager: offlineManager),
          Expanded(child: body),
        ],
      ),
    );
  }
}

/// Diálogo de cola de operaciones pendientes
class PendingOperationsDialog extends StatelessWidget {
  final OfflineManager offlineManager;

  const PendingOperationsDialog({
    super.key,
    required this.offlineManager,
  });

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.pending_actions, size: 28),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Operaciones Pendientes',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const Divider(),
            StreamBuilder<List<PendingOperation>>(
              stream: offlineManager.queueStream,
              initialData: offlineManager.pendingQueue,
              builder: (context, snapshot) {
                final operations = snapshot.data ?? [];

                if (operations.isEmpty) {
                  return const Padding(
                    padding: EdgeInsets.all(20),
                    child: Center(
                      child: Text(
                        'No hay operaciones pendientes',
                        style: TextStyle(color: Colors.grey),
                      ),
                    ),
                  );
                }

                return ConstrainedBox(
                  constraints: const BoxConstraints(maxHeight: 300),
                  child: ListView.builder(
                    shrinkWrap: true,
                    itemCount: operations.length,
                    itemBuilder: (context, index) {
                      final op = operations[index];
                      return ListTile(
                        leading: _getOperationIcon(op.type),
                        title: Text(op.module),
                        subtitle: Text(
                          '${op.typeLabel} · ${_formatTime(op.createdAt)}',
                        ),
                        trailing: op.retryCount > 0
                            ? Chip(
                                label: Text('${op.retryCount}'),
                                backgroundColor: Colors.orange.shade100,
                              )
                            : null,
                      );
                    },
                  ),
                );
              },
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  onPressed: () async {
                    await offlineManager.clearQueue();
                    if (context.mounted) Navigator.pop(context);
                  },
                  child: const Text('Limpiar'),
                ),
                const SizedBox(width: 8),
                ElevatedButton(
                  onPressed: offlineManager.isOnline
                      ? () async {
                          await offlineManager.syncPending();
                          if (context.mounted) Navigator.pop(context);
                        }
                      : null,
                  child: const Text('Sincronizar'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _getOperationIcon(String type) {
    IconData icon;
    Color color;

    switch (type) {
      case 'create':
        icon = Icons.add_circle_outline;
        color = Colors.green;
        break;
      case 'update':
        icon = Icons.edit;
        color = Colors.blue;
        break;
      case 'delete':
        icon = Icons.delete_outline;
        color = Colors.red;
        break;
      default:
        icon = Icons.sync;
        color = Colors.grey;
    }

    return Icon(icon, color: color);
  }

  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);

    if (diff.inMinutes < 1) return 'Ahora';
    if (diff.inMinutes < 60) return 'Hace ${diff.inMinutes}m';
    if (diff.inHours < 24) return 'Hace ${diff.inHours}h';
    return 'Hace ${diff.inDays}d';
  }
}
