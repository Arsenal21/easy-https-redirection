<?php

class EHSSL_Static_Resources_Scan_Update {

    public $batch_size = 100;

    public $scan_results = array();

    public $post_types;

    public $other_tables;

    public $flags;

    public function __construct() {
        add_action( 'wp_ajax_ehssl_static_resources_scan', array( $this, 'handle_static_resources_scan' ) );
        add_action( 'wp_ajax_ehssl_get_scanned_resources_table', array( $this, 'handle_get_static_resources_table' ) );
        add_action( 'wp_ajax_ehssl_load_static_resources_table_page', array( $this, 'handle_load_static_resources_table_page' ) );
        add_action( 'wp_ajax_ehssl_update_http_urls', array( $this, 'handle_update_http_urls' ) );
    }

    public function handle_static_resources_scan() {
        if ( ! check_ajax_referer( 'ehssl_static_resources_scan_form_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                    array(
                            'message' => __( 'Nonce verification failed!', 'https-redirection' ),
                    )
            );
        }

        $post_types       = isset( $_POST['ehssl_post_types'] ) ? $_POST['ehssl_post_types'] : array();
        $this->post_types = $post_types;

        $other_tables       = isset( $_POST['ehssl_other_tables'] ) ? $_POST['ehssl_other_tables'] : array();
        $this->other_tables = $other_tables;

        $flags       = isset( $_POST['ehssl_additional_flags'] ) ? $_POST['ehssl_additional_flags'] : array();
        $this->flags = $flags;

        $total = isset( $_POST['total'] ) ? json_decode( ( stripslashes( $_POST['total'] ) ), true ) : array();

        $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;

        // Check if this is the initial request.
        if ( $offset == 0 ) {
            // Clear old data.
            $this->clear_scan_results();

            // Get the scannable records count.
            $total = $this->count_scannable_items();
        }

