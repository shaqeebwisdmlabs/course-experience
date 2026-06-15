<?php
/**
 * Child Course Admin Functionality
 *
 * @package EB_Course_Experience
 */

/**
 * Class CourseExp_Child_Course_Admin
 *
 * Handles child course checkbox and parent course selector in WooCommerce product edit page
 */
class CourseExp_Child_Course_Admin {

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function init(): void {
		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add fields to WooCommerce Integration tab.
		add_action( 'wdm_display_fields', array( $this, 'render_child_course_checkbox' ), 10, 3 );
		add_action( 'wdm_display_fields', array( $this, 'render_parent_course_selector' ), 11, 3 );

		// Save fields - run after Edwiser Bridge (priority 15).
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ), 99 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_meta' ), 99, 2 );

		// AJAX search for courses.
		add_action( 'wp_ajax_ce_search_courses', array( $this, 'ajax_search_courses' ) );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		global $post;

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		// Enqueue WooCommerce select2.
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'select2' );

		// Enqueue CSS.
		wp_enqueue_style(
			'courseexp-child-course-admin-css',
			COURSEEXP_PLUGIN_URL . 'assets/css/admin.css',
			array( 'woocommerce_admin_styles' ),
			COURSEEXP_VERSION
		);

		// Enqueue JS.
		wp_enqueue_script(
			'courseexp-child-course-admin',
			COURSEEXP_PLUGIN_URL . 'assets/js/child-course-admin.js',
			array( 'jquery', 'wc-enhanced-select', 'select2' ),
			COURSEEXP_VERSION,
			true
		);
	}

	/**
	 * Render child course checkbox
	 *
	 * @param int    $product_id Product ID.
	 * @param string $post_type  Post type.
	 * @param int    $index      Variation index.
	 * @return void
	 */
	public function render_child_course_checkbox( int $product_id, string $post_type, int $index ): void {
		$meta_key = 'is_child_course';

		// Get saved value from custom meta key (not product_options).
		if ( 'product_variation' === $post_type ) {
			$value         = get_post_meta( $product_id, '_ce_is_child_course', true );
			$field_name    = 'ce_variation_is_child[' . $index . ']';
			$wrapper_class = 'ce-child-course-wrapper variation-field';
		} else {
			$value = get_post_meta( $product_id, '_ce_is_child_course', true );
			// Fallback to product_options for backward compatibility.
			if ( empty( $value ) ) {
				$product_options = get_post_meta( $product_id, 'product_options', true );
				$value           = isset( $product_options[ $meta_key ] ) ? $product_options[ $meta_key ] : 'no';
			}
			$field_name    = 'product_options[' . $meta_key . ']';
			$wrapper_class = 'ce-child-course-wrapper';
		}
		?>
		<p class="form-field <?php echo esc_attr( $wrapper_class ); ?>">
			<label for="<?php echo esc_attr( $meta_key . '_' . $product_id ); ?>">
				<?php esc_html_e( 'Child Course', 'eb-course-exp' ); ?>
			</label>
			<input type="checkbox"
					class="ce-is-child-course"
					name="<?php echo esc_attr( $field_name ); ?>"
					id="<?php echo esc_attr( $meta_key . '_' . $product_id ); ?>"
					value="yes"
					data-product-id="<?php echo esc_attr( $product_id ); ?>"
					data-post-type="<?php echo esc_attr( $post_type ); ?>"
					data-index="<?php echo esc_attr( $index ); ?>"
					<?php checked( $value, 'yes' ); ?> />
			<span class="description">
				<?php esc_html_e( 'Enable to select parent courses for enrollment', 'eb-course-exp' ); ?>
			</span>
		</p>
		<?php
	}

	/**
	 * Render parent course selector
	 *
	 * @param int    $product_id Product ID.
	 * @param string $post_type  Post type.
	 * @param int    $index      Variation index.
	 * @return void
	 */
	public function render_parent_course_selector( int $product_id, string $post_type, int $index ): void {
		$is_child_key       = 'is_child_course';
		$parent_courses_key = 'parent_courses';

		// Get saved values from custom meta keys.
		if ( 'product_variation' === $post_type ) {
			$is_child       = get_post_meta( $product_id, '_ce_is_child_course', true );
			$parent_courses = get_post_meta( $product_id, '_ce_parent_courses', true );
			if ( ! is_array( $parent_courses ) ) {
				$parent_courses = array();
			}
			$field_name = 'ce_variation_parent_courses[' . $index . '][]';
			$wrapper_id = 'ce-parent-courses-wrapper-variation-' . $index;
		} else {
			$is_child       = get_post_meta( $product_id, '_ce_is_child_course', true );
			$parent_courses = get_post_meta( $product_id, '_ce_parent_courses', true );
			// Fallback to product_options for backward compatibility.
			if ( empty( $is_child ) ) {
				$product_options = get_post_meta( $product_id, 'product_options', true );
				$is_child        = isset( $product_options[ $is_child_key ] ) ? $product_options[ $is_child_key ] : 'no';
				$parent_courses  = isset( $product_options[ $parent_courses_key ] ) ? $product_options[ $parent_courses_key ] : array();
			}
			if ( ! is_array( $parent_courses ) ) {
				$parent_courses = array();
			}
			$field_name = 'product_options[' . $parent_courses_key . '][]';
			$wrapper_id = 'ce-parent-courses-wrapper';
		}

		// Get all available courses.
		$courses = $this->get_available_courses();

		$display_style = ( 'yes' === $is_child ) ? 'block' : 'none';
		?>
		<div id="<?php echo esc_attr( $wrapper_id ); ?>" class="ce-parent-courses-wrapper" style="display: <?php echo esc_attr( $display_style ); ?>;">
			<p class="form-field">
				<label for="<?php echo esc_attr( $parent_courses_key . '_' . $product_id ); ?>">
					<?php esc_html_e( 'Parent Courses', 'eb-course-exp' ); ?>
				</label>
				<select name="<?php echo esc_attr( $field_name ); ?>"
						class="ce-parent-courses-select woo-moodle-post-course-id wc-enhanced-select"
						id="<?php echo esc_attr( $parent_courses_key . '_' . $product_id ); ?>"
						multiple="multiple"
						style="width: 50%;"
						data-placeholder="<?php esc_attr_e( 'Select any course', 'eb-course-exp' ); ?>"
						data-allow_clear="true"
						data-multiple="true"
						data-action="ce_search_courses">
					<?php foreach ( $courses as $course_id => $course_title ) : ?>
						<option value="<?php echo esc_attr( $course_id ); ?>"
								<?php selected( in_array( $course_id, $parent_courses, true ) ); ?>>
							<?php echo esc_html( $course_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php if ( function_exists( 'WC' ) ) : ?>
				<img class="help_tip"
					data-tip="<?php esc_attr_e( 'Select parent courses. User will be enrolled in all selected courses when purchasing this product.', 'eb-course-exp' ); ?>"
					src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png"
					height="16"
					width="16" />
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get available courses
	 *
	 * @return array
	 */
	private function get_available_courses(): array {
		global $wpdb;

		$courses = array();

		$query = $wpdb->prepare(
			"SELECT ID, post_title, post_status FROM {$wpdb->posts}
			 WHERE post_type = %s
			 AND (post_status = 'publish' OR post_status = 'draft')
			 ORDER BY post_title ASC",
			'eb_course'
		);

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $results ) ) {
			foreach ( $results as $course ) {
				$draft_label            = ( 'draft' === $course->post_status ) ? ' (Draft)' : '';
				$courses[ $course->ID ] = $course->post_title . $draft_label;
			}
		}

		return $courses;
	}

	/**
	 * Save product meta
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_product_meta( int $post_id ): void {
		// Only save for products.
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		// Don't save during autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		// Save to separate meta keys (not product_options to avoid conflict with Edwiser Bridge).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$is_child = isset( $_POST['product_options']['is_child_course'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_ce_is_child_course', $is_child );

		// Save parent_courses.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['product_options']['parent_courses'] ) && is_array( $_POST['product_options']['parent_courses'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$parent_courses = array_map( 'intval', $_POST['product_options']['parent_courses'] );
			update_post_meta( $post_id, '_ce_parent_courses', $parent_courses );
		} else {
			update_post_meta( $post_id, '_ce_parent_courses', array() );
		}
	}

	/**
	 * Save variation meta
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $i            Index.
	 * @return void
	 */
	public function save_variation_meta( int $variation_id, int $i ): void {
		// Save is_child_course to custom meta key.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$is_child = isset( $_POST['ce_variation_is_child'][ $i ] ) ? 'yes' : 'no';
		update_post_meta( $variation_id, '_ce_is_child_course', $is_child );

		// Save parent_courses if child course is enabled.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['ce_variation_parent_courses'][ $i ] ) && is_array( $_POST['ce_variation_parent_courses'][ $i ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$parent_courses = array_map( 'intval', $_POST['ce_variation_parent_courses'][ $i ] );
			update_post_meta( $variation_id, '_ce_parent_courses', $parent_courses );
		} else {
			update_post_meta( $variation_id, '_ce_parent_courses', array() );
		}
	}

	/**
	 * AJAX search courses
	 *
	 * @return void
	 */
	public function ajax_search_courses(): void {
		check_ajax_referer( 'search-products', 'security' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( -1 );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( strlen( $term ) < 3 ) {
			wp_die();
		}

		global $wpdb;

		$courses = array();

		$query = $wpdb->prepare(
			"SELECT ID, post_title, post_status
			 FROM {$wpdb->posts}
			 WHERE post_type = 'eb_course'
			 AND (post_status = 'publish' OR post_status = 'draft')
			 AND post_title LIKE %s
			 ORDER BY post_title ASC
			 LIMIT 50",
			'%' . $wpdb->esc_like( $term ) . '%'
		);

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $results ) ) {
			foreach ( $results as $course ) {
				$draft_label            = ( 'draft' === $course->post_status ) ? ' (Draft)' : '';
				$courses[ $course->ID ] = $course->post_title . $draft_label;
			}
		}

		wp_send_json( $courses );
	}
}
