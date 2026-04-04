part of 'biblioteca_screen.dart';

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
    final i18n = AppLocalizations.of(context);

    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.bibliotecaError,
              onRetry: () {
                setState(() {
                  final api = ref.read(apiClientProvider);
                  _future = api.getBibliotecaLibro(widget.libroId);
                });
              },
            );
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
                          color: Theme.of(context).colorScheme.surfaceContainerHighest,
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
    final i18n = AppLocalizations.of(context);
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
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }
  }

  Future<void> _reservarLibro(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
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
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }
  }
}
