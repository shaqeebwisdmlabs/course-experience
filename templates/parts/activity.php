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
				height="600"
				scrolling="no"
				loading="lazy"
			></iframe>
		</div>
		<?php if ( '' !== $fallback_url ) : ?>
			<p class="courseexp-activity-embed__fallback courseexp-prose">
				<a class="courseexp-activity-embed__fallback-link" href="<?php echo esc_url( $fallback_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in a new tab', 'eb-course-exp' ); ?></a>
			</p>
			<?php
		endif;
	}
}

if ( ! function_exists( 'courseexp_render_file_viewer' ) ) {
	/**
	 * Render a file inline in the embedded viewer iframe (PDF, image, etc.).
	 *
	 * @param string $src   File URL.
	 * @param string $title Accessible iframe title.
	 * @return void
	 */
	function courseexp_render_file_viewer( string $src, string $title ): void {
		if ( '' === $src ) {
			?>
			<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity could not be loaded.', 'eb-course-exp' ); ?></p></div>
			<?php
			return;
		}
		?>
		<div class="courseexp-activity-file">
			<iframe class="courseexp-activity-file__viewer" src="<?php echo esc_url( $src ); ?>" title="<?php echo esc_attr( $title ); ?>"></iframe>
		</div>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_download' ) ) {
	/**
	 * Render a prompt to download a file resource (Moodle "force download"), using
	 * the file name as the link text.
	 *
	 * Adds Moodle's forcedownload flag so the file is served as an attachment.
	 *
	 * @param string $url      File URL.
	 * @param string $filename File name used as the link text and download attribute.
	 * @return void
	 */
	function courseexp_render_download( string $url, string $filename = '' ): void {
		if ( '' === $url ) {
			?>
			<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity could not be loaded.', 'eb-course-exp' ); ?></p></div>
			<?php
			return;
		}

		$link = sprintf(
			'<a class="courseexp-activity-launch__link" href="%s" download="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( add_query_arg( 'forcedownload', 1, $url ) ),
			esc_attr( $filename ),
			esc_html( '' !== trim( $filename ) ? $filename : __( 'the file', 'eb-course-exp' ) )
		);
		?>
		<p class="courseexp-activity-launch courseexp-prose">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: linked file name. */
					__( 'Click %s link to download the file.', 'eb-course-exp' ),
					$link
				),
				array(
					'a' => array(
						'href'     => array(),
						'download' => array(),
						'target'   => array(),
						'rel'      => array(),
						'class'    => array(),
					),
				)
			);
			?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_launch' ) ) {
	/**
	 * Render the prompt for an activity that opens off-page: a plain sentence with
	 * the activity name as the link (mirrors Moodle, no surrounding chrome).
	 *
	 * When popup dimensions are given the link carries them as data attributes so
	 * the front-end opens a sized window (Moodle's popup display); the new-tab
	 * target remains as the fallback when the browser blocks the popup.
	 *
	 * @param string $url          Destination URL.
	 * @param string $name         Activity name used as the link text.
	 * @param int    $popup_width  Popup window width in pixels, or 0 for a new tab.
	 * @param int    $popup_height Popup window height in pixels, or 0 for a new tab.
	 * @return void
	 */
	function courseexp_render_launch( string $url, string $name, int $popup_width = 0, int $popup_height = 0 ): void {
		if ( '' === $url ) {
			?>
			<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity could not be loaded.', 'eb-course-exp' ); ?></p></div>
			<?php
			return;
		}

		$popup_attrs = ( $popup_width > 0 && $popup_height > 0 )
			? sprintf( ' data-courseexp-popup-width="%d" data-courseexp-popup-height="%d"', $popup_width, $popup_height )
			: '';

		$link = sprintf(
			'<a class="courseexp-activity-launch__link" href="%s" target="_blank" rel="noopener noreferrer"%s>%s</a>',
			esc_url( $url ),
			$popup_attrs,
			esc_html( '' !== trim( $name ) ? $name : __( 'this activity', 'eb-course-exp' ) )
		);
		?>
		<p class="courseexp-activity-launch courseexp-prose">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: linked activity name. */
					__( 'Click on %s to open the resource.', 'eb-course-exp' ),
					$link
				),
				array(
					'a' => array(
						'href'                        => array(),
						'target'                      => array(),
						'rel'                         => array(),
						'class'                       => array(),
						'data-courseexp-popup-width'  => array(),
						'data-courseexp-popup-height' => array(),
					),
				)
			);
			?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_file_launch' ) ) {
	/**
	 * Render the prompt for a file resource that opens off-page, using the file
	 * name as the link text (mirrors Moodle's "Click NAME link to view the file").
	 *
	 * @param string $url          File URL.
	 * @param string $filename     File name used as the link text.
	 * @param int    $popup_width  Popup window width in pixels, or 0 for a new tab.
	 * @param int    $popup_height Popup window height in pixels, or 0 for a new tab.
	 * @return void
	 */
	function courseexp_render_file_launch( string $url, string $filename, int $popup_width = 0, int $popup_height = 0 ): void {
		if ( '' === $url ) {
			?>
			<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This activity could not be loaded.', 'eb-course-exp' ); ?></p></div>
			<?php
			return;
		}

		$popup_attrs = ( $popup_width > 0 && $popup_height > 0 )
			? sprintf( ' data-courseexp-popup-width="%d" data-courseexp-popup-height="%d"', $popup_width, $popup_height )
			: '';

		$link = sprintf(
			'<a class="courseexp-activity-launch__link" href="%s" target="_blank" rel="noopener noreferrer"%s>%s</a>',
			esc_url( $url ),
			$popup_attrs,
			esc_html( '' !== trim( $filename ) ? $filename : __( 'the file', 'eb-course-exp' ) )
		);
		?>
		<p class="courseexp-activity-launch courseexp-prose">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: linked file name. */
					__( 'Click %s link to view the file.', 'eb-course-exp' ),
					$link
				),
				array(
					'a' => array(
						'href'                        => array(),
						'target'                      => array(),
						'rel'                         => array(),
						'class'                       => array(),
						'data-courseexp-popup-width'  => array(),
						'data-courseexp-popup-height' => array(),
					),
				)
			);
			?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_book_pager' ) ) {
	/**
	 * Render the previous/next chapter pager for a book activity.
	 *
	 * Each link reloads the activity page with the target chapter's
	 * courseexp_chapter argument, mirroring Moodle's per-chapter navigation.
	 *
	 * @param array  $prev     Previous chapter {id,title} or empty array at the start.
	 * @param array  $next     Next chapter {id,title} or empty array at the end.
	 * @param string $base_url Activity page URL the chapter argument is added to.
	 * @return void
	 */
	function courseexp_render_book_pager( array $prev, array $next, string $base_url ): void {
		if ( empty( $prev ) && empty( $next ) ) {
			return;
		}
		?>
		<nav class="courseexp-activity-book__pager" aria-label="<?php esc_attr_e( 'Chapter navigation', 'eb-course-exp' ); ?>">
			<?php if ( ! empty( $prev ) ) : ?>
				<a class="courseexp-activity-book__pager-link courseexp-activity-book__pager-link--prev" href="<?php echo esc_url( add_query_arg( 'courseexp_chapter', (int) $prev['id'], $base_url ) ); ?>" rel="prev" title="<?php echo esc_attr( $prev['title'] ); ?>">
					<span class="courseexp-activity-book__pager-arrow" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
					</span>
					<span class="courseexp-activity-book__pager-meta">
						<span class="courseexp-activity-book__pager-label"><?php esc_html_e( 'Previous', 'eb-course-exp' ); ?></span>
						<span class="courseexp-activity-book__pager-title"><?php echo esc_html( $prev['title'] ); ?></span>
					</span>
				</a>
			<?php else : ?>
				<span class="courseexp-activity-book__pager-spacer" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ( ! empty( $next ) ) : ?>
				<a class="courseexp-activity-book__pager-link courseexp-activity-book__pager-link--next" href="<?php echo esc_url( add_query_arg( 'courseexp_chapter', (int) $next['id'], $base_url ) ); ?>" rel="next" title="<?php echo esc_attr( $next['title'] ); ?>">
					<span class="courseexp-activity-book__pager-meta">
						<span class="courseexp-activity-book__pager-label"><?php esc_html_e( 'Next', 'eb-course-exp' ); ?></span>
						<span class="courseexp-activity-book__pager-title"><?php echo esc_html( $next['title'] ); ?></span>
					</span>
					<span class="courseexp-activity-book__pager-arrow" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
					</span>
				</a>
			<?php endif; ?>
		</nav>
		<?php
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
$available     = ( ! isset( $activity['available'] ) || (bool) $activity['available'] ) && ! courseexp_activity_is_unavailable( $activity );
$avail_info    = isset( $activity['availabilityinfo'] ) ? (string) $activity['availabilityinfo'] : '';
$description   = isset( $activity['description'] ) ? (string) $activity['description'] : '';
$embed_url     = isset( $activity['embedurl'] ) ? (string) $activity['embedurl'] : '';
$external_url  = isset( $activity['externalurl'] ) ? (string) $activity['externalurl'] : '';
$activity_name = isset( $activity['name'] ) ? (string) $activity['name'] : '';
$modname       = isset( $activity['modname'] ) ? (string) $activity['modname'] : '';
$has_desc      = '' !== trim( $description );

$moduledata = courseexp_activity_moduledata( $activity );

$embeds_own_intro = ( 'iframe' === $rendermode && 'url' !== $modname );
$show_intro       = $has_desc && ! $embeds_own_intro && ( ! isset( $moduledata['printintro'] ) || ! empty( $moduledata['printintro'] ) );
$show_dates       = ! empty( courseexp_activity_dates( $activity ) );
$book_numbering   = isset( $moduledata['numbering'] ) ? (int) $moduledata['numbering'] : 1;

$afterlink = isset( $activity['afterlink'] ) ? trim( (string) $activity['afterlink'] ) : '';
?>

<div class="courseexp-activity-content" data-activity-id="<?php echo esc_attr( $cmid ); ?>" data-rendermode="<?php echo esc_attr( $rendermode ); ?>">
	<?php if ( ! $available ) : ?>
		<div class="courseexp-activity-locked">
			<span class="courseexp-activity-locked__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
			</span>
			<?php if ( '' !== trim( $avail_info ) ) : ?>
				<span class="courseexp-activity-locked__text courseexp-prose"><?php echo wp_kses_post( $avail_info ); ?></span>
			<?php else : ?>
				<span class="courseexp-activity-locked__text"><?php esc_html_e( 'This activity is not available yet.', 'eb-course-exp' ); ?></span>
			<?php endif; ?>
		</div>
	<?php else : ?>

		<?php if ( $show_intro ) : ?>
		<div class="courseexp-activity-content__description courseexp-prose">
			<?php courseexp_render_trusted_html( $description ); ?>
		</div>
	<?php endif; ?>

		<?php if ( '' !== $afterlink ) : ?>
		<div class="courseexp-activity-afterlink courseexp-prose"><?php courseexp_render_trusted_html( $afterlink ); ?></div>
	<?php endif; ?>

		<?php if ( $show_dates ) : ?>
			<?php courseexp_render_activity_dates( $activity ); ?>
	<?php endif; ?>

		<?php
		if ( 'url' === $modname ) {
			$url_target  = '' !== $external_url ? $external_url : ( isset( $activity['url'] ) ? (string) $activity['url'] : '' );
			$url_display = courseexp_display_mode( $moduledata );

			if ( 1 === $url_display ) {
				courseexp_render_embed( $url_target, $activity_name, $url_target );
			} elseif ( 6 === $url_display ) {
				$popup_width  = isset( $moduledata['popupwidth'] ) ? (int) $moduledata['popupwidth'] : 620;
				$popup_height = isset( $moduledata['popupheight'] ) ? (int) $moduledata['popupheight'] : 450;
				courseexp_render_launch( $url_target, $activity_name, $popup_width, $popup_height );
			} else {
				courseexp_render_launch( $url_target, $activity_name );
			}
		} elseif ( 'resource' === $modname && '' !== $external_url ) {
			$file_url     = ( new CourseExp_API_Client() )->append_file_token( $external_url );
			$file_display = courseexp_display_mode( $moduledata );
			$embeddable   = ! empty( $moduledata['embeddable'] );
			$file_name    = isset( $moduledata['filename'] ) ? (string) $moduledata['filename'] : '';

			if ( ! $embeddable || 4 === $file_display ) {
				courseexp_render_download( $file_url, $file_name );
			} elseif ( 1 === $file_display || 2 === $file_display ) {
				courseexp_render_file_viewer( $file_url, $activity_name );
			} else {
				$popup_width  = 0;
				$popup_height = 0;
				if ( 6 === $file_display ) {
					$popup_width  = isset( $moduledata['popupwidth'] ) ? (int) $moduledata['popupwidth'] : 620;
					$popup_height = isset( $moduledata['popupheight'] ) ? (int) $moduledata['popupheight'] : 450;
				}
				courseexp_render_file_launch( $file_url, $file_name, $popup_width, $popup_height );
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
						$chapters   = (array) $body['chapters'];
						$book_modes = array(
							0 => 'none',
							1 => 'numbers',
							2 => 'bullets',
							3 => 'indented',
						);
						$book_mode  = isset( $book_modes[ $book_numbering ] ) ? $book_modes[ $book_numbering ] : 'numbers';
						$toc_class  = 'courseexp-activity-book__toc courseexp-activity-book__toc--' . $book_mode;
						$chap_nums  = ( 'numbers' === $book_mode ) ? courseexp_book_chapter_numbers( $chapters ) : array();
						$visible    = array();
						foreach ( $chapters as $index => $chapter ) {
							$chapter = (array) $chapter;
							if ( ! empty( $chapter['hidden'] ) ) {
								continue;
							}
							$visible[] = array(
								'id'      => isset( $chapter['id'] ) ? (int) $chapter['id'] : 0,
								'title'   => isset( $chapter['title'] ) ? (string) $chapter['title'] : '',
								'content' => isset( $chapter['content'] ) ? (string) $chapter['content'] : '',
								'is_sub'  => ! empty( $chapter['subchapter'] ),
								'number'  => isset( $chap_nums[ $index ] ) ? $chap_nums[ $index ] : '',
							);
						}

						if ( empty( $visible ) ) {
							?>
				<div class="courseexp-sections__empty"><p><?php esc_html_e( 'This book has no chapters yet.', 'eb-course-exp' ); ?></p></div>
							<?php
							break;
						}

						$requested  = (int) get_query_var( 'courseexp_chapter' );
						$active_pos = 0;
						if ( $requested > 0 ) {
							foreach ( $visible as $pos => $chap ) {
								if ( $chap['id'] === $requested ) {
									$active_pos = $pos;
									break;
								}
							}
						}

						$active    = $visible[ $active_pos ];
						$prev      = isset( $visible[ $active_pos - 1 ] ) ? $visible[ $active_pos - 1 ] : array();
						$next      = isset( $visible[ $active_pos + 1 ] ) ? $visible[ $active_pos + 1 ] : array();
						$book_base = home_url( '/' . COURSEEXP_SLUG . '/' . get_query_var( 'course_slug' ) . '/activity/' . $cmid . '/' );
						?>
				<div class="courseexp-activity-book">
					<nav class="courseexp-activity-book__nav" aria-label="<?php esc_attr_e( 'Chapters', 'eb-course-exp' ); ?>">
						<ul class="<?php echo esc_attr( $toc_class ); ?>">
							<?php foreach ( $visible as $pos => $chap ) : ?>
								<?php $is_active = ( $pos === $active_pos ); ?>
								<li class="courseexp-activity-book__toc-item<?php echo $chap['is_sub'] ? ' is-sub' : ''; ?><?php echo $is_active ? ' is-active' : ''; ?>">
									<a class="courseexp-activity-book__toc-link" href="<?php echo esc_url( add_query_arg( 'courseexp_chapter', (int) $chap['id'], $book_base ) ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
										<?php if ( '' !== $chap['number'] ) : ?>
											<span class="courseexp-activity-book__toc-number"><?php echo esc_html( $chap['number'] ); ?></span>
										<?php endif; ?>
										<span class="courseexp-activity-book__toc-text"><?php echo esc_html( $chap['title'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
					<div class="courseexp-activity-book__content">
						<section class="courseexp-activity-book__chapter" id="courseexp-chapter-<?php echo esc_attr( $active['id'] ); ?>">
							<?php if ( '' !== trim( $active['title'] ) ) : ?>
								<h2 class="courseexp-activity-book__chapter-title">
									<?php if ( '' !== $active['number'] ) : ?>
										<span class="courseexp-activity-book__chapter-number"><?php echo esc_html( $active['number'] ); ?></span>
									<?php endif; ?>
									<?php echo esc_html( $active['title'] ); ?>
								</h2>
							<?php endif; ?>
							<div class="courseexp-activity-book__chapter-body courseexp-prose">
								<?php courseexp_render_trusted_html( $active['content'] ); ?>
							</div>
						</section>
						<?php courseexp_render_book_pager( $prev, $next, $book_base ); ?>
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
							courseexp_render_file_viewer( $file_url, '' !== $filename ? $filename : $activity_name );
							break;
						}
						?>
				<ul class="courseexp-activity-files">
						<?php foreach ( $files as $file ) : ?>
							<?php
							$file     = (array) $file;
							$file_url = courseexp_file_url( $file, $api_client );
							$filename = isset( $file['filename'] ) ? (string) $file['filename'] : '';
							?>
						<li class="courseexp-activity-files__item">
							<a class="courseexp-activity-files__link" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer">
								<span class="courseexp-activity-files__icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
								</span>
								<span class="courseexp-activity-files__name"><?php echo esc_html( $filename ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
						<?php
						break;
					}

					if ( ! empty( $body['html'] ) ) {
						?>
				<div class="courseexp-activity-html courseexp-prose">
						<?php courseexp_render_trusted_html( (string) $body['html'] ); ?>
				</div>
						<?php if ( 'page' === $modname && ! empty( $moduledata['lastmodifiedformatted'] ) ) : ?>
				<p class="courseexp-activity-last-modified">
					<span class="courseexp-activity-last-modified__label"><?php esc_html_e( 'Last modified:', 'eb-course-exp' ); ?></span>
					<span class="courseexp-activity-last-modified__value"><?php echo esc_html( (string) $moduledata['lastmodifiedformatted'] ); ?></span>
				</p>
						<?php endif; ?>
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
