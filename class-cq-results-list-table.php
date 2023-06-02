<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class CQ_Results_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => __('Result', 'cq'),
            'plural' => __('Results', 'cq'),
            'ajax' => false,
        ));
    }

    public function get_columns() {
        return array(
            'id' => __('ID', 'cq'),
            'hash' => __('Hash', 'cq'),
            'total_score' => __('Total Score %', 'cq'),
            'raw_score' => __('Raw Score', 'cq'),
            'max_score_possible' => __('Max Score Possible', 'cq'),
            'recommendations' => __('Recommendations', 'cq'),
            'email_share_count' => __('Email Share Count', 'cq'),
            'created_at' => __('Date', 'cq'),
        );
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();

        $this->_column_headers = array($columns, $hidden, $sortable);

        global $wpdb;
        $table_name = $wpdb->prefix . 'cq_results';
        $query = "SELECT * FROM $table_name order by ID desc";
        $this->items = $wpdb->get_results($query, ARRAY_A);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'hash':
            case 'max_score_possible':
            case 'raw_score':
            case 'email_share_count':
            case 'created_at':
                return $item[$column_name];
            case 'total_score':
                return intval($item[$column_name]) . '%';
            case 'recommendations':
                $recommendations = maybe_unserialize($item[$column_name]);
                if (is_array($recommendations)) {
                    return implode(', ', $recommendations);
                } else {
                    return 'Error: Invalid recommendations data.';
                }
            default:
                return print_r($item, true);
        }
    }
}
