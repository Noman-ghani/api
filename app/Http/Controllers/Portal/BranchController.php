<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\Branches;
use App\Models\BranchTimings;
use App\Models\ShortUrls;
use App\Models\Staff;
use App\Models\StaffBranches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;

class BranchController extends Controller
{   
    public function browse(Request $request)
    {
        $branches = Branches::whereBusinessId(Helpers::getJWTData("business_id"));

        if ($request->has("with-phone-country")) {
            $branches->with("phone_country");
        }

        if ($request->has("with-state")) {
            $branches->with("state");
        }

        if ($request->has("with-city")) {
            $branches->with("city");
        }

        if ($request->has("with-product-tax")) {
            $branches->with("product_tax");
        }
        
        if ($request->has("with-service-tax")) {
            $branches->with("service_tax");
        }

        if ($request->has("with-staff")) {
            $branches->with(["staff" => function ($branchStaff) use ($request) {
                $branchStaff->with(["staff" => function ($staff) use ($request) {
                    if ($request->has("with-staff-services")) {
                        $staff->with("services");
                    }
                }]);
            }]);
        }

        if ($request->has("with-timings")) {
            $branches->with(["timings"]);
        }

        if ($request->has("with-short-url")) {
            $branches->with(["shorturl"]);
        }

        return $branches->orderBy("name")->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "profile_image" => "required",
            "name" => "required",
            "email" => "nullable|email",
            "phone_country_id" => "required",
            "phone_number" => "required",
            "address" => "required",
            "state" => "required",
            "city" => "required",
            "business_types" => "required|array"
        ]);
        
        DB::beginTransaction();

        try {
            $branch = Branches::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
            $branch->name = $request->name;
            
            if (!$branch->slug) {
                $branch->slug = Str::slug($request->name, '-');
                
                // if this slug is already taken, we will append a unique number to make it unique.
                if (Branches::whereSlug($branch->slug)->exists()) {
                    $branch->slug .= '-' . uniqid();
                }
            }
            
            $branch->email = !empty($request->email) ? $request->email : null;
            $branch->phone_country_id = $request->phone_country_id;
            $branch->phone_number = $request->phone_number;
            $branch->address = $request->address;
            $branch->state_id = $request->state;
            $branch->city_id = $request->city;
            $branch->business_type_1 = !empty($request->business_types[0]) ? $request->business_types[0] : null;
            $branch->business_type_2 = !empty($request->business_types[1]) ? $request->business_types[1] : null;
            $branch->business_type_3 = !empty($request->business_types[2]) ? $request->business_types[2] : null;
            $branch->save();

            BranchTimings::whereBranchId($branch->id)->delete();
            
            foreach ($request->timings as $timings) {
                BranchTimings::create([
                    "branch_id" => $branch->id,
                    "day_of_week" => $timings["day_of_week"],
                    "is_closed" => $timings["is_closed"],
                    "starts_at" => $timings["starts_at"],
                    "ends_at" => $timings["ends_at"]
                ]);
            }

            // check if there is any staff associated with this branch. If not, we assign the owner with this branch
            if (!StaffBranches::whereBranchId($branch->id)->exists()) {
                $staffOwner = Staff::whereBusinessId(Helpers::getJWTData("business_id"))->whereRole("owner")->firstOrFail();
                StaffBranches::create([
                    "branch_id" => $branch->id,
                    "staff_id" => $staffOwner->id
                ]);
            }

            Helpers::createDirectoryAndUploadMedia("business/branch-{$branch->id}", $request->profile_image, "profile");
            
            $shortUrl = ShortUrls::whereType("branch")->whereTypeId($branch->id)->first();
    
            if (!$shortUrl) {
                ShortUrls::create([
                    "type" => "branch",
                    "type_id" => $branch->id,
                    "url_code" => Str::random(4)
                ]);
            }
            
            DB::commit();
            return ["success" => true, "message" => __("Branch details saved successfully."), "branch_id" => $branch->id];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function get_by_id(Request $request, $id)
    {
        $branch = Branches::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);
        
        if ($request->has("with-staff")) {
            $branch->with(["staff.staff"]);
        }

        if ($request->has("with-phone-country")) {
            $branch->with(["phone_country"]);
        }

        if ($request->has("with-state")) {
            $branch->with(["state"]);
        }

        if ($request->has("with-city")) {
            $branch->with(["city"]);
        }

        if ($request->has("with-timings")) {
            $branch->with(["timings"]);
        }

        if ($request->has("with-short-url")) {
            $branch->with(["shorturl"]);
        }
        
        return $branch->firstOrFail();
    }

    public function update_tax_defaults(Request $request, $id)
    {
        $branch = $this->get_by_id($request, $id);

        if ($request->has("product_tax")) {
            $branch->product_tax_id = $request->product_tax;
        }

        if ($request->has("service_tax")) {
            $branch->service_tax_id = $request->service_tax;
        }

        if (!$branch->id) {
            $branch->next_invoice_number = 1;
        }

        $branch->save();

        return ["success" => true, "message" => __("Tax default updated.")];
    }

    public function invoice_sequences(Request $request, $id)
    {
        $this->validate($request, [
            "next_invoice_number" => 'numeric|min:0|not_in:0'
        ]);
        
        $branch = $this->get_by_id($request, $id);
        $branch->invoice_prefix = null;
        
        if ($request->get("invoice_prefix")) {
            $branch->invoice_prefix = $request->invoice_prefix;
        }

        if ($request->get("next_invoice_number")) {
            $branch->next_invoice_number = $request->next_invoice_number;
        }

        $branch->save();

        return ["success" => true, "message" => __("Invoice sequence updated.")];
    }
}