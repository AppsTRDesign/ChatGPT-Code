<?php
namespace NoaSoft\AiWoo\Helpers;

use NoaSoft\AiWoo\Frontend\UX_Tracker;

/**
 * Helper utilities for reports.
 */
class Reports_Helper {
    /**
     * Cache for UX table lookup.
     *
     * @var bool|null
     */
    protected $ux_table_exists = null;

    /**
     * Get reports table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'noasoft_ai_reports';
    }

    /**
     * Create reports table.
     *
     * @return void
     */
    public static function create_table() {
        global $wpdb;
        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(190) NOT NULL,
            raw_data longtext NULL,
            ai_summary longtext NULL,
            ai_recommendations longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Drop reports table.
     *
     * @return void
     */
    public static function drop_table() {
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Collect metrics for AI reports.
     *
     * @return array
     */
    public function collect_metrics() {
        $range = $this->get_range();

        $metrics = array(
            'range'       => $range,
            'events'      => $this->get_event_metrics( $range ),
            'orders'      => $this->get_order_metrics( $range ),
            'bestsellers' => $this->get_top_products( 'total_sales' ),
            'highest_rated' => $this->get_top_products( 'rating' ),
        );

        $metrics['kpis'] = $this->calculate_kpis( $metrics );

        return $metrics;
    }

    /**
     * Store report row.
     *
     * @param array $data Report data.
     * @return array|null
     */
    public function save_report( $data ) {
        global $wpdb;
        $table = self::get_table_name();
        $inserted = $wpdb->insert(
            $table,
            array(
                'title'               => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
                'raw_data'            => isset( $data['raw_data'] ) ? wp_json_encode( $data['raw_data'] ) : '',
                'ai_summary'          => isset( $data['ai_summary'] ) ? wp_kses_post( $data['ai_summary'] ) : '',
                'ai_recommendations'  => isset( $data['ai_recommendations'] ) ? wp_kses_post( $data['ai_recommendations'] ) : '',
                'created_at'          => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return null;
        }

        return $this->get_report( $wpdb->insert_id );
    }

    /**
     * Fetch multiple reports.
     *
     * @param int $limit Limit.
     * @return array
     */
    public function get_reports( $limit = 20 ) {
        global $wpdb;
        $table = self::get_table_name();

        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, title, created_at FROM {$table} ORDER BY created_at DESC LIMIT %d", absint( $limit ) ), ARRAY_A );

        return $rows ? $rows : array();
    }

    /**
     * Fetch single report.
     *
     * @param int $id Report ID.
     * @return array|null
     */
    public function get_report( $id ) {
        global $wpdb;
        $table = self::get_table_name();

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), ARRAY_A );
        if ( ! $row ) {
            return null;
        }

        $row['metrics'] = array();
        if ( ! empty( $row['raw_data'] ) ) {
            $decoded = json_decode( $row['raw_data'], true );
            if ( is_array( $decoded ) ) {
                $row['metrics'] = $decoded;
            }
        }

        return $row;
    }

    /**
     * Get date range array.
     *
     * @return array
     */
    protected function get_range() {
        $end_ts   = current_time( 'timestamp' );
        $start_ts = strtotime( '-30 days', $end_ts );

        return array(
            'start' => wp_date( 'Y-m-d H:i:s', $start_ts ),
            'end'   => wp_date( 'Y-m-d H:i:s', $end_ts ),
        );
    }

    /**
     * Gather UX tracker metrics.
     *
     * @param array $range Date range.
     * @return array
     */
    protected function get_event_metrics( $range ) {
        $events = array( 'view_product', 'add_to_cart', 'favorite', 'review' );
        $output = array();
        foreach ( $events as $event ) {
            $output[ $event ] = array(
                'total' => $this->count_events( $event, $range ),
                'top'   => $this->get_top_event_products( $event, $range ),
            );
        }

        $output['unique_sessions'] = $this->count_unique_sessions( $range );

        return $output;
    }

    /**
     * Count events by type.
     *
     * @param string $event Event key.
     * @param array  $range Range data.
     * @return int
     */
    protected function count_events( $event, $range ) {
        global $wpdb;
        $table = UX_Tracker::get_table_name();
        if ( ! $this->table_exists( $table ) ) {
            return 0;
        }

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE event_type = %s AND created_at BETWEEN %s AND %s",
            $event,
            $range['start'],
            $range['end']
        );

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Count unique sessions.
     *
     * @param array $range Date range.
     * @return int
     */
    protected function count_unique_sessions( $range ) {
        global $wpdb;
        $table = UX_Tracker::get_table_name();
        if ( ! $this->table_exists( $table ) ) {
            return 0;
        }

        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at BETWEEN %s AND %s",
            $range['start'],
            $range['end']
        );

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Get top products for specific event.
     *
     * @param string $event Event key.
     * @param array  $range Date range.
     * @return array
     */
    protected function get_top_event_products( $event, $range ) {
        global $wpdb;
        $table = UX_Tracker::get_table_name();
        if ( ! $this->table_exists( $table ) ) {
            return array();
        }

        $sql   = $wpdb->prepare(
            "SELECT product_id, COUNT(*) as total FROM {$table} WHERE event_type = %s AND product_id > 0 AND created_at BETWEEN %s AND %s GROUP BY product_id ORDER BY total DESC LIMIT 5",
            $event,
            $range['start'],
            $range['end']
        );
        $rows  = $wpdb->get_results( $sql, ARRAY_A );
        $items = array();

        if ( empty( $rows ) ) {
            return $items;
        }

        foreach ( $rows as $row ) {
            $items[] = array(
                'product_id' => (int) $row['product_id'],
                'count'      => (int) $row['total'],
                'product'    => $this->format_product_snapshot( (int) $row['product_id'] ),
            );
        }

        return $items;
    }

    /**
     * Order level metrics.
     *
     * @param array $range Date range.
     * @return array
     */
    protected function get_order_metrics( $range ) {
        global $wpdb;
        $orders_table = $wpdb->posts;
        $meta_table   = $wpdb->postmeta;

        $statuses_sql = $wpdb->prepare(
            "SELECT post_status, COUNT(*) AS total FROM {$orders_table} WHERE post_type = 'shop_order' AND post_date BETWEEN %s AND %s GROUP BY post_status",
            $range['start'],
            $range['end']
        );
        $status_rows  = $wpdb->get_results( $statuses_sql, ARRAY_A );
        $status_map   = array();
        $total_orders = 0;

        if ( $status_rows ) {
            foreach ( $status_rows as $row ) {
                $key                 = $row['post_status'];
                $status_map[ $key ]  = array(
                    'count' => (int) $row['total'],
                    'label' => $this->translate_status_label( $key ),
                );
                $total_orders       += (int) $row['total'];
            }
        }

        $revenue_sql = $wpdb->prepare(
            "SELECT SUM(meta.meta_value) FROM {$orders_table} AS posts INNER JOIN {$meta_table} AS meta ON posts.ID = meta.post_id WHERE posts.post_type = 'shop_order' AND posts.post_status IN ('wc-completed','wc-processing','wc-on-hold') AND meta.meta_key = '_order_total' AND posts.post_date BETWEEN %s AND %s",
            $range['start'],
            $range['end']
        );
        $revenue     = (float) $wpdb->get_var( $revenue_sql );
        $avg_order   = $total_orders ? ( $revenue / max( 1, $total_orders ) ) : 0.0;

        $repeat_sql = $wpdb->prepare(
            "SELECT meta.meta_value AS customer_id, COUNT(*) AS total FROM {$orders_table} AS posts INNER JOIN {$meta_table} AS meta ON posts.ID = meta.post_id WHERE posts.post_type = 'shop_order' AND meta.meta_key = '_customer_user' AND posts.post_date BETWEEN %s AND %s GROUP BY meta.meta_value HAVING CAST(meta.meta_value AS UNSIGNED) > 0",
            $range['start'],
            $range['end']
        );
        $repeat_rows   = $wpdb->get_results( $repeat_sql, ARRAY_A );
        $repeat_orders = 0;
        if ( $repeat_rows ) {
            foreach ( $repeat_rows as $row ) {
                if ( (int) $row['total'] > 1 ) {
                    $repeat_orders += (int) $row['total'];
                }
            }
        }

        return array(
            'statuses' => $status_map,
            'revenue'  => array(
                'total'   => round( $revenue, 2 ),
                'average' => round( $avg_order, 2 ),
            ),
            'totals'   => array(
                'orders'           => $total_orders,
                'repeat_customers' => $repeat_orders,
            ),
        );
    }

    /**
     * Top WooCommerce products via wc_get_products.
     *
     * @param string $orderby Order by field.
     * @return array
     */
    protected function get_top_products( $orderby ) {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return array();
        }

        $args = array(
            'status' => 'publish',
            'limit'  => 5,
            'return' => 'objects',
            'order'  => 'DESC',
        );
        if ( 'rating' === $orderby ) {
            $args['orderby'] = 'rating';
        } else {
            $args['orderby'] = 'total_sales';
        }

        $products = wc_get_products( $args );
        $items    = array();

        foreach ( $products as $product ) {
            $items[] = array_merge(
                $this->format_product_snapshot( $product->get_id() ),
                array(
                    'sales'  => (int) $product->get_total_sales(),
                    'rating' => (float) $product->get_average_rating(),
                )
            );
        }

        return $items;
    }

