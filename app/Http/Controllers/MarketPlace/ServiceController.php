<?php

namespace App\Http\Controllers\MarketPlace;

use App\Models\Branches;
use App\Models\Businesses;
use App\Models\ServicesCategories;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use App\Helpers\Helpers;

class ServiceController extends Controller
{
    public function browse(Request $request)
    {
        $branch = Branches::with(['city', 'state','business.country']);
        $business = null;

        if ($request->has("with-staff")) {
            $branch = $branch->with(["staff" => function($staffServices){
                $staffServices->with(["staff" => function ($staff){
                    $staff->where('enable_appointments',1);
                }]);
                $staffServices->whereHas("staff", function ($staff){
                    $staff->where('enable_appointments',1);
                });
            }]);
        }

        if ($request->has("with-timings")) {
            $branch = $branch->with(["timings"]);
        }
        
        if ($request->has("branch_slug")) {
            $branch = $branch->where('slug', $request->branch_slug)->first();
        }
        $branch_id = $branch->id;

        $model = ServicesCategories::whereHas("services", function ($services) use ($request, $branch_id) {
            $services->whereHas("staffs", function ($staffs) use ($branch_id) {
                $staffs->whereHas("staff", function ($staff) use ($branch_id) {
                    $staff->whereEnableAppointments(1);
                    $staff->whereHas("branches", function($branches) use ($branch_id){
                        $branches->whereBranchId($branch_id);
                    });
                });
            });

            if ($request->has("branch_slug")) {
                $services->whereHas("branches", function ($branches) use ($branch_id) {
                    $branches->where("branch_id", $branch_id);
                });
            }
        })->with(["services" => function ($services) use ($request, $branch_id) {
            $services->with(["pricings","branches" => function($branches) {
                $branches->with(['tax' => function($tax) {
                    $tax->with("tax_1")->with("tax_2")->with("tax_3");
                }]);
            }]);
        }]);

        
        $model = $model->get();
        
        $colorList = Helpers::getColorList();
        $durations = Helpers::getDurations();

        return [
            'data' => $model,
            'branch' => $branch,
            "settings" => [
                "colors" => $colorList,
                "durations" => $durations
            ]
        ];
    }
}