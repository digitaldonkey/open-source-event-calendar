<div class="ai1ec-panel ai1ec-panel-default ai1ec-feed-container">
	<div class="ai1ec-panel-heading">
		<a data-toggle="ai1ec-collapse"
		   data-parent="#ai1ec-feeds-accordion"
		   href="#ai1ec-feed-<?php echo $feed_id; ?>">
			<?php echo $feed_name; ?>
		</a>
	</div>
	<div class="ai1ec-panel-collapse ai1ec-collapse"
	     id="ai1ec-feed-<?php echo $feed_id; ?>">
		<div class="ai1ec-panel-body">
			<div class="ai1ec-feed-content">
				<div class="ai1ec-form-group">
					<label><?php _e( 'iCalendar/.ics Feed URL:', OSEC_TXT_DOM ); ?></label>
					<input type="text" class="ai1ec-feed-url ai1ec-form-control"
						readonly="readonly" value="<?php echo $feed_url ?>">
				</div>
				<input type="hidden" name="feed_id" class="ai1ec_feed_id"
					value="<?php echo $feed_id; ?>">
				<div class="ai1ec-clearfix">
					<?php if ( $event_category ) : ?>
						<div class="ai1ec-feed-category"
							 data-ids="<?php echo $categories_ids; ?>">
							<?php _e( 'Event categories:', OSEC_TXT_DOM ); ?>
							<strong><?php echo $event_category; ?></strong>
						</div>
					<?php endif; ?>
					<?php if ( $tags ) : ?>
						<div class="ai1ec-feed-tags ai1ec-pull-left"
							 data-ids="<?php echo $tags_ids; ?>">
							<?php _e( 'Tag with', OSEC_TXT_DOM ); ?>:
							<strong><?php echo $tags; ?></strong>
						</div>
					<?php endif; ?>
				</div>

				<?php
                /**
                 * Add Html content above feeds options
                 *
                 * On Feeds admin page you can echo/print any Html sting.
                 *
                 * @since 1.0
                 *
                 * @param ?int $feed_id DB id of the feed.
                 */
                do_action( 'osec_admin_ics_feeds_options_header_html', $feed_id); ?>
				<div class="ai1ec-clearfix">
					<div class="ai1ec-feed-comments-enabled"
						 data-state="<?php echo esc_attr( $comments_enabled ? 1 : 0 ); ?>">
						<?php _e( 'Allow comments', OSEC_TXT_DOM ); ?>:
						<strong><?php
						if ( $comments_enabled ) {
							_e( 'Yes', OSEC_TXT_DOM );
						} else {
							_e( 'No',  OSEC_TXT_DOM );
						}
						?></strong>
					</div>
					<div class="ai1ec-feed-map-display-enabled"
						 data-state="<?php echo esc_attr( $map_display_enabled ? 1 : 0 ); ?>">
						<?php _e( 'Show map', OSEC_TXT_DOM ); ?>:
						<strong><?php
						if ( $map_display_enabled ) {
							_e( 'Yes', OSEC_TXT_DOM );
						} else {
							_e( 'No',  OSEC_TXT_DOM );
						}
						?></strong>
					</div>
				</div>
				<div class="ai1ec-feed-keep-tags-categories"
					 data-state="<?php echo esc_attr( $keep_tags_categories ? 1 : 0 ); ?>">
					<?php _e( 'Keep original events categories and tags', OSEC_TXT_DOM ); ?>:
					<strong><?php
					if ( $keep_tags_categories ) {
						_e( 'Yes', OSEC_TXT_DOM );
					} else {
						_e( 'No',  OSEC_TXT_DOM );
					}
					?></strong>
				</div>
                <?php
                /**
                 * Add Html content above keep-options
                 *
                 * On Feeds admin page you can echo/print any Html sting.
                 *
                 * @since 1.0
                 *
                 * @param ?int $feed_id DB id of the feed if in feed context.
                 */
                do_action( 'osec_admin_ics_feeds_options_before_keep_original_html', $feed_id ); ?>
				<div class="ai1ec-feed-keep-old-events"
					 data-state="<?php echo esc_attr( $keep_old_events ? 1 : 0 ); ?>">
					<?php _e( 'On refresh, preserve previously imported events that are missing from the feed', OSEC_TXT_DOM ); ?>:
					<strong><?php
					if ( $keep_old_events ) {
						_e( 'Yes', OSEC_TXT_DOM );
					} else {
						_e( 'No',  OSEC_TXT_DOM );
					}
					?></strong>
				</div>
				<div class="ai1ec-feed-import-timezone"
					 data-state="<?php echo esc_attr( $feed_import_timezone ? 1 : 0 ); ?>">
					<span class="ai1ec-tooltip-toggle" title="<?php _e( 'Guesses the time zone of events that have none specified; recommended for Google Calendar feeds', OSEC_TXT_DOM ); ?>">
						<?php _e( 'Assign default time zone to events in UTC', OSEC_TXT_DOM );
					?>:</span>
					<strong><?php
						if ( $feed_import_timezone ) {
							_e( 'Yes', OSEC_TXT_DOM );
						} else {
							_e( 'No',  OSEC_TXT_DOM );
						} ?>
					</strong>
				</div>
                <?php
                /**
                 * Add Html content below feeds options
                 *
                 * On Feeds admin page you can echo/print any Html sting.
                 *
                 * @since 1.0
                 *
                 * @param ?int $feed_id DB id of the feed.
                 */
                do_action('osec_admin_ics_feeds_options_after_settings_html', $feed_id ); ?>
				<div class="ai1ec-btn-group ai1ec-pull-right ai1ec-feed-actions">
					<button type="button"
						class="ai1ec-btn ai1ec-btn-sm ai1ec-btn-default ai1ec-text-primary
							osec_update_ics"
						data-loading-text="<?php echo esc_attr(
							'<i class="ai1ec-fa ai1ec-fa-refresh ai1ec-fa-spin ai1ec-fa-fw"></i> ' .
							__( 'Refreshing&#8230;', OSEC_TXT_DOM ) ); ?>">
						<i class="ai1ec-fa ai1ec-fa-refresh ai1ec-fa-fw"></i>
						<?php _e( 'Refresh', OSEC_TXT_DOM ); ?>
					</button>
					<button type="button"
						class="ai1ec-btn ai1ec-btn-sm ai1ec-btn-default ai1ec-text-warning
							ai1ec_edit_ics">
						<i class="ai1ec-fa ai1ec-fa-edit ai1ec-fa-fw"></i>
						<?php _e( 'Edit', OSEC_TXT_DOM ); ?>
					</button>
					<button type="button"
						class="ai1ec-btn ai1ec-btn-sm ai1ec-btn-default ai1ec-text-danger
							osec_delete_ics"
						data-loading-text="<?php echo esc_attr(
							'<i class="ai1ec-fa ai1ec-fa-spinner ai1ec-fa-spin ai1ec-fa-fw"></i> ' .
							__( 'Removing&#8230;', OSEC_TXT_DOM ) ); ?>">
						<i class="ai1ec-fa ai1ec-fa-times ai1ec-fa-fw"></i>
						<?php _e( 'Remove', OSEC_TXT_DOM ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
