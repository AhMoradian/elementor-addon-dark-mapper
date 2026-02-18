<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EDM_CSS_Generator
 *
 * Generates runtime CSS that overrides Elementor CSS variables when dark mode is active.
 *
 * - Reads saved mapping option (edm_color_map)
 * - Generates CSS inside `body.edm-dark { ... }` using `--e-global-color-<id>` names
 * - Attaches CSS via wp_add_inline_style() to a safe handle:
 *     preferred: 'elementor-frontend'
 *     fallback: 'edm-frontend-styles' (this plugin's frontend.css)
 */
class EDM_CSS_Generator {

    /** @var EDM_Color_Manager */
    protected $color_manager;

    /** Option name (from color manager) */
    protected $option_name;

    public function __construct( $color_manager ) {
        $this->color_manager = $color_manager;
        $this->option_name   = $this->color_manager->get_option_name();

        // We add inline CSS during front-end enqueue — after styles are registered.
        add_action( 'wp_enqueue_scripts', array( $this, 'attach_inline_css' ), 30 );
    }

    /**
     * Build the CSS string for the current saved mapping.
     *
     * @return string
     */
    public function build_css() {
        $map = get_option( $this->option_name, array() );
        if ( ! is_array( $map ) || empty( $map ) ) {
            return '';
        }

        $lines = array();

        foreach ( $map as $id => $hex ) {
            $id   = (string) $id;
            $hex  = (string) $hex;
            // only valid hex colors (empty values skip)
            if ( empty( $hex ) ) {
                continue;
            }
            // sanitize key to be a safe css variable name segment
            $var_key = preg_replace( '/[^a-z0-9_\-]/', '-', strtolower( $id ) );
            $lines[] = sprintf( '  --e-global-color-%s: %s;', $var_key, esc_html( $hex ) );
        }

        if ( empty( $lines ) ) {
            return '';
        }

        $css = "/* EDM — generated dark-mode CSS */\n";
        $css .= "body.edm-dark {\n";
        $css .= implode( "\n", $lines ) . "\n";
        $css .= "}\n";

        /**
         * Allow other plugins/devs to filter generated CSS.
         * Example: add_filter('edm_generated_css', fn($css)=>$css);
         */
        $css = apply_filters( 'edm_generated_css', $css );

        return $css;
    }

    /**
     * Attach the generated CSS inline to a style handle.
     *
     * Preferred handle: elementor-frontend
     * Fallback: edm-frontend-styles (this plugin's front-end CSS)
     */
    public function attach_inline_css() {
        $css = $this->build_css();
        if ( empty( $css ) ) {
            return;
        }

        // If elementor-frontend style is registered, attach there.
        if ( wp_style_is( 'elementor-frontend', 'registered' ) || wp_style_is( 'elementor-frontend', 'enqueued' ) ) {
            wp_add_inline_style( 'elementor-frontend', $css );
            return;
        }

        // Fallback — ensure our plugin front-end CSS is registered, then attach inline to it.
        $handle = 'edm-frontend-styles';
        if ( ! wp_style_is( $handle, 'registered' ) ) {
            $src        = EDM_PLUGIN_URL . 'assets/css/frontend.css';
            $style_path = EDM_PLUGIN_DIR . 'assets/css/frontend.css';
            $version    = file_exists( $style_path ) ? filemtime( $style_path ) : null;

            wp_register_style( $handle, $src, array(), $version );
            // do not automatically enqueue here; but adding inline requires it to be registered.
        }

        wp_add_inline_style( $handle, $css );
    }
}
