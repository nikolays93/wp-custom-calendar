<?php

class WP_Custom_Calendar
{
    public $calendname = '';

    static public $ts;

    private $where_string;

    private $key;
    private $last_day;
    private $unixmonth;

    protected $args;
    protected $output;

    function set_date()
    {
        global $m, $monthnum, $year;

        if( $this->args['check_request'] ) {
            if( !empty($_REQUEST['monthdate']) ) {
                $this->args['monthdate'] = zeroise( intval( $_REQUEST['monthdate'] ), 2 );
            }

            if( !empty($_REQUEST['yeardate']) ) {
                $this->args['yeardate'] = (int) $_REQUEST['yeardate'];
            }
        }

        if( ! $this->args['monthdate'] ) {
            $this->args['monthdate'] = gmdate( 'm', self::$ts );
        }

        if( ! $this->args['yeardate'] ) {
            $this->args['yeardate'] = gmdate( 'Y', self::$ts );
        }
    }

    function is_empty()
    {
        global $wpdb;

        $empty = false;
        if( ! $gotsome = $wpdb->get_var("SELECT 1 as test
            FROM $wpdb->posts
            WHERE post_type {$this->prepare( $this->args['post_type'] )}
            AND post_status {$this->prepare( $this->args['post_status'] )} LIMIT 1") ) {
            $cache[ $this->key ] = '';
            $this->set_cache( $cache );
            $empty = true;
        }

        return $empty;
    }

    public function get_calendar()
    {
        if( $this->output ) {
            return '<table id="wp-custom-calendar">' . $this->output . '</table>';
        }

        return '';
    }

    private function prepare( $value )
    {
        if( is_array($value) ) {
            if( sizeof($value) >= 1 ) {
                $res = 'IN (\'' . implode('\',\'', $value) . '\')';
            }
            else {
                $res = '= \'' . current($value) . '\'';
            }
        }
        else {
            $res = '= \'' . $value . '\'';
        }

        return $res;
    }

    function __construct( $calendname = '', $args = array() )
    {
        global $wpdb, $wp_locale, $m;

        if( $this->calendname ) {
            $this->calendname = '_' . $this->calendname;
        }

        if( ! self::$ts ) {
            self::$ts = current_time( 'timestamp' );
        }

        $this->args = wp_parse_args( $args, array(
            'monthdate'   => false,
            'yeardate'    => false,
            'week_begins' => false,
            'post_type'   => defined( 'CALEND_POST_TYPE' ) ? CALEND_POST_TYPE : 'post',
            'post_status' => array('publish', 'future'),
            'check_request'  => true,
            ) );

        if( ! $this->args['monthdate'] || ! $this->args['yeardate'] ) {
            $this->set_date();
        }

        $this->key = md5( $m . $this->args['monthdate'] . $this->args['yeardate'] );

        if( $cache = $this->get_cache() ) {
            $this->output = $cache;
            return;
        }

        $this->where_string = "
            AND post_type {$this->prepare( $this->args['post_type'] )}
            AND post_status {$this->prepare( $this->args['post_status'] )}";

        $this->unixmonth = mktime( 0, 0 , 0, $this->args['monthdate'], 1, $this->args['yeardate'] );
        $this->last_day = date( 't', $this->unixmonth );

        if( false === $this->args['week_begins'] ) {
            $this->args['week_begins'] = (int) get_option( 'start_of_week' );
        }

        $cache = array($this->key => $this->output);
        $this->set_cache( $cache );
    }

    private function get_cache()
    {
        $cache = wp_cache_get( 'get_calendar', 'calendar' . $this->calendname );

        if ( $cache && is_array( $cache ) && isset( $cache[ $this->key ] ) ) {
            $output = apply_filters( 'get_calendar' . $this->calendname, $cache[ $this->key ] );

            return $output;
        }

        return false;
    }

    private function set_cache( $cache )
    {
        $calendname = '';
        if( $this->calendname ) {
            $calendname = '_' . $this->calendname;
        }

        wp_cache_set( 'get_calendar' . $calendname, $cache, 'calendar' );
    }

    public function set_caption()
    {
        global $wp_locale;

        /* translators: Calendar caption: 1: month name, 2: 4-digit year */
        $calendar_caption = _x('%1$s %2$s', 'calendar caption');
        $this->output .= '<caption>' . sprintf(
            $calendar_caption,
            $wp_locale->get_month( $this->args['monthdate'] ),
            $this->args['yeardate']
            ) . '</caption>';
    }

    public function set_week( $initial )
    {
        global $wp_locale;

        $this->output .= '<thead><tr>';

        $myweek = array();

        for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
            $myweek[] = $wp_locale->get_weekday( ( $wdcount + $this->args['week_begins'] ) % 7 );
        }

        foreach ( $myweek as $wd ) {
            $day_name = $initial ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
            $wd = esc_attr( $wd );
            $this->output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
        }

        $this->output .= '</tr></thead>';
    }

