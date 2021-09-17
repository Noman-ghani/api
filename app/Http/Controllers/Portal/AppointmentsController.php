<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Helpers\Appointments as AppointmentHelper;
use App\Jobs\AppointmentReminderJob;
use App\Models\AppointmentHistory;
use App\Models\AppointmentItems;
use App\Models\Appointments;
use App\Models\SmsTemplates;
use App\Models\Staff;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AppointmentsController extends Controller
{
    public function getAppointments(Request $request)
    {
        if ($request->has("for_calendar")) {
            $appointments = AppointmentItems::with(["appointment" => function ($query) {
                $query->where("status", '<>', "cancelled");
                $query->with(["client.phone_country"]);
                $query->with("branch");
            }])
            ->with(["service.category", "staff"])
            ->whereHas("appointment", function ($query) use ($request) {
                $query->whereBusinessId(Helpers::getJWTData("business_id"));
                $query->whereBranchId($request->branch_id);
                $query->where("status", '<>', "cancelled");

                if ($request->has("date")) {
                    $query->whereBookingDate($request->date);
                }
            })
            ->orderBy("id", "desc");

            return $appointments->get();
        } else {
            $appointmentItems = AppointmentItems::whereHas("appointment", function ($query) use ($request) {
                $query->whereBusinessId(Helpers::getJWTData("business_id"));
                if ($request->has("branch_id")) {
                    $query->whereBranchId($request->branch_id);
                }
                if ($request->has("date")) {
                    $query->whereBookingDate($request->date);
                }
            });
            $appointmentItems->with("service");
            $appointmentItems->with("staff");
            $appointmentItems->with(["appointment" => function ($query) {
                $query->with("client");
                $query->with("branch");
            }]);
    
            if ($request->has("dateRange")) {
                $dateRange = json_decode($request->dateRange);
                $startDate = Carbon::createFromDate($dateRange->startDate)->toDateString() . " 00:00:00";
                $endDate = Carbon::createFromDate($dateRange->endDate)->toDateString() . " 23:59:59";
                $offset = $request->business->timezone->offset;
                $appointmentItems->whereRaw("created_at >= DATE_ADD('{$startDate} 00:00:00', INTERVAL '{$offset}' HOUR)");
                $appointmentItems->whereRaw("created_at <= DATE_ADD('{$endDate} 23:59:59', INTERVAL '{$offset}' HOUR)");
            }
    
            return $appointmentItems->orderBy("id", "desc")->get();
        }
    }
    
    public function scheduleAppointment(Request $request, $branch_id, $id = null)
    {
        $this->validate($request, [
            "booking_date" => "required|date",
            "items" => "required|array"
        ]);

        $newAppointment = new AppointmentHelper();
        $request->request->set("business_id", Helpers::getJWTData("business_id"));
        $request->request->set("branch_id", $branch_id);
        $request->request->set("client_id", $request->has("client_id") ? $request->client_id : null);
        $request->request->set("bookingDate", $request->booking_date);
        $request->request->set("notes", $request->notes);
        $request->request->set("items", $request->items);
        
        return $newAppointment->scheduleAppointment($request, $id);
    }

    public function get_by_id(Request $request, $id)
    {
        $appointment = Appointments::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);

        if ($request->has("with-client")) {
            $appointment->with(["client" => function ($client) {
                $client->with("phone_country");
                $client->with(["deals" => function ($deals) {
                    $deals->with("items");
                    $deals->with("deal");
                }]);
            }]);
        }
        if ($request->has("with-items")) {
            $appointment->with(["items" => function ($query) {
                $query->with("staff");
                $query->with("service");
            }]);
        }
        if ($request->has("with-history")) {
            $appointment->with("history", function ($query) {
                $query->with("staff");
                $query->orderBy("id", "desc");
            });
        }
        if ($request->has("with-invoice")) {
            $appointment->with("invoice");
        }

        $data = $appointment->firstOrFail();

        return $data;
    }

    public function changeStatus(Request $request, $id)
    {
        $this->validate($request, [
            "status" => "required"
        ]);
        
        DB::beginTransaction();

        try {
            $appointment = Appointments::with("items")->whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
            $appointment->status = $request->status;

            if ($request->status === "cancelled" && $request->has("cancel_reason_id")) {
                $appointment->cancel_reason_id = $request->cancel_reason_id;
            }

            $appointment->save();

            $staff = Staff::whereBusinessId(Helpers::getJWTData("business_id"))
            ->whereRole("owner")
            ->whereUserId(Auth::user()->id)
            ->firstOrFail();

            AppointmentHistory::create([
                "appointment_id" => $id,
                "staff_id" => $staff->id,
                "status" => $request->status
            ]);

            $message = __("Appointment status updated successfully.");

            if ($request->status === "cancelled") {
                $message = __("Appointment cancelled successfully.");
            }

            // if appointment is set to confirmed, we will set reminders for this appointment
            if ($request->status === "confirmed") {
                $currentBusinessTime = Carbon::parse(Carbon::now()->setTimezone($request->business->timezone->timezone)->toDateTimeString());
                $appointmentTime = CarbonImmutable::parse($appointment->booking_date . " " . $appointment->items[0]->start_time);

                if ($appointmentTime->gt($currentBusinessTime)) {
                    $diffInMinutes = $appointmentTime->diffInMinutes($currentBusinessTime);
                    $reminders = SmsTemplates::whereBusinessId(Helpers::getJWTData("business_id"))
                    ->whereIsActive(1)
                    ->whereIn("event", ["reminder_1", "reminder_2", "reminder_3"])
                    ->where("minutes", '<=', $diffInMinutes)
                    ->get();

                    foreach ($reminders as $reminder) {
                        $reminderDateTime = Carbon::createFromFormat("Y-m-d H:i:s", $appointmentTime->subMinutes($reminder->minutes), $request->business->timezone->timezone)->setTimezone("UTC");
                        Queue::laterOn("default", $reminderDateTime, new AppointmentReminderJob($appointment->id));
                    }
                }
            }

            DB::commit();
            return ["success" => true, "message" => $message];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}