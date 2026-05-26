<?php
/**
 * Plugin Name: Elementor Link Shortener Button Widget
 * Description: Adds an Elementor button widget that shortens the current page URL and copies it to clipboard.
 * Version: 1.0.0
 * Author: Codex Agent
 * Requires Plugins: elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

final class ELSB_Plugin {
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_elsb_shorten_url', [$this, 'ajax_shorten_url']);
        add_action('wp_ajax_nopriv_elsb_shorten_url', [$this, 'ajax_shorten_url']);
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

        $short_url = $this->shorten_with_isgd($url);

        if (is_wp_error($short_url)) {
            wp_send_json_error(['message' => $short_url->get_error_message()], 500);
        }

        wp_send_json_success(['shortUrl' => $short_url]);
    }

    private function shorten_with_isgd(string $url) {
        $endpoint = add_query_arg([
            'format' => 'simple',
            'url' => rawurlencode($url),
        ], 'https://is.gd/create.php');

        $response = wp_remote_get($endpoint, [
            'timeout' => 12,
            'redirection' => 3,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('elsb_remote_error', __('ارتباط با سرویس کوتاه‌کننده برقرار نشد.', 'elsb'));
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = trim((string) wp_remote_retrieve_body($response));

        if ($code !== 200 || empty($body) || !filter_var($body, FILTER_VALIDATE_URL)) {
            return new WP_Error('elsb_invalid_response', __('پاسخ معتبر از سرویس کوتاه‌کننده دریافت نشد.', 'elsb'));
        }

        return esc_url_raw($body);
    }
}

new ELSB_Plugin();
