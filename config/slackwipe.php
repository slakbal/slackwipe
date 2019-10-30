<?php

return [

    /*
     * Names of the private channels to clean-up and the number of days from NOW to cleanup.
     * Everything older than NOW - x days will be purged
     *
     * setting days_to_keep to 0 will purge all the messages
     *
     */
    'private-channels' => [
        //['name' => 'dev-chat', 'days_to_keep' => 30],
        //['name' => 'questions', 'days_to_keep' => 100],
    ],

    /*
     * Names of the public channels to clean-up and the number of days from NOW to cleanup.
     * Everything older than NOW - x days will be purged
     *
     * setting days_to_keep to 0 will purge all the messages
     *
     */
    'public-channels' => [
        ['name' => 'general', 'days_to_keep' => 365],
        ['name' => 'random', 'days_to_keep' => 365],
    ],

    /*
     * If queues are activate the clean-up jobs will be queued onto the following queue tube
     * It is recommended to have a separate queue with a single worker to avoid busting the Slack API limit
     */
    'slack_wipe_queue' => env('QUEUE_TUBE_SLACK', 'default'),

];
