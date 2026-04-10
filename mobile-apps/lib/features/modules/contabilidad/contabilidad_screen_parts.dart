part of 'contabilidad_screen.dart';

// =============================================================================
// SELECTOR DE PERÍODO
// =============================================================================

class _PeriodoSelector extends StatelessWidget {
  final int mes;
  final int ano;
  final Function(int mes, int ano) onChanged;

  const _PeriodoSelector({
    required this.mes,
    required this.ano,
    required this.onChanged,
  });

  static const _meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).primaryColor.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left),
            onPressed: () {
              final nuevoMes = mes == 1 ? 12 : mes - 1;
              final nuevoAno = mes == 1 ? ano - 1 : ano;
              onChanged(nuevoMes, nuevoAno);
            },
          ),
          Text(
            '${_meses[mes - 1]} $ano',
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          IconButton(
            icon: const Icon(Icons.chevron_right),
            onPressed: () {
              final ahora = DateTime.now();
              if (ano < ahora.year || (ano == ahora.year && mes < ahora.month)) {
                final nuevoMes = mes == 12 ? 1 : mes + 1;
                final nuevoAno = mes == 12 ? ano + 1 : ano;
                onChanged(nuevoMes, nuevoAno);
              }
            },
          ),
        ],
      ),
    );
  }
}

// =============================================================================
// RESUMEN DEL MES
// =============================================================================

class _ResumenMesCard extends StatelessWidget {
  final Map<String, dynamic> datos;

  const _ResumenMesCard({required this.datos});

