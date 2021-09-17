<?php

namespace App\Http\Controllers\MarketPlace;

use App\Helpers\Helpers;
use App\Models\Businesses;
use App\Models\Branches;
use App\Models\Services;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Carbon;

class BranchController extends Controller
{
    public function browse(Request $request)
    {
        
        $branch = Branches::with(["state", "city","business" => function($bus){
            $bus->whereDate("subscription_expires_at", ">=", Carbon::now());
        }])->whereHas("business", function($bus){
            $bus->whereDate("subscription_expires_at", ">=", Carbon::now());
        });
        
        if($request->has('city_id') && !empty($request->city_id)){
            $branch = $branch->where('city_id',$request->city_id);
        }

        if ($request->has("with-staff")) {
            $branch = $branch->with(["staff.staff"]);
        }

        if ($request->has("with-timings")) {
            $branch = $branch->with(["timings"]);
        }

        $branch = $branch->orderBy("name")->get();

        return $branch;
    }

    public function getAllBranches(Request $request){

        $branch = Branches::with(["state", "city", "business" => function ($b) use ($request){
            $b->whereDate("subscription_expires_at", ">=", Carbon::now());
            $b->with(['services' => function ($s) use ($request){
                if($request->has("typeId")){
                    $s->where('treatment_type',$request->typeId);
                }
            }]);
            if ($request->has("typeId") && !empty($request->typeId)) {
                $b->whereHas('services', function ($s) use ($request) {
                    $s->where('treatment_type', $request->typeId);
                });
            }
        }])->whereHas("business", function($bus){
            $bus->whereDate("subscription_expires_at", ">=", Carbon::now());
        });
            
        if($request->has('city_id') && !empty($request->city_id)){
            $branch = $branch->where('city_id', $request->city_id);
        }

        if($request->has("business") && !empty($request->business)){
            $branch = $branch->where(function ($query) use ($request) {
                $query->orWhere("business_type_1","like","%".$request->business."%");
                $query->orWhere("business_type_2","like","%".$request->business."%");
                $query->orWhere("business_type_3","like","%".$request->business."%");
            });
        }
        

        $branch = $branch->orderBy("name")->get();
        return $branch;

    }

}