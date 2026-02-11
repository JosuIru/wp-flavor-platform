import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

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
    final i18n = AppLocalizations.of(context)!;

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
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.bibliotecaError));
        }

        final libros = (response.data!['libros'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (libros.isEmpty) {
          return Center(child: Text(i18n.bibliotecaEmpty));
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
      color: Theme.of(context).colorScheme.surfaceVariant,
      child: const Icon(Icons.menu_book, size: 40),
    );
  }

  Widget _buildMisPrestamosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMisPrestamos,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.bibliotecaLoansError));
        }

        final prestamos = (response.data!['prestamos'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (prestamos.isEmpty) {
          return Center(child: Text(i18n.bibliotecaLoansEmpty));
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
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.bibliotecaReservationsError));
        }

        final reservas = (response.data!['reservas'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (reservas.isEmpty) {
          return Center(child: Text(i18n.bibliotecaReservationsEmpty));
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
    final i18n = AppLocalizations.of(context)!;
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          _refresh();
        }
      }
    }
  }

  Future<void> _cancelarReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context)!;
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          _refresh();
        }
      }
    }
  }
}

/// Pantalla de detalle de un libro
class LibroDetailScreen extends ConsumerStatefulWidget {
  final int libroId;

  const LibroDetailScreen({
    super.key,
    required this.libroId,
  });

  @override
  ConsumerState<LibroDetailScreen> createState() => _LibroDetailScreenState();
}

