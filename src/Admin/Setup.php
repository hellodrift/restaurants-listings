<?php

namespace Listings\Restaurants\Admin;

class Setup {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'redirect' ) );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_dashboard_page( __( 'Setup', 'listings-jobs' ), __( 'Setup', 'listings-jobs' ), 'manage_options', 'listings-jobs-setup', array( $this, 'output' ) );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'listings-jobs-setup' );
	}

	/**
	 * Sends user to the setup page on first activation
	 */
	public function redirect() {
		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_listings_restaurants_activation_redirect' ) ) {
			return;
	    }

	    if ( ! current_user_can( 'manage_options' ) ) {
	    	return;
	    }

		// Delete the redirect transient
		delete_transient( '_listings_restaurants_activation_redirect' );

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'listings-jobs.php' ) ) ) {
			return;
		}

		wp_redirect( admin_url( 'index.php?page=listings-jobs-setup' ) );
		exit;
	}

	/**
	 * Create a page.
	 * @param  string $title
	 * @param  string $content
	 * @param  string $option
	 */
	public function create_page( $title, $content, $option ) {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => sanitize_title( $title ),
			'post_title'     => $title,
			'post_content'   => $content,
			'post_parent'    => 0,
			'comment_status' => 'closed'
		);
		$page_id = wp_insert_post( $page_data );

		if ( $option ) {
			update_option( $option, $page_id );
		}
	}

	/**
	 * Output addons page
	 */
	public function output() {
		$step = ! empty( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;

		if ( 3 === $step && ! empty( $_POST ) ) {
			$create_pages    = isset( $_POST['listings-jobs-create-page'] ) ? $_POST['listings-jobs-create-page'] : array();
			$page_titles     = $_POST['listings-jobs-page-title'];
			$pages_to_create = array(
				'submit_restaurant_form' => '[submit_restaurant_form]',
				'restaurant_dashboard'   => '[restaurant_dashboard]',
				'jobs'            => '[jobs]'
			);

			foreach ( $pages_to_create as $page => $content ) {
				if ( ! isset( $create_pages[ $page ] ) || empty( $page_titles[ $page ] ) ) {
					continue;
				}
				$this->create_page( sanitize_text_field( $page_titles[ $page ] ), $content, 'listings_' . $page . '_page_id' );
			}
		}
		?>
		<div class="wrap listings_restaurants listings_restaurants_addons_wrap">
			<h2><?php _e( 'Listings Jobs Setup', 'listings-jobs' ); ?></h2>

			<ul class="listings-jobs-setup-steps">
				<li class="<?php if ( $step === 1 ) echo 'listings-jobs-setup-active-step'; ?>"><?php _e( '1. Introduction', 'listings-jobs' ); ?></li>
				<li class="<?php if ( $step === 2 ) echo 'listings-jobs-setup-active-step'; ?>"><?php _e( '2. Page Setup', 'listings-jobs' ); ?></li>
				<li class="<?php if ( $step === 3 ) echo 'listings-jobs-setup-active-step'; ?>"><?php _e( '3. Done', 'listings-jobs' ); ?></li>
			</ul>

			<?php if ( 1 === $step ) : ?>

				<h3><?php _e( 'Setup Wizard Introduction', 'listings-jobs' ); ?></h3>

				<p><?php _e( 'Welcome and thanks for installing <em>Listings Jobs</em>!', 'listings-jobs' ); ?></p>
				<p><?php _e( 'This setup wizard will help you get started by creating the pages for job submission, job management, and listing your jobs.', 'listings-jobs' ); ?></p>
				<p><?php printf( __( 'You can also skip the wizard and setup the pages and shortcodes yourself manually, the process is still relatively simple as Listings is easy to use. Refer to the %sdocumentation%s for help.', 'listings-jobs' ), '<a href="https://wpjobmanager.com/documentation/">', '</a>' ); ?></p>

				<p class="submit">
					<a href="<?php echo esc_url( add_query_arg( 'step', 2 ) ); ?>" class="button button-primary"><?php _e( 'Continue to page setup', 'listings-jobs' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'skip-listings-jobs-setup', 1, admin_url( 'index.php?page=listings-jobs-setup&step=3' ) ) ); ?>" class="button"><?php _e( 'Skip setup. I will setup the plugin manually', 'listings-jobs' ); ?></a>
				</p>

			<?php endif; ?>
			<?php if ( 2 === $step ) : ?>

				<h3><?php _e( 'Page Setup', 'listings-jobs' ); ?></h3>

				<p><?php printf( __( '<em>Listings Jobs</em> includes %1$sshortcodes%2$s which can be used within your %3$spages%2$s to output content. These can be created for you below. For more information on the job shortcodes view the %4$sshortcode documentation%2$s.', 'listings-jobs' ), '<a href="http://codex.wordpress.org/Shortcode" title="What is a shortcode?" target="_blank" class="help-page-link">', '</a>', '<a href="http://codex.wordpress.org/Pages" target="_blank" class="help-page-link">', '<a href="https://wpjobmanager.com/document/shortcode-reference/" target="_blank" class="help-page-link">' ); ?></p>

				<form action="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" method="post">
					<table class="listings-jobs-shortcodes widefat">
						<thead>
							<tr>
								<th>&nbsp;</th>
								<th><?php _e( 'Page Title', 'listings-jobs' ); ?></th>
								<th><?php _e( 'Page Description', 'listings-jobs' ); ?></th>
								<th><?php _e( 'Content Shortcode', 'listings-jobs' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><input type="checkbox" checked="checked" name="listings-jobs-create-page[submit_restaurant_form]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Post a Job', 'Default page title (wizard)', 'listings-jobs' ) ); ?>" name="listings-jobs-page-title[submit_restaurant_form]" /></td>
								<td>
									<p><?php _e( 'This page allows employers to post jobs to your website from the front-end.', 'listings-jobs' ); ?></p>

									<p><?php _e( 'If you do not want to accept submissions from users in this way (for example you just want to post jobs from the admin dashboard) you can skip creating this page.', 'listings-jobs' ); ?></p>
								</td>
								<td><code>[submit_restaurant_form]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="listings-jobs-create-page[restaurant_dashboard]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Job Dashboard', 'Default page title (wizard)', 'listings-jobs' ) ); ?>" name="listings-jobs-page-title[restaurant_dashboard]" /></td>
								<td>
									<p><?php _e( 'This page allows employers to manage and edit their own jobs from the front-end.', 'listings-jobs' ); ?></p>

									<p><?php _e( 'If you plan on managing all listings from the admin dashboard you can skip creating this page.', 'listings-jobs' ); ?></p>
								</td>
								<td><code>[restaurant_dashboard]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="listings-jobs-create-page[jobs]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Jobs', 'Default page title (wizard)', 'listings-jobs' ) ); ?>" name="listings-jobs-page-title[jobs]" /></td>
								<td><?php _e( 'This page allows users to browse, search, and filter job listings on the front-end of your site.', 'listings-jobs' ); ?></td>
								<td><code>[jobs]</code></td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="4">
									<input type="submit" class="button button-primary" value="Create selected pages" />
									<a href="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" class="button"><?php _e( 'Skip this step', 'listings-jobs' ); ?></a>
								</th>
							</tr>
						</tfoot>
					</table>
				</form>

			<?php endif; ?>
			<?php if ( 3 === $step ) : ?>

				<h3><?php _e( 'All Done!', 'listings-jobs' ); ?></h3>

				<p><?php _e( 'Looks like you\'re all set to start using the plugin. In case you\'re wondering where to go next:', 'listings-jobs' ); ?></p>

				<ul class="listings-jobs-next-steps">
					<li><a href="<?php echo admin_url( 'admin.php?page=listings-settings' ); ?>"><?php _e( 'Tweak the plugin settings', 'listings-jobs' ); ?></a></li>
					<li><a href="<?php echo admin_url( 'post-new.php?post_type=listing' ); ?>"><?php _e( 'Add a job via the back-end', 'listings-jobs' ); ?></a></li>

					<?php if ( $permalink = listings_get_permalink( 'submit_restaurant_form' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'Add a job via the front-end', 'listings-jobs' ); ?></a></li>
					<?php else : ?>
						<li><a href="https://wpjobmanager.com/document/the-job-submission-form/"><?php _e( 'Find out more about the front-end job submission form', 'listings-jobs' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = listings_get_permalink( 'jobs' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View submitted job listings-jobs', 'listings-jobs' ); ?></a></li>
					<?php else : ?>
						<li><a href="https://wpjobmanager.com/document/shortcode-reference/#section-1"><?php _e( 'Add the [jobs] shortcode to a page to list jobs', 'listings-jobs' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = listings_get_permalink( 'restaurant_dashboard' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View the job dashboard', 'listings-jobs' ); ?></a></li>
					<?php else : ?>
						<li><a href="https://wpjobmanager.com/document/the-job-dashboard/"><?php _e( 'Find out more about the front-end job dashboard', 'listings-jobs' ); ?></a></li>
					<?php endif; ?>
				</ul>

				<p><?php printf( __( 'And don\'t forget, if you need any more help using <em>Listings</em> you can consult the %1$sdocumentation%2$s or %3$spost on the forums%2$s!', 'listings-jobs' ), '<a href="https://wpjobmanager.com/documentation/">', '</a>', '<a href="https://wordpress.org/support/plugin/wp-job-manager">' ); ?></p>

				<div class="listings-jobs-support-the-plugin">
					<h3><?php _e( 'Support the Ongoing Development of this Plugin', 'listings-jobs' ); ?></h3>
					<p><?php _e( 'There are many ways to support open-source projects such as Listings, for example code contribution, translation, or even telling your friends how awesome the plugin (hopefully) is. Thanks in advance for your support - it is much appreciated!', 'listings-jobs' ); ?></p>
					<ul>
						<li class="icon-review"><a href="https://wordpress.org/support/view/plugin-reviews/listings-jobs#postform"><?php _e( 'Leave a positive review', 'listings-jobs' ); ?></a></li>
						<li class="icon-localization"><a href="https://www.transifex.com/projects/p/listings-jobs/"><?php _e( 'Contribute a localization', 'listings-jobs' ); ?></a></li>
						<li class="icon-code"><a href="https://github.com/TheLookandFeel/listings-jobs"><?php _e( 'Contribute code or report a bug', 'listings-jobs' ); ?></a></li>
						<li class="icon-forum"><a href="https://wordpress.org/support/plugin/listings-jobs"><?php _e( 'Help other users on the forums', 'listings-jobs' ); ?></a></li>
					</ul>
				</div>

			<?php endif; ?>
		</div>
		<?php
	}
}