<?php

namespace Slakbal\Slackwipe;

use Illuminate\Support\ServiceProvider;
use Slakbal\Slackwipe\Commands\cleanSlackHistory;

class SlackwipeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected $defer = false; //must be false for the routes to work

    /**
     * Bootstrap the application services.
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                                cleanSlackHistory::class,
                            ]);
        }

        $this->publishes([__DIR__.'/../config/slackwipe.php' => config_path('slackwipe.php')], 'config');
    }

    public function register()
    {
        //runtime merge config
        $this->mergeConfigFrom(__DIR__.'/../config/slackwipe.php', 'slackwipe');
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return [
            cleanSlackHistory::class,
        ];
    }
}
