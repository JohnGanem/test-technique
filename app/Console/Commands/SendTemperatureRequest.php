<?php

namespace App\Console\Commands;

use App\Jobs\TemperatureRequest as TemperatureRequest;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use karpy47\PhpMqttClient\MQTTClient;

class SendTemperatureRequest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:send-temperature-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send temperature request';

    /**
     * @var string
     */
    private $topic = 'eficia/weather-request';
    private $city = 'Paris';

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
            $client->sendPublish($this->topic, $this->city);
            $this->info('Send request for ' . $this->city);
            $client->sendDisconnect();
        }
        $client->close();
    }

//            $client->sendPublish('topic2', 'Message to all subscribers of this topic');
}
