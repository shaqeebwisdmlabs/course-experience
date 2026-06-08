<?php
/**
 * Activity Body Template Part
 *
 * Renders a single activity's intro description followed by its body, chosen by
 * the server-resolved rendermode:
 *   iframe / iframe-unsafe -> chromeless embed or new-tab launch
 *   file                   -> embedded PDF viewer or folder file list
 *   external               -> new-tab launch (no inline body)
 *   html                   -> native page HTML or book chapters
 *   inline                 -> description only
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$activity       = get_query_var( 'courseexp_activity' );
$activity       = is_array( $activity ) ? $activity : (array) $activity;
$cmid           = (int) get_query_var( 'courseexp_cmid' );
$moodle_user_id = (int) get_query_var( 'courseexp_moodle_user_id' );

if ( ! function_exists( 'courseexp_render_trusted_html' ) ) {
	/**
	 * Render Moodle rich text verbatim.
	 *
	 * Moodle course content is authored inside the trusted LMS, so it is rendered
	 * as-is — not run through wp_kses_post() — so embedded media (video players
	 * that rely on <script>) runs exactly as it does in Moodle.
	 *
	 * @param string $html Trusted HTML from the Moodle payload.
	 * @return void
	 */
	function courseexp_render_trusted_html( string $html ): void {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Moodle course content, rendered verbatim so script-based embeds run.
	}
}

if ( ! function_exists( 'courseexp_file_meta_parts' ) ) {
	/**
	 * Build the file metadata parts honouring the resource moduledata toggles.
	 *
	 * @param array $file      File block (filesize, mimetype, timemodified).
	 * @param bool  $show_size Whether to include the file size.
	 * @param bool  $show_type Whether to include the MIME type.
	 * @param bool  $show_date Whether to include the modified date.
	 * @return string[] Display-ready metadata strings.
	 */
	function courseexp_file_meta_parts( array $file, bool $show_size, bool $show_type, bool $show_date ): array {
		$parts = array();

		if ( $show_size && ! empty( $file['filesize'] ) ) {
			$parts[] = size_format( (int) $file['filesize'] );
		}
		if ( $show_type && ! empty( $file['mimetype'] ) ) {
			$parts[] = (string) $file['mimetype'];
		}
		if ( $show_date && ! empty( $file['timemodified'] ) ) {
			$parts[] = date_i18n( (string) get_option( 'date_format' ), (int) $file['timemodified'] );
		}

		return $parts;
	}
}

if ( ! function_exists( 'courseexp_file_url' ) ) {
	/**
	 * Resolve a file URL for the browser: append the EB token for Moodle-hosted
	 * files, but leave externally-hosted files (e.g. a url activity's link) untouched.
	 *
	 * @param array                $file       File block.
	 * @param CourseExp_API_Client $api_client API client for token handling.
	 * @return string
	 */
	function courseexp_file_url( array $file, CourseExp_API_Client $api_client ): string {
		$url = isset( $file['fileurl'] ) ? (string) $file['fileurl'] : '';

		if ( '' === $url || ! empty( $file['isexternalfile'] ) ) {
			return $url;
		}

		return $api_client->append_file_token( $url );
	}
}

if ( ! function_exists( 'courseexp_render_embed' ) ) {
	/**
	 * Render a chromeless iframe embed, with an optional new-tab fallback link.
	 *
	 * @param string $src          Iframe source URL.
	 * @param string $title        Accessible iframe title.
	 * @param string $fallback_url Optional URL for an "open in a new tab" link below
	 *                             the frame (used when embedding cross-origin sites
	 *                             that may refuse to be framed).
	 * @return void
	 */
	function courseexp_render_embed( string $src, string $title, string $fallback_url = '' ): void {
		if ( '' === $src ) {
			?>
			<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity could not be loaded.', 'eb-course-exp' ); ?></p></div>
			<?php
			return;
		}
		?>
		<div class="courseexp-activity-embed" id="courseexp-activity-embed">
			<div class="courseexp-activity-embed__loading" aria-hidden="true">
				<span class="courseexp-activity-embed__spinner"></span>
				<span class="courseexp-activity-embed__loading-text"><?php esc_html_e( 'Loading activity…', 'eb-course-exp' ); ?></span>
			</div>
			<iframe
				class="courseexp-activity-embed__frame"
				id="courseexp-activity-frame"
				src="<?php echo esc_url( $src ); ?>"
				title="<?php echo esc_attr( $title ); ?>"
				loading="lazy"
			></iframe>
		</div>
		<?php if ( '' !== $fallback_url ) : ?>
			<p class="courseexp-activity-embed__fallback">
				<a class="courseexp-activity-embed__fallback-link" href="<?php echo esc_url( $fallback_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in a new tab', 'eb-course-exp' ); ?></a>
			</p>
			<?php
		endif;
	}
}

