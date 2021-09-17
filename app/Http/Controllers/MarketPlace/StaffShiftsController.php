<?php

namespace App\Http\Controllers\MarketPlace;

use App\Helpers\Helpers;
use App\Models\AppointmentItems;
use App\Models\Branches;
use App\Models\StaffShifts;
use App\Models\Businesses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Lumen\Routing\Controller;

class StaffShiftsController extends Controller
{   
    public function __construct(StaffShifts $staffShifts)
    {
        $this->staff_shifts = $staffShifts;
    }

    public function browse(Request $request)
    {
        $this->validate($request, [
            "date" => "required",
            "day_of_week" => "required",
            "staff_id" => "required",
            "business_id" => "required",
            "branch_id" => "required",
        ]);
        $business = Businesses::with('timezone')->where('id',$request->business_id)->first();

        $branch = Branches::with('timings')->where('id',$request->branch_id)->first();
        
        foreach($branch->timings as $time){
            if($time->is_closed && $time->day_of_week == $request->day_of_week){
                return [
                    'data' => [],
                    'close' => true,
                    'empty' => false,
                    'message' => 'Branch has Closed'
                ];
            }
        }
        
        $results = $this->staff_shifts
        ->where('branch_id',$request->branch_id)
        ->where('staff_id',$request->staff_id)
        ->where("day_of_week" , $request->day_of_week)
        ->orderBy("date_start", "asc")
        ->get();

        $appointments = AppointmentItems::with(["appointment", "staff"])
        ->whereHas("appointment", function ($query) use ($request) {   
            if ($request->has("business_id")) {
                $query->whereBusinessId($request->business_id);
            }
            if ($request->has("branch_id")) {
                $query->whereBranchId($request->branch_id);
            }

            if ($request->has("date")) {
                $query->whereBookingDate($request->date);
            }
        })->orderBy("start_time", "asc")->get();

        $appointmentTimes = [];
        foreach($appointments as $key => $app) {

            $startTime = Carbon::parse($app->start_time);
            $endTime = Carbon::parse($app->end_time);
            
            $appointmentTimes[] = $startTime->format('H:i:s');
            
            while($startTime->diffInMinutes($endTime) > 0){
                $appointmentTimes[] = $startTime->format('H:i:s');
                $startTime->addMinutes(15);
            }
        }

        $timmings = [];
        
        foreach($results as $result){
            //Get Current Time
            $currentTime = Carbon::now()->setTimezone($business->timezone->timezone)->toDateTimeString();
            //PArse Current Time into Carbon
            $currentTime = Carbon::parse($currentTime);
            
            if($request->date >= $result->date_start ){
                $timmings = [];
                //Get Staff shift Start Time
                $startTime = Carbon::parse($request->date.' '.$result->starts_at);
                //Get Staff shift End Time
                $endTime = Carbon::parse($request->date.' '.$result->ends_at);
            }

            if($request->date >= $result->date_start && $request->date <= $result->date_end){
                
                $timmings = [];
                //Get Staff shift Start Time
                $startTime = Carbon::parse($request->date.' '.$result->starts_at);
                //Get Staff shift End Time
                $endTime = Carbon::parse($request->date.' '.$result->ends_at);
            }
            
            // if current tiem is greter tyhan start time, start time = current time
            if ($currentTime->gt($startTime)) {
                $startTime = $currentTime;
                $startTime = Carbon::parse(ceil($startTime->timestamp / (15 * 60)) * (15 * 60));
            }
        
            while($endTime->gt($startTime) || $endTime->equalTo($startTime)){
                $timmings[] = $startTime->format('H:i:s');
                $startTime->addMinutes(15);
            }
        }
        
        $timmings = array_diff($timmings, $appointmentTimes);
    
        $timmings = collect($timmings)->map(function ($value) use ($request,$business) {
            return [
                "text" => Carbon::parse($value)->format(Helpers::getTimeFormat($business)),
                "value" => $request->date . " " . $value
            ];
        })->values();
        
        if(empty($timmings->toArray())){
            return [
                'data' => $timmings,
                'close' => false,
                'empty' => true,
                'message' => 'No slot found or '.$request->date
            ];
        }
        
        return [
            'data' => $timmings,
            'close' => false,
            'empty' => false,
            'message' => 'success'
        ];
    }
}