<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\BranchTimings;
use App\Models\Staff;
use App\Models\StaffBranches;
use App\Models\StaffServices;
use App\Models\StaffShifts;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;

class StaffController extends Controller
{
    public function browse(Request $request)
    {
        $model = Staff::whereBusinessId(Helpers::getJWTData("business_id"));

        if ($request->has("with-phone-country")) {
            $model->with("phone_country");
        }

        if ($request->has("with-user")) {
            $model->with("user");
        }

        if ($request->has("branch_ids")) {
            $model->whereHas("branches", function ($query) use ($request) {
                $query->whereIn("branch_id", explode(',', $request->branch_ids));
            });
        }

        if ($request->has("with-services")) {
            $model->with("services");
        }

        return $model->get();
    }
    
    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "first_name" => "required",
            "last_name" => "required",
            "email" => "required|email",
            "phone_country_id" => "required",
            "phone_number" => "required",
            "staff_title" => "required",
            "appointment_color" => "required",
            "branch_ids" => "required|array",
            "service_ids" => "required|array"
        ]);

        if (Staff::emailExists($request->email, "staff", $id)) {
            return response()->json(["email" => [__("This email is already taken.")]], 422);
        }

        if (Staff::phoneNumberExists($request->phone_number, "staff", $id)) {
            return response()->json(["phone_number" => [__("This phone number is already taken.")]], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::firstOrNew(["email" =>  $request->email]);

            if (!$user->password) {
                $user->password = Hash::make(Str::random(6));
            }

            $user->save();

            $staff = Staff::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "user_id" => $user->id]);
            $doesStaffExists = $staff->exists;
            
            if (empty($staff->role)) {
                $staff->role = "staff";
            }

            $staff->first_name = $request->first_name;
            $staff->last_name = $request->last_name;
            $staff->phone_country_id = $request->phone_country_id;
            $staff->phone_number = $request->phone_number;
            $staff->permission = $request->permission;
            $staff->staff_title = $request->staff_title;
            $staff->emp_start_date = $request->emp_start_date["startDate"] ? Carbon::createFromDate($request->emp_start_date["startDate"])->toDateString() : null;
            $staff->emp_end_date = $request->emp_end_date["startDate"] ? Carbon::createFromDate($request->emp_end_date["startDate"])->toDateString() : null;
            $staff->notes = $request->notes;
            $staff->enable_appointments = ($request->enable_appointments) ? 1 : 0;
            $staff->appointment_color = $request->appointment_color;
            $staff->service_commission = $request->service_commission;
            $staff->product_commission = $request->product_commission;
            $staff->deal_commission = $request->deal_commission;
            $staff->save();

            StaffBranches::whereStaffId($staff->id)->delete();
            StaffServices::whereStaffId($staff->id)->delete();

            foreach ($request->branch_ids as $branch_id) {
                StaffBranches::create([
                    "staff_id" => $staff->id,
                    "branch_id" => $branch_id
                ]);
            }

            foreach ($request->service_ids as $service_id) {
                StaffServices::create([
                    "staff_id" => $staff->id,
                    "service_id" => $service_id
                ]);
            }

            // if the staff is created for the first time, we will enter the shift timings for this staff based on the branch timings
            if (!$doesStaffExists) {
                foreach ($request->branch_ids as $branch_id) {
                    $branchTimings = BranchTimings::whereBranchId($branch_id)->whereIsClosed(0)->get();
                    foreach ($branchTimings as $branchTiming) {
                        StaffShifts::create([
                            "branch_id" => $branch_id,
                            "staff_id" => $staff->id,
                            "date_start" => Carbon::createFromDate($request->emp_start_date["startDate"])->toDateString(),
                            "day_of_week" => $branchTiming->day_of_week,
                            "repeats" => "weekly",
                            "end_repeat" => "ongoing",
                            "starts_at" => $branchTiming->starts_at,
                            "ends_at" => $branchTiming->ends_at
                        ]);
                    }
                }
            }

            if ($request->profile_image) {
                Helpers::createDirectoryAndUploadMedia("business/staff", $request->profile_image, $staff->id);
            } else {
                Helpers::deleteFile("business/staff", $staff->id);
            }

            DB::commit();
            return ["success" => true, "message" => "Staff saved successfully.", "staff" => $staff->toArray()];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function get_by_id($id)
    {
        return Staff::with(["user", "phone_country", "branches", "services"])
            ->whereId($id)
            ->whereBusinessId(Helpers::getJWTData("business_id"))
            ->firstOrFail();
    }
}