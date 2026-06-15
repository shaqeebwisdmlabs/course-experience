<?php
/**
 * Child Course Enrollment Handler
 *
 * @package EB_Course_Experience
 */

/**
 * Class CourseExp_Child_Course_Enrollment
 *
 * Handles enrollment of users in parent courses when purchasing child course products
 */
class CourseExp_Child_Course_Enrollment {

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function init(): void {
		// Handle enrollment when order is completed.
		// Note: Only using woocommerce_order_status_completed to prevent double enrollment.
		// woocommerce_checkout_order_processed fires during checkout but order may not be completed yet.
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_parent_course_enrollment' ), 10, 1 );

		// For WooCommerce Subscriptions.
		add_action( 'woocommerce_subscription_payment_complete', array( $this, 'handle_subscription_parent_enrollment' ), 10, 1 );

		// Log enrollment in order notes.
		add_action( 'courseexp_parent_courses_enrolled', array( $this, 'log_enrollment_to_order' ), 10, 3 );

		// MAIN FIX: Filter My Courses query to show only valid child courses from purchased products.
		add_action( 'pre_get_posts', array( $this, 'exclude_parent_courses_from_query' ), 999 );

		// BACKUP: Filter enrolled courses list (used by some EB templates).
		add_filter( 'eb_get_user_enrolled_courses', array( $this, 'filter_enrolled_courses' ), 10, 2 );

		// AJAX handler for cleanup.
		add_action( 'wp_ajax_courseexp_cleanup_enrollments', array( $this, 'ajax_cleanup_enrollments' ) );
	}

