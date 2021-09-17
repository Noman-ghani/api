<?php

namespace App\Jobs;

use App\Models\Businesses;
use App\Models\SmsHistory;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SendSmsJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Business Id for further use cases.
     *
     * @var string|integer
     */
    protected $business_id;
    
    /**
     * Customer mobile number on which the sms will be sent.
     *
     * @var string|integer
     */
    protected $recepient;

    /**
     * Message that will be sent to the customer.
     *
     * @var string
     */
    protected $message;

    public function __construct($business_id, $recepient, $message)
    {
        $this->business_id = $business_id;
        $this->recepient = $recepient;
        $this->message = $message;
    }
    
    /**
     * Execute the job.
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // check whether this business has message capacity
        $business = Businesses::with("country")->whereId($this->business_id)->whereIsActive(1)->first();

        if ($business && $business->sms_limit > 0) {
            $recipient = $business->country->phone_code . $this->recepient;
            $recipient = 923222353116;
            
            $query = Arr::query([
                "action" => "sendmessage",
                "username" => env("EOCEAN_USERNAME"),
                "password" => env("ECOEAN_PASSWORD"),
                "originator" => env("EOCEAN_ORIGINATOR"),
                "recipient" => $recipient,
                "messagedata" => $this->message
            ]);

            $url = env("EOCEAN_API") . '?' . $query;
            $client = new Client();
            $response = $client->get($url);
            $contents = (string) $response->getBody();
            $parseContents = simplexml_load_string($contents);

            if ((string)$parseContents->action === "sendmessage") {
                // decrease sms counter
                $business->sms_limit -= 1;
                $business->save();
    
                SmsHistory::create([
                    "business_id" => $this->business_id,
                    "mobile_number" => $recipient,
                    "message" => $this->message
                ]);
            }

            print "\n\n";
            print $url . "\n\n";
            echo "========================================\n";
            print_r($contents);
            print "\n";
            echo "========================================\n";
            print "\n\n";
        }
    }
}