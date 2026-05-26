<?php

if (!defined('ABSPATH')) {
    exit;
}

class ELSB_Widget_Button extends \Elementor\Widget_Base {
    public function get_name() {
        return 'elsb_short_link_button';
    }

    public function get_title() {
        return __('دکمه کوتاه‌کننده لینک صفحه', 'elsb');
    }

    public function get_icon() {
        return 'eicon-link';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_script_depends() {
        return ['elsb-widget-script'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات دکمه', 'elsb'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('متن دکمه', 'elsb'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('کوتاه کردن لینک این صفحه', 'elsb'),
                'placeholder' => __('مثال: کپی لینک کوتاه', 'elsb'),
            ]
        );

        $this->add_control(
            'button_align',
            [
                'label' => __('تراز', 'elsb'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('چپ', 'elsb'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('وسط', 'elsb'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('راست', 'elsb'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'toggle' => true,
                'selectors' => [
                    '{{WRAPPER}} .elsb-wrap' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $text = !empty($settings['button_text']) ? $settings['button_text'] : __('کوتاه کردن لینک این صفحه', 'elsb');

        echo '<div class="elsb-wrap">';
        echo '<button type="button" class="elementor-button-link elementor-button elsb-short-btn">';
        echo '<span class="elementor-button-content-wrapper"><span class="elementor-button-text">' . esc_html($text) . '</span></span>';
        echo '</button>';
        echo '<div class="elsb-message" style="margin-top:8px;font-size:13px;"></div>';
        echo '</div>';
    }
}
