<div class="hfsync-box">
	<div class="hfsync-box-header"><?php _e( 'Cache', 'hfsync' ); ?></div>
	<div class="hfsync-box-body">
		<p><?php esc_html_e( 'We store hostfully API data into cache. If you are facing
		diffucties getting the latest data, clearing cache might help.' ); ?></p>
		<a class="button button-secondary" href="<?php echo add_query_arg(
			array(
				'action' => 'clear_cache',
				'_wpnonce' => wp_create_nonce( 'hfsync_clear_cache' )
			)
		); ?>"><?php _e( 'Clear Cached Data', 'hfsync' ); ?></a>
	</div>
</div>