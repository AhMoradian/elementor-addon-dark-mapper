<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EDM_Switcher
 *
 * Handles front-end assets for dark-mode switching.
 */
class EDM_Switcher {

    /** @var EDM_CSS_Generator */
    protected $css_generator;

    /** @var string */
    protected $script_handle = 'edm-switcher';

    /** @var string */
    protected $style_handle = 'edm-frontend-styles';

    /**
     * @param EDM_CSS_Generator $css_generator
     */
    public function __construct( $css_generator ) {
        $this->css_generator = $css_generator;

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
    }

    /**
     * Enqueue front-end style/script required for switcher behavior.
     */
    public function enqueue_assets() {
        $style_path = EDM_PLUGIN_DIR . 'assets/css/frontend.css';
        $style_src  = EDM_PLUGIN_URL . 'assets/css/frontend.css';

        wp_enqueue_style(
            $this->style_handle,
            $style_src,
            array(),
            file_exists( $style_path ) ? filemtime( $style_path ) : null
        );

        $script_path = EDM_PLUGIN_DIR . 'assets/js/switcher.js';
        $script_src  = EDM_PLUGIN_URL . 'assets/js/switcher.js';

        wp_enqueue_script(
            $this->script_handle,
            $script_src,
            array(),
            file_exists( $script_path ) ? filemtime( $script_path ) : null,
            true
        );

        wp_localize_script(
            $this->script_handle,
            'EDM_SWITCHER',
            array(
                'ls_key' => apply_filters( 'edm_switcher_storage_key', 'edm_dark_mode' ),
            )
        );
    }
}
