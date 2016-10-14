<div id="listings-restaurants-restaurant-dashboard">
	<p><?php _e( 'Your listings are shown in the table below.', 'restaurants-listings' ); ?></p>
	<table class="listings-restaurants-restaurants">
		<thead>
			<tr>
				<?php foreach ( $restaurant_dashboard_columns as $key => $column ) : ?>
					<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $restaurants ) : ?>
				<tr>
					<td colspan="6"><?php _e( 'You do not have any active listings.', 'restaurants-listings' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $restaurants as $restaurant ) : ?>
					<tr>
						<?php foreach ( $restaurant_dashboard_columns as $key => $column ) : ?>
							<td class="<?php echo esc_attr( $key ); ?>">
								<?php if ('restaurant_title' === $key ) : ?>
									<?php if ( $restaurant->post_status == 'publish' ) : ?>
										<a href="<?php echo get_permalink( $restaurant->ID ); ?>"><?php echo $restaurant->post_title; ?></a>
									<?php else : ?>
										<?php echo $restaurant->post_title; ?> <small>(<?php listings_restaurants_restaurant_status( $restaurant ); ?>)</small>
									<?php endif; ?>
									<ul class="restaurant-dashboard-actions">
										<?php
											$actions = array();

											switch ( $restaurant->post_status ) {
												case 'publish' :
													$actions['edit'] = array( 'label' => __( 'Edit', 'restaurants-listings' ), 'nonce' => false );

													if ( listings_restaurants_is_position_filled( $restaurant ) ) {
														$actions['mark_not_filled'] = array( 'label' => __( 'Mark not filled', 'restaurants-listings' ), 'nonce' => true );
													} else {
														$actions['mark_filled'] = array( 'label' => __( 'Mark filled', 'restaurants-listings' ), 'nonce' => true );
													}

													$actions['duplicate'] = array( 'label' => __( 'Duplicate', 'restaurants-listings' ), 'nonce' => true );
													break;
												case 'expired' :
													if ( listings_get_permalink( 'submit_restaurant_form' ) ) {
														$actions['relist'] = array( 'label' => __( 'Relist', 'restaurants-listings' ), 'nonce' => true );
													}
													break;
												case 'pending_payment' :
												case 'pending' :
													if ( listings_user_can_edit_pending_submissions() ) {
														$actions['edit'] = array( 'label' => __( 'Edit', 'restaurants-listings' ), 'nonce' => false );
													}
												break;
											}

											$actions['delete'] = array( 'label' => __( 'Delete', 'restaurants-listings' ), 'nonce' => true );
											$actions           = apply_filters( 'listings_restaurants_my_restaurant_actions', $actions, $restaurant );

											foreach ( $actions as $action => $value ) {
												$action_url = add_query_arg( array( 'action' => $action, 'restaurant_id' => $restaurant->ID ) );
												if ( $value['nonce'] ) {
													$action_url = wp_nonce_url( $action_url, 'listings_restaurants_my_restaurant_actions' );
												}
												echo '<li><a href="' . esc_url( $action_url ) . '" class="restaurant-dashboard-action-' . esc_attr( $action ) . '">' . esc_html( $value['label'] ) . '</a></li>';
											}
										?>
									</ul>
								<?php elseif ('date' === $key ) : ?>
									<?php echo date_i18n( get_option( 'date_format' ), strtotime( $restaurant->post_date ) ); ?>
								<?php elseif ('expires' === $key ) : ?>
									<?php echo $restaurant->_restaurant_expires ? date_i18n( get_option( 'date_format' ), strtotime( $restaurant->_restaurant_expires ) ) : '&ndash;'; ?>
								<?php elseif ('filled' === $key ) : ?>
									<?php echo listings_restaurants_is_position_filled( $restaurant ) ? '&#10004;' : '&ndash;'; ?>
								<?php else : ?>
									<?php do_action( 'listings_restaurants_restaurant_dashboard_column_' . $key, $restaurant ); ?>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php listings_get_template( 'restaurant-pagination.php', array( 'max_num_pages' => $max_num_pages ) ); ?>
</div>
