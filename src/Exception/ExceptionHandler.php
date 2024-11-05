<?php

namespace Osec\Exception;

use Osec\App\Model\Notifications\NotificationAdmin;
use Osec\Http\Response\ResponseHelper;
use Throwable;

/**
 * Handles exception and errors
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Exception_Handler
 * @author     Time.ly Network Inc.
 */
class ExceptionHandler
{

    /**
     * @var string The option for the messgae in the db
     */
    public const DB_DEACTIVATE_MESSAGE = 'ai1ec_deactivate_message';

    /**
     * @var string The GET parameter to reactivate the plugin
     */
    public const DB_REACTIVATE_PLUGIN = 'ai1ec_reactivate_plugin';

    /**
     * @var callable|null Previously set exception handler if any
     */
    protected $_prev_ex_handler;

    /**
     * @var callable|null Previously set error handler if any
     */
    protected $_prev_er_handler;

    /**
     * @var string The message to display in the admin notice
     */
    protected $_message;

    /**
     * @var array Mapped list of errors that are non-fatal, to be ignored
     *            in production.
     */
    protected $_nonfatal_errors = null;

    /**
     * Constructor accepts names of classes to be handled
     *
     * @param  string  $_exception_class  Name of exceptions base class to handle
     * @param  string  $_error_exception_class  Name of errors base class to handle
     *
     * @return void Constructor newer returns
     */
    public function __construct(protected $_exception_class, protected $_error_exception_class)
    {
        $this->_nonfatal_errors = [
            E_USER_WARNING => true,
            E_WARNING      => true,
            E_USER_NOTICE  => true,
            E_NOTICE       => true,
            E_STRICT       => true,
        ];
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            // wrapper `constant( 'XXX' )` is used to avoid compile notices
            // on earlier PHP versions.
            $this->_nonfatal_errors[ constant('E_DEPRECATED') ] = true;
            $this->_nonfatal_errors[ constant('E_USER_DEPRECATED') ] = true;
        }
    }

    /**
     * Store exception handler that was previously set
     *
     * @param $handler
     *
     * @return void Method does not return
     */
    public function set_prev_ex_handler($handler)
    {
        $this->_prev_ex_handler = $handler;
    }

    /**
     * Store error handler that was previously set
     *
     * @param $handler
     * @return void Method does not return
*/
    public function set_prev_er_handler($handler)
    {
        $this->_prev_er_handler = $handler;
    }

    /**
     * Global exceptions handling method
     *
     * @param  Exception  $exception  Previously thrown exception to handle
     *
     * @return void Exception handler is not expected to return
     */
    public function handle_exception(Throwable $exception)
    {
        if (defined('OSEC_DEBUG') && true === OSEC_DEBUG) {
            die ('<pre>'.print_r($exception, true).'</pre>');
        }
        // if it's something we handle, handle it
        throw $exception;
        $backtrace = $this->_get_backtrace($exception);
        if ($exception instanceof $this->_exception_class) {
            // check if it's a plugin instead of core
            $disable_addon = $this->is_caused_by_addon($exception);
            $message = method_exists($exception, 'get_html_message')
                ? $exception->get_html_message()
                : $exception->getMessage();
            $message = '<p>'.$message.'</p>';
            if ($exception->display_backtrace()) {
                $message .= $backtrace;
            }
            if (null !== $disable_addon) {
                include_once ABSPATH.'wp-admin/includes/plugin.php';
                // deactivate the plugin. Fire handlers to hide options.
                deactivate_plugins($disable_addon);
                global $osec_app;
                NotificationAdmin::factory($osec_app)->store(
                    $this->get_disabled_line($disable_addon).$message,
                    'error',
                    2,
                    [NotificationAdmin::RCPT_ADMIN],
                    true
                );
                $this->redirect($exception->get_redirect_url());
            } else {
                // check if it has a methof for deatiled html
                $this->soft_deactivate_plugin($message);
            }

        } // if it's a PHP error in our plugin files, deactivate and redirect
        else {
            if ($exception instanceof $this->_error_exception_class) {
                $this->soft_deactivate_plugin(
                    $exception->getMessage().$backtrace
                );
            }
        }
        // if another handler was set, let it handle the exception
        if (is_callable($this->_prev_ex_handler)) {
            call_user_func($this->_prev_ex_handler, $exception);
        }
    }

    /**
     * Get HTML code with backtrace information for given exception.
     *
     *
     * @return string HTML code.
     */
    protected function _get_backtrace(Exception $exception)
    {

        $backtrace = '';
        $trace = nl2br($exception->getTraceAsString());
        $ident = sha1($trace);
        if ( ! empty($trace)) {
            $request_uri = $_SERVER[ 'REQUEST_URI' ];
            $button_label = __('Toggle error details', OSEC_TXT_DOM);
            $title = __('Error Details:', OSEC_TXT_DOM);
            $backtrace = <<<JAVASCRIPT
			<script type="text/javascript">
			jQuery( function($) {
				$( "a[data-rel='$ident']" ).click( function() {
					jQuery( "#ai1ec-error-$ident" ).slideToggle( "fast" );
					return false;
				});
			});
			</script>
			<blockquote id="ai1ec-error-$ident" style="display: none;">
				<strong>$title</strong>
				<p>$trace</p>
				<p>Request Uri: $request_uri</p>
			</blockquote>
			<a href="#" data-rel="$ident" class="button">$button_label</a>
JAVASCRIPT;
        }

        return $backtrace;
    }

    /**
     * Return add-on, which caused the exception or null if it was Core.
     *
     * Relies on `plugin_to_disable` method which may be implemented by
     * an exception. If it returns non empty value - it is returned.
     *
     * @param  Exception  $exception  Actual exception which was thrown.
     *
     * @return string|null Add-on identifier (plugin url), or null.
     */
    public function is_caused_by_addon(Exception $exception)
    {
        $addon = null;
        if (method_exists($exception, 'plugin_to_disable')) {
            $addon = $exception->plugin_to_disable();
            if (empty($addon)) {
                $addon = null;
            }
        }
        if (null === $addon) {
            $position = strlen(dirname(OSEC_PATH));
            $length = strlen(OSEC_PLUGIN_NAME);
            $trace_list = $exception->getTrace();
            array_unshift(
                $trace_list,
                ['file' => $exception->getFile()]
            );
            foreach ($trace_list as $trace) {
                if (
                    ! isset($trace[ 'file' ]) ||
                    ! isset($trace[ 'file' ][ $position ])
                ) {
                    continue;
                }
                $file = substr(
                    $trace[ 'file' ],
                    $position,
                    strpos($trace[ 'file' ], '/', $position) - $position
                );
                if (0 === strncmp(OSEC_PLUGIN_NAME, $file, $length)) {
                    if (OSEC_PLUGIN_NAME !== $file) {
                        $addon = $file.'/'.$file.'.php';
                    }
                }
            }
        }
        if ('core' === strtolower((string) $addon)) {
            return null;
        }

        return $addon;
    }

    /**
     * Get tag-line for disabling.
     *
     * Extracts plugin name from file.
     *
     * @param  string  $addon  Name of disabled add-on.
     *
     * @return string Message to display before full trace.
     */
    public function get_disabled_line($addon)
    {
        $file = dirname(OSEC_PATH).$addon;
        $line = '';
        if (
            is_file($file) &&
            preg_match(
                '|Plugin Name:\s*(.+)|',
                file_get_contents($file),
                $matches
            )
        ) {
            $line = '<p><strong>'.
                    sprintf(
                        __('The add-on "%s" has been disabled due to an error:'),
                        __(trim($matches[ 1 ]), dirname($addon))
                    ).
                    '</strong></p>';
        }

        return $line;
    }

    /**
     * Redirect the user either to the front page or the dashbord page
     *
     * @return void Method does not return
     */
    protected function redirect($suggested_url = null)
    {
        $url = get_site_url();
        if (is_admin()) {
            $url = $suggested_url ?? get_admin_url();
        }
        ResponseHelper::redirect($url);
    }

    /**
     * Perform what's needed to deactivate the plugin softly
     *
     * @param  string  $message  Error message to be displayed to admin
     *
     * @return void Method does not return
     */
    protected function soft_deactivate_plugin($message)
    {
        add_option(self::DB_DEACTIVATE_MESSAGE, $message);
        $this->redirect();
    }

    /**
     * Throws an ErrorException if the error comes from our plugin
     *
     * @param  int  $errno  Error level as integer
     * @param  string  $errstr  Error message raised
     * @param  string  $errfile  File in which error was raised
     * @param  string  $errline  Line in which error was raised
     * @param  array  $errcontext  Error context symbols table copy
     *
     * @return boolean|void Nothing when error is ours, false when no
     *                      other handler exists
     * @throws ErrorException If error originates from within Ai1EC
     *
     */
    public function handle_error(
        $errno,
        $errstr,
        $errfile,
        $errline,
        $errcontext = []
    ) {
        // if the error is not in our plugin, let PHP handle things.
        $position = strpos($errfile, (string) OSEC_PLUGIN_NAME);
        if (false === $position) {
            if (is_callable($this->_prev_er_handler)) {
                return call_user_func_array(
                    $this->_prev_er_handler,
                    func_get_args()
                );
            }

            return false;
        }
        // do not disable plugin in production if the error is rather low
        $isVendor = 'vendor' === substr($errfile, strlen(OSEC_PATH), 6);
        if (
            isset($this->_nonfatal_errors[ $errno ])
            && (
                // Non fatal /vendor errors should not become fatal here.
                ( ! defined('OSEC_DEBUG') || false === OSEC_DEBUG)
                || $isVendor && defined('OSEC_DEBUG') && OSEC_DEBUG && defined('OSEC_DEBUG_VENDOR') && OSEC_DEBUG_VENDOR === false
            )
        ) {
            $message = sprintf(
                'Osec: %s @ %s:%d #%d',
                $errstr,
                $errfile,
                $errline,
                $errno
            );

            return error_log($message, 0);
        }
        // let's get the plugin folder
        $tail = substr($errfile, $position);
        $exploded = explode(DIRECTORY_SEPARATOR, $tail);
        $plugin_dir = $exploded[ 0 ];
        // if the error doesn't belong to core, throw the plugin exception to trigger disabling
        // of the plugin in the exception handler
        if (OSEC_PLUGIN_NAME !== $plugin_dir) {
            $exc = implode(
                '', array_map(
                    $this->return_first_char(...),
                    explode('-', $plugin_dir)
                )
            );

            // TODO
            //  What kind of Exception handling we have here?

            // all plugins should implement an exception based on this convention
            // which is the same convention we use for constants, only with just first letter uppercase
            $exc = str_replace('aioec', 'Ai1ec', $exc).'_Exception';
            if (class_exists($exc)) {
                $message = sprintf(
                    'Osec: %s @ %s:%d #%d',
                    $errstr,
                    $errfile,
                    $errline,
                    $errno
                );
                throw new $exc($message);
            }
        }
        throw new ErrorException(
            $errstr,
            $errno,
            0,
            $errfile,
            $errline
        );
    }

    public function return_first_char($name)
    {
        return $name[ 0 ];
    }

    /**
     * Perform what's needed to reactivate the plugin
     *
     * @return boolean Success
     */
    public function reactivate_plugin()
    {
        return delete_option(self::DB_DEACTIVATE_MESSAGE);
    }

    /**
     * Get message to be displayed to admin if any
     *
     * @return string|boolean Error message or false if plugin is not disabled
     */
    public function get_disabled_message()
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
                self::DB_DEACTIVATE_MESSAGE
            )
        );
        if (is_object($row)) {
            return $row->option_value;
        } else { // option does not exist, so we must cache its non-existence
            return false;
        }
    }

    /**
     * Add an admin notice
     *
     * @param  string  $message  Message to be displayed to admin
     *
     * @return void Method does not return
     */
    public function show_notices($message)
    {
        // save the message to use it later
        $this->_message = $message;
        add_action('admin_notices', $this->render_admin_notice(...));
    }

