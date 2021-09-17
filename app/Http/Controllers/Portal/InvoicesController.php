<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Helpers\Invoices as HelpersInvoices;
use App\Models\Invoices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Laravel\Lumen\Routing\Controller;

class InvoicesController extends Controller
{
    public function browse(Request $request)
    {
        $invoices = Invoices::with(["branch", "client"])
            ->whereBusinessId(Helpers::getJWTData("business_id"))
            ->orderBy("id", "desc");
        
        if ($request->has("client_id")) {
            $invoices->whereClientId($request->client_id);
        }

        $offset = $request->business->timezone->offset;

        if ($request->has("start_date")) {
            $invoices->whereRaw("created_at >= DATE_ADD('{$request->start_date} 00:00:00', INTERVAL '{$offset}' HOUR)");
        }

        if ($request->has("end_date")) {
            $invoices->whereRaw("created_at <= DATE_ADD('{$request->end_date} 23:59:59', INTERVAL '{$offset}' HOUR)");
        }

        return $invoices->get();
    }
    
    public function create(Request $request)
    {
        $this->validate($request, [
            "branch_id" => "required",
            "subtotal" => "required|numeric",
            "tax" => "required|numeric",
            "grandtotal" => "required|numeric",
            "items" => "required|array"
        ]);
        
        $invoice = new HelpersInvoices();
        $request->request->set("business_id", Helpers::getJWTData("business_id"));
        return $invoice->create($request);
    }

    public function get_by_id(Request $request, $id)
    {
        $invoice = Invoices::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);

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
                if ($request->has("with-client-deal")) {
                    $query->with(["clientDeal" => function ($clientDeal) {
                        $clientDeal->with("deal");
                        $clientDeal->with("items");
                    }]);
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
            $invoice->with("branch.business.country");
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

        return $invoice->firstOrFail();
    }

    public function completeSale(Request $request, $id)
    {
        $this->validate($request, [
            "payment_method_id" => "required"
        ]);
        
        $invoice = new HelpersInvoices();
        $request->request->set("business_id", Helpers::getJWTData("business_id"));
        return $invoice->completeSale($request, $id);
    }

    public function voidInvoice(Request $request, $id)
    {
        $invoice = new HelpersInvoices();
        $request->request->set("business_id", Helpers::getJWTData("business_id"));
        return $invoice->voidSale($request, $id);
    }

    public function refundInvoice(Request $request, $id)
    {
        $this->validate($request, [
            "items" => "required|array",
            "payment_method_id" => "required"
        ]);
        
        $invoice = new HelpersInvoices();
        $request->request->set("business_id", Helpers::getJWTData("business_id"));
        return $invoice->refundSale($request, $id);
    }

