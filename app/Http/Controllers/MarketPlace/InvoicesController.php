<?php

namespace App\Http\Controllers\MarketPlace;

use App\Helpers\Helpers;
use App\Helpers\Invoices as HelpersInvoices;
use App\Models\Invoices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Support\Facades\Auth;

class InvoicesController extends Controller
{

  
    public function browse(Request $request)
    {
       
         $invoices = Invoices::with(["client" => function ($query) use ($request){
            $query->where("customer_id", $request->id);

         }])->whereHas("client", function ($query) use ($request){
            $query->where("customer_id", $request->id);
         })->with(["business.country", "items" => function ($query) use ($request) {
                if ($request->has("with-item-discounts")) {
                    $query->with("discounts");
                }
                if ($request->has("with-item-staff")) {
                    $query->with("staff");
                }
                if ($request->has("with-item-taxes")) {
                    $query->with("taxes");
                }
            }]);

            if ($request->has("with-taxes")) {
                $invoices->with("taxes");
            }
            if ($request->has("with-client")) {
                $invoices->with("client.phone_country");
            }
            if ($request->has("with-branch")) {
                $invoices->with("branch");
            }
            if ($request->has("with-payment-method")) {
                $invoices->with("payment_method");
            }
            if ($request->has("with-refund-invoice")) {
                $invoices->with("refund_invoice");
            }
            if ($request->has("with-original-invoice")) {
                $invoices->with("original_invoice");
            }
            if ($request->has("with-transactions")) {
                $invoices->with("transactions");
                
            }
            $invoices = $invoices->orderBy('id','desc')->get();
         
         

       
         return $invoices;
        
        
    }
    
    public function get_by_id(Request $request, $id)
    {
       
        //dd();
        $invoice = Invoices::with("business.country")->whereId($id)->whereHas("client", function ($query) use ($request){
            $user = Auth::guard('marketPlace')->user();
            $query->where("customer_id", $user->id);
         });

        if ($request->has("with-items")) {
            $invoice->with(["items" => function ($query) use ($request) {
                if ($request->has("with-item-discounts")) {
                    $query->with("discounts");
                }
                if ($request->has("with-item-staff")) {
                    $query->with("staff");
                }
                if ($request->has("with-item-taxes")) {
                    $query->with("taxes");
                }
            }]);
        }
        if ($request->has("with-taxes")) {
            $invoice->with("taxes");
        }
        if ($request->has("with-client")) {
            $invoice->with("client.phone_country");
        }
        if ($request->has("with-branch")) {
            $invoice->with("branch");
        }
        if ($request->has("with-payment-method")) {
            $invoice->with("payment_method");
        }
        if ($request->has("with-refund-invoice")) {
            $invoice->with("refund_invoice");
        }
        if ($request->has("with-original-invoice")) {
            $invoice->with("original_invoice");
        }
        if ($request->has("with-transactions")) {
            $invoice->with("transactions");
        }
        

        return $invoice->firstOrFail();
    }

}