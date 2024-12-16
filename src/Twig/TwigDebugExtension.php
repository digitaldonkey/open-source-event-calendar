<?php

namespace Osec\Twig;

use Osec\App\Model\Notifications\NotificationAdmin;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

use function extension_loaded;
use function ini_get;

use const PHP_SAPI;

/**
 * Twig Debugging:
 *
 * Use the Twig standard dump() if xdebug is active.
 *  Alternatively we will use \Symfony\Component\VarDumper\VarDumper
 *  if this dev dependency is available.
 *
 *  If not We will inform admin that there will be no dump
 *  despite using dump() somewhere in twig.
 *
 * OSEC_DEBUG MUST ALSO BE TRUE. Or we will never get here.
 */
class TwigDebugExtension extends AbstractExtension
{
    public static function no_dump()
    {
        global $osec_app;
        $notification = NotificationAdmin::factory($osec_app);

        $notification->store(
            __(
                'You need to enable xdebug or run `composer install` to use Twig debug() in twig files. <br /><br />',
                'open-source-event-calendar'
            ),
            'error',
            2,
            [NotificationAdmin::RCPT_ADMIN],
            true,
        );

        return;
    }

    /**
     * @internal
     */
    public static function dump(Environment $env, $context, ...$vars)
    {
        if ( ! $env->isDebug()) {
            return;
        }

        ob_start();

        if ( ! $vars) {
            $vars = [];
            foreach ($context as $key => $value) {
                if ( ! $value instanceof Template && ! $value instanceof TemplateWrapper) {
                    $vars[$key] = $value;
                }
            }

            var_dump($vars);
        } else {
            var_dump(...$vars);
        }

        return ob_get_clean();
    }

    /**
     *
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        // @see Twig\Extension\DebugExtension
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
                                // Xdebug overloads var_dump in develop mode when html_errors is enabled
                                && str_contains(ini_get('xdebug.mode'), 'develop')
                                && (false === ini_get('html_errors') || ini_get('html_errors'))
                                || 'cli' === PHP_SAPI;

        if ($isDumpOutputHtmlSafe) {
            // If xdebug is enables we can dump fast here.
            return [
                new TwigFunction(
                    'dump',
                    [self::class, 'dump'],
                    [
                        'is_safe'           => $isDumpOutputHtmlSafe ? ['html'] : [],
                        'needs_context'     => true,
                        'needs_environment' => true,
                        'is_variadic'       => true,
                    ]
                ),
            ];
        }

        if (class_exists('\Symfony\Component\VarDumper\VarDumper')) {
            // Alternatively we will use symfoy varDumper.
            // requires composer install (includes --dev packages
            return [
                new TwigFunction('dump', ['Symfony\Component\VarDumper\VarDumper', 'dump']),
            ];
        }

        // Todo maybe we need an alternative?
        return [
            new TwigFunction('dump', [self::class, 'no_dump']),
        ];
    }
}
