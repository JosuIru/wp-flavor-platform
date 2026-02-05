import 'package:flutter/material.dart';
import '../models/models.dart';

/// Selector de cantidad para tickets
class TicketQuantitySelector extends StatelessWidget {
  final TicketType ticket;
  final int quantity;
  final Function(int) onQuantityChanged;
  final int maxQuantity;
  final bool isEnabled;
  final String? disabledReason;

  const TicketQuantitySelector({
    super.key,
    required this.ticket,
    required this.quantity,
    required this.onQuantityChanged,
    this.maxQuantity = 10,
    this.isEnabled = true,
    this.disabledReason,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveMaxQuantity = ticket.maxQuantity > 0 ? ticket.maxQuantity : maxQuantity;

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: isEnabled ? null : Theme.of(context).colorScheme.surfaceContainerHighest.withOpacity(0.5),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        ticket.name,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                              color: isEnabled ? null : Theme.of(context).colorScheme.onSurface.withOpacity(0.5),
                            ),
                      ),
                      if (ticket.description.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          ticket.description,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurface
                                    .withOpacity(isEnabled ? 0.7 : 0.4),
                              ),
                        ),
                      ],
                      const SizedBox(height: 4),
                      Text(
                        ticket.formattedPrice,
                        style: Theme.of(context).textTheme.titleSmall?.copyWith(
                              color: isEnabled
                                  ? Theme.of(context).colorScheme.primary
                                  : Theme.of(context).colorScheme.primary.withOpacity(0.5),
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ],
                  ),
                ),
                _QuantityControl(
                  quantity: quantity,
                  onDecrease: isEnabled && quantity > 0
                      ? () => onQuantityChanged(quantity - 1)
                      : null,
                  onIncrease: isEnabled && quantity < effectiveMaxQuantity
                      ? () => onQuantityChanged(quantity + 1)
                      : null,
                  isEnabled: isEnabled,
                ),
              ],
            ),
            // Mostrar razón de deshabilitación
            if (!isEnabled && disabledReason != null) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.errorContainer.withOpacity(0.3),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.info_outline,
                      size: 14,
                      color: Theme.of(context).colorScheme.error,
                    ),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        disabledReason!,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context).colorScheme.error,
                            ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _QuantityControl extends StatelessWidget {
  final int quantity;
  final VoidCallback? onDecrease;
  final VoidCallback? onIncrease;
  final bool isEnabled;

  const _QuantityControl({
    required this.quantity,
    this.onDecrease,
    this.onIncrease,
    this.isEnabled = true,
  });

  @override
  Widget build(BuildContext context) {
    final borderColor = isEnabled
        ? Theme.of(context).colorScheme.outline.withOpacity(0.5)
        : Theme.of(context).colorScheme.outline.withOpacity(0.2);

    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: borderColor),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          IconButton(
            onPressed: onDecrease,
            icon: Icon(
              Icons.remove,
              color: isEnabled ? null : Theme.of(context).colorScheme.onSurface.withOpacity(0.3),
            ),
            iconSize: 20,
            constraints: const BoxConstraints(
              minWidth: 40,
              minHeight: 40,
            ),
          ),
          Container(
            constraints: const BoxConstraints(minWidth: 32),
            child: Text(
              '$quantity',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: isEnabled ? null : Theme.of(context).colorScheme.onSurface.withOpacity(0.3),
                  ),
            ),
          ),
          IconButton(
            onPressed: onIncrease,
            icon: Icon(
              Icons.add,
              color: isEnabled ? null : Theme.of(context).colorScheme.onSurface.withOpacity(0.3),
            ),
            iconSize: 20,
            constraints: const BoxConstraints(
              minWidth: 40,
              minHeight: 40,
            ),
          ),
        ],
      ),
    );
  }
}

/// Lista de tickets para selección con soporte de dependencias
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

  /// Genera el mensaje de dependencias no satisfechas
  String? _getDisabledReason(TicketType ticket) {
    if (ticket.dependsOn.isEmpty) return null;

    final missingDependencies = <String>[];
    for (final requiredSlug in ticket.dependsOn) {
      final qty = quantities[requiredSlug] ?? 0;
      if (qty <= 0) {
        // Buscar el nombre del ticket requerido
        final requiredTicket = tickets.where((t) => t.slug == requiredSlug).firstOrNull;
        missingDependencies.add(requiredTicket?.name ?? requiredSlug);
      }
    }

    if (missingDependencies.isEmpty) return null;

    if (missingDependencies.length == 1) {
      return 'Requiere: ${missingDependencies.first}';
    } else {
      return 'Requiere: ${missingDependencies.join(', ')}';
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: tickets.length,
      itemBuilder: (context, index) {
        final ticket = tickets[index];
        final isEnabled = ticket.areDependenciesSatisfied(quantities);
        final disabledReason = _getDisabledReason(ticket);

        return TicketQuantitySelector(
          ticket: ticket,
          quantity: quantities[ticket.slug] ?? 0,
          onQuantityChanged: (qty) => onQuantityChanged(ticket.slug, qty),
          isEnabled: isEnabled,
          disabledReason: disabledReason,
        );
      },
    );
  }
}

/// Resumen del carrito
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
    this.date,
    this.onCheckout,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    final items = <({TicketType ticket, int qty})>[];
    double total = 0;

    for (final ticket in tickets) {
      final qty = quantities[ticket.slug] ?? 0;
      if (qty > 0) {
        items.add((ticket: ticket, qty: qty));
        total += ticket.price * qty;
      }
    }

    if (items.isEmpty) {
      return const SizedBox.shrink();
    }

    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Resumen',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            if (date != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.calendar_today,
                    size: 16,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(width: 8),
                  Text(_formatDate(date!)),
                ],
              ),
            ],
            const Divider(height: 24),
            ...items.map((item) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('${item.qty}x ${item.ticket.name}'),
                      Text(
                        '${(item.ticket.price * item.qty).toStringAsFixed(2)}€',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                )),
            const Divider(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Total',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                Text(
                  '${total.toStringAsFixed(2)}€',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: isLoading ? null : onCheckout,
                icon: isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.shopping_cart_checkout),
                label: Text(isLoading ? 'Procesando...' : 'Finalizar reserva'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String date) {
    try {
      final parts = date.split('-');
      return '${parts[2]}/${parts[1]}/${parts[0]}';
    } catch (e) {
      return date;
    }
  }
}
