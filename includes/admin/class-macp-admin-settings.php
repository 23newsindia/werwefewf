<?php
/**
 * Handles admin settings operations
 */
class MACP_Admin_Settings {
    private $settings_manager;

     public function __construct() {
        $this->settings_manager = new MACP_Settings_Manager();
        add_action('wp_ajax_macp_toggle_setting', [$this, 'ajax_toggle_setting']);
        add_action('wp_ajax_macp_save_textarea', [$this, 'ajax_save_textarea']);
        add_action('wp_ajax_macp_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('admin_init', [$this, 'register_settings']);
    }
  
   public function register_settings() {
        register_setting('macp_settings', 'macp_enable_lazy_load');
        register_setting('macp_settings', 'macp_lazy_load_excluded');
    }


    public function get_all_settings() {
        return $this->settings_manager->get_all_settings();
    }

    public function ajax_toggle_setting() {
    try {
        // Verify nonce
        check_ajax_referer('macp_admin_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access']);
            return;
        }

        // Validate input
        if (!isset($_POST['option']) || !isset($_POST['value'])) {
            wp_send_json_error(['message' => 'Missing required parameters']);
            return;
        }

        $option = sanitize_key($_POST['option']);
        $value = (int)$_POST['value'];

        // Update option
        if (update_option($option, $value)) {
            do_action('macp_settings_updated', $option, $value);
            wp_send_json_success(['message' => 'Setting updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update setting']);
        }

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Error: ' . $e->getMessage(),
            'trace' => WP_DEBUG ? $e->getTraceAsString() : null
        ]);
    }
}



    public function ajax_save_textarea() {
    check_ajax_referer('macp_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $option = sanitize_key($_POST['option']);
    $value = sanitize_textarea_field($_POST['value']);

    // Convert textarea content to array
    $values = array_filter(array_map('trim', explode("\n", $value)));

    // Save the settings
    if ($option === 'macp_deferred_scripts' || $option === 'macp_defer_excluded_scripts') {
        update_option($option, $values);
    }

    wp_send_json_success(['message' => 'Settings saved']);
}


    public function ajax_clear_cache() {
        check_ajax_referer('macp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        do_action('macp_clear_cache');
        
        if (get_option('macp_enable_varnish', 0)) {
            do_action('macp_clear_all_cache');
        }
        
        wp_send_json_success(['message' => 'Cache cleared successfully']);
    }
}