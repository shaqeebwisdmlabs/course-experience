<?php
/**
 * Admin settings template
 *
 * @package Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'courseexp_settings' );
		do_settings_sections( 'course-experience' );
		?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="courseexp_moodle_url"><?php esc_html_e( 'Moodle URL', 'course-exp' ); ?></label>
				</th>
				<td>
					<input
						type="url"
						id="courseexp_moodle_url"
						name="courseexp_moodle_url"
						value="<?php echo esc_attr( get_option( 'courseexp_moodle_url' ) ); ?>"
						class="regular-text"
					>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="courseexp_api_token"><?php esc_html_e( 'API Token', 'course-exp' ); ?></label>
				</th>
				<td>
					<input
						type="password"
						id="courseexp_api_token"
						name="courseexp_api_token"
						value="<?php echo esc_attr( get_option( 'courseexp_api_token' ) ); ?>"
						class="regular-text"
					>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
