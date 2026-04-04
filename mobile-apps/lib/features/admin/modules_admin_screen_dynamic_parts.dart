part of 'modules_admin_screen_dynamic.dart';

class _ModuleMetricsCard extends StatelessWidget {
  final _ModuleMetrics metric;
  final String subtitle;
  final VoidCallback onTap;

  const _ModuleMetricsCard({
    required this.metric,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: metric.color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  metric.icon,
                  color: metric.color,
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      metric.name,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                ),
              ),
              Text(
                metric.value,
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: metric.color,
                ),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right, color: Colors.grey.shade400),
            ],
          ),
        ),
      ),
    );
  }
}

Widget _buildAdminModuleScreen(String moduleId, String moduleName) {
  switch (moduleId) {
    case 'reservas':
      return const AdminReservationsScreen();
    case 'facturas':
      return const FacturasScreen();
    case 'fichaje_empleados':
      return const FichajeEmpleadosScreen();
    case 'bares':
      return const BaresScreen();
    case 'woocommerce':
      return const WooCommerceAdminScreen();
    case 'campamentos':
    case 'basabere-campamentos':
      return const CampsManagementScreen();
    case 'avisos_municipales':
    case 'avisos-municipales':
      return const AvisosMunicipalesScreen();
    case 'ayuda_vecinal':
    case 'ayuda-vecinal':
      return const AyudaVecinalScreen();
    case 'banco_tiempo':
    case 'banco-tiempo':
      return const BancoTiempoScreen();
    case 'grupos_consumo':
    case 'grupos-consumo':
      return const GruposConsumoScreen();
    case 'huertos_urbanos':
    case 'huertos-urbanos':
      return const HuertosUrbanosScreen();
    case 'biblioteca':
      return const BibliotecaScreen();
    case 'espacios_comunes':
    case 'espacios-comunes':
      return const EspaciosComunesScreen();
    case 'eventos':
      return const EventosScreen();
    case 'cursos':
      return const CursosScreen();
    case 'talleres':
      return const TalleresScreen();
    case 'bicicletas_compartidas':
    case 'bicicletas-compartidas':
      return const BicicletasCompartidasScreen();
    case 'parkings':
      return const ParkingsScreen();
    case 'chat_grupos':
    case 'chat-grupos':
      return const ChatGruposScreen();
    case 'chat_interno':
    case 'chat-interno':
      return const ChatInternoScreen();
    case 'incidencias':
      return const IncidenciasScreen();
    case 'tramites':
      return const TramitesScreen();
    case 'marketplace':
      return const MarketplaceScreen();
    case 'socios':
      return const SociosScreen();
    case 'reciclaje':
      return const ReciclajeScreen();
    default:
      return ModulePlaceholderScreen(
        moduleId: moduleId,
        moduleName: moduleName,
      );
  }
}

class _ModuleMetrics {
  final String id;
  final String name;
  final String subtitle;
  final String value;
  final IconData icon;
  final Color color;

  const _ModuleMetrics({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.value,
    required this.icon,
    required this.color,
  });
}
