<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\ClosedDates;
use App\Models\ClosedDatesBranches;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class ClosedDatesController extends Controller
{
    public function browse()
    {
        return ClosedDates::with("branches.branch")->whereBusinessId(Helpers::getJWTData("business_id"))->get();
    }

    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "dateRange" => "required",
            "description" => "required|max:100"
        ]);

        $model = ClosedDates::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $model->starts_at = Carbon::parse(Carbon::parse($request->dateRange["startDate"])->toDateString() . " 00:00:00", $request->business->timezone->timezone)->setTimezone(config("app.timezone"))->toDateTimeString();
        $model->ends_at = Carbon::parse(Carbon::parse($request->dateRange["endDate"])->toDateString() . " 23:59:59", $request->business->timezone->timezone)->setTimezone(config("app.timezone"))->toDateTimeString();
        $model->description = $request->description;
        $model->save();
        
        if ($request->get("branch_ids")) {
            ClosedDatesBranches::whereClosedDatesId($model->id)->delete();
            
            foreach ($request->branch_ids as $branch_id) {
                ClosedDatesBranches::create([
                    "closed_dates_id" => $model->id,
                    "branch_id" => $branch_id
                ]);
            }
        }

        return ["success" => true, "message" => __("Closed date saved successfully.")];
    }

    public function delete($id = null)
    {
        $model = ClosedDates::where(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $model = $model->firstOrFail();

        if (!$model) {
            return ["success" => false, "message" => __("An error occurred while deleting closed date.")];
        }

        $model->delete();

        return ["success" => true, "message" => __("Closed date deleted successfully.")];
    }
}