        try {
            if ( ! empty( $post_types ) ) {
                $this->scan_post_types( $offset );
            }

            if ( ! empty( $other_tables ) && in_array( 'wp_options_table', $other_tables ) ) {
                $this->scan_wp_options( $offset );
            }

            // Debug purpose only. Uncomment to inspect found matches.
            //EHSSL_Logger::log( $this->scan_results );

            if ( ! empty( $this->scan_results ) ) {
                $this->save_scan_result();
            }

            $next_offset = $offset + $this->batch_size;

            $response = array(
                    'message'     => __( 'URLs scanned successfully!', 'https-redirection' ),
                    'completed'   => $next_offset >= $total['ehssl_post_types'] && $next_offset >= $total['ehssl_other_tables'],
                    'processed'   => $next_offset,
                    'total'       => $total,
                    'next_offset' => $next_offset,
            );

            wp_send_json_success( $response );

        } catch ( \Exception $e ) {
            EHSSL_Logger::log( $e->getMessage(), 4 );
            wp_send_json_error( array(
                    'message' => $e->getMessage(),
            ) );
        }
    }

    public function count_scannable_items() {
        global $wpdb;

        $result = array();

        try {
            $post_types = isset( $_POST['ehssl_post_types'] ) ? $_POST['ehssl_post_types'] : array();
            if ( ! empty( $post_types ) ) {
                $post_types_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
                $query                   = $wpdb->prepare(
                        'SELECT COUNT(*) FROM ' . $wpdb->posts . ' WHERE post_type IN (' . $post_types_placeholders . ')',
                        array( ...$post_types ) );

                $count = $wpdb->get_var( $query );
                if ( ! empty( $count ) ) {
                    $result['ehssl_post_types'] = (int) $count;
                }
            }


            $other_tables = isset( $_POST['ehssl_other_tables'] ) ? $_POST['ehssl_other_tables'] : array();
            if ( ! empty( $other_tables ) ) {
                $query = $wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->options . " WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s ",
                        array( '_transient_%', '_site_transient_%' ),
                );
                $count = $wpdb->get_var( $query );
                if ( ! empty( $count ) ) {
                    $result['ehssl_other_tables'] = (int) $count;
                }
            }

        } catch ( \Exception $e ) {
            EHSSL_Logger::log( $e->getMessage(), 4);
        }

        return $result;
    }

    public function handle_get_static_resources_table() {
        try {
            self::render_http_scan_result_table();
            wp_die();
        } catch ( Exception $e ) {
            wp_die( $e->getMessage() );
        }
    }

    public function scan_post_types( $offset = 0 ) {
        global $wpdb;

        $post_types = $this->post_types;
        $flags      = $this->flags;
        $limit      = $this->batch_size;

        $post_types_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
        $query                   = $wpdb->prepare( 'SELECT ID, post_content, post_excerpt 
                                        FROM ' . $wpdb->posts . ' 
                                        WHERE post_type IN (' . $post_types_placeholders . ') 
                                        LIMIT %d OFFSET %d',
                array( ...$post_types, $limit, $offset ) );

        $posts = $wpdb->get_results( $query );

        $post_table_columns = array( 'post_content', 'post_excerpt' );

        foreach ( $posts as $post ) {
            $matches = array();

            foreach ( $post_table_columns as $p_col ) {
                $content = $post->$p_col;
                $this->find_matches_recursively( $content, $matches[$p_col] );
            }

            $cols_map = array();

            foreach ( $matches as $column => $url_matches ) {
                if ( ! empty( $url_matches[0] ) ) {
                    $cols_map[ $column ] = array_unique( $url_matches[0] );
                }
            }

            $found_post_urls = array();
            if ( ! empty( $cols_map ) ) {
                $found_post_urls = array(
                        'source_table' => $wpdb->posts,
                        'source_uid'   => $post->ID,
                        'cols_map'     => $cols_map,
                        'meta_map'     => array(),
                );
            }

            if ( ! empty( $flags ) && in_array( 'include_post_meta', $flags ) ) {
                // Scan post meta
                $found_urls_in_meta = array();
                $all_post_meta      = get_post_meta( $post->ID );

                foreach ( $all_post_meta as $meta_key => $meta_values ) {
                    foreach ( $meta_values as $meta_value ) {
                        // Convert arrays/objects into searchable text
                        if ( is_array( $meta_value ) || is_object( $meta_value ) ) {
                            $meta_value = wp_json_encode( $meta_value );
                        }

                        if ( ! is_string( $meta_value ) ) {
                            continue;
                        }

                        $this->find_matches_recursively( $meta_value, $meta_matches );

                        if ( empty( $meta_matches[0] ) ) {
                            continue;
                        }

                        foreach ( array_unique( $meta_matches[0] ) as $url ) {
                            $found_urls_in_meta[] = array(
                                    'url'      => $url,
                                    'meta_key' => $meta_key,
                            );
                        }
                    }
                }

                if ( ! empty( $found_urls_in_meta ) ) {
                    if ( empty( $found_post_urls ) ) {
                        $found_post_urls = array(
                                'source_table' => $wpdb->posts,
                                'source_uid'   => $post->ID,
                                'cols_map'     => array(),
                                'meta_map'     => array(),
                        );
                    }

                    foreach ( $found_urls_in_meta as $item ) {
                        $found_post_urls['meta_map'][ $item['meta_key'] ][] = $item['url'];
                    }
                }
            }

            if ( ! empty( $found_post_urls ) ) {
                $this->scan_results[] = $found_post_urls;
            }
        }
    }

    public function scan_wp_options( $offset = 0 ) {
        global $wpdb;

        $limit = $this->batch_size;

        $query   = $wpdb->prepare( "SELECT option_name, option_value 
            FROM " . $wpdb->options . " 
            WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s 
            LIMIT %d OFFSET %d",
                array( '_transient_%', '_site_transient_%', $limit, $offset ),
        );
        $options = $wpdb->get_results( $query );

        $table_columns = array( 'option_value' );

        foreach ( $options as $option ) {
            $matches = array();

            foreach ( $table_columns as $col ) {
                $content = $option->$col;
                $this->find_matches( $content, $matches[$col] ); // TODO
            }

            $cols_map = array();

            foreach ( $matches as $column => $url_matches ) {
                if ( ! empty( $url_matches[0] ) ) {
                    $cols_map[ $column ] = array_unique( $url_matches[0] );
                }
            }

            if ( ! empty( $cols_map ) ) {
                $this->scan_results[] = array(
                        'source_table' => $wpdb->options,
                        'source_uid'   => $option->option_name,
                        'cols_map'     => $cols_map,
                        'meta_map'     => array(),
                );
            }
        }
    }

    public function find_matches( $haystack, &$result ) {
        preg_match_all(
                '#http://[^\s"\'<>{}|\\\\^`]+#i',
                $haystack,
                $result
        );
    }

    /**
     * Makes sure it properly scans serialized data.
     */
    public function find_matches_recursively( $haystack, &$result ) {
        $haystack = maybe_unserialize( $haystack );

        if ( is_array( $haystack ) ) {
            foreach ( $haystack as $key => $value ) {
                $haystack[ $key ] = $this->find_matches_recursively( $value, $result );
            }

            return $haystack;
        }

        if ( is_object( $haystack ) ) {
            foreach ( $haystack as $key => $value ) {
                $haystack->$key = $this->find_matches_recursively( $value, $result );
            }

            return $haystack;
        }

        if ( is_string( $haystack ) ) {
            $this->find_matches($haystack, $result);
        }

        return $haystack;
    }

    public function save_scan_result() {
        $new_results = $this->scan_results;

        global $wpdb;

        $placeholders = array();
        $values       = array();

        foreach ( $new_results as $row ) {
            $placeholders[] = '(%s,%s,%s,%s)';
            array_push(
                    $values,
                    $row['source_table'],
                    $row['source_uid'],
                    isset( $row['cols_map'] ) ? serialize( $row['cols_map'] ) : array(),
                    isset( $row['meta_map'] ) ? serialize( $row['meta_map'] ) : array(),
            );
        }

        $query = "INSERT INTO {$wpdb->prefix}ehssl_resource_scan_tbl (source_table, source_uid, cols_map, meta_map) VALUES " . implode( ',', $placeholders );

        $query = $wpdb->prepare( $query, $values );

        return $wpdb->query( $query );
    }

    public static function get_scan_results_count( $skip_fixed = false ) {
        global $wpdb;
        $query =  "SELECT COUNT(*) FROM {$wpdb->prefix}ehssl_resource_scan_tbl";

        if ( $skip_fixed ) {
            $query .= " WHERE fixed != %d";
            $query = $wpdb->prepare( $query, 1 );
        }

        $count = $wpdb->get_var( $query );

        return (int) $count;
    }

    public static function get_scan_results_chunk( $offset = 0, $limit = 10, $where = array(), $skip_fixed = false ) {
        global $wpdb;

        $sql          = "SELECT * FROM {$wpdb->prefix}ehssl_resource_scan_tbl";
        $where_parts  = array();
        $prepare_args = array();

        if ($skip_fixed) {
            $where_parts[] = "fixed != 1";
        }

        foreach ( $where as $column => $ids ) {

            $column = sanitize_key( $column );

            if ( empty( $ids ) || ! is_array( $ids ) ) {
                continue;
            }

            $ids = array_map( 'absint', $ids );
            $ids = array_filter( $ids );

            if ( empty( $ids ) ) {
                continue;
            }

            $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

            $where_parts[] = "`{$column}` IN ({$placeholders})";

            $prepare_args = array_merge(
                    $prepare_args,
                    $ids
            );
        }

        if ( ! empty( $where_parts ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where_parts );
        }

        $sql .= ' LIMIT %d OFFSET %d';

        $prepare_args[] = (int) $limit;
        $prepare_args[] = (int) $offset;

        $query = $wpdb->prepare( $sql, $prepare_args );

        return $wpdb->get_results( $query, ARRAY_A );
    }

    public function mark_items_fixed( &$batch ) {
        if (empty($batch) || !is_array($batch)) {
            return;
        }

        global $wpdb;

        $ids = array_column( $batch, 'id' );
        $ids = array_unique( $ids );

        if ( empty( $ids ) ) {
            return;
        }

        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $sql = "UPDATE {$wpdb->prefix}ehssl_resource_scan_tbl SET `fixed` = 1 WHERE `id` IN ({$placeholders})";

        $query = $wpdb->prepare( $sql, $ids );

        $wpdb->query( $query );
    }

    public function clear_scan_results() {
        global $wpdb;
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ehssl_resource_scan_tbl;" );

        return $wpdb->query( $query );
    }

    public function handle_load_static_resources_table_page() {
        self::render_http_scan_result_table();

        wp_die();
    }

    public static function render_http_scan_result_table() {
        $table = new EHSSL_Static_Resources_Scan_Result_Table();
        $table->prepare_items();
        ?>
        <div class="wrap" id="nhs-table-container">
            <form method="post" id="ehssl_static_resources_table_form">
                <?php $table->display(); ?>
                <input type="hidden" name="ehssl_update_all_http_urls_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ehssl_update_all_http_urls' ) ); ?>">
            </form>
        </div>
        <?php
    }

    public function handle_update_http_urls() {
        if ( ! check_ajax_referer( 'ehssl_update_all_http_urls', 'nonce', false ) ) {
            wp_send_json_error(
                    array(
                            'message' => __( 'Nonce verification failed!', 'https-redirection' ),
                    )
            );
        }

        $limit = $this->batch_size;
        $offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
        $selected_ids = isset( $_POST['selected_ids'] ) ? json_decode(stripslashes($_POST['selected_ids'])) : null;

        $total = 0;

        if ( is_null( $selected_ids ) ) {
            // Need to update all items.
            $total = self::get_scan_results_count();
        } else {
            // Need to update selected items.
            $total = count( $selected_ids );
        }

        if ( empty( $total ) ) {
            wp_send_json_success(
                array(
                    'completed' => true,
                    'processed' => 0,
                )
            );
        }

        $batch = self::get_scan_results_chunk( $offset, $limit, array( 'id' => $selected_ids ) );

        if ( empty( $batch ) ) {
            wp_send_json_success(
                array(
                    'completed' => true,
                    'processed' => 0,
                )
            );
        }

        try {
            $this->update_urls( $batch );

            $this->mark_items_fixed($batch);

            $next_offset = $offset + count( $batch );

            $response = array(
                'message'     => __( 'URLs update to https successfully.', 'https-redirection' ),
                'completed'   => $next_offset >= $total,
                'processed'   => $next_offset,
                'total'       => $total,
                'next_offset' => $next_offset,
            );

            wp_send_json_success($response);

        } catch ( \Exception $e ) {
            EHSSL_Logger::log( $e->getMessage(), 4 );
            wp_send_json_error( array(
                    'message' => $e->getMessage(),
            ) );
        }
    }

    public function update_urls( &$batch ) {
        global $wpdb;
        foreach ( $batch as $item ) {
            switch ( $item['source_table'] ) {
                case $wpdb->posts:
                    $this->update_urls_in_post_type_item( $item );
                    break;
                case $wpdb->options:
                    $this->update_urls_in_option_table( $item );
                    break;
            }
        }
    }

    public function update_urls_in_post_type_item( &$item ) {
        $cols_map = isset( $item['cols_map'] ) ? maybe_unserialize( $item['cols_map'] ) : array();
        $meta_map = isset( $item['meta_map'] ) ? maybe_unserialize( $item['meta_map'] ) : array();

        $post_id = (int) $item['source_uid'];

        if (! empty( $cols_map ) ) {

            global $wpdb;
            $query = $wpdb->prepare( "SELECT ID, post_content, post_excerpt FROM {$wpdb->posts} WHERE ID = %d", $post_id );
            $post  = $wpdb->get_row( $query );
            if ( empty( $post ) ) {
                return;
            }

            $update_columns = array();

            foreach ( $cols_map as $column => $urls ) {
                if ( ! property_exists( $post, $column ) || empty( $urls )  ) {
                    continue;
                }

                $value = $post->$column;

                // Check if the column data is serialized or not.
                $was_serialized = is_serialized( $value );

                $value = $this->replace_urls_recursively( $value, $urls );

                if ( $was_serialized ) {
                    $value = maybe_serialize( $value );
                }

                $update_columns[ $column ] = $value;
            }

            if ( ! empty( $update_columns ) ) {
                $wpdb->update(
                        $wpdb->posts,
                        $update_columns,
                        array(
                            'ID' => $post_id
                        )
                );

                clean_post_cache( $post_id );
            }
        }

        if ( ! empty( $meta_map ) ) {
            $meta_keys_to_update = array_keys( $meta_map );
            $all_meta            = get_post_meta( $post_id );
            foreach ( $meta_keys_to_update as $meta_key ) {
                $value = isset($all_meta[$meta_key][0]) ? $all_meta[$meta_key][0] : '';

                $value = $this->replace_urls_recursively( $value, $meta_map[ $meta_key ] );

                update_post_meta( $post_id, $meta_key, $value );
            }
        }

    }

    public function update_urls_in_option_table( &$item ) {
        $cols_map = isset( $item['cols_map'] ) ? maybe_unserialize( $item['cols_map'] ) : '';

        $option_name = isset( $item['source_uid'] ) ? sanitize_key( $item['source_uid'] ) : '';

        if ( empty( $cols_map ) || empty( $option_name ) ) {
            return;
        }

        foreach ( $cols_map as $urls ) {
            if ( empty( $urls ) ) {
                continue;
            }

            $option_value = get_option( $option_name, '' );

            $option_value = $this->replace_urls_recursively( $option_value, $urls );

            update_option( $option_name, $option_value );
        }

    }

    public function replace_with_https( $http_url, $subject ) {
        $https_url = preg_replace( '#^http://#i', 'https://', $http_url );

        return str_replace( $http_url, $https_url, $subject );
    }

    /**
     * Makes sure it properly updates serialized data.
     */
    public function replace_urls_recursively( $value_to_update, $urls ) {
        $value_to_update = maybe_unserialize( $value_to_update );

        if ( is_array( $value_to_update ) ) {
            foreach ( $value_to_update as $key => $value ) {
                $value_to_update[ $key ] = $this->replace_urls_recursively( $value, $urls );
            }

            return $value_to_update;
        }

        if ( is_object( $value_to_update ) ) {
            foreach ( $value_to_update as $key => $value ) {
                $value_to_update->$key = $this->replace_urls_recursively( $value, $urls );
            }

            return $value_to_update;
        }

        if ( is_string( $value_to_update ) ) {
            foreach ( $urls as $url ) {
                $value_to_update = $this->replace_with_https( $url, $value_to_update );
            }
        }

        return $value_to_update;
    }

}

new EHSSL_Static_Resources_Scan_Update();