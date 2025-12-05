<?php

function kpz_enqueue_styles() {
    wp_enqueue_style('kpz-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'kpz_enqueue_styles');