    public function set_month_selector()
    {
        global $wpdb, $wp_locale;

        $this->output .= '<tfoot><tr>';

        // Get the next and previous month and year with at least one post
        $previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
            FROM $wpdb->posts
            WHERE post_date < '{$this->args['yeardate']}-{$this->args['monthdate']}-01' {$this->where_string}
            ORDER BY post_date DESC
            LIMIT 1");

        $next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
            FROM $wpdb->posts
            WHERE post_date > '{$this->args['yeardate']}-{$this->args['monthdate']}-{$this->last_day} 23:59:59' {$this->where_string}
            ORDER BY post_date ASC
            LIMIT 1");

        if ( $previous ) {
            $href = "?monthdate={$previous->month}&yeardate={$previous->year}";
            $this->output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . $href . '">&laquo; ' .
            $wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) .
            '</a></td>';
        } else {
            $this->output .= "\n\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
        }

        $this->output .= "\n\t\t".'<td class="pad">&nbsp;</td>';

        if ( $next ) {
            $href = "?monthdate={$next->month}&yeardate={$next->year}";
            $this->output .= "\n\t\t".'<td colspan="3" id="next"><a href="' . $href . '">' .
            $wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) .
            ' &raquo;</a></td>';
        } else {
            $this->output .= "\n\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
        }

        $this->output .= '
        </tr>
        </tfoot>';
    }

    private function get_days_with_posts() {
        global $wpdb;
        $daywithpost = array();

        if( ! $this->is_empty() ) {
            // Get days with posts
            $dayswithposts = $wpdb->get_results("SELECT DISTINCT post_id
                FROM $wpdb->postmeta
                WHERE
                ( meta_key = 'post_date_from' AND   meta_value >= '{$this->args['yeardate']}-{$this->args['monthdate']}-01 00:00:00'
                AND   meta_value <= '{$this->args['yeardate']}-{$this->args['monthdate']}-{$this->last_day} 23:59:59' ) OR
                ( meta_key = 'post_date_to' AND   meta_value >= '{$this->args['yeardate']}-{$this->args['monthdate']}-01 00:00:00'
                AND   meta_value <= '{$this->args['yeardate']}-{$this->args['monthdate']}-{$this->last_day} 23:59:59' )
                ORDER BY meta_value ASC",
                ARRAY_A);

            $posts = array();
            foreach ($dayswithposts as $post) {
                $post_id = $post['post_id'];

                $from = new DateTime( get_post_meta( $post_id, 'post_date_from', true ) );
                $to = new DateTime( get_post_meta( $post_id, 'post_date_to', true ) );

                $post = new stdClass();
                $post->ID = $post_id;
                $post->from = $from->format('d');
                $post->to = $to->format('d');

                $daywithpost[] = $post;
            }
            // if ( $dayswithposts ) {
            //     foreach ( (array) $dayswithposts as $daywith ) {
            //         $daywithpost[] = $daywith[0];
            //     }
            // }
        }

        return $daywithpost;
    }

    public function set_body()
    {
        global $wpdb;

        $this->output .= '<tbody><tr>';

        $daywithpost = $this->get_days_with_posts();
        // var_dump($daywithpost);


        // See how much we should pad in the beginning
        $pad = calendar_week_mod( date( 'w', $this->unixmonth ) - $this->args['week_begins'] );
        if ( 0 != $pad ) {
            $this->output .= "\n\t\t".'<td colspan="'. esc_attr( $pad ) .'" class="pad">&nbsp;</td>';
        }

        $newrow = false;
        $daysinmonth = (int) date( 't', $this->unixmonth );

        // var_dump($daywithpost);
        for ( $day = 1; $day <= $daysinmonth; ++$day ) {
            if ( isset($newrow) && $newrow ) {
                $this->output .= "\n\t</tr>\n\t<tr>\n\t\t";
            }
            $newrow = false;

            if ( $day == gmdate( 'j', self::$ts ) &&
                $this->args['monthdate'] == gmdate( 'm', self::$ts ) &&
                $this->args['yeardate'] == gmdate( 'Y', self::$ts ) ) {
                $this->output .= '<td id="today">';
            } else {
                $this->output .= '<td>';
            }

            $c = current($daywithpost);
            if ( $c && $day >= $c->from && $day <= $c->to ) {
            // if ( in_array( $day, $daywithpost ) ) {
                // any posts today?
                $date_format = date( _x( 'F j, Y', 'daily archives date format' ), strtotime( "{$this->args['yeardate']}-{$this->args['monthdate']}-{$day}" ) );
                $label = sprintf( __( 'Posts published on %s' ), $date_format );
                $this->output .= sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    '#' . $c->ID,//get_day_link( $this->args['yeardate'], $this->args['monthdate'], $day ),
                    esc_attr( $label ),
                    $day
                );

                if( $day == $c->to ) {
                    $c = next($daywithpost);
                }
            } else {
                $this->output .= $day;
            }
            $this->output .= '</td>';

            if ( 6 == calendar_week_mod( date( 'w', mktime(0, 0 , 0, $this->args['monthdate'], $day, $this->args['yeardate'] ) ) - $this->args['week_begins'] ) ) {
                $newrow = true;
            }
        }

        $pad = 7 - calendar_week_mod( date( 'w', mktime( 0, 0 , 0, $this->args['monthdate'], $day, $this->args['yeardate'] ) ) - $this->args['week_begins'] );
        if ( $pad != 0 && $pad != 7 ) {
            $this->output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr( $pad ) .'">&nbsp;</td>';
        }
        $this->output .= "\n\t</tr>\n\t</tbody>\n\t";
    }
}