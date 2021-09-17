<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\Discounts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Lumen\Routing\Controller;

class DiscountsController extends Controller
{
    public function browse()
    {
        return Discounts::whereBusinessId(Helpers::getJWTData("business_id"))->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "title" => [
                "required",
                Rule::unique("discounts")
                ->where("business_id", Helpers::getJWTData("business_id"))
                ->ignore($id)
            ],
            "type" => "required",
            "value" => "required",
            "enable_for_service" => "required",
            "enable_for_product" => "required",
            "enable_for_voucher" => "required"
        ]);

        $model = Discounts::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $model->title = $request->title;
        $model->type = $request->type;
        $model->value = $request->value;
        $model->enable_for_service = $request->enable_for_service;
        $model->enable_for_product = $request->enable_for_product;
        $model->enable_for_voucher = $request->enable_for_voucher;

        if ($model->save()) {
            return ["success" => true, "message" => __("Discount saved successfully.")];
        }

        return ["success" => false, "message" => __("An error occurred while updating discount.")];
    }

    public function delete_by_id($id)
    {
        $model = Discounts::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
        $model->delete();
            
        return ["success" => true, "message" => __("Discount has been deleted successfully.")];
    }
}