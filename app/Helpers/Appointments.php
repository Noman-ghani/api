<?php

namespace App\Helpers;

use App\Models\AppointmentHistory;
use App\Models\AppointmentItems;
use App\Models\Appointments as AppointmentsModel;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;

class Appointments
{
    public function scheduleAppointment(Request $request, $appointment_id = null)
    {
        DB::beginTransaction();
        
        try {
            $appointment = AppointmentsModel::firstOrNew(["business_id" => $request->business_id, "branch_id" => $request->branch_id, "id" => $appointment_id]);
            $appointment->client_id = $request->client_id;
            $appointment->booking_date = $request->bookingDate;
            $appointment->status = "new";
            $appointment->notes = $request->notes;
            $appointment->save();

            AppointmentItems::whereAppointmentId($appointment->id)->delete();
            
            foreach ($request->items as $item) {
                AppointmentItems::create([
                    "appointment_id" => $appointment->id,
                    "type" => $request->type,
                    "service_id" => $item["service_id"],
                    "start_time" => $item["start_time"],
                    "end_time" => date("H:i:s", strtotime($item["duration"] . "minutes", strtotime($item["start_time"]))),
                    "duration" => $item["duration"],
                    "staff_id" => $item["staff_id"],
                    "price" => $item["price"]
                ]);
            }

            $staff_id = null;

            if (Auth::user()) {
                $staff = Staff::whereBusinessId($request->business_id)
                ->whereUserId(Auth::user()->id)
                ->whereRole("owner")
                ->firstOrFail();

                $staff_id = $staff->id;
            }
            
            if ($appointment_id) {
                if ($request->has("action")) {
                    AppointmentHistory::create([
                        "appointment_id" => $appointment->id,
                        "staff_id" => $staff_id,
                        "status" => $request->action
                    ]);
                }
            } else {
                AppointmentHistory::create([
                    "appointment_id" => $appointment->id,
                    "staff_id" => $staff_id,
                    "status" => "new"
                ]);
            }

            DB::commit();
            return ["success" => true, "message" => "Appointment scheduled successfully", "appointment_id" => $appointment->id];
        } catch (\Exception $e) {
            DB::rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}