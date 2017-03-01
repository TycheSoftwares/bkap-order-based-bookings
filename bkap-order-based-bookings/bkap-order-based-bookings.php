<?php 

/*
Plugin Name: Order Based Bookings Addon
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
Description: This addon lets you to edit the bookings for all the products together in one go.
Version: 1.0
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
*/
    
function is_bkap_order_based_bookings_active() {
    if( is_plugin_active( 'bkap-order-based-bookings/bkap-order-based-bookings.php' ) ) {
        return true;
    } else {
        return false;
    }
}

//include_once( 'admin-bookings.php' );
class bkap_order_based_bookings {
    public function __construct(){
        add_action( 'bkap_add_addon_settings', array( &$this, 'bkap_order_based_settings' ), 10, 1 );
        add_action( 'admin_init', array( &$this, 'bkap_order_based_plugin_options' ) );
        
        //View Bookings Order based
        add_filter( 'bkap_view_bookings_table_columns', array( &$this, 'wapbk_view_bookings_column_names' ), 10, 1 );
        add_filter( 'bkap_bookings_table_data', array( &$this, 'wapbk_view_bookings_column_data' ), 10, 1 );
        add_filter( 'bkap_bookings_table_data_count', array( &$this, 'wapbk_view_bookings_column_data_count' ), 10, 2 );
        add_filter( 'bkap_bookings_calendar_view_data', array( &$this, 'wapbk_bookings_calendar_view_data' ), 10, 1 );
        add_filter( 'bkap_bookings_calendar_view_qtip_content', array( &$this, 'wapbk_bookings_calendar_view_qtip_content' ), 10, 1 );
        add_filter( 'bkap_view_bookings_print_columns', array( &$this, 'wapbk_view_bookings_print_columns' ), 10, 1 );
        add_filter( 'bkap_bookings_export_data', array( &$this, 'wapbk_bookings_export_data' ), 10, 1 );
        add_filter( 'bkap_view_bookings_print_rows', array( &$this, 'wapbk_view_bookings_print_rows' ), 10, 2 );
        add_filter( 'bkap_bookings_csv_data', array( &$this, 'wapbk_bookings_csv_data' ), 10, 2 );
        
        //Add to Calendar per order
        add_filter( 'bkap_bookings_show_add_to_calendar_button', array( &$this, 'wapbk_bookings_show_add_to_calendar_button' ), 10, 1 );
        add_filter( 'bkap_ics_file_content', array( &$this, 'wapbk_ics_file_content' ), 10, 2 );
        
        //Edit bookings per order
        add_action( 'add_meta_boxes', array( &$this, 'wapbk_admin_booking_box' ) );
        add_action( 'wp_ajax_woocommerce_save_order_items', array( &$this, 'orddd_load_delivery_dates' ) );
        add_action( 'wp_ajax_save_booking_dates_call', array( &$this, 'save_booking_dates_call' ) );
        add_action( 'wp_ajax_bkap_check_for_order_based_time_slot_admin', array( &$this, 'bkap_check_for_order_based_time_slot_admin' ) );
        add_action( 'wp_ajax_bkap_insert_admin_date_order_based', array( &$this, 'bkap_insert_admin_date_order_based' ) );
        
        //Booking confirmation Order based
        add_filter( 'bkap_approve_booking_order_based', array( &$this, 'wapbk_approve_booking_order_based' ), 10, 1 );
        add_filter( 'bkap_save_booking_status_order_based', array( &$this, 'wapbk_save_booking_status_order_based' ), 10, 1 );
        add_filter( 'bkap_customer_booking_cancelled_email', array( &$this, 'wapbk_customer_booking_cancelled_email' ), 10, 1 );

        //is order based booking confirmation enabled 
        add_filter( 'bkap_is_order_based_booking_confirmation', array( &$this, 'wapbk_is_order_based_booking_confirmation' ) );
    }
    
    

    function bkap_order_based_settings() {
        if ( isset( $_GET[ 'action' ] ) ) {
            $action = $_GET[ 'action' ];
        } else {
            $action = '';
        }
            
        if ( 'addon_settings' == $action ) {
            ?>
            <div id="content">
                <form method="post" action="options.php">
                <?php settings_fields( 'bkap_order_based_booking_settings' ); ?>
                <?php do_settings_sections( 'woocommerce_booking_page-bkap_order_based_settings_section' ); ?> 
                <?php submit_button(); ?>
                </form>
            </div>
            <?php 
        }
    }
    
    function bkap_order_based_plugin_options() {
        add_settings_section (
            'bkap_order_based_settings_section',         // ID used to identify this section and with which to register options
            __( 'Order Based Bookings Addon Settings', 'bkap-order-based-bookings' ),                  // Title to be displayed on the administration page
            array( $this, 'bkap_order_based_bookings_callback' ), // Callback used to render the description of the section
            'woocommerce_booking_page-bkap_order_based_settings_section'     // Page on which to add this section of options
        );
        
        add_settings_field (
            'global_booking_view_bookings_view',
            __( 'Display bookings based on per order:', 'printable-tickets' ),
            array( &$this, 'global_booking_view_bookings_view_callback' ),
            'woocommerce_booking_page-bkap_order_based_settings_section',
            'bkap_order_based_settings_section',
            array( __( 'Enable the checkbox if you want to displays the bookings for each order on Booking -> View Bookings page.  Note: Booking dates/s and/or time be taken of the first product in the order.', 'bkap-order-based-bookings' ) )
        );

        add_settings_field (
            'global_show_add_to_calendar_view',
            __( 'Show "Add to Calendar" button on Order Received page per order:', 'printable-tickets' ),
            array( &$this, 'global_show_add_to_calendar_view_callback' ),
            'woocommerce_booking_page-bkap_order_based_settings_section',
            'bkap_order_based_settings_section',
            array( __( 'Shows the \'Add to Calendar\' button on the Order Received page per order. Note: Booking dates/s and/or time be taken of the first product in the order.', 'bkap-order-based-bookings' ) )
        );

        add_settings_field (
            'global_allow_edit_bookings_order_based',
            __( 'Allow edit of bookings per order on WooCommerce Edit orders page:', 'printable-tickets' ),
            array( &$this, 'global_allow_edit_bookings_order_based_callback' ),
            'woocommerce_booking_page-bkap_order_based_settings_section',
            'bkap_order_based_settings_section',
            array( __( 'Enable this checkbox to allow admin to edit the booking date/s and/or time order based and not per product. Note: Booking date/s and/or time will be loaded for the first product in the order.', 'bkap-order-based-bookings' ) )
        );

        add_settings_field (
            'global_allow_booking_confirmation_order_based',
            __( 'Allow Booking confirmation per order:', 'printable-tickets' ),
            array( &$this, 'global_allow_booking_confirmation_order_based_callback' ),
            'woocommerce_booking_page-bkap_order_based_settings_section',
            'bkap_order_based_settings_section',
            array( __( 'Enable this checkbox to allow admin Booking confirmation of orders per order based.', 'bkap-order-based-bookings' ) )
        );

        register_setting( 
            'bkap_order_based_booking_settings',
            'global_booking_view_bookings_view'
        );

        register_setting( 
            'bkap_order_based_booking_settings',
            'global_show_add_to_calendar_view'
        );

        register_setting(
            'bkap_order_based_booking_settings',
            'global_allow_edit_bookings_order_based'
        );

        register_setting(
            'bkap_order_based_booking_settings',
            'global_allow_booking_confirmation_order_based'
        );
    }

    function bkap_order_based_bookings_callback() {

    }

