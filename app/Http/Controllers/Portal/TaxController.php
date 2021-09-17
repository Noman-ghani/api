<?php

namespace App\Http\Controllers\Portal;

use App\Models\Taxes;
use Illuminate\Http\Request;
use App\Helpers\Helpers;
use App\Models\BranchProducts;
use App\Models\BranchServices;
use Laravel\Lumen\Routing\Controller;

class TaxController extends Controller
{
    public function browse(Request $request)
    {
        return Taxes::whereBusinessId(Helpers::getJWTData("business_id"))->get();
    }

    public function store(Request $request, $type, $id = null)
    {
        if ($type === "tax") {
            $this->validate($request, [
                "title" => "required",
                "rate" => "required"
            ]);
        } else {
            $this->validate($request, [
                "title" => "required",
                "tax_1" => "required",
                "tax_2" => "required"
            ]);
        }
        
        $model = Taxes::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $model->title = $request->title;
        
        if ($type === "tax") {
            $model->rate = $request->rate;
        } else {
            $model->tax_1 = $request->tax_1;
            $model->tax_2 = $request->tax_2;

            if ($request->get("tax_3")) {
                $model->tax_3 = $request->tax_3;
            }
        }

        $model->save();

        return ["success" => true, "message" => __("Tax saved successfully.")];
    }

    public function delete_by_id(Request $request, $id)
    {
        $tax = Taxes::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();

        if (!$tax) {
            return response("Unauthorized", 403);
        }

        // if tax belongs to any group, we will not allow it to be deleted.
        if ($tax->rate && (Taxes::where("tax_1", $tax->id)->exists() || Taxes::where("tax_2", $tax->id)->exists() || Taxes::where("tax_3", $tax->id)->exists())) {
            return ["success" => false, "message" => __("Tax cannot be deleted as tax belongs to tax group.")];
        }

        $tax->delete();
        
        return ["success" => true, "message" => __("Tax deleted successfully.")];
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
}