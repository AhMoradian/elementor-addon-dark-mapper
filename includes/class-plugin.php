<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Loader
 */
class EDM_Plugin {

    private static $instance = null;

    /** @var EDM_Color_Manager */
    public $color_manager;

    /** @var EDM_Settings */
    public $settings;

    /** @var EDM_CSS_Generator */
    public $css_generator;

    /** @var EDM_Switcher */
    public $switcher;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'check_elementor' ), 5 );
    }

    public function check_elementor() {
        if ( defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) ) {
            $this->color_manager = new EDM_Color_Manager();
            $this->settings      = new EDM_Settings( $this->color_manager );

            // Step 2 components
            $this->css_generator = new EDM_CSS_Generator( $this->color_manager );
            $this->switcher      = new EDM_Switcher( $this->css_generator );

        } else {
            add_action( 'admin_notices', array( $this, 'elementor_missing_notice' ) );
        }
    }

    public function elementor_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'Elementor Global Dark Mode Mapper requires Elementor to be active. The plugin is currently inactive.', 'elementor-dark-mapper' ); ?></p>
        </div>
        <?php
    }

    /* TODO placeholders (no logic yet)
     * - Per-page dark mode (EDM_Per_Page)
     * - Editor preview (EDM_Editor_Preview)
     * - Kit export integration (EDM_Kit_Integration)
     */
}
