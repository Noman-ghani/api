<?php

namespace App\Http\Controllers\MarketPlace;

use Illuminate\Http\Request;
use App\Models\Branches;
use App\Models\BranchProducts;
use App\Models\BranchServices;
use Laravel\Lumen\Routing\Controller;

class TaxController extends Controller
{
    public function get_by_service(Request $request)
    {
        $branchService = BranchServices::with(["tax" => function ($query) {
            $query->with("tax_1")->with("tax_2")->with("tax_3");
        }]);
        
        if ($request->has("branch_id")) {
            $branchService = $branchService->whereBranchId($request->get("branch_id"));
        }

        if ($request->has("service_id")) {
            $branchService = $branchService->whereServiceId($request->get("service_id"));
        }

        return $branchService->first();
    }

    public function get_by_product(Request $request)
    {
        $branchProduct = BranchProducts::with(["tax" => function ($query) {
            return $query->with("tax_1")->with("tax_2")->with("tax_3");
        }]);
        
        if ($request->has("branch_id")) {
            $branchProduct = $branchProduct->whereBranchId($request->get("branch_id"));
        }

        if ($request->has("product_id")) {
            $branchProduct = $branchProduct->whereProductId($request->get("product_id"));
        }

        return $branchProduct->first();
    }

}