<?php

namespace Slakbal\Slackwipe\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Craftyx\SlackApi\Facades\SlackChat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteSlackMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 30;

    public $channelId;

    public $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($channelId, $message)
    {
        $this->channelId = $channelId;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Redis::throttle('wipe')->allow(1)->every(4)->then(function () {
            $response = SlackChat::delete($this->channelId, $this->message->ts);

            if ($response->ok) {
                //Log::info('delete-slack-message:', ['action' => 'delete-slack-message-job', 'message' => 'Slack message was deleted.']);
            } else {
                Log::warning('delete-slack-message:', ['action' => 'delete-slack-message-job', 'message' => 'Slack message could not be deleted.', 'response' => json_encode($response)]);
            }
        }, function () {
            return $this->release(10);
        });
    }
}
