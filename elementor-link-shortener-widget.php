<?php
/**
 * Plugin Name: Elementor Link Shortener Button Widget
 * Description: Adds an Elementor button widget that shortens the current page URL and copies it to clipboard.
 * Version: 1.1.0
 * Author: Codex Agent
 * Requires Plugins: elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

final class ELSB_Plugin {
    const VERSION = '1.1.0';
    const OPTION_LINKS = 'elsb_short_links';

    public function __construct() {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_elsb_shorten_url', [$this, 'ajax_shorten_url']);
        add_action('wp_ajax_nopriv_elsb_shorten_url', [$this, 'ajax_shorten_url']);
        add_action('template_redirect', [$this, 'handle_redirect']);
    }

    public function register_assets(): void {
        wp_register_script(
            'elsb-widget-script',
            plugin_dir_url(__FILE__) . 'assets/js/widget.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('elsb-widget-script', 'elsbData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elsb_nonce'),
            'messages' => [
                'shortening' => __('در حال کوتاه‌سازی...', 'elsb'),
                'copied' => __('لینک کوتاه شد و کپی شد ✅', 'elsb'),
                'failed' => __('خطا در کوتاه‌سازی لینک.', 'elsb'),
                'copyFailed' => __('کوتاه شد، ولی کپی خودکار انجام نشد.', 'elsb'),
            ],
        ]);
    }

    public function register_widgets($widgets_manager): void {
        require_once __DIR__ . '/includes/class-elsb-widget.php';
        $widgets_manager->register(new ELSB_Widget_Button());
    }

    public function ajax_shorten_url(): void {
        check_ajax_referer('elsb_nonce', 'nonce');

        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => __('آدرس صفحه معتبر نیست.', 'elsb')], 400);
        }

        $short_url = $this->create_local_short_url($url);

        if (is_wp_error($short_url)) {
            wp_send_json_error(['message' => $short_url->get_error_message()], 500);
        }

        wp_send_json_success(['shortUrl' => $short_url]);
    }

    public function handle_redirect(): void {
        $code = isset($_GET['elsb_go']) ? sanitize_key(wp_unslash($_GET['elsb_go'])) : '';

        if (empty($code)) {
            return;
        }

        $links = $this->get_links_store();

        if (!isset($links[$code])) {
            wp_die(__('لینک کوتاه معتبر نیست یا منقضی شده است.', 'elsb'), __('خطا', 'elsb'), ['response' => 404]);
        }

        wp_safe_redirect($links[$code], 301);
        exit;
    }

    private function create_local_short_url(string $url) {
        $links = $this->get_links_store();

        $existing_code = array_search($url, $links, true);
        if ($existing_code !== false) {
            return home_url('/?elsb_go=' . rawurlencode($existing_code));
        }

        $code = $this->generate_unique_code($links, $url);
        if (empty($code)) {
            return new WP_Error('elsb_code_error', __('ساخت کد کوتاه با خطا مواجه شد.', 'elsb'));
        }

        $links[$code] = $url;
        update_option(self::OPTION_LINKS, $links, false);

        return home_url('/?elsb_go=' . rawurlencode($code));
    }

    private function get_links_store(): array {
        $links = get_option(self::OPTION_LINKS, []);
        return is_array($links) ? $links : [];
    }

    private function generate_unique_code(array $links, string $url): string {
        $seed = wp_hash($url . '|' . wp_generate_uuid4() . '|' . microtime(true));
        $clean_seed = preg_replace('/[^a-zA-Z0-9]/', '', $seed);

        for ($i = 0; $i < 8; $i++) {
            $start = $i;
            $candidate = strtolower(substr($clean_seed, $start, 7));

            if (!empty($candidate) && !isset($links[$candidate])) {
                return $candidate;
            }
        }

        return '';
    }
}

new ELSB_Plugin();