    function global_booking_view_bookings_view_callback( $args ) {
        $global_booking_view_bookings_view = "";
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $global_booking_view_bookings_view = 'checked';
        }
        echo '<input type="checkbox" id="global_booking_view_bookings_view" name="global_booking_view_bookings_view"' . $global_booking_view_bookings_view .'/>';
        $html = '<label for="global_booking_view_bookings_view"> ' . $args[ 0 ] . '</label>';
        echo $html;
    }

    function global_show_add_to_calendar_view_callback( $args ) {
        $global_show_add_to_calendar_view = "";
        if( get_option( 'global_show_add_to_calendar_view' ) == 'on' ) {
            $global_show_add_to_calendar_view = 'checked';
        }
        echo '<input type="checkbox" id="global_show_add_to_calendar_view" name="global_show_add_to_calendar_view"' . $global_show_add_to_calendar_view .'/>';
        $html = '<label for="global_show_add_to_calendar_view"> ' . $args[ 0 ] . '</label>';
        echo $html;   
    }

    function global_allow_edit_bookings_order_based_callback( $args ) {
        $global_allow_edit_bookings_order_based = "";
        if( get_option( 'global_allow_edit_bookings_order_based' ) == 'on' ) {
            $global_allow_edit_bookings_order_based = 'checked';
        }
        echo '<input type="checkbox" id="global_allow_edit_bookings_order_based" name="global_allow_edit_bookings_order_based"' . $global_allow_edit_bookings_order_based .'/>';
        $html = '<label for="global_allow_edit_bookings_order_based"> ' . $args[ 0 ] . '</label>';
        echo $html;   
    }

    function global_allow_booking_confirmation_order_based_callback( $args ) {
        $global_allow_booking_confirmation_order_based = "";
        if( get_option( 'global_allow_booking_confirmation_order_based' ) == 'on' ) {
            $global_allow_booking_confirmation_order_based = 'checked';
        }
        echo '<input type="checkbox" id="global_allow_booking_confirmation_order_based" name="global_allow_booking_confirmation_order_based"' . $global_allow_booking_confirmation_order_based .'/>';
        $html = '<label for="global_allow_booking_confirmation_order_based"> ' . $args[ 0 ] . '</label>';
        echo $html;   
    }

    function wapbk_view_bookings_column_names( $columns ) {
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $columns = array(
                'ID'     		     => __( 'Order Id', 'woocommerce-booking' ),
                'name'  		     => __( 'Customer Name', 'woocommerce-booking' ),
                'checkin_date'       => __( 'Check-in Date', 'woocommerce-booking' ),
    			'checkout_date'      => __( 'Check-out Date', 'woocommerce-booking' ),
    			'booking_time'       => __( 'Booking Time', 'woocommerce-booking' ),
    			'number_of_products' => __( 'Number of Products', 'woocommerce-booking' ),
    			'amount'  		     => __( 'Amount', 'woocommerce-booking' ),
    			'order_date'  	     => __( 'Order Date', 'woocommerce-booking' ),
    			'actions'  		     => __( 'Actions', 'woocommerce-booking' )
            );
        }
        return $columns;
    }
    
    function wapbk_view_bookings_column_data( $booking_data ) {
        global $wpdb;
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $booking_data = array();
            $bkap_view_bookings = new WAPBK_View_Bookings_Table();
            $per_page     = $bkap_view_bookings->per_page;
            $results      = array();
            $current_time = current_time( 'timestamp' );
            $current_date = date( "Y-m-d", $current_time );

            if ( isset( $_GET['status'] ) && $_GET['status'] == 'future' ) {
                $booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date >= '".$current_date."' ORDER BY a2.order_id DESC";
                $query_results         = $wpdb->get_results( $booking_query );
            } else if ( isset( $_GET['status'] ) && $_GET['status'] == 'today_checkin' ) {
                $booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.start_date = '".$current_date."' ORDER BY a2.order_id DESC";
                $query_results         = $wpdb->get_results( $booking_query );
            } else if ( isset( $_GET['status'] ) && $_GET['status'] == 'today_checkout' ) {
                $booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a1.end_date = '".$current_date."' ORDER BY a2.order_id DESC";
                $query_results         = $wpdb->get_results( $booking_query );
            } else {
                $booking_query   = "SELECT *,a2.order_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id ORDER BY a2.order_id DESC";
                $query_results         = $wpdb->get_results( $booking_query );
            }
            
            $results = array();
            $order_ids = array();
            $_status = '';
            if ( isset( $_GET[ 'status' ] ) ) {
                $_status = $_GET[ 'status' ];
            }
            
            switch ( $_status ) {
                case 'pending_confirmation':
                    $_status = 'pending-confirmation';
                    break;
                case 'unpaid':
                    $_status = 'confirmed';
                    break;
                case 'future':
                    $_status = 'paid';
                    break;
                case 'today_checkout':
                    $_status = 'paid';
                    break;
                case 'today_checkin':
                    $_status = 'paid';
                    break;
                default:
                    $_status = '';
                    break;
            
            }
            
            $records = array();
            foreach ( $query_results as $key => $value ) {
                $records[ $value->order_id ][] = $value;
            }
            
            foreach ( $query_results as $key => $value ) {
				if( !in_array( $value->order_id, $order_ids ) ) {
                    $order   =   new WC_Order( $value->order_id );
                    $get_items = $order->get_items();
                    $records_for_order = end( $records[ $value->order_id ] );
                    foreach( $get_items as $item_id => $item_values ) {
                        $booking_status = '';
                        if ( $records_for_order->post_id == $item_values[ 'product_id' ] ) {
                            if ( isset( $item_values[ 'wapbk_booking_status' ] ) )  {
                                $booking_status = $item_values[ 'wapbk_booking_status' ];
                            }
                            if ( isset( $booking_status ) ) {
                                if ( $_status == $booking_status ) {
                                    $results[] = $records_for_order;//$query_results[ $key ];
                                } else if ( ( $booking_status != 'confirmed' && $booking_status != 'pending-confirmation' ) && ( 'paid' == $_status ) ) {
                                    $results[] = $records_for_order;
                                } else if( $booking_status != 'cancelled' && '' == $_status ) {
                                    $results[] = $records_for_order;
                                }
                            }
                        }
                    }
                }
                $order_ids[] = $value->order_id;
            }
            
            $i = 0;
            foreach ( $results as $key => $value ) {
                $number_of_products = 0;
                $time = '';
                $order   =   new WC_Order( $value->order_id );
                $get_items = $order->get_items();
                foreach( $get_items as $item_id => $item_values ) {
                    $number_of_products += 1;
                }
                $booking_data[ $i ] = new stdClass();
                $booking_data[ $i ]->name  =   $order->billing_first_name . " " . $order->billing_last_name;
                $booking_data[ $i ]->number_of_products = $number_of_products;
                $booking_data[ $i ]->ID            = $value->order_id;
                $booking_data[ $i ]->booking_id    = $value->booking_id;
                $booking_data[ $i ]->product_id    = $value->post_id;
                $booking_data[ $i ]->checkin_date  = $value->start_date;
                $booking_data[ $i ]->checkout_date = $value->end_date;
                if ( $value->from_time != "" ) {
                    $time = $value->from_time;
                }
                if ( $value->to_time != "" ) {
                    $time .=  " - " . $value->to_time;
                }
                $booking_data[ $i ]->booking_time    = $time;
                $booking_data[ $i ]->amount          = $order->get_total();
                $booking_data[ $i ]->order_date      = $order->completed_date;
                $i++;
            }
            //sort for order Id
            if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'ID' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_order_id_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_order_id_dsc" ) );
                }
            } else if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'amount' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_amount_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_amount_dsc" ) );
                }
            } else if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'quantity' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_quantity_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_quantity_dsc" ) );
                }
            } else if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'order_date' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_order_date_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_order_date_dsc" ) );
                }
            } else if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'checkin_date' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table", "bkap_class_checkin_date_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table", "bkap_class_checkin_date_dsc" ) );
                }
            } else if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'checkout_date' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table", "bkap_class_checkout_date_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table", "bkap_class_checkout_date_dsc" ) );
                }
            } else if ( isset( $_GET[ 'orderby' ] ) && $_GET[ 'orderby' ] == 'name' ) {
                if ( isset( $_GET[ 'order' ] ) && $_GET[ 'order' ] == 'asc' ) {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_name_asc" ) );
                } else {
                    usort( $booking_data, array( "WAPBK_View_Bookings_Table" , "bkap_class_name_dsc" ) );
                }
            }
            $search_results = array();
            if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
                $date            = '';
                $date_formats    = bkap_get_book_arrays( 'date_formats' );
                // get the global settings to find the date formats
                $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
                $date_format_set = $date_formats[ $global_settings->booking_date_format ];
                $date_formatted  = date_create_from_format( $date_format_set, $_GET[ 's' ] );
                if ( isset( $date_formatted ) && $date_formatted != '' ) {
                    $date = date_format( $date_formatted, 'Y-m-d' );
                }
                $time = $from_time = $to_time = '';
                if ( strpos( $_GET[ 's' ], '-' ) ) {
                    $time_array = explode( '-', $_GET[ 's' ] );
                    if ( isset( $time_array[0] ) && $time_array[0] != '' ) {
                        $from_time = date( 'G:i', strtotime( trim( $time_array[0] ) ) );
                    }
                    if ( isset( $time_array[1] ) && $time_array[1] !== '' ) {
                        $to_time = date( 'G:i', strtotime( trim( $time_array[1] ) ) );
                    }
                    $time = $from_time . " - " . $to_time;
                }
                foreach ( $booking_data as $key => $value ) {
                    if ( is_numeric( $_GET[ 's' ] ) ) {
                        if ( $value->ID == $_GET[ 's' ] ) {
                            $search_results[] = $booking_data[ $key ];
                        }
                    } else {
                        foreach ( $value as $k => $v ) {
                            if ( $k == 'checkin_date' || $k == 'checkout_date' && $date != '' ) {
                                if ( stripos( $v, $date ) !== false ) {
                                    $search_results[] = $booking_data[ $key ];
                                }
                            } else if ( $k == 'booking_time' ) {
                                if ( isset( $v ) && $v != '' && $time != '' ) {
                                    if ( stripos( $v, $time ) !== false ) {
                                        $search_results[] = $booking_data[ $key ];
                                    }
                                }
                            } else {
                                if ( stripos( $v, $_GET[ 's' ] ) !== false ) {
                                    $search_results[] = $booking_data[ $key ];
                                }
                            }
                        }
                    }
                }
                if ( is_array( $search_results ) && count( $search_results ) > 0 ) {
                    $booking_data = $search_results;
                } else {
                    $booking_data = array();
                }
            }
            
            if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
                $page_number = $_GET[ 'paged' ] - 1;
                $k           = $per_page * $page_number;
            } else {
                $k = 0;
            }
            
            $return_booking_display = array();
            for ( $j = $k; $j < ( $k+$per_page ); $j++ ) {
                if ( isset( $booking_data[ $j ] ) ) {
                    $return_booking_display[ $j ] = $booking_data[ $j ];
                } else {
                    break;
                }
            }
        }
        return $booking_data;
    }
    
    function wapbk_view_bookings_column_data_count( $bookings_count, $args ) {
        global $wpdb;
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $bookings_count = array(
                'total_count' => 0,
                'future_count' => 0,
                'today_checkin_count' => 0,
                'today_checkout_count' => 0,
                'unpaid' => 0,
                'pending_confirmation' => 0
            );
            //Today's date
            $current_time = current_time( 'timestamp' );
            $current_date = date( "Y-m-d", $current_time );
            $start_date   = $end_date = '';
            if ( isset( $args[ 'start-date' ] ) ) {
                $start_date = $args['start-date'];
            }
            if ( isset( $args['end-date'] ) ) {
                $end_date = $args['end-date'];
            }
            if ( $start_date != '' && $end_date != '' && $start_date != '1970-01-01' && $end_date != '1970-01-01' ) {
            } else {
                $today_query = "SELECT a2.order_id,a1.start_date,a1.end_date,a1.post_id FROM `".$wpdb->prefix."booking_history` AS a1,`".$wpdb->prefix."booking_order_history` AS a2 WHERE a1.id = a2.booking_id";
            }
            $results_date = $wpdb->get_results ( $today_query );
            
            $order_ids = array();
            foreach ( $results_date as $key => $value ) {
                if( !in_array( $value->order_id, $order_ids ) ) {
                    $post_data = get_post( $value->order_id );
                    if ( isset( $post_data->post_status ) && $post_data->post_status != 'wc-refunded' && $post_data->post_status != 'trash' && $post_data->post_status != 'wc-cancelled' && $post_data->post_status != '' && $post_data->post_status != 'wc-failed' ) {
                        // Order details
                        $order   =   new WC_Order( $value->order_id );
                        $get_items = $order->get_items();
                        foreach( $get_items as $item_id => $item_values ) {
                            $booking_status = '';
                            if ( $value->post_id == $item_values[ 'product_id' ] ) {
                                if ( isset( $item_values[ 'wapbk_booking_status' ] ) ) {
                                    $booking_status = $item_values[ 'wapbk_booking_status' ];
                                }
                
                                if ( isset( $booking_status ) ) {
                                    // if it's not cancelled, add it to the All count
                                    if ( 'cancelled' != $booking_status ) {
                                        $bookings_count['total_count'] += 1;
                                    }
                
                                    // Unpaid count
                                    if ( 'confirmed' == $booking_status ) {
                                        $bookings_count[ 'unpaid' ] += 1;
                                    } else if( 'pending-confirmation' == $booking_status ) { // pending confirmation count
                                        $bookings_count[ 'pending_confirmation' ] += 1;
                                    } else if ( 'paid' == $booking_status || '' == $booking_status ) {
            
                                        if ( $value->start_date >= $current_date ) { // future count
                                            $bookings_count['future_count'] += 1;
                                        }
                                        if ( $value->start_date == $current_date ) { // today's checkin's
                                            $bookings_count['today_checkin_count'] += 1;
                                        }
                                        if ( $value->end_date == $current_date ) { // today's checkouts
                                            $bookings_count['today_checkout_count'] += 1;
                                        }
                    
                                    }
                                }
                            }
                        }
                    }
                    $order_ids[] = $value->order_id;
                }
            }
        }
        return $bookings_count;
    }   
    
    function wapbk_bookings_calendar_view_data( $data ) {
        $order_ids = $data_array = array();
        $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
		
        $records = array();
		foreach( $data as $data_key => $data_value ) {
			$records[ $data_value[ 'id' ] ][] = $data_value; 
		}
        
       if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            foreach( $data as $data_key => $data_value ) {
                if( !in_array( $data_value[ 'id' ], $order_ids ) ) {
                    $title = "Order Number: " . $data_value[ 'id' ];
                    $records_for_order = end( $records[ $data_value[ 'id' ] ] );
                    array_push( $data_array, array(
                        'id'       =>  $records_for_order[ 'id' ],
                        'title'    =>  $title,
                        'start'    =>  $records_for_order[ 'start' ],
                        'end'      =>  $records_for_order[ 'end' ],
                        'value'    =>  $records_for_order[ 'value' ],
                        )
                    );
                    $order_ids[] = $data_value[ 'id' ];
                }
            }
        } else {
            $data_array = $data;
        }
        return( $data_array );
    }
    
    function wapbk_bookings_calendar_view_qtip_content( $content ) {
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $content = '';
            $date_formats = bkap_get_book_arrays( 'date_formats' );
            $date_format_set = $date_formats[ $saved_settings->booking_date_format ];
            if( !empty( $_REQUEST[ 'order_id' ] ) && ! empty( $_REQUEST[ 'event_value' ] ) ) {
                $order                    =   new WC_Order( $_REQUEST[ 'order_id' ] );
                $order_items              =   $order->get_items();
                $attribute_name           =   '';
                $attribute_selected_value =   '';
                $value[]                  =   $_REQUEST[ 'event_value' ];
                $number_of_products = 0;
                foreach( $order_items as $item_id => $item_values ) {
                    $number_of_products += 1;
                }
                $content                  =   "<table>
                    <tr> <td> <strong>Order: </strong></td><td><a href=\"post.php?post=". $order->id."&action=edit\">#" . $order->id ." </a> </td> </tr>
                    <tr> <td> <strong>Number of Products:</strong></td><td> " . $number_of_products . "</td> </tr>
                    <tr> <td> <strong>Customer Name:</strong></td><td> " . $order->billing_first_name . " " . $order->billing_last_name . "</td> </tr>" ;
                if ( isset( $value[ 0 ][ 'start_date' ] ) && $value[ 0 ][ 'start_date' ] != '0000-00-00' ) {
                    $date        = strtotime( $value[ 0 ][ 'start_date' ] );
                    $value_date  = date( $date_format_set, $date );
                    $content    .= " <tr> <td> <strong>Start Date:</strong></td><td> " . $value_date . "</td> </tr>";
                }
                if ( isset( $value[ 0 ][ 'end_date' ] ) && $value[ 0 ][ 'end_date' ] != '0000-00-00' ) {
                    $date            = strtotime( $value[ 0 ][ 'end_date' ] );
                    $value_end_date  = date( $date_format_set, $date );
                    $content        .= " <tr> <td> <strong>End Date:</strong></td><td> " . $value_end_date . "</td> </tr> ";
                }
                // Booking Time
                $time = '';
                if ( isset( $value[ 0 ][ 'from_time' ] ) && $value[ 0 ][ 'from_time' ] != "" && isset( $value[ 0 ][ 'to_time' ] ) && $value[0]['to_time'] != "" ) {
                    if ( $saved_settings->booking_time_format == 12 ) {
                        $to_time     = '';
                        $from_time   = date( 'h:i A', strtotime( $value[0][ 'from_time' ] ) );
                        $time        = $from_time ;
                        if ( isset( $value[0][ 'to_time' ] ) && $value[0][ 'to_time' ] != '' ) {
                            $to_time = date( 'h:i A', strtotime( $value[0][ 'to_time' ] ) );
                            $time    = $from_time . " - " . $to_time;
                        }
                    } else {
                        $time = $time = $value[0]['from_time'] . " - " . $value[0]['to_time'];
                    }
                    $content .= "<tr> <td> <strong>Time:</strong></td><td> " . $time . "</td> </tr>";
                } else if ( isset( $value[ 0 ][ 'from_time' ] ) && $value[ 0 ][ 'from_time' ] != "" ) {
                    if ( $saved_settings->booking_time_format == 12 ) {
                        $to_time = '';
                        $from_time = date( 'h:i A', strtotime( $value[0]['from_time'] ) );
                        $time = $from_time. " - Open-end" ;
                    } else {
                        $time = $time = $value[0]['from_time'] ." - Open-end";
                    }
                    $content .= "<tr> <td> <strong>Time:</strong></td><td> " . $time . "</td> </tr>";
                }
                $content .= '</table>';
            }
        }
        return $content;
    }
    
    function wapbk_view_bookings_print_columns( $columns ) {
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $columns  = "<tr>
                <th style='border:1px solid black;padding:5px;'>".__( 'Order ID', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Customer Name', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Check-in Date', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Check-out Date', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Booking Time', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Number of Products', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Amount', 'woocommerce-booking' )."</th>
                <th style='border:1px solid black;padding:5px;'>".__( 'Order Date', 'woocommerce-booking' )."</th>
            </tr>";
        }
        return $columns;
    }
    
    function wapbk_bookings_export_data( $report ) {
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $i = 0; $order_ids = array();
			$records = array();
			foreach( $report as $report_key => $report_value ) {
				$records[ $report_value->order_id ][] = $report_value; 
			}

            foreach( $report as $report_key => $report_value ) {
                if( !in_array( $report_value->order_id, $order_ids ) ) {     
                    $order = new WC_Order( $report_value->order_id );
                    $get_items = $order->get_items();
                    $number_of_products = 0;

                    $records_for_order = end( $records[ $report_value->order_id ] );
					$report_value = $records_for_order;
					
                    $get_items = array_reverse( $get_items, true );
                    foreach( $get_items as $k => $v ) {
                        $number_of_products += 1;
                    }
					
                    $report_value->number_of_products = $number_of_products;
                    $report_value->amount       = $order->get_total();
					$report[ $report_key ] = $report_value ;
                    $order_ids[] = $report_value->order_id;
                } else {
                    unset( $report[ $report_key ] );
                }
            }
        }

        return $report;
    }
    
    function wapbk_view_bookings_print_rows( $print_data_row_data, $report ) {
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $print_data_row_data = '';
            foreach ( $report as $key => $value ) {
                // Currency Symbol
                // The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
                $the_order          = wc_get_order( $value->order_id );
                $currency           = $the_order->get_order_currency();
                $currency_symbol    = get_woocommerce_currency_symbol( $currency );
                $print_data_row_data .= "<tr>
    				<td style='border:1px solid black;padding:5px;'>" . $value->order_id . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $value->customer_name . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $value->checkin_date . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $value->checkout_date . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $value->time . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $value->number_of_products . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $currency_symbol . $value->amount . "</td>
    				<td style='border:1px solid black;padding:5px;'>" . $value->order_date . "</td>
                </tr>";
            }
        }
        return $print_data_row_data;
    }
    
    function wapbk_bookings_csv_data( $csv, $report ) {
        if( get_option( 'global_booking_view_bookings_view' ) == 'on' ) {
            $csv = 'Order ID,Customer Name,Check-in Date,Check-out Date,Booking Time,Number of Products,Amount,Order Date';
       		$csv .= "\n";
       		foreach ( $report as $key => $value ) {
       			$order_id           = $value->order_id;
       			$customer_name      = $value->customer_name;
       			$checkin_date       = $value->checkin_date;
       			$checkout_date      = $value->checkout_date;
       			$time               = $value->time;
       			$number_of_products = $value->number_of_products;
       			$amount             = $value->amount;
       			$order_date         = $value->order_date;
       			// Currency Symbol
       			// The order currency is fetched to ensure the correct currency is displayed if the site uses multi-currencies
       			$the_order          = wc_get_order( $value->order_id );
       			$currency           = $the_order->get_order_currency();
       			$currency_symbol    = get_woocommerce_currency_symbol( $currency );
       			// Create the data row
       			$csv .= $order_id . ',' . $customer_name  . ',"' . $checkin_date . '","' . $checkout_date . '","' . $time . '",' . $number_of_products . ',' . $currency_symbol . $amount . ',' . $order_date;
       			$csv .= "\n";  
       		}
        }
        return $csv;
    }
    
    function wapbk_bookings_show_add_to_calendar_button( $order ) {
        global $wpdb;
        if( get_option( 'global_show_add_to_calendar_view' ) == 'on' ) {
            $order_obj    = new WC_Order( $order->id );
            $order_items  = $order_obj->get_items();
            $today_query  = "SELECT * FROM `" . $wpdb->prefix."booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = %d";
            $results_date = $wpdb->get_results ( $wpdb->prepare( $today_query, $order->id ) );
            $product_names = "";
            foreach ( $order_items as $item_key => $item_value ) {
                $product_names .= $item_value[ 'name' ] . ", ";
            }
            $product_names = substr( $product_names, 0, -2 );
            $time = $time_start = $time_end = 0;
            foreach ( $order_items as $item_key => $item_value ) {
                $duplicate_of      = bkap_common::bkap_get_product_id( $item_value[ 'product_id' ] );
                $booking_settings  = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
                for ( $c = 0; $c < count( $results_date ); $c++ ) {
                    if ( $results_date[ $c ]->post_id == $duplicate_of ) {
                        $dt = new DateTime( $results_date[ $c ]->start_date );
                        if ( isset( $booking_settings[ 'booking_enable_date' ] ) && $booking_settings['booking_enable_date'] == 'on' && isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings[ 'booking_enable_multiple_day' ] == '' ) {
                            if ( isset( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
                                $time_start    = explode( ':', $results_date[ 0 ]->from_time );
                                $time_end      = explode( ':', $results_date[ 0 ]->to_time );
                            }

                            $start_timestamp   =   strtotime( $dt->format( 'Y-m-d' ) ) + $time_start[0]*60*60 + $time_start[ 1 ]*60 + ( time() - current_time('timestamp') );
                            $end_timestamp     =   '';
                            if ( isset( $time_end[0] ) && isset( $time_end[1] ) ) {
                                $end_timestamp = strtotime( $dt->format( 'Y-m-d' ) ) + $time_end[0]*60*60 + $time_end[ 1 ]*60 + ( time() - current_time('timestamp') );
                            } else {
                                $end_timestamp = 0;
                            }
                        } else if ( isset( $booking_settings[ 'booking_enable_date' ] ) && $booking_settings[ 'booking_enable_date' ] == 'on' && isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                            $dt_start           = new DateTime( $results_date[ 0 ]->start_date );
                            $dt_end             = new DateTime($results_date[ 0 ]->end_date );
                            $start_timestamp    = strtotime( $dt_start->format( 'Y-m-d' ) );
                            $end_timestamp      = strtotime( $dt_end->format( 'Y-m-d' ) );
                        }
                        break 2;
                    }
                }    
            }
            ?>
            <form method="post" action="<?php echo content_url( "plugins/woocommerce-booking/export-ics.php" );?>" id="export_to_ics">
                <input type="hidden" id="book_date_start" name="book_date_start" value="<?php echo $start_timestamp; ?>" /> 
                <input type="hidden" id="book_date_end" name="book_date_end" value="<?php echo $end_timestamp; ?>" /> 
                <input type="hidden" id="current_time" name="current_time" value="<?php echo current_time('timestamp'); ?>" /> 
                <input type="hidden" id="book_name" name="book_name" value="<?php echo $product_names ?>" />
                <input type="hidden" id="book_name_subject" name="book_name_subject" value="<?php echo "Order Number: " . $order->id; ?>" />
                <input type="submit" id="exp_ics" name="exp_ics" value="<?php _e( 'Add to Calendar', 'woocommerce-booking' ); ?>" /> (<?php echo "Order Number: " . $order->id; ?>)
			</form>
            <?php
            return 'Yes';
        } else {
            return 'No';
        }
    }
    
    function wapbk_ics_file_content( $file, $order ) {
        global $wpdb;
        if( get_option( 'global_show_add_to_calendar_view' ) == 'on' ) {
            if( $order->id != 0 && $order->id != "" ) {
                $order_obj    = new WC_Order( $order->id );
                $order_items  = $order_obj->get_items();
                $random_hash  = md5( date( 'r', time() ) );
                $today_query  = "SELECT * FROM `" . $wpdb->prefix."booking_history` AS a1,`" . $wpdb->prefix . "booking_order_history` AS a2 WHERE a1.id = a2.booking_id AND a2.order_id = %d";
                $results_date = $wpdb->get_results ( $wpdb->prepare( $today_query, $order->id ) );
                $file_path    = WP_CONTENT_DIR .'/uploads/wbkap_tmp';
                $file_name    = get_option( 'book_ics-file-name' );
                $file         = array();
                $c            = 0;
                
                $product_names = "";
                foreach ( $order_items as $item_key => $item_value ) {
                    $product_names .= $item_value[ 'name' ] . ", ";
                }
                $product_names = substr( $product_names, 0, -2 );
                $start_timestamp = $end_timestamp = 0;
                foreach ( $order_items as $item_key => $item_value ) {
                    $duplicate_of = bkap_common::bkap_get_product_id( $item_value[ 'product_id' ] );
                    $booking_settings   = get_post_meta( $duplicate_of, 'woocommerce_booking_settings', true );
                    for ( $c = 0; $c < count( $results_date ); $c++ ) {
                        $dt = new DateTime( $results_date[ $c ]->start_date );
                        if ( $results_date[ $c ]->post_id == $duplicate_of ) {  
                            $time = $time_start = $time_end = 0;
                            if ( ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) && ( isset( $booking_settings['booking_enable_multiple_day' ] ) && $booking_settings['booking_enable_multiple_day' ] == '') ) {
                                if ( isset( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
                                    $time_start = explode( ':', $results_date[ $c ]->from_time );
                                    $time_end   = explode( ':', $results_date[ $c ]->to_time );
                                }
                                $start_timestamp = strtotime( $dt->format( 'Y-m-d' ) ) + $time_start[0]*60*60 + $time_start[1]*60 + ( time() - current_time('timestamp') );	
                                if ( isset( $time_end ) && count( $time_end ) > 0 && $time_end[0] != "" ) {
                                    $end_timestamp = strtotime( $dt->format( 'Y-m-d' ) ) + $time_end[0]*60*60 + $time_end[1]*60 + ( time() - current_time('timestamp') );
                                } else {
                                    $end_timestamp = 0;
                                }
                            } elseif ( ( isset( $booking_settings['booking_enable_date'] ) && $booking_settings['booking_enable_date'] == 'on' ) && ( isset( $booking_settings['booking_enable_multiple_day'] ) && $booking_settings['booking_enable_multiple_day'] == 'on' ) ) {                    	
                                for ( $c = 0; $c < count( $results_date ); $c++ ) {
                                    if ( $results_date[ $c ]->post_id == $duplicate_of ) {
                                        $dt_start        = new DateTime( $results_date[ $c ]->start_date );
                                        $dt_end          = new DateTime( $results_date[$c]->end_date );
                                        $start_timestamp = strtotime( $dt_start->format( 'Y-m-d' ) );
                                        $end_timestamp   = strtotime( $dt_end->format( 'Y-m-d' ) );
                                    }
                                }
                            }
                            break 2;
                        } 
                    } 
                }
                $summary = "Order Number: " . $order->id;
                $icsString = "BEGIN:VCALENDAR
PRODID:-//Events Calendar//iCal4j 1.0//EN
VERSION:2.0
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTART:".date('Ymd\THis\Z',$start_timestamp)."
DTEND:".date('Ymd\THis\Z',$end_timestamp)."
DTSTAMP:".date('Ymd\THis\Z',current_time('timestamp'))."
UID:".(uniqid())."
DESCRIPTION:".$product_names."
SUMMARY:".$summary."
END:VEVENT
END:VCALENDAR";
                if ( !file_exists( $file_path ) ) {
                    mkdir( $file_path, 0777 );
                }
                 
                $file[ 0 ] = $file_path.'/'.$file_name.'_' . $order->id .'.ics';
                // Append a new person to the file
                $current = $icsString;
                
                // Write the contents back to the file
                file_put_contents( $file[ 0 ], $current );
            }
        }
        return $file;
    }
    
    function wapbk_admin_booking_box() {
        if( get_option( 'global_allow_edit_bookings_order_based' ) == 'on' ) {
            add_meta_box( 'woocommerce-booking', __( 'Edit Booking Date/s and/or Time', 'woocommerce-booking' ), array( &$this, 'wapbk_meta_box' ), 'shop_order','normal','core' );
        }
    }
    
    function wapbk_meta_box( $order, $post ) {
        global $wpdb;
        $order_id = $order->ID;
        $order_obj    = new WC_Order( $order->ID );
        $order_items  = $order_obj->get_items();
        
        if( get_option( 'global_allow_edit_bookings_order_based' ) == 'on' ) {
            echo "<input type='hidden' name='global_allow_edit_bookings_order_based' id='global_allow_edit_bookings_order_based' value='on' />";
        } else {
            echo "<input type='hidden' name='global_allow_edit_bookings_order_based' id='global_allow_edit_bookings_order_based' value='' />";
        }
        
        if( count( $order_items ) > 0 ) {
            foreach( $order_items as $order_item_key => $order_item_value ) {
                $product_id = $order_item_value[ 'product_id' ];
                break;
            }

            $prod_id          =   bkap_common::bkap_get_product_id( $product_id );
            $product_settings =   get_post_meta( $prod_id, 'woocommerce_booking_settings', true );
            echo '<table>';
                if ( ( isset( $product_settings[ 'booking_enable_multiple_day' ] ) && $product_settings[ 'booking_enable_multiple_day' ] == 'on' ) && ( isset( $product_settings[ 'booking_fixed_block_enable' ] ) && $product_settings[ 'booking_fixed_block_enable' ] == 'yes' ) )  {
                    $results   =   bkap_block_booking::bkap_get_fixed_blocks( $prod_id );
                    if ( count( $results ) > 0 ) {
                        echo '<tr>
                            <td>
                                <label for="select_period">Select Period</label>
        		            </td>
                            <td>
                                <select name="admin_block_option" id="admin_block_option">';
                                    foreach ( $results as $key => $value ) {
                                        echo '<option id = '.$value->start_day.'&'.$value->number_of_days.'&'.$value->price.' value="'.$value->block_name.'">'.$value->block_name.'</option>';
                                    }
                                echo '</select>
                            </td>
                        </tr>';
                        ?>
                        <script type="text/javascript">	
                        var order_item_id = jQuery("#order_item_ids").val();
    					jQuery( "#admin_block_option" ).change(function() {
    						if ( jQuery( "#admin_block_option" ).val() != "" ) {
    							var passed_id = jQuery(this).children( ":selected" ).attr("id");
    							var exploded_id = passed_id.split('&');
    							jQuery("#admin_block_option_start_day" ).val(exploded_id[0]);
    							jQuery("#admin_block_option_number_of_day" ).val(exploded_id[1]);
    							jQuery("#admin_block_option_price" ).val(exploded_id[2]);
    							jQuery("#wapbk_admin_hidden_date" ).val("");
    							jQuery("#wapbk_admin_hidden_date_checkout" ).val("");
    	
    							jQuery("#admin_booking_calender" ).datepicker("setDate");
    							jQuery("#admin_booking_calender_checkout" ).datepicker("setDate");
    						}
    					});
    					</script>
            			<?php
            			if ( count( $results ) >= 0 ) {
                            $sd = $results[0]->start_day;
                            $nd = $results[0]->number_of_days;
                            $pd = $results[0]->price;
                        }
            			echo '<input type="hidden" id="admin_block_option_enabled" name="admin_block_option_enabled" value="on"/> 
                        <input type="hidden" id="admin_block_option_start_day" name="admin_block_option_start_day" value="' . $sd . '"/> 
                        <input type="hidden" id="admin_block_option_number_of_day" name="admin_block_option_number_of_day" value="' . $nd . '"/>
            			<input type="hidden" id="admin_block_option_price" name="admin_block_option_price" value="' . $pd . '"/>';	
                    } else {
                		$number_of_fixed_price_blocks   =   0;
                		echo ' <input type="hidden" id="admin_block_option_enabled" name="admin_block_option_enabled" value="off"/>
                		<input type="hidden" id="admin_block_option_start_day" name="admin_block_option_start_day" value=""/> 
                		<input type="hidden" id="admin_block_option_number_of_day" name="admin_block_option_number_of_day" value=""/>
                		<input type="hidden" id="admin_block_option_price"  name="admin_block_option_price" value=""/>';
                    }
                }
            
                if ( isset( $product_settings[ 'booking_enable_date' ] ) && $product_settings['booking_enable_date'] == 'on' ) {

                    $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
                	if ( $saved_settings == '' ) {
                        $saved_settings                         = new stdClass();
                		$saved_settings->booking_date_format    = 'd MM, yy';
                		$saved_settings->booking_time_format    = '12';
                		$saved_settings->booking_months         = '1';
                    }
                    
                	echo '<tr>
                        <td>
                            <label for="start_date">'. __( get_option( "book_item-meta-date" ), "woocommerce-booking" ) . '</label>
                        </td>
                        <td>
                            <input type="text" name="start_date" id="admin_booking_calender" value="" />
                        </td>
                    </tr>';
                	
                	if ( isset( $saved_settings->booking_global_holidays ) ) {
                        $book_global_holidays = $saved_settings->booking_global_holidays;
                		$book_global_holidays = substr( $book_global_holidays, 0, strlen( $book_global_holidays ) );
                		$book_global_holidays = '"'.str_replace( ',', '","', $book_global_holidays ).'"';
                    } else {
                        $book_global_holidays = '';
                    }
                	print( '<input type="hidden" name="wapbk_admin_booking_global_holidays" id="wapbk_admin_booking_global_holidays" value=\'' . $book_global_holidays . '\'>' );
                    
                    if ( $product_settings != '' ) {
                        $booking_dates_arr = $product_settings[ 'booking_specific_date' ]; // fetch specific booking dates
                        $booking_dates_str = "";
                		if ( $product_settings[ 'booking_specific_booking' ] == "on" ) {
                            if( !empty( $booking_dates_arr ) ){
                                foreach ( $booking_dates_arr as $k => $v ) {
                				    $booking_dates_str .= '"'.$v.'",';
                                }
                            }
                			$booking_dates_str = substr( $booking_dates_str, 0, strlen( $booking_dates_str )-1 );
                        }

                		print( '<input type="hidden" name="wapbk_admin_booking_dates" id="wapbk_admin_booking_dates" value=\'' . $booking_dates_str . '\'>' );
                        // Global or Product level minimum Multiple day setting
                		$enable_min_multiple_day =   '';
                		$minimum_multiple_day    =   1;
                		if ( isset( $product_settings[ 'enable_minimum_day_booking_multiple' ] ) && $product_settings[ 'enable_minimum_day_booking_multiple' ] == "on" ) {
                            $enable_min_multiple_day    =   $product_settings['enable_minimum_day_booking_multiple'];
                			$minimum_multiple_day       =   $product_settings['booking_minimum_number_days_multiple'];
                        } elseif ( isset( $saved_settings->minimum_day_booking ) && $saved_settings->minimum_day_booking == "on" ) {
                			$enable_min_multiple_day    =   $saved_settings->minimum_day_booking;
                			$minimum_multiple_day       =   $saved_settings->global_booking_minimum_number_days;
                        }
                		print( '<input type="hidden" id="wapbk_enable_min_multiple_day" name="wapbk_enable_min_multiple_day" value="' . $enable_min_multiple_day . '">' );
                		print( '<input type="hidden" id="wapbk_multiple_day_minimum" name="wapbk_multiple_day_minimum" value="' . $minimum_multiple_day . '">' );	
                        $booking_holidays_string = '"' . str_replace( ',', '","', $product_settings[ 'booking_product_holiday' ] ) . '"';
                        print( '<input type="hidden" name="wapbk_admin_booking_holidays" id="wapbk_admin_booking_holidays" value=\'' . $booking_holidays_string . '\'>' );
                        $default = "Y"; //Default settings
                		if ( ( isset( $product_settings[ 'booking_recurring_booking' ] ) && $product_settings[ 'booking_recurring_booking' ] == "on" ) || ( isset( $product_settings[ 'booking_specific_booking' ] ) && $product_settings[ 'booking_specific_booking' ] == "on" ) ) {
                            $default = "N";
                        }
                        foreach ( $product_settings['booking_recurring'] as $wkey => $wval ) {
                            if ( $default == "Y" ) {
                                print( '<input type="hidden" name="wapbk_admin_' . $wkey . '" id="wapbk_admin_' . $wkey . '" value="on">' );
                            } else {
        						if ( $product_settings['booking_recurring_booking'] == "on" ) {
        							print( '<input type="hidden" name="wapbk_admin_' . $wkey . '" id="wapbk_admin_' . $wkey . '" value="' . $wval . '">' );
        						} else {
        							print( '<input type="hidden" name="wapbk_admin_' . $wkey . '" id="wapbk_admin_' . $wkey . '" value="">' );
        						}
                            }
                        }
                        if ( isset( $product_settings[ 'booking_time_settings' ] ) ) {
                            print( '<input type="hidden" name="wapbk_admin_booking_times" id="wapbk_admin_booking_times" value="YES">' );
                        } else {
                            print( '<input type="hidden" name="wapbk_admin_booking_times" id="wapbk_admin_booking_times" value="NO">' );
                        }
            							
        				if ( isset( $product_settings['booking_enable_multiple_day'] ) ) {
        					print( '<input type="hidden" id="wapbk_admin_multiple_day_booking" name="wapbk_admin_multiple_day_booking" value="' . $product_settings[ 'booking_enable_multiple_day' ] . '"/>' );
        				} 
        				else {
        					print( '<input type="hidden" id="wapbk_admin_multiple_day_booking" name="wapbk_admin_multiple_day_booking" value=""/>' );
        				}            								
            			// set mindate and maxdate based on the Bookable time period
            			if ( isset( $product_settings['booking_date_range_type'] ) && $product_settings['booking_date_range_type'] == 'fixed_range' ) {
                            $min_date = $days = '';
                            if ( isset( $product_settings['booking_start_date_range'] ) ) { // check if the range start date is a past dat, if yes then we need to set the mindate to today
        						$current_time   =   current_time( 'timestamp' );
        						$current_date  =   date( "d-m-Y", $current_time );
        						$range_start   =   date( "d-m-Y", strtotime( $product_settings[ 'booking_start_date_range' ] ) );
        						$current_date1 =   new DateTime( $current_date );
        						$range_start1  =   new DateTime( $range_start );
        						$diff_days     =   $current_date1->diff( $range_start1 );
        						// the diff days is always a positive number. However, if the start date is in the future then invert = 0, else = 1
        						if ( $diff_days->invert == 0 ) {
        							$min_date = date( "j-n-Y", strtotime( $product_settings[ 'booking_start_date_range' ] ) );
        						} else {
        							$min_date = date( "j-n-Y", strtotime( $current_date ) );
        						}
        						$date2 = new DateTime( $min_date );
        					}
        					
        					if ( isset( $product_settings[ 'booking_start_date_range' ] ) && isset( $product_settings[ 'booking_end_date_range' ] ) ) {
        					    // calculate the end date of the range based on the number of years it is set to recurr
        					    if ( isset( $product_settings[ 'recurring_booking_range' ] ) && 'on' == $product_settings[ 'recurring_booking_range' ] ) {
        					        if ( isset( $product_settings[ 'booking_range_recurring_years' ] ) && is_numeric( $product_settings[ 'booking_range_recurring_years' ] ) && $product_settings[ 'booking_range_recurring_years' ] > 0 ) {
        					            $end_date = date( 'j-n-Y', strtotime( '+' . $product_settings[ 'booking_range_recurring_years' ] . 'years', strtotime( $product_settings[ 'booking_end_date_range' ] ) ) );
        					        }
        					    }
        					    
        					    // create a hidden field which will contain a list of start and end ranges ( for the years to come).
        					    $fixed_date_range = '"' . $product_settings[ 'booking_start_date_range' ] . '","' . $product_settings[ 'booking_end_date_range' ] . '"';
        					    
        					    if ( isset( $product_settings[ 'booking_range_recurring_years' ] ) && is_numeric( $product_settings[ 'booking_range_recurring_years' ] ) && $product_settings[ 'booking_range_recurring_years' ] > 0 ) {
        					        for ( $i = 1; $i <= $product_settings[ 'booking_range_recurring_years' ]; $i++ ) {
        					            $start_range = date( 'j-n-Y', strtotime( '+' . $i . 'years', strtotime( $product_settings[ 'booking_start_date_range' ] ) ) );
        					            $end_range = date( 'j-n-Y', strtotime( '+' . $i . 'years', strtotime( $product_settings[ 'booking_end_date_range' ] ) ) );
        					            $fixed_date_range .= ',"' . $start_range . '","' . $end_range . '"';
        					        }
        					    }
        					    print( "<input type='hidden' id='wapbk_admin_fixed_range_" . $_POST[ 'order_item_id' ] . "' name='wapbk_admin_fixed_range_" . $_POST[ 'order_item_id' ] . "' value='" . $fixed_date_range . "'>" );
        					    
        					    // set the maxdate to the end date of the range for the last year to be enabled
        					    $days = date ( "j-n-Y", strtotime ( $end_date ) );
        					}
                        } else {
        					$min_date = $days = '';
        					if ( isset( $product_settings['booking_minimum_number_days'] ) ) {
        						$current_time      =   current_time( 'timestamp' ); // Wordpress Time
        						$advance_seconds   =   $product_settings['booking_minimum_number_days'] *60 *60; // Convert the advance period to seconds and add it to the current time
        						$cut_off_timestamp =   $current_time + $advance_seconds;
        						$cut_off_date      =   date("d-m-Y", $cut_off_timestamp);
        						$min_date          =   date("j-n-Y",strtotime($cut_off_date));
        					}
        					if ( isset( $product_settings['booking_maximum_number_days'] ) ) {
        						$days = $product_settings['booking_maximum_number_days'];
        					}
                        }
        				print( '<input type="hidden" name="wapbk_admin_minimumOrderDays" id="wapbk_admin_minimumOrderDays" value="' . $min_date . '">' );
        				print( '<input type="hidden" name="wapbk_admin_number_of_dates" id="wapbk_admin_number_of_dates" value="' . $days . '">' );
        				
        				if ( isset( $product_settings[ 'booking_enable_time' ] ) ) {
        					print( '<input type="hidden" name="wapbk_admin_bookingEnableTime" id="wapbk_admin_bookingEnableTime" value="' . $product_settings[ 'booking_enable_time' ] . '">' );
        				} else {
        					print( '<input type="hidden" name="wapbk_admin_bookingEnableTime" id="wapbk_admin_bookingEnableTime" value="">' );
        				}
        				
        				if ( isset( $product_settings[ 'booking_recurring_booking' ] ) ) {
        					print( '<input type="hidden" name="wapbk_admin_recurringDays" id="wapbk_admin_recurringDays" value="' . $product_settings[ 'booking_recurring_booking' ] . '">' );
        				} else {
        					print('<input type="hidden" name="wapbk_admin_recurringDays" id="wapbk_admin_recurringDays" value="">' );
        				}
        				
        				if ( isset( $product_settings[ 'booking_specific_booking' ] ) ) {
        					print( '<input type="hidden" name="wapbk_admin_specificDates" id="wapbk_admin_specificDates" value="' . $product_settings[ 'booking_specific_booking' ] . '">' );
        				} else {
        					print( '<input type="hidden" name="wapbk_admin_specificDates" id="wapbk_admin_specificDates" value="">' );
        				}
        	
        				$lockout_query   =   "SELECT DISTINCT start_date FROM `" . $wpdb->prefix . "booking_history`
        							         WHERE post_id= %d
        							         AND total_booking > 0
        							         AND available_booking = 0";
        				$results_lockout =   $wpdb->get_results ( $wpdb->prepare( $lockout_query, $prod_id ) );
        			
        				$lockout_query   =   "SELECT DISTINCT start_date FROM `" . $wpdb->prefix . "booking_history`
        									 WHERE post_id= %d
        									 AND available_booking > 0";
        	
        				$results_lock    =   $wpdb->get_results( $wpdb->prepare( $lockout_query, $prod_id ) );
        				$lockout_date    =   '';
                        foreach( $results_lock as $key => $value ) {
                            $start_date     =   $value->start_date;
            				$bookings_done  =   bkap_admin_bookings::bkap_get_date_lockout( $start_date, $prod_id );
            				if( $bookings_done >= $product_settings[ 'booking_date_lockout' ] ) {
            				    $lockout       = explode( "-", $start_date );
            					$lockout_date .= '"' . intval( $lockout[2] ) . "-" . intval( $lockout[1] ) . "-" . $lockout[0] . '",';
                            }
                        }
            				
        				$lockout_str = substr( $lockout_date, 0, strlen( $lockout_date )-1 );
        				foreach ( $results_lockout as $k => $v ) {
        					foreach( $results_lock as $key => $value ) {
        						if ( $v->start_date == $value->start_date ) {
        							$date_lockout         = "SELECT COUNT(start_date) FROM `".$wpdb->prefix."booking_history`
        											        WHERE post_id= %d
        											        AND start_date= %s
        											        AND available_booking = 0";
        							$results_date_lock    = $wpdb->get_results($wpdb->prepare($date_lockout,$prod_id,$v->start_date));
        						
        							if ( $product_settings['booking_date_lockout'] > $results_date_lock[0]->{'COUNT(start_date)'} ) unset( $results_lockout[ $k ] );	
        						} 
        					}
        				}
                        $lockout_dates_str = "";
            
        				foreach ( $results_lockout as $k => $v ) {
        					$lockout_temp       =   $v->start_date;
        					$lockout            =   explode("-",$lockout_temp);
        					$lockout_dates_str .=   '"'.intval($lockout[2])."-".intval($lockout[1])."-".$lockout[0].'",';
        					$lockout_temp       =   "";
        				}
        				
        				$lockout_dates_str      =   substr($lockout_dates_str,0,strlen($lockout_dates_str)-1);
        				$lockout_dates          =   $lockout_dates_str.",".$lockout_str;
                        print( '<input type="hidden" name="wapbk_admin_lockout_days" id="wapbk_admin_lockout_days" value=\'' . $lockout_dates . '\'>' );
        
                        $todays_date     = date( 'Y-m-d' );
                        $query_date      = "SELECT DATE_FORMAT(start_date,'%d-%c-%Y') as start_date,DATE_FORMAT(end_date,'%d-%c-%Y') as end_date FROM ".$wpdb->prefix."booking_history WHERE start_date >='".$todays_date."' AND post_id = '".$prod_id."'";
            			$results_date    = $wpdb->get_results( $query_date );
            						
        				$dates_new       = array();
        				$booked_dates    = array();
            							
        				foreach( $results_date as $k => $v ) {
        					$start_date  = $v->start_date;
        					$end_date    = $v->end_date;
        					$dates       = bkap_common::bkap_get_betweendays( $start_date, $end_date );
        					$dates_new   = array_merge( $dates, $dates_new );
        				}
            			//	Enable the start date for the booking period for checkout
            			foreach ( $results_date as $k => $v ){
        					$start_date     =   $v->start_date;
        					$end_date       =   $v->end_date;
        					$new_start      =   strtotime( "+1 day", strtotime( $start_date ) );
        					$new_start      =   date( "d-m-Y", $new_start );
        					$dates          =   bkap_common::bkap_get_betweendays( $new_start, $end_date );
        					$booked_dates   =   array_merge( $dates,$booked_dates );
        				}
        		
        				$dates_new_arr       =   array_count_values( $dates_new );
        				$booked_dates_arr    =   array_count_values( $booked_dates );
        				$lockout             =   "";
            							
        				if ( isset( $product_settings['booking_date_lockout'] ) )   {
        					$lockout         =   $product_settings['booking_date_lockout'];
        				}
        	             
        				$new_arr_str =   '';
        				
        				foreach( $dates_new_arr as $k => $v ) {
        					if( $v >= $lockout && $lockout != 0 ) {
        						$date_temp     = $k;
        						$date          = explode("-",$date_temp);
        						$new_arr_str  .= '"'.intval($date[0])."-".intval($date[1])."-".$date[2].'",';
        						$date_temp     = "";
        					}
        				}
        				
        				$new_arr_str = substr( $new_arr_str, 0, strlen( $new_arr_str )-1 );
        				print( "<input type='hidden' id='wapbk_admin_hidden_booked_dates' name='wapbk_admin_hidden_booked_dates' value='" . $new_arr_str . "'/>" );
                        
                        //checkout calendar booked dates
        				$blocked_dates       =   array();
        				$booked_dates_str    =   "";
            							
        				foreach ( $booked_dates_arr as $k => $v ) {
        					if( $v >= $lockout && $lockout != 0 ) {
        						$date_temp                     =   $k;
        						$date                          =   explode( "-", $date_temp );
        						$date_without_zero_prefixed    =   intval( $date[0])."-".intval( $date[1] )."-".$date[2];
        						$booked_dates_str             .=   '"'.intval( $date[0] )."-".intval( $date[1] )."-".$date[2].'",';
        						$date_temp                     =   "";
        						$blocked_dates[]               =   $date_without_zero_prefixed;
        					}
        				}
        				
        				if ( isset( $booked_dates_str ) ) {
        					$booked_dates_str   =   substr( $booked_dates_str, 0, strlen( $booked_dates_str )-1 );
        				} else {
        					$booked_dates_str   =   "";
        				}
        				
        				print( "<input type='hidden' id='wapbk_admin_hidden_booked_dates_checkout' name='wapbk_admin_hidden_booked_dates_checkout' value='" . $booked_dates_str . "'/>" );
        				print( '<input type="hidden" id="wapbk_admin_hidden_date" name="wapbk_admin_hidden_date" />' );
        				print( '<input type="hidden" id="wapbk_admin_hidden_date_checkout" name="wapbk_admin_hidden_date_checkout" />' );
        				print( '<input type="hidden" id="wapbk_admin_diff_days" name="wapbk_admin_hidden_diff_days" />' );
                    }
                    
                    $method_to_show    =   'bkap_check_for_order_based_time_slot_admin';
                	$options_checkin   =   $options_checkout = array();
                	$js_code           =   $blocked_dates_hidden_var = '';
                	$block_dates       =   array();
                	$block_dates       =   (array) apply_filters( 'bkap_block_dates', $prod_id , $blocked_dates );
                	if ( isset( $block_dates ) && count( $block_dates ) > 0) {
        				$i        =   1;
        				$bvalue   =   array();
        				$add_day  =   '';
        				$same_day =   '';
                        foreach ( $block_dates as $bkey => $bvalue ) {
                            if ( is_array( $bvalue ) && isset( $bvalue['dates'] ) && count( $bvalue['dates'] ) > 0 ) {
                                $blocked_dates_str  =   '"'.implode('","', $bvalue['dates']).'"';
                            } else {
                                $blocked_dates_str  =   "";
                            }
                			$field_name  =   $i;
                			if ( ( is_array( $bvalue ) && isset( $bvalue['field_name'] ) && $bvalue['field_name'] != '' ) ) {
                                $field_name =   $bvalue['field_name'];
                            }
                			$fld_name        =   'woobkap_' . str_replace( ' ','_', $field_name );
                			$fld_name_admin  =   $fld_name;
                			print( "<input type='hidden' id='" . $fld_name_admin . "' name='" . $fld_name_admin . "' value='" . $blocked_dates_str . "'/>" );
                			$i++;
                							
        					if( is_array( $bvalue ) && isset( $bvalue[ 'add_days_to_charge_booking' ] ) ) {
        						$add_day    =   $bvalue[ 'add_days_to_charge_booking' ];
        					}
        					
        					if( $add_day == '' ) {
        						$add_day    =   0;
        					}
                							
                			print( "<input type='hidden' id='add_days' name='add_days' value='" . $add_day . "'/>" );
                							
        					if( is_array( $bvalue ) && isset( $bvalue['same_day_booking'] ) ) {
        						$same_day   =   $bvalue['same_day_booking'];
        					}
                			print( "<input type='hidden' id='wapbk_admin_same_day' name='wapbk_admin_same_day' value='" . $same_day . "'/>" );
                        }
                							
        				if ( isset( $bvalue[ 'date_label' ] ) && $bvalue[ 'date_label' ] != '' ) {
        					$date_label  =   $bvalue['date_label'];
        				} else {
        					$date_label  =   'Unavailable for Booking';
        				}
                						
                        $js_code = 'var ' . $fld_name_admin . ' = eval( "[" + jQuery( "#' . $fld_name_admin . '" ).val() + "]" );
                        for( i = 0; i < ' . $fld_name_admin . '.length; i++ ) {
                            if( jQuery.inArray( d + "-" + (m+1) + "-" + y,' . $fld_name_admin . ' ) != -1 ) {
                                return [false, "", "' . $date_label . '"];
                            }
                        }';
                		
                		$js_block_date  = 'var ' . $fld_name_admin . ' = eval( "[" + jQuery( "#' . $fld_name_admin . '" ).val() + "]" );
                            var date = new_end = new Date(CheckinDate);
                			var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
                			for( var i = 1; i<= count;i++ ) {
                                if( jQuery.inArray( d + "-" + (m+1) + "-" + y,' . $fld_name_admin . ' ) != -1 ) {
                                    jQuery( "#wapbk_hidden_date_checkout" ).val( "" );
                					jQuery( "#booking_calender_checkout" ).val( "" );
                					CalculatePrice = "N";
                                    alert("Some of the dates in the selected range are on rent. Please try another date range.");
                					break;
                                }
                				new_end = new Date( ad( new_end, 1 ) );
                				var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();
                            }';
                    }
                	
                    if ( isset( $product_settings[ 'booking_enable_multiple_day' ] ) && $product_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                        echo '<tr>
                            <td>
                                <label for="end_date">'. __( get_option( "checkout_item-meta-date" ), "woocommerce-booking" ) . '</label>
                            </td>
                            <td>
                                <input type="text" name="end_date" id="admin_booking_calender_checkout" value="" />
                            </td>
                        </tr>';
                        $options_checkout[] =   "minDate: 1";
                        $options_checkin[]  =   'onClose: function( selectedDate, inst ) {
        				    var monthValue = inst.selectedMonth+1;
                            var dayValue = inst.selectedDay;
                            var yearValue = inst.selectedYear;
                            var current_sel_dt = dayValue + "-" + monthValue + "-" + yearValue;
                            jQuery( "#wapbk_admin_hidden_date" ).val( current_sel_dt );
                            if( jQuery( "#admin_block_option_enabled" ).val() == "on" ) {
                                var nod= parseInt( jQuery( "#admin_block_option_number_of_day" ).val(), 10 );
                                if( jQuery( "#wapbk_admin_hidden_date" ).val() != "" ) {
                                    var num_of_day = jQuery( "#admin_block_option_number_of_day" ).val();
                                    var split = jQuery( "#wapbk_admin_hidden_date").val().split("-");
                                    split[1] = split[1] - 1;
                                    var minDate = new Date(split[2],split[1],split[0]);
                                    minDate.setDate( minDate.getDate() + nod );
                                    jQuery( "input[name=\"end_date\"]" ).datepicker( "setDate", minDate );
                                }
                            } else {
                                if( jQuery( "#wapbk_admin_hidden_date" ).val() != "" ) {
                                    if( jQuery( "#wapbk_admin_same_day" ).val() == "on" ) {
        								if ( jQuery( "#wapbk_admin_hidden_date" ).val() != "" ) {
        									var split = jQuery("#wapbk_admin_hidden_date" ).val().split("-");
        									split[1] = split[1] - 1;
        									var minDate = new Date(split[2],split[1],split[0]);
        									minDate.setDate(minDate.getDate());
        									jQuery( "input[name=\"end_date\"]" ).datepicker( "option", "minDate", minDate);
        								}
                                    } else {
                                        var split = jQuery( "#wapbk_admin_hidden_date" ).val().split("-");
                                        split[1] = split[1] - 1;
                                        var minDate = new Date(split[2],split[1],split[0]);
                                        if( jQuery( "#wapbk_enable_min_multiple_day" ).val() == "on" ) {
                                            var minimum_multiple_day = jQuery( "#wapbk_multiple_day_minimum" ).val();
                                            if( minimum_multiple_day == 0 || !minimum_multiple_day ) {
                                            	minimum_multiple_day = 1;
                                            }
                                        	minDate.setDate( minDate.getDate() + parseInt( minimum_multiple_day ) );
                                        } else {
                                            minDate.setDate( minDate.getDate() + 1 );
                                        }
                                        jQuery( "input[name=\"end_date\"]" ).datepicker( "option", "minDate", minDate );
                                    }
                                }
                            }
                        }';
                        $options_checkout[] = "onSelect: bkap_get_per_night_price";
                        $options_checkin[]  = "onSelect: bkap_set_checkin_date";
                        $options_checkout[] = "beforeShowDay: bkap_check_booked_dates";
                        $options_checkin[]  = "beforeShowDay: bkap_check_booked_dates";
                    } else if( isset( $product_settings[ 'booking_enable_time' ] ) && $product_settings[ 'booking_enable_time' ] == 'on' ) {
                        echo '<tr>
                            <td>
                                <label for="time_slot">' . get_option( 'book_time-label' ) . '<label/>
                            </td>
                			<td>
                                <select name="time_slot" id="time_slot" style="width:100%;"></select>
                            </td>
                        </tr>';
                        $options_checkin[]    =   "beforeShowDay: bkap_show_book";
                        $options_checkin[]    =   "onSelect: bkap_show_times";
                        $options_checkin[]    =   'onClose: function( selectedDate, inst ) {
                            var monthValue = inst.selectedMonth+1;
                            var dayValue = inst.selectedDay;
                            var yearValue = inst.selectedYear;
                            var current_sel_dt = dayValue + "-" + monthValue + "-" + yearValue;
                            jQuery( "#wapbk_admin_hidden_date" ).val( current_sel_dt );
                        }';
                    } else {
                        $options_checkin[]    =   'onClose: function( selectedDate, inst ) {
                            var monthValue = inst.selectedMonth+1;
                            var dayValue = inst.selectedDay;
                            var yearValue = inst.selectedYear;
                            var current_sel_dt = dayValue + "-" + monthValue + "-" + yearValue;
                            jQuery( "#wapbk_admin_hidden_date" ).val(current_sel_dt);
                        }';
                        $options_checkin[]    =  "beforeShowDay: bkap_show_book";
                        $options_checkin[]    =   "onSelect: bkap_show_times";
                    }	
                	
                    $options_checkin_str   =   '';
                	if ( count( $options_checkin ) > 0 ) {
                        $options_checkin_str  =   implode( ',', $options_checkin );
                    }
                					
        			$options_checkout_str  =   '';
        			
        			if ( count( $options_checkout ) > 0 ) {
        				$options_checkout_str = implode( ',', $options_checkout );
        			}

                    print( '<script type="text/javascript">
                        jQuery(document).ready(function() {	
                            jQuery.extend(jQuery.datepicker, { afterShow: function(event) {
                                jQuery.datepicker._getInst(event.target).dpDiv.css("z-index", 9999);
                            }});
                        	var today = new Date();
                        	jQuery(function() {
                                jQuery( "input[name=\"start_date\"]" ).datepicker({
                                    beforeShow: avd,
                        			dateFormat: "' . $saved_settings->booking_date_format . '",						
                        			numberOfMonths: parseInt( ' . $saved_settings->booking_months . '),
                        			' . $options_checkin_str . ' ,
                                }).focus(function (event) {
                                    jQuery.datepicker.afterShow(event);
                                });
                            });
        					jQuery( "input[name=\"start_date\"]" ).wrap("<div class=\"hasDatepicker\"></div>");
        				});' );
                        if ( isset( $product_settings[ 'booking_enable_multiple_day' ] ) && $product_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                            print ( 'jQuery( "input[name=\"end_date\"]" ).datepicker({
                                dateFormat: "' . $saved_settings->booking_date_format . '",
                				numberOfMonths: parseInt(' . $saved_settings->booking_months . '),
                				' . $options_checkout_str . ' ,
                				onClose: function( selectedDate, inst ) {
                                    jQuery( "input[name=\"end_date\"]" ).datepicker( "option", "maxDate", selectedDate );
                                }
                            }).focus(function (event) {
                                jQuery.datepicker.afterShow(event);
                            });
                            jQuery( "input[name=\"end_date\"]" ).wrap("<div class=\"hasDatepicker\"></div>");' );
                        }
                		print( '//********************************************************************************
                        // It will used to show the booked dates in the calendar for multiple day booking.
                        //********************************************************************************
            			function bkap_check_booked_dates(date) {
    						var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
    						var holidayDates = eval( "[" + jQuery( "#wapbk_admin_booking_holidays" ).val()+"]");
    						var globalHolidays = eval( "[" + jQuery("#wapbk_admin_booking_global_holidays" ).val()+"]");
    						var bookedDates=eval("[" + jQuery("#wapbk_admin_hidden_booked_dates").val() + "]");
    						var bookedDatesCheckout = eval("["+jQuery("#wapbk_admin_hidden_booked_dates_checkout").val()+"]");
    
    						var block_option_start_day= jQuery("#admin_block_option_start_day").val();
    					 	var block_option_price= jQuery("#admin_block_option_price").val();
    					
    						for (iii = 0; iii < globalHolidays.length; iii++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
    								return [false, "", "Holiday"];
    							}
    						}
    						for (ii = 0; ii < holidayDates.length; ii++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
    								return [false, "", "Holiday"];
    							}
    						}
    						var id_booking = jQuery(this).attr("id");
    						if (id_booking == "admin_booking_calender" || id_booking == "inline_calendar") {
    							for (iii = 0; iii < bookedDates.length; iii++) {
    								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
    									return [false, "", "Unavailable for Booking"];
    								}
    							}
    						}	
    						if (id_booking == "admin_booking_calender_checkout" || id_booking == "inline_calendar_checkout")  {
    							for (iii = 0; iii < bookedDatesCheckout.length; iii++) {
    								if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDatesCheckout) != -1 ) {
    									return [false, "", "Unavailable for Booking"];
    								}
    							}
    						}
    						var block_option_enabled= jQuery("#admin_block_option_enabled").val();
    	
    						if (block_option_enabled =="on") {
    							if ( id_booking == "admin_booking_calender" || id_booking == "inline_calendar" ) {
    						   		if (block_option_start_day == date.getDay() || block_option_start_day == "any_days") {
    					              return [true];
    					            } else {
    									return [false];
    					            }
    				       		}
    				       		var bcc_date=jQuery( "input[name=\"end_date\"]" ).datepicker("getDate");
    							
    							if(bcc_date != null) {
    								var dd = bcc_date.getDate();
    								var mm = bcc_date.getMonth()+1; //January is 0!
    								var yyyy = bcc_date.getFullYear();
    								var checkout = dd + "-" + mm + "-"+ yyyy;
    								jQuery("#wapbk_admin_hidden_date_checkout").val(checkout);
    
    						   		if (id_booking == "admin_booking_calender_checkout" || id_booking == "inline_calendar_checkout"){
    
    				       			if (Date.parse(bcc_date) === Date.parse(date)){
    				       					return [true];
    				       			}else {
    				       					return [false];
    				       			}
    							}
    				       		}
    				       	}
    						'.$js_code.'
    					    // if a fixed date range is enabled, then check if the date lies in the range and enable/disable accordingly
    					    if ( jQuery( "#wapbk_admin_fixed_range" ).length > 0 ) {
    					       var in_range = fixed_range( date );
    					       return in_range;
    					    }
    						return [true];
                        }
            					
    					//************************************************************************************************************
                        //This function disables the dates in the calendar for holidays, global holidays set and for which lockout is reached for Single day booking feature.
                        //************************************************************************************************************
            					
    					function bkap_show_book(date) {
    						var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
    						var deliveryDates = eval("["+jQuery("#wapbk_admin_booking_dates").val()+"]");
    						
    						var holidayDates = eval("["+jQuery("#wapbk_admin_booking_holidays").val()+"]");
    							
    						var globalHolidays = eval("["+jQuery("#wapbk_admin_booking_global_holidays").val()+"]");
    						
    						//Lockout Dates
    						var lockoutdates = eval("["+jQuery("#wapbk_admin_lockout_days").val()+"]");
    						
    						var bookedDates = eval("["+jQuery("#wapbk_admin_hidden_booked_dates").val()+"]");
    						var dt = new Date();
    						var today = dt.getMonth() + "-" + dt.getDate() + "-" + dt.getFullYear();
    						for (iii = 0; iii < lockoutdates.length; iii++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,lockoutdates) != -1 ) {
    								return [false, "", "Booked"];
    	
    							}
    						}	
    						
    						for (iii = 0; iii < globalHolidays.length; iii++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
    								return [false, "", "Holiday"];
    							}
    						}
    						
    						for (ii = 0; ii < holidayDates.length; ii++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
    								return [false, "", "Holiday"];
    							}
    						}
    					
    						for (i = 0; i < bookedDates.length; i++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
    								return [false, "", "Unavailable for Booking"];
    							}
    						}
    						 	
    						for (i = 0; i < deliveryDates.length; i++) {
    							if( jQuery.inArray(d + "-" + (m+1) + "-" + y,deliveryDates) != -1 ) {
    								return [true];
    							}
    						}
                            // if a fixed date range is enabled, then check if the date lies in the range and enable/disable accordingly
    					    if ( jQuery( "#wapbk_admin_fixed_range" ).length > 0 ) {
    					       var in_range = fixed_range( date );
    					       return in_range;
    					    }
    						var day = "booking_weekday_" + date.getDay();
    						var name = day;
    						if (jQuery("#wapbk_admin_"+name).val() == "on") {
    							return [true];
    						}
    						return [false];
    					}
    					    
    					//***********************************************************************************************************
                        //This function calls an ajax when a date is selected which displays the time slots on frontend product page.
                        //***********************************************************************************************************
    					    
    					function bkap_show_times(date,inst) {
    						var monthValue = inst.selectedMonth+1;
    						var dayValue = inst.selectedDay;
    						var yearValue = inst.selectedYear;
    
    						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
    						var sold_individually = jQuery("#wapbk_sold_individually").val();
    						jQuery("#wapbk_admin_hidden_date").val(current_dt);
    						if (jQuery("#wapbk_admin_bookingEnableTime").val() == "on" && jQuery("#wapbk_admin_booking_times").val() != "") {
    							var time_slots_arr = jQuery("#wapbk_admin_booking_times").val();
    							var data = {
    								current_date: current_dt,
                		            order_id: "' . $order-> ID . '",
    								post_id: "'.$prod_id.'",
    								action: "'.$method_to_show.'"
                                };
    										
    							jQuery.post("'.get_admin_url().'admin-ajax.php", data, function(response) {
    								jQuery( "#time_slot" ).show();
    						
    								/*var select = jQuery("<select style=\"width:100%;\">");*/
    								jQuery( "#time_slot" ).append(jQuery("<option>").val("Choose a Time").html("'.get_option('book_time-select-option').'"));
    							
    								var time_slots = response.split("|");
    						
    								for (var i = 0; i <= time_slots.length; ++i)  {
    									if(time_slots[i] != "" && time_slots[i] != null)
    										jQuery( "#time_slot" ).append(jQuery("<option>").val(time_slots[i]).html(time_slots[i]));
    								}
    								 
    								//select.val(1).attr({name: "time_slot"}).change(function(){
    								    
    								//});
    								//jQuery("#time_slot").replaceWith(select);
    								jQuery( "#ajax_img" ).hide();
    								jQuery("#show_time_slot").html(response);
    								jQuery("#time_slot").change(function()
    								{
    									if ( jQuery("#time_slot").val() != "" ) {
    										jQuery( ".single_add_to_cart_button" ).show();
    										if(sold_individually == "yes") {
    											jQuery( ".quantity" ).hide();
    										} else {
    											jQuery( ".quantity" ).show();
    										}
    							
    									} else if ( jQuery("#time_slot").val() == "" ) {
    										jQuery( ".single_add_to_cart_button" ).hide();
    										jQuery( ".quantity" ).hide();
    									}
    								})
    								
    							});
    						} else {
    							if ( jQuery("#wapbk_admin_hidden_date").val() != "" ) {
    								var data = {
    								current_date: current_dt,
                		            order_id: "' . $order->ID . '",
    								post_id: "'.$prod_id.'", 
    								action: "bkap_insert_admin_date_order_based"
    								};
    								jQuery.post("'.get_admin_url().'/admin-ajax.php", data, function(response)
    								{
    									jQuery( ".single_add_to_cart_button" ).show();
    									if(sold_individually == "yes") {
    										jQuery( ".quantity" ).hide();
    									} else {
    										jQuery( ".quantity" ).show();
    									}
    							
    								});
    							} else if ( jQuery("#wapbk_admin_hidden_date").val() == "" ) {
    								jQuery( ".single_add_to_cart_button" ).hide();
    								jQuery( ".quantity" ).hide();
    							}
    						}
    					}
    					//**********************************************************************************************************************************
                        //This functions checks if the selected date range does not have product holidays or global holidays and sets the hidden date field.
                        //**********************************************************************************************************************************
    					    
    					function bkap_set_checkin_date(date,inst) {
    						var monthValue = inst.selectedMonth+1;
    						var dayValue = inst.selectedDay;
    						var yearValue = inst.selectedYear;
    
    						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
    						jQuery("#wapbk_admin_hidden_date").val(current_dt);
    						// Check if any date in the selected date range is unavailable
    						if (jQuery("#wapbk_admin_hidden_date").val() != "" ) {
    							var CalculatePrice = "Y";
    							var split = jQuery("#wapbk_admin_hidden_date").val().split("-");
    							split[1] = split[1] - 1;		
    							var CheckinDate = new Date(split[2],split[1],split[0]);
    								
    							var split = jQuery("#wapbk_admin_hidden_date_checkout").val().split("-");
    							split[1] = split[1] - 1;
    							var CheckoutDate = new Date(split[2],split[1],split[0]);
    								
    							var date = new_end = new Date(CheckinDate);
    							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
    								
    							var bookedDates = eval("["+jQuery("#wapbk_admin_hidden_booked_dates").val()+"]");
    							var holidayDates = eval("["+jQuery("#wapbk_admin_booking_holidays").val()+"]");
    							var globalHolidays = eval("["+jQuery("#wapbk_admin_booking_global_holidays").val()+"]");
    						
    							var count = gd(CheckinDate, CheckoutDate, "days");
    							//Locked Dates
    							for (var i = 1; i<= count;i++) {
    									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,bookedDates) != -1 ) {
    										jQuery( "#wapbk_admin_hidden_date" ).val("");
    										jQuery( "input[name=\"start_date\"]" ).val("");
    										jQuery( ".single_add_to_cart_button" ).hide();
    										jQuery( ".quantity" ).hide();
    										CalculatePrice = "N";
    										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
    										break;
    									}
    								new_end = new Date(ad(new_end,1));
    								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
    								}
    							//Global Holidays
    							var date = new_end = new Date(CheckinDate);
    							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
    						
    							for (var i = 1; i<= count;i++) {
    									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,globalHolidays) != -1 ) {
    										jQuery("#wapbk_admin_hidden_date").val("");
    										jQuery( "input[name=\"start_date\"]" ).val("");
    										CalculatePrice = "N";
    										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
    										break;
    									}
    								new_end = new Date(ad(new_end,1));
    								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
    								}
    							//Product Holidays
    							var date = new_end = new Date(CheckinDate);
    							var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
    						
    							for (var i = 1; i<= count;i++) {
    									if( jQuery.inArray(d + "-" + (m+1) + "-" + y,holidayDates) != -1 ) {
    										jQuery("#wapbk_admin_hidden_date").val("");
    										jQuery( "input[name=\"start_date\"]" ).val("");
    										CalculatePrice = "N";
    										alert("Some of the dates in the selected range are unavailable. Please try another date range.");
    										break;
    									}
    								new_end = new Date(ad(new_end,1));
    								var m = new_end.getMonth(), d = new_end.getDate(), y = new_end.getFullYear();													
    								}
    							'.$js_block_date.'
    						//	if (CalculatePrice == "Y") calculate_price();
    						}
    					}
    					    
                        //**************************************************
                        // This function sets the hidden checkout date for Multiple day booking feature.
                        //***************************************************
    					    
    					function bkap_get_per_night_price( date, inst ) {
    						var monthValue = inst.selectedMonth+1;
    						var dayValue = inst.selectedDay;
    						var yearValue = inst.selectedYear;
    
    						var current_dt = dayValue + "-" + monthValue + "-" + yearValue;
    						jQuery( "#wapbk_admin_hidden_date_checkout" ).val( current_dt );
    					}
                		jQuery(document).ready(function() {
                            jQuery( "#save_booking_dates" ).on( "click", function() {
                    		    var data = {
                                    order_id: "' . $order->ID . '",
                                    start_date: jQuery( "#admin_booking_calender" ).val(),
                                    hidden_date: jQuery( "#wapbk_admin_hidden_date" ).val(),
                    		        end_date: jQuery( "#admin_booking_calender_checkout" ).val(),
                                    hidden_date_checkout: jQuery( "#wapbk_admin_hidden_date_checkout" ).val(),
                                    time_slot: jQuery( "select[name=\"time_slot\"] option:selected" ).text(),
                                    action: "save_booking_dates_call"
                                };
                                jQuery.post( "' . get_admin_url() . 'admin-ajax.php", data, function( response ) {
                		            location.reload();
                    		    });
                    		});
                        });
                    </script> 
                    <tr id="save_booking_date_button">
                        <td><input type="button" value="Update" id="save_booking_dates" class="save_button"></td>
                    </tr>' );
                
            }   
            print( '</table>' );             
        } else {
            echo "Please add products to the order and save the order to update the booking details for the products.";
        }
    }
    
    function bkap_insert_admin_date_order_based() {
        global $wpdb;
        if( isset( $_POST[ 'order_id' ] ) ) {
            $current_date     =   $_POST[ 'current_date' ];
            $date_to_check    =   date( 'Y-m-d', strtotime( $current_date ) );
            $day_check        =   "booking_weekday_" . date( 'w', strtotime( $current_date ) );
            
            $order_obj    = new WC_Order( $_POST[ 'order_id' ] );
            $order_items  = $order_obj->get_items();
            $i = 0 ;
            foreach( $order_items as $order_item_key  => $order_item_value ) {
                $post_id          =   $order_item_value[ 'product_id' ];
                $product          =   get_product( $post_id );
                $product_type     =   $product->product_type;
                
                // Grouped products compatibility
                if ( $product->has_child() ) {
                    $has_children = "yes";
                    $child_ids = $product->get_children();
                }
                
                $check_query  =   "SELECT * FROM `".$wpdb->prefix."booking_history`
                    WHERE start_date= %s
                    AND post_id= %d
                    AND status = ''
                    AND available_booking >= 0";
                $results_check    =   $wpdb->get_results ( $wpdb->prepare( $check_query, $date_to_check, $post_id ) );
                
                if ( !$results_check ) {
                    $check_day_query =  "SELECT * FROM `".$wpdb->prefix."booking_history`
                        WHERE weekday= %s
                        AND post_id= %d
                        AND start_date='0000-00-00'
                        AND status = ''
                        AND available_booking > 0";
                    $results_day_check   =   $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check, $post_id ) );
                
                    if ( !$results_day_check ) {
                        $check_day_query    =  "SELECT * FROM `" . $wpdb->prefix . "booking_history`
    									WHERE weekday= %s
    									AND post_id= %d
    									AND start_date='0000-00-00'
    									AND status = ''
    									AND total_booking = 0
    									AND available_booking = 0";
                        $results_day_check  =   $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check,$post_id ) );
                    }
                    
                    foreach ( $results_day_check as $key => $value ) {
                        $insert_date        =   "INSERT INTO `".$wpdb->prefix."booking_history`
										(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
										VALUES (
										'".$post_id."',
										'".$day_check."',
										'".$date_to_check."',
										'0000-00-00',
										'',
										'',
										'".$value->total_booking."',
										'".$value->available_booking."' )";
                        $wpdb->query( $insert_date );
                
                        // Grouped products compatibility
                        if ( $product_type == "grouped" ) {
                            if ( $has_children == "yes" ) {
                                foreach ( $child_ids as $k => $v ) {
                                    $check_day_query     =  "SELECT * FROM `".$wpdb->prefix."booking_history`
    												WHERE weekday= %s
    												AND post_id= %d
    												AND start_date='0000-00-00'
    												AND status = ''
    												AND available_booking > 0";
                                    $results_day_check   =  $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check, $v ) );
                
                                    if ( !$results_day_check ) {
                                        $check_day_query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    													WHERE weekday= %s
    													AND post_id= %d
    													AND start_date='0000-00-00'
    													AND status = ''
    													AND total_booking = 0
    													AND available_booking = 0";
                                        $results_day_check  =   $wpdb->get_results ( $wpdb->prepare( $check_day_query, $day_check, $v ) );
                                    }
                
                                    $insert_date     =  "INSERT INTO `".$wpdb->prefix."booking_history`
    											(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    											VALUES (
    											'".$v."',
    											'".$day_check."',
    											'".$date_to_check."',
    											'0000-00-00',
    											'',
    											'',
    											'".$results_day_check[0]->total_booking."',
    											'".$results_day_check[0]->available_booking."' )";
                                    $wpdb->query( $insert_date );
                                }
                            }
                        }
                    }
                }
            }
        }
        die();
    }
    /************************************************************************************
     * This function displays the timeslots for the selected date on the admin order page.
     ************************************************************************************/
    	
    function bkap_check_for_order_based_time_slot_admin() {
        if( isset( $_POST['checkin_date'] ) ) {
            $checkin_date =   $_POST[ 'checkin_date' ];
        } else {
            $checkin_date =   '';
        }
        
        $current_date   =   $_POST[ 'current_date' ];
        $post_id        =   $_POST[ 'post_id' ];
        $drop_down      =   "";
        if( isset( $_POST[ 'order_id' ] ) ) {
            $order_obj    = new WC_Order( $_POST[ 'order_id' ] );
            $order_items  = $order_obj->get_items();
            $i = 0 ;
            foreach( $order_items as $order_item_key  => $order_item_value ) {
                if( $i == 0 ) {
                    $drop_down  = bkap_booking_process::get_time_slot($current_date,$post_id,$checkin_date);
                } else {
                    $this->bkap_insert_records_for_products( $current_date, $order_item_value[ 'product_id' ] );
                }
                $i++;
            }
        } 
        
        echo $drop_down;
        die();
    }
    
    public static function bkap_insert_records_for_products( $current_date, $post_id ) {
        global $wpdb;
        
        $saved_settings   =   json_decode( get_option( 'woocommerce_booking_global_settings' ) );
        $booking_settings =   get_post_meta( $post_id, 'woocommerce_booking_settings', true );
  
        $time_format_db_value = 'G:i';
        
        $current_time         =   current_time( 'timestamp' );
        $today                =   date( "Y-m-d G:i", $current_time );
        $date1                =   new DateTime( $today );
    
        $date_to_check        =   date( 'Y-m-d', strtotime( $current_date ) );
        $day_check            =   "booking_weekday_".date( 'w', strtotime( $current_date ) );
        $from_time_db_value   =   '';
    
        $product              =   get_product( $post_id );
        $product_type         =   $product->product_type;
    
        // Grouped products compatibility
        if ( $product->has_child() ) {
            $has_children    =   "yes";
            $child_ids       =   $product->get_children();
        }
     
        // check if there's a record available for the given date and time with availability > 0
        $check_query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
            				WHERE start_date='".$date_to_check."'
            				AND post_id = '".$post_id."'
            				AND status = ''
            				AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')
        ";
        $results_check  =   $wpdb->get_results ( $check_query );
        
        if ( count( $results_check ) > 0 ) {
            $specific = "N";
            foreach ( $results_check as $key => $value ) {
                if ( $value->weekday == "" ) {
                    $specific = "Y";
                }
            }
            if ( $specific == "N" ) {
                foreach ( $results_check as $key => $value ) {                   	
                    // get all the records using the base record to ensure we include any time slots that might hv been added after the original date record was created
                    // This can happen only for recurring weekdays
                    $check_day_query     =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    									 WHERE weekday= '".$day_check."'
    									 AND post_id= '".$post_id."'
    									 AND start_date='0000-00-00'
    									 AND status = ''
    									 AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
                    $results_day_check   =   $wpdb->get_results ( $check_day_query );                	
                    //remove duplicate time slots that have available booking set to 0
                    foreach ( $results_day_check as $k => $v ) {
                        $from_time_qry = date( $time_format_db_value, strtotime( $v->from_time ) );
    
                        if ( $v->to_time != '' ) {
                            $to_time_qry = date( $time_format_db_value, strtotime( $v->to_time ) );
                        } else {
                            $to_time_qry = "";
                        }
    
                        $time_check_query   =   "SELECT * FROM `".$wpdb->prefix."booking_history`
        									WHERE start_date= '".$date_to_check."'
        									AND post_id= '".$post_id."'
        									AND from_time= '".$from_time_qry."'
        									AND to_time= '".$to_time_qry."'
        									AND status = '' ORDER BY STR_TO_DATE(from_time,'%H:%i')";
                        $results_time_check =   $wpdb->get_results( $time_check_query );
    
                        if ( count( $results_time_check ) > 0 ) {
                            unset( $results_day_check[ $k ] );
                        }
                    }
                	
                    //remove duplicate time slots that have available booking > 0
                    foreach ( $results_day_check as $k => $v ) {
                        foreach ( $results_check as $key => $value ) {
                            if ( $v->from_time != '' && $v->to_time != '' ) {
                                $from_time_chk = date( $time_format_db_value, strtotime( $v->from_time ) );
                                if ( $value->from_time == $from_time_chk ) {
                                    if ( $v->to_time != '' ){
                                        $to_time_chk = date( $time_format_db_value, strtotime( $v->to_time ) );
                                    }
    
                                    if ( $value->to_time == $to_time_chk ){
                                        unset( $results_day_check[ $k ] );
                                    }
    
                                }
                            } else {
                                if( $v->from_time == $value->from_time ) {
                                    if ( $v->to_time == $value->to_time ) {
                                        unset( $results_day_check[ $k ] );
                                    }
                                }
                            }
                        }
                    }
                	
                    foreach ( $results_day_check as $key => $value ) {
                        if ( $value->from_time != '' ) {
                            $from_time_db_value    =   date( $time_format_db_value, strtotime( $value->from_time ) );
                        }
    
                            
                        if ( $value->to_time!= '' ) {
                            $to_time_db_value  =   date( $time_format_db_value, strtotime( $value->to_time ) );
                        } else {
                            $to_time_db_value = '';
                        }
    
                        $insert_date    =   "INSERT INTO `".$wpdb->prefix."booking_history`
									(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
									VALUES (
									'".$post_id."',
									'".$day_check."',
									'".$date_to_check."',
									'0000-00-00',
									'".$from_time_db_value."',
									'".$to_time_db_value."',
									'".$value->total_booking."',
									'".$value->available_booking."' )";
                        $wpdb->query( $insert_date );
                    	
                        // Grouped products compatibility
                        if ( $product_type == "grouped" ) {
    
                            if ( $has_children == "yes" ) {
                            	
                                foreach ( $child_ids as $k => $v ) {
                                    $check_day_query_child      =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    															WHERE weekday= '".$day_check."'
    															AND post_id= '".$v."'
    															AND start_date='0000-00-00'
    															AND status = ''
    															AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
                                    $results_day_check_child    =   $wpdb->get_results ($check_day_query_child);
                                	
                                    $insert_date                =   "INSERT INTO `".$wpdb->prefix."booking_history`
                												(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                												VALUES (
                												'".$v."',
                												'".$day_check."',
                												'".$date_to_check."',
                												'0000-00-00',
                												'".$from_time_db_value."',
                												'".$to_time_db_value."',
                												'".$results_day_check_child[0]->total_booking."',
                												'".$results_day_check_child[0]->available_booking."' )";
                                    $wpdb->query( $insert_date );
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $check_day_query     =   "SELECT * FROM `".$wpdb->prefix."booking_history`
								 WHERE weekday= '".$day_check."'
								 AND post_id= '".$post_id."'
								 AND start_date='0000-00-00'
								 AND status = ''
								 AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
            $results_day_check   =   $wpdb->get_results ( $check_day_query );
        	
            // No base record for availability > 0
            if ( !$results_day_check ) {
                // check if there's a record for the date where unlimited bookings are allowed i.e. total and available = 0
                $check_query    =   "SELECT * FROM `".$wpdb->prefix."booking_history`
								WHERE start_date= '".$date_to_check."'
								AND post_id= '".$post_id."'
								AND total_booking = 0
								AND available_booking = 0
								AND status = '' ORDER BY STR_TO_DATE(from_time,'%H:%i')
								";
            	
                $results_check  =   $wpdb->get_results( $check_query );

                // if record found, then create the dropdown
                if ( isset( $results_check ) && count( $results_check ) > 0 ) {
                    foreach ( $results_check as $key => $value ) {
                        if ( $value->from_time != '' ) {
                            $from_time_db_value  =   date( $time_format_db_value, strtotime( $value->from_time ) );
                        } else {
                            $from_time_db_value = "";
                        }
                        $to_time_db_value   =   date( $time_format_db_value, strtotime( $value->to_time ) );
                    }
                } else {
                    // else check if there's a base record with unlimited bookings i.e. total and available = 0
                    $check_day_query       =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    										WHERE weekday= '".$day_check."'
    										AND post_id= '".$post_id."'
    										AND start_date='0000-00-00'
    										AND status = ''
    										AND total_booking = 0
    										AND available_booking = 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
                    $results_day_check     =   $wpdb->get_results ($check_day_query);
                }
            }
        	
            if ( $results_day_check ) {

                foreach ( $results_day_check as $key => $value ) {
                    if ( $value->from_time != '' ) {
                        $from_time_db_value   =   date( $time_format_db_value, strtotime( $value->from_time ) );
                    } else {
                        $from_time_db_value = "";
                    }
                	
                    $to_time_db_value =   date( $time_format_db_value, strtotime( $value->to_time ) );

                   
                    $insert_date   =   "INSERT INTO `".$wpdb->prefix."booking_history`
    									(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
    									VALUES (
    									'".$post_id."',
    									'".$day_check."',
    									'".$date_to_check."',
    									'0000-00-00',
    									'".$from_time_db_value."',
    									'".$to_time_db_value."',
    									'".$value->total_booking."',
    									'".$value->available_booking."' )";
                    $wpdb->query( $insert_date );
                
                    // Grouped products compatibility
                    if ( $product_type == "grouped" ) {
                        if ( $has_children == "yes" ) {
                            foreach ( $child_ids as $k => $v ) {
                                $check_day_query_child      =   "SELECT * FROM `".$wpdb->prefix."booking_history`
    															WHERE weekday= '".$day_check."'
    															AND post_id= '".$v."'
    															AND start_date='0000-00-00'
    															AND status = ''
    															AND available_booking > 0 ORDER BY STR_TO_DATE(from_time,'%H:%i')";
                                $results_day_check_child    =   $wpdb->get_results ($check_day_query_child);
                                $insert_date                =   "INSERT INTO `".$wpdb->prefix."booking_history`
                												(post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
                												VALUES (
                												'".$v."',
                												'".$day_check."',
                												'".$date_to_check."',
                												'0000-00-00',
                												'".$from_time_db_value."',
                												'".$to_time_db_value."',
                												'".$results_day_check_child[0]->total_booking."',
                												'".$results_day_check_child[0]->available_booking."' )";
                                $wpdb->query( $insert_date );
                            }
                        }
                    }
                }
            }
        }
    }
    
    function save_booking_dates_call() {
        global $wpdb, $woocommerce;
        $order_query = "SELECT * FROM `" . $wpdb->prefix."booking_order_history` WHERE order_id = '" . $_POST[ 'order_id' ] . "' ";
        $existing_order_result  =   $wpdb->get_results( $order_query );
        $new_order              =   false;
        $edit_order             =   true;
        if ( count( $existing_order_result ) == 0 ) {
            $new_order     =   true;
            $edit_order    =   false;
        }
        $validation_message = "" ;
        $order          =   new WC_Order( $_POST[ 'order_id' ] );
        $item_values    =   $order->get_items();
        $items          =   array();
        $order_total = 0;
        foreach ( $item_values as $cart_item_key => $values ) {
            $post_id      =   bkap_common::bkap_get_product_id( $values[ 'product_id' ] );
            $product      =   get_product( $post_id );
            $product_type =   $product->product_type;
            $price = bkap_common::bkap_get_price($post_id, $values[ 'variation_id' ], $product_type);	
            if( isset( $_POST[ 'start_date' ] ) ) {
                $start_date = $_POST[ 'start_date' ];
            } else {
                $start_date = "";
            }
            	
            if( isset( $_POST[ 'end_date' ] ) ) {
                $end_date = $_POST[ 'end_date' ];
            } else {
                $end_date = "";
            }
            
            if( isset( $_POST[ 'time_slot' ] ) ) {
                $time_slot = $_POST[ 'time_slot' ];
            } else {
                $time_slot = "";
            }
            
            $booking_settings                =   get_post_meta( $post_id, 'woocommerce_booking_settings', true );
            $date_name                       =   get_option( 'book_item-meta-date' );
            $number_of_fixed_price_blocks    =   bkap_block_booking::bkap_get_fixed_blocks_count( $post_id );
            $check_out_name                  =   strip_tags( get_option( "checkout_item-meta-date" ) );
                    	
            if ( isset( $_POST[ 'start_date' ] ) && $_POST[ 'start_date' ] != "" ) {
                $date_select = $_POST[ 'start_date' ];
                wc_update_order_item_meta( $cart_item_key, $date_name, sanitize_text_field( $date_select, true ) );
                wc_update_order_item_meta( $cart_item_key, '_wapbk_booking_date', sanitize_text_field( $_POST[ 'hidden_date' ], true ) );
            }
        
            $date_checkout_select    =   '';
            $details                 =   array();
            if ( isset( $_POST[ 'end_date' ] ) && $_POST[ 'end_date' ] != "" ) {
                $date_checkout_select = $_POST[ 'end_date' ];
                wc_update_order_item_meta( $cart_item_key, $check_out_name, sanitize_text_field( $date_checkout_select, true ) );
                wc_update_order_item_meta( $cart_item_key, '_wapbk_checkout_date', sanitize_text_field( $_POST[ 'hidden_date_checkout' ], true ) );
                
                if ( isset( $_POST[ 'hidden_date_checkout' ] ) ) {
                    $checkout_date = $_POST[ 'hidden_date_checkout' ];
                }
                            	
                if ( isset( $_POST[ 'hidden_date' ] ) ) {
                    $checkin_date = $_POST[ 'hidden_date' ];
                }
                $days = ( strtotime( $checkout_date ) - strtotime( $checkin_date ) ) / (60*60*24);
                
                if ( isset( $_POST[ 'same_day' ] ) && $_POST[ 'same_day' ] == 'on' ) {
                    if ( $days >= 0 ) {
                        if( isset( $_POST[ 'add_days' ] ) ) {
                            $days   =   $days + $_POST[ 'add_days' ];
                        }
                        $total_price = $days * $price;
                    }
                } else {
                    if ( $days > 0 ) {
                        if( isset( $_POST[ 'add_days' ] ) ) {
                            $days = $days + $_POST[ 'add_days' ];
                        }
                        $total_price = $days * $price;
                    }
                }
                    
                if ( isset( $booking_settings[ 'booking_fixed_block_enable' ] ) && $booking_settings['booking_fixed_block_enable'] == 'yes'  &&( isset( $number_of_fixed_price_blocks ) && $number_of_fixed_price_blocks > 0 ) ) {
                    if( isset( $_POST[ 'admin_block_option_price' ] ) ) {
                        $total_price = $items[ 'admin_block_option_price' ] * $values[ 'qty' ];
                    } else {
                        $total_price = '';
                    }
                } else if( isset( $booking_settings[ 'booking_block_price_enable' ] ) && $booking_settings[ 'booking_block_price_enable' ] == 'yes' ) {
                    if ( $product_type == 'variable' ) {
                        $_product            =   new WC_Product_Variation( $values[ 'variation_id' ] );
                        $var_attributes      =   $_product->get_variation_attributes( );
                        $attribute_names     =   str_replace( "-", " ", $var_attributes );
                    } else {
                        $attribute_names     =   array();
                    }
                    $get_price        =   bkap_block_booking_price::price_range_calculate_price( $post_id, $product_type, $values[ 'variation_id' ], $days, $attribute_names );
                    $price_exploded   =   explode( "-", $get_price );
                    $total_price      =   '';
                    $total_price      =   $price_exploded[0] * $values[ 'qty' ];
                }
            
                // Round the price if rounding is enabled
                $global_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
                if ( isset( $global_settings->enable_rounding ) && $global_settings->enable_rounding == "on" ) {
                    $round_price   =   round( $total_price );
                    $total_price   =   $round_price;
                }
                $order_total += $total_price;
                $query_update_subtotal          =   "UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
        								        SET meta_value = '".woocommerce_clean( $total_price )."'
        								        WHERE order_item_id = '".$cart_item_key."'
        								        AND meta_key = '_line_subtotal'";
                $wpdb->query( $query_update_subtotal );
        
                $query_update_total             =  "UPDATE `".$wpdb->prefix."woocommerce_order_itemmeta`
        							           SET meta_value = '".woocommerce_clean( $total_price )."'
        							           WHERE order_item_id = '".$cart_item_key."'
        							           AND meta_key = '_line_total'";
                $wpdb->query( $query_update_total );
            }
            
            $time_name = get_option( 'book_item-meta-time' );
            if ( isset( $time_slot ) && $time_slot != "" ) {
                $time_select    =   $time_slot;
                $time_exploded  =   explode( "-", $time_select );
                $saved_settings =   json_decode( get_option('woocommerce_booking_global_settings') );
        
                if ( isset( $saved_settings ) ) {
                    $time_format = $saved_settings->booking_time_format;
                } else {
                    $time_format = "12";
                }
                $time_slot_to_display   =   '';
                $from_time              =   trim( $time_exploded[0] );
                if( isset( $time_exploded[1] ) ) {
                    $to_time = trim( $time_exploded[1] );
                } else {
                    $to_time = '';
                }
        
                if ( $time_format == '12' ) {
                    $from_time = date( 'h:i A', strtotime( $time_exploded[0] ) );
                    if( isset( $time_exploded[1] ) ) {
                        $to_time = date( 'h:i A', strtotime( $time_exploded[1] ) );
                    }
                }

                $query_from_time = $meta_data_format = date( 'G:i', strtotime( $time_exploded[0] ) );
        
                if( isset( $time_exploded[1] ) ) {
                    $query_to_time = date( 'G:i', strtotime( $time_exploded[1] ) );
                    $meta_data_format   .= ' - ' . $query_to_time;
                } else {
                    $query_to_time = '';
                }
        
                if( $to_time != '' ) {
                    $time_slot_to_display = $from_time.' - '.$to_time;
                } else {
                    $time_slot_to_display = $from_time;
                }
                
                wc_update_order_item_meta( $cart_item_key,  $time_name, $time_slot_to_display );
                wc_update_order_item_meta( $cart_item_key,  '_wapbk_time_slot', $meta_data_format, true );
            }
        
            $saved_settings = json_decode( get_option( 'woocommerce_booking_global_settings' ) );
            $date_format    = $saved_settings->booking_date_format;
            if ( $new_order == false && $edit_order == true ) {
                if( isset( $values[ 'item_meta' ][ '_wapbk_booking_date' ][ 0 ] ) ) {
                    $previous_date = $values[ 'item_meta' ][ '_wapbk_booking_date' ][ 0 ];
                } else {
                    $previous_date = '';
                }
                
                if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                    if( isset( $values[ 'item_meta' ][ '_wapbk_checkout_date' ][ 0 ] ) ) {
                        $previous_date_checkout = $values[ 'item_meta' ][ '_wapbk_checkout_date' ][ 0 ];
                    } else {
                        $previous_date_checkout = '';
                    }
                    $booking_ids = array();
                    if( isset( $_POST[ 'end_date' ] ) && $_POST[ 'end_date' ] != "" ) {
                        if( $date_format == 'dd/mm/y' ) {
                            $date_explode    =   explode( "/", $_POST[ 'start_date' ] );
                            $start_date      =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
                        } else {
                            $date_str    =   str_replace( ",", "", $_POST[ 'start_date' ] );
                            $start_date  =   date( 'Y-m-d', strtotime( $date_str ) );
                        }

                        if( $date_format == 'dd/mm/y' ) {
                            $checkout_date_explode   =   explode( "/", $_POST[ 'end_date' ] );
                            $end_date                =   date( 'Y-m-d', mktime( 0, 0, 0, $checkout_date_explode[1], $checkout_date_explode[0], $checkout_date_explode[2] ) );
                        } else {
                            $checkout_date_str   =   str_replace( ",", "", $_POST[ 'end_date' ] );
                            $end_date            =   date( 'Y-m-d', strtotime( $checkout_date_str ) );
                        }

                        foreach ( $existing_order_result as $ekey => $evalue ) {
                            $booking_id         =   $evalue->booking_id;
                            $query              =   "SELECT * FROM `".$wpdb->prefix."booking_history` WHERE id = $booking_id ";
                            $item_results       =   $wpdb->get_results( $query );
                            if( count( $item_results ) > 0) {
                                $booking_ids[] = $booking_id;
                            }
                        }

                        for ( $i = 0; $i < $values[ 'qty' ]; $i++ ) {
                            $query = "DELETE FROM `" . $wpdb->prefix . "booking_history` WHERE id = " . $booking_ids[ $i ];
                            $wpdb->query( $query );
                            
                            $query = "DELETE FROM `" . $wpdb->prefix . "booking_order_history` WHERE booking_id = " . $booking_ids[ $i ];
                            $wpdb->query( $query );
                       
                            $query = "INSERT INTO `".$wpdb->prefix."booking_history`
				                (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
								VALUES (
								'".$post_id."',
								'',
								'".$start_date."',
								'".$end_date."',
								'',
								'',
								'0',
								'0' )";
                            $wpdb->query( $query );
                            $new_booking_id  =   $wpdb->insert_id;
                            
                            $order_query = "INSERT INTO `".$wpdb->prefix."booking_order_history`
																(order_id,booking_id)
																VALUES (
																'" . $_POST[ 'order_id' ] . "',
																'" . $new_booking_id . "' )";
                            $wpdb->query( $order_query );
                        }
                    }
                } else if ( isset( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
                    $previous_time_slot = $values[ 'item_meta' ][ '_wapbk_time_slot' ][ 0 ];                    
                    if( $date_format == 'dd/mm/y' ) {
                        $date_explode   =   explode( "/", $_POST[ 'start_date' ] );
                        $start_date     =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
                    } else {
                        $date_str       = str_replace( ",", "", $_POST[ 'start_date' ] );
                        $start_date     = date( 'Y-m-d', strtotime( $date_str ) );
                    }
                    $booking_ids = array();
                    if( isset( $_POST[ 'time_slot' ] ) && $_POST[ 'time_slot' ] != '' ) {
                        $time_slot =   explode( "-", $_POST[ 'time_slot' ] );
                        $from_time =   date( "G:i", strtotime( $time_slot[0] ) );
                        if( isset( $time_slot[1] ) ) {
                            $to_time   =   date( "G:i", strtotime( $time_slot[1] ) );
                        } else {
                            $to_time  = '';
                        }
                        
                        foreach ( $existing_order_result as $ekey => $evalue ) {
                            $booking_id         =   $evalue->booking_id;
                            $query              =   "SELECT * FROM `" . $wpdb->prefix . "booking_history` WHERE id = $booking_id AND post_id = $post_id";
                            $item_results       =   $wpdb->get_results( $query );
                            if( count( $item_results ) > 0) {
                                $booking_ids[] = $booking_id;
                            }
                        }
                        
                        for ( $i = 0; $i < $values[ 'qty' ]; $i++ ) {
                            $query = "UPDATE `" . $wpdb->prefix . "booking_history` SET available_booking = available_booking + " . $values[ 'qty' ] . " WHERE id = '" . $booking_ids[ $i ] . "'";
                            $wpdb->query( $query );
                            
                            $query = "DELETE FROM `" . $wpdb->prefix . "booking_order_history` WHERE booking_id = " . $booking_ids[ $i ];
                            $wpdb->query( $query );
                            
                            if( $to_time != "" ) {
                                $query = "UPDATE `" . $wpdb->prefix . "booking_history`
                                    SET available_booking = available_booking - " . $values[ 'qty' ] . "
                                    WHERE post_id = '".$post_id."' AND
									start_date = '".$start_date."' AND
									from_time = '".$from_time."' AND
									to_time = '".$to_time."' AND
									total_booking > 0";
                                $wpdb->query( $query );
                                
                            } else {
                                $query = "UPDATE `".$wpdb->prefix."booking_history`
									SET available_booking = available_booking + " . $values[ 'qty' ] . "
									WHERE post_id = '".$post_id."' AND
									start_date = '".$start_date."' AND
									from_time = '".$from_time."' AND
									total_booking > 0";
                                $wpdb->query( $query );
                            }
                        }
                        
                        if( $to_time != '' ) {
                            $order_select_query =   "SELECT id FROM `".$wpdb->prefix."booking_history`
                        			WHERE post_id = '".$post_id."' AND
                        			start_date = '".$start_date."' AND
                        			from_time = '".$from_time."' AND
                        			to_time = '".$to_time."' ";
                            $order_results      =   $wpdb->get_results( $order_select_query );
                        } else {
                            $order_select_query =   "SELECT id FROM `".$wpdb->prefix."booking_history`
									WHERE post_id = '".$post_id."' AND
									start_date = '".$start_date."' AND
									from_time = '".$from_time."'";
                            $order_results      =   $wpdb->get_results( $order_select_query );
                        }
                        
                        $j = 0;
                        foreach( $order_results as $k => $v ) {
                            $booking_id  =  $order_results[ $j ]->id;
                            $order_query =  "INSERT INTO `" . $wpdb->prefix . "booking_order_history`
                											(order_id,booking_id)
                											VALUES (
                											'" . $_POST['order_id'] . "',
                											'" . $booking_id . "' )";
                            $wpdb->query( $order_query );
                            $j++;
                        }
                    } 
                } else {
                    if( $date_format == 'dd/mm/y' ) {
                        $date_explode    =   explode( "/", $_POST[ 'start_date' ] );
                        $start_date      =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
                    } else {
                        $date_str        =   str_replace( ",", "", $_POST[ 'start_date' ] );
                        $start_date      =   date( 'Y-m-d', strtotime( $date_str ) );
                    }
                    $booking_ids = array();
                    foreach ( $existing_order_result as $ekey => $evalue ) {
                        $booking_id         =   $evalue->booking_id;
                        $query              =   "SELECT * FROM `" . $wpdb->prefix . "booking_history` WHERE id = $booking_id AND post_id = $post_id";
                        $item_results       =   $wpdb->get_results( $query );
                        if( count( $item_results ) > 0) {
                            $booking_ids[] = $booking_id;
                        }
                    }
                    
                    for ( $i = 0; $i < $values[ 'qty' ]; $i++ ) {
                        $query = "UPDATE `".$wpdb->prefix."booking_history`
						   SET available_booking = available_booking + " . $values[ 'qty' ] . "
						   WHERE id = '" . $booking_ids[ $i ] . "'";
                        $wpdb->query( $query );
                        
                        $query = "DELETE FROM `" . $wpdb->prefix . "booking_order_history` WHERE booking_id = " . $booking_ids[ $i ];
                        $wpdb->query( $query );
                        
                        $query = "UPDATE `".$wpdb->prefix."booking_history`
						   SET available_booking = available_booking - ".$values[ 'qty' ]."
						   WHERE post_id = '" . $post_id . "' AND
						   start_date = '" . $start_date . "' AND
						   total_booking > 0";
                        $wpdb->query( $query );
                    }
                    
                    $order_select_query = "SELECT id FROM `".$wpdb->prefix."booking_history`
																	WHERE post_id = '".$post_id."' AND
																	start_date = '".$start_date."'";
                    $order_results = $wpdb->get_results( $order_select_query );
                    
                    $j = 0;
                    foreach( $order_results as $k => $v ) {
                        $booking_id  =  $order_results[ $j ]->id;
                        $order_query =  "INSERT INTO `" . $wpdb->prefix . "booking_order_history`
                											(order_id,booking_id)
                											VALUES (
                											'" . $_POST['order_id'] . "',
                											'" . $booking_id . "' )";
                        $wpdb->query( $order_query );
                        $j++;
                    }
                }
            } else {
                if ( isset( $booking_settings[ 'booking_enable_multiple_day' ] ) && $booking_settings[ 'booking_enable_multiple_day' ] == 'on' ) {
                    if( isset( $_POST[ 'end_date' ] ) && $_POST[ 'end_date' ] != "" ) {
                        if( $date_format == 'dd/mm/y' ) {
                            $date_explode    =   explode( "/", $_POST[ 'start_date' ] );
                            $start_date      =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
                        } else {
                            $date_str    =   str_replace( ",", "", $_POST[ 'start_date' ] );
                            $start_date  =   date( 'Y-m-d', strtotime( $date_str ) );
                        }
                
                        if( $date_format == 'dd/mm/y' ) {
                            $checkout_date_explode   =   explode( "/", $_POST[ 'end_date' ] );
                            $end_date                =   date( 'Y-m-d', mktime( 0, 0, 0, $checkout_date_explode[1], $checkout_date_explode[0], $checkout_date_explode[2] ) );
                        } else {
                            $checkout_date_str   =   str_replace( ",", "", $_POST[ 'end_date' ] );
                            $end_date            =   date( 'Y-m-d', strtotime( $checkout_date_str ) );
                        }
                
                        for ( $i = 0; $i < $values[ 'qty' ]; $i++ ) {
                            $query = "INSERT INTO `".$wpdb->prefix."booking_history`
				                (post_id,weekday,start_date,end_date,from_time,to_time,total_booking,available_booking)
								VALUES (
								'".$post_id."',
								'',
								'".$start_date."',
								'".$end_date."',
								'',
								'',
								'0',
								'0' )";
                            $wpdb->query( $query );
                            $new_booking_id  =   $wpdb->insert_id;
                
                            $order_query = "INSERT INTO `".$wpdb->prefix."booking_order_history`
																(order_id,booking_id)
																VALUES (
																'" . $_POST[ 'order_id' ] . "',
																'" . $new_booking_id . "' )";
                            $wpdb->query( $order_query );
                        }
                    }
                } else if ( isset( $booking_settings[ 'booking_enable_time' ] ) && $booking_settings[ 'booking_enable_time' ] == 'on' ) {
                    $previous_time_slot = $values[ 'item_meta' ][ '_wapbk_time_slot' ][ 0 ];
                    if( $date_format == 'dd/mm/y' ) {
                        $date_explode   =   explode( "/", $_POST[ 'start_date' ] );
                        $start_date     =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
                    } else {
                        $date_str       = str_replace( ",", "", $_POST[ 'start_date' ] );
                        $start_date     = date( 'Y-m-d', strtotime( $date_str ) );
                    }
                
                    if( isset( $_POST[ 'time_slot' ] ) && $_POST[ 'time_slot' ] != '' ) {
                        $time_slot =   explode( "-", $_POST[ 'time_slot' ] );
                        $from_time =   date( "G:i", strtotime( $time_slot[0] ) );
                        if( isset( $time_slot[1] ) ) {
                            $to_time   =   date( "G:i", strtotime( $time_slot[1] ) );
                        } else {
                            $to_time  = '';
                        }
                                
                        for ( $i = 0; $i < $values[ 'qty' ]; $i++ ) {
                            if( $to_time != "" ) {
                                $query = "UPDATE `" . $wpdb->prefix . "booking_history`
                                    SET available_booking = available_booking - " . $values[ 'qty' ] . "
                                    WHERE post_id = '".$post_id."' AND
									start_date = '".$start_date."' AND
									from_time = '".$from_time."' AND
									to_time = '".$to_time."' AND
									total_booking > 0";
                                $wpdb->query( $query );
                            } else {
                                $query = "UPDATE `".$wpdb->prefix."booking_history`
									SET available_booking = available_booking + " . $values[ 'qty' ] . "
									WHERE post_id = '".$post_id."' AND
									start_date = '".$start_date."' AND
									from_time = '".$from_time."' AND
									total_booking > 0";
                                $wpdb->query( $query );
                            }
                        }
                        
                        if( $to_time != '' ) {
                            $order_select_query =   "SELECT id FROM `".$wpdb->prefix."booking_history`
                        			WHERE post_id = '".$post_id."' AND
                        			start_date = '".$start_date."' AND
                        			from_time = '".$from_time."' AND
                        			to_time = '".$to_time."' ";
                            $order_results      =   $wpdb->get_results( $order_select_query );
                        } else {
                            $order_select_query =   "SELECT id FROM `".$wpdb->prefix."booking_history`
									WHERE post_id = '".$post_id."' AND
									start_date = '".$start_date."' AND
									from_time = '".$from_time."'";
                            $order_results      =   $wpdb->get_results( $order_select_query );
                        }
                        
                        $j = 0;
                        foreach( $order_results as $k => $v ) {
                            $booking_id  =  $order_results[ $j ]->id;
                            $order_query =  "INSERT INTO `" . $wpdb->prefix . "booking_order_history`
                											(order_id,booking_id)
                											VALUES (
                											'" . $_POST['order_id'] . "',
                											'" . $booking_id . "' )";
                            $wpdb->query( $order_query );
                            $j++;
                        }
                    }
                } else {
                    if( $date_format == 'dd/mm/y' ) {
                        $date_explode    =   explode( "/", $_POST[ 'start_date' ] );
                        $start_date      =   date( 'Y-m-d', mktime( 0, 0, 0, $date_explode[1], $date_explode[0], $date_explode[2] ) );
                    } else {
                        $date_str        =   str_replace( ",", "", $_POST[ 'start_date' ] );
                        $start_date      =   date( 'Y-m-d', strtotime( $date_str ) );
                    }

                    for ( $i = 0; $i < $values[ 'qty' ]; $i++ ) {
                        $query = "UPDATE `".$wpdb->prefix."booking_history`
						   SET available_booking = available_booking - ".$values[ 'qty' ]."
						   WHERE post_id = '" . $post_id . "' AND
						   start_date = '" . $start_date . "' AND
						   total_booking > 0";
                        $wpdb->query( $query );
                        
                    }
                    
                    $order_select_query = "SELECT id FROM `".$wpdb->prefix."booking_history`
																	WHERE post_id = '".$post_id."' AND
																	start_date = '".$start_date."'";
                    $order_results = $wpdb->get_results( $order_select_query );
                    $j = 0;
                    foreach( $order_results as $k => $v ) {
                        $booking_id  =  $order_results[ $j ]->id;
                        $order_query =  "INSERT INTO `" . $wpdb->prefix . "booking_order_history`
                											(order_id,booking_id)
                											VALUES (
                											'" . $_POST['order_id'] . "',
                											'" . $booking_id . "' )";
                        $wpdb->query( $order_query );
                        $j++;
                    }
                }
            }
        }
        $order->set_total( $order_total );
        die();
    }
    
    function orddd_load_delivery_dates() {
        if( isset( $_POST[ 'order_id' ] ) ) {
            $order_id = $_POST[ 'order_id' ];
        } else {
            $order_id = '';
        }
        
    }
             
    function wapbk_approve_booking_order_based( $item_id ) {
        global $wpdb;
        if( get_option( 'global_allow_booking_confirmation_order_based' ) == 'on' ) {
            $booking_status = array( 'pending-confirmation' => 'pending-confirmation',
                'confirmed'         => 'confirmed',
                'paid'              => 'paid',
                'cancelled'         => 'cancelled'
            );
            
            $query_order_id = "SELECT order_id FROM `". $wpdb->prefix . "woocommerce_order_items`
                        WHERE order_item_id = %d";
            $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
            if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
                $order_id = $get_order_id[0]->order_id;
            }
            
            // get the order details from post
            $post_data = get_post( $order_id );
            //create order object
            $order = new WC_Order( $order_id );
            // order details
            $order_data = $order->get_items();
            
            // customer details
            $customer_id = $order->user_id;
            
            if ( isset( $customer_id ) && 0 == $customer_id ) {
                $customer_login = 'Guest';
            } else {
                $customer_details = get_userdata( $customer_id );
                $customer_login = $customer_details->data->user_login;
            }
            
            // name
            $customer_name = $order->billing_first_name . " " . $order->billing_last_name;
            //billing address
            $customer_address = $order->get_formatted_billing_address();
            // replace the <br/> tag with HTML entities &#10; Line Feed and &#13; Carriage Return
            $customer_address = str_replace( '<br/>', '&#13;&#10;', $customer_address );
            // billing email
            $customer_email = $order->billing_email;
            // phone
            $customer_phone = $order->billing_phone;
            
            $start_date_label = get_option( 'book_item-meta-date' );
            $end_date_label = get_option( 'checkout_item-meta-date' );
            $time_label = get_option( 'book_item-meta-time' );
            
            $product_name = '';
            $booking_start_date = '';
            $booking_end_date = '';
            $booking_time = '';
            
            foreach ( $order_data as $item_key => $item_value ) {
                $product_id = $item_value[ 'product_id' ];
                $item_booking_status = $item_value[ 'wapbk_booking_status' ];
                break;
            }
            
            ?>
            <h2>Booking Details</h2>
                Order number:
                <a href="<?php echo admin_url( 'post.php?post=' . $order_id . '&action=edit' ); ?>">#<?php echo $order_id; ?></a>
                <div id="updated_message" class="updated fade" style="display: none;">
                    <p>
                        <strong><?php _e( 'Your settings have been saved.', 'woocommerce-booking' ); ?></strong>
    	           </p>   
                </div>
                <div id="first" style="height: 230px;">
    	           <div id="general_details" style="width: 50%; max-width: 550px; float: left;">
                        <h4>General Details</h4>
                        <table style="width: 100%; max-width: 450px;">
                            <tr>
    				            <th style="vertical-align: top; float: left;"><label for="order_id"><?php _e( 'Order ID', 'woocommerce-booking' ); ?></label></th>
    				            <td>
    				                <input type="text" style="width: 100%; max-width: 200px;"name="order_id" id="order_id" value="<?php echo $order_id; ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="order_date"><?php _e( 'Date Created', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="order_date" id="order_date" value="<?php echo date( 'M d, Y H:i A', strtotime( $post_data->post_date ) ); ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="booking_status"><?php _e( 'Booking Status', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <?php
                                    $field_status = 'disabled';
                                    $requires_confirmation = bkap_common::bkap_product_requires_confirmation( $product_id );
                                    if ( $requires_confirmation ) {
                                        $field_status = '';
                                    } 
                                    ?>
                                    <select id="booking_status" name="booking_status" <?php echo $field_status; ?> style="width: 200px;">
                                        <?php
                                        if ( ( isset( $item_booking_status ) && '' == $item_booking_status ) || ! isset( $item_booking_status ) ) {
                                            $item_booking_status = 'paid';
                                        }
                                        foreach ( $booking_status as $key => $value ) {
                                            $selected_attr = '';
                                            if ( $value == $item_booking_status ) {
                                                $selected_attr = 'selected';
                                            }
                                            printf( "<option %s value='%s'>%s</option>\n",
                                                esc_attr( $selected_attr ),
                                                esc_attr( $value ),
                                                $value
                                            );
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="customer"><?php _e( 'Customer', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="customer" id="customer" value="<?php echo $customer_login; ?>" readonly>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div id="customer_details" style="width: 50%; max-width: 550px; float: right;">
                        <h4>Customer Details</h4>
                        <table style="width: 100%; max-width: 450px;">
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="customer_name"><?php _e( 'Name', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="customer_name" id="customer_name" value="<?php echo $customer_name; ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="customer_address"><?php _e( 'Address', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <textarea style="width: 100%; max-width: 200px;" rows="5" name="customer_address" id="customer_address" readonly><?php echo $customer_address; ?></textarea>
                            	</td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="customer_email"><?php _e( 'Email', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="customer_email" id="customer_email" value="<?php echo $customer_email; ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="customer_phone"><?php _e( 'Phone', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="customer_phone" id="customer_phone" value="<?php echo $customer_phone; ?>" readonly>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div id="booking_details" style="width: 100%; max-width: 450px; float: left;">
                    <h4>Booking Details</h4>
                    <table>
                        <?php 
                        foreach ( $order_data as $item_key => $item_value ) {
                            $product_name = $item_value['name'];
                            if( isset( $item_value[ $start_date_label ] ) && '' != $item_value[ $start_date_label ] ) {
                                $booking_start_date = $item_value[ $start_date_label ];
                            }
                
                            if( isset( $item_value[ $end_date_label ] ) && '' != $item_value[ $end_date_label ] ) {
                                $booking_end_date = $item_value[ $end_date_label ];
                            }
                
                            if( isset( $item_value[ $time_label ] ) && '' != $item_value[ $time_label ] ) {
                                $booking_time = $item_value[ $time_label ];
                            }
                           
                            ?>
                            <tr>
                                <th style="vertical-align: top; float: left;"><label for="product_booked"><?php _e( 'Product Booked', 'woocommerce-booking' ); ?></label></th>
                                <td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="product_booked" id="product_booked" value="<?php echo $product_name; ?>" readonly>
                                </td>
                            </tr>
                    		<tr>
                    			<th style="vertical-align: top; float: left;"><label for="start_date"><?php _e( 'Booking Start Date', 'woocommerce-booking' ); ?></label></th>
                    			<td>
                                    <input type="text" style="width: 100%; max-width: 200px;" name="start_date" id="start_date" value="<?php echo $booking_start_date; ?>" readonly>
                                </td>
                    		</tr>
                            <?php 
                            if ( isset ( $booking_end_date ) && '' != $booking_end_date ) {
                                ?>
                                <tr>
                        			<th style="vertical-align: top; float: left;"><label for="end_date"><?php _e( 'Booking End Date', 'woocommerce-booking' ); ?></label></th>
                        			<td>
                                        <input type="text" style="width: 100%; max-width: 200px;" name="end_date" id="end_date" value="<?php echo $booking_end_date; ?>" readonly>
                                    </td>
                        		</tr>
                                <?php 
                            }
                            if ( isset ( $booking_time ) && '' != $booking_time ) {
                                ?>
                                <tr>
                                    <th style="vertical-align: top; float: left;"><label for="booking_time"><?php _e( 'Booking Time', 'woocommerce-booking' ); ?></label></th>
                                    <td>
                                        <input type="text" style="width: 100%; max-width: 200px;" name="booking_time" id="booking_time" value="<?php echo $booking_time; ?>" readonly>
                                    </td>
                                </tr>
                                <?php 
                            }
                        }
                        ?>
                    </table>
                    <br> 
                    <input type="button" class="button-primary" id="save_status" name="save_status" value="<?php _e( 'Save', 'woocommerce-booking' ); ?>" onclick="bkap_save_booking_status(<?php echo $item_id;?>)" />
                </div>
            <?php 
            return 'Yes';
        } else {
            return 'No';
        }
    } 
    
    function wapbk_save_booking_status_order_based( $item_id ) {
        global $wpdb;
        if( get_option( 'global_allow_booking_confirmation_order_based' ) == 'on' ) {
            $query_order_id = "SELECT order_id FROM `". $wpdb->prefix . "woocommerce_order_items`
                        WHERE order_item_id = %d";
            $get_order_id = $wpdb->get_results( $wpdb->prepare( $query_order_id, $item_id ) );
            if ( isset( $get_order_id ) && is_array( $get_order_id ) && count( $get_order_id ) > 0 ) {
                $order_id = $get_order_id[0]->order_id;
            }
            $post_data = get_post( $order_id );
            $order = new WC_Order( $order_id );
            $order_data = $order->get_items();
            $_status = $_POST[ 'status' ];
            foreach( $order_data as $order_data_key => $order_data_value ) {
                wc_update_order_item_meta( $order_data_key, '_wapbk_booking_status', $_status );        
                // if the booking has been denied, release the bookings for re-allotment
                if ( 'cancelled' == $_status ) {
                    $array = $b = $results_post =  $a = array();
                    // get the order ID
                    $item_value = $order_data_value;
                    $select_query =   "SELECT booking_id FROM `".$wpdb->prefix."booking_order_history` WHERE order_id=%d";
                    $results      =   $wpdb->get_results ( $wpdb->prepare( $select_query, $order_id ) );
                    foreach( $results as $k => $v ) {
                        $b[]                 =   $v->booking_id;
                        $select_query_post   =   "SELECT post_id,id FROM `".$wpdb->prefix."booking_history` WHERE id= %d";
                        $results_post[]      =   $wpdb->get_results( $wpdb->prepare( $select_query_post, $v->booking_id ) );                 
                    }
                    if ( isset( $results_post ) && count( $results_post ) > 0 && $results_post != false ) {
                        foreach( $results_post as $k => $v ) {
                            if ( isset( $v[0]->id ) ) {
                                $a[ $v[0]->post_id ][] = $v[0]->id;
                            }
                        }
                    }
                    bkap_cancel_order::bkap_reallot_item( $order_data_value, $array, $a, $b, $order_id );
                }
            }
            // create an instance of the WC_Emails class , so emails are sent out to customers
            new WC_Emails();
            if ( 'cancelled' == $_status ) {
                do_action( 'bkap_booking_pending-confirmation_to_cancelled_notification', $item_id );
                do_action( 'bkap_booking_pending-confirmation_to_cancelled', $item_id );
            
            } else if ( 'confirmed' == $_status ) {// if booking has been approved, send email to user
                do_action( 'bkap_booking_confirmed_notification', $item_id );
            }
            return 'Yes';
        } else {
            return 'No';
        }
    }
    
    function wapbk_customer_booking_cancelled_email( $order ) {
        if( get_option( 'global_allow_booking_confirmation_order_based' ) == 'on' ) {
            ob_start();
            wc_get_template( 'emails/email-order-details.php', 
                array( 'order' => $order, 
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => '' ) );
            echo ob_get_clean();
            return 'Yes';
        } else {
            return 'No';
        }
    }

    function wapbk_is_order_based_booking_confirmation() {
        if( get_option( 'global_allow_booking_confirmation_order_based' ) == 'on' ) {
            return 'Yes';
        } else {
            return 'No';
        }
    }
}
$bkap_order_based_bookings = new bkap_order_based_bookings();