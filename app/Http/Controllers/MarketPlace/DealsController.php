<?php

namespace App\Http\Controllers\MarketPlace;

use App\Models\Deals;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Lumen\Routing\Controller;

class DealsController extends Controller
{
    public function __construct(Deals $deals)
    {
        $this->deals = $deals;
    }

    public function browse(Request $request){

       $result = $this->deals->with(['business' => function($b){
           $b->with('country');
           $b->whereDate("subscription_expires_at", ">=", Carbon::now());
       },'inclusions' => function($query) use ($request){
           $query->with(['service' => function ($s) use ($request){
            if($request->has("category") && !empty($request->category)){
                $s->where('treatment_type',$request->category);
            }
        },'product']);
        if ($request->has("category") && !empty($request->category)) {
            $query->whereHas('service', function ($s) use ($request) {
                $s->where('treatment_type', $request->category);
            });
        }

       },'branches' => function($branches) use ($request){
           $branches->with(['branch' => function($b) use ($request){
            if($request->has('city_id') && !empty($request->city_id)){
                $b->where('city_id',$request->city_id);
            }
           }])->whereHas('branch', function($b) use ($request){
            if($request->has('city_id') && !empty($request->city_id)){
                $b->where('city_id',$request->city_id);
            }
           });
       }])->has('branches')->whereHas('business', function($b){
            $b->with('country');
            $b->whereDate("subscription_expires_at", ">=", Carbon::now());
        })
        ->whereDate('available_from','<=',Carbon::now()->toDateTimeString())
        ->whereDate('available_until','>=',Carbon::now()->toDateTimeString())
        ->whereIsActive(1);

        if($request->has('business_id')){
            $result = $result->where('business_id',$request->business_id);
        }

        if($request->has("filterPrice") && count($request->filterPrice) > 0){
            $first = explode("-",$request->filterPrice[0])[0];
            $last = explode("-",$request->filterPrice[count($request->filterPrice) - 1])[1];

            $result = $result->where('price','>=' ,$first)
                    ->where('price','<=' ,$last); 
        }
        
        
        if($request->has("selectedSort") && !empty($request->selectedSort)){
            
            $sort = 'desc';
            $sortBy = "id";

            switch($request->selectedSort){
                case 'lth' :
                    $sortBy = "price";
                    $sort = "asc";
                break;
                case 'htl' :
                    $sortBy = "price";
                    $sort = "desc";
                break;
                case 'recent' :
                    $sortBy = "created_at";
                    $sort = "desc";
                break;
                case 'closesoon' :
                    $sortBy = "available_until";
                    $sort = "desc";
                break;
            }
            $result = $result->orderBy($sortBy, $sort); 
        }

        $result = $result->get()->filter(function($query){
            if(count($query->branches) == 0){
                return null;
            }
            if(count($query->inclusions) > 0){
                return $query;
            }
        })->values();

        return $result;
    }

    public function getFilterPriceList(Request $request){
        
        $min = $this->deals->whereDate('available_from','<=',Carbon::now()->toDateTimeString())->whereDate('available_until','>=',Carbon::now()->toDateTimeString())->whereIsActive(1)->min('price');
        $max = $this->deals->whereDate('available_from','<=',Carbon::now()->toDateTimeString())->whereDate('available_until','>=',Carbon::now()->toDateTimeString())->whereIsActive(1)->max('price');
        
        return [
            [
                "min" => floor($min),
                "max" => floor($max * 0.25)
            ],
            [
                "min" => floor($max * 0.25),
                "max" => floor($max * 0.5)
            ],
            [
                "min" => floor($max * 0.5),
                "max" => floor($max * 0.75)
            ],
            [
                "min" => floor($max * 0.75),
                "max" => floor($max)
            ],
        ];
    }

    public function get_by_id(Request $request, $slug)
    {
        $result = $this->deals->with(['business' => function($b){
            $b->with('country');
            $b->whereDate("subscription_expires_at", ">=", Carbon::now());
        },'branches.branch','inclusions' => function($query) use ($request){
            $query->with(['service' => function($query){
                $query->with(["branches" => function($branches) {
                    $branches->with(['tax' => function($tax) {
                        $tax->with("tax_1")->with("tax_2")->with("tax_3");
                    }]);
                }]);
            },'product' => function($query){
                $query->with(["branches" => function($branches) {
                    $branches->with(['tax' => function($tax) {
                        $tax->with("tax_1")->with("tax_2")->with("tax_3");
                    }]);
                }]);
            }]);
        }])
        ->whereHas('business', function($b){
            $b->with('country');
            $b->whereDate("subscription_expires_at", ">=", Carbon::now());
        })
        ->where('slug',$slug)
        ->firstOrFail();
        
        $purchase = false;
        $message = "This Deal has expired.";

        if(!empty($result) && $result->available_from <= Carbon::now()->toDateTimeString() && $result->available_until >= Carbon::now()->toDateTimeString() && $result->is_active == 1){
            $purchase = true;
            $message = "This Deal is valid and ready for purchase.";
        }

        return [
            "purchase" => $purchase,
            "deal" => $result,
            "message" => $message
        ];

    }
}