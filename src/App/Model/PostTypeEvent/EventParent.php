<?php

namespace Osec\App\Model\PostTypeEvent;

use Osec\App\Controller\AccessControl;
use Osec\App\Controller\TrashController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\Date\UIDateFormats;
use Osec\App\View\RepeatRuleToText;
use Osec\Bootstrap\App;
use Osec\Bootstrap\OsecBaseClass;
use Osec\Cache\CacheMemory;
use stdClass;
use WP_Post;

/**
 * Class which represnt event parent/child relationship.
 *
 * @since        2.0
 * @author       Time.ly Network, Inc.
 * @package PostTypeEvent
 * @replaces Ai1ec_Event_Parent
 */
class EventParent extends OsecBaseClass
{
    protected const POST_META_PARENT_KEY = '_osec_parent';

    public static function add_actions(App $app, bool $is_admin)
    {
        // If editing a child instance.
        if (basename((string)$_SERVER['SCRIPT_NAME']) === 'post.php') {
            add_action(
                'admin_action_editpost',
                function () use ($app) {
                    self::factory($app)->admin_init_post();
                }
            );

            // Display_trash_link
            add_filter(
                'user_has_cap',
                function ($allcaps, $caps, $args, $user) use ($app) {
                    return TrashController::factory($app)->display_trash_link($allcaps, $caps, $args, $user);
                },
                10,
                4
            );
        }

        add_action(
            'post_row_actions',
            function ($actions, $post) use ($app) {
                return self::factory($app)->post_row_actions($actions, $post);
            },
            10,
            2
        );
    }

    /**
     * admin_init_post method
     *
     * Bind to admin_action_editpost action to override default save
     * method when user is editing single instance.
     * New post is created with some fields unset.
     */
    public function admin_init_post(): void
    {
        if (
            isset($_POST['osec_instance_id']) &&
            isset($_POST['action']) &&
            'editpost' === $_POST['action']
        ) {
            $old_post_id = $_POST['post_ID'];
            $instance_id = $_POST['osec_instance_id'];
            $post_id     = EventEditing::factory($this->app)->create_duplicate_post();
            if (false !== $post_id) {
                $this->_handle_instances(
                    new Event($this->app, $post_id),
                    new Event($this->app, $old_post_id),
                    $instance_id
                );
                EventInstance::factory($this->app)->clean(
                    $old_post_id,
                    $instance_id
                );
                $location = add_query_arg(
                    'message',
                    1,
                    get_edit_post_link($post_id, 'url')
                );
                wp_redirect(
                    apply_filters(
                        'redirect_post_location',
                        $location,
                        $post_id
                    )
                );
                exit(0);
            }
        }
    }

    /**
     * Handles instances saving and switching if needed. If original event
     * and created event have different start dates proceed in old style
     * otherwise find next instance, switch original start date to next
     * instance start date. If there are no next instances mark event as
     * non recurring. Filter also exception dates if are past.
     *
     * @param  Event  $created_event  Created event object.
     * @param  Event  $original_event  Original event object.
     *
     * @return void Method does not return.
     */
    protected function _handle_instances(
        Event $created_event,
        Event $original_event,
        $instance_id
    ) {
        $ce_start = $created_event->get('start');
        $oe_start = $original_event->get('start');
        if (
            $ce_start->format() !== $oe_start->format()
        ) {
            $this->add_exception_date(
                $original_event->get('post_id'),
                $ce_start
            );

            return;
        }
        $next_instance = $this->_find_next_instance(
            $original_event->get('post_id'),
            $instance_id
        );
        if ( ! $next_instance) {
            $original_event->set('recurrence_rules', null);
            $original_event->save(true);

            return;
        }
        $original_event->set('start', new DT($next_instance->get('start')));
        $original_event->set('end', new DT($next_instance->get('end')));
        $edates = $this->_filter_exception_dates($original_event);
        $original_event->set('exception_dates', implode(',', $edates));
        $recurrence_rules = $original_event->get('recurrence_rules');
        $rules_info       = RepeatRuleToText::factory($this->app)
                                            ->build_recurrence_rules_array($recurrence_rules);
        if (isset($rules_info['COUNT'])) {
            $next_instances_count = $this->_count_next_instances(
                $original_event->get('post_id'),
                $instance_id
            );
            $rules_info['COUNT']  = (int)$next_instances_count + count($edates);
            $rules                = '';
            if ($rules_info['COUNT'] <= 1) {
                $rules_info = [];
            }
            foreach ($rules_info as $key => $value) {
                $rules .= $key . '=' . $value . ';';
            }
            $original_event->set(
                'recurrence_rules',
                $rules
            );
        }
        $original_event->save(true);
    }

