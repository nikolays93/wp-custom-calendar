<?php

class WPCustomCalendar
{
    static $ts; // unix timestamp
    public $calendar_id;
    public $output;
    private $key;
    private $cache;

    public $args = array();
    public $date = array();

    function __construct( String $calendar_id, Array $args = null )
    {
        global $m, $monthnum, $year;

        $this->calendar_id = $calendar_id;

        $this->args = wp_parse_args( $args, array(
            'monthdate' => false,
            'yaerdate' => false,
            'week_begins' => (int) get_option( 'start_of_week' ),
            'post_type' => 'post',
            'render_type' => 'table',
            ) );

        $this->set_date();

        if( $this->get_cache() && count($this->cache) ) {
            $this->output = $this->cache;
            return;
        }

        /** validate */
        $post_types = get_post_types();
        if( ! in_array($this->args['post_type'], $post_types) ) {
            $this->output = apply_filters( $calendar_id . '_calendar_type_unregistred',
                $calendar_id . ' ' . __('calendar post type unregistred', DOMAIN) );
            return;
        }

        if( ! $this->got_someone( $this->args['post_type'] ) ) {
            $this->output = apply_filters( $calendar_id . '_calendar_empty',
                $calendar_id . ' ' . __('calendar is empty', DOMAIN) );
            return;
        }

        // if( 'bootstrap' === $this->args['render_type'] ) {
        //     $this->render_calendar_bootstrap( (Array) $this->get_days_with_posts( $this->args['post_type'] ) );
        // }
        // else {
            $this->render_calendar_table( (Array) $this->get_days_with_posts( $this->args['post_type'] ) );
        // }
    }

    function render_calendar_table( $dayswithpost ) {
        $this->output .= '<table id="wp-custom-calendar">';
        $this->output .= '<tbody><tr>';

        // See how much we should pad in the beginning
        $pad = calendar_week_mod( date( 'w', $this->date['unixmonth'] ) - $this->args[ 'week_begins' ] );
        if ( 0 != $pad ) {
            $this->output .= "\n\t\t".'<td colspan="'. esc_attr( $pad ) .'" class="pad">&nbsp;</td>';
        }

        $newrow = false;
        $daysinmonth = (int) date( 't', $this->date['unixmonth'] );
        $is_current_month =
            $this->date['month'] == gmdate( 'm', self::$ts ) &&
            $this->date['year'] == gmdate( 'Y', self::$ts );

        for ( $day = 1; $day <= $daysinmonth; ++$day ) {
            if ( $newrow ) {
                $this->output .= "\n\t</tr>\n\t<tr>\n\t\t";
            }
            $newrow = false;

            if ( $is_current_month && $day == gmdate( 'j', self::$ts ) ) {
                $this->output .= '<td id="today">';
            } else {
                $this->output .= '<td>';
            }

            // any posts today?
            if ( in_array( $day, $dayswithpost ) ) {
                // $date_format = date(
                //     _x( 'F j, Y', 'daily archives date format' ),
                //     strtotime( "{$this->date['year']}-{$this->date['month']}-{$day}" ) );
                // $label = sprintf( __( 'Posts published on %s' ), $date_format );
                $this->output .= sprintf(
                    '<a href="%s">%s</a>', // aria-label="%s"
                    get_day_link( $this->date['year'], $this->date['month'], $day ),
                    // esc_attr( $label ),
                    $day );
            } else {
                $this->output .= $day;
            }
            $this->output .= '</td>';

            if ( 6 == calendar_week_mod( date( 'w', mktime(0, 0 , 0, $this->date['month'], $day, $this->date['year'] ) ) - $this->args[ 'week_begins' ] ) ) {
                $newrow = true;
            }
        }

        $pad = 7 - calendar_week_mod( date( 'w', mktime( 0, 0 , 0, $this->date['month'], $day, $this->date['year'] ) ) - $this->args[ 'week_begins' ] );

        if ( $pad != 0 && $pad != 7 ) {
            $this->output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr( $pad ) .'">&nbsp;</td>';
        }

        $this->output .= "\n\t</tr>\n\t</tbody>\n\t";
        $this->output .= "</table>";
    }

    public function get_calendar() {
        if( ! $this->output )
            return '';

        $this->set_cache( $cache );
        return $this->output;
    }

    /******************************** Utilites ********************************/
    function set_date()
    {
        global $m, $monthnum, $year;

        /* Set current is empty */
        if( ! $this->args['monthdate'] ) $this->args['monthdate'] = $monthnum;
        if( ! $this->args['yaerdate'] ) $this->args['yaerdate'] = $year;

        $this->key = md5( $m . $this->args['monthdate'] . $this->args['yaerdate'] );

        self::$ts = current_time( 'timestamp' );

        // Let's figure out when we are
        // if ( ! empty( $monthnum ) && ! empty( $year ) ) {
        if( ! empty($this->args['monthdate']) && !empty($this->args['yaerdate']) ) {
            $this->date['month'] = zeroise( intval( $this->args['monthdate'] ), 2 );
            $this->date['year'] = (int) $this->args['yaerdate'];
        }
        else {
            $this->date['year'] = gmdate( 'Y', self::$ts );
            $this->date['month'] = gmdate( 'm', self::$ts );
        }

        $this->date['unixmonth'] = mktime( 0, 0 , 0, $this->date['month'], 1, $this->date['year'] );
        $this->date['last_day'] = date( 't', $this->date['unixmonth'] );
    }

