<?php

namespace Slakbal\Slackwipe\Commands;

use Slakbal\Slackwipe\Jobs\DeleteSlackMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackConversation;
use Wgmv\SlackApi\Facades\SlackGroup;
use Illuminate\Support\Facades\Log;

class cleanSlackHistory extends Command
{

    const SCOPE_PUBLIC = 'PUBLIC';
    const SCOPE_PRIVATE = 'PRIVATE';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:wipe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe old Slack messages for the configured channels.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (strtolower(config('queue.default')) == 'redis') {

            Log::info('Slack Wipe:', ['action' => 'slack:wipe', 'message' => 'Wiping Slack channels.']);

            $this->cleanupChannels(self::SCOPE_PUBLIC);

            $this->cleanupChannels(self::SCOPE_PRIVATE);

            Log::info('Slack Wipe:', ['action' => 'slack:wipe', 'message' => 'Wiping completed.']);
            $this->info('Slack Wipe: Done!');

        } else {

            $message = 'The ' . strtoupper(config('queue.default')) . ' queue driver is not supported! Only Redis is currently supported.';
            Log::warning('Slack Wipe:', ['action' => 'slack:wipe', 'message' => $message]);
            $this->error('Slack Wipe: '.$message);

        }
    }

//        $this->warn('Slack Wipe: Make sure a worker is running on the queue.');
    private function cleanupChannels($scope = self::SCOPE_PUBLIC)
    {

        $channelsToWipe = ($scope == self::SCOPE_PUBLIC) ? config('slackwipe.public-channels') : config('slackwipe.private-channels');

        if (count($channelsToWipe) > 0) {

            $this->info('Slack Wipe: Retrieving ' . $scope . ' channels from Slack.');


            if ($scope == self::SCOPE_PRIVATE) {

                $response = SlackGroup::lists(); //get private channels

            } else {

                $response = SlackChannel::lists(); //get public channels

            }

            if ($response->ok) {

                //extract channels from the different responses
                if ($scope == self::SCOPE_PRIVATE) {

                    $channels = $response->groups;

                } else {

                    $channels = $response->channels;

                }

                foreach ($channels as $channel) {

                    $this->cleanupSingleChannel($channelsToWipe, $channel);

                }

            } else {

                $message = 'Could not retrieve ' . $scope . ' channels from Slack. Make sure your token is correct.';
                Log::error('Slack Wipe:', ['action' => 'slack:wipe', 'message' => $message]);
                $this->error('Slack Wipe: '.$message);

            }


        } else {

            $this->warn('Slack Wipe: No ' . $scope . ' channels to be wiped.');

        }
    }


    private function cleanupSingleChannel($channelsToWipe, $channel)
    {
        foreach ($channelsToWipe as $channelToWipe) {

            if ($channelToWipe['name'] == $channel->name) {

                $this->info('Slack Wipe: Cleaning channel \'' . $channel->name . '\' (' . $channel->id . ') with ' . $channelToWipe['days_to_keep'] . ' days retention.');

                $this->dispatchCleanupJobs($channel->id, $channelToWipe);

            }
        }
    }


    private function dispatchCleanupJobs($channelId, $channel)
    {
        /*
            Other options
            'channel' => C1234567890, // Channel to fetch history for.
            'cursor' => dXNlcjpVMDYxTkZUVDI, //Paginate through collections of data by setting the cursor parameter to a next_cursor attribute returned by a previous request's response_metadata. Default value fetches the first 'page' of the collection. See pagination for more detail.
            'inclusive' => true, // Include messages with latest or oldest timestamp in results.
            'oldest' => 0, // Start of time range of messages to include in results.
         */
        $options = [
            'latest' => ($channel['days_to_keep'] != 0) ? Carbon::now()->subDays($channel['days_to_keep'])->timestamp : Carbon::now()->timestamp, // End of time range of messages to include in results.
            'limit' => 100, // Number of messages to return, between 1 and 1000.
        ];

        //will fetch only a certain number of messages, the job will catchup over time
        //todo implement paging or schedule run job more often per day and then it will start to catch-up
        $conversations = SlackConversation::history($channelId, $options);

        if ($conversations->ok) {

            $jobs_count = 0;
            $queueName = config('slackwipe.slack_wipe_queue', 'default');

            foreach ($conversations->messages as $message) {

                dispatch(new DeleteSlackMessage($channelId, $message))->onQueue($queueName);

                $jobs_count++;

            }

            $message = $jobs_count . ' delete job(s) queued for the \'' . $channel['name'] . '\' (' . $channelId . ') channel';
            Log::info('Slack Wipe:', ['action' => 'slack:wipe', 'message' => $message]);
            $this->info('Slack Wipe: ' . $message);

        } else {

            $this->info('Slack Wipe: An issue occurred while retrieving the conversations for the \'' . $channel['name'] . '\' (' . $channelId . ') channel');

        }
    }

}



