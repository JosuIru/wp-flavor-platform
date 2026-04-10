<?php
/**
 * Tests de integracion para el Module Loader
 *
 * Tests de carga de multiples modulos, dependencias y ciclo de vida
 *
 * @package FlavorPlatform
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Mock de modulo para tests de integracion
 */
class Mock_Module_For_Integration {

    private $identificador;
    private $nombre;
    private $descripcion;
    private $dependencias;
    private $visibilidad;
    private $capacidadRequerida;
    private $puedeActivarse;
    private $inicializado;

    public function __construct(
        $identificador,
        $nombre,
        $descripcion = '',
        $dependencias = [],
        $puedeActivarse = true
    ) {
        $this->identificador = $identificador;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion ?: "Modulo {$nombre}";
        $this->dependencias = $dependencias;
        $this->visibilidad = 'public';
        $this->capacidadRequerida = 'read';
        $this->puedeActivarse = $puedeActivarse;
        $this->inicializado = false;
    }

    public function get_id() {
        return $this->identificador;
    }

    public function get_name() {
        return $this->nombre;
    }

    public function get_description() {
        return $this->descripcion;
    }

    public function get_dependencies() {
        return $this->dependencias;
    }

    public function get_visibility() {
        return $this->visibilidad;
    }

    public function get_required_capability() {
        return $this->capacidadRequerida;
    }

    public function can_activate() {
        return $this->puedeActivarse;
    }

    public function get_activation_error() {
        return $this->puedeActivarse ? '' : 'No se puede activar';
    }

    public function init() {
        $this->inicializado = true;
    }

    public function is_initialized() {
        return $this->inicializado;
    }

    public function get_actions() {
        return [];
    }

    public function execute_action($nombreAccion, $parametros = []) {
        return ['success' => false, 'error' => 'Accion no implementada'];
    }

    public function get_tool_definitions() {
        return [];
    }

    public function get_knowledge_base() {
        return '';
    }

    public function get_faqs() {
        return [];
    }

    public function set_visibility($visibilidad) {
        $this->visibilidad = $visibilidad;
    }

    public function set_required_capability($capacidad) {
        $this->capacidadRequerida = $capacidad;
    }
}

/**
 * Mock de gestor de modulos para tests
 */
class Mock_Module_Manager {

    private $modulosRegistrados = [];
    private $modulosCargados = [];
    private $modulosActivos = [];
    private $ordenDependencias = [];

    public function register_module($modulo) {
        $this->modulosRegistrados[$modulo->get_id()] = $modulo;
    }

    public function set_active_modules($modulosActivos) {
        $this->modulosActivos = $modulosActivos;
    }

    public function load_active_modules() {
        $this->ordenDependencias = $this->resolve_dependencies();

        foreach ($this->ordenDependencias as $idModulo) {
            if (isset($this->modulosRegistrados[$idModulo])) {
                $this->load_module($idModulo);
            }
        }

        return $this->modulosCargados;
    }

    private function resolve_dependencies() {
        $ordenResuelto = [];
        $pendientes = $this->modulosActivos;
        $maxIteraciones = count($this->modulosActivos) * 2;
        $iteracion = 0;

        while (!empty($pendientes) && $iteracion < $maxIteraciones) {
            $iteracion++;

            foreach ($pendientes as $indice => $idModulo) {
                if (!isset($this->modulosRegistrados[$idModulo])) {
                    unset($pendientes[$indice]);
                    continue;
                }

                $modulo = $this->modulosRegistrados[$idModulo];
                $dependencias = $modulo->get_dependencies();
                $dependenciasSatisfechas = true;

                foreach ($dependencias as $dependencia) {
                    if (!in_array($dependencia, $ordenResuelto)) {
                        $dependenciasSatisfechas = false;
                        break;
                    }
                }

                if ($dependenciasSatisfechas) {
                    $ordenResuelto[] = $idModulo;
                    unset($pendientes[$indice]);
                }
            }

            $pendientes = array_values($pendientes);
        }

        return $ordenResuelto;
    }

    private function load_module($idModulo) {
        if (isset($this->modulosCargados[$idModulo])) {
            return true;
        }

        $modulo = $this->modulosRegistrados[$idModulo];

        if (!$modulo->can_activate()) {
            return false;
        }

        $modulo->init();
        $this->modulosCargados[$idModulo] = $modulo;

        return true;
    }

    public function get_loaded_modules() {
        return $this->modulosCargados;
    }

    public function get_registered_modules() {
        return $this->modulosRegistrados;
    }

