<form method="get" novalidate="" class="hfsync-filters">
	<?php 
		foreach ( $filter_fields as $filter_field ) {
			printf( '<div class="field-wrap field-%s">', sanitize_html_class( $filter_field ) );

			switch ( $filter_field ) {
				case 'checkin':
					printf( 
						'<input name="%1$s" id="%1$s" value="%2$s" type="text" class="uk-input" placeholder="%3$s" />',
						'checkin',
						$filter_params['checkin'],
						__( 'Check in', 'hfsync' )
					);
					break;

				case 'checkout':
					printf( 
						'<input name="%1$s" id="%1$s" value="%2$s" type="text" class="uk-input" placeholder="%3$s" />',
						'checkout',
						$filter_params['checkout'],
						__( 'Check out', 'hfsync' )
					);
					break;

				case 'guests':
					printf( 
						'<select name="%1$s" id="%1$s" class="uk-input uk-select">',
						'guests',
					);

					for ( $i = 1; $i < 9; $i ++ ) {
						printf( 
							'<option value="%d"%s>%s</option>',
							$i,
							! empty( $filter_params['guests'] ) && $i === intval( $filter_params['guests'] ) ? ' selected="selected"' : '',
							sprintf( _n( '%d Guest', '%s Guests', $i ), $i )
						);
					}

					echo '</select>';
					break;

				case 'location':
					printf( 
						'<select name="%1$s" id="%1$s" class="uk-input uk-select">
							<option value="">%2$s</option>',
						'location',
						__( '-- Property Location --' )
					);

					$terms = get_terms( array( 'taxonomy' => Hostfully_Sync\Config::PROPERTY_CATEGORY ) );
					foreach ( $terms as $term ) {
						printf( 
							'<option value="%s"%s>%s</option>',
							$term->slug,
							! empty( $filter_params['location'] ) && $term->slug === trim( $filter_params['location'] ) ? ' selected="selected"' : '',
							$term->name
						);
					}

					echo '</select>';
					break;

				case 'type':
					printf( 
						'<select name="%1$s" id="%1$s" class="uk-input uk-select">
							<option value="">%2$s</option>',
						'type',
						__( '-- Property Type --' )
					);

					$terms = get_terms( array( 'taxonomy' => Hostfully_Sync\Config::PROPERTY_TYPE ) );
					foreach ( $terms as $term ) {
						printf( 
							'<option value="%s"%s>%s</option>',
							$term->slug,
							! empty( $filter_params['type'] ) && $term->slug === trim( $filter_params['type'] ) ? ' selected="selected"' : '',
							$term->name
						);
					}

					echo '</select>';
					break;

				default:
					do_action( 'hfsync_filter_' . $filter_field, $filter_params );
					break;
			}

			echo '</div>';
		}
	?>
	<div class="field-wrap field-submit">
		<button type="submit" class="uk-button uk-button-default uk-width-1-1"><?php _e( 'Search', 'hfsync' ); ?></button>
	</div>
</form>

