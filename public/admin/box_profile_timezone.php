<h3><a name="ai1ec"><?php
        esc_html_e('Open Source Event Calendar', 'open-source-event-calendar');
        ?></a></h3>
<table class="ai1ec-form">
    <tbody>
    <tr>
        <td class="ai1ec-first">
            <label for="osec_user_timezone">
                <?php esc_html_e('Your preferred timezone', 'open-source-event-calendar'); ?>?
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