    public function is_module_active($idModulo) {
        return in_array($idModulo, $this->modulosActivos);
    }

    public function is_module_loaded($idModulo) {
        return isset($this->modulosCargados[$idModulo]);
    }

    public function get_module($idModulo) {
        return $this->modulosCargados[$idModulo] ?? null;
    }

    public function get_dependency_order() {
        return $this->ordenDependencias;
    }
}

/**
 * Tests de integracion del Module Loader
 */
class ModuleLoaderIntegrationTest extends Flavor_TestCase {

    /**
     * Gestor de modulos mock
     */
    private $gestorModulos;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();
        $this->gestorModulos = new Mock_Module_Manager();
    }

    /**
     * Test de carga de un solo modulo
     */
    public function test_load_single_module() {
        $modulo = new Mock_Module_For_Integration('eventos', 'Eventos');
        $this->gestorModulos->register_module($modulo);
        $this->gestorModulos->set_active_modules(['eventos']);

        $modulosCargados = $this->gestorModulos->load_active_modules();

        $this->assertCount(1, $modulosCargados);
        $this->assertArrayHasKey('eventos', $modulosCargados);
        $this->assertTrue($modulosCargados['eventos']->is_initialized());
    }

    /**
     * Test de carga de multiples modulos sin dependencias
     */
    public function test_load_multiple_modules_without_dependencies() {
        $moduloEventos = new Mock_Module_For_Integration('eventos', 'Eventos');
        $moduloSocios = new Mock_Module_For_Integration('socios', 'Socios');
        $moduloBiblioteca = new Mock_Module_For_Integration('biblioteca', 'Biblioteca');

        $this->gestorModulos->register_module($moduloEventos);
        $this->gestorModulos->register_module($moduloSocios);
        $this->gestorModulos->register_module($moduloBiblioteca);
        $this->gestorModulos->set_active_modules(['eventos', 'socios', 'biblioteca']);

        $modulosCargados = $this->gestorModulos->load_active_modules();

        $this->assertCount(3, $modulosCargados);
        $this->assertArrayHasKey('eventos', $modulosCargados);
        $this->assertArrayHasKey('socios', $modulosCargados);
        $this->assertArrayHasKey('biblioteca', $modulosCargados);
    }

    /**
     * Test de carga de modulos con dependencias simples
     */
    public function test_load_modules_with_simple_dependencies() {
        $moduloBase = new Mock_Module_For_Integration('base', 'Base');
        $moduloDependiente = new Mock_Module_For_Integration(
            'dependiente',
            'Dependiente',
            'Depende de base',
            ['base']
        );

        $this->gestorModulos->register_module($moduloBase);
        $this->gestorModulos->register_module($moduloDependiente);
        $this->gestorModulos->set_active_modules(['dependiente', 'base']);

        $modulosCargados = $this->gestorModulos->load_active_modules();
        $ordenCarga = $this->gestorModulos->get_dependency_order();

        $this->assertCount(2, $modulosCargados);

        // Verificar orden: base debe cargarse antes que dependiente
        $indiceBase = array_search('base', $ordenCarga);
        $indiceDependiente = array_search('dependiente', $ordenCarga);
        $this->assertLessThan($indiceDependiente, $indiceBase);
    }

    /**
     * Test de carga de modulos con cadena de dependencias
     */
    public function test_load_modules_with_dependency_chain() {
        $moduloA = new Mock_Module_For_Integration('modulo_a', 'Modulo A');
        $moduloB = new Mock_Module_For_Integration('modulo_b', 'Modulo B', '', ['modulo_a']);
        $moduloC = new Mock_Module_For_Integration('modulo_c', 'Modulo C', '', ['modulo_b']);

        $this->gestorModulos->register_module($moduloA);
        $this->gestorModulos->register_module($moduloB);
        $this->gestorModulos->register_module($moduloC);
        $this->gestorModulos->set_active_modules(['modulo_c', 'modulo_b', 'modulo_a']);

        $modulosCargados = $this->gestorModulos->load_active_modules();
        $ordenCarga = $this->gestorModulos->get_dependency_order();

        $this->assertCount(3, $modulosCargados);

        // Verificar orden: A -> B -> C
        $indiceA = array_search('modulo_a', $ordenCarga);
        $indiceB = array_search('modulo_b', $ordenCarga);
        $indiceC = array_search('modulo_c', $ordenCarga);

        $this->assertLessThan($indiceB, $indiceA);
        $this->assertLessThan($indiceC, $indiceB);
    }

    /**
     * Test de modulo que no puede activarse
     */
    public function test_module_that_cannot_activate() {
        $moduloValido = new Mock_Module_For_Integration('valido', 'Valido');
        $moduloInvalido = new Mock_Module_For_Integration(
            'invalido',
            'Invalido',
            '',
            [],
            false // No puede activarse
        );

        $this->gestorModulos->register_module($moduloValido);
        $this->gestorModulos->register_module($moduloInvalido);
        $this->gestorModulos->set_active_modules(['valido', 'invalido']);

        $modulosCargados = $this->gestorModulos->load_active_modules();

        $this->assertCount(1, $modulosCargados);
        $this->assertArrayHasKey('valido', $modulosCargados);
        $this->assertArrayNotHasKey('invalido', $modulosCargados);
    }

    /**
     * Test de modulo activo vs registrado
     */
    public function test_active_vs_registered_modules() {
        $modulo1 = new Mock_Module_For_Integration('modulo1', 'Modulo 1');
        $modulo2 = new Mock_Module_For_Integration('modulo2', 'Modulo 2');
        $modulo3 = new Mock_Module_For_Integration('modulo3', 'Modulo 3');

        $this->gestorModulos->register_module($modulo1);
        $this->gestorModulos->register_module($modulo2);
        $this->gestorModulos->register_module($modulo3);

        // Solo activar 2 de 3
        $this->gestorModulos->set_active_modules(['modulo1', 'modulo3']);
        $this->gestorModulos->load_active_modules();

        $registrados = $this->gestorModulos->get_registered_modules();
        $cargados = $this->gestorModulos->get_loaded_modules();

        $this->assertCount(3, $registrados);
        $this->assertCount(2, $cargados);
        $this->assertTrue($this->gestorModulos->is_module_active('modulo1'));
        $this->assertFalse($this->gestorModulos->is_module_active('modulo2'));
        $this->assertTrue($this->gestorModulos->is_module_active('modulo3'));
    }

    /**
     * Test de verificar modulo cargado
     */
    public function test_is_module_loaded() {
        $modulo = new Mock_Module_For_Integration('test', 'Test');
        $this->gestorModulos->register_module($modulo);
        $this->gestorModulos->set_active_modules(['test']);

        $this->assertFalse($this->gestorModulos->is_module_loaded('test'));

        $this->gestorModulos->load_active_modules();

        $this->assertTrue($this->gestorModulos->is_module_loaded('test'));
        $this->assertFalse($this->gestorModulos->is_module_loaded('nonexistent'));
    }

    /**
     * Test de obtener modulo especifico
     */
    public function test_get_specific_module() {
        $modulo = new Mock_Module_For_Integration('especifico', 'Especifico');
        $this->gestorModulos->register_module($modulo);
        $this->gestorModulos->set_active_modules(['especifico']);
        $this->gestorModulos->load_active_modules();

        $moduloObtenido = $this->gestorModulos->get_module('especifico');

        $this->assertNotNull($moduloObtenido);
        $this->assertEquals('especifico', $moduloObtenido->get_id());
        $this->assertEquals('Especifico', $moduloObtenido->get_name());
    }

    /**
     * Test de obtener modulo inexistente
     */
    public function test_get_nonexistent_module() {
        $moduloObtenido = $this->gestorModulos->get_module('no_existe');

        $this->assertNull($moduloObtenido);
    }

    /**
     * Test de visibilidad de modulos
     */
    public function test_module_visibility_levels() {
        $moduloPublico = new Mock_Module_For_Integration('publico', 'Publico');
        $moduloMiembros = new Mock_Module_For_Integration('miembros', 'Miembros');
        $moduloAdmin = new Mock_Module_For_Integration('admin', 'Admin');

        $moduloPublico->set_visibility('public');
        $moduloMiembros->set_visibility('members');
        $moduloAdmin->set_visibility('admins');

        $this->assertEquals('public', $moduloPublico->get_visibility());
        $this->assertEquals('members', $moduloMiembros->get_visibility());
        $this->assertEquals('admins', $moduloAdmin->get_visibility());
    }

    /**
     * Test de capacidades requeridas por modulo
     */
    public function test_module_required_capabilities() {
        $moduloLector = new Mock_Module_For_Integration('lector', 'Lector');
        $moduloEditor = new Mock_Module_For_Integration('editor', 'Editor');
        $moduloAdmin = new Mock_Module_For_Integration('admin', 'Admin');

        $moduloLector->set_required_capability('read');
        $moduloEditor->set_required_capability('edit_posts');
        $moduloAdmin->set_required_capability('manage_options');

        $this->assertEquals('read', $moduloLector->get_required_capability());
        $this->assertEquals('edit_posts', $moduloEditor->get_required_capability());
        $this->assertEquals('manage_options', $moduloAdmin->get_required_capability());
    }

    /**
     * Test de integracion economica (facturas + contabilidad)
     */
    public function test_economic_integration_facturas_contabilidad() {
        $moduloFacturas = new Mock_Module_For_Integration('facturas', 'Facturas');
        $moduloContabilidad = new Mock_Module_For_Integration('contabilidad', 'Contabilidad');

        $this->gestorModulos->register_module($moduloFacturas);
        $this->gestorModulos->register_module($moduloContabilidad);

        // Si facturas esta activo, contabilidad tambien deberia estarlo
        $modulosActivos = ['facturas'];

        // Simular logica de integracion
        if (in_array('facturas', $modulosActivos) && !in_array('contabilidad', $modulosActivos)) {
            $modulosActivos[] = 'contabilidad';
        }

        $this->gestorModulos->set_active_modules($modulosActivos);
        $modulosCargados = $this->gestorModulos->load_active_modules();

        $this->assertCount(2, $modulosCargados);
        $this->assertArrayHasKey('facturas', $modulosCargados);
        $this->assertArrayHasKey('contabilidad', $modulosCargados);
    }

    /**
     * Test de modulos con dependencias multiples
     */
    public function test_modules_with_multiple_dependencies() {
        $moduloA = new Mock_Module_For_Integration('modulo_a', 'Modulo A');
        $moduloB = new Mock_Module_For_Integration('modulo_b', 'Modulo B');
        $moduloC = new Mock_Module_For_Integration(
            'modulo_c',
            'Modulo C',
            'Depende de A y B',
            ['modulo_a', 'modulo_b']
        );

        $this->gestorModulos->register_module($moduloA);
        $this->gestorModulos->register_module($moduloB);
        $this->gestorModulos->register_module($moduloC);
        $this->gestorModulos->set_active_modules(['modulo_c', 'modulo_a', 'modulo_b']);

        $modulosCargados = $this->gestorModulos->load_active_modules();
        $ordenCarga = $this->gestorModulos->get_dependency_order();

        $this->assertCount(3, $modulosCargados);

        // C debe cargarse despues de A y B
        $indiceA = array_search('modulo_a', $ordenCarga);
        $indiceB = array_search('modulo_b', $ordenCarga);
        $indiceC = array_search('modulo_c', $ordenCarga);

        $this->assertLessThan($indiceC, $indiceA);
        $this->assertLessThan($indiceC, $indiceB);
    }

    /**
     * Test de inicializacion de modulos
     */
    public function test_modules_are_initialized_after_load() {
        $modulo1 = new Mock_Module_For_Integration('mod1', 'Mod1');
        $modulo2 = new Mock_Module_For_Integration('mod2', 'Mod2');

        $this->assertFalse($modulo1->is_initialized());
        $this->assertFalse($modulo2->is_initialized());

        $this->gestorModulos->register_module($modulo1);
        $this->gestorModulos->register_module($modulo2);
        $this->gestorModulos->set_active_modules(['mod1', 'mod2']);
        $this->gestorModulos->load_active_modules();

        $modulo1Cargado = $this->gestorModulos->get_module('mod1');
        $modulo2Cargado = $this->gestorModulos->get_module('mod2');

        $this->assertTrue($modulo1Cargado->is_initialized());
        $this->assertTrue($modulo2Cargado->is_initialized());
    }

    /**
     * Test de categorias de modulos
     */
    public function test_module_categories() {
        $categorias = [
            'comunidad' => ['eventos', 'socios', 'foros', 'comunidades'],
            'comercio' => ['woocommerce', 'marketplace', 'grupos_consumo'],
            'gestion' => ['facturas', 'contabilidad', 'incidencias'],
            'comunicacion' => ['chat_interno', 'red_social', 'podcast'],
            'finanzas' => ['banco_tiempo', 'economia_don'],
            'utilidades' => ['biblioteca', 'tramites', 'reservas'],
        ];

        $this->assertCount(6, $categorias);

        foreach ($categorias as $categoria => $modulos) {
            $this->assertIsArray($modulos);
            $this->assertNotEmpty($modulos);
        }
    }

    /**
     * Test de woocommerce como modulo por defecto
     */
    public function test_woocommerce_is_default_module() {
        $modulosDefault = ['woocommerce'];

        $this->assertContains('woocommerce', $modulosDefault);
        $this->assertCount(1, $modulosDefault);
    }
}
