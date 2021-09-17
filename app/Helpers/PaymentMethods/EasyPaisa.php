<?php

namespace App\Helpers\PaymentMethods;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class EasyPaisa
{
    /**
     * The algorithm to be used for encryption
     *
     * @var string
     */
    protected $cipher = "aes-128-ecb";

    /**
     * The algorithm to be used for encryption
     *
     * @var string
     */
    protected $tokenExpiry;

    /**
     * Amount that will be charged to the customer
     *
     * @var float|int
     */
    public $amount;

    /**
     * System generated order number for easypaisa
     *
     * @var string
     */
    public $orderId;

    /**
     * URL that easypaisa will redirect to when the session is over or checkout is complete
     *
     * @var string
     */
    public $postBackURL;

    /**
     * Customer email address
     * -- if empty, do not add to the post data array
     *
     * @var string
     */
    public $emailAddress;

    public function __construct()
    {
        $this->tokenExpiry = Carbon::now()->setTimezone("Asia/Karachi")->addMinutes(env("EASYPAISA_EXPIRE_MINUTES"))->format("Ymd His");
    }

    public function generateUrl()
    {
        $urlArray = [
            "storeId" => env("EASYPAISA_STORE_ID"),
            "orderId" => $this->orderId,
            "transactionAmount" => $this->amount,
            "transactionType" => "InitialRequest",
            "tokenExpiry" => $this->tokenExpiry,
            "encryptedHashRequest" => $this->generateHashCode(),
            "merchantPaymentMethod" => "",
            "mobileAccountNo" => "",
            "bankIdentificationNumber" => "",
            "signature" => "",
            "postBackURL" => $this->postBackURL
        ];

        if ($this->emailAddress) {
            $urlArray["emailAddress"] = $this->emailAddress;
        }

        // dd($urlArray);
        
        return env("EASYPAISA_POST_URL") . '?' . Arr::query($urlArray);
    }
    
    private function generateHashCode()
    {
        $postDataArray = [
            "amount" => $this->amount,
            "expiryDate" => $this->tokenExpiry,
            "merchantPaymentMethod" => "",
            "orderRefNum" => $this->orderId,
            "paymentMethod" => "InitialRequest",
            "storeId" => env("EASYPAISA_STORE_ID"),
            "timeStamp" => str_replace(" ", 'T', Carbon::now()->setTimezone("Asia/Karachi")->format("Y-m-d H:i:s")),
            "postBackURL" => $this->postBackURL
        ];

        if ($this->emailAddress) {
            $postDataArray["emailAddress"] = $this->emailAddress;
        }

        $sortedArray = $postDataArray;
        ksort($sortedArray);
        $urlStr = "";
        $i = 1;

        foreach ($sortedArray as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if ($i == 1) {
                $urlStr = $key. '=' .$value;
            } else {
                $urlStr .= '&' . $key. '=' .$value;
            }

            $i++;
        }

        return Base64_encode(openssl_encrypt($urlStr, $this->cipher, env("EASYPAISA_HASH"), OPENSSL_RAW_DATA));
    }
}