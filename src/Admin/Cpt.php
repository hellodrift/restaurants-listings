<?php

namespace Listings\Restaurants\Admin;

use Listings\CategoryWalker;

class Cpt {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		add_filter( 'manage_edit-restaurant_listing_columns', array( $this, 'columns' ) );
		add_action( 'manage_restaurant_listing_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-restaurant_listing_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'request', array( $this, 'sort_columns' ) );
		add_action( 'parse_query', array( $this, 'search_meta' ) );
		add_filter( 'get_search_query', array( $this, 'search_meta_label' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'add_bulk_actions' ) );
		add_action( 'load-edit.php', array( $this, 'do_bulk_actions' ) );
		add_action( 'admin_init', array( $this, 'approve_restaurant' ) );
		add_action( 'admin_notices', array( $this, 'approved_notice' ) );
		add_action( 'admin_notices', array( $this, 'expired_notice' ) );

		if ( get_option( 'listings_restaurants_enable_categories' ) ) {
			add_action( "restrict_manage_posts", array( $this, "restaurants_by_category" ) );
		}

		foreach ( array( 'post', 'post-new' ) as $hook ) {
			add_action( "admin_footer-{$hook}.php", array( $this,'extend_submitdiv_post_status' ) );
		}
	}

	/**
	 * Edit bulk actions
	 */
	public function add_bulk_actions() {
		global $post_type, $wp_post_types;;

		if ( $post_type == 'restaurant_listing' ) {
			?>
			<script type="text/javascript">
		      jQuery(document).ready(function() {
		        jQuery('<option>').val('approve_restaurants').text('<?php printf( __( 'Approve %s', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->name ); ?>').appendTo("select[name='action']");
		        jQuery('<option>').val('approve_restaurants').text('<?php printf( __( 'Approve %s', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->name ); ?>').appendTo("select[name='action2']");

		        jQuery('<option>').val('expire_restaurants').text('<?php printf( __( 'Expire %s', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->name ); ?>').appendTo("select[name='action']");
		        jQuery('<option>').val('expire_restaurants').text('<?php printf( __( 'Expire %s', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->name ); ?>').appendTo("select[name='action2']");
		      });
		    </script>
		    <?php
		}
	}

	/**
	 * Do custom bulk actions
	 */
	public function do_bulk_actions() {
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();

		switch( $action ) {
			case 'approve_restaurants' :
				check_admin_referer( 'bulk-posts' );

				$post_ids      = array_map( 'absint', array_filter( (array) $_GET['post'] ) );
				$approved_restaurants = array();

				if ( ! empty( $post_ids ) )
					foreach( $post_ids as $post_id ) {
						$restaurant_data = array(
							'ID'          => $post_id,
							'post_status' => 'publish'
						);
						if ( in_array( get_post_status( $post_id ), array( 'pending', 'pending_payment' ) ) && current_user_can( 'publish_post', $post_id ) && wp_update_post( $restaurant_data ) ) {
							$approved_restaurants[] = $post_id;
						}
					}

				wp_redirect( add_query_arg( 'approved_restaurants', $approved_restaurants, remove_query_arg( array( 'approved_restaurants', 'expired_restaurants' ), admin_url( 'edit.php?post_type=restaurant_listing' ) ) ) );
				exit;
			break;
			case 'expire_restaurants' :
				check_admin_referer( 'bulk-posts' );

				$post_ids     = array_map( 'absint', array_filter( (array) $_GET['post'] ) );
				$expired_restaurants = array();

				if ( ! empty( $post_ids ) )
					foreach( $post_ids as $post_id ) {
						$restaurant_data = array(
							'ID'          => $post_id,
							'post_status' => 'expired'
						);
						if ( current_user_can( 'manage_restaurant_listings' ) && wp_update_post( $restaurant_data ) )
							$expired_restaurants[] = $post_id;
					}

				wp_redirect( add_query_arg( 'expired_restaurants', $expired_restaurants, remove_query_arg( array( 'approved_restaurants', 'expired_restaurants' ), admin_url( 'edit.php?post_type=restaurant_listing' ) ) ) );
				exit;
			break;
		}

		return;
	}

