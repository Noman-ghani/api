<?php

namespace App\Http\Controllers\PaymentMethods;

use App\Helpers\Helpers;
use App\Helpers\Invoices as HelpersInvoices;
use App\Jobs\SendEmailJob;
use App\Models\Businesses;
use App\Models\Staff;
use App\Models\Invoices;
use App\Models\Transactions;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Lumen\Routing\Controller;

class KeenuController extends Controller
{
    public function getBanks()
    {
        $client = new Client();
        $response = $client->post(env("KEENU_BANKS_API_URL"), [
            "body" => "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">
            <soap:Body>
                <GetBanks xmlns=\"http://tempuri.org/\">
                    <ClientID>" . env("KEENU_CLIENT_ID") . "</ClientID>
                    <Password>" . env("KEENU_PASSWORD") . "</Password>
                    <MerchantId>" . env("KEENU_MERCHANT_ID") . "</MerchantId>
                </GetBanks>
            </soap:Body>
            </soap:Envelope>",
            "headers" => [
                "Content-Type" => "text/xml",
                "accept" => "*/*",
                "accept-encoding" => "gzip, deflate"
            ]
        ]);

        $clean_xml = str_ireplace(["SOAP-ENV:", "SOAP:"], '', (string)$response->getBody());
        $parse_xml = simplexml_load_string($clean_xml);

        return $parse_xml->Body->GetBanksResponse->GetBanksResult;
    }

    public function generateSecuredHash(Request $request)
    {
        $this->validate($request, [
            "amount" => "required",
            "order_id" => "required",
            "bank_id" => "required"
        ]);
        
        $privateKey = file_get_contents(base_path("resources/private_key.txt"));
        $amount = $request->amount;
        $order_id = $request->order_id;
        $bank_id = $request->bank_id;
        $date = Carbon::now()->setTimezone("Asia/Karachi")->format("d/m/Y");
        $time = Carbon::now()->setTimezone("Asia/Karachi")->format("H:i:s");
        $stringToEncrypt = env("KEENU_CLIENT_ID") . '|' . env("KEENU_MERCHANT_ID") . '|' . $bank_id . '|' . $order_id . '|' . $amount . '|' . $date . '|' . $time;
        openssl_sign($stringToEncrypt, $hashCodeGenerated, $privateKey, OPENSSL_ALGO_SHA256);

        return [
            "action" => env("KEENU_REDIRECT_API_URL"),
            "client_id" => env("KEENU_CLIENT_ID"),
            "merchant_id" => env("KEENU_MERCHANT_ID"),
            "checksum" => base64_encode($hashCodeGenerated),
            "date" => $date,
            "time" => $time
        ];
    }

