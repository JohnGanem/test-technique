<?php

namespace App\Console\Commands;

use App\Jobs\TemperatureRequest as TemperatureRequest;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use karpy47\PhpMqttClient\MQTTClient;

class ListenTemperatureRequest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen-temperature-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen temperature request';

    /**
     * @var string
     */
    private $topic = 'eficia/weather-request';

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
     * @param Client $client
     * @return void
     */
    public function handle(): void
    {
        $client = new MQTTClient(env('mqtt_host', 'broker.hivemq.com'), env('mqtt_port', 1883));
        $success = $client->sendConnect(12345);  // set your client ID
        if ($success) {
            try {
                $client->sendSubscribe($this->topic);
                $this->info('Listening to ' . $this->topic);

                while (true) {
                    $messages = $client->getPublishMessages();  // now read and acknowledge all messages waiting
                    print_r($messages);
                    foreach ($messages as $message) {
                        $this->info('Received request for ' . $message['message']);
                        TemperatureRequest::dispatch($message['message'])->onQueue('slow');
                    }
                }
            } catch (Exception $e) {
                $this->info($e->getMessage());

                $client->sendDisconnect();
                $client->close();
            }
        }
        $client->close();
    }

//            $client->sendPublish('topic2', 'Message to all subscribers of this topic');
}
