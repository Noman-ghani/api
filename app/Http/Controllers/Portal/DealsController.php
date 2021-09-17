<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\Businesses;
use App\Models\Deals;
use App\Models\DealsBranches;
use App\Models\DealsInclusions;
use App\Models\ShortUrls;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;

class DealsController extends Controller
{
    public function browse(Request $request)
    {
        $deals = Deals::whereBusinessId(Helpers::getJWTData("business_id"));

        if ($request->has("with-inclusions")) {
            $deals->with("inclusions", function ($query) {
                $query->with("service");
                $query->with("product");
            });
        }

        if ($request->has("with-short-url")) {
            $deals->with(["shorturl"]);
        }

        if ($request->has("purchasable")) {
            $deals->where("available_from", '<=', Carbon::now());
            $deals->where("available_until", '>=', Carbon::now());
            $deals->whereIsActive(1);
            $deals->where("limit", '>', "utilized");
        }

        if ($request->has("with-tax")) {
            $deals->with("tax");
        }

        return $deals->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "name" => "required",
            "description" => "required",
            "code" => "required",
            "price" => "required",
            "expires_in_days" => "required",
            "available_from" => "required|date",
            "available_until" => "required|date",
            "inclusions" => "required|array",
            "branch_ids" => "required|array",
        ]);

        $business = Businesses::with("timezone")->whereId(Helpers::getJWTData("business_id"))->firstOrFail();
        $deal = Deals::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $deal->name = $request->name;

        if (!$deal->slug) {
            $deal->slug = Str::slug($request->name, '-');

            // if this slug is already taken, we will append a unique number to make it unique.
            if (Deals::whereSlug($deal->slug)->exists()) {
                $deal->slug .= '-' . uniqid();
            }
        }

        $deal->description = $request->description;
        $deal->code = $request->code;
        $deal->price = $request->price;
        $deal->tax_id = $request->tax_id;
        $deal->limit = $request->limit;
        $deal->utilized = 0;
        $deal->expires_in_days = $request->expires_in_days;
        $deal->available_from = Carbon::parse($request->available_from, $business->timezone->timezone)->setTimezone(config("app.timezone"))->toDateTimeString();
        $deal->available_until = Carbon::parse($request->available_until, $business->timezone->timezone)->setTimezone(config("app.timezone"))->toDateTimeString();
        $deal->enable_commission = $request->enable_commission ?? 0;
        $deal->is_active = $request->is_active ?? 0;
        $deal->save();

        DealsBranches::whereDealId($deal->id)->delete();
        
        foreach($request->branch_ids as $branch_id) {
            DealsBranches::create([
                "deal_id" => $deal->id,
                "branch_id" => $branch_id
            ]);
        }

        if (!$id) {
            DealsInclusions::whereDealId($deal->id)->delete();
            foreach ($request->inclusions as $inclusion) {
                DealsInclusions::create([
                    "deal_id" => $deal->id,
                    "service_id" => $inclusion["type"] === "service" ? $inclusion["id"] : null,
                    "product_id" => $inclusion["type"] === "product" ? $inclusion["id"] : null,
                    "quantity" => $inclusion["quantity"],
                    "price" => $inclusion["price"]
                ]);
            }
        }

        $shortUrl = ShortUrls::whereType("deals")->whereTypeId($deal->id)->first();
    
        if (!$shortUrl) {
            ShortUrls::create([
                "type" => "deals",
                "type_id" => $deal->id,
                "url_code" => Str::random(4)
            ]);
        }

        Helpers::createDirectoryAndUploadMedia("business/deal-{$deal->id}", $request->profile_image, "profile");

        return ["success" => true, "message" => "Deal saved successfully"];
    }

    public function get_by_id(Request $request, $id)
    {
        $deal = Deals::with("shorturl")->whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);

        if ($request->has("with-branches")) {
            $deal->with("branches");
        }

        if ($request->has("with-inclusions")) {
            $deal->with("inclusions", function ($query) {
                $query->with("service");
                $query->with("product");
            });
        }

        if ($request->has("with-tax")) {
            $deal->with("tax");
        }

        return $deal->firstOrFail();
    }
}