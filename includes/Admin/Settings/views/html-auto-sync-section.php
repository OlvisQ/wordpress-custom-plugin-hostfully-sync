<div class="hfsync-box">
	<div class="hfsync-box-header"><?php _e( 'Auto Synchronization', 'hfsync' ); ?></div>
	<div class="hfsync-box-body">
		<p>
			<?php 
				if ( 'yes' === get_option( 'hfsync_auto_sync_enabled' ) ) {
					$scheduled = wp_next_scheduled( 'hfsync_auto_sync_cron' );
					printf(
						__( 'Auto sync enabled. Next process time is <b>%s</b> (in %s)', 'hfsync' ),
						wp_date( 'l H:i:s A', $scheduled ),
						human_time_diff( $scheduled, time() )
					);
				} else {
					_e( 'Auto sync is disabled. You can still import manually using the Synchronize Now panel.', 'hfsync' );
				}
			?>
		</p>
	</div>
</div>