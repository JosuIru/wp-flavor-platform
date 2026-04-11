<?php
/**
 * Base test case for VBP tests.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

/**
 * VBP Unit Test Case base class.
 *
 * Provides helper methods and common setup for VBP tests.
 */
class VBP_UnitTestCase extends WP_UnitTestCase {

    /**
     * Mock post ID for testing.
     *
     * @var int
     */
    protected $test_post_id = 1;

    /**
     * Mock user ID for testing.
     *
     * @var int
     */
    protected $test_user_id = 1;

    /**
     * Sample VBP content structure.
     *
     * @var array
     */
    protected $sample_vbp_content;

    /**
     * Sample symbol content.
     *
     * @var array
     */
    protected $sample_symbol_content;

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        // Initialize sample content structures.
        $this->sample_vbp_content = $this->create_sample_vbp_content();
        $this->sample_symbol_content = $this->create_sample_symbol_content();
    }

    /**
     * Create sample VBP content structure.
     *
     * @return array
     */
    protected function create_sample_vbp_content() {
        return array(
            'elements' => array(
                array(
                    'id'       => 'section-1',
                    'type'     => 'section',
                    'props'    => array(
                        'backgroundColor' => '#ffffff',
                        'padding'         => '40px',
                    ),
                    'children' => array(
                        array(
                            'id'    => 'heading-1',
                            'type'  => 'heading',
                            'props' => array(
                                'text'  => 'Welcome',
                                'level' => 1,
                                'color' => '#333333',
                            ),
                        ),
                        array(
                            'id'    => 'text-1',
                            'type'  => 'text',
                            'props' => array(
                                'content' => 'This is a sample paragraph.',
                            ),
                        ),
                        array(
                            'id'    => 'button-1',
                            'type'  => 'button',
                            'props' => array(
                                'text' => 'Click me',
                                'url'  => '/action',
                            ),
                        ),
                    ),
                ),
            ),
            'styles' => array(),
            'settings' => array(
                'pageWidth' => 1200,
            ),
        );
    }

    /**
     * Create sample symbol content structure.
     *
     * @return array
     */
    protected function create_sample_symbol_content() {
        return array(
            array(
                'id'       => 'symbol-root',
                'type'     => 'container',
                'props'    => array(
                    'className' => 'symbol-container',
                ),
                'children' => array(
                    array(
                        'id'    => 'symbol-heading',
                        'type'  => 'heading',
                        'props' => array(
                            'text'  => 'Symbol Title',
                            'level' => 2,
                        ),
                    ),
                    array(
                        'id'    => 'symbol-text',
                        'type'  => 'text',
                        'props' => array(
                            'content' => 'Symbol description text.',
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Create a test post and return its ID.
     *
     * @param array $args Post arguments.
     * @return int|WP_Error
     */
    protected function create_test_post( $args = array() ) {
        if ( function_exists( 'wp_insert_post' ) ) {
            $default_args = array(
                'post_title'   => 'Test VBP Page',
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );

            return wp_insert_post( array_merge( $default_args, $args ) );
        }

        // Return mock ID for standalone tests.
        return ++$this->test_post_id;
    }

    /**
     * Delete a test post.
     *
     * @param int $post_id Post ID to delete.
     * @return bool
     */
    protected function delete_test_post( $post_id ) {
        if ( function_exists( 'wp_delete_post' ) ) {
            return (bool) wp_delete_post( $post_id, true );
        }
        return true;
    }

    /**
     * Create a test user and return their ID.
     *
     * @param string $role User role.
     * @return int
     */
    protected function create_test_user( $role = 'editor' ) {
        if ( function_exists( 'wp_create_user' ) ) {
            $username = 'testuser_' . wp_generate_password( 6, false );
            $user_id = wp_create_user( $username, 'password', $username . '@test.local' );

            if ( ! is_wp_error( $user_id ) ) {
                $user = new WP_User( $user_id );
                $user->set_role( $role );
            }

            return $user_id;
        }

        // Return mock ID for standalone tests.
        return ++$this->test_user_id;
    }

    /**
     * Assert array contains expected keys.
     *
     * @param array  $expected_keys Keys to check for.
     * @param array  $array         Array to check.
     * @param string $message       Failure message.
     */
    protected function assertArrayHasKeys( array $expected_keys, array $array, string $message = '' ) {
        foreach ( $expected_keys as $key ) {
            $this->assertArrayHasKey( $key, $array, $message ?: "Array missing expected key: {$key}" );
        }
    }

    /**
     * Assert that a value is a valid ID (positive integer).
     *
     * @param mixed  $value   Value to check.
     * @param string $message Failure message.
     */
    protected function assertValidId( $value, string $message = '' ) {
        $this->assertTrue(
            is_int( $value ) && $value > 0,
            $message ?: 'Value is not a valid ID (positive integer)'
        );
    }

    /**
     * Generate unique ID for testing.
     *
     * @param string $prefix ID prefix.
     * @return string
     */
    protected function generate_unique_id( string $prefix = 'test' ): string {
        return $prefix . '-' . uniqid();
    }

    /**
     * Deep compare two arrays ignoring order.
     *
     * @param array $expected Expected array.
     * @param array $actual   Actual array.
     * @return bool
     */
    protected function arrays_equal_recursive( array $expected, array $actual ): bool {
        if ( count( $expected ) !== count( $actual ) ) {
            return false;
        }

        foreach ( $expected as $key => $value ) {
            if ( ! array_key_exists( $key, $actual ) ) {
                return false;
            }

            if ( is_array( $value ) ) {
                if ( ! is_array( $actual[ $key ] ) ) {
                    return false;
                }
                if ( ! $this->arrays_equal_recursive( $value, $actual[ $key ] ) ) {
                    return false;
                }
            } elseif ( $value !== $actual[ $key ] ) {
                return false;
            }
        }

        return true;
    }
}