    /**
     * Calculate KPI metrics.
     *
     * @param array $metrics Metrics array.
     * @return array
     */
    protected function calculate_kpis( $metrics ) {
        $events        = isset( $metrics['events'] ) ? $metrics['events'] : array();
        $orders        = isset( $metrics['orders'] ) ? $metrics['orders'] : array();
        $sessions      = isset( $events['unique_sessions'] ) ? (int) $events['unique_sessions'] : 0;
        $views_total   = isset( $events['view_product']['total'] ) ? (int) $events['view_product']['total'] : 0;
        $cart_total    = isset( $events['add_to_cart']['total'] ) ? (int) $events['add_to_cart']['total'] : 0;
        $order_total   = isset( $orders['totals']['orders'] ) ? (int) $orders['totals']['orders'] : 0;
        $revenue       = isset( $orders['revenue']['total'] ) ? (float) $orders['revenue']['total'] : 0.0;
        $repeat        = isset( $orders['totals']['repeat_customers'] ) ? (int) $orders['totals']['repeat_customers'] : 0;

        $conversion    = $sessions ? ( $order_total / $sessions ) : 0;
        $cart_rate     = $views_total ? ( $cart_total / $views_total ) : 0;
        $repeat_rate   = $order_total ? ( $repeat / $order_total ) : 0;
        $avg_order     = isset( $orders['revenue']['average'] ) ? (float) $orders['revenue']['average'] : ( $order_total ? ( $revenue / $order_total ) : 0 );

        return array(
            'conversion_rate'   => round( $conversion * 100, 2 ),
            'cart_rate'         => round( $cart_rate * 100, 2 ),
            'repeat_rate'       => round( $repeat_rate * 100, 2 ),
            'average_order'     => round( $avg_order, 2 ),
        );
    }

