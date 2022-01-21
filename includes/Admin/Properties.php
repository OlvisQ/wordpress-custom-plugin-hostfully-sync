<?php
namespace Hostfully_Sync\Admin;

use Exception;
use Hostfully_Sync\Property;
use Hostfully_Sync\Config;

class Properties {
    function __construct() {
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
    }

    /**
     * Handle property page actions
     */
    public function handle_actions() {
		add_filter( 'manage_' . Config::PROPERTY_POST_TYPE . '_posts_columns', array( $this, 'property_columns' ) );
        add_action( 'manage_' . Config::PROPERTY_POST_TYPE . '_posts_custom_column', array( $this, 'property_column_values' ), 10, 2 );

		if ( isset( $_REQUEST['action'] ) && 'hfsync_synchronize_property' === $_REQUEST['action'] ) {
			try {
				$property = new Property( '', intval( $_REQUEST['property_id'] ) );
				$property->ignore_api_cached();
				$property->import_now();

			} catch ( Exception $e ) {}

			wp_redirect( 
				add_query_arg( 
					array( 
						'action' => false, 
						'property_id' => false, 
						'settings-updated' => 'property-synchronized'
					)
				)
			);
            exit;
        }
    }

    public function property_columns( $columns ) {
        unset( $columns['date'] );

		if ( isset( $columns['taxonomy-' . Config::PROPERTY_CATEGORY] ) ) {
			$columns['taxonomy-' . Config::PROPERTY_CATEGORY] = __( 'Categories', 'hfsync' );
		}

		if ( isset( $columns['taxonomy-' . Config::PROPERTY_TYPE] ) ) {
			$columns['taxonomy-' . Config::PROPERTY_TYPE] = __( 'Types', 'hfsync' );
		}

		if ( isset( $columns['taxonomy-' . Config::PROPERTY_POST_TYPE . '_flag'] ) ) {
			$columns['taxonomy-' . Config::PROPERTY_POST_TYPE . '_flag'] = 'Flags';
		}

		$columns['base_daily_rate'] = __( 'Daily Rate', 'hfsync' );
		$columns['hfsync_api'] = __( 'Hostfully', 'hfsync' );
        $columns['date'] = __( 'Date' );

        return $columns;
    }

	public function property_column_values( $column, $post_id ) {
       if ( 'base_daily_rate' === $column ) {
			$base_daily_rate = get_post_meta( $post_id, 'base_daily_rate', true );
			$currency_symbol = get_post_meta( $post_id, 'currency_symbol', true );
			if ( empty( $currency_symbol ) ) {
				$currency_symbol = '$';
			}

			if ( $base_daily_rate ) {
				printf( '%s%s', $currency_symbol, $base_daily_rate );
			}
		} elseif ( 'hfsync_api' === $column ) {
			if ( hfsync_property_uid( $post_id ) ) {
				echo '<span class="dashicons dashicons-yes-alt" style="color: green;"></span>';
				printf( 
                    '<br /><a href="%1$s">%2$s</a>',
                    esc_url( add_query_arg( array( 'action' => 'hfsync_synchronize_property', 'property_id' => $post_id ) ) ),
                    esc_html( __( 'Sync', 'hfsync' ) )
                );
			} else {
				echo '<span class="dashicons dashicons-dismiss" style="color: grey;"></span>';
			}
		}
    }
}