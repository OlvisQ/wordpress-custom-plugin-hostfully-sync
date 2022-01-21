<?php
	$url = get_permalink();
	if ( $is_date_filtered ) {
		foreach ( array( 'checkin', 'checkout', 'guests' ) as $param ) {
			if ( ! empty( $filter_params[ $param ] ) ) {
				$url = add_query_arg( $param, $filter_params[ $param ], $url );
			}
		}
	}
?>
<div>
	<a class="el-item uk-card uk-card-hover uk-card-small uk-link-toggle uk-display-block" href="<?php echo esc_url( $url ); ?>">
		<div class="uk-card-media-top">
			<?php the_post_thumbnail( 'hfsync-thumbnail', array( 'class' => 'el-image' ) ); ?>
		</div>
		<div class="uk-card-body uk-margin-remove-first-child">
			<div class="el-title uk-width-expand uk-text-lead uk-margin-small-top uk-margin-remove-bottom">						
				<?php the_title(); ?>
			</div>
			<div class="el-content uk-panel uk-margin-small-top">

				<?php if ( $is_date_filtered ) : ?>
					<span class="uk-text-bold uk-text-large">
						<?php printf( '%s / night', hfsync_get_property_base_rate() ); ?>
					</span>

					&middot; 

					<?php
						printf( 
							'%s total', 
							hfsync_get_property_stay_expense( 
								$filter_params['checkin'],
								$filter_params['checkout'],
								$filter_params['guests']
							)
						);
					?>
					<div class="uk-text uk-text-small"><?php _e( 'Total includes fees & taxes' ); ?></div>

				<?php elseif ( $show_base_price ) : ?>
					<span class="uk-text-bold uk-text-large">
						<?php printf( '%s / night', hfsync_get_property_base_rate() ); ?>
					</span>
				<?php endif; ?>
			</div>
			<div class="uk-margin-top">
				<div class="uk-position-relative">
					<ul class="uk-child-width-1-1 uk-child-width-1-3@s uk-child-width-1-3@m uk-child-width-1-3@l uk-grid-column-small uk-grid-row-small uk-grid-divider uk-flex-middle uk-text-center uk-grid" uk-grid="">
						<li class="fs-teaser-attrs-item uk-first-column">
							<div class="uk-panel">
								<img class="fs-teaser-attrs-image" alt="" data-src="<?php echo HFSYNC_URL . 'assets/img/icon-guests.svg'; ?>" uk-img>
								<div class="fs-teaser-attr-content uk-panel uk-text-small" style="margin-top:10px;"><?php echo hfsync_get_property_guests(); ?></div>
							</div>
						</li>
						<li class="fs-teaser-attrs-item">
							<div class="uk-panel">
								<img class="fs-teaser-attrs-image" alt="" data-src="<?php echo HFSYNC_URL . 'assets/img/icon-double-bed.svg'; ?>" uk-img>
								<div class="fs-teaser-attr-content uk-panel uk-text-small" style="margin-top:10px;"><?php echo hfsync_get_property_bedrooms(); ?></div>
							</div>
						</li>
						<li class="fs-teaser-attrs-item">
							<div class="uk-panel">
								<img class="fs-teaser-attrs-image" alt="" data-src="<?php echo HFSYNC_URL . 'assets/img/icon-bathtub.svg'; ?>" uk-img>
								<div class="fs-teaser-attr-content uk-panel uk-text-small" style="margin-top:10px;"><?php echo hfsync_get_property_bathrooms(); ?></div>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</a>
</div>

