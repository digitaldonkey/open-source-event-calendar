<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="ai1ec-modal-content">
    <div class="ai1ec-modal-header">
        <h4 class="ai1ec-modal-title">
            <?php esc_html_e('Select recurrence pattern:', 'open-source-event-calendar'); ?>
        </h4>
    </div>
    <div class="ai1ec-modal-body ai1ec-form-horizontal">
        <div class="ai1ec-alert ai1ec-alert-danger ai1ec-hide"></div>
        <div class="ai1ec-lead">
            <ul class="ai1ec-nav ai1ec-nav-pills ai1ec-row">
                <li class="ai1ec-col-xs-4 ai1ec-col-sm-4 ai1ec-text-center ai1ec-freq-daily ai1ec-freq">
                    <a href="#osec_daily_content" data-toggle="ai1ec-tab">
                        <?php esc_html_e('Daily', 'open-source-event-calendar'); ?>
                    </a>
                </li>
                <li class="ai1ec-col-xs-4 ai1ec-col-sm-4 ai1ec-text-center ai1ec-freq-weekly ai1ec-freq">
                    <a href="#osec_weekly_content" data-toggle="ai1ec-tab">
                        <?php esc_html_e('Weekly', 'open-source-event-calendar'); ?>
                    </a>
                </li>
                <li class="ai1ec-col-xs-4 ai1ec-col-sm-4 ai1ec-text-center ai1ec-freq-monthly ai1ec-freq">
                    <a href="#osec_monthly_content" data-toggle="ai1ec-tab">
                        <?php esc_html_e('Monthly', 'open-source-event-calendar'); ?>
                    </a>
                </li>
                <li class="ai1ec-col-xs-4 ai1ec-col-sm-4 ai1ec-text-center ai1ec-freq-yearly ai1ec-freq">
                    <a href="#osec_yearly_content" data-toggle="ai1ec-tab">
                        <?php esc_html_e('Yearly', 'open-source-event-calendar'); ?>
                    </a>
                </li>
                <li class="ai1ec-col-xs-4 ai1ec-col-sm-4 ai1ec-text-center ai1ec-freq-custom ai1ec-freq">
                    <a href="#osec_custom_content" data-toggle="ai1ec-tab">
                        <?php esc_html_e('Custom', 'open-source-event-calendar'); ?>
                    </a>
                </li>
            </ul>
            <p></p>
        </div>

        <div class="ai1ec-tab-content" id="ai1ec-tab-content" data-active-freq="<?php echo $selected_tab; ?>">
            <div id="osec_daily_content" data-freq="daily"
                 class="ai1ec-tab-pane ai1ec-freq ai1ec-freq-daily">
                <?php echo $row_daily; ?>
            </div>
            <div id="osec_weekly_content" data-freq="weekly"
                 class="ai1ec-tab-pane ai1ec-freq ai1ec-freq-weekly">
                <?php echo $row_weekly; ?>
            </div>
            <div id="osec_monthly_content" data-freq="monthly"
                 class="ai1ec-tab-pane ai1ec-freq ai1ec-freq-monthly">
                <?php echo $row_monthly; ?>
            </div>
            <div id="osec_yearly_content" data-freq="yearly"
                 class="ai1ec-tab-pane ai1ec-freq ai1ec-freq-yearly">
                <?php echo $row_yearly; ?>
            </div>
            <div id="osec_custom_content" data-freq="custom"
                 class="ai1ec-tab-pane ai1ec-freq ai1ec-freq-custom">
                <?php echo $row_custom; ?>
            </div>
        </div>
    </div>

    <div class="ai1ec-modal-footer">
        <div class="ai1ec-form-horizontal ai1ec-text-left ai1ec-end-field">
            <div class="ai1ec-form-group">
                <label for="end-input" class="ai1ec-control-label ai1ec-col-sm-3">
                    <?php esc_html_e('End', 'open-source-event-calendar'); ?>:
                </label>
                <div class="ai1ec-col-sm-9" id="end-input" data-ending-type="<?php echo esc_attr($ending_type); ?>">
                    <?php echo esc_html($end); ?>
                </div>
            </div>

            <div id="osec_count_holder" class="ai1ec-form-group ai1ec-collapse"
                 data-toggle="false">
                <label for="osec_count" class="ai1ec-control-label ai1ec-col-sm-3">
                    <?php esc_html_e('Ending after', 'open-source-event-calendar'); ?>:
                </label>
                <div class="ai1ec-col-sm-9">
                    <?php echo esc_html($count); ?>
                </div>
            </div>

            <div id="osec_until_holder" class="ai1ec-form-group ai1ec-collapse"
                 data-toggle="false">
                <label for="osec_until-date-input"
                       class="ai1ec-control-label ai1ec-col-sm-3">
                    <?php esc_html_e('On date', 'open-source-event-calendar'); ?>:
                </label>
                <div class="ai1ec-col-sm-9">
                    <input type="text" class="ai1ec-date-input" id="osec_until-date-input">
                    <input type="hidden" name="osec_until_time" id="osec_until-time"
                           value="<?php echo ! is_null($until) && $until > 0 ? esc_attr($until) : ''; ?>">
                </div>
            </div>
        </div>
        <input type="hidden" id="osec_is_box_repeat" value="<?php echo  esc_attr($repeat); ?>">
        <button type="button" id="osec_repeat_apply"
                class="ai1ec-btn ai1ec-btn-primary ai1ec-btn-lg"
                data-loading-text="<?php echo esc_attr(
                    '<i class="ai1ec-fa ai1ec-fa-spinner ai1ec-fa-fw ai1ec-fa-spin"></i> ' .
                    __('Please wait&#8230;', 'open-source-event-calendar')
                ); ?>">
            <i class="ai1ec-fa ai1ec-fa-check ai1ec-fa-fw"></i>
            <?php esc_html_e('Apply', 'open-source-event-calendar'); ?>
        </button>
        <a id="osec_repeat_cancel"
           class="ai1ec-btn ai1ec-btn-default ai1ec-text-danger ai1ec-btn-lg">
            <i class="ai1ec-fa ai1ec-fa-undo ai1ec-fa-fw"></i
            ><?php
            esc_html_e('Cancel', 'open-source-event-calendar');
        ?></a>
    </div>
</div>
