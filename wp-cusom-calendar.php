<?php

/*
Plugin Name: Календарь
Plugin URI: https://github.com/nikolays93/wp-developers-tool
Description: Плагин добавляет дополнительные настройки в WordPress.
Version: 5.1 beta
Author: NikolayS93
Author URI: https://vk.com/nikolays_93
Author EMAIL: nikolayS93@ya.ru
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define('CALEND_POST_TYPE', 'event');

add_action('plugins_loaded', 'initialize_calendar_plugin');
function initialize_calendar_plugin() {
    require_once __DIR__ . '/include/register-post-type-calendar.php';
    require_once __DIR__ . '/include/class-wp-custom-calendar.php';
}

add_action( 'get_header', function(){
    $calend = new WP_Custom_Calendar('calend');

    $calend->set_caption();
    $calend->set_body();
    $calend->set_week( $initial = true );
    $calend->set_month_selector();

    echo $calend->get_calendar();
} );
