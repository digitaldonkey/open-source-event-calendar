<select name="cron_freq">
	<option value="hourly" <?php echo $cron_freq == 'hourly' ? 'selected' : ''; ?>>
		<?php _e( 'Hourly', OSEC_TXT_DOM ) ?>
	</option>
	<option value="twicedaily" <?php echo $cron_freq == 'twicedaily' ? 'selected' : '' ?>>
		<?php _e( 'Twice Daily', OSEC_TXT_DOM ) ?>
	</option>
	<option value="daily" <?php echo $cron_freq == 'daily' ? 'selected' : '' ?>>
		<?php _e( 'Daily', OSEC_TXT_DOM ) ?>
	</option>
</select>
