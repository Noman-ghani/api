<?php

namespace App\Http\Controllers\PaymentMethods;

use App\Helpers\Helpers;
use App\Helpers\Invoices as HelpersInvoices;
use App\Jobs\SendEmailJob;
use App\Models\Businesses;
use App\Models\Invoices;
use App\Models\Staff;
use App\Models\Transactions;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Lumen\Routing\Controller;

class EasyPaisaController extends Controller
{
    public function generateUrl(Request $request)
    {
        $this->validate($request, [
            "amount" => "required",
            "order_id" => "required",
            "order_type" => "required"
        ]);

        $postBackURL = env("MARKETPLACE_URL");

        if (in_array($request->order_type, ["subscription-portal", "sms-manager-portal"])) {
            $postBackURL = env("PORTAL_URL");
        }

        $postDataArray = [
            "amount" => $request->amount,
            "expiryDate" => Carbon::now()->setTimezone("Asia/Karachi")->addMinutes(env("EASYPAISA_EXPIRE_MINUTES"))->format("Ymd His"),
            "merchantPaymentMethod" => "",
            "orderRefNum" => $request->order_id,
            "paymentMethod" => "InitialRequest",
            "storeId" => env("EASYPAISA_STORE_ID"),
            "timeStamp" => str_replace(" ", 'T', Carbon::now()->setTimezone("Asia/Karachi")->format("Y-m-d H:i:s")),
            "postBackURL" => $postBackURL . "thankyou?order_type={$request->order_type}&order_reference={$request->order_id}"
        ];

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
        
        return env("EASYPAISA_POST_URL") . '?' . Arr::query([
            "storeId" => env("EASYPAISA_STORE_ID"),
            "orderId" => $request->order_id,
            "transactionAmount" => $request->amount,
            "transactionType" => "InitialRequest",
            "tokenExpiry" => Carbon::now()->setTimezone("Asia/Karachi")->addMinutes(env("EASYPAISA_EXPIRE_MINUTES"))->format("Ymd His"),
            "encryptedHashRequest" => base64_encode(openssl_encrypt($urlStr, "aes-128-ecb", env("EASYPAISA_HASH"), OPENSSL_RAW_DATA)),
            "merchantPaymentMethod" => "",
            "mobileAccountNo" => "",
            "bankIdentificationNumber" => "",
            "signature" => "",
            "postBackURL" => $postBackURL . "thankyou?order_type={$request->order_type}&order_reference={$request->order_id}"
        ]);
    }

    public function onTransactionComplete(Request $request)
    {
        $this->validate($request, [
            "url" => "required",
        ]);

        try {
            $client = new Client(["verify" => false]);
            $response = $client->get($request->url);
            $contents = (string) $response->getBody();
            $contents = json_decode($contents);

            if (strtolower($contents->transaction_status) === "paid") {
                $invoice = Invoices::whereId($contents->order_id)->first();
                if ($invoice) {
                    $request->request->add(["transaction_num" => $contents->transaction_id]);
                    $request->request->add(["status" => strtolower($contents->transaction_status)]);
                    $request->request->add(["payment_method_id" => 2]);
                    $request->request->add(["business_id" => $invoice->business_id]);
            
                    $newInvoice = new HelpersInvoices();
                    $newInvoice->completeSale($request, $contents->order_id);
                } else {
                    foreach (Helpers::getSubscriptionPackages() as $subscriptionPackage) {
                        if (strpos($contents->order_id, $subscriptionPackage["order_reference"]) === false) {
                            continue;
                        }
        
                        $business_id = str_replace($subscriptionPackage["order_reference"], '', $contents->order_id);
                        $business = Businesses::with("country")->whereId($business_id)->firstOrFail();
                        $isTransactionInserted = Transactions::whereBusinessId($business->id)
                        ->whereTransactionNum($contents->transaction_id)
                        ->whereInvoiceId($contents->order_id)
                        ->wherePaymentId(2)
                        ->exists();
        
                        if (!$isTransactionInserted) {
                            $transaction = new Transactions();
                            $transaction->business_id = $business->id;
                            $transaction->transaction_num = $contents->transaction_id;
                            $transaction->status = $contents->transaction_status;
                            $transaction->payment_id = 2;
                            $transaction->invoice_id = $contents->order_id;
                            $transaction->save();
                            
                            $business->subscription_expires_at = Carbon::now()->addMonth()->toDateString() . " 23:59:59";
                            $business->save();
        
                            $staff = Staff::with("user")->whereBusinessId($business->id)->whereRole("owner")->firstOrFail();
                            dispatch(new SendEmailJob([$staff->user->email], "ServU Subscription Extended", "<p style='margin: 0;'>Dear {$staff->full_name},</p><p style='margin: 10px 0 0;'>Thank you for your purchase. Below are the details.</p><p style='margin: 20px 0 0; text-align: center; font-size: 12px; color: #AAAAAA;'>This is an automatically generated email.</p>"));
                        }
                    }

                    foreach (Helpers::getSMSPackages() as $smsPackage) {
                        if (strpos($contents->order_id, $smsPackage["order_reference"]) === false) {
                            continue;
                        }
        
                        $business_id = str_replace($smsPackage["order_reference"], '', $contents->order_id);
                        $business = Businesses::with("country")->whereId($business_id)->firstOrFail();
                        $isTransactionInserted = Transactions::whereBusinessId($business->id)
                        ->whereTransactionNum($contents->transaction_id)
                        ->whereInvoiceId($contents->order_id)
                        ->wherePaymentId(2)
                        ->exists();
        
                        if (!$isTransactionInserted) {
                            $transaction = new Transactions();
                            $transaction->business_id = $business->id;
                            $transaction->transaction_num = $contents->transaction_id;
                            $transaction->status = $contents->transaction_status;
                            $transaction->payment_id = 2;
                            $transaction->invoice_id = $contents->order_id;
                            $transaction->save();
                            
                            $business->sms_limit += $smsPackage["sms_credits"];
                            $business->save();
        
                            $staff = Staff::with("user")->whereBusinessId($business->id)->whereRole("owner")->firstOrFail();
                            dispatch(new SendEmailJob([$staff->user->email], "ServU Sms Credits Purchased", "<p style='margin: 0;'>Dear {$staff->full_name},</p><p style='margin: 10px 0 0;'>Thank you for your purchase. Below are the details.</p><table style='width: 100%; margin-top: 20px;' cellpadding='0' cellspacing='0' border='0'><thead><tr><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>#</th><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>Package</th><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>Price</th></tr></thead><tbody><tr><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>1</td><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>" . $smsPackage["title"] . "</td><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>" . $business->country->currency . " " . $smsPackage["sms_credits"] . "</td></tr></tbody></table><p style='margin: 20px 0 0; text-align: center; font-size: 12px; color: #AAAAAA;'>This is an automatically generated email.</p>"));
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            dispatch(new SendEmailJob(explode(',', env("DEVELOPER_SUPPORT_EMAIL")), "ServU Developer - Easypaisa Error - " . $e->getLine(), $e->getMessage()));
        }
    }
}