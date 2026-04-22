<?php
/**
 * Cliente para la API pública de GitHub Releases.
 *
 * Usado por el updater del core y el updater de addons para obtener la última
 * release de un repositorio publico. Sin licencia ni telemetria: solo GET a
 * api.github.com. Token opcional (FLAVOR_GH_TOKEN) unicamente para evitar
 * rate limit en instalaciones grandes.
 *
 * @package FlavorPlatform
 * @subpackage Licensing
 * @since 3.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_GitHub_Release_API {

    /**
     * Obtiene la ultima release publicada de un repositorio.
     *
     * @param string $repositorio Formato "owner/repo" (ej: "JosuIru/wp-flavor-platform").
     * @param bool   $incluir_prerelease Si es true, devuelve la ultima release incluyendo prereleases.
     * @return array|null Array normalizado o null si no se pudo obtener.
     *                    Claves: version, tag_name, zip_url, changelog, published_at, html_url.
     */
    public static function fetch_latest_release($repositorio, $incluir_prerelease = false) {
        $repositorio = trim((string) $repositorio, " /\t\n\r\0\x0B");
        if ($repositorio === '' || strpos($repositorio, '/') === false) {
            return null;
        }

        $endpoint = $incluir_prerelease
            ? "https://api.github.com/repos/{$repositorio}/releases"
            : "https://api.github.com/repos/{$repositorio}/releases/latest";

        $cabeceras = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'FlavorPlatform/' . (defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : 'dev'),
        ];

        $token_github = self::get_token();
        if ($token_github !== '') {
            $cabeceras['Authorization'] = 'Bearer ' . $token_github;
        }

        $respuesta = wp_remote_get($endpoint, [
            'headers' => $cabeceras,
            'timeout' => 15,
        ]);

        if (is_wp_error($respuesta)) {
            if (function_exists('flavor_platform_log')) {
                flavor_platform_log(
                    'GitHub Release API error (' . $repositorio . '): ' . $respuesta->get_error_message(),
                    'warning'
                );
            }
            return null;
        }

        $codigo_http = wp_remote_retrieve_response_code($respuesta);
        if ($codigo_http !== 200) {
            return null;
        }

        $datos_release = json_decode(wp_remote_retrieve_body($respuesta), true);
        if (!is_array($datos_release)) {
            return null;
        }

        // Cuando se piden prereleases, la API devuelve un array de releases: escoger la primera no draft.
        if ($incluir_prerelease) {
            $release_seleccionada = null;
            foreach ($datos_release as $release_individual) {
                if (!empty($release_individual['draft'])) {
                    continue;
                }
                $release_seleccionada = $release_individual;
                break;
            }
            if ($release_seleccionada === null) {
                return null;
            }
            $datos_release = $release_seleccionada;
        }

        return self::normalizar($datos_release);
    }

    /**
     * Normaliza la respuesta de GitHub al formato que consume el updater.
     *
     * @param array $datos_release Payload crudo de GitHub.
     * @return array|null
     */
    private static function normalizar(array $datos_release) {
        $tag = isset($datos_release['tag_name']) ? (string) $datos_release['tag_name'] : '';
        if ($tag === '') {
            return null;
        }

        // Los tags siguen el formato "vX.Y.Z"; WordPress compara sin la "v".
        $version = ltrim($tag, 'vV');

        $url_zip = self::buscar_zip_asset($datos_release['assets'] ?? []);

        return [
            'version'      => $version,
            'tag_name'     => $tag,
            'zip_url'      => $url_zip,
            'changelog'    => isset($datos_release['body']) ? (string) $datos_release['body'] : '',
            'published_at' => isset($datos_release['published_at']) ? (string) $datos_release['published_at'] : '',
            'html_url'     => isset($datos_release['html_url']) ? (string) $datos_release['html_url'] : '',
        ];
    }

    /**
     * Devuelve la URL del primer asset .zip del release, ignorando checksums.
     *
     * @param array $lista_assets
     * @return string Vacio si no hay asset ZIP.
     */
    private static function buscar_zip_asset($lista_assets) {
        if (!is_array($lista_assets)) {
            return '';
        }

        foreach ($lista_assets as $asset) {
            $nombre = isset($asset['name']) ? (string) $asset['name'] : '';
            $url_descarga = isset($asset['browser_download_url']) ? (string) $asset['browser_download_url'] : '';

            if ($nombre === '' || $url_descarga === '') {
                continue;
            }

            // Ignorar checksums, firmas y otros adjuntos.
            if (preg_match('/\.(sha256|sha512|md5|asc|sig)$/i', $nombre)) {
                continue;
            }

            if (substr(strtolower($nombre), -4) === '.zip') {
                return $url_descarga;
            }
        }

        return '';
    }

    /**
     * Token opcional para la API de GitHub (evita rate limit en redes grandes).
     *
     * Se define en wp-config.php con: define('FLAVOR_GH_TOKEN', 'ghp_...');
     *
     * @return string
     */
    private static function get_token() {
        if (defined('FLAVOR_GH_TOKEN') && is_string(FLAVOR_GH_TOKEN) && FLAVOR_GH_TOKEN !== '') {
            return FLAVOR_GH_TOKEN;
        }

        /**
         * Permite inyectar el token desde otra capa (ej: settings UI).
         */
        $token_filtrado = apply_filters('flavor_platform_github_token', '');
        return is_string($token_filtrado) ? $token_filtrado : '';
    }
}