  @override
  Widget build(BuildContext context) {
    final ingresos = (datos['ingresos'] ?? 0).toDouble();
    final gastos = (datos['gastos'] ?? 0).toDouble();
    final resultado = (datos['resultado'] ?? (ingresos - gastos)).toDouble();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: _MetricaItem(
                    icono: Icons.trending_up,
                    label: 'Ingresos',
                    valor: ingresos,
                    color: Colors.green,
                  ),
                ),
                Container(width: 1, height: 50, color: Colors.grey[300]),
                Expanded(
                  child: _MetricaItem(
                    icono: Icons.trending_down,
                    label: 'Gastos',
                    valor: gastos,
                    color: Colors.red,
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            _MetricaItem(
              icono: resultado >= 0 ? Icons.thumb_up : Icons.thumb_down,
              label: 'Resultado',
              valor: resultado,
              color: resultado >= 0 ? Colors.blue : Colors.orange,
              grande: true,
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// RESUMEN DEL AÑO
// =============================================================================

class _ResumenAnoCard extends StatelessWidget {
  final Map<String, dynamic> datos;

  const _ResumenAnoCard({required this.datos});

  @override
  Widget build(BuildContext context) {
    final ingresos = (datos['ingresos'] ?? 0).toDouble();
    final gastos = (datos['gastos'] ?? 0).toDouble();
    final resultado = (datos['resultado'] ?? 0).toDouble();
    final ivaNeto = (datos['iva_neto'] ?? 0).toDouble();

    return Card(
      color: Colors.grey[50],
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: _MetricaMiniItem(
                    label: 'Ingresos año',
                    valor: ingresos,
                    color: Colors.green,
                  ),
                ),
                Expanded(
                  child: _MetricaMiniItem(
                    label: 'Gastos año',
                    valor: gastos,
                    color: Colors.red,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _MetricaMiniItem(
                    label: 'Resultado año',
                    valor: resultado,
                    color: resultado >= 0 ? Colors.blue : Colors.orange,
                  ),
                ),
                Expanded(
                  child: _MetricaMiniItem(
                    label: 'IVA a liquidar',
                    valor: ivaNeto,
                    color: Colors.purple,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// MÉTRICAS
// =============================================================================

class _MetricaItem extends StatelessWidget {
  final IconData icono;
  final String label;
  final double valor;
  final Color color;
  final bool grande;

  const _MetricaItem({
    required this.icono,
    required this.label,
    required this.valor,
    required this.color,
    this.grande = false,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icono, color: color, size: grande ? 32 : 24),
        const SizedBox(height: 8),
        Text(
          label,
          style: TextStyle(
            fontSize: grande ? 14 : 12,
            color: Colors.grey[600],
          ),
        ),
        const SizedBox(height: 4),
        Text(
          _formatearMoneda(valor),
          style: TextStyle(
            fontSize: grande ? 24 : 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _MetricaMiniItem extends StatelessWidget {
  final String label;
  final double valor;
  final Color color;

  const _MetricaMiniItem({
    required this.label,
    required this.valor,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: color.withOpacity(0.8),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            _formatearMoneda(valor),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

// =============================================================================
// FILTROS DE TIPO
// =============================================================================

class _TipoFilterChips extends StatelessWidget {
  final String tipoSeleccionado;
  final Function(String) onChanged;

  const _TipoFilterChips({
    required this.tipoSeleccionado,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _FilterChip(
            label: 'Todos',
            selected: tipoSeleccionado.isEmpty,
            onTap: () => onChanged(''),
          ),
          const SizedBox(width: 8),
          _FilterChip(
            label: 'Ingresos',
            selected: tipoSeleccionado == 'ingreso',
            color: Colors.green,
            onTap: () => onChanged('ingreso'),
          ),
          const SizedBox(width: 8),
          _FilterChip(
            label: 'Gastos',
            selected: tipoSeleccionado == 'gasto',
            color: Colors.red,
            onTap: () => onChanged('gasto'),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final Color? color;
  final VoidCallback onTap;

  const _FilterChip({
    required this.label,
    required this.selected,
    this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final chipColor = color ?? Theme.of(context).primaryColor;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? chipColor : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: chipColor),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: selected ? Colors.white : chipColor,
            fontWeight: selected ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ),
    );
  }
}

// =============================================================================
// MOVIMIENTO ITEM (lista compacta)
// =============================================================================

class _MovimientoItem extends StatelessWidget {
  final dynamic movimiento;
  final VoidCallback onTap;

  const _MovimientoItem({
    required this.movimiento,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final tipo = movimiento['tipo'] ?? 'ingreso';
    final concepto = movimiento['concepto'] ?? '';
    final total = (movimiento['total'] ?? 0).toDouble();
    final esIngreso = tipo == 'ingreso';

    return ListTile(
      leading: CircleAvatar(
        backgroundColor: esIngreso ? Colors.green[100] : Colors.red[100],
        child: Icon(
          esIngreso ? Icons.arrow_downward : Icons.arrow_upward,
          color: esIngreso ? Colors.green : Colors.red,
          size: 20,
        ),
      ),
      title: Text(
        concepto,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      trailing: Text(
        '${esIngreso ? '+' : '-'}${_formatearMoneda(total.abs())}',
        style: TextStyle(
          fontWeight: FontWeight.bold,
          color: esIngreso ? Colors.green : Colors.red,
        ),
      ),
      onTap: onTap,
    );
  }
}

// =============================================================================
// MOVIMIENTO CARD (lista detallada)
// =============================================================================

class _MovimientoCard extends StatelessWidget {
  final dynamic movimiento;
  final VoidCallback onTap;

  const _MovimientoCard({
    required this.movimiento,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final tipo = movimiento['tipo'] ?? 'ingreso';
    final concepto = movimiento['concepto'] ?? '';
    final categoria = movimiento['categoria'] ?? '';
    final fecha = movimiento['fecha'] ?? '';
    final total = (movimiento['total'] ?? 0).toDouble();
    final tercero = movimiento['tercero'] ?? '';
    final esIngreso = tipo == 'ingreso';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Icono tipo
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: esIngreso ? Colors.green[100] : Colors.red[100],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  esIngreso ? Icons.arrow_downward : Icons.arrow_upward,
                  color: esIngreso ? Colors.green : Colors.red,
                ),
              ),
              const SizedBox(width: 12),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      concepto,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        if (categoria.isNotEmpty) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.grey[200],
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              _formatearCategoria(categoria),
                              style: TextStyle(
                                fontSize: 10,
                                color: Colors.grey[700],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                        ],
                        if (tercero.isNotEmpty)
                          Expanded(
                            child: Text(
                              tercero,
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey[600],
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _formatearFecha(fecha),
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey[500],
                      ),
                    ),
                  ],
                ),
              ),
              // Total
              Text(
                '${esIngreso ? '+' : '-'}${_formatearMoneda(total.abs())}',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: esIngreso ? Colors.green : Colors.red,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// =============================================================================
// DETALLE MOVIMIENTO
// =============================================================================

class _MovimientoDetalleSheet extends StatelessWidget {
  final dynamic movimiento;

  const _MovimientoDetalleSheet({required this.movimiento});

  @override
  Widget build(BuildContext context) {
    final tipo = movimiento['tipo'] ?? 'ingreso';
    final concepto = movimiento['concepto'] ?? '';
    final categoria = movimiento['categoria'] ?? '';
    final fecha = movimiento['fecha'] ?? '';
    final tercero = movimiento['tercero'] ?? '';
    final baseImponible = (movimiento['base_imponible'] ?? 0).toDouble();
    final ivaPorcentaje = (movimiento['iva_porcentaje'] ?? 0).toDouble();
    final ivaImporte = (movimiento['iva_importe'] ?? 0).toDouble();
    final retencionPorcentaje =
        (movimiento['retencion_porcentaje'] ?? 0).toDouble();
    final retencionImporte = (movimiento['retencion_importe'] ?? 0).toDouble();
    final total = (movimiento['total'] ?? 0).toDouble();
    final moduloOrigen = movimiento['modulo_origen'] ?? '';
    final esIngreso = tipo == 'ingreso';

    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      maxChildSize: 0.9,
      minChildSize: 0.4,
      expand: false,
      builder: (context, scrollController) => SingleChildScrollView(
        controller: scrollController,
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Tipo y total
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: esIngreso ? Colors.green : Colors.red,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      esIngreso ? 'INGRESO' : 'GASTO',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const Spacer(),
                  Text(
                    _formatearMoneda(total),
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: esIngreso ? Colors.green : Colors.red,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Concepto
              Text(
                concepto,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),

              // Fecha y categoría
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 14, color: Colors.grey[500]),
                  const SizedBox(width: 4),
                  Text(
                    _formatearFecha(fecha),
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                  if (categoria.isNotEmpty) ...[
                    const SizedBox(width: 16),
                    Icon(Icons.category, size: 14, color: Colors.grey[500]),
                    const SizedBox(width: 4),
                    Text(
                      _formatearCategoria(categoria),
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                  ],
                ],
              ),

              // Tercero
              if (tercero.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.person, size: 14, color: Colors.grey[500]),
                    const SizedBox(width: 4),
                    Text(
                      tercero,
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                  ],
                ),
              ],

              const Divider(height: 32),

              // Desglose fiscal
              const Text(
                'Desglose',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),

              _DesgloseRow(
                label: 'Base imponible',
                valor: baseImponible,
              ),
              if (ivaPorcentaje > 0)
                _DesgloseRow(
                  label: 'IVA (${ivaPorcentaje.toStringAsFixed(0)}%)',
                  valor: ivaImporte,
                ),
              if (retencionPorcentaje > 0)
                _DesgloseRow(
                  label: 'Retención (${retencionPorcentaje.toStringAsFixed(0)}%)',
                  valor: -retencionImporte,
                ),
              const Divider(),
              _DesgloseRow(
                label: 'TOTAL',
                valor: total,
                esBold: true,
              ),

              // Origen
              if (moduloOrigen.isNotEmpty) ...[
                const SizedBox(height: 20),
                Row(
                  children: [
                    Icon(Icons.source, size: 14, color: Colors.grey[400]),
                    const SizedBox(width: 4),
                    Text(
                      'Origen: ${_formatearModulo(moduloOrigen)}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[500],
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _DesgloseRow extends StatelessWidget {
  final String label;
  final double valor;
  final bool esBold;

  const _DesgloseRow({
    required this.label,
    required this.valor,
    this.esBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: esBold ? FontWeight.bold : FontWeight.normal,
            ),
          ),
          Text(
            _formatearMoneda(valor),
            style: TextStyle(
              fontWeight: esBold ? FontWeight.bold : FontWeight.normal,
            ),
          ),
        ],
      ),
    );
  }
}

// =============================================================================
// GRÁFICO EVOLUCIÓN
// =============================================================================

class _GraficoEvolucion extends StatelessWidget {
  final List<dynamic> datos;

  const _GraficoEvolucion({required this.datos});

  @override
  Widget build(BuildContext context) {
    if (datos.isEmpty) return const SizedBox.shrink();

    // Encontrar el valor máximo para escalar
    double maxValor = 0;
    for (final d in datos) {
      final ingresos = (d['ingresos'] ?? 0).toDouble();
      final gastos = (d['gastos'] ?? 0).toDouble();
      if (ingresos > maxValor) maxValor = ingresos;
      if (gastos > maxValor) maxValor = gastos;
    }

    if (maxValor == 0) maxValor = 1;

    return SizedBox(
      height: 200,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: datos.map((d) {
          final ingresos = (d['ingresos'] ?? 0).toDouble();
          final gastos = (d['gastos'] ?? 0).toDouble();
          final mes = d['mes']?.toString() ?? '';
          final mesCorto = mes.length >= 7 ? mes.substring(5) : mes;

          return Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 2),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  // Barras
                  SizedBox(
                    height: 150,
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Expanded(
                          child: Container(
                            height: (ingresos / maxValor) * 150,
                            decoration: BoxDecoration(
                              color: Colors.green[300],
                              borderRadius: const BorderRadius.vertical(
                                top: Radius.circular(4),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 1),
                        Expanded(
                          child: Container(
                            height: (gastos / maxValor) * 150,
                            decoration: BoxDecoration(
                              color: Colors.red[300],
                              borderRadius: const BorderRadius.vertical(
                                top: Radius.circular(4),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 4),
                  // Mes
                  Text(
                    mesCorto,
                    style: TextStyle(
                      fontSize: 9,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

// =============================================================================
// TABLA EVOLUCIÓN
// =============================================================================

class _TablaEvolucion extends StatelessWidget {
  final List<dynamic> datos;

  const _TablaEvolucion({required this.datos});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(8),
        child: Table(
          columnWidths: const {
            0: FlexColumnWidth(1.5),
            1: FlexColumnWidth(1),
            2: FlexColumnWidth(1),
            3: FlexColumnWidth(1),
          },
          children: [
            // Header
            TableRow(
              decoration: BoxDecoration(
                color: Colors.grey[100],
              ),
              children: const [
                Padding(
                  padding: EdgeInsets.all(8),
                  child: Text('Mes', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
                Padding(
                  padding: EdgeInsets.all(8),
                  child: Text('Ingresos',
                      style: TextStyle(fontWeight: FontWeight.bold),
                      textAlign: TextAlign.right),
                ),
                Padding(
                  padding: EdgeInsets.all(8),
                  child: Text('Gastos',
                      style: TextStyle(fontWeight: FontWeight.bold),
                      textAlign: TextAlign.right),
                ),
                Padding(
                  padding: EdgeInsets.all(8),
                  child: Text('Resultado',
                      style: TextStyle(fontWeight: FontWeight.bold),
                      textAlign: TextAlign.right),
                ),
              ],
            ),
            // Datos
            ...datos.reversed.take(6).map((d) {
              final mes = d['mes']?.toString() ?? '';
              final ingresos = (d['ingresos'] ?? 0).toDouble();
              final gastos = (d['gastos'] ?? 0).toDouble();
              final resultado = (d['resultado'] ?? 0).toDouble();

              return TableRow(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(8),
                    child: Text(_formatearMesNombre(mes)),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(8),
                    child: Text(
                      _formatearMonedaCorta(ingresos),
                      textAlign: TextAlign.right,
                      style: const TextStyle(color: Colors.green),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(8),
                    child: Text(
                      _formatearMonedaCorta(gastos),
                      textAlign: TextAlign.right,
                      style: const TextStyle(color: Colors.red),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(8),
                    child: Text(
                      _formatearMonedaCorta(resultado),
                      textAlign: TextAlign.right,
                      style: TextStyle(
                        color: resultado >= 0 ? Colors.blue : Colors.orange,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              );
            }),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// UTILIDADES
// =============================================================================

String _formatearMoneda(double valor) {
  final signo = valor < 0 ? '-' : '';
  final abs = valor.abs();
  if (abs >= 1000) {
    return '$signo${(abs / 1000).toStringAsFixed(1)}k €';
  }
  return '$signo${abs.toStringAsFixed(2)} €';
}

String _formatearMonedaCorta(double valor) {
  final signo = valor < 0 ? '-' : '';
  final abs = valor.abs();
  if (abs >= 1000000) {
    return '$signo${(abs / 1000000).toStringAsFixed(1)}M';
  }
  if (abs >= 1000) {
    return '$signo${(abs / 1000).toStringAsFixed(1)}k';
  }
  return '$signo${abs.toStringAsFixed(0)}';
}

String _formatearFecha(String fecha) {
  try {
    final dt = DateTime.parse(fecha);
    return '${dt.day}/${dt.month}/${dt.year}';
  } catch (e) {
    return fecha;
  }
}

String _formatearCategoria(String categoria) {
  return categoria
      .replaceAll('_', ' ')
      .split(' ')
      .map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '')
      .join(' ');
}

String _formatearModulo(String modulo) {
  final nombres = {
    'facturas': 'Facturas',
    'socios': 'Socios',
    'reservas': 'Reservas',
    'marketplace': 'Marketplace',
    'crowdfunding': 'Crowdfunding',
    'manual': 'Manual',
    'api_mobile': 'App Móvil',
  };
  return nombres[modulo] ?? modulo;
}

String _formatearMesNombre(String fecha) {
  const meses = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
  ];
  try {
    final partes = fecha.split('-');
    if (partes.length >= 2) {
      final mes = int.parse(partes[1]);
      return '${meses[mes - 1]} ${partes[0].substring(2)}';
    }
  } catch (e) {
    // Ignorar
  }
  return fecha;
}
