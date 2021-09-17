<?php

namespace App\Helpers;

use App\Http\Controllers\Portal\AppointmentsController;
use App\Jobs\SendSmsJob;
use App\Models\Appointments;
use App\Models\Branches;
use App\Models\BranchProducts;
use App\Models\Businesses;
use App\Models\ClientDeals;
use App\Models\ClientDealsItems;
use App\Models\Deals;
use App\Models\Inventory\Products;
use App\Models\Inventory\StockHistory;
use App\Models\InvoiceItems;
use App\Models\InvoiceItemsDiscounts;
use App\Models\InvoiceItemsTaxes;
use App\Models\Invoices as ModelsInvoices;
use App\Models\Services;
use App\Models\Staff;
use App\Models\Transactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Invoices
{
    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            $business = Businesses::whereIsActive(1)->whereId($request->business_id)->firstOrFail();
            $branch = Branches::whereBusinessId($request->business_id)->whereId($request->branch_id)->firstOrFail();
            $discount = 0;

            foreach ($request->items as $item) {
                $discount += ($item["discount"] ?? 0) * $item["quantity"];
            }
            
            $invoice = new ModelsInvoices();
            $invoice->business_id = $request->business_id;
            $invoice->branch_id = $request->branch_id;
            $invoice->client_id = $request->client_id;
            $invoice->payment_method_id = $request->payment_method_id ?? null;
            $invoice->status = $request->payment_method_id ? "completed" : "unpaid";
            $invoice->is_tax_inclusive = $business->is_tax_inclusive;
            $invoice->invoice_number = ($branch->invoice_prefix ? $branch->invoice_prefix . '-' : '') . $branch->next_invoice_number;
            $invoice->discount = $discount;
            $invoice->subtotal = $request->subtotal;
            $invoice->tax = $request->tax;
            $invoice->grandtotal = $request->grandtotal;
            $invoice->balance = $request->payment_method_id ? $request->grandtotal : 0;
            $invoice->payment_received_by = $request->payment_received_by;
            $invoice->notes = $request->notes;
            $invoice->save();

            foreach ($request->items as $item) {
                if (!$item["title"]) {
                    throw new \Exception("Item title is required");
                }

                $itemTaxAmount = 0;
                if (!empty($item["taxes"])) {
                    foreach ($item["taxes"] as $taxes) {
                        $itemTaxAmount += $taxes["amount"];
                    }
                }

                if ($item["type"] === "deal") {
                    // if client has already purchased the deal, system will not allow that deal to be purchased again
                    if (ClientDeals::whereClientId($request->client_id)->whereDealId($item["id"])->where("expires_at", '>=', Carbon::now())->exists()) {
                        throw new \Exception("This deal is already purchased.");
                    }

                    // increment the deals for utilization
                    $deal = Deals::whereBusinessId($request->business_id)->whereId($item["id"])->firstOrFail();
                    $deal->utilized++;
                    $deal->save();
                }

                // if deal is being utilized
                if (!empty($item["client_deal_id"])) {
                    $clientDeal = ClientDeals::with("deal")->whereClientId($request->client_id)->whereId($item["client_deal_id"])->firstOrFail();
                    $clientDealItem = ClientDealsItems::whereClientDealId($clientDeal->id);
                    
                    if ($item["type"] === "service") {
                        $clientDealItem = $clientDealItem->whereServiceId($item["id"]);
                    } else if ($item["type"] === "product") {
                        $clientDealItem = $clientDealItem->whereProductId($item["id"]);
                    }

                    $clientDealItem = $clientDealItem->firstOrFail();
                    $clientDealItem->quantity_utilized += ($item["quantity"] < $clientDealItem->quantity_available) ? $item["quantity"] : $clientDealItem->quantity_available;
                    $clientDealItem->save();
                }
                
                $invoiceItems = new InvoiceItems();
                $invoiceItems->invoice_id = $invoice->id;
                $invoiceItems->product_id = $item["type"] === "product" ? $item["id"] : null;
                $invoiceItems->service_id = $item["type"] === "service" ? $item["id"] : null;
                $invoiceItems->deal_id = $item["type"] === "deal" ? $item["id"] : null;
                $invoiceItems->staff_id = $item["staff_id"] ?? null;
                $invoiceItems->title = $item["title"];
                $invoiceItems->price = $item["price"];
                $invoiceItems->quantity = $item["quantity"];
                $invoiceItems->deal_quantity = 0;
                $invoiceItems->tax = $itemTaxAmount;
                $invoiceItems->discount = $item["discount"];
                $invoiceItems->client_deal_id = $item["client_deal_id"] ?? null;

                if (isset($item["deal_quantity"]) && $item["deal_quantity"] > 0) {
                    $invoiceItems->deal_quantity = $item["deal_quantity"] >= $item["quantity"] ? $item["quantity"] : $item["deal_quantity"];
                }
                
                // calculate staff commission
                if ($invoiceItems->staff_id) {
                    $staff = Staff::whereBusinessId($request->business_id)->whereId($invoiceItems->staff_id)->firstOrFail();
                    $commission = null;
                    $isCommissionEnabled = false;
                    
                    if ($item["type"] === "service") {
                        $commission = $staff->service_commission;
                        $service = Services::whereBusinessId($request->business_id)->whereId($invoiceItems->service_id)->firstOrfail();
                        $isCommissionEnabled = $service->enable_commission == 1;
                    } else if ($item["type"] === "product") {
                        $commission = $staff->product_commission;
                        $product = Products::whereBusinessId($request->business_id)->whereId($invoiceItems->product_id)->firstOrfail();
                        $isCommissionEnabled = $product->enable_commission == 1;
                    } else if ($item["type"] === "deal") {
                        $commission = $staff->deal_commission;
                        $deal = Deals::whereBusinessId($request->business_id)->whereId($invoiceItems->deal_id)->firstOrfail();
                        $isCommissionEnabled = $deal->enable_commission == 1;
                    }
                    
                    if ($commission > 0 && $isCommissionEnabled) {
                        $calculateCommissionOnPrice = $invoiceItems->price;
                        
                        if ($business->staff_commission_logic === 2) {
                            $calculateCommissionOnPrice += $invoiceItems->tax;
                        } else if ($business->staff_commission_logic === 3) {
                            $calculateCommissionOnPrice -= $invoiceItems->discount;
                        } else if ($business->staff_commission_logic === 4) {
                            $calculateCommissionOnPrice -= $invoiceItems->discount;
                            $calculateCommissionOnPrice += $invoiceItems->tax;
                        }
                        
                        $invoiceItems->staff_commission_logic = $business->staff_commission_logic;
                        $invoiceItems->staff_commission_rate = $commission;
                        $invoiceItems->staff_commission_value = $calculateCommissionOnPrice * ($commission / 100);
                    }
                }

                $invoiceItems->save();
                
                foreach ($item["taxes"] as $tax) {
                    $invoiceItemTaxes = new InvoiceItemsTaxes();
                    $invoiceItemTaxes->invoice_id = $invoice->id;
                    $invoiceItemTaxes->invoice_item_id = $invoiceItems->id;
                    $invoiceItemTaxes->tax_id = $tax["id"];
                    $invoiceItemTaxes->title = $tax["title"];
                    $invoiceItemTaxes->rate = $tax["rate"];
                    $invoiceItemTaxes->amount = $tax["amount"];
                    $invoiceItemTaxes->save();
                }

                if ($item["discount_id"]) {
                    $discountOption = collect($item["discountOptions"])->firstWhere("id", $item["discount_id"]);
                    $invoiceItemDiscounts = new InvoiceItemsDiscounts();
                    $invoiceItemDiscounts->invoice_id = $invoice->id;
                    $invoiceItemDiscounts->invoice_item_id = $invoiceItems->id;
                    $invoiceItemDiscounts->discount_id = $item["discount_id"] === "special" ? null : $item["discount_id"];
                    $invoiceItemDiscounts->type = "amount";
                    $invoiceItemDiscounts->title = $discountOption["text"];
                    $invoiceItemDiscounts->value = $discountOption["value"];
                    $invoiceItemDiscounts->save();
                }

                if (isset($item["original_price"]) && ($item["original_price"] - $item["price"]) > 0) {
                    $invoiceItemDiscounts = new InvoiceItemsDiscounts();
                    $invoiceItemDiscounts->invoice_id = $invoice->id;
                    $invoiceItemDiscounts->invoice_item_id = $invoiceItems->id;
                    $invoiceItemDiscounts->discount_id = null;
                    $invoiceItemDiscounts->type = "amount";
                    $invoiceItemDiscounts->title = "Manual Discount";
                    $invoiceItemDiscounts->value = $item["original_price"] - $item["price"];
                    $invoiceItemDiscounts->save();
                }

                if ($item["type"] === "product") {
                    $branchProduct = BranchProducts::whereBranchId($request->branch_id)->whereProductId($item["id"])->firstOrFail();
                    $branchProduct->stock_on_hand -= $item["quantity"];
                    $branchProduct->save();

                    $product = Products::whereBusinessId($request->business_id)->whereId($item["id"])->firstOrFail();
                    $product->stock_on_hand -= $item["quantity"];
                    $product->save();

                    $stockHistory = new StockHistory();
                    $stockHistory->business_id = $request->business_id;
                    $stockHistory->product_id = $item["id"];
                    $stockHistory->branch_id = $request->branch_id;
                    $stockHistory->staff_id = $item["staff_id"] ?? null;
                    $stockHistory->action = "+";
                    $stockHistory->quantity = $item["quantity"];
                    $stockHistory->invoice_id = $invoice->id;
                    $stockHistory->description = "Sale against Invoice #{$invoice->invoice_number}";
                    $stockHistory->save();
                }
            }

            if ($request->has("appointment_id")) {
                $appointment = Appointments::whereBusinessId($request->business_id)->whereId($request->appointment_id)->firstOrFail();
                $appointment->invoice_id = $invoice->id;
                $appointment->client_id = $invoice->client_id;
                $appointment->status = "completed";
                $appointment->save();
            }

            if ($invoice->status === "completed" && $invoice->payment_method_id) {
                $completeSale = $this->completeSale($request, $invoice->id);

                if (!$completeSale["success"]) {
                    throw new \Exception($completeSale["message"]);
                }
            }

            // increment invoice number for upcoming invoices
            $branch->next_invoice_number++;
            $branch->save();

            // send invoice email to client
            if ($request->client_id) {}

            DB::commit();
            return ["success" => true, "message" => "Invoice generated successfully", "invoice_id" => $invoice->id];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function completeSale(Request $request, $invoice_id)
    {
        DB::beginTransaction();
        
        try {
            $invoice = ModelsInvoices::with(["business", "client"])->whereBusinessId($request->business_id)->whereId($invoice_id)->firstOrFail();
            $invoice->payment_method_id = $request->payment_method_id;
            $invoice->balance = 0;
            $invoice->payment_created_at = Carbon::now();
            $invoice->status = "completed";
            $invoice->save();

            $dealItems = InvoiceItems::with("deal.inclusions")->whereInvoiceId($invoice->id)->whereNotNull("deal_id")->get();
            foreach ($dealItems as $dealItem) {
                $clientDeals = new ClientDeals();
                $clientDeals->client_id = $invoice->client_id;
                $clientDeals->deal_id = $dealItem->deal_id;
                $clientDeals->expires_at = Carbon::now()->addDays($dealItem->deal->expires_in_days);
                $clientDeals->invoice_id = $invoice_id;
                $clientDeals->save();

                foreach ($dealItem->deal->inclusions as $inclusion) {
                    $clientDealsItems = new ClientDealsItems();
                    $clientDealsItems->client_deal_id = $clientDeals->id;
                    $clientDealsItems->service_id = $inclusion->service_id;
                    $clientDealsItems->product_id = $inclusion->product_id;
                    $clientDealsItems->quantity_available = $inclusion->quantity;
                    $clientDealsItems->quantity_utilized = 0;
                    $clientDealsItems->save();
                }
            }

            if ($invoice->client_id) {
                $appointment = Appointments::whereInvoiceId($invoice->id)->first();

                if ($appointment) {
                    dispatch(new SendSmsJob($invoice->business_id, $invoice->client->phone_number, "Dear {$invoice->client->first_name},\n\nThank you for visiting us.\n\n{$invoice->business->name}"));
                } else {
                    dispatch(new SendSmsJob($invoice->business_id, $invoice->client->phone_number, "Dear {$invoice->client->first_name},\n\nThank you for your purchase.\n\n{$invoice->business->name}"));
                }
            }

            if ($request->has("transaction_num")) {
                $transaction = new Transactions();
                $transaction->business_id = $request->business_id;
                $transaction->transaction_num = $request->transaction_num;
                $transaction->status = $request->status;
                $transaction->payment_id = $request->payment_method_id;
                $transaction->invoice_id = $invoice_id;
                $transaction->save();
            }

            DB::commit();
            return ["success" => true, "message" => "Invoice completed successfully"];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function voidSale(Request $request, $invoice_id)
    {
        DB::beginTransaction();

        try {
            $invoice = ModelsInvoices::with(["items"])->whereBusinessId($request->business_id)->whereId($invoice_id)->firstOrFail();

            foreach ($invoice->items as $item) {
                if ($item->product_id) {
                    $branchProduct = BranchProducts::whereBranchId($invoice->branch_id)->whereProductId($item->product_id)->firstOrFail();
                    $branchProduct->stock_on_hand += $item->quantity;
                    $branchProduct->save();

                    $inventory = Products::whereBusinessId($request->business_id)->whereId($item->product_id)->firstOrFail();
                    $inventory->stock_on_hand += $item->quantity;
                    $inventory->save();

                    $stockHistory = new StockHistory();
                    $stockHistory->business_id = $request->business_id;
                    $stockHistory->product_id = $item->product_id;
                    $stockHistory->branch_id = $invoice->branch_id;
                    $stockHistory->staff_id = $item->staff_id;
                    $stockHistory->action = "+";
                    $stockHistory->quantity = $item->quantity;
                    $stockHistory->description = "Voided Invoice #{$invoice->invoice_number}";
                    $stockHistory->save();
                }

                if ($item->deal_id) {
                    // decrease deal utilization so that it can be purchaseable if not all limit exhausted
                    $deal = Deals::whereBusinessId($request->business_id)->whereId($item->deal_id)->firstOrFail();
                    $deal->utilized--;
                    $deal->save();
                    
                    // delete this deal from client deals so that in future, client can purchase this deal again
                    $clientDeal = ClientDeals::whereClientId($invoice->client_id)->whereDealId($item->deal_id)->first();
                    // dd($clientDeal);
                    if($clientDeal){
                        ClientDealsItems::whereClientDealId($clientDeal)->delete();
                        $clientDeal->delete();
                    }
                }

                if ($item->client_deal_id) {
                    $clientDealsItems = ClientDealsItems::whereClientDealId($item->client_deal_id);
                    if ($item->service_id) {
                        $clientDealsItems = $clientDealsItems->whereServiceId($item->service_id);
                    } else if ($item->product_id) {
                        $clientDealsItems = $clientDealsItems->whereProductId($item->product_id);
                    }
                    $clientDealsItems = $clientDealsItems->firstOrFail();
                    $clientDealsItems->quantity_utilized -= $item->quantity;
                    $clientDealsItems->save();
                }
            }
            
            $appointment = Appointments::whereBusinessId($request->business_id)->whereInvoiceId($invoice_id)->first();
            if ($appointment) {
                $appointmentsController = new AppointmentsController();
                $appointmentsController->changeStatus(new Request([
                    "status" => "cancelled",
                    "cancel_reason_id" => 4
                ]), $appointment->id);
            }

            $invoice->status = "voided";
            $invoice->void_created_at = Carbon::now();
            $invoice->save();
            DB::commit();
            return ["success" => true, "message" => "Invoice voided successfully."];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function refundSale(Request $request, $invoice_id)
    {
        DB::beginTransaction();

        try {
            $originalInvoice = ModelsInvoices::with("items")->whereBusinessId($request->business_id)->whereId($invoice_id)->firstOrFail();
            $branch = Branches::whereBusinessId($request->business_id)->whereId($originalInvoice->branch_id)->firstOrFail();
            $refundInvoice = $originalInvoice->replicate();
            $quantity = 0;
            $grossTotal = 0;
            $discounts = 0;
            $taxes = 0;

            foreach ($request->items as $item) {
                $quantity += $item["quantity"];
            }

            foreach ($originalInvoice->items as $item) {
                $grossTotal += ($item->price * $quantity);
                $discounts += ($item->discount * $quantity);
                $taxes += ($item->tax * $quantity);
            }

            $refundInvoice->payment_created_at = Carbon::now();
            $refundInvoice->original_invoice_id = $invoice_id;
            $refundInvoice->status = "refunded";
            $refundInvoice->discount = $discounts;
            $refundInvoice->subtotal = $grossTotal - $discounts;
            $refundInvoice->tax = $taxes;
            $refundInvoice->grandtotal = $refundInvoice->subtotal + $refundInvoice->tax;
            $refundInvoice->notes = $request->notes;
            $refundInvoice->invoice_number = ($branch->invoice_prefix ? $branch->invoice_prefix . '-' : '') . $branch->next_invoice_number;
            $refundInvoice->save();
            
            foreach ($request->items as $item) {
                $originalInvoiceItem = InvoiceItems::whereInvoiceId($invoice_id);
                if ($item["product_id"]) {
                    $originalInvoiceItem->whereProductId($item["product_id"]);
                } else if ($item["service_id"]) {
                    $originalInvoiceItem->whereServiceId($item["service_id"]);
                }

                $originalInvoiceItem = $originalInvoiceItem->firstOrFail();
                $refundInvoiceItem = $originalInvoiceItem->replicate();
                $refundInvoiceItem->invoice_id = $refundInvoice->id;
                $refundInvoiceItem->quantity = $item["quantity"];
                $refundInvoiceItem->save();

                // replicate taxes
                $originalInvoiceItemTaxes = InvoiceItemsTaxes::whereInvoiceId($invoice_id)->get();
                foreach ($originalInvoiceItemTaxes as $originalInvoiceItemTax) {
                    $refundInvoiceItemTax = $originalInvoiceItemTax->replicate();
                    $refundInvoiceItemTax->invoice_id = $refundInvoice->id;
                    $refundInvoiceItemTax->invoice_item_id = $refundInvoiceItem->id;
                    $refundInvoiceItemTax->save();
                }

                //replicate discounts
                $originalInvoiceItemDiscounts = InvoiceItemsDiscounts::whereInvoiceId($invoice_id)->get();
                foreach ($originalInvoiceItemDiscounts as $originalInvoiceItemDiscount) {
                    $refundInvoiceItemDiscount = $originalInvoiceItemDiscount->replicate();
                    $refundInvoiceItemDiscount->invoice_id = $refundInvoice->id;
                    $refundInvoiceItemDiscount->invoice_item_id = $refundInvoiceItem->id;
                    $refundInvoiceItemDiscount->save();
                }

                if ($item["product_id"]) {
                    $branchProduct = BranchProducts::whereBranchId($request->branch_id)->whereProductId($item["product_id"])->firstOrFail();
                    $branchProduct->stock_on_hand += $item["quantity"];
                    $branchProduct->save();

                    $product = Products::whereBusinessId($request->business_id)->whereId($item["product_id"])->firstOrFail();
                    $product->stock_on_hand += $item["quantity"];
                    $product->save();

                    $stockHistory = new StockHistory();
                    $stockHistory->business_id = $request->business_id;
                    $stockHistory->product_id = $item["id"];
                    $stockHistory->branch_id = $originalInvoice->branch_id;
                    $stockHistory->staff_id = $item["staff_id"] ?? null;
                    $stockHistory->action = "-";
                    $stockHistory->quantity = $item["quantity"];
                    $stockHistory->invoice_id = $refundInvoice->id;
                    $stockHistory->description = "Refund against Invoice #{$refundInvoice->invoice_number}";
                    $stockHistory->save();
                }
            }

            // increment invoice number for upcoming invoices
            $branch->next_invoice_number++;
            $branch->save();

            DB::commit();
            return ["success" => true, "message" => "Invoice refunded successfully.", "invoice_id" => $refundInvoice->id];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}