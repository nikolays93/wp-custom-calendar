<?php

function custom_calend_meta_field( $id ) {
    $day = '';
    $month = '';
    $year = '2017';
    if( $date = strtotime(get_post_meta( $_GET['post'], 'post_date_' . $id, true )) ) {
        $day = date("d", $date);
        $month = date("m", $date);
        $year = date("Y", $date);
    }

    $calend_months = array(
        '01' => '01-Янв',
        '02' => '02-Фев',
        '03' => '03-Мар',
        '04' => '04-Апр',
        '05' => '05-Май',
        '06' => '06-Июн',
        '07' => '07-Июл',
        '08' => '08-Авг',
        '09' => '09-Сен',
        '10' => '10-Окт',
        '11' => '11-Ноя',
        '12' => '12-Дек',
        );

    ?>
    <fieldset id="timestamp-event-<?=$id;?>" class="hide-if-js" style="display: block;">
        <legend class="screen-reader-text">Дата от</legend>
        <div class="timestamp-wrap">
            <label>
                <span class="screen-reader-text">День</span>
                <input type="text" id="calend-day-<?=$id;?>" name="calend-day-<?=$id;?>" value="<?=$day;?>" size="2" maxlength="2" autocomplete="off">
            </label>
            <label>
                <span class="screen-reader-text">Месяц</span>
                <select id="mm" name="calend-month-<?=$id;?>">
                    <?php
                    foreach ($calend_months as $m_key => $m) {
                        echo "<option value='{$m_key}' ".selected( $month, $m_key )."> {$m} </option> ";
                    }
                    ?>
                </select>
            </label>
            <label>
                <span class="screen-reader-text">Год</span>
                <input type="text" id="calend-year-<?=$id;?>" name="calend-year-<?=$id;?>" value="<?=$year;?>" size="4" maxlength="4" autocomplete="off">
            </label>
        </div>
    </fieldset>
    <?php
}

add_action( 'add_meta_boxes', 'calendar_meta_box' );
function calendar_meta_box() {
    add_meta_box('calend_meta_box', 'Календарь', 'calendar_meta_box_callback', defined('CALEND_POST_TYPE') ? CALEND_POST_TYPE : 'post', 'normal', 'high');
}

function calendar_meta_box_callback() {
    ?>
    <table class="table form-table">
        <tr>
            <th>Дата проведения от</th>
            <td>
                <?php custom_calend_meta_field('from'); ?>
            </td>
        </tr>
        <tr>
            <th>Дата проведения до</th>
            <td>
                <?php custom_calend_meta_field('to'); ?>
            </td>
        </tr>
        <tr>
            <th>Ссылка на событие</th>
            <td>
                <input type="text" name="calend-link">
            </td>
        </tr>
    </table>
    <?php
    wp_nonce_field( 'save_calend_data', 'save_data' );
}

add_action( 'save_post', 'calendar_meta_box_validate' );
function calendar_meta_box_validate( $post_id ){
        if ( ! isset( $_POST['save_data'] ) || ! wp_verify_nonce( $_POST['save_data'], 'save_calend_data' ) ) {
            return false;
        }

        $atts = shortcode_atts( array(
            'calend-day-from'   => false,
            'calend-month-from' => '09',
            'calend-year-from'  => '2017',
            'calend-day-to'   => false,
            'calend-month-to' => '09',
            'calend-year-to'  => '2017',
            'calend-link'       => false,
            ), $_POST );

        if( $atts['calend-day-from'] || $atts['calend-day-to'] ) {
            $datetime_format_from = sprintf('%04d-%02d-%02d 00:00:00',
                $atts['calend-year-from'],
                $atts['calend-month-from'],
                $atts['calend-day-from'] );

            $datetime_format_to = sprintf('%04d-%02d-%02d 00:00:00',
                $atts['calend-year-to'],
                $atts['calend-month-to'],
                $atts['calend-day-to'] );


            if( $atts['calend-day-to'] && strtotime($datetime_format_from) > strtotime($datetime_format_to) ) {
                // flip data
                $temp = $datetime_format_from;
                $datetime_format_from = $datetime_format_to;
                $datetime_format_to = $temp;
                unset($temp);
            }

            // save from
            if( $atts['calend-day-from'] ) {
                update_post_meta( $post_id, 'post_date_from', $datetime_format_from );
            }

            // save to (or from if empty)
            if( $atts['calend-day-to'] ) {
                update_post_meta( $post_id, 'post_date_to', $datetime_format_to );
            }
            else {
                update_post_meta( $post_id, 'post_date_to', $datetime_format_from );
            }
        }

        if( $atts['calend-link'] ) {
            add_post_meta( $post_id, 'calend_link', esc_url($atts['calend-link']) );
        }

    }