    /**
     * add_exception_date method
     *
     * Add exception (date) to event.
     *
     * @param  int  $post_id  Event edited post ID
     * @param  mixed  $date  Parseable date representation to exclude
     *
     * @return bool Success
     */
    public function add_exception_date($post_id, DT $date)
    {
        $event      = new Event($this->app, $post_id);
        $dates_list = explode(',', (string)$event->get('exception_dates'));
        if (empty($dates_list[0])) {
            unset($dates_list[0]);
        }
        $date->set_time(0, 0, 0);
        $dates_list[] = $date->format(
            'Ymd\THis\Z'
        );
        $event->set('exception_dates', implode(',', $dates_list));

        return $event->save(true);
    }

    /**
     * Returns next instance.
     *
     * @param  int  $post_id  Post ID.
     * @param  int  $instance_id  Instance ID.
     *
     * @return null|Event Result.
     */
    protected function _find_next_instance($post_id, $instance_id)
    {
        $dbi              = $this->app->db;
        $table_instances  = $dbi->get_table_name(OSEC_DB__INSTANCES);
        $table_posts      = $dbi->get_table_name('posts');
        $query            = $dbi->prepare(
            'SELECT i.id FROM ' . $table_instances . ' i JOIN ' .
            $table_posts . ' p ON (p.ID = i.post_id) ' .
            'WHERE i.post_id = %d AND i.id > %d ' .
            'AND p.post_status = \'publish\' ' .
            'ORDER BY id ASC LIMIT 1',
            $post_id,
            $instance_id
        );
        $next_instance_id = $dbi->get_var($query);
        if ( ! $next_instance_id) {
            return null;
        }

        return EventSearch::factory($this->app)
                          ->get_event($post_id, $next_instance_id);
    }

