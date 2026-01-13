<?php

namespace Osec\Twig;

use Osec\App\Controller\AppendContentController;
use Osec\App\Model\Date\DT;
use Osec\App\Model\PostTypeEvent\Event;
use Osec\App\View\Event\EventAvatarView;
use Osec\App\View\Event\EventTimeView;
use Osec\Bootstrap\App;
use Osec\Exception\BootstrapException;
use Osec\Exception\Exception;
use Osec\Theme\ThemeLoader;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * The extension class used by twig..
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Twig_Ai1ec_Extension
 * @author     Time.ly Network Inc.
 */
class TwigExtension extends AbstractExtension
{
    /**
     * @var App
     */
    protected App $app;

    /**
     * Get HTML markup for the post's "avatar" image according conditional
     * fallback model.
     *
     * Accepts an ordered array of named avatar $fallbacks. Also accepts a string
     * of space-separated classes to add to the default classes.
     *
     * @param  Event  $event  The event to get the avatar for
     * @param  array|null  $fallback_order  Order of fallback in searching for
     *                                     images, or null to use default
     * @param  string  $classes  A space-separated list of CSS classes
     *                                         to apply to the outer <div> element.
     * @param  bool  $wrap_permalink  Whether to wrap the element in a link
     *                                        to the event details page.
     *
     * @return  string                   String of HTML if image is found
     */
    public static function avatar(
        Event $event,
        $fallback_order = null,
        $classes = '',
        $wrap_permalink = true
    ) {
        return EventAvatarView::factory($event->get('app'))->get_event_avatar(
            $event,
            $fallback_order,
            $classes,
            $wrap_permalink
        );
    }

    /*
    (non-PHPdoc)
     * @see Twig_Extension::getFunctions()
     */

    /**
     * Get URL for avatar.
     *
     * Accepts an ordered array of named avatar $fallbacks.
     *
     * @param  Event  $event  The event to get the avatar for.
     * @param  array|null  $fallback_order  Order of fallback in searching for
     *                                     images, or null to use default.
     *
     * @return  string                   URL if image is found.
     */
    public static function avatar_url(Event $event, $fallback_order = null)
    {
        return EventAvatarView::factory($event->get('app'))->get_event_avatar_url(
            $event,
            $fallback_order
        );
    }

    /**
     * Check if provided value is a string.
     *
     * @param  mixed  $var  Suspected string
     *
     * @return bool True if it is a string, false otherwise.
     */
    public static function is_string(mixed $variable)
    {
        return is_string($variable);
    }

    /**
     * Convert an hour to an DT object.
     *
     * @param  int  $hour
     *
     * @return DT
     */
    public static function hour_to_datetime($hour)
    {
        $ret = new DT('now', 'sys.default');

        return $ret->set_time($hour, 0, 0);
    }

    /**
     * Get the name of the weekday.
     *
     * @param  int  $unix_timestamp
     *
     * @return string
     */
    public static function weekday($unix_timestamp)
    {
        $ret = new DT($unix_timestamp);

        return $ret->format_i18n('D');
    }

    /**
     * Get the name of the day.
     *
     * @param  int  $unix_timestamp
     *
     * @return string
     */
    public static function day($unix_timestamp)
    {
        $ret = new DT($unix_timestamp);

        return $ret->format_i18n('j');
    }

    /**
     * Get the name of the month.
     *
     * @param  int  $unix_timestamp
     *
     * @return string
     */
    public static function month($unix_timestamp)
    {
        $ret = new DT($unix_timestamp);

        return $ret->format_i18n('M');
    }

    /**
     * Get the date's year
     *
     * @param  int  $unix_timestamp
     *
     * @return string
     */
    public static function year($unix_timestamp)
    {
        $ret = new DT($unix_timestamp);

        return $ret->format_i18n('Y');
    }

    /**
     * Internationalize the given UNIX timestamp with the given format string.
     *
     * @param  int  $unix_timestamp
     * @param  string  $format
     *
     * @return string
     */
    public static function date_i18n($unix_timestamp, $format)
    {
        $ret = new DT($unix_timestamp);

        return $ret->format_i18n($format);
    }

    /**
     * Truncate a string after $length characters, appending $read_more string
     * at end of truncation.
     *
     * @param  number  $length  Length to truncate string to.
     * @param  string  $read_more  What string to append if truncated.
     * @param  string  $html_entities  Whether to treat input string as HTML with
     *                             possible &asdf; entities
     *
     * @return string
     */
    public static function truncate($str, $length = 35, $read_more = '...', $html_entities = true): string
    {
        // Truncate multibyte encodings differently, if supported.
        // First decode entities if requested.
        if ($html_entities) {
            $str = html_entity_decode((string)$str, ENT_QUOTES, 'UTF-8');
        }
        // Truncate string.
        $str = mb_strimwidth((string)$str, 0, $length, $read_more, 'UTF-8');
        // Reencode entities if requested.
        if ($html_entities) {
            $str = htmlentities($str, ENT_QUOTES, 'UTF-8');
        }

        return $str;
    }