	/**
	 * Approve a single restaurant
	 */
	public function approve_restaurant() {
		if ( ! empty( $_GET['approve_restaurant'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'approve_restaurant' ) && current_user_can( 'publish_post', $_GET['approve_restaurant'] ) ) {
			$post_id = absint( $_GET['approve_restaurant'] );
			$restaurant_data = array(
				'ID'          => $post_id,
				'post_status' => 'publish'
			);
			wp_update_post( $restaurant_data );
			wp_redirect( remove_query_arg( 'approve_restaurant', add_query_arg( 'approved_restaurants', $post_id, admin_url( 'edit.php?post_type=restaurant_listing' ) ) ) );
			exit;
		}
	}

	/**
	 * Show a notice if we did a bulk action or approval
	 */
	public function approved_notice() {
		 global $post_type, $pagenow;

		if ( $pagenow == 'edit.php' && $post_type == 'restaurant_listing' && ! empty( $_REQUEST['approved_restaurants'] ) ) {
			$approved_restaurants = $_REQUEST['approved_restaurants'];
			if ( is_array( $approved_restaurants ) ) {
				$approved_restaurants = array_map( 'absint', $approved_restaurants );
				$titles        = array();
				foreach ( $approved_restaurants as $restaurant_id )
					$titles[] = get_the_title( $restaurant_id );
				echo '<div class="updated"><p>' . sprintf( __( '%s approved', 'restaurants-listings' ), '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';
			} else {
				echo '<div class="updated"><p>' . sprintf( __( '%s approved', 'restaurants-listings' ), '&quot;' . get_the_title( $approved_restaurants ) . '&quot;' ) . '</p></div>';
			}
		}
	}

	/**
	 * Show a notice if we did a bulk action or approval
	 */
	public function expired_notice() {
		 global $post_type, $pagenow;

		if ( $pagenow == 'edit.php' && $post_type == 'restaurant_listing' && ! empty( $_REQUEST['expired_restaurants'] ) ) {
			$expired_restaurants = $_REQUEST['expired_restaurants'];
			if ( is_array( $expired_restaurants ) ) {
				$expired_restaurants = array_map( 'absint', $expired_restaurants );
				$titles        = array();
				foreach ( $expired_restaurants as $restaurant_id )
					$titles[] = get_the_title( $restaurant_id );
				echo '<div class="updated"><p>' . sprintf( __( '%s expired', 'restaurants-listings' ), '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';
			} else {
				echo '<div class="updated"><p>' . sprintf( __( '%s expired', 'restaurants-listings' ), '&quot;' . get_the_title( $expired_restaurants ) . '&quot;' ) . '</p></div>';
			}
		}
	}

	/**
	 * Show category dropdown
	 */
	public function restaurants_by_category() {
		global $typenow, $wp_query;

	    if ( $typenow != 'restaurant_listing' || ! taxonomy_exists( 'restaurant_listing_category' ) ) {
	    	return;
	    }

		$r                 = array();
		$r['pad_counts']   = 1;
		$r['hierarchical'] = 1;
		$r['hide_empty']   = 0;
		$r['show_count']   = 1;
		$r['selected']     = ( isset( $wp_query->query['restaurant_listing_category'] ) ) ? $wp_query->query['restaurant_listing_category'] : '';
		$r['menu_order']   = false;
		$terms             = get_terms( 'restaurant_listing_category', $r );
		$walker            = new CategoryWalker();

		if ( ! $terms ) {
			return;
		}

		$output  = "<select name='restaurant_listing_category' id='dropdown_restaurant_listing_category'>";
		$output .= '<option value="" ' . selected( isset( $_GET['restaurant_listing_category'] ) ? $_GET['restaurant_listing_category'] : '', '', false ) . '>' . __( 'Select category', 'restaurants-listings' ) . '</option>';
		$output .= $walker->walk( $terms, 0, $r );
		$output .= "</select>";

		echo $output;
	}

	/**
	 * enter_title_here function.
	 *
	 * @access public
	 * @return void
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'restaurant_listing' )
			return __( 'Position', 'restaurants-listings' );
		return $text;
	}

	/**
	 * post_updated_messages function.
	 *
	 * @access public
	 * @param mixed $messages
	 * @return void
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID, $wp_post_types;

		$messages['restaurant_listing'] = array(
			0 => '',
			1 => sprintf( __( '%s updated. <a href="%s">View</a>', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'restaurants-listings' ),
			3 => __( 'Custom field deleted.', 'restaurants-listings' ),
			4 => sprintf( __( '%s updated.', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%s published. <a href="%s">View</a>', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
			7 => sprintf( __( '%s saved.', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name ),
			8 => sprintf( __( '%s submitted. <a target="_blank" href="%s">Preview</a>', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview</a>', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name,
			  date_i18n( __( 'M j, Y @ G:i', 'restaurants-listings' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( '%s draft updated. <a target="_blank" href="%s">Preview</a>', 'restaurants-listings' ), $wp_post_types['restaurant_listing']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * columns function.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['title'], $columns['date'], $columns['author'] );

		$columns["restaurant_listing_type"]     = __( "Type", 'restaurants-listings' );
		$columns["restaurant_position"]         = __( "Position", 'restaurants-listings' );
		$columns["restaurant_location"]         = __( "Location", 'restaurants-listings' );
		$columns['restaurant_status']           = '<span class="tips" data-tip="' . __( "Status", 'restaurants-listings' ) . '">' . __( "Status", 'restaurants-listings' ) . '</span>';
		$columns["restaurant_posted"]           = __( "Posted", 'restaurants-listings' );
		$columns["restaurant_expires"]          = __( "Expires", 'restaurants-listings' );
		$columns["restaurant_listing_category"] = __( "Categories", 'restaurants-listings' );
		$columns['featured_restaurant']         = '<span class="tips" data-tip="' . __( "Featured?", 'restaurants-listings' ) . '">' . __( "Featured?", 'restaurants-listings' ) . '</span>';
		$columns['filled']               = '<span class="tips" data-tip="' . __( "Filled?", 'restaurants-listings' ) . '">' . __( "Filled?", 'restaurants-listings' ) . '</span>';
		$columns['restaurant_actions']          = __( "Actions", 'restaurants-listings' );

		if ( ! get_option( 'listings_restaurants_enable_categories' ) ) {
			unset( $columns["restaurant_listing_category"] );
		}

		return $columns;
	}

	/**
	 * custom_columns function.
	 *
	 * @access public
	 * @param mixed $column
	 * @return void
	 */
	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case "restaurant_listing_type" :
				$type = listings_restaurants_get_the_restaurant_type( $post );
				if ( $type )
					echo '<span class="restaurant-type ' . $type->slug . '">' . $type->name . '</span>';
			break;
			case "restaurant_position" :
				echo '<div class="restaurant_position">';
				echo '<a href="' . admin_url('post.php?post=' . $post->ID . '&action=edit') . '" class="tips restaurant_title" data-tip="' . sprintf( __( 'ID: %d', 'restaurants-listings' ), $post->ID ) . '">' . $post->post_title . '</a>';

				echo '<div class="company">';

				if ( listings_restaurants_get_the_company_website() ) {
					listings_restaurants_the_company_name( '<span class="tips" data-tip="' . esc_attr( listings_restaurants_get_the_company_tagline() ) . '"><a href="' . esc_url( listings_restaurants_get_the_company_website() ) . '">', '</a></span>' );
				} else {
					listings_restaurants_the_company_name( '<span class="tips" data-tip="' . esc_attr( listings_restaurants_get_the_company_tagline() ) . '">', '</span>' );
				}

				echo '</div>';

				listings_restaurants_the_company_logo();
				echo '</div>';
			break;
			case "restaurant_location" :
				listings_restaurants_the_restaurant_location( $post );
			break;
			case "restaurant_listing_category" :
				if ( ! $terms = get_the_term_list( $post->ID, $column, '', ', ', '' ) ) echo '<span class="na">&ndash;</span>'; else echo $terms;
			break;
			case "filled" :
				if ( listings_restaurants_is_position_filled( $post ) ) echo '&#10004;'; else echo '&ndash;';
			break;
			case "featured_restaurant" :
				if ( listings_restaurants_is_position_featured( $post ) ) echo '&#10004;'; else echo '&ndash;';
			break;
			case "restaurant_posted" :
				echo '<strong>' . date_i18n( __( 'M j, Y', 'restaurants-listings' ), strtotime( $post->post_date ) ) . '</strong><span>';
				echo ( empty( $post->post_author ) ? __( 'by a guest', 'restaurants-listings' ) : sprintf( __( 'by %s', 'restaurants-listings' ), '<a href="' . esc_url( add_query_arg( 'author', $post->post_author ) ) . '">' . get_the_author() . '</a>' ) ) . '</span>';
			break;
			case "restaurant_expires" :
				if ( $post->_restaurant_expires )
					echo '<strong>' . date_i18n( __( 'M j, Y', 'restaurants-listings' ), strtotime( $post->_restaurant_expires ) ) . '</strong>';
				else
					echo '&ndash;';
			break;
			case "restaurant_status" :
				echo '<span data-tip="' . esc_attr( listings_restaurants_get_restaurant_status( $post ) ) . '" class="tips status-' . esc_attr( $post->post_status ) . '">' . listings_restaurants_get_restaurant_status( $post ) . '</span>';
			break;
			case "restaurant_actions" :
				echo '<div class="actions">';
				$admin_actions = apply_filters( 'post_row_actions', array(), $post );

				if ( in_array( $post->post_status, array( 'pending', 'pending_payment' ) ) && current_user_can ( 'publish_post', $post->ID ) ) {
					$admin_actions['approve']   = array(
						'action'  => 'approve',
						'name'    => __( 'Approve', 'restaurants-listings' ),
						'url'     =>  wp_nonce_url( add_query_arg( 'approve_restaurant', $post->ID ), 'approve_restaurant' )
					);
				}
				if ( $post->post_status !== 'trash' ) {
					if ( current_user_can( 'read_post', $post->ID ) ) {
						$admin_actions['view']   = array(
							'action'  => 'view',
							'name'    => __( 'View', 'restaurants-listings' ),
							'url'     => get_permalink( $post->ID )
						);
					}
					if ( current_user_can( 'edit_post', $post->ID ) ) {
						$admin_actions['edit']   = array(
							'action'  => 'edit',
							'name'    => __( 'Edit', 'restaurants-listings' ),
							'url'     => get_edit_post_link( $post->ID )
						);
					}
					if ( current_user_can( 'delete_post', $post->ID ) ) {
						$admin_actions['delete'] = array(
							'action'  => 'delete',
							'name'    => __( 'Delete', 'restaurants-listings' ),
							'url'     => get_delete_post_link( $post->ID )
						);
					}
				}

				$admin_actions = apply_filters( 'listings_restaurants_admin_actions', $admin_actions, $post );

				foreach ( $admin_actions as $action ) {
					if ( is_array( $action ) ) {
						printf( '<a class="button button-icon tips icon-%1$s" href="%2$s" data-tip="%3$s">%4$s</a>', $action['action'], esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_html( $action['name'] ) );
					} else {
						echo str_replace( 'class="', 'class="button ', $action );
					}
				}

				echo '</div>';

			break;
		}
	}

	/**
	 * sortable_columns function.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return void
	 */
	public function sortable_columns( $columns ) {
		$custom = array(
			'restaurant_posted'   => 'date',
			'restaurant_position' => 'title',
			'restaurant_location' => 'restaurant_location',
			'restaurant_expires'  => 'restaurant_expires'
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * sort_columns function.
	 *
	 * @access public
	 * @param mixed $vars
	 * @return void
	 */
	public function sort_columns( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'restaurant_expires' === $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_restaurant_expires',
					'orderby' 	=> 'meta_value'
				) );
			} elseif ( 'restaurant_location' === $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_restaurant_location',
					'orderby' 	=> 'meta_value'
				) );
			}
		}
		return $vars;
	}

	/**
	 * Search custom fields as well as content.
	 * @param \WP_Query $wp
	 */
	public function search_meta( $wp ) {
		/** @var $wpdb \wpdb */
		global $pagenow, $wpdb;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'restaurant_listing' !== $wp->query_vars['post_type'] ) {
			return;
		}

		$post_ids = array_unique( array_merge(
			$wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID
					FROM {$wpdb->posts} posts
					INNER JOIN {$wpdb->postmeta} p1 ON posts.ID = p1.post_id
					WHERE p1.meta_value LIKE '%%%s%%'
					OR posts.post_title LIKE '%%%s%%'
					OR posts.post_content LIKE '%%%s%%'
					AND posts.post_type = 'restaurant_listing'
					",
					esc_sql( $wp->query_vars['s'] ),
					esc_sql( $wp->query_vars['s'] ),
					esc_sql( $wp->query_vars['s'] )
				)
			),
			array( 0 )
		) );

		// Adjust the query vars
		unset( $wp->query_vars['s'] );
		$wp->query_vars['restaurant_listing_search'] = true;
		$wp->query_vars['post__in'] = $post_ids;
	}

	/**
	 * Change the label when searching meta.
	 * @param string $query
	 * @return string
	 */
	public function search_meta_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || $typenow !== 'restaurant_listing' || ! get_query_var( 'restaurant_listing_search' ) ) {
			return $query;
		}

		return wp_unslash( sanitize_text_field( $_GET['s'] ) );
	}

    /**
	 * Adds post status to the "submitdiv" Meta Box and post type WP List Table screens. Based on https://gist.github.com/franz-josef-kaiser/2930190
	 *
	 * @return void
	 */
	public function extend_submitdiv_post_status() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction
		if ( 'restaurant_listing' !== $post_type ) {
			return;
		}

		// Get all non-builtin post status and add them as <option>
		$options = $display = '';
		foreach ( listings_restaurants_get_listing_post_statuses() as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it
			$selected AND $display = $name;

			// Build the options
			$options .= "<option{$selected} value='{$status}'>{$name}</option>";
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( '<?php echo $display; ?>' );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( "<?php echo $options; ?>" );
			} );
		</script>
		<?php
	}
}