<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Color Manager
 *
 * Responsible for reading Elementor Kit global colors and normalizing them.
 */
class EDM_Color_Manager {

    /**
     * Return array of global colors from the active kit.
     *
     * Returns an associative array keyed by color _id:
     * [
     *   'primary' => [
     *       'id'    => 'primary',
     *       'title' => 'Primary',
     *       'color' => '#2a2a2a',
     *   ],
     *   ...
     * ]
     *
     * @return array
     */
    public function get_global_colors() {
        $colors = array();

        // Ensure Elementor plugin object exists
        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return $colors;
        }

        $elementor = \Elementor\Plugin::$instance;

        // kits_manager should exist in Elementor free as well.
        if ( empty( $elementor->kits_manager ) ) {
            return $colors;
        }

        $kit = $elementor->kits_manager->get_active_kit();

        // If no kit or invalid, return empty
        if ( empty( $kit ) || ! method_exists( $kit, 'get_settings' ) ) {
            return $colors;
        }

        $system_colors = $kit->get_settings( 'system_colors' );

        // system_colors might be an array of arrays
        if ( is_array( $system_colors ) ) {
            foreach ( $system_colors as $item ) {
                // Expect at least: _id, title, color
                if ( ! is_array( $item ) ) {
                    continue;
                }

                $id    = isset( $item['_id'] ) ? sanitize_text_field( $item['_id'] ) : '';
                $title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : $id;
                $color = isset( $item['color'] ) ? $this->normalize_color( $item['color'] ) : '';

                if ( $id ) {
                    $colors[ $id ] = array(
                        'id'    => $id,
                        'title' => $title,
                        'color' => $color,
                    );
                }
            }
        }

        return $colors;
    }

    /**
     * Normalize a color string to a valid hex (or empty).
     *
     * @param string $color
     * @return string
     */
    public function normalize_color( $color ) {
        $color = trim( $color );

        // Some Elementor kits store rgba() or other formats - try to maintain hex if present.
        // If it's already a hex, sanitize it. Otherwise, attempt a quick rgba -> hex fallback
        if ( 0 === strpos( $color, '#' ) ) {
            return sanitize_hex_color( $color );
        }

        // Basic rgba() -> hex conversion (best effort).
        if ( preg_match( '/rgba?\(([^)]+)\)/', $color, $m ) ) {
            $parts = explode( ',', $m[1] );
            if ( count( $parts ) >= 3 ) {
                $r = (int) trim( $parts[0] );
                $g = (int) trim( $parts[1] );
                $b = (int) trim( $parts[2] );
                $hex = sprintf( '#%02x%02x%02x', $r, $g, $b );
                return sanitize_hex_color( $hex );
            }
        }

        // Fallback: return empty so UI can show original value if needed.
        return '';
    }

    /**
     * Helper to get the option-name used for storing the color map.
     *
     * @return string
     */
    public function get_option_name() {
        return 'edm_color_map';
    }

} // end class