    /**
     * Generate a timespan HTML block for an event.
     *
     * @param  Event  $event  Event to generate timespan for.
     * @param  string  $start_date_display  Start date display format.
     *
     * @return string Rendered HTML timespan block.
     */
    public static function timespan(Event $event, $start_date_display = 'long')
    {
        return EventTimeView::factory($event->get('app'))
                            ->get_timespan_html($event, $start_date_display);
    }

    /**
     * Inject the registry object.
     *
     * @param  App  $app
     */
    public function set_registry(App $app): void
    {
        $this->app = $app;
    }

    public function getFunctions()
    {
        return [
            'wp_nonce_field'               => new TwigFunction('wp_nonce_field', [$this, 'wp_nonce_field']),
            'do_meta_boxes'                => new TwigFunction('do_meta_boxes', [$this, 'do_meta_boxes']),
            'fb'                           => new TwigFunction('fb', [$this, 'fb']),
            'osec_disable_content_output' => new TwigFunction(
                'osec_disable_content_output',
                [
                    $this,
                    'osec_disable_content_output',
                ]
            ),
        ];
    }

    /**
     * Twig callback - return a list of filters registered by this extension.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', $this->truncate(...)),
            new TwigFilter('timespan', $this->timespan(...)),
            new TwigFilter('avatar', $this->avatar(...)),
            new TwigFilter('avatar_url', $this->avatar_url(...)),
            new TwigFilter('hour_to_datetime', $this->hour_to_datetime(...)),
            new TwigFilter('weekday', $this->weekday(...)),
            new TwigFilter('day', $this->day(...)),
            new TwigFilter('month', $this->month(...)),
            new TwigFilter('year', $this->year(...)),
            new TwigFilter('theme_img_url', $this->theme_img_url(...)),
        ];
    }

    /**
     * Twig callback - return a list of tests registered by this extension.
     *
     * @return array
     */
    public function getTests()
    {
        return [new TwigTest('string', $this->is_string(...))];
    }

    /**
     * Debug function to be used in twig templates with Firebug/FirePHP
     */
    public function fb(mixed $obj): void
    {
        if (function_exists('fb')) {
            /** @noinspection PhpUndefinedFunctionInspection */
            fb($obj);
        }
    }

    /**
     * Get URL of the given image file in the calendar theme's directory.
     *
     * @param $image
     *
     * @return string
     * @throws BootstrapException
     * @throws Exception
     */
    public function theme_img_url($image)
    {
        return ThemeLoader::factory($this->app)->get_file($image, [], false)->get_url();
    }

    /**
     * Meta-Box template function
     *
     * @since 2.5.0
     *
     * @param  string|object  $screen  Screen identifier
     *
     * @param  string  $context  box context
     * @param  mixed  $object  gets passed to the box callback function as first parameter
     *
     * @return void number of meta_boxes
     */
    public function do_meta_boxes($screen, $context, mixed $obj): void
    {
        do_meta_boxes($screen, $context, $obj);
    }

    /**
     * Retrieve or display nonce hidden field for forms.
     *
     * The nonce field is used to validate that the contents of the form came from
     * the location on the current site and not somewhere else. The nonce does not
     * offer absolute protection, but should protect against most cases. It is very
     * important to use nonce field in forms.
     *
     * The $action and $name are optional, but if you want to have better security,
     * it is strongly suggested to set those two parameters. It is easier to just
     * call the function without any parameters, because validation of the nonce
     * doesn't require any parameters, but since crackers know what the default is
     * it won't be difficult for them to find a way around your nonce and cause
     * damage.
     *
     * The input name will be whatever $name value you gave. The input value will be
     * the nonce creation value.
     *
     * @since 2.0.4
     *
     * @param  string  $name  Optional. Nonce name.
     * @param  bool  $referer  Optional, default true. Whether to set the referer field for validation.
     * @param  bool  $echo  Optional, default true. Whether to display or return hidden form field.
     *
     * @param  string  $action  Optional. Action name.
     *
     * @return string Nonce field.
     * @package WordPress
     */
    public function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true)
    {
        return wp_nonce_field($action, $name, $referer, false);
    }

    /*
    (non-PHPdoc)
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'osec';
    }

    /**
     * Hooks into the_content filter to disable its output.
     *
     * @return void Method does not return.
     */
    public function osec_disable_content_output()
    {
        AppendContentController::factory($this->app)->set_append_content(false);
    }
}