    /**
     * Check table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    protected function table_exists( $table ) {
        global $wpdb;
        if ( null !== $this->ux_table_exists && UX_Tracker::get_table_name() === $table ) {
            return $this->ux_table_exists;
        }
        $exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( UX_Tracker::get_table_name() === $table ) {
            $this->ux_table_exists = $exists;
        }

        return $exists;
    }

    /**
     * Format product data snapshot.
     *
     * @param int $product_id Product ID.
     * @return array
     */
    protected function format_product_snapshot( $product_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return array(
                'id'    => $product_id,
                'name'  => '',
                'price' => '',
                'image' => '',
                'permalink' => '',
            );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return array(
                'id'    => $product_id,
                'name'  => '',
                'price' => '',
                'image' => '',
                'permalink' => '',
            );
        }

        $image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';

        return array(
            'id'        => $product->get_id(),
            'name'      => $product->get_name(),
            'price'     => strip_tags( $product->get_price_html() ),
            'permalink' => $product->get_permalink(),
            'image'     => $image,
        );
    }

    /**
     * Translate WC status key.
     *
     * @param string $status Status key.
     * @return string
     */
    protected function translate_status_label( $status ) {
        $map = array(
            'wc-completed' => __( 'Tamamlanan', 'noasoft-ai-woocommerce' ),
            'wc-processing' => __( 'Hazırlanıyor', 'noasoft-ai-woocommerce' ),
            'wc-on-hold' => __( 'Beklemede', 'noasoft-ai-woocommerce' ),
            'wc-cancelled' => __( 'İptal', 'noasoft-ai-woocommerce' ),
            'wc-refunded' => __( 'İade', 'noasoft-ai-woocommerce' ),
            'wc-failed' => __( 'Başarısız', 'noasoft-ai-woocommerce' ),
            'wc-pending' => __( 'Ödeme Bekleniyor', 'noasoft-ai-woocommerce' ),
        );

        return isset( $map[ $status ] ) ? $map[ $status ] : $status;
    }
}
