<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Models\Staff;
use App\Models\StaffShifts;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller;

class StaffShiftsController extends Controller
{
    public function browse(Request $request)
    {
        $this->validate($request, [
            "dateRange" => "required",
            "branch_id" => "required"
        ]);

        $dateRange = json_decode($request->dateRange);
        $requestedDate = CarbonImmutable::createFromDate($dateRange->startDate);
        $startOfWeek = $request->business->week_start;
        $endOfWeek = ($startOfWeek + 6) - 7;
        $startDate = $requestedDate->startOfWeek($startOfWeek);
        $endDate = $requestedDate->endOfWeek($endOfWeek);

        $staffs = Staff::whereBusinessId(Helpers::getJWTData("business_id"))->whereHas("branches", function ($query) use ($request) {
            $query->whereBranchId($request->branch_id);
        })->get();
        $branchShifts = StaffShifts::whereBranchId($request->branch_id)->get();
        $data = [];

        foreach ($staffs as $index => $staff) {
            $data[$index] = [
                "staff" => [
                    "id" => $staff->id,
                    "name" => $staff->full_name
                ],
                "shifts" => []
            ];

            foreach (CarbonPeriod::create($startDate, $endDate) as $carbonPeriod) {
                $timeslots = [];
                
                $staffShiftOnWeek = Arr::where($branchShifts->toArray(), function ($value, $key) use ($staff, $carbonPeriod) {
                    return $value["staff_id"] === $staff->id && intval($value["day_of_week"]) === $carbonPeriod->dayOfWeek;
                });

                foreach ($staffShiftOnWeek as $staffShift) {
                    // check if the shift date lies between our start and end week dates.
                    $carbonDateStart = Carbon::createFromDate($staffShift["date_start"]);
                    
                    if ($carbonPeriod->lt($carbonDateStart)) {
                        continue;
                    }

                    if ($staffShift["date_end"]) {
                        $carbonDateEnd = Carbon::createFromDate($staffShift["date_end"]);

                        if ($carbonPeriod->gt($carbonDateEnd)) {
                            continue;
                        }
                    }

                    // if staff shift is dont_repeat, will override any other shift possible in this day
                    if ($staffShift["repeats"] === "dont_repeat" && $carbonDateStart->gte($carbonPeriod) && $carbonDateEnd->lte($carbonPeriod)) {
                        $timeslots = [];
                    }

                    $timeslots[] = $staffShift;
                }

                $data[$index]["shifts"][] = [
                    "date" => $carbonPeriod->toDateString(),
                    "timeslots" => $timeslots
                ];
            }
        }

        return $data;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "branch_id" => "required",
            "staff_id" => "required",
            "date" => "required"
        ]);

        DB::beginTransaction();

        try {
            $requestedDate = CarbonImmutable::createFromDate($request->date);

            $staffShifts = StaffShifts::whereBranchId($request->branch_id)
            ->whereStaffId($request->staff_id)
            ->whereDayOfWeek($requestedDate->dayOfWeek)
            ->get();
            $ongoingStaffShifts = collect($staffShifts)->where("repeats", "weekly")->where("end_repeat", "ongoing")->where("date_end", null);
            $specificDateStaffShifts = collect($staffShifts)->where("repeats", "weekly")->where("end_repeat", "specific_date")->where("date_end", '!=', null);
            $dontRepeatStaffShifts = collect($staffShifts)->where("repeats", "dont_repeat")->where("end_repeat", null);

            $insertNewShift = true;
            $newStaffShift = new StaffShifts();
            $newStaffShift->branch_id = $request->branch_id;
            $newStaffShift->staff_id = $request->staff_id;
            $newStaffShift->day_of_week = $requestedDate->dayOfWeek;

            if ($request->repeats === "weekly") {
                if ($request->end_repeat === "ongoing") {
                    foreach ($ongoingStaffShifts as $ongoingStaffShift) {
                        if ($ongoingStaffShift->date_start > $requestedDate->toDateString()) {
                            // if ongoing shift is more than the requested date, simply update the date_start column
                            $ongoingStaffShift->date_start = $requestedDate->toDateString();
                            $ongoingStaffShift->starts_at = $request->first_shift["starts_at"];
                            $ongoingStaffShift->ends_at = $request->first_shift["ends_at"];
                            $ongoingStaffShift->save();
                            $insertNewShift = false;
                        } else if ($ongoingStaffShift->date_start == $requestedDate->toDateString()) {
                            // if ongoing shift is being updated
                            $ongoingStaffShift->starts_at = $request->first_shift["starts_at"];
                            $ongoingStaffShift->ends_at = $request->first_shift["ends_at"];
                            $ongoingStaffShift->save();
                            $insertNewShift = false;
                        } else if ($ongoingStaffShift->date_start < $requestedDate->toDateString()) {
                            // if ongoing shift is less than the requested date, close that shift
                            $ongoingStaffShift->date_end = $requestedDate->subDays(7)->toDateString();
                            $ongoingStaffShift->end_repeat = "specific_date";
                            if ($ongoingStaffShift->date_start == $ongoingStaffShift->date_end) {
                                $ongoingStaffShift->repeats = "dont_repeat";
                                $ongoingStaffShift->end_repeat = null;
                            }
                            $ongoingStaffShift->save();
                        }
                    }

                    foreach ($specificDateStaffShifts as $specificDateStaffShift) {
                        if ($requestedDate->toDateString() >= $specificDateStaffShift->date_start && $requestedDate->toDateString() <= $specificDateStaffShift->date_end) {
                            if ($specificDateStaffShift->date_start == $requestedDate->toDateString()) {
                                $specificDateStaffShift->delete();
                            } else {
                                $specificDateStaffShift->date_end = $requestedDate->subDays(7)->toDateString();
                                if ($specificDateStaffShift->date_start == $specificDateStaffShift->date_end) {
                                    $specificDateStaffShift->repeats = "dont_repeat";
                                    $specificDateStaffShift->end_repeat = null;
                                }
                                $specificDateStaffShift->save();
                            }
                        }
                    }

                    // delete any dont repeat shift that is greater than the requested date as the new ongoing shift will replace that
                    foreach ($dontRepeatStaffShifts as $dontRepeatStaffShift) {
                        if ($dontRepeatStaffShift->date_start >= $requestedDate->toDateString()) {
                            $dontRepeatStaffShift->delete();
                        }
                    }
                    
                    $newStaffShift->date_start = $requestedDate->toDateString();
                    $newStaffShift->repeats = $request->repeats;
                    $newStaffShift->end_repeat = $request->end_repeat;
                    $newStaffShift->starts_at = $request->first_shift["starts_at"];
                    $newStaffShift->ends_at = $request->first_shift["ends_at"];
                } else {
                    foreach ($ongoingStaffShifts as $ongoingStaffShift) {
                        $newOnGoingStaffShift = $ongoingStaffShift->replicate();
                        if ($ongoingStaffShift->date_start < $requestedDate->toDateString()) {
                            $ongoingStaffShift->date_end = $requestedDate->subDays(7)->toDateString();
                            $ongoingStaffShift->end_repeat = "specific_date";
                            if ($ongoingStaffShift->date_start == $ongoingStaffShift->date_end) {
                                $ongoingStaffShift->repeats = "dont_repeat";
                                $ongoingStaffShift->end_repeat = null;
                            }
                            $ongoingStaffShift->save();
                            
                            // continue the ongoing shift
                            $newOnGoingStaffShift->date_start = Carbon::parse($request->specific_date["startDate"])->addDays(7)->toDateString();
                            $newOnGoingStaffShift->date_end = null;
                            $newOnGoingStaffShift->repeats = "weekly";
                            $newOnGoingStaffShift->end_repeat = "ongoing";
                            $newOnGoingStaffShift->save();
                        } else if ($ongoingStaffShift->date_start == $requestedDate->toDateString()) {
                            $newOnGoingStaffShift->date_start = Carbon::parse($request->specific_date["startDate"])->addDays(7)->toDateString();
                            $newOnGoingStaffShift->save();
                            $ongoingStaffShift->delete();
                        } else if ($ongoingStaffShift->date_start > Carbon::parse($request->specific_date["startDate"])->toDateString()) {
                            $ongoingStaffShift->delete();
                        } else if ($ongoingStaffShift->date_start == Carbon::parse($request->specific_date["startDate"])->toDateString()) {
                            // continue the ongoing shift
                            $newOnGoingStaffShift->date_start = Carbon::parse($request->specific_date["startDate"])->addDays(7)->toDateString();
                            $newOnGoingStaffShift->date_end = null;
                            $newOnGoingStaffShift->repeats = "weekly";
                            $newOnGoingStaffShift->end_repeat = "ongoing";
                            $newOnGoingStaffShift->save();
                        }
                    }

                    foreach ($specificDateStaffShifts as $specificDateStaffShift) {
                        // update same specific date shift
                        if ($specificDateStaffShift->date_start == $requestedDate->toDateString() && $specificDateStaffShift->date_end == Carbon::parse($request->specific_date["startDate"])->toDateString()) {
                            $specificDateStaffShift->starts_at = $request->first_shift["starts_at"];
                            $specificDateStaffShift->ends_at = $request->first_shift["ends_at"];
                            $specificDateStaffShift->save();
                            $insertNewShift = false;
                        }
                    }

                    foreach ($dontRepeatStaffShifts as $dontRepeatStaffShift) {
                        // delete any dont repeat staff shift that occurs between specific dates
                        if ($dontRepeatStaffShift->date_start >= $requestedDate->toDateString() && $dontRepeatStaffShift->end_date <= Carbon::parse($request->specific_date["startDate"])->toDateString()) {
                            $dontRepeatStaffShift->delete();
                        }
                    }

                    $newStaffShift->date_start = $requestedDate->toDateString();
                    $newStaffShift->date_end = Carbon::parse($request->specific_date["startDate"])->toDateString();
                    $newStaffShift->repeats = $request->repeats;
                    $newStaffShift->end_repeat = $request->end_repeat;
                    $newStaffShift->starts_at = $request->first_shift["starts_at"];
                    $newStaffShift->ends_at = $request->first_shift["ends_at"];
                }
            } else {
                foreach ($ongoingStaffShifts as $ongoingStaffShift) {
                    if ($ongoingStaffShift->date_start > $requestedDate->toDateString()) {
                        continue;
                    } else if ($ongoingStaffShift->date_start == $requestedDate->toDateString()) {
                        $newOnGoingStaffShift = $ongoingStaffShift->replicate();
                        
                        $ongoingStaffShift->date_end = $ongoingStaffShift->date_start;
                        $ongoingStaffShift->repeats = "dont_repeat";
                        $ongoingStaffShift->end_repeat = null;
                        $ongoingStaffShift->starts_at = $request->first_shift["starts_at"];
                        $ongoingStaffShift->ends_at = $request->first_shift["ends_at"];
                        $ongoingStaffShift->save();
                        
                        // continue the ongoing shift
                        $newOnGoingStaffShift->date_start = $requestedDate->addDays(7)->toDateString();
                        $newOnGoingStaffShift->date_end = null;
                        $newOnGoingStaffShift->repeats = "weekly";
                        $newOnGoingStaffShift->end_repeat = "ongoing";
                        $newOnGoingStaffShift->save();

                        $insertNewShift = false;
                    } else if ($ongoingStaffShift->date_start < $requestedDate->toDateString()) {
                        $ongoingStaffShift->date_end = $requestedDate->subDays(7)->toDateString();
                        if ($ongoingStaffShift->date_start == $ongoingStaffShift->date_end) {
                            $ongoingStaffShift->repeats = "dont_repeat";
                            $ongoingStaffShift->end_repeat = null;
                        }
                        $ongoingStaffShift->save();

                        // continue the ongoing shift
                        $newOnGoingStaffShift = $ongoingStaffShift->replicate();
                        $newOnGoingStaffShift->date_start = $requestedDate->addDays(7)->toDateString();
                        $newOnGoingStaffShift->date_end = null;
                        $newOnGoingStaffShift->repeats = "weekly";
                        $newOnGoingStaffShift->end_repeat = "ongoing";
                        $newOnGoingStaffShift->save();
                    }
                }
                
                foreach ($specificDateStaffShifts as $specificDateStaffShift) {
                    if ($requestedDate->toDateString() >= $specificDateStaffShift->date_start && $requestedDate->toDateString() <= $specificDateStaffShift->date_end) {
                        $newSpecificDateShift = $specificDateStaffShift->replicate();
                        if ($requestedDate->toDateString() == $specificDateStaffShift->date_start) {
                            // if specific date start is same as requested date, close that to dont repeat and do not insert a new row
                            
                            $specificDateStaffShift->date_end = $requestedDate->toDateString();
                            $specificDateStaffShift->repeats = "dont_repeat";
                            $specificDateStaffShift->end_repeat = null;
                            $specificDateStaffShift->starts_at = $request->first_shift["starts_at"];
                            $specificDateStaffShift->ends_at = $request->first_shift["ends_at"];
                            $specificDateStaffShift->save();

                            $newSpecificDateShift->date_start = $requestedDate->addDays(7)->toDateString();
                            if ($newSpecificDateShift->date_start == $newSpecificDateShift->date_end) {
                                $newSpecificDateShift->repeats = "dont_repeat";
                                $newSpecificDateShift->end_repeat = null;
                            }
                            $newSpecificDateShift->save();
                            $insertNewShift = false;
                        } else {
                            $specificDateStaffShift->date_end = $requestedDate->subDays(7)->toDateString();
                            if ($specificDateStaffShift->date_start == $specificDateStaffShift->date_end) {
                                $specificDateStaffShift->repeats = "dont_repeat";
                                $specificDateStaffShift->end_repeat = null;
                            }
                            $specificDateStaffShift->save();

                            if ($specificDateStaffShift->repeats === "weekly") {
                                $newSpecificDateShift->date_start = $requestedDate->addDays(7)->toDateString();
                                $newSpecificDateShift->save();
                            }
                        }
                    }
                }

                $sameDontRepeatStaffShifts = collect($dontRepeatStaffShifts)->where("date_start", $requestedDate->toDateString())->where("date_end", $requestedDate->toDateString());
                foreach ($sameDontRepeatStaffShifts as $sameDontRepeatStaffShift) {
                    $sameDontRepeatStaffShift->starts_at = $request->first_shift["starts_at"];
                    $sameDontRepeatStaffShift->ends_at = $request->first_shift["ends_at"];
                    $sameDontRepeatStaffShift->save();
                    $insertNewShift = false;
                }

                $newStaffShift->date_start = $requestedDate->toDateString();
                $newStaffShift->date_end = $requestedDate->toDateString();
                $newStaffShift->repeats = $request->repeats;
                $newStaffShift->starts_at = $request->first_shift["starts_at"];
                $newStaffShift->ends_at = $request->first_shift["ends_at"];
            }

            if ($insertNewShift) {
                $newStaffShift->save();
            }
            
            DB::commit();
            return ["success" => true, "message" => __("Staff shift saved scucessfully.")];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function delete(Request $request)
    {
        return ["success" => true, "message" => __("Shift deleted successfully.")];
    }
}