<?php

namespace App\Http\Controllers\Portal\Inventory;

use App\Helpers\Helpers;
use App\Models\BranchProducts;
use App\Models\Inventory\Categories;
use App\Models\Inventory\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Laravel\Lumen\Routing\Controller;

class ProductsController extends Controller
{
    public function browse(Request $request)
    {
        if ($request->get("group_by") === "category_hierarchy") {
            $products = Categories::whereBusinessId(Helpers::getJWTData("business_id"))->with(["products" => function ($query) use ($request) {
                $query->with("brand");

                if ($request->has("branch_id")) {
                    $query->whereHas("branches", function ($query) use ($request) {
                        $query->whereBranchId($request->branch_id);
                    });
                }

                if ($request->has("stock_available")) {
                    $query->where("stock_on_hand", '>', 0);
                }
            }]);
        } else {
            $products = Products::whereBusinessId(Helpers::getJWTData("business_id"));
        }
        
        return $products->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "name" => [
                "required",
                Rule::unique("inventory_products")
                ->where("business_id", Helpers::getJWTData("business_id"))
                ->ignore($id)
            ],
            "category_id" => "required",
            "brand_id" => "required",
            "retail_price" => "required",
            "branch_ids" => "required|array",
            "tax_ids" => "required|array"
        ]);

        $product = Products::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->barcode = $request->barcode;
        $product->sku = $request->sku;
        $product->retail_price = $request->retail_price;
        $product->special_price = $request->special_price ?? 0;
        $product->enable_commission = $request->enable_commission ?? 0;
        $product->supplier_id = $request->supplier_id;
        $product->supply_price = $request->supply_price ?? 0;
        $product->stock_on_hand = $product->name ? $product->stock_on_hand : 0;
        $product->save();

        foreach ($request->branch_ids as $branch_id) {
            $tax = Arr::first(Arr::where($request->tax_ids, function ($tax) use ($branch_id) {
                return $tax["branch_id"] == $branch_id;
            }));
            
            $branchProduct = BranchProducts::firstOrNew(["branch_id" => $branch_id, "product_id" => $product->id]);

            if (!$branchProduct->id) {
                $branchProduct->stock_on_hand = 0;
            }

            $branchProduct->tax_id = $tax["tax_id"];
            $branchProduct->save();
        }

        return ["success" => true, "data" => ["id" => $product->id], "message" => __("Product saved successfully")];
    }

    public function get_by_id(Request $request, $id)
    {
        $product = Products::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);

        if ($request->has("with-branches")) {
            $product->with("branches");
        }
        if ($request->has("with-brand")) {
            $product->with("brand");
        }
        if ($request->has("with-category")) {
            $product->with("category");
        }
        if ($request->has("with-supplier")) {
            $product->with("supplier");
        }

        return $product->firstOrFail();
    }
}