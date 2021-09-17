<?php

namespace App\Http\Controllers\MarketPlace;

use App\Helpers\Helpers;
use App\Models\Businesses;
use App\Models\Countries;
use App\Models\Branches;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller;

class BusinessController extends Controller
{
    public function __construct(Businesses $business, Branches $branches)
    {
        $this->business = $business;
        $this->branches = $branches;
    }

    public function browse(Request $request){

        $business = $this->business;
        if($request->has('country_id') && !empty($request->country_id)){
            $business = $business->where('country_id',$request->country_id);
        }
        $business = $business->get();
        $result = [];

        foreach ($business as $key => $value) {
            $result[$key] = [
                'id' => $value->id,
                'name' => $value->name,
                'slug' => $value->slug,
                'country_id' => $value->country_id,
                'time_zone_id' => $value->time_zone_id,
                'time_format' => $value->time_format,
                'week_start' => $value->week_start,
                'profile_image' => $value->profile_image,
                'is_profile_complete' => $value->is_profile_complete
            ];
        }

        return $result;
    }
}