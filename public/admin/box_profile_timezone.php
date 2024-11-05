<h3><a name="ai1ec"><?php
_e( 'Open Source Event Calendar', OSEC_TXT_DOM );
?></a></h3>
<table class="ai1ec-form">
	<tbody>
		<tr>
			<td class="ai1ec-first">
				<label for="osec_user_timezone">
					<?php _e( 'Your preferred timezone', OSEC_TXT_DOM ); ?>?
				</label>
			</td>
			<td>
				<select name="osec_user_timezone" id="osec_user_timezone">
				<?php echo $tz_selector; ?>
				</select>
			</td>
		</tr>
	</tbody>
</table>
