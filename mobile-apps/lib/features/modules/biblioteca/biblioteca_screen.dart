import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'biblioteca_screen_parts.dart';

class BibliotecaScreen extends ConsumerStatefulWidget {
  const BibliotecaScreen({super.key});

  @override
  ConsumerState<BibliotecaScreen> createState() => _BibliotecaScreenState();
}

class _BibliotecaScreenState extends ConsumerState<BibliotecaScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _futureCatalogo;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMisPrestamos;
  late Future<ApiResponse<Map<String, dynamic>>> _futureReservas;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _futureCatalogo = api.getBibliotecaCatalogo();
    _futureMisPrestamos = api.getBibliotecaMisPrestamos();
    _futureReservas = api.getBibliotecaReservas();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureCatalogo = api.getBibliotecaCatalogo();
      _futureMisPrestamos = api.getBibliotecaMisPrestamos();
      _futureReservas = api.getBibliotecaReservas();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Biblioteca'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.bibliotecaTabCatalog, icon: const Icon(Icons.menu_book)),
              Tab(text: i18n.bibliotecaTabLoans, icon: const Icon(Icons.book)),
              Tab(text: i18n.bibliotecaTabReservations, icon: const Icon(Icons.bookmark)),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildCatalogoTab(i18n),
            _buildMisPrestamosTab(i18n),
            _buildReservasTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildCatalogoTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureCatalogo,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.bibliotecaError,
            onRetry: _refresh,
            icon: Icons.menu_book_outlined,
          );
        }

        final libros = (response.data!['libros'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (libros.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.menu_book_outlined,
            title: i18n.bibliotecaEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: libros.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final libro = libros[index];
              final id = (libro['id'] as num?)?.toInt() ?? 0;
              final titulo = libro['titulo']?.toString() ?? '';
              final autor = libro['autor']?.toString() ?? '';
              final genero = libro['genero']?.toString() ?? '';
              final disponible = libro['disponible'] == true || libro['disponible'] == 1;
              final portada = libro['portada']?.toString() ?? '';
              final ubicacion = libro['ubicacion']?.toString() ?? '';

              return Card(
                elevation: 2,
                clipBehavior: Clip.antiAlias,
                child: InkWell(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => LibroDetailScreen(libroId: id),
                      ),
                    ).then((_) => _refresh());
                  },
                  child: Row(
                    children: [
                      if (portada.isNotEmpty)
                        Image.network(
                          portada,
                          width: 80,
                          height: 120,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _buildPlaceholderCover(),
                        )
                      else
                        _buildPlaceholderCover(),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                titulo,
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                      fontWeight: FontWeight.bold,
                                    ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Text(
                                autor,
                                style: Theme.of(context).textTheme.bodyMedium,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 8),
                              if (genero.isNotEmpty)
                                Chip(
                                  label: Text(genero),
                                  visualDensity: VisualDensity.compact,
                                ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Icon(
                                    disponible ? Icons.check_circle : Icons.cancel,
                                    size: 16,
                                    color: disponible ? Colors.green : Colors.red,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    disponible
                                        ? i18n.bibliotecaAvailable
                                        : i18n.bibliotecaNotAvailable,
                                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                          color: disponible ? Colors.green : Colors.red,
                                        ),
                                  ),
                                  if (ubicacion.isNotEmpty) ...[
                                    const Spacer(),
                                    Icon(Icons.place, size: 14, color: Colors.grey[600]),
                                    const SizedBox(width: 2),
                                    Text(
                                      ubicacion,
                                      style: Theme.of(context).textTheme.bodySmall,
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildPlaceholderCover() {
    return Container(
      width: 80,
      height: 120,
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: const Icon(Icons.menu_book, size: 40),
    );
  }

  Widget _buildMisPrestamosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMisPrestamos,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.bibliotecaLoansError,
            onRetry: _refresh,
            icon: Icons.book_outlined,
          );
        }

        final prestamos = (response.data!['prestamos'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (prestamos.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.book_outlined,
            title: i18n.bibliotecaLoansEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: prestamos.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final prestamo = prestamos[index];
              final id = (prestamo['id'] as num?)?.toInt() ?? 0;
              final libroTitulo = prestamo['libro_titulo']?.toString() ?? '';
              final fechaPrestamo = prestamo['fecha_prestamo']?.toString() ?? '';
              final fechaDevolucion = prestamo['fecha_devolucion']?.toString() ?? '';
              final estado = prestamo['estado']?.toString() ?? 'activo';
              final renovable = prestamo['renovable'] == true || prestamo['renovable'] == 1;

              return Card(
                elevation: 1,
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _getEstadoColor(estado),
                    child: const Icon(Icons.book, color: Colors.white),
                  ),
                  title: Text(libroTitulo),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      Text('${i18n.bibliotecaLoanDate}: $fechaPrestamo'),
                      Text('${i18n.bibliotecaReturnDate}: $fechaDevolucion'),
                      const SizedBox(height: 4),
                      Chip(
                        label: Text(_getEstadoLabel(estado, i18n)),
                        visualDensity: VisualDensity.compact,
                        backgroundColor: _getEstadoColor(estado).withOpacity(0.2),
                      ),
                    ],
                  ),
                  trailing: renovable
                      ? IconButton(
                          icon: const Icon(Icons.refresh),
                          onPressed: () => _renovarPrestamo(context, id),
                          tooltip: i18n.bibliotecaRenew,
                        )
                      : null,
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildReservasTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureReservas,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.bibliotecaReservationsError,
            onRetry: _refresh,
            icon: Icons.bookmark_outline,
          );
        }

        final reservas = (response.data!['reservas'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (reservas.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.bookmark_outline,
            title: i18n.bibliotecaReservationsEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: reservas.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final reserva = reservas[index];
              final id = (reserva['id'] as num?)?.toInt() ?? 0;
              final libroTitulo = reserva['libro_titulo']?.toString() ?? '';
              final fechaReserva = reserva['fecha_reserva']?.toString() ?? '';
              final estado = reserva['estado']?.toString() ?? 'pendiente';

              return Card(
                elevation: 1,
                child: ListTile(
                  leading: const CircleAvatar(
                    child: Icon(Icons.bookmark),
                  ),
                  title: Text(libroTitulo),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      Text('${i18n.bibliotecaReservationDate}: $fechaReserva'),
                      const SizedBox(height: 4),
                      Chip(
                        label: Text(_getEstadoReservaLabel(estado, i18n)),
                        visualDensity: VisualDensity.compact,
                        backgroundColor: _getEstadoReservaColor(estado).withOpacity(0.2),
                      ),
                    ],
                  ),
                  trailing: IconButton(
                    icon: const Icon(Icons.delete_outline),
                    onPressed: () => _cancelarReserva(context, id),
                    tooltip: i18n.bibliotecaCancelReservation,
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }

  Color _getEstadoColor(String estado) {
    switch (estado) {
      case 'activo':
        return Colors.green;
      case 'atrasado':
        return Colors.red;
      case 'devuelto':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }

  String _getEstadoLabel(String estado, AppLocalizations i18n) {
    switch (estado) {
      case 'activo':
        return i18n.bibliotecaStatusActive;
      case 'atrasado':
        return i18n.bibliotecaStatusOverdue;
      case 'devuelto':
        return i18n.bibliotecaStatusReturned;
      default:
        return estado;
    }
  }

  Color _getEstadoReservaColor(String estado) {
    switch (estado) {
      case 'pendiente':
        return Colors.orange;
      case 'disponible':
        return Colors.green;
      case 'cancelada':
        return Colors.grey;
      default:
        return Colors.blue;
    }
  }

  String _getEstadoReservaLabel(String estado, AppLocalizations i18n) {
    switch (estado) {
      case 'pendiente':
        return i18n.bibliotecaReservationPending;
      case 'disponible':
        return i18n.bibliotecaReservationAvailable;
      case 'cancelada':
        return i18n.bibliotecaReservationCancelled;
      default:
        return estado;
    }
  }

  Future<void> _renovarPrestamo(BuildContext context, int prestamoId) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.bibliotecaRenew),
        content: Text(i18n.bibliotecaRenewConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.commonConfirm),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.renovarBibliotecaPrestamo(prestamoId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.bibliotecaRenewSuccess
            : (response.error ?? i18n.bibliotecaRenewError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          _refresh();
        }
      }
    }
  }

  Future<void> _cancelarReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.bibliotecaCancelReservation),
        content: Text(i18n.bibliotecaCancelReservationConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.commonConfirm),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.cancelarBibliotecaReserva(reservaId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.bibliotecaCancelSuccess
            : (response.error ?? i18n.bibliotecaCancelError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          _refresh();
        }
      }
    }
  }
}
