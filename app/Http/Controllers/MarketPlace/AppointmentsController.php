<?php

namespace App\Http\Controllers\MarketPlace;


use App\Helpers\Appointments as HelpersAppointments;
use App\Models\AppointmentItems;
use App\Models\Appointments;
use App\Models\BranchServices;
use App\Models\Clients;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class AppointmentsController extends Controller
{
    public function getMyALLAppointments(Request $request)
    {

        $appointments = Appointments::with(["items" => function ($query){
            $query->with(["service.branches"]);
        },"branch","business.country","client" => function ($client) use ($request){
            $client->where('customer_id',$request->customer_id);
        }])->whereHas('client', function ($query) use ($request){
            $query->where('customer_id',$request->customer_id);
        })
        ->orderBy("booking_date", "desc");
        
        return $appointments->get()->filter(function($query) {
            $branch_id = $query->branch_id;
            
            foreach($query->items as $item){
                $branchService = BranchServices::with(["tax" => function ($branch) {
                    $branch->with("tax_1")->with("tax_2")->with("tax_3");
                }])
                ->whereBranchId($branch_id)
                ->whereServiceId($item->service_id)->first();
                if($branchService){
                    $item->tax = $branchService->tax;
                }
            };
            
            return $query;
        })->values();
    }

    public function getAppointments(Request $request)
    {
        $appointments = AppointmentItems::with(["appointment" => function ($query) {
            $query->with(["client.phone_country"]);
            $query->with("branch");
            
        }])->with(["service.category", "staff"])->whereHas("appointment", function ($query) use ($request) {
            
            if ($request->has("business_id")) {
                $query->whereBusinessId($request->business_id);
            }

            if ($request->has("branch_id")) {
                $query->whereBranchId($request->branch_id);
            }

            if ($request->has("date")) {
                $query->whereBookingDate($request->date);
            }
        })->orderBy("id", "desc");

        return $appointments->get();
    }

    public function scheduleAppointment(Request $request)
    {
        $this->validate($request, [
            "branch_id" => "required",
            "bookingDate" => "required|date",
            "items" => "required|array",
            "customer" => "required|array"
        ]);

        $client = Clients::where('customer_id',$request->customer['id'])->where('business_id',$request->business_id)->first();
        
        if(empty($client)){
            $client = new Clients();
            $client->business_id = $request->business_id;
            $client->email = $request->customer["email"];
            $client->phone_country_id = $request->customer["phone_country_id"];
            $client->phone_number = $request->customer["phone_number"];
            $client->first_name = $request->customer["first_name"];
            $client->last_name = $request->customer["last_name"];
            $client->birthday = $request->customer["birthday"];
            $client->customer_id = $request->customer["id"];
            $client->save();
        }

        $request->client_id = $client->id;

        $appointment = new HelpersAppointments();
        $appointment = $appointment->scheduleAppointment($request);

        if(!$appointment['success']){
            return "Appointment not created please contact administrator.";  
        }

        return ["success" => true, "message" => "Appointment scheduled successfully"];
    }

}