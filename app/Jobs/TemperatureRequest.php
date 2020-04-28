<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportHarmonyObjects implements ShouldQueue
{

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;
    /**
     * @var string
     */
    private $city;
    private $api_endpoint = "http://api.weatherstack.com/current";
    private $topic_to_publish = 'eficia/weather-response';
    public int $retryAfter = 300;
    public int $maxAttempts = 20;

    /**
     * Create a new job instance.
     *
     * @param int $harmonyId
     */
    public function __construct(string $message)
    {
        $this->city = $message;
        $this->queue = 'temperature-request';
    }

    /**
     * Execute the job.
     *
     * @param Client $client
     * @return void
     */
    public function handle(Client $client)
    {
        try {
            $response = $client->request('GET', $this->api_endpoint,
                [
                    'query' => [
                        'access_key' => env('weatherstack_api', ''),
                        'query' => $this->city
                    ]
                ]
            );
        } catch (RequestException $e) {
            $this->retryOrDelete();
            return;
        }

        if ($response->getStatusCode() != 200) {
            $this->retryOrDelete(); // $e->getMessage()
            return;
        }

        $this->publishTemperature($response->getBody()->getContents());
    }

    private function publishTemperature($temperature)
    {
        $client = new MQTTClient(env('mqtt_host', 'broker.hivemq.com'), env('mqtt_port', 1883));
        $success = $client->sendConnect(12345);  // set your client ID
        if ($success) {
            $client->sendPublish($this->topic_to_publish, $this->city . '-' . $temperature);
            $client->sendDisconnect();
        }
        $client->close();
    }

    protected function retryOrDelete(): void
    {
        if ($this->attempts() >= $this->maxAttempts) {
            $this->delete();
        } else {
            $this->release($this->retryAfter);
        }

        return;
    }

}
