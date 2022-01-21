<div class="hfsync-box" id="hfsync-webhook-box">
	<div class="hfsync-box-header"><?php _e('Webhooks', 'hfsync' ); ?></div>
	<div class="hfsync-box-body">
		<p><?php _e( 'Use webhooks to update property asap they are changed on hostfully. 
		Dont change the following url unless required.
		Before changing your site domain, unregister previous webhooks.' ); ?></p>

		<p>
			<input type="url" class="widefat" id="webhook_callback_url" value="<?php echo rest_url( 'hfsync/v1/hostfully-event' ); ?>" /></p>
			<button class="button button-primary" id="hfsync-register-webhooks-btn"><?php _e('Register Webhooks'); ?></button>
			<button class="button button-danger" id="hfsync-delete-webhooks-btn"><?php _e('Delete Webhooks'); ?></button>
			<button class="button button-secondary" id="hfsync-check-webhooks-btn"><?php _e('Check Webhooks'); ?></button>
		</p>
		<p id="hfsync-webhook-message"></p>
	</div>
</div>