	/**
	 * AJAX handler to cleanup old enrollment data.
	 *
	 * @return void
	 */
	public function ajax_cleanup_enrollments(): void {
		check_ajax_referer( 'courseexp_cleanup', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$user_id = get_current_user_id();
		$this->cleanup_user_enrollments( $user_id );

		wp_send_json_success( 'Enrollments cleaned up' );
	}

	/**
	 * Cleanup user enrollment data - sync with current product settings.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function cleanup_user_enrollments( int $user_id ): void {

		// Get all orders for this user.
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'completed', 'processing' ),
				'limit'       => -1,
			)
		);

		$correct_parent_courses = array();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$is_child   = get_post_meta( $product_id, '_ce_is_child_course', true );

				if ( 'yes' === $is_child ) {
					// Get current parent courses for this product.
					$product_parent_courses = get_post_meta( $product_id, '_ce_parent_courses', true );
					if ( is_array( $product_parent_courses ) ) {
						foreach ( $product_parent_courses as $parent_course_id ) {
							if ( ! in_array( $parent_course_id, $correct_parent_courses, true ) ) {
								$correct_parent_courses[] = $parent_course_id;
							}
						}
					}
				}
			}
		}

		// Update user meta with correct parent courses.
		update_user_meta( $user_id, '_ce_parent_enrolled_courses', $correct_parent_courses );
	}

	/**
	 * Exclude parent courses from My Courses WP_Query.
	 *
	 * This filters the query BEFORE it runs to exclude parent courses.
	 *
	 * @param WP_Query $query Query object.
	 * @return void
	 */
	public function exclude_parent_courses_from_query( $query ): void {
		// Only affect frontend.
		if ( is_admin() ) {
			return;
		}

		// Only affect eb_course post type.
		if ( 'eb_course' !== $query->get( 'post_type' ) ) {
			return;
		}

		// Check if user is logged in.
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Get current post__in (enrolled courses).
		$post__in = $query->get( 'post__in' );
		if ( empty( $post__in ) || ! is_array( $post__in ) ) {
			return;
		}

		// Convert to integers for comparison.
		$post__in = array_map( 'intval', $post__in );

		// Get all products purchased by user.
		$purchased_product_ids = array();
		$orders                = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'completed', 'processing' ),
				'limit'       => -1,
			)
		);
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$purchased_product_ids[] = $item->get_product_id();
			}
		}
		$purchased_product_ids = array_unique( $purchased_product_ids );

		// Find parent courses to hide.
		$parent_courses_to_hide = array();
		$valid_child_courses    = array();

		foreach ( $purchased_product_ids as $product_id ) {
			// Check if this is a child course product.
			$is_child = get_post_meta( $product_id, '_ce_is_child_course', true );
			if ( 'yes' !== $is_child ) {
				continue;
			}

			// Get linked courses for this product (these are child courses).
			$eb_options     = get_post_meta( $product_id, 'product_options', true );
			$linked_courses = isset( $eb_options['moodle_post_course_id'] ) ? (array) $eb_options['moodle_post_course_id'] : array();
			$linked_courses = array_map( 'intval', $linked_courses );

			// Get parent courses for this product.
			$product_parent_courses = get_post_meta( $product_id, '_ce_parent_courses', true );
			if ( ! is_array( $product_parent_courses ) ) {
				$product_parent_courses = array();
			}

			// Mark linked courses as valid child courses.
			foreach ( $linked_courses as $course_id ) {
				if ( in_array( $course_id, $post__in, true ) ) {
					$valid_child_courses[] = $course_id;
				}
			}

			// Mark parent courses to hide.
			foreach ( $product_parent_courses as $parent_course_id ) {
				$parent_course_id = intval( $parent_course_id );
				if ( in_array( $parent_course_id, $post__in, true ) ) {
					$parent_courses_to_hide[] = $parent_course_id;
				}
			}
		}

		// Remove duplicates.
		$parent_courses_to_hide = array_unique( $parent_courses_to_hide );
		$valid_child_courses    = array_unique( $valid_child_courses );

		// FIX: Only apply filtering if user purchased child course products.
		// If no child course products purchased, show all enrolled courses (normal behavior).
		$has_child_course_products = false;
		foreach ( $purchased_product_ids as $product_id ) {
			$is_child = get_post_meta( $product_id, '_ce_is_child_course', true );
			if ( 'yes' === $is_child ) {
				$has_child_course_products = true;
				break;
			}
		}

		// If no child course products purchased, don't filter - show all enrolled courses.
		if ( ! $has_child_course_products ) {
			return;
		}

		// If child course products purchased, only show valid child courses.
		// If no valid child courses found, hide all (edge case).
		if ( empty( $valid_child_courses ) ) {
			$query->set( 'post__in', array( 0 ) );
			return;
		}

		// Only show valid child courses from purchased child course products.
		$courses_to_show = $valid_child_courses;

		// Save parent courses to user meta for other functions (CSS/JS hiding).
		update_user_meta( $user_id, '_ce_parent_enrolled_courses', $parent_courses_to_hide );

		// Save valid child courses to user meta for the_posts filter.
		update_user_meta( $user_id, '_ce_valid_child_courses', $valid_child_courses );

		// Only show valid child courses.
		$query->set( 'post__in', array_values( $courses_to_show ) );
	}

	/**
	 * Handle parent course enrollment when order is completed
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function handle_parent_course_enrollment( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Prevent duplicate enrollment runs for this order.
		$already_processed = $order->get_meta( '_ce_parent_enrollment_done' );
		if ( 'yes' === $already_processed ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product_id       = $item->get_product_id();
			$variation_id     = $item->get_variation_id();
			$enrolled_courses = array();

			// Check if variation has child course settings.
			if ( $variation_id ) {
				$is_child       = get_post_meta( $variation_id, '_ce_is_child_course', true );
				$parent_courses = get_post_meta( $variation_id, '_ce_parent_courses', true );

				// If variation not set as child, fallback to main product.
				if ( empty( $is_child ) || 'yes' !== $is_child ) {
					$is_child       = get_post_meta( $product_id, '_ce_is_child_course', true );
					$parent_courses = get_post_meta( $product_id, '_ce_parent_courses', true );
				}

				if ( 'yes' === $is_child && ! empty( $parent_courses ) ) {
					$enrolled_courses = $this->enroll_in_parent_courses( $user_id, $parent_courses, $order_id );
				}
			} else {
				// Check simple product.
				$is_child       = get_post_meta( $product_id, '_ce_is_child_course', true );
				$parent_courses = get_post_meta( $product_id, '_ce_parent_courses', true );

				// Fallback to product_options for backward compatibility.
				if ( empty( $is_child ) ) {
					$product_options = get_post_meta( $product_id, 'product_options', true );
					$is_child        = isset( $product_options['is_child_course'] ) ? $product_options['is_child_course'] : 'no';
					$parent_courses  = isset( $product_options['parent_courses'] ) ? $product_options['parent_courses'] : array();
				}

				if ( 'yes' === $is_child && ! empty( $parent_courses ) ) {
					$enrolled_courses = $this->enroll_in_parent_courses( $user_id, $parent_courses, $order_id );
				}
			}

			// Log enrollment.
			if ( ! empty( $enrolled_courses ) ) {
				do_action( 'courseexp_parent_courses_enrolled', $order_id, $user_id, $enrolled_courses );
			}
		}

		// Mark order as processed to prevent duplicate runs.
		$order->update_meta_data( '_ce_parent_enrollment_done', 'yes' );
		$order->save();
	}

	/**
	 * Handle enrollment for subscription payments
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 * @return void
	 */
	public function handle_subscription_parent_enrollment( $subscription ): void {
		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			return;
		}

		$parent_order = $subscription->get_parent();
		if ( ! $parent_order ) {
			return;
		}

		$this->handle_parent_course_enrollment( $parent_order->get_id() );
	}

	/**
	 * Enroll user in parent courses
	 *
	 * @param int   $user_id        User ID.
	 * @param array $parent_courses Array of course post IDs.
	 * @param int   $order_id       Order ID.
	 * @return array Enrolled course IDs
	 */
	private function enroll_in_parent_courses( int $user_id, array $parent_courses, int $order_id ): array {
		if ( empty( $parent_courses ) ) {
			return array();
		}

		$enrolled_courses = array();

		foreach ( $parent_courses as $course_post_id ) {
			$course_post_id = intval( $course_post_id );
			if ( $course_post_id <= 0 ) {
				continue;
			}

			// Get moodle course ID from post meta.
			$moodle_course_id = get_post_meta( $course_post_id, 'moodle_course_id', true );
			if ( ! $moodle_course_id ) {
				continue;
			}

			// Check if already enrolled.
			if ( $this->is_user_enrolled( $user_id, $course_post_id ) ) {
				$enrolled_courses[] = array(
					'course_id'   => $course_post_id,
					'course_name' => get_the_title( $course_post_id ),
					'status'      => 'already_enrolled',
				);
				continue;
			}

			// Attempt enrollment using Edwiser Bridge.
			$result = $this->enroll_user( $user_id, $course_post_id, $moodle_course_id, $order_id );

			if ( $result ) {
				$enrolled_courses[] = array(
					'course_id'   => $course_post_id,
					'course_name' => get_the_title( $course_post_id ),
					'status'      => 'enrolled',
				);
			} else {
				$enrolled_courses[] = array(
					'course_id'   => $course_post_id,
					'course_name' => get_the_title( $course_post_id ),
					'status'      => 'failed',
				);
			}
		}

		return $enrolled_courses;
	}

	/**
	 * Check if user is already enrolled in course
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 * @return bool
	 */
	private function is_user_enrolled( int $user_id, int $course_id ): bool {
		$enrolled_courses = get_user_meta( $user_id, 'eb_user_enrolled_courses', true );

		if ( ! is_array( $enrolled_courses ) ) {
			$enrolled_courses = array();
		}

		return in_array( $course_id, $enrolled_courses, true );
	}

	/**
	 * Enroll user in a course using Edwiser Bridge
	 *
	 * @param int    $user_id          User ID.
	 * @param int    $course_post_id   Course post ID.
	 * @param string $moodle_course_id Moodle course ID.
	 * @param int    $order_id         Order ID.
	 * @return bool
	 */
	private function enroll_user( int $user_id, int $course_post_id, string $moodle_course_id, int $order_id ): bool {
		// FIX: Use only specific course enrollment to avoid enrolling in ALL user courses.
		// The Bridge_Woocommerce_Order_Manager::enroll_user_in_courses() was enrolling
		// user in ALL courses from ALL their orders, not just the specified courses.

		// Method 1: Use Edwiser Bridge enrollment manager directly.
		if ( function_exists( '\app\wisdmlabs\edwiserBridge\edwiser_bridge_instance' ) ) {
			$eb = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();

			if ( method_exists( $eb, 'enrollment_manager' ) ) {
				$enrollment_manager = $eb->enrollment_manager();

				$args = array(
					'user_id'  => $user_id,
					'courses'  => array( $course_post_id ),
					'unenroll' => 0,
					'suspend'  => 0,
				);

				$result = $enrollment_manager->update_user_course_enrollment( $args );

				if ( $result ) {
					$this->add_enrolled_course_meta( $user_id, $course_post_id );
					return true;
				}
			}
		}

		// Method 2: Direct database enrollment as fallback.
		return $this->enroll_directly( $user_id, $course_post_id, $moodle_course_id, $order_id );
	}

	/**
	 * Enroll directly via database (fallback method)
	 *
	 * @param int    $user_id          User ID.
	 * @param int    $course_post_id   Course post ID.
	 * @param string $moodle_course_id Moodle course ID.
	 * @param int    $order_id         Order ID.
	 * @return bool
	 */
	private function enroll_directly( int $user_id, int $course_post_id, string $moodle_course_id, int $order_id ): bool {
		global $wpdb;

		// Insert into enrollment table.
		$table_name = $wpdb->prefix . 'eb_user_enrollments';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'          => $user_id,
				'course_id'        => $course_post_id,
				'moodle_course_id' => $moodle_course_id,
				'order_id'         => $order_id,
				'enrolled_date'    => current_time( 'mysql' ),
				'status'           => 'active',
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s' )
		);

		if ( $result ) {
			$this->add_enrolled_course_meta( $user_id, $course_post_id );
			return true;
		}

		return false;
	}

	/**
	 * Add enrolled course to user meta
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 * @return void
	 */
	private function add_enrolled_course_meta( int $user_id, int $course_id ): void {
		// Parent courses ko alag meta me add karo.
		// Taki My Courses page pe na dikhe.
		$parent_enrolled = get_user_meta( $user_id, '_ce_parent_enrolled_courses', true );
		if ( ! is_array( $parent_enrolled ) ) {
			$parent_enrolled = array();
		}

		if ( ! in_array( $course_id, $parent_enrolled, true ) ) {
			$parent_enrolled[] = $course_id;
			update_user_meta( $user_id, '_ce_parent_enrolled_courses', $parent_enrolled );
		}

		// Parent courses ko main enrolled courses me add mat karo.
		// Taki My Courses page pe na dikhe.
	}



	/**
	 * Log enrollment to order notes
	 *
	 * @param int   $order_id         Order ID.
	 * @param int   $user_id          User ID.
	 * @param array $enrolled_courses Enrolled courses data.
	 * @return void
	 */
	public function log_enrollment_to_order( int $order_id, int $user_id, array $enrolled_courses ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$note = __( 'Parent Course Enrollment (via Child Course):', 'eb-course-exp' ) . "\n";

		foreach ( $enrolled_courses as $course_data ) {
			$status_label = '';
			switch ( $course_data['status'] ) {
				case 'enrolled':
					$status_label = __( 'Enrolled', 'eb-course-exp' );
					break;
				case 'already_enrolled':
					$status_label = __( 'Already Enrolled', 'eb-course-exp' );
					break;
				case 'failed':
					$status_label = __( 'Failed', 'eb-course-exp' );
					break;
			}

			$note .= sprintf(
				"- %s: %s\n",
				$course_data['course_name'],
				$status_label
			);
		}

		$order->add_order_note( $note );
	}


	/**
	 * Filter enrolled courses to exclude parent courses.
	 *
	 * This is the PROPER fix - filters courses before WP_Query runs.
	 *
	 * @param array $courses Enrolled course IDs.
	 * @param int   $user_id User ID.
	 * @return array Filtered course IDs.
	 */
	public function filter_enrolled_courses( array $courses, int $user_id ): array {
		// Only filter if user has purchased child course products.
		// Check by looking for valid child courses in user meta.
		$valid_child_courses = get_user_meta( $user_id, '_ce_valid_child_courses', true );

		// If no valid child courses set, return all courses (normal behavior).
		if ( empty( $valid_child_courses ) || ! is_array( $valid_child_courses ) ) {
			return $courses;
		}

		// Only show valid child courses from purchased child course products.
		return array_values( array_intersect( $courses, $valid_child_courses ) );
	}
}
