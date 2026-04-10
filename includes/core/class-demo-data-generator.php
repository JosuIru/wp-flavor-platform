<?php
/**
 * Demo Data Generator - Core Wrapper
 *
 * Este archivo es un wrapper de compatibilidad.
 * La implementación principal está en includes/class-demo-data-generator.php
 *
 * @package Flavor_Platform
 * @subpackage Core
 * @deprecated Use includes/class-demo-data-generator.php directamente
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Cargar la implementación principal
$main_generator_path = dirname( __DIR__ ) . '/class-demo-data-generator.php';

if ( file_exists( $main_generator_path ) ) {
    require_once $main_generator_path;
}

// Nota: La clase Flavor_Demo_Data_Generator ahora se carga desde
// includes/class-demo-data-generator.php
