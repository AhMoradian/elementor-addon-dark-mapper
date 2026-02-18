<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings Page and Settings API integration
 *
 * Renders: Dashboard -> Settings -> Dark Mode Colors
 */
class EDM_Settings {

    /**
     * Color manager instance
     *
     * @var EDM_Color_Manager
     */
    private $color_manager;

    /**
     * Option name
     *
     * @var string
     */
    private $option_name;

    /**
     * Constructor.
     *
     * @param EDM_Color_Manager $color_manager
     */
    public function __construct( $color_manager ) {
        $this->color_manager = $color_manager;
        $this->option_name   = $this->color_manager->get_option_name();

        // Register admin menu and settings
        add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Enqueue color picker only on our settings page
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_color_picker' ) );
    }

    /**
     * Register the settings page under Settings.
     */
    public function register_settings_page() {
        add_options_page(
            __( 'Dark Mode Colors', 'elementor-dark-mapper' ),
            __( 'Dark Mode Colors', 'elementor-dark-mapper' ),
            'manage_options',
            'edm-dark-mode-colors',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register the setting and sanitize callback.
     */
    public function register_settings() {
        register_setting(
            'edm_settings_group',
            $this->option_name,
            array(
                'type'              => 'array',
                'description'       => 'Mapping of Elementor global color IDs to dark mode colors.',
                'sanitize_callback' => array( $this, 'sanitize_color_map' ),
                'show_in_rest'      => false,
            )
        );
    }

    /**
     * Enqueue WP color picker on our settings page only.
     */
    public function maybe_enqueue_color_picker( $hook ) {
        // $hook is like 'settings_page_edm-dark-mode-colors'
        if ( 'settings_page_edm-dark-mode-colors' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        // Optional small script to init pickers (we'll inline small JS)
        wp_add_inline_script( 'wp-color-picker', "
			jQuery(document).ready(function($){
				$('.edm-color-field').wpColorPicker();
			});
		" );
    }

    /**
     * Sanitize the saved mapping.
     *
     * Ensures keys are strings and values are sanitized hex colors (or empty).
     *
     * @param array $input
     * @return array
     */
    public function sanitize_color_map( $input ) {
        $clean = array();

        if ( ! is_array( $input ) ) {
            return $clean;
        }

        // Only accept keys that match known global colors (defensive)
        $known_colors = $this->color_manager->get_global_colors();
        $allowed_keys = array_keys( $known_colors );

        foreach ( $input as $key => $val ) {
            $key = sanitize_text_field( $key );
            if ( ! in_array( $key, $allowed_keys, true ) ) {
                // Unknown mapping key — skip
                continue;
            }
            $color = sanitize_hex_color( $val );
            $clean[ $key ] = $color ? $color : '';
        }
        return $clean;
    }

    /**
     * Render the settings page HTML.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions', 'elementor-dark-mapper' ) );
        }

        $global_colors = $this->color_manager->get_global_colors();
        $saved_map     = get_option( $this->option_name, array() );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Dark Mode Colors', 'elementor-dark-mapper' ); ?></h1>
            <p><?php esc_html_e( 'Define Night Mode replacement colors for Elementor Global Colors (Elementor Kit system_colors). This plugin overrides CSS variables at runtime — widgets are not modified.', 'elementor-dark-mapper' ); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields( 'edm_settings_group' ); ?>
                <?php // do_settings_sections( 'edm_settings_group' ); ?>

                <table class="widefat fixed striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e( 'Global Color', 'elementor-dark-mapper' ); ?></th>
                        <th><?php esc_html_e( 'Day (current)', 'elementor-dark-mapper' ); ?></th>
                        <th><?php esc_html_e( 'Night (replacement)', 'elementor-dark-mapper' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ( empty( $global_colors ) ) : ?>
                        <tr>
                            <td colspan="3">
                                <?php esc_html_e( 'No global colors found. Make sure Elementor is active and an Elementor Kit is assigned as active.', 'elementor-dark-mapper' ); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $global_colors as $id => $meta ) :
                            $day_color   = ! empty( $meta['color'] ) ? $meta['color'] : '';
                            $night_color = isset( $saved_map[ $id ] ) ? $saved_map[ $id ] : '';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $meta['title'] ); ?></strong>
                                    <br/><small><?php echo esc_html( $id ); ?></small>
                                </td>
                                <td>
                                    <input type="text" disabled value="<?php echo esc_attr( $day_color ); ?>" class="regular-text" />
                                    <?php if ( $day_color ) : ?>
                                        <div style="display:inline-block; width:28px; height:28px; border:1px solid #ddd; vertical-align:middle; margin-left:8px; background:<?php echo esc_attr( $day_color ); ?>;"></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input
                                        name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $id ); ?>]"
                                        value="<?php echo esc_attr( $night_color ); ?>"
                                        class="edm-color-field regular-text"
                                        type="text"
                                        placeholder="<?php esc_attr_e( 'Pick a night color', 'elementor-dark-mapper' ); ?>"
                                    />
                                    <p class="description"><?php esc_html_e( 'Leave blank to not override this variable.', 'elementor-dark-mapper' ); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Notes & TODOs', 'elementor-dark-mapper' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'This is the MVP settings UI. The CSS override engine and front-end switcher will be added in Step 2.', 'elementor-dark-mapper' ); ?></li>
                <li><?php esc_html_e( 'TODO: per-page exclusions, editor preview, kit export integration, and smooth transitions (placeholders added in code).', 'elementor-dark-mapper' ); ?></li>
            </ul>
        </div>
        <?php
    }

} // end class
