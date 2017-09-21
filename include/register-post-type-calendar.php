<?php

define('CALEND_POST_TYPE', 'event');

add_action('init', 'register_post_type_calendar');
function register_post_type_calendar() {
    register_post_type( CALEND_POST_TYPE, array(
        'query_var' => true,
        'rewrite' => true,
        'public' => true,
        // 'menu_position' => 10,
        'supports' => array('title', 'custom-fields', 'excerpt'),
        'labels' => array(
          'name' => 'События',
          'singular_name'      => 'Событие',
          'add_new'            => 'Новое событие',
          'add_new_item'       => 'Добавить событие',
          'edit_item'          => 'Изменить событие',
          'new_item'           => 'Новое событие',
          'view_item'          => 'Смотреть событие',
          'search_items'       => 'Искать событие',
          'menu_name'          => 'События',
      )
    ) );
}