    function get_days_with_posts( String $post_type ) {
        global $wpdb;

        $date = $this->date;
        $result = array();

        $dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
            FROM $wpdb->posts
            WHERE post_type = '{$post_type}'
            AND post_status = 'publish'
            AND post_date >= '{$date['year']}-{$date['month']}-01 00:00:00'
            AND post_date <= '{$date['year']}-{$date['month']}-{$date['last_day']} 23:59:59'", ARRAY_N);

        if ( $dayswithposts ) {
            foreach ( (array) $dayswithposts as $daywith ) {
                $result[] = $daywith[0];
            }
        }

        return $result;
    }

    function got_someone( String $post_type ) {
        global $wpdb;

        $sql = "
            SELECT 1 as test
            FROM $wpdb->posts
            WHERE post_type = '$post_type'
            AND post_status = 'publish'
            LIMIT 1";

        if( ! $gotsome = $wpdb->get_var($sql) ) {
            $cache = array();
            $this->set_cache( $cache );
        }

        return (bool) $gotsome;
    }

    // echo mysql2date( 'Y-m-d', get_post()->post_date, false );

    /********************************** Cache *********************************/
    function set_cache( Array $cache )
    {
        $this->cache[ $this->key ] = $this->output;
        wp_cache_set( 'get_calendar_' . $this->calendar_id, $this->cache, 'calendar' );
    }

    function get_cache()
    {
       $this->cache = wp_cache_get( 'get_calendar_' . $this->calendar_id, 'calendar' );

        if ( $this->cache && is_array( $this->cache ) && isset( $this->cache[ $this->key ] ) ) {
            $output = apply_filters( 'get_calendar_' . $this->calendar_id, $this->cache[ $this->key ] );

            return $output;
        }

        return array();
    }

    function delete_cache()
    {

        wp_cache_delete( 'get_calendar_' . $this->calendar_id, 'calendar' );
    }

    /******************************* Counstruct *******************************/
    public function set_caption($thismonth, $thisyear)
    {
        global $wp_locale;

        /* translators: Calendar caption: 1: month name, 2: 4-digit year */
        $calendar_caption = _x('%1$s %2$s', 'calendar caption');
        $this->output .= '<caption>' . sprintf(
            $calendar_caption,
            $wp_locale->get_month( $thismonth ),
            $thisyear
            ) . '</caption>';
    }

    public function set_week( $initial )
    {
        global $wp_locale;

        $this->output .= '<thead><tr>';

        $myweek = array();

        for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
            $myweek[] = $wp_locale->get_weekday( ( $wdcount + $this->week_begins ) % 7 );
        }

        foreach ( $myweek as $wd ) {
            $day_name = $initial ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
            $wd = esc_attr( $wd );
            $this->output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
        }

        $this->output .= '</tr></thead>';
    }

    public function set_month_selector( $thismonth, $thisyear, $last_day )
    {
        global $wpdb, $wp_locale;

        $this->output .= '<tfoot><tr>';

        // Get the next and previous month and year with at least one post
        $previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
            FROM $wpdb->posts
            WHERE post_date < '$thisyear-$thismonth-01'
            AND post_type = '".self::post_type."' AND (post_status = 'publish' OR post_status = 'future')
            ORDER BY post_date DESC
            LIMIT 1");

        $next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
            FROM $wpdb->posts
            WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
            AND post_type = '".self::post_type."' AND (post_status = 'publish' OR post_status = 'future')
            ORDER BY post_date ASC
            LIMIT 1");

        if ( $previous ) {
            $this->output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . get_month_link( $previous->year, $previous->month ) . '">&laquo; ' .
            $wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) .
            '</a></td>';
        } else {
            $this->output .= "\n\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
        }

        $this->output .= "\n\t\t".'<td class="pad">&nbsp;</td>';

        if ( $next ) {
            $this->output .= "\n\t\t".'<td colspan="3" id="next"><a href="' . get_month_link( $next->year, $next->month ) . '">' .
            $wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) .
            ' &raquo;</a></td>';
        } else {
            $this->output .= "\n\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
        }

        $this->output .= '
        </tr>
        </tfoot>';
    }
}

$calend = new WP_Custom_Calendar('calend');

// has cache
if( $calendar = $calend->get_calendar() ) {
    echo $calendar;
}
else {
    // $calend->set_caption($thismonth, $thisyear);
    // $calend->set_week( $initial = true );
    // $calend->set_month_selector( $thismonth, $thisyear, $last_day );
    echo $calend->get_calendar();
}
