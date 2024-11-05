<p>
	<label for="<?php echo $title['id'] ?>"><?php _e( 'Title:', OSEC_TXT_DOM ) ?></label>
	<input class="widefat" id="<?php echo $title['id'] ?>" name="<?php echo $title['name'] ?>" type="text" value="<?php echo $title['value'] ?>" />
</p>

<p>
	<input type="radio" id="<?php echo $events_seek_type['id']; ?>_events" name="<?php echo $events_seek_type['name']; ?>" value="events"<?php
	if ( 'events' === $events_seek_type['value'] ) {
		echo ' checked="checked"';
	}
	?> />
	<label for="<?php echo $events_seek_type['id']; ?>_events"><?php _e( 'Number of events to show:', OSEC_TXT_DOM ) ?></label>
	<input id="<?php echo $events_per_page['id'] ?>" name="<?php echo $events_per_page['name'] ?>" type="text" size="3" value="<?php echo $events_per_page['value'] ?>" />
</p>
<p>
	<input type="radio" id="<?php echo $events_seek_type['id']; ?>_days" name="<?php echo $events_seek_type['name']; ?>" value="days"<?php
	if ( 'days' === $events_seek_type['value'] ) {
		echo ' checked="checked"';
	}
	?> />
	<label for="<?php echo $events_seek_type['id']; ?>_days"><?php _e( 'Number of days to show:', OSEC_TXT_DOM ) ?></label>
	<input id="<?php echo $days_per_page['id'] ?>" name="<?php echo $days_per_page['name'] ?>" type="text" size="3" value="<?php echo $days_per_page['value'] ?>" />
</p>

<p class="ai1ec-limit-by-container">
	<?php _e( 'Limit to:', OSEC_TXT_DOM ); ?>
	<br />
	<input id="<?php echo $limit_by_cat['id'] ?>" class="ai1ec-limit-by-cat ai1ec-toggle-fix" name="<?php echo $limit_by_cat['name'] ?>" type="checkbox" value="1" <?php if( $limit_by_cat['value'] ) echo 'checked="checked"' ?>
         onchange="this.parentElement.nextElementSibling.style.display = (this.parentElement.nextElementSibling.style.display === 'none') ? 'block' : 'none'; this.parentElement.nextElementSibling.children[0].value = '';"
  />
	<label for="<?php echo $limit_by_cat['id'] ?>"><?php _e( 'Events with these <strong>Categories</strong>', OSEC_TXT_DOM ) ?></label>
</p>
<p class="ai1ec-limit-by-options-container" <?php if( ! $limit_by_cat['value'] ) { ?> style="display: none;" <?php } ?>>
	<select id="<?php echo $cat_ids['id'] ?>" class="ai1ec-widget-cat-ids" name="<?php echo $cat_ids['name'] ?>[]" size="5" multiple="multiple" style="width: 18em; margin-left: 2em">
		<?php foreach( $cat_ids['options'] as $event_cat ): ?>
			<option value="<?php echo $event_cat->term_id; ?>"<?php if( in_array( $event_cat->term_id, $cat_ids['value'] ) ) { ?> selected="selected"<?php } ?>><?php echo $event_cat->name; ?></option>
		<?php endforeach ?>
		<?php if( count( $cat_ids['options'] ) == 0 ) : ?>
			<option disabled><?php _e( 'No categories found.', OSEC_TXT_DOM ) ?></option>
		<?php endif ?>
	</select>
</p>
<p class="ai1ec-limit-by-container">
  <?php
  // Fix unknows missing script
  // by adding onchange="this.parentElement.nextElementSibling...  to quickfix in plain js.
  // TODO
  //   Widget is deprecated, so we need to replace all this with a Block Plugin.
  ?>
	<input
    id="<?php echo $limit_by_tag['id'] ?>"
    class="ai1ec-limit-by-tag ai1ec-toggle-fix"
    name="<?php echo $limit_by_tag['name'] ?>"
    type="checkbox"
    value="1" <?php if( $limit_by_tag['value'] ) echo 'checked="checked"' ?>
    onchange="this.parentElement.nextElementSibling.style.display = (this.parentElement.nextElementSibling.style.display === 'none') ? 'block' : 'none'; this.parentElement.nextElementSibling.children[0].value = '';"
  "/>
	<label for="<?php echo $limit_by_tag['id'] ?>"><?php _e( '<strong>Or</strong> events with these <strong>Tags</strong>', OSEC_TXT_DOM ) ?></label>
</p>
<p class="ai1ec-limit-by-options-container" <?php if( ! $limit_by_tag['value'] ) { ?> style="display: none;" <?php } ?>>
	<select id="<?php echo $tag_ids['id'] ?>" class="ai1ec-widget-tag-ids" name="<?php echo $tag_ids['name'] ?>[]" size="5" multiple="multiple" style="width: 20em; margin-left: 2em">
		<?php foreach( $tag_ids['options'] as $event_tag ): ?>
			<option value="<?php echo $event_tag->term_id; ?>"<?php if( in_array( $event_tag->term_id, $tag_ids['value'] ) ) { ?> selected="selected"<?php } ?>><?php echo $event_tag->name; ?></option>
		<?php endforeach ?>
		<?php if( count( $tag_ids['options'] ) == 0 ) : ?>
			<option disabled><?php _e( 'No tags found.', OSEC_TXT_DOM ) ?></option>
		<?php endif ?>
	</select>
</p>

<p>
	<input
    id="<?php echo $show_calendar_button['id'] ?>"
    name="<?php echo $show_calendar_button['name'] ?>"
    type="checkbox"
    value="1" <?php if( $show_calendar_button['value'] ) echo 'checked="checked"' ?>
    onchange="this.parentElement.nextElementSibling.style.display = (this.parentElement.nextElementSibling.style.display === 'none') ? 'block' : 'none'; this.parentElement.nextElementSibling.children[0].value = '';"
	<label for="<?php echo $show_calendar_button['id'] ?>"><?php _e( 'Show <strong>View Calendar</strong> button', OSEC_TXT_DOM ) ?></label>
	<br />
	<input id="<?php echo $show_subscribe_buttons['id'] ?>" name="<?php echo $show_subscribe_buttons['name'] ?>" type="checkbox" value="1" <?php if( $show_subscribe_buttons['value'] ) echo 'checked="checked"' ?> />
	<label for="<?php echo $show_subscribe_buttons['id'] ?>"><?php _e( 'Show <strong>Subscribe</strong> buttons', OSEC_TXT_DOM ) ?></label>
	<br />
	<input id="<?php echo $hide_on_calendar_page['id'] ?>" name="<?php echo $hide_on_calendar_page['name'] ?>" type="checkbox" value="1" <?php if( $hide_on_calendar_page['value'] ) echo 'checked="checked"' ?> />
	<label for="<?php echo $hide_on_calendar_page['id'] ?>"><?php _e( 'Hide this widget on calendar page', OSEC_TXT_DOM ) ?></label>
</p>