    public function downloadPdf($id)
    {
        $invoice = Invoices::with(["branch" => function ($branch) {
            $branch->with(["business" => function ($business) {
                $business->with("country");
                $business->with("timezone");
            }]);
            $branch->with("phone_country");
        }, "client.phone_country", "items.taxes"])->whereId($id)->firstOrFail();

        $html = "
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: DejaVu Sans;
                font-size: 14px;
            }
        </style>
        ";
        $html .= '<p style="text-align: center; font-size: 20px; margin: 0;">' . $invoice->branch->business->name . '</p>';
        $html .= '<p style="text-align: center; margin: 0; padding: 5px 0;">' . $invoice->branch->name . ', ' . $invoice->branch->address . '</p>';
        $html .= '<p style="text-align: center; margin: 0;">+' . $invoice->branch->phone_country->phone_code . ' ' . Helpers::getMaskedPhoneNumber($invoice->branch->phone_number, $invoice->branch->phone_country->phone_mask) . '</p>';
        $html .= '<div style="border-top: 1px solid #DDDDDD; margin: 20px 0 10px; height: 1px;"></div>';
        $html .= '<table style="width: 100%;">';
        $html .= '<tr>';
        $html .= '<td style="vertical-align: top; line-height: 25px;">';
        if ($invoice->client) {
            $html .= '<p style="margin: 0;">' . $invoice->client->full_name . '</p>';
            $html .= '<p style="margin: 0;">+' . $invoice->client->phone_country->phone_code . ' ' . Helpers::getMaskedPhoneNumber($invoice->client->phone_number, $invoice->client->phone_country->phone_mask) . '</p>';
            $html .= '<p style="margin: 0;">' . $invoice->client->email . '</p>';
        } else {
            $html .= '<p style="margin: 0;">Walk-In</p>';
        }
        $html .= '</td>';
        $html .= '<td align="right" style="vertical-align: top; line-height: 25px;">';
        $html .= '<p style="margin: 0;">Invoice #' . $invoice->invoice_number . '</p>';
        $html .= '<p style="margin: 0;">' . Carbon::parse($invoice->created_at)->setTimezone($invoice->branch->business->timezone->timezone)->format("D, d M Y H:ia") . '</p>';
        $html .= '<p style="margin: 0;">Status: <b>' . strtoupper($invoice->status) . '</b></p>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '<table cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin-top: 20px;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="background: #000000; color: #FFFFFF; padding: 10px; text-align: center;">#</th>';
        $html .= '<th style="background: #000000; color: #FFFFFF; padding: 10px; text-align: left;">Item</th>';
        $html .= '<th style="background: #000000; color: #FFFFFF; padding: 10px; text-align: center;">Qty</th>';
        $html .= '<th style="background: #000000; color: #FFFFFF; padding: 10px; text-align: right;">Amount</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        $itemNumber = 1;
        $subTotal = $grandTotal = 0;
        $taxes = [];
        foreach ($invoice->items as $item) {
            $price = $item->price;
            $html .= '<tr>';
            $html .= '<td style="text-align: center; padding: 10px; vertical-align: top;">' . $itemNumber . '</td>';
            $html .= '<td style="text-align: left; padding: 10px; vertical-align: top;">' . $item->title . '</td>';
            $html .= '<td style="text-align: center; padding: 10px; vertical-align: top;">' . $item->quantity . '</td>';
            $html .= '<td style="text-align: right; padding: 10px; vertical-align: top;">';
            if ($item->discount > 0) {
                $html .= '<p style="margin: 0;">' . number_format($item->price - $item->discount, 2) . '</p>';
                $html .= '<p style="margin: 0;"><del>' . number_format($item->price, 2) . '</del></p>';
            } else {
                $html .= $invoice->branch->business->country->currency . ' ' . $price;
            }
            $html .= '</td>';
            $html .= '</tr>';
            $itemNumber++;
            $subTotal += ($item->price - $item->discount);
            $grandTotal += ($item->price - $item->discount);
            foreach ($item->taxes as $tax) {
                $taxRow = collect($taxes)->firstWhere("title", $tax->title);
                if ($taxRow) {
                    $taxRow["amount"] += $tax->amount;
                } else {
                    $taxes[] = [
                        "title" => $tax->title,
                        "rate" => $tax->rate,
                        "amount" => $tax->amount
                    ];
                }
                
                $grandTotal += $tax->amount;
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<table cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin-top: 20px;">';
        $html .= '<tr>';
        $html .= '<td style="width: 55%;"></td>';
        $html .= '<td>';
        $html .= '<table cellpadding="0" cellspacing="0" border="0" style="width: 100%;">';
        if (!empty($taxes)) {
            $html .= '<tr>';
            $html .= '<td>Subtotal</td>';
            $html .= '<td width="50%" style="text-align: right; padding: 5px 10px;">' . $invoice->branch->business->country->currency . ' ' . number_format($subTotal, 2) . '</td>';
            $html .= '</tr>';
            foreach ($taxes as $tax) {
                $html .= '<tr>';
                $html .= '<td>' . $tax["title"] . ' - ' . $tax["rate"] . '%</td>';
                $html .= '<td style="text-align: right; padding: 5px 10px;">' . $invoice->branch->business->country->currency . ' ' . number_format($tax["amount"], 2) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '<tr>';
        $html .= '<td style="font-weight: bold; padding-top: 10px;">Total</td>';
        $html .= '<td style="font-weight: bold; text-align: right; padding: 0 10px;">' . $invoice->branch->business->country->currency . ' ' . number_format($grandTotal, 2) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        if ($invoice->notes) {
            $html .= '<div style="border-top: 1px solid #DDDDDD; margin: 20px 0 10px; height: 1px;"></div>';
            $html .= '<p style="margin: 0; font-style: italic;">' . $invoice->notes . '</p>';
        }
        
        $pdf = App::make("dompdf.wrapper");
        $pdf->loadHTML($html);

        return $pdf->download();
    }
}