<?php

namespace App\Http\Controllers\Portal\Inventory;

use App\Helpers\Helpers;
use App\Models\BranchProducts;
use App\Models\Inventory\Products;
use App\Models\Inventory\StockHistory;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;

class StocksController extends Controller
{
    public function browse($id)
    {
        $products = StockHistory::with(["branch", "staff"])->whereBusinessId(Helpers::getJWTData("business_id"))->whereProductId($id)->get();
        $stock_on_hand = 0;

        $products = $products->map(function ($row) use (&$stock_on_hand) {
            if ($row->action === '+') {
                if ($row->reason) {
                    if ($row->reason === "other") {
                        $row->description = "Other: {$row->description}";
                    } else {
                        $row->description = Helpers::getIncreaseStockReasons($row->reason);
                    }
                } else {
                    $row->description = "wah reh wah";
                }
                $row->text = $row->staff->full_name . " adjusted stock (+{$row->quantity})";
                $stock_on_hand += $row->quantity;
            } else if ($row->action === '-') {
                if ($row->reason) {
                    if ($row->reason === "other") {
                        $row->description = "Other: {$row->description}";
                    } else {
                        $row->description = Helpers::getDecreaseStockReasons($row->reason);
                    }
                } else {
                    $row->description = "wah reh wah";
                }
                $row->text = $row->staff->full_name . " returned stock (-{$row->quantity})";
                $stock_on_hand -= $row->quantity;
            }
            
            $row->stock_on_hand = $stock_on_hand;
            
            return $row;
        });

        return array_reverse($products->toArray());
    }
    
    public function increase(Request $request, $branch_id, $product_id)
    {
        $this->validate($request, [
            "reason" => "required",
            "quantity" => "required"
        ]);

        DB::beginTransaction();
        
        try {
            $product = Products::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($product_id)->firstOrFail();
            $staff = Staff::whereBusinessId(Helpers::getJWTData("business_id"))->whereUserId(Auth::user()->id)->firstOrFail();
            $product->stock_on_hand += $request->quantity;
            $product->save();
            
            $stockHistory = new StockHistory();
            $stockHistory->business_id = Helpers::getJWTData("business_id");
            $stockHistory->product_id = $product_id;
            $stockHistory->branch_id = $branch_id;
            $stockHistory->staff_id = $staff->id;
            $stockHistory->reason = $request->reason;
            $stockHistory->description = $request->description;
            $stockHistory->action = "+";
            $stockHistory->quantity = $request->quantity;
            $stockHistory->cost_price = 0;
            $stockHistory->save();

            $branchProduct = BranchProducts::firstOrNew(["branch_id" => $branch_id, "product_id" => $product_id]);

            if (!$branchProduct->id) {
                $branchProduct->stock_on_hand = 0;
            }

            $branchProduct->stock_on_hand += $request->quantity;
            $branchProduct->save();

            DB::commit();
            return ["success" => true, "message" => __("Stock history saved successfully.")];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function decrease(Request $request, $branch_id, $product_id)
    {
        $this->validate($request, [
            "reason" => "required",
            "quantity" => "required"
        ]);

        DB::beginTransaction();

        try {
            $product = Products::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($product_id)->firstOrFail();
            $product->stock_on_hand -= $request->quantity;
            $product->save();
            
            $staff = Staff::whereBusinessId(Helpers::getJWTData("business_id"))->whereUserId(Auth::user()->id)->firstOrFail();
            $stockHistory = new StockHistory();
            $stockHistory->business_id = Helpers::getJWTData("business_id");
            $stockHistory->product_id = $product_id;
            $stockHistory->branch_id = $branch_id;
            $stockHistory->staff_id = $staff->id;
            $stockHistory->reason = $request->reason;
            $stockHistory->description = $request->description;
            $stockHistory->action = "-";
            $stockHistory->quantity = $request->quantity;
            $stockHistory->save();

            $branchProduct = BranchProducts::whereBranchId($branch_id)->whereProductId($product_id)->firstOrFail();
            $branchProduct->stock_on_hand -= $request->quantity;
            $branchProduct->save();

            DB::commit();
            return ["success" => true, "message" => __("Stock history saved successfully.")];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}