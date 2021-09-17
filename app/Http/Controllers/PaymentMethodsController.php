<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\PaymentMethods;
use Laravel\Lumen\Routing\Controller;
use App\Helpers\Invoices as HelpersInvoices;
use App\Helpers\PaymentMethods\EasyPaisa;
use App\Jobs\SendEmailJob;
use App\Models\Businesses;
use App\Models\Clients;
use App\Models\Invoices;
use App\Models\Staff;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class PaymentMethodsController extends Controller
{
    public function browse(Request $request, $country_id)
    {
        $result = PaymentMethods::whereIsActive(1);

        if (!$request->has("for_portal")) {
            $result->whereHas("countries", function ($query) use ($country_id) {
                $query->whereCountryId($country_id);
                $query->where("id", '<>', 1);
            });
        }

        return $result->get();
    }
    // =========================
    // Easypaisa Functions Start
    // =========================
    public function payEasyPaisa(Request $request)
    {
        $this->validate($request, [
            "business_id" => "required",
            "branch_id" => "required",
            "subtotal" => "required",
            "tax" => "required",
            "grandtotal" => "required",
            "items" => "required|array"
        ]);

        if ($request->has("customer")) {
            $client = Clients::where("customer_id",$request->customer["id"])->where("business_id",$request->business_id)->first();
            if (empty($client)) {
                $client = new Clients();
                $client->business_id = $request->business_id;
                $client->email = $request->customer["email"];
                $client->phone_country_id = $request->customer["phone_country_id"];
                $client->phone_number = $request->customer["phone_number"];
                $client->first_name = $request->customer["first_name"];
                $client->last_name = $request->customer["last_name"];
                $client->birthday = $request->customer["birthday"];
                $client->customer_id = $request->customer["id"];
                $client->save();
            }

            $request->request->add(["client_id" => $client->id]);
        }

        $amount = $request->grandtotal;
        $invoice = new HelpersInvoices();
        $invoice = $invoice->create($request);

        if (!$invoice["success"]) {
          return response()->json(["success" => $invoice["success"], "message" => $invoice["message"]], 422);  
        }

        $post_data = [
            "amount" 	            => $amount,
            "expiryDate" 		    => Carbon::now()->setTimezone('Asia/Karachi')->addMinutes(env("EASYPAISA_EXPIRE_MINUTES"))->format('Ymd His'), 
            "merchantPaymentMethod" => $request->payment_method ? $request->payment_method : "",
            "orderRefNum" 		    => $invoice["invoice_id"],
            "paymentMethod"         => "InitialRequest",
            "storeId" 			    => env("EASYPAISA_STG_STORE_ID"),
            "timeStamp"             => str_replace(" ","T",Carbon::now()->setTimezone('Asia/Karachi')->format('Y-m-d H:i:s')),
            "postBackURL"           => env("APP_URL").'pay/easypaisa/thankyou',
            "emailAddress"          => $request->customer['email'],
        ];

        $sortedArray = $post_data;
        ksort($sortedArray);
        $sorted_string = '';
        $i = 1;

        foreach ($sortedArray as $key => $value) {
            if (!empty($value)) {
                if ($i == 1) {
                    $sorted_string = $key. '=' .$value;
                } else {
                    $sorted_string = $sorted_string . '&' . $key. '=' .$value;
                }
            }
            $i++;
        }
        
        $cipher = "aes-128-ecb";
        $crypttext = openssl_encrypt($sorted_string, $cipher, env("EASYPAISA_HASH"), OPENSSL_RAW_DATA);
        $HashedRequest = Base64_encode($crypttext);
        $post_data["merchantHashedReq"] =  $HashedRequest;
        $post_data["url"] = env("EASYPAISA_POST_URL");

        return $post_data;
    }

    public function easyPaisaDetails(Request $request)
    {
        $easyPaisa = new EasyPaisa();
        $easyPaisa->amount = $request->amount;
        $easyPaisa->orderId = $request->order_id;
        $easyPaisa->postBackURL = env("APP_URL") . "pay/easypaisa-payment-response?purchase_type=sms_credits";

        return $easyPaisa->generateUrl();
    }

    public function easypaisePaymentResponse(Request $request)
    {
        if ($request->purchase_type === "sms_credits") {
            $status = "declined";
            $message = "Transaction declined by easypaisa. Please contact support.";

            if ($request->has("transactionRefNumber")) {
                $status = "success";
                $message = "Payment made successfully. Your SMS credit topup is done.";
            }
            return redirect(env("PORTAL_URL") . "sms-manager?" . Arr::query(["payment-response" => ["status" => $status, "message" => $message]]));
        }
    }

    public function confirmEasypaisa(Request $request)
    {
        $this->validate($request, [
            "url" => "required",
        ]);

        try {
            $client = new Client(["verify" => false]);
            $response = $client->get($request->url);
            $contents = (string) $response->getBody();
            $contents = json_decode($contents);
            $order_id = $contents->order_id;

            // this is the order id for sms packages bought directly from portal
            if (strpos($order_id, "sms-package") !== false && strtolower($contents->transaction_status) === "paid") {
                $array = explode('-', $order_id);
                $business = Businesses::with("country")->whereId($array[count($array) - 1])->firstOrFail();
                $isTransactionInserted = Transactions::whereBusinessId($business->id)
                ->whereTransactionNum($contents->transaction_id)
                ->whereInvoiceId($contents->order_id)
                ->wherePaymentId(2)
                ->exists();

                if (!$isTransactionInserted) {
                    $packages = Helpers::getSMSPackages($business->country_id);
                    $package = $packages[$array[count($array) - 2]];

                    $transaction = new Transactions();
                    $transaction->business_id = $business->id;
                    $transaction->transaction_num = $contents->transaction_id;
                    $transaction->status = $contents->transaction_status;
                    $transaction->payment_id = 2;
                    $transaction->invoice_id = $contents->order_id;
                    $transaction->save();
                    
                    $business->sms_limit += $package["sms_credits"];
                    $business->save();

                    $staff = Staff::with("user")->whereBusinessId($business->id)->whereRole("owner")->firstOrFail();
                    dispatch(new SendEmailJob([$staff->user->email], "ServU Sms Credits Purchased", "<p style='margin: 0;'>Dear {$staff->full_name},</p><p style='margin: 10px 0 0;'>Thank you for your purchase. Below are the details.</p><table style='width: 100%; margin-top: 20px;' cellpadding='0' cellspacing='0' border='0'><thead><tr><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>#</th><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>Package</th><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>Price</th></tr></thead><tbody><tr><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>1</td><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>" . $package["title"] . "</td><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>" . $business->country->currency . " " . $array[count($array) - 2] . "</td></tr></tbody></table><p style='margin: 20px 0 0; text-align: center; font-size: 12px; color: #AAAAAA;'>This is an automatically generated email.</p>"));
                }
            } else {
                $invoice = Invoices::whereId($order_id)->first();
                if ($invoice && $invoice->status === "unpaid") {
                    $request->request->add(["transaction_num" => $contents->transaction_id]);
                    $request->request->add(["status" => strtolower($contents->transaction_status)]);
                    $request->request->add(["payment_method_id" => 2]);
                    $request->request->add(["business_id" => $invoice->business_id]);
            
                    $invoice_completed = new HelpersInvoices();
                    $invoice_completed = $invoice_completed->completeSale($request, $order_id);
                    
                    if ($invoice_completed["success"]) {
                        return redirect(env("MARKETPLACE_URL") . "/deals/thankyou");
                    }
                }
            }
        } catch (\Exception $e) {
            dispatch(new SendEmailJob(explode(',', env("DEVELOPER_SUPPORT_EMAIL")), "ServU Developer - Easypaisa Error", $e->getMessage()));
        }
    }
    
    public function thankyouEasypaisa(Request $request){
        if(isset($request->status) && strtolower($request->status) != "success"){
            $invoice_id = $request->orderRefNumber;
            $deal_slug = null;
            $invoice = Invoices::with('items.deal')->whereId($invoice_id)->firstOrFail();
            
            $request->request->add(["business_id" => $invoice->business_id]);
            
            if($invoice->items[0]->deal_id){
                $deal_slug = $invoice->items[0]->deal->slug;
            }
            
            $invoice_void = new HelpersInvoices();
            $invoice_void = $invoice_void->voidSale($request, $invoice_id);

            if(!$invoice_void['success']){
                return response()->json(["success" => $invoice_void["success"], "message" => $invoice_void["message"]], 422);  
            }

            if($deal_slug){
                return redirect(env("MARKETPLACE_URL").'deals/'.$deal_slug.'?error="Something went wrong while processing a payment."');
            }

            return redirect(env("MARKETPLACE_URL"));
        }
        
        return redirect(env("MARKETPLACE_URL").'deals/thankyou?invoice='.$request->orderRefNumber); 
        
    }
    // =========================
    // Easypaisa Functions End
    // =========================

    // =========================
    // Keenu Functions Start
    // =========================

    public function getBank(Request $request){

        $clientID = $merchantID = "210700509805588";
        $password = "B30EB861BA0ECF217921B4335F55D578";

        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <GetBanks xmlns="http://tempuri.org/">
                <ClientID>'.$clientID.'</ClientID>
                <Password>'.$password.'</Password>
                <MerchantId>'.$merchantID.'</MerchantId>
                </GetBanks>
            </soap:Body>
            </soap:Envelope>';

        $client = new Client();
        $response = $client->post("https://netconnectsit.keenu.pk/NCApplication/KPALApi.asmx", [
            "body" => $xml_post_string,
            'headers' => [
                "Content-Type" => "text/xml",
                "accept" => "*/*",
                "accept-encoding" => "gzip, deflate"
            ]
        ]);
        print_r($response);
        die;
    }

    public function confirmKeenu(Request $request)
    {
        Mail::send([], [], function ($message) use($request) {
            $message->to("noman.ghani@genetechsolutions.com");
            $message->subject(__("Keenu Confirm URL Hit"));
            $message->setBody("Request => ".json_encode($request->all()). " ,Method => ".$request->method());
        });
    }   

    // =========================
    // Keenu Functions End
    // =========================
}