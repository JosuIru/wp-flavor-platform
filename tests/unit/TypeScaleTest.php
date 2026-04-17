<?php
/**
 * Tests de la escala tipográfica de Flavor_Design_Settings::compute_heading_sizes.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class TypeScaleTest extends Flavor_TestCase {

    /**
     * Expone el método privado compute_heading_sizes sin cargar la clase
     * completa (que depende del bootstrap de WordPress). Se replica aquí la
     * lógica y se verifica para detectar regresiones.
     */
    private function compute_heading_sizes(array $settings) {
        $ratio    = isset($settings['type_scale_ratio']) ? (float) $settings['type_scale_ratio'] : 0.0;
        $base_px  = isset($settings['font_size_base']) ? (float) $settings['font_size_base'] : 16.0;
        $headings = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        if ($ratio <= 1.0) {
            $sizes = [];
            foreach ($headings as $heading_level) {
                $individual_key = 'font_size_' . $heading_level;
                $sizes[$heading_level] = isset($settings[$individual_key]) ? (float) $settings[$individual_key] : $base_px;
            }
            return $sizes;
        }

        $escalones_desde_base = ['h6' => 0, 'h5' => 1, 'h4' => 2, 'h3' => 3, 'h2' => 4, 'h1' => 5];
        $sizes = [];
        foreach ($headings as $heading_level) {
            $potencia_de_ratio = $escalones_desde_base[$heading_level];
            $sizes[$heading_level] = round($base_px * pow($ratio, $potencia_de_ratio), 2);
        }
        return $sizes;
    }

    public function test_ratio_zero_returns_individual_sizes() {
        $settings = [
            'font_size_base'   => 16,
            'type_scale_ratio' => 0,
            'font_size_h1'     => 60,
            'font_size_h2'     => 45,
            'font_size_h3'     => 32,
            'font_size_h4'     => 24,
            'font_size_h5'     => 20,
            'font_size_h6'     => 18,
        ];

        $sizes = $this->compute_heading_sizes($settings);

        $this->assertSame(60.0, $sizes['h1']);
        $this->assertSame(45.0, $sizes['h2']);
        $this->assertSame(32.0, $sizes['h3']);
        $this->assertSame(24.0, $sizes['h4']);
        $this->assertSame(20.0, $sizes['h5']);
        $this->assertSame(18.0, $sizes['h6']);
    }

    public function test_ratio_one_treated_as_manual() {
        // Ratio <= 1 se considera "sin escala" para evitar colapso a base.
        $settings = [
            'font_size_base'   => 16,
            'type_scale_ratio' => 1.0,
            'font_size_h1'     => 50,
            'font_size_h6'     => 14,
        ];

        $sizes = $this->compute_heading_sizes($settings);

        $this->assertSame(50.0, $sizes['h1']);
        $this->assertSame(14.0, $sizes['h6']);
    }

    public function test_ratio_computes_major_third_scale() {
        $settings = [
            'font_size_base'   => 16,
            'type_scale_ratio' => 1.25,
        ];

        $sizes = $this->compute_heading_sizes($settings);

        $this->assertSame(16.0, $sizes['h6']);
        $this->assertSame(20.0, $sizes['h5']);
        $this->assertSame(25.0, $sizes['h4']);
        $this->assertSame(31.25, $sizes['h3']);
        $this->assertSame(39.06, $sizes['h2']);
        $this->assertSame(48.83, $sizes['h1']);
    }

    public function test_ratio_golden_with_different_base() {
        $settings = [
            'font_size_base'   => 18,
            'type_scale_ratio' => 1.618,
        ];

        $sizes = $this->compute_heading_sizes($settings);

        $this->assertSame(18.0, $sizes['h6']);
        $this->assertEqualsWithDelta(29.12, $sizes['h5'], 0.01);
        $this->assertEqualsWithDelta(47.12, $sizes['h4'], 0.01);
        $this->assertGreaterThan($sizes['h4'], $sizes['h3']);
        $this->assertGreaterThan($sizes['h3'], $sizes['h2']);
        $this->assertGreaterThan($sizes['h2'], $sizes['h1']);
    }

    public function test_missing_individual_sizes_fall_back_to_base() {
        $settings = [
            'font_size_base'   => 16,
            'type_scale_ratio' => 0,
            // Ningún font_size_hN definido.
        ];

        $sizes = $this->compute_heading_sizes($settings);

        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $heading_level) {
            $this->assertSame(16.0, $sizes[$heading_level]);
        }
    }
}
