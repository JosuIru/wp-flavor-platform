<?php
/**
 * Keystore Manager - Gestión de firma de APKs
 *
 * Permite gestionar keystores para firmar APKs de release.
 *
 * @package Flavor_Chat_IA
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar keystores de Android
 */
class Flavor_Keystore_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Keystore_Manager|null
     */
    private static $instance = null;

    /**
     * Directorio de keystores
     *
     * @var string
     */
    private $keystore_dir;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Keystore_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->keystore_dir = $upload_dir['basedir'] . '/flavor-keystores';

        // Crear directorio si no existe
        if (!file_exists($this->keystore_dir)) {
            wp_mkdir_p($this->keystore_dir);
            // Proteger con .htaccess
            file_put_contents($this->keystore_dir . '/.htaccess', 'deny from all');
        }

        add_action('wp_ajax_flavor_keystore_upload', [$this, 'ajax_upload_keystore']);
        add_action('wp_ajax_flavor_keystore_list', [$this, 'ajax_list_keystores']);
        add_action('wp_ajax_flavor_keystore_delete', [$this, 'ajax_delete_keystore']);
        add_action('wp_ajax_flavor_keystore_generate', [$this, 'ajax_generate_keystore']);
        add_action('wp_ajax_flavor_keystore_verify', [$this, 'ajax_verify_keystore']);
        add_action('wp_ajax_flavor_keystore_set_default', [$this, 'ajax_set_default']);
    }

    /**
     * Subir un keystore existente
     */
    public function ajax_upload_keystore() {
        check_ajax_referer('flavor_keystore', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        if (empty($_FILES['keystore'])) {
            wp_send_json_error(__('No se recibió ningún archivo', 'flavor-chat-ia'));
        }

        $file = $_FILES['keystore'];
        $name = sanitize_text_field($_POST['name'] ?? '');
        $alias = sanitize_text_field($_POST['alias'] ?? '');
        $store_pass = $_POST['store_password'] ?? '';
        $key_pass = $_POST['key_password'] ?? '';

        // Validar extensión
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jks', 'keystore'])) {
            wp_send_json_error(__('Tipo de archivo no válido. Use .jks o .keystore', 'flavor-chat-ia'));
        }

        // Validar tamaño (máx 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error(__('El archivo es demasiado grande', 'flavor-chat-ia'));
        }

        // Generar nombre único
        $filename = wp_unique_filename($this->keystore_dir, sanitize_file_name($file['name']));
        $destination = $this->keystore_dir . '/' . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            wp_send_json_error(__('Error al guardar el archivo', 'flavor-chat-ia'));
        }

        // Guardar metadatos (encriptados)
        $keystore_id = uniqid('ks_');
        $keystores = get_option('flavor_keystores', []);

        $keystores[$keystore_id] = [
            'id' => $keystore_id,
            'name' => $name ?: pathinfo($filename, PATHINFO_FILENAME),
            'filename' => $filename,
            'alias' => $this->encrypt_value($alias),
            'store_password' => $this->encrypt_value($store_pass),
            'key_password' => $this->encrypt_value($key_pass),
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        ];

        update_option('flavor_keystores', $keystores);

        wp_send_json_success([
            'id' => $keystore_id,
            'name' => $keystores[$keystore_id]['name'],
            'message' => __('Keystore subido correctamente', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Listar keystores
     */
    public function ajax_list_keystores() {
        check_ajax_referer('flavor_keystore', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        $keystores = get_option('flavor_keystores', []);
        $default_id = get_option('flavor_default_keystore', '');
        $result = [];

        foreach ($keystores as $id => $ks) {
            $result[] = [
                'id' => $id,
                'name' => $ks['name'],
                'filename' => $ks['filename'],
                'created_at' => $ks['created_at'],
                'is_default' => ($id === $default_id),
            ];
        }

        wp_send_json_success($result);
    }

    /**
     * Eliminar keystore
     */
    public function ajax_delete_keystore() {
        check_ajax_referer('flavor_keystore', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        $keystore_id = sanitize_text_field($_POST['keystore_id'] ?? '');
        $keystores = get_option('flavor_keystores', []);

        if (!isset($keystores[$keystore_id])) {
            wp_send_json_error(__('Keystore no encontrado', 'flavor-chat-ia'));
        }

        // Eliminar archivo
        $filepath = $this->keystore_dir . '/' . $keystores[$keystore_id]['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Eliminar de opciones
        unset($keystores[$keystore_id]);
        update_option('flavor_keystores', $keystores);

        // Si era el default, limpiar
        if (get_option('flavor_default_keystore') === $keystore_id) {
            delete_option('flavor_default_keystore');
        }

        wp_send_json_success(__('Keystore eliminado', 'flavor-chat-ia'));
    }

    /**
     * Generar un nuevo keystore
     */
    public function ajax_generate_keystore() {
        check_ajax_referer('flavor_keystore', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        // Validar datos requeridos
        $params = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'alias' => sanitize_text_field($_POST['alias'] ?? 'upload'),
            'store_password' => $_POST['store_password'] ?? '',
            'key_password' => $_POST['key_password'] ?? '',
            'validity' => absint($_POST['validity'] ?? 10000),
            'cn' => sanitize_text_field($_POST['cn'] ?? ''),
            'ou' => sanitize_text_field($_POST['ou'] ?? ''),
            'o' => sanitize_text_field($_POST['o'] ?? ''),
            'l' => sanitize_text_field($_POST['l'] ?? ''),
            'st' => sanitize_text_field($_POST['st'] ?? ''),
            'c' => sanitize_text_field($_POST['c'] ?? 'ES'),
        ];

        // Validaciones
        if (empty($params['name']) || empty($params['store_password']) || empty($params['cn'])) {
            wp_send_json_error(__('Faltan campos requeridos', 'flavor-chat-ia'));
        }

        if (strlen($params['store_password']) < 6) {
            wp_send_json_error(__('La contraseña debe tener al menos 6 caracteres', 'flavor-chat-ia'));
        }

        // Verificar keytool disponible
        $keytool = $this->find_keytool();
        if (!$keytool) {
            wp_send_json_error(__('keytool no encontrado. Instale Java JDK', 'flavor-chat-ia'));
        }

        // Generar nombre de archivo único
        $filename = sanitize_file_name($params['name']) . '-' . time() . '.jks';
        $filepath = $this->keystore_dir . '/' . $filename;

        // Construir DN (Distinguished Name)
        $dname = sprintf(
            'CN=%s, OU=%s, O=%s, L=%s, ST=%s, C=%s',
            $params['cn'],
            $params['ou'],
            $params['o'],
            $params['l'],
            $params['st'],
            $params['c']
        );

        // Comando keytool
        $command = sprintf(
            '%s -genkeypair -v -keystore %s -keyalg RSA -keysize 2048 -validity %d -alias %s -storepass %s -keypass %s -dname "%s" 2>&1',
            escapeshellcmd($keytool),
            escapeshellarg($filepath),
            $params['validity'],
            escapeshellarg($params['alias']),
            escapeshellarg($params['store_password']),
            escapeshellarg($params['key_password'] ?: $params['store_password']),
            $dname
        );

        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        if ($return_var !== 0 || !file_exists($filepath)) {
            wp_send_json_error(__('Error generando keystore: ', 'flavor-chat-ia') . implode("\n", $output));
        }

        // Guardar metadatos
        $keystore_id = uniqid('ks_');
        $keystores = get_option('flavor_keystores', []);

        $keystores[$keystore_id] = [
            'id' => $keystore_id,
            'name' => $params['name'],
            'filename' => $filename,
            'alias' => $this->encrypt_value($params['alias']),
            'store_password' => $this->encrypt_value($params['store_password']),
            'key_password' => $this->encrypt_value($params['key_password'] ?: $params['store_password']),
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
            'generated' => true,
            'validity' => $params['validity'],
            'dname' => $dname,
        ];

        update_option('flavor_keystores', $keystores);

        wp_send_json_success([
            'id' => $keystore_id,
            'name' => $params['name'],
            'message' => __('Keystore generado correctamente', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Verificar keystore (probar contraseñas)
     */
    public function ajax_verify_keystore() {
        check_ajax_referer('flavor_keystore', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        $keystore_id = sanitize_text_field($_POST['keystore_id'] ?? '');
        $keystores = get_option('flavor_keystores', []);

        if (!isset($keystores[$keystore_id])) {
            wp_send_json_error(__('Keystore no encontrado', 'flavor-chat-ia'));
        }

        $ks = $keystores[$keystore_id];
        $filepath = $this->keystore_dir . '/' . $ks['filename'];

        if (!file_exists($filepath)) {
            wp_send_json_error(__('Archivo de keystore no encontrado', 'flavor-chat-ia'));
        }

        $keytool = $this->find_keytool();
        if (!$keytool) {
            wp_send_json_error(__('keytool no encontrado', 'flavor-chat-ia'));
        }

        $store_pass = $this->decrypt_value($ks['store_password']);

        // Verificar keystore
        $command = sprintf(
            '%s -list -v -keystore %s -storepass %s 2>&1',
            escapeshellcmd($keytool),
            escapeshellarg($filepath),
            escapeshellarg($store_pass)
        );

        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            wp_send_json_error(__('Error verificando keystore. Contraseña incorrecta?', 'flavor-chat-ia'));
        }

        // Parsear información
        $output_str = implode("\n", $output);
        $info = [
            'valid' => true,
            'alias' => $this->decrypt_value($ks['alias']),
            'creation_date' => '',
            'expiry_date' => '',
        ];

        // Extraer fechas
        if (preg_match('/Valid from: (.+?) until: (.+)/i', $output_str, $matches)) {
            $info['creation_date'] = $matches[1];
            $info['expiry_date'] = $matches[2];
        }

        wp_send_json_success($info);
    }

    /**
     * Establecer keystore por defecto
     */
    public function ajax_set_default() {
        check_ajax_referer('flavor_keystore', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        $keystore_id = sanitize_text_field($_POST['keystore_id'] ?? '');
        $keystores = get_option('flavor_keystores', []);

        if (!isset($keystores[$keystore_id])) {
            wp_send_json_error(__('Keystore no encontrado', 'flavor-chat-ia'));
        }

        update_option('flavor_default_keystore', $keystore_id);

        wp_send_json_success(__('Keystore establecido como predeterminado', 'flavor-chat-ia'));
    }

    /**
     * Obtener ruta del keystore por defecto
     *
     * @return array|null
     */
    public function get_default_keystore() {
        $default_id = get_option('flavor_default_keystore', '');
        if (empty($default_id)) {
            return null;
        }

        $keystores = get_option('flavor_keystores', []);
        if (!isset($keystores[$default_id])) {
            return null;
        }

        $ks = $keystores[$default_id];
        return [
            'path' => $this->keystore_dir . '/' . $ks['filename'],
            'alias' => $this->decrypt_value($ks['alias']),
            'store_password' => $this->decrypt_value($ks['store_password']),
            'key_password' => $this->decrypt_value($ks['key_password']),
        ];
    }

    /**
     * Generar key.properties para Flutter
     *
     * @param string $keystore_id
     * @return string|false
     */
    public function generate_key_properties($keystore_id = null) {
        if ($keystore_id === null) {
            $keystore_id = get_option('flavor_default_keystore', '');
        }

        $keystores = get_option('flavor_keystores', []);
        if (!isset($keystores[$keystore_id])) {
            return false;
        }

        $ks = $keystores[$keystore_id];
        $filepath = $this->keystore_dir . '/' . $ks['filename'];

        $content = sprintf(
            "storePassword=%s\nkeyPassword=%s\nkeyAlias=%s\nstoreFile=%s\n",
            $this->decrypt_value($ks['store_password']),
            $this->decrypt_value($ks['key_password']),
            $this->decrypt_value($ks['alias']),
            $filepath
        );

        return $content;
    }

    /**
     * Buscar keytool en el sistema
     *
     * @return string|null
     */
    private function find_keytool() {
        $paths = [
            '/usr/bin/keytool',
            '/usr/local/bin/keytool',
            'C:\\Program Files\\Java\\jdk*\\bin\\keytool.exe',
            getenv('JAVA_HOME') . '/bin/keytool',
        ];

        foreach ($paths as $path) {
            if (strpos($path, '*') !== false) {
                $glob = glob($path);
                if (!empty($glob)) {
                    return $glob[0];
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        // Intentar con which/where
        $command = PHP_OS_FAMILY === 'Windows' ? 'where keytool' : 'which keytool';
        $result = trim(shell_exec($command) ?? '');

        return !empty($result) ? $result : null;
    }

    /**
     * Encriptar un valor sensible
     *
     * @param string $value
     * @return string
     */
    private function encrypt_value($value) {
        if (empty($value)) {
            return '';
        }

        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencriptar un valor
     *
     * @param string $encrypted
     * @return string
     */
    private function decrypt_value($encrypted) {
        if (empty($encrypted)) {
            return '';
        }

        $key = $this->get_encryption_key();
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $encrypted_data = substr($data, 16);

        return openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Obtener clave de encriptación
     *
     * @return string
     */
    private function get_encryption_key() {
        $key = get_option('flavor_keystore_encryption_key');

        if (empty($key)) {
            $key = wp_generate_password(32, true, true);
            update_option('flavor_keystore_encryption_key', $key);
        }

        return hash('sha256', $key . AUTH_SALT);
    }
}

// Inicializar
Flavor_Keystore_Manager::get_instance();
