# Slackwipe for Laravel

Slackwipe is a Laravel package to purge old slack message from your account and keep your channels fresh.

## Contributions and Bugs

Please create a pull request for any changes, update or bugs. Thanks!

## Requirements

- Laravel ~5.6 or higher
- Currently only supports Redis queue due to throttling functionality supplied by Laravel and required by Slack API. More can be read [here](https://laravel.com/docs/5.6/queues#rate-limiting). Using the Sync driver won't work.

## Installation

You can install the package via composer:

```
composer require slakbal/slackwipe
```

Laravel will auto-discover and register the `SlackwipeServiceProvider`, so no further setup is required.

After installing, you must publish the `slackwipe.php` configuration file:

```
php artisan vendor:publish --provider="Slakbal\Slackwipe\SlackwipeServiceProvider"
```

it will only publish the config file, or you can alternatively use:

```
php artisan vendor:publish
```

and select the Slackwipe dependency.

## Configuration

## API Token

In the `config\services.php` config file set the legacy API token for Slack. You can obtain a token from [here](https://api.slack.com/custom-integrations/legacy-tokens).You can also extract it to a `.env` variable to keep it out of your source-repository.  

```
'slack' => [
    'token' => 'xoxp-337094627015-336894794790-399611226556404-18456546534534534535321565625234234234234344'
 ]
``` 

### Channels to wipe

In the config file the channels and the retention period in days can be configured. If the retention period is set to 0 all messages from the channel will be wiped once the command is executed. If the retention period is set to for example 10 days all messages older from (NOW TIMESTAMP - 10 days) will be wiped.  

```
'private-channels' => [
    ['name' => 'dev-chat', 'days_to_keep' => 100],
    ['name' => 'private-channel', 'days_to_keep' => 0],
],

'public-channels' => [
    ['name' => 'general', 'days_to_keep' => 365],
    ['name' => 'random', 'days_to_keep' => 365],
]        
```

### Queue

Per default all jobs will be queued onto the `default` Redis queue. It is however recommended to keep things separate and have a dedicated queue (tube) for the wipe jobs with a single queue worker that would work the specific queue. In the config file you can set the name of the queue to which the jobs should be dispatched to or add the `QUEUE_TUBE_SLACK_WIPE` variable to your environment (.env) file   

```
'slack_wipe_queue' => env('QUEUE_TUBE_SLACK_WIPE', 'default')      
```

## Execution

You can run the command manually by executing:

```
php artisan slack:wipe
```

Make sure you have a queue working running, otherwise you won't see any effect in Slack.

You may define a scheduled task in the schedule method of the `App\Console\Kernel` class to queue wipe jobs, eg.:

```
$schedule->command('slack:wipe')->everyFifteenMinutes()->between('3:00', '5:00')->withoutOverlapping();
```

Your contributions (Pull Requests) or bug fixes are welcome!

Enjoy!

Slakbal
