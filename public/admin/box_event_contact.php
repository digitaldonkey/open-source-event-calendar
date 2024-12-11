<div class="ai1ec-panel-heading">
    <a data-toggle="ai1ec-collapse"
       id="osec-event-contact-link"
       data-parent="#osec-add-new-event-accordion"
       href="#osec-event-contact-box">
        <i class="ai1ec-fa ai1ec-fa-phone ai1ec-fa-fw"></i>
        <?php esc_html_e('Organizer contact info', 'open-source-event-calendar'); ?>
        <i class="ai1ec-fa ai1ec-fa-warning ai1ec-fa-fw ai1ec-hidden"></i>
    </a>
</div>
<div id="osec-event-contact-box" class="ai1ec-panel-collapse ai1ec-collapse">
    <div class="ai1ec-panel-body">
        <table class="ai1ec-form">
            <tbody>
            <tr>
                <td class="ai1ec-first">
                    <label for="osec_contact_name">
                        <?php esc_html_e('Contact name:', 'open-source-event-calendar'); ?>
                    </label>
                </td>
                <td>
                    <input type="text" name="osec_contact_name"
                           id="osec_contact_name"
                           class="ai1ec-form-control"
                           value="<?php echo esc_attr($contact_name); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="osec_contact_phone">
                        <?php esc_html_e('Phone:', 'open-source-event-calendar'); ?>
                    </label>
                </td>
                <td>
                    <input type="text" name="osec_contact_phone"
                           id="osec_contact_phone"
                           class="ai1ec-form-control"
                           value="<?php echo esc_attr($contact_phone); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="osec_contact_email">
                        <?php esc_html_e('E-mail:', 'open-source-event-calendar'); ?>
                    </label>
                </td>
                <td>
                    <input name="osec_contact_email"
                           type="text"
                           id="osec_contact_email"
                           class="ai1ec-form-control"
                           value="<?php echo esc_attr($contact_email); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="osec_contact_url">
                        <?php esc_html_e('Website URL:', 'open-source-event-calendar'); ?>
                    </label>
                </td>
                <td>
                    <input type="text" name="osec_contact_url"
                           id="osec_contact_url"
                           class="ai1ec-form-control"
                           value="<?php echo esc_attr($contact_url); ?>">
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
