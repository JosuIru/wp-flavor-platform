import 'package:flutter/material.dart';
import '../models/models.dart';
import '../utils/haptics.dart';

/// Widget de lista de selección de tickets
class TicketSelectionList extends StatelessWidget {
  final List<TicketType> tickets;
  final Map<String, int> quantities;
  final Function(String slug, int quantity) onQuantityChanged;

  const TicketSelectionList({
    super.key,
    required this.tickets,
    required this.quantities,
    required this.onQuantityChanged,
  });

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: tickets.length,
      itemBuilder: (context, index) {
        final ticket = tickets[index];
        final quantity = quantities[ticket.slug] ?? 0;

        return Card(
          margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        ticket.name,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ),
                    Text(
                      '${ticket.price.toStringAsFixed(2)} €',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ],
                ),
                if (ticket.description.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    ticket.description,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Cantidad:'),
                    Row(
                      children: [
                        Semantics(
                          label: 'Disminuir cantidad de ${ticket.name}',
                          button: true,
                          enabled: quantity > 0,
                          child: IconButton(
                            onPressed: quantity > 0
                                ? () {
                                    Haptics.selection();
                                    onQuantityChanged(ticket.slug, quantity - 1);
                                  }
                                : null,
                            icon: const Icon(Icons.remove_circle_outline),
                            tooltip: 'Disminuir cantidad',
                          ),
                        ),
                        Semantics(
                          label: '$quantity tickets de ${ticket.name} seleccionados',
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.grey),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              quantity.toString(),
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                          ),
                        ),
                        Semantics(
                          label: 'Aumentar cantidad de ${ticket.name}',
                          button: true,
                          child: IconButton(
                            onPressed: () {
                              Haptics.selection();
                              onQuantityChanged(ticket.slug, quantity + 1);
                            },
                            icon: const Icon(Icons.add_circle_outline),
                            tooltip: 'Aumentar cantidad',
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

/// Widget de resumen del carrito
class CartSummary extends StatelessWidget {
  final List<TicketType> tickets;
  final Map<String, int> quantities;
  final String? date;
  final VoidCallback? onCheckout;
  final bool isLoading;

  const CartSummary({
    super.key,
    required this.tickets,
    required this.quantities,
    required this.date,
    this.onCheckout,
    this.isLoading = false,
  });

  double get total {
    double sum = 0;
    for (final ticket in tickets) {
      final quantity = quantities[ticket.slug] ?? 0;
      sum += ticket.price * quantity;
    }
    return sum;
  }

  int get totalItems {
    return quantities.values.fold(0, (sum, qty) => sum + qty);
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.all(16),
      color: Theme.of(context).colorScheme.primaryContainer,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'Resumen',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 16),
            ...quantities.entries.map((entry) {
              final ticket = tickets.firstWhere(
                (t) => t.slug == entry.key,
                orElse: () => TicketType(
                  slug: entry.key,
                  name: entry.key,
                  price: 0,
                  description: '',
                ),
              );
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text('${ticket.name} x${entry.value}'),
                    ),
                    Text(
                      '${(ticket.price * entry.value).toStringAsFixed(2)} €',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              );
            }),
            const Divider(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Total ($totalItems items)',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                Text(
                  '${total.toStringAsFixed(2)} €',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        color: Theme.of(context).colorScheme.primary,
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ],
            ),
            if (date != null) ...[
              const SizedBox(height: 8),
              Text(
                'Fecha: $date',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
            const SizedBox(height: 16),
            Semantics(
              label: isLoading
                  ? 'Procesando pago'
                  : 'Proceder al pago. Total: ${total.toStringAsFixed(2)} euros',
              button: true,
              enabled: !isLoading,
              child: FilledButton(
                onPressed: isLoading ? null : onCheckout,
                child: isLoading
                    ? ExcludeSemantics(
                        child: const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : const Text('Proceder al pago'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