//  /**
//   * Had to add it as var_dump was locking my browser.
//   *
//   * Taken from
//   * http://www.leaseweblabs.com/2013/10/smart-alternative-phps-var_dump-function/
//   *
//   * @param int $strlen
//   * @param int $width
//   * @param int $depth
//   * @param int $i
//   * @param array $objects
//   *
//   * @return string
//   */
//  public function var_debug(
//    mixed $variable,
//          $strlen = 400,
//          $width = 25,
//          $depth = 10,
//          $i = 0,
//          &$objects = []
//  ) {
//    $search = ["\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v"];
//    $replace = ['\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v'];
//    $string = '';
//
//    switch (gettype($variable)) {
//      case 'boolean' :
//        $string .= $variable ? 'true' : 'false';
//        break;
//      case 'integer' :
//        $string .= $variable;
//        break;
//      case 'double' :
//        $string .= $variable;
//        break;
//      case 'resource' :
//        $string .= '[resource]';
//        break;
//      case 'NULL' :
//        $string .= "NULL";
//        break;
//      case 'unknown type' :
//        $string .= '???';
//        break;
//      case 'string' :
//        $len = strlen($variable);
//        $variable = str_replace(
//          $search,
//          $replace,
//          substr($variable, 0, $strlen),
//          $count);
//        $variable = substr($variable, 0, $strlen);
//        if ($len < $strlen) {
//          $string .= '"' . $variable . '"';
//        }
//        else {
//          $string .= 'string(' . $len . '): "' . $variable . '"...';
//        }
//        break;
//      case 'array' :
//        $len = count($variable);
//        if ($i == $depth) {
//          $string .= 'array(' . $len . ') {...}';
//        }
//        elseif (!$len) {
//          $string .= 'array(0) {}';
//        }
//        else {
//          $keys = array_keys($variable);
//          $spaces = str_repeat(' ', $i * 2);
//          $string .= "array($len)\n" . $spaces . '{';
//          $count = 0;
//          foreach ($keys as $key) {
//            if ($count == $width) {
//              $string .= "\n" . $spaces . "  ...";
//              break;
//            }
//            $string .= "\n" . $spaces . "  [$key] => ";
//            $string .= $this->var_debug(
//              $variable[$key],
//              $strlen,
//              $width,
//              $depth,
//              $i + 1,
//              $objects
//            );
//            $count++;
//          }
//          $string .= "\n" . $spaces . '}';
//        }
//        break;
//      case 'object':
//        $id = array_search($variable, $objects, TRUE);
//        if ($id !== FALSE) {
//          $string .= $variable::class . '#' . ($id + 1) . ' {...}';
//        }
//        else {
//          if ($i == $depth) {
//            $string .= $variable::class . ' {...}';
//          }
//          else {
//            $id = array_push($objects, $variable);
//            $array = ( array ) $variable;
//            $spaces = str_repeat(' ', $i * 2);
//            $string .= $variable::class . "#$id\n" . $spaces . '{';
//            $properties = array_keys($array);
//            foreach ($properties as $property) {
//              $name = str_replace("\0", ':', trim($property));
//              $string .= "\n" . $spaces . "  [$name] => ";
//              $string .= $this->var_debug(
//                $array[$property],
//                $strlen,
//                $width,
//                $depth,
//                $i + 1,
//                $objects
//              );
//            }
//            $string .= "\n" . $spaces . '}';
//          }
//        }
//        break;
//    }
//
//    if ($i > 0) {
//      return $string;
//    }
//
//    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//    do {
//      $caller = array_shift($backtrace);
//    } while (
//      $caller &&
//      !isset($caller['file'])
//    );
//    if ($caller) {
//      $string = $caller['file'] . ':' . $caller['line'] . "\n" . $string;
//    }
//
//    echo nl2br(str_replace(' ', '&nbsp;', htmlentities($string)));
//  }

    /**
     * Render HTML snipped to be displayd as a notice to admin
     *
     * @hook admin_notices When plugin is soft-disabled
     *
     * @return void Method does not return
     */
    public function render_admin_notice()
    {
        $redirect_url = add_query_arg(
            self::DB_REACTIVATE_PLUGIN,
            'true',
            get_admin_url()
        );
        $label = __(
            'Open Source Event Calendar has been disabled due to an error:', OSEC_TXT_DOM);
        $message = '<div class="message error">';
        $message .= '<p><strong>'.$label.'</strong></p>';
        $message .= $this->_message;
        $message .= ' <a href="'.$redirect_url.
                    '" class="button button-primary ai1ec-dismissable">'.
                    __( 'Try reactivating plugin', OSEC_TXT_DOM );
        $message .= '</a>';
        $message .= '<p></p></div>';
        echo $message;
    }

}