if ( ! function_exists( 'courseexp_render_launch' ) ) {
	/**
	 * Render the prompt for an activity that opens in a new tab: a plain sentence
	 * with the activity name as the link (mirrors Moodle, no surrounding chrome).
	 *
	 * @param string $url  Destination URL.
	 * @param string $name Activity name used as the link text.
	 * @return void
	 */
	function courseexp_render_launch( string $url, string $name ): void {
		if ( '' === $url ) {
			?>
			<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity could not be loaded.', 'eb-course-exp' ); ?></p></div>
			<?php
			return;
		}

		$link = sprintf(
			'<a class="courseexp-activity-launch__link" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $url ),
			esc_html( '' !== trim( $name ) ? $name : __( 'this activity', 'eb-course-exp' ) )
		);
		?>
		<p class="courseexp-activity-launch">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: linked activity name. */
					__( 'Click on %s to open the resource.', 'eb-course-exp' ),
					$link
				),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
						'class'  => array(),
					),
				)
			);
			?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'courseexp_url_display' ) ) {
	/**
	 * Resolve a url activity's display mode from moduledata.
	 *
	 * Prefers the already-resolved value so an "automatic" setting is honoured
	 * without re-deriving it. Matches Moodle's RESOURCELIB_DISPLAY_* constants
	 * (1 embed, 5 open, 6 popup; 0 automatic).
	 *
	 * @param array $moduledata Decoded moduledata bag.
	 * @return int Display constant.
	 */
	function courseexp_url_display( array $moduledata ): int {
		if ( ! empty( $moduledata['displayresolved'] ) ) {
			return (int) $moduledata['displayresolved'];
		}

		return isset( $moduledata['display'] ) ? (int) $moduledata['display'] : 0;
	}
}

if ( empty( $activity ) ) {
	?>
	<div class="courseexp-activity-content">
		<div class="courseexp-sections__empty">
			<p><?php esc_html_e( 'Activity not found.', 'eb-course-exp' ); ?></p>
		</div>
	</div>
	<?php
	return;
}

$rendermode    = isset( $activity['rendermode'] ) ? (string) $activity['rendermode'] : '';
$available     = ! isset( $activity['available'] ) || (bool) $activity['available'];
$avail_info    = isset( $activity['availabilityinfo'] ) ? (string) $activity['availabilityinfo'] : '';
$description   = isset( $activity['description'] ) ? (string) $activity['description'] : '';
$embed_url     = isset( $activity['embedurl'] ) ? (string) $activity['embedurl'] : '';
$external_url  = isset( $activity['externalurl'] ) ? (string) $activity['externalurl'] : '';
$activity_name = isset( $activity['name'] ) ? (string) $activity['name'] : '';
$modname       = isset( $activity['modname'] ) ? (string) $activity['modname'] : '';
$has_desc      = '' !== trim( $description );

$moduledata = isset( $activity['moduledata'] ) ? $activity['moduledata'] : array();
if ( is_string( $moduledata ) ) {
	$moduledata = json_decode( $moduledata, true );
}
$moduledata = is_array( $moduledata ) ? $moduledata : array();

$embeds_own_intro = ( 'iframe' === $rendermode && 'url' !== $modname );
$show_intro       = $has_desc && ! $embeds_own_intro && ( ! isset( $moduledata['printintro'] ) || ! empty( $moduledata['printintro'] ) );
$show_file_size   = ! empty( $moduledata['showsize'] );
$show_file_type   = ! empty( $moduledata['showtype'] );
$show_file_date   = ! empty( $moduledata['showdate'] );
$book_numbering   = isset( $moduledata['numbering'] ) ? (int) $moduledata['numbering'] : 1;
?>

