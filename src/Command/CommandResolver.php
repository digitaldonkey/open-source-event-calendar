<?php

namespace Osec\Command;

use Osec\App\View\Admin\AdminPageSettings;
use Osec\App\View\Admin\AdminPageThemeOptions;
use Osec\Bootstrap\App;
use Osec\Exception\BootstrapException;
use Osec\Http\Request\RequestParser;

/**
 * The command resolver class that handles command.
 *
 * @since      2.0
 *
 * @replaces Ai1ec_Command_Resolver
 * @author     Time.ly Network Inc.
 */
class CommandResolver
{
    /**
     * @var App.
     */
    protected App $app;

    /**
     * @var CommandAbstract[] The available commands.
     */
    private array $commands = [];

    /**
     * Public constructor
     *
     * @return void
     */
    public function __construct(App $app, RequestParser $request)
    {
        $this->app = $app;

        $this->add_command(
            CompileThemes::factory($this->app, $request)
        );

        $this->add_command(
            ExportEvents::factory($this->app, $request)
        );

        $this->add_command(
            RenderEvent::factory($this->app, $request)
        );

        $this->add_command(
            RenderCalendar::factory($this->app, $request)
        );

        $this->add_command(
            ChangeTheme::factory($this->app, $request)
        );

        $this->add_command(
            SaveSettings::factory(
                $this->app,
                $request,
                [
                    'action'       => 'osec_save_settings',
                    'nonce_action' => AdminPageSettings::NONCE_ACTION,
                    'nonce_name'   => AdminPageSettings::NONCE_NAME,
                ]
            )
        );

        $this->add_command(
            SaveThemeOptions::factory(
                $this->app,
                $request,
                [
                    'action'       => 'ai1ec_save_theme_options',
                    'nonce_action' => AdminPageThemeOptions::NONCE_ACTION,
                    'nonce_name'   => AdminPageThemeOptions::NONCE_NAME,
                ]
            )
        );

        $this->add_command(
            CommandClone::factory($this->app, $request)
        );

        $request->parse();
    }

    /**
     * Add a command.
     *
     * @return self Self for calls chaining
     */
    public function add_command(object $command): self
    {
        if ( ! $command instanceof CommandAbstract) {
            throw new BootstrapException(
                esc_html($command->get_class() . ' does not implement CommandAbstract')
            );
        }
        $this->commands[] = $command;

        return $this;
    }

    /**
     * Return the command to execute or false.
     *
     * @return array [CommandAbstract]|null
     */
    public function get_commands(): array
    {
        $commands = [];
        foreach ($this->commands as $command) {
            if ($command->is_this_to_execute()) {
                $commands[] = $command;
            }
        }

        return $commands;
    }
}
