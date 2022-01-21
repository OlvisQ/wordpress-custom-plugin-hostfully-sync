<div class="hfsync-box">
	<div class="hfsync-box-header"><?php _e( 'Reset', 'hfsync' ); ?></div>
	<div class="hfsync-box-body">
		<p><?php _e( 'Delete all imported properties & images from this site.', 'hfsync' ); ?></p>
		<a class="button button-secondary button-danger" href="<?php 
			echo add_query_arg(
				array(
					'action'   => 'delete_all',
					'_wpnonce' => wp_create_nonce( 'hfsync_delete_all' )
				)
			);
		?>"><?php _e( 'Delete All', 'hfsync' ); ?></a>
	</div>
</div>