class _LibroDetailScreenState extends ConsumerState<LibroDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getBibliotecaLibro(widget.libroId);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.bibliotecaError));
          }

          final libro = response.data!['libro'] as Map<String, dynamic>? ?? {};
          final titulo = libro['titulo']?.toString() ?? '';
          final autor = libro['autor']?.toString() ?? '';
          final isbn = libro['isbn']?.toString() ?? '';
          final editorial = libro['editorial']?.toString() ?? '';
          final anio = libro['anio']?.toString() ?? '';
          final genero = libro['genero']?.toString() ?? '';
          final sinopsis = libro['sinopsis']?.toString() ?? '';
          final portada = libro['portada']?.toString() ?? '';
          final disponible = libro['disponible'] == true || libro['disponible'] == 1;
          final ubicacion = libro['ubicacion']?.toString() ?? '';
          final ejemplaresDisponibles = (libro['ejemplares_disponibles'] as num?)?.toInt() ?? 0;
          final ejemplaresTotales = (libro['ejemplares_totales'] as num?)?.toInt() ?? 0;

          return CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: 300,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  title: Text(
                    titulo,
                    style: const TextStyle(
                      color: Colors.white,
                      shadows: [
                        Shadow(
                          offset: Offset(0, 1),
                          blurRadius: 3,
                          color: Colors.black54,
                        ),
                      ],
                    ),
                  ),
                  background: portada.isNotEmpty
                      ? Stack(
                          fit: StackFit.expand,
                          children: [
                            Image.network(
                              portada,
                              fit: BoxFit.cover,
                            ),
                            Container(
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                  colors: [
                                    Colors.transparent,
                                    Colors.black.withOpacity(0.7),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        )
                      : Container(
                          color: Theme.of(context).colorScheme.surfaceVariant,
                          child: const Icon(Icons.menu_book, size: 100),
                        ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Información básica
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (autor.isNotEmpty) ...[
                                Row(
                                  children: [
                                    const Icon(Icons.person, size: 20),
                                    const SizedBox(width: 8),
                                    Text(
                                      i18n.bibliotecaAuthor,
                                      style: Theme.of(context).textTheme.labelMedium,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  autor,
                                  style: Theme.of(context).textTheme.bodyLarge,
                                ),
                                const SizedBox(height: 12),
                              ],
                              if (isbn.isNotEmpty) ...[
                                Row(
                                  children: [
                                    const Icon(Icons.qr_code, size: 20),
                                    const SizedBox(width: 8),
                                    Text(
                                      'ISBN',
                                      style: Theme.of(context).textTheme.labelMedium,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  isbn,
                                  style: Theme.of(context).textTheme.bodyMedium,
                                ),
                                const SizedBox(height: 12),
                              ],
                              if (editorial.isNotEmpty || anio.isNotEmpty) ...[
                                Row(
                                  children: [
                                    const Icon(Icons.business, size: 20),
                                    const SizedBox(width: 8),
                                    Text(
                                      i18n.bibliotecaPublisher,
                                      style: Theme.of(context).textTheme.labelMedium,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '$editorial${anio.isNotEmpty ? ' ($anio)' : ''}',
                                  style: Theme.of(context).textTheme.bodyMedium,
                                ),
                                const SizedBox(height: 12),
                              ],
                              if (genero.isNotEmpty) ...[
                                Row(
                                  children: [
                                    const Icon(Icons.category, size: 20),
                                    const SizedBox(width: 8),
                                    Text(
                                      i18n.bibliotecaGenre,
                                      style: Theme.of(context).textTheme.labelMedium,
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Chip(label: Text(genero)),
                              ],
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Sinopsis
                      if (sinopsis.isNotEmpty) ...[
                        Text(
                          i18n.bibliotecaSynopsis,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Text(
                              sinopsis,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Disponibilidad
                      Text(
                        i18n.bibliotecaAvailabilityTitle,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      Card(
                        color: disponible
                            ? Colors.green.withOpacity(0.1)
                            : Colors.red.withOpacity(0.1),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    disponible ? Icons.check_circle : Icons.cancel,
                                    color: disponible ? Colors.green : Colors.red,
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    disponible
                                        ? i18n.bibliotecaAvailable
                                        : i18n.bibliotecaNotAvailable,
                                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                          color: disponible ? Colors.green : Colors.red,
                                          fontWeight: FontWeight.bold,
                                        ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              LinearProgressIndicator(
                                value: ejemplaresTotales > 0
                                    ? ejemplaresDisponibles / ejemplaresTotales
                                    : 0,
                                backgroundColor: Colors.grey[300],
                                color: disponible ? Colors.green : Colors.red,
                              ),
                              const SizedBox(height: 8),
                              Text(
                                '$ejemplaresDisponibles ${i18n.bibliotecaOf} $ejemplaresTotales ${i18n.bibliotecaCopies}',
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                              if (ubicacion.isNotEmpty) ...[
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    const Icon(Icons.place, size: 18),
                                    const SizedBox(width: 4),
                                    Text(
                                      '${i18n.bibliotecaLocation}: $ubicacion',
                                      style: Theme.of(context).textTheme.bodyMedium,
                                    ),
                                  ],
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Botones de acción
                      if (disponible)
                        FilledButton.icon(
                          onPressed: () => _solicitarPrestamo(context),
                          icon: const Icon(Icons.book),
                          label: Text(i18n.bibliotecaRequestLoan),
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                          ),
                        )
                      else
                        FilledButton.icon(
                          onPressed: () => _reservarLibro(context),
                          icon: const Icon(Icons.bookmark_add),
                          label: Text(i18n.bibliotecaReserve),
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                          ),
                        ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _solicitarPrestamo(BuildContext context) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.bibliotecaRequestLoan),
        content: Text(i18n.bibliotecaRequestLoanConfirm),
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
      final response = await api.solicitarBibliotecaPrestamo(widget.libroId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.bibliotecaRequestSuccess
            : (response.error ?? i18n.bibliotecaRequestError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }
  }

  Future<void> _reservarLibro(BuildContext context) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.bibliotecaReserve),
        content: Text(i18n.bibliotecaReserveConfirm),
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
      final response = await api.reservarBibliotecaLibro(widget.libroId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.bibliotecaReserveSuccess
            : (response.error ?? i18n.bibliotecaReserveError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }
  }
}