    /**
     * Filters past or out of range exception dates.
     *
     * @param  Event  $event  Event.
     *
     * @return array Filtered exception dates.
     */
    protected function _filter_exception_dates(Event $event)
    {
        $start           = (int)$event->get('start')->format();
        $exception_dates = explode(',', (string)$event->get('exception_dates'));
        $dates           = [];
        foreach ($exception_dates as $date) {
            $ex_date = new DT($date);
            $ex_date = (int)$ex_date->format();
            if ($ex_date > $start) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * Counts future instances.
     *
     * @param  int  $post_id  Post ID.
     * @param  int  $instance_id  Instance ID.
     *
     * @return int Result.
     */
    protected function _count_next_instances($post_id, $instance_id)
    {
        $dbi             = $this->app->db;
        $table_instances = $dbi->get_table_name(OSEC_DB__INSTANCES);
        $table_posts     = $dbi->get_table_name('posts');
        $query           = $dbi->prepare(
            'SELECT COUNT(i.id) FROM ' . $table_instances . ' i JOIN ' .
            $table_posts . ' p ON (p.ID = i.post_id) ' .
            'WHERE i.post_id = %d AND i.id > %d ' .
            'AND p.post_status = \'publish\'',
            $post_id,
            $instance_id
        );

        return (int)$dbi->get_var($query);
    }

    /**
     * Inject base event edit link for modified instances
     *
     * Modified instances are events, belonging to some parent having recurrence
     * rule, and having some of it's properties altered.
     *
     * @param  array  $actions  List of defined actions
     * @param  stdClass|WP_Post  $post
     *     Instance being rendered (WP_Post class instance in WP 3.5+ <-- ??)
     *
     * @return array Optionally modified $actions list
     */
    public function post_row_actions(array $actions, $post)
    {
        if (AccessControl::is_our_post_type($post)) {
            $parent_post_id = $this->event_parent($post->ID);
            if (
                $parent_post_id &&
                null !== ($parent_post = get_post($parent_post_id)) &&
                isset($parent_post->post_status) &&
                'trash' !== $parent_post->post_status
            ) {
                $parent_link             = get_edit_post_link(
                    $parent_post_id,
                    'display'
                );
                $actions['ai1ec_parent'] = sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    wp_nonce_url($parent_link),
                    sprintf(
                        __('Edit &#8220;%s&#8221;', 'open-source-event-calendar'),
                        apply_filters(
                            'the_title',
                            $parent_post->post_title,
                            $parent_post->ID
                        )
                    ),
                    __('Base Event', 'open-source-event-calendar')
                );
            }
        }

        return $actions;
    }

    /**     *
     * Get/set event parent
     *
     * @param  int  $event_id  ID of checked event
     * @param  int  $parent_id  ID of new parent [optional=NULL, acts as getter]
     * @param  int  $instance_id  ID of old instance id
     *
     * @return int|bool Value depends on mode:
     *     Getter: {@see self::get_parent_event()} for details
     *     Setter: true on success.
     */
    public function event_parent(
        $event_id,
        $parent_id = null,
        $instance_id = null
    ) {
        if (null === $parent_id) {
            return $this->get_parent_event($event_id);
        }
        $meta_value = json_encode(
            [
                'created'  => UIDateFormats::factory($this->app)->current_time(),
                'instance' => $instance_id,
            ]
        );

        return add_post_meta($event_id, self::POST_META_PARENT_KEY, $meta_value, true);
    }

    /**
     * Get parent ID for given event
     *
     * @param  int  $current_id  Current event ID
     *
     * @return int|bool ID of parent event or bool(false)
     */
    public function get_parent_event($current_id)
    {
        static $parents = null;
        if (null === $parents) {
            $parents = CacheMemory::factory($this->app);
        }
        $current_id = (int)$current_id;
        if (null === ($parent_id = $parents->get($current_id))) {
            $db = $this->app->db;
            /* @var $db |Ai1ec_Dbi */
            $query  = '
				SELECT parent.ID, parent.post_status
				FROM
					' . $db->get_table_name('posts') . ' AS child
					INNER JOIN ' . $db->get_table_name('posts') . ' AS parent
						ON ( parent.ID = child.post_parent )
				WHERE child.ID = ' . $current_id;
            $parent = $db->get_row($query);
            if (
                empty($parent) ||
                'trash' === $parent->post_status
            ) {
                $parent_id = false;
            } else {
                $parent_id = $parent->ID;
            }
            $parents->set($current_id, $parent_id);
            unset($query);
        }

        return $parent_id;
    }

    /**
     * Returns a list of modified (children) event objects
     *
     * @param  int  $parent_id  ID of parent event
     * @param  bool  $include_trash  Includes trashed when `true` [optional=false]
     *
     * @return array List (might be empty) of Event objects
     */
    public function get_child_event_objects(
        $parent_id,
        $include_trash = false
    ) {
        $db        = $this->app->db;
        $parent_id = (int)$parent_id;
        $sql_query = 'SELECT ID FROM ' . $db->get_table_name('posts') .
                     ' WHERE post_parent = ' . $parent_id;
        $children  = (array)$db->get_col($sql_query);
        $objects   = [];
        foreach ($children as $child_id) {
            try {
                $instance = new Event($this->app, $child_id);
                if (
                    $include_trash ||
                    'trash' !== $instance->get('post')->post_status
                ) {
                    $objects[$child_id] = $instance;
                }
            } catch (EventNotFoundException) {
                // ignore
            }
        }

        return $objects;
    }
}
