<?php

namespace App\Http\Controllers\Portal;

use App\Models\BranchServices;
use App\Helpers\Helpers;
use App\Models\Services;
use App\Models\ServicesCategories;
use App\Models\StaffServices;
use App\Models\ServicesPricings;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Lumen\Routing\Controller;

class ServiceController extends Controller
{
    public function browse(Request $request)
    {
        if ($request->get("group_by") === "category_hierarchy") {
            return ServicesCategories::whereBusinessId(Helpers::getJWTData("business_id"))->with(["services" => function ($query) use ($request) {
                $query->with("pricings");

                if ($request->has("branch_id")) {
                    $query->whereHas("branches", function ($query) use ($request) {
                        $query->whereBranchId($request->branch_id);
                    });
                }

                if ($request->has("staff_id")) {
                    $query->whereHas("staffs", function ($query) use ($request) {
                        $query->whereStaffId($request->staff_id);
                    });
                }
            }])->whereBusinessId(Helpers::getJWTData("business_id"))
            ->get();
        }

        $model = new Services();

        if ($request->has("with-category")) {
            $model = $model->with(["category"]);
        }

        if ($request->has("with-pricings")) {
            $model = $model->with(["pricings"]);
        }

        $model = $model->whereBusinessId(Helpers::getJWTData("business_id"));
        $model = $model->whereIsActive(1);
        $model = $model->orderBy("title");

        if ($request->has("category_id")) {
            $model = $model->whereCategoryId($request->category_id);
        }

        if ($request->has("branch_id")) {
            $model = $model->whereHas("branches", function ($query) use ($request) {
                $query->where("branch_id", $request->branch_id);
            });
        }

        return $model->get();
    }

    public function store_service(Request $request, $id = null)
    {
        $this->validate($request, [
            "title" => [
                "required",
                Rule::unique("services")
                ->where("business_id", Helpers::getJWTData("business_id"))
                ->ignore($id)
            ],
            "category_id" => "required",
            "treatment_type" => "required",
            "pricing" => "required|array",
            "staff_ids" => "required|array",
            "branch_ids" => "required|array",
            "tax_ids" => "nullable|array"
        ]);

        DB::beginTransaction();
        
        try {
            $service = Services::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
            $service->is_package = 0;
            $service->title = $request->title;
            $service->treatment_type = $request->treatment_type;
            $service->category_id = $request->category_id;
            $service->available_for = $request->available_for;
            $service->description = $request->description;
            $service->enable_online_booking = $request->enable_online_booking ? 1 : 0;
            $service->enable_commission = $request->enable_commission ? 1 : 0;
            $service->save();

            StaffServices::whereServiceId($service->id)->delete();
            BranchServices::whereServiceId($service->id)->delete();
            ServicesPricings::whereServiceId($service->id)->delete();

            foreach ($request->staff_ids as $staff_id) {
                StaffServices::create([
                    "service_id" => $service->id,
                    "staff_id" => $staff_id
                ]);
            }

            foreach ($request->branch_ids as $branch_id) {
                $tax = Arr::first(Arr::where($request->tax_ids, function ($tax) use ($branch_id) {
                    return $tax["branch_id"] == $branch_id;
                }));
                
                BranchServices::create([
                    "service_id" => $service->id,
                    "branch_id" => $branch_id,
                    "tax_id" => $tax["tax_id"]
                ]);
            }

            $pricings = $request->pricing;

            usort($pricings, function($a, $b) {
                return $a["price"] - $b["price"];
            });

            foreach ($pricings as $pricing) {
                ServicesPricings::create([
                    "service_id" => $service->id,
                    "name" => $pricing["name"] !== "" ? $pricing["name"] : null,
                    "duration" => $pricing["duration"],
                    "price" => $pricing["price"],
                    "special_price" => !empty($pricing["special_price"]) ? $pricing["special_price"] : 0
                ]);
            }

            DB::commit();
            return ["success" => true, "message" => __("Service saved successfully.")];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function get_by_id(Request $request, $id)
    {
        $model = Services::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);

        if ($request->has("with-pricings")) {
            $model = $model->with("pricings");
        }

        if ($request->has("with-staffs")) {
            $model = $model->with("staffs");
        }

        if ($request->has("with-branches")) {
            $model = $model->with("branches");
        }
        
        $model = $model->firstOrFail();
        $model->treatment_type = Helpers::getTreatmentTypes($model->treatment_type);

        return $model;
    }
}