<?php

add_action( 'add_meta_boxes', 'calendar_meta_box' );
function calendar_meta_box() {
    add_meta_box('calend_meta_box', 'Календарь', 'calendar_meta_box_callback', CALEND_POST_TYPE, 'normal', 'high');
}

function calendar_meta_box_callback() {
    echo "hello world!";
}