    public function onTransactionComplete(Request $request)
    {
        $this->validate($request, [
            "Order_ID" => "required",
            "Status" => "required",
            "AUTH_STATUS_NO" => "required"
        ]);
        
        $status = "declined";
        $message = "An error occurred while proceeding with your transaction. Please contact support.";
        $invoice = Invoices::whereId($request->Order_ID)->first();
        $isSuccess = $request->Status === "Success" && $request->AUTH_STATUS_NO === "01";
        if ($invoice) {
            $newInvoice = new HelpersInvoices();
            $request->request->add(["business_id" => $invoice->business_id]);
            if ($isSuccess) {
                $request->request->add(["transaction_num" => $request->Transaction_ID]);
                $request->request->add(["status" => strtolower($request->Status)]);
                $request->request->add(["payment_method_id" => 3]);
                $newInvoice->completeSale($request, $request->Order_ID);
            } else {
                $newInvoice->voidSale($request, $request->Order_ID);
            }

            return redirect(env("MARKETPLACE_URL") . "/deals/thankyou");
        } else {
            foreach (Helpers::getSubscriptionPackages() as $subscriptionPackage) {
                if (strpos($request->Order_ID, $subscriptionPackage["order_reference"]) === false) {
                    continue;
                }

                if ($isSuccess) {
                    $business_id = str_replace($subscriptionPackage["order_reference"], '', $request->Order_ID);
                    $business = Businesses::with("country")->whereId($business_id)->firstOrFail();
                    $isTransactionInserted = Transactions::whereBusinessId($business->id)
                    ->whereTransactionNum($request->Transaction_ID)
                    ->whereInvoiceId($request->Order_ID)
                    ->wherePaymentId(3)
                    ->exists();

                    if (!$isTransactionInserted) {
                        $transaction = new Transactions();
                        $transaction->business_id = $business->id;
                        $transaction->transaction_num = $request->Transaction_ID;
                        $transaction->status = $request->Status;
                        $transaction->payment_id = 3;
                        $transaction->invoice_id = $request->Order_ID;
                        $transaction->bank_name = $request->Bank_Name;
                        $transaction->save();
                        
                        $business->subscription_expires_at = Carbon::now()->addMonth()->toDateString() . " 23:59:59";
                        $business->save();

                        $staff = Staff::with("user")->whereBusinessId($business->id)->whereRole("owner")->firstOrFail();
                        dispatch(new SendEmailJob([$staff->user->email], "ServU Subscription Extended", "<p style='margin: 0;'>Dear {$staff->full_name},</p><p style='margin: 10px 0 0;'>Thank you for your purchase. Below are the details.</p><p style='margin: 20px 0 0; text-align: center; font-size: 12px; color: #AAAAAA;'>This is an automatically generated email.</p>"));

                        $status = "success";
                        $message = "Payment made successfully. Your subscription has been extended.";
                    }
                }

                return redirect(env("PORTAL_URL") . "sms-manager?" . Arr::query(["payment-response" => ["status" => $status, "message" => $message]]));
            }
            
            foreach (Helpers::getSMSPackages() as $smsPackage) {
                if (strpos($request->Order_ID, $smsPackage["order_reference"]) === false) {
                    continue;
                }

                if ($isSuccess) {
                    $business_id = str_replace($smsPackage["order_reference"], '', $request->Order_ID);
                    $business = Businesses::with("country")->whereId($business_id)->firstOrFail();
                    $isTransactionInserted = Transactions::whereBusinessId($business->id)
                    ->whereTransactionNum($request->Transaction_ID)
                    ->whereInvoiceId($request->Order_ID)
                    ->wherePaymentId(3)
                    ->exists();

                    if (!$isTransactionInserted) {
                        $transaction = new Transactions();
                        $transaction->business_id = $business->id;
                        $transaction->transaction_num = $request->Transaction_ID;
                        $transaction->status = $request->Status;
                        $transaction->payment_id = 3;
                        $transaction->invoice_id = $request->Order_ID;
                        $transaction->bank_name = $request->Bank_Name;
                        $transaction->save();
                        
                        $business->sms_limit += $smsPackage["sms_credits"];
                        $business->save();

                        $staff = Staff::with("user")->whereBusinessId($business->id)->whereRole("owner")->firstOrFail();
                        dispatch(new SendEmailJob([$staff->user->email], "ServU Sms Credits Purchased", "<p style='margin: 0;'>Dear {$staff->full_name},</p><p style='margin: 10px 0 0;'>Thank you for your purchase. Below are the details.</p><table style='width: 100%; margin-top: 20px;' cellpadding='0' cellspacing='0' border='0'><thead><tr><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>#</th><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>Package</th><th style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>Price</th></tr></thead><tbody><tr><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>1</td><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>" . $smsPackage["title"] . "</td><td style='text-align: left; border: 1px solid #DDDDDD; padding: 10px;'>" . $business->country->currency . " " . $smsPackage["sms_credits"] . "</td></tr></tbody></table><p style='margin: 20px 0 0; text-align: center; font-size: 12px; color: #AAAAAA;'>This is an automatically generated email.</p>"));

                        $status = "success";
                        $message = "Payment made successfully. Your SMS credit topup is done.";
                    }
                }

                return redirect(env("PORTAL_URL") . "sms-manager?" . Arr::query(["payment-response" => ["status" => $status, "message" => $message]]));
            }
        }
    }
}