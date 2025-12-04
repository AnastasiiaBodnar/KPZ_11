<?php
/*
Plugin Name: KPZ Smooth Animations
Description: Простий плагін анімацій при скролі (fade-in).
Version: 1.0
Author: Anastasiia Bodnar
*/

if (!defined('ABSPATH')) exit;

/* -------------------------
   Підключаємо CSS і JS
--------------------------- */
function kpz_animations_assets() {
    wp_enqueue_style(
        'kpz-animations-style',
        plugin_dir_url(__FILE__) . 'assets/style.css'
    );

    wp_enqueue_script(
        'kpz-animations-script',
        plugin_dir_url(__FILE__) . 'assets/script.js',
        array('jquery'),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'kpz_animations_assets');


/* -------------------------
   SHORTCODE: [kpz_animate]
--------------------------- */
function kpz_animation_shortcode($atts, $content = null) {
    return '<div class="kpz-fade-in">' . do_shortcode($content) . '</div>';
}
add_shortcode('kpz_animate', 'kpz_animation_shortcode');

