<?php

add_action('init', 'register_post_type_calendar');
function register_post_type_calendar() {
    $post_type = get_calend_post_type();
    $post_types_registred = get_post_types();

    if( in_array($post_type, $post_types_registred) )
      return;

    register_post_type( $post_type, array(
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
