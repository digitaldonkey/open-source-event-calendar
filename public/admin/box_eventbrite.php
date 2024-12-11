<h4 class="box_h4"><?php esc_html_e('Eventbrite Ticketing', 'open-source-event-calendar'); ?>:</h4>
<table>
    <tbody>
    <tr>
        <td>
            <label>
                <?php esc_html_e('Register this event with Eventbrite.com?', 'open-source-event-calendar'); ?>
            </label>
        </td>
        <td>
            <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_yes"
                   id="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_yes"/>
            <label for="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_yes"><?php esc_html_e('Yes', 'open-source-event-calendar'); ?></label>
            <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_no"
                   id="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_no"/>
            <label for="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_no"><?php esc_html_e('No', 'open-source-event-calendar'); ?></label>
        </td>
    </tr>
    </tbody>
</table>

<div id="<?php echo OSEC_PLUGIN_NAME; ?>_eventbrite_body">
    <h4>
        <?php esc_html_e('Set up your first ticket', 'open-source-event-calendar'); ?>
        <small>
            <?php esc_html_e(
                'To create multiple tickets per event, submit this form, then follow the link to Eventbrite.',
                'open-source-event-calendar'
            ); ?>
        </small>
    </h4>
    <table>
        <tbody>
        <tr>
            <td>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_name">
                    <?php esc_html_e('Name', 'open-source-event-calendar'); ?>:
                </label>
            </td>
            <td>
                <input type="text" name="<?php echo OSEC_PLUGIN_NAME; ?>_name"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_name"/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_description">
                    <?php esc_html_e('Description', 'open-source-event-calendar'); ?>:
                </label>
            </td>
            <td>
                    <textarea name="<?php echo OSEC_PLUGIN_NAME; ?>_description"
                              id="<?php echo OSEC_PLUGIN_NAME; ?>_description">
                    </textarea>
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <?php esc_html_e('Type', 'open-source-event-calendar'); ?>:
                </label>
            </td>
            <td>
                <input type="radio" name="<?php echo OSEC_PLUGIN_NAME; ?>_type"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_type_price"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_type_price"><?php esc_html_e('Set Price', 'open-source-event-calendar'); ?></label>
                <input type="radio" name="<?php echo OSEC_PLUGIN_NAME; ?>_type"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_type_donation"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_type_donation"><?php esc_html_e(
                        'Donation Based',
                        'open-source-event-calendar'
                    ); ?></label>
            </td>
        </tr>
        <tr>
            <td>
            </td>
            <td>
                <small>
                    <?php esc_html_e(
                        "The price for this event's first ticket will be taken from the Cost field above.",
                        'open-source-event-calendar'
                    ); ?>
                </small>
            </td>
        </tr>
        <tr>
            <td>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_quantity">
                    <?php esc_html_e('Quantity', 'open-source-event-calendar'); ?>:
                </label>
            </td>
            <td>
                <input type="text" name="<?php echo OSEC_PLUGIN_NAME; ?>_quantity"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_quantity"/>
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <?php esc_html_e('Include Fee in Price', 'open-source-event-calendar'); ?>:
                </label>
            </td>
            <td>
                <input type="radio" name="<?php echo OSEC_PLUGIN_NAME; ?>_fee_in_price"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_add_fee"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_add_fee"><?php esc_html_e(
                        'Add Service Fee on top of price',
                        'open-source-event-calendar'
                    ); ?></label>
                <input type="radio" name="<?php echo OSEC_PLUGIN_NAME; ?>_fee_in_price"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_include_fee"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_include_fee"><?php esc_html_e(
                        'Include Service fee in price',
                        'open-source-event-calendar'
                    ); ?></label>
            </td>
        </tr>
        <tr>
            <td>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>">
                    <?php esc_html_e('Payment Options', 'open-source-event-calendar'); ?>:
                </label>
            </td>
            <td>
                <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_payment_paypal"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_payment_paypal"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_payment_paypal"><?php esc_html_e('Paypal', 'open-source-event-calendar'); ?></label>
                <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_payment_google"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_payment_google"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_payment_google"><?php esc_html_e(
                        'Google Checkout',
                        'open-source-event-calendar'
                    ); ?></label>
                <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_payment_check"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_payment_check"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_payment_check"><?php esc_html_e('Check', 'open-source-event-calendar'); ?></label>
                <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_payment_cash"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_payment_cash"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_payment_cash"><?php esc_html_e('Cash', 'open-source-event-calendar'); ?></label>
                <input type="checkbox" name="<?php echo OSEC_PLUGIN_NAME; ?>_payment_invoice"
                       id="<?php echo OSEC_PLUGIN_NAME; ?>_payment_invoice"/>
                <label for="<?php echo OSEC_PLUGIN_NAME; ?>_payment_invoice"><?php esc_html_e(
                        'Send an Invoice',
                        'open-source-event-calendar'
                    ); ?></label>
            </td>
        </tr>
        </tbody>
    </table>
</div>
