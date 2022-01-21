<div class="hfsync-box" id="hfsync-sync-box">
	<div class="hfsync-box-header"><?php _e('Synchronize Now', 'hfsync' ); ?></div>
	<div class="hfsync-box-body">
		<p><?php _e( 'Manually synchronize properties from hostfully. Use this if 
		auto sync is disabled or you want to perform an update asap.' ); ?></p>

		<button class="button hfsync-sync-btn"><?php _e('Start Synchronization'); ?></button>
		<button style="display:none;" class="button hfsync-sync-cancel-btn"><?php _e('Cancel Synchronization'); ?></button>

		<div id="hfsync-sync-container" style="display:none;">
			<h3 id="hfsync-sync-message"><?php _e('Sync Log', 'hfsync'); ?></h3>
			<ul id="hfsync-sync-logs"><li><?php _e('Logs will appear here.'); ?></li></ul>
		</div>
	</div>
</div>