<div class="courseexp-activity-content" data-activity-id="<?php echo esc_attr( $cmid ); ?>" data-rendermode="<?php echo esc_attr( $rendermode ); ?>">
	<?php if ( ! $available ) : ?>
		<div class="courseexp-activity-locked">
			<span class="courseexp-activity-locked__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
			</span>
			<?php if ( '' !== trim( $avail_info ) ) : ?>
				<span class="courseexp-activity-locked__text"><?php echo wp_kses_post( $avail_info ); ?></span>
			<?php else : ?>
				<span class="courseexp-activity-locked__text"><?php esc_html_e( 'This activity is not available yet.', 'eb-course-exp' ); ?></span>
			<?php endif; ?>
		</div>
	<?php else : ?>

		<?php if ( $show_intro ) : ?>
		<div class="courseexp-activity-content__description">
			<?php courseexp_render_trusted_html( $description ); ?>
		</div>
	<?php endif; ?>

		<?php
		if ( 'url' === $modname ) {
			$url_target = '' !== $external_url ? $external_url : ( isset( $activity['url'] ) ? (string) $activity['url'] : '' );

			if ( 1 === courseexp_url_display( $moduledata ) ) {
				courseexp_render_embed( $url_target, $activity_name, $url_target );
			} else {
				courseexp_render_launch( $url_target, $activity_name );
			}
		} else {
			switch ( $rendermode ) {
				case 'iframe':
					courseexp_render_embed( $embed_url, $activity_name );
					break;

				case 'iframe-unsafe':
				case 'external':
					$launch_url = '' !== $external_url ? $external_url : ( isset( $activity['url'] ) ? (string) $activity['url'] : '' );
					courseexp_render_launch( $launch_url, $activity_name );
					break;

				case 'file':
				case 'html':
					$api_client = new CourseExp_API_Client();
					$content    = $cmid > 0 && $moodle_user_id > 0 ? $api_client->get_activity_content( $cmid, $moodle_user_id ) : new \WP_Error( 'no_ids', '' );

					if ( is_wp_error( $content ) ) {
						?>
				<div class="courseexp-error"><p><?php esc_html_e( 'Unable to load this activity. Please try again later.', 'eb-course-exp' ); ?></p></div>
						<?php
						break;
					}

					$content      = is_object( $content ) ? json_decode( wp_json_encode( $content ), true ) : (array) $content;
					$body         = isset( $content['content'] ) ? (array) $content['content'] : array();
					$content_type = isset( $body['type'] ) ? (string) $body['type'] : '';

					if ( 'book' === $content_type && ! empty( $body['chapters'] ) ) {
						$chapters       = (array) $body['chapters'];
						$book_toc_class = 'courseexp-activity-book__toc';
						if ( 0 === $book_numbering ) {
							$book_toc_class .= ' courseexp-activity-book__toc--none';
						} elseif ( 2 === $book_numbering ) {
							$book_toc_class .= ' courseexp-activity-book__toc--bullets';
						} elseif ( 3 === $book_numbering ) {
							$book_toc_class .= ' courseexp-activity-book__toc--indented';
						}
						?>
				<div class="courseexp-activity-book">
					<nav class="courseexp-activity-book__nav" aria-label="<?php esc_attr_e( 'Chapters', 'eb-course-exp' ); ?>">
						<ol class="<?php echo esc_attr( $book_toc_class ); ?>">
							<?php foreach ( $chapters as $chapter ) : ?>
								<?php
								$chapter = (array) $chapter;
								if ( ! empty( $chapter['hidden'] ) ) {
									continue;
								}
								$chap_id    = isset( $chapter['id'] ) ? (int) $chapter['id'] : 0;
								$chap_title = isset( $chapter['title'] ) ? (string) $chapter['title'] : '';
								$is_sub     = ! empty( $chapter['subchapter'] );
								?>
								<li class="courseexp-activity-book__toc-item<?php echo $is_sub ? ' is-sub' : ''; ?>">
									<a class="courseexp-activity-book__toc-link" href="#courseexp-chapter-<?php echo esc_attr( $chap_id ); ?>"><?php echo esc_html( $chap_title ); ?></a>
								</li>
							<?php endforeach; ?>
						</ol>
					</nav>
					<div class="courseexp-activity-book__content">
								<?php foreach ( $chapters as $chapter ) : ?>
									<?php
									$chapter = (array) $chapter;
									if ( ! empty( $chapter['hidden'] ) ) {
										continue;
									}
									$chap_id    = isset( $chapter['id'] ) ? (int) $chapter['id'] : 0;
									$chap_title = isset( $chapter['title'] ) ? (string) $chapter['title'] : '';
									$chap_html  = isset( $chapter['content'] ) ? (string) $chapter['content'] : '';
									?>
							<section class="courseexp-activity-book__chapter" id="courseexp-chapter-<?php echo esc_attr( $chap_id ); ?>">
									<?php if ( '' !== trim( $chap_title ) ) : ?>
									<h2 class="courseexp-activity-book__chapter-title"><?php echo esc_html( $chap_title ); ?></h2>
								<?php endif; ?>
								<div class="courseexp-activity-book__chapter-body">
									<?php courseexp_render_trusted_html( $chap_html ); ?>
								</div>
							</section>
						<?php endforeach; ?>
					</div>
				</div>
						<?php
						break;
					}

					if ( ! empty( $body['files'] ) ) {
						$files  = (array) $body['files'];
						$first  = (array) $files[0];
						$is_pdf = false;
						if ( 1 === count( $files ) ) {
							$mime   = isset( $first['mimetype'] ) ? (string) $first['mimetype'] : '';
							$is_pdf = ( false !== strpos( $mime, 'pdf' ) );
						}

						if ( $is_pdf ) {
							$file_url = courseexp_file_url( $first, $api_client );
							$filename = isset( $first['filename'] ) ? (string) $first['filename'] : '';
							$meta     = courseexp_file_meta_parts( $first, $show_file_size, $show_file_type, $show_file_date );
							?>
					<div class="courseexp-activity-file">
							<?php if ( ! empty( $meta ) ) : ?>
							<p class="courseexp-activity-file__meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></p>
						<?php endif; ?>
						<iframe class="courseexp-activity-file__viewer" src="<?php echo esc_url( $file_url ); ?>" title="<?php echo esc_attr( $filename ? $filename : $activity_name ); ?>"></iframe>
					</div>
							<?php
							break;
						}
						?>
				<ul class="courseexp-activity-files">
						<?php foreach ( $files as $file ) : ?>
							<?php
							$file     = (array) $file;
							$file_url = courseexp_file_url( $file, $api_client );
							$filename = isset( $file['filename'] ) ? (string) $file['filename'] : '';
							$meta     = courseexp_file_meta_parts( $file, $show_file_size, $show_file_type, $show_file_date );
							?>
						<li class="courseexp-activity-files__item">
							<a class="courseexp-activity-files__link" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer">
								<span class="courseexp-activity-files__icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
								</span>
								<span class="courseexp-activity-files__name"><?php echo esc_html( $filename ); ?></span>
								<?php if ( ! empty( $meta ) ) : ?>
									<span class="courseexp-activity-files__meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
						<?php
						break;
					}

					if ( ! empty( $body['html'] ) ) {
						?>
				<div class="courseexp-activity-html">
						<?php courseexp_render_trusted_html( (string) $body['html'] ); ?>
				</div>
						<?php
						break;
					}

					if ( ! $has_desc ) {
						?>
				<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity has no content yet.', 'eb-course-exp' ); ?></p></div>
						<?php
					}
					break;

				case 'inline':
					if ( ! $has_desc ) {
						?>
				<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity has no content yet.', 'eb-course-exp' ); ?></p></div>
						<?php
					}
					break;

				default:
					if ( ! $has_desc ) {
						?>
				<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity cannot be displayed here.', 'eb-course-exp' ); ?></p></div>
						<?php
					}
					break;
			}
		}
		?>
	<?php endif; ?>
</div>
