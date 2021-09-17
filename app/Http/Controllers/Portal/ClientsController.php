<?php

namespace App\Http\Controllers\Portal;

use App\Helpers\Helpers;
use App\Jobs\SendEmailJob;
use App\Models\AppointmentItems;
use App\Models\Appointments;
use App\Models\ClientDeals;
use App\Models\Clients;
use App\Models\Countries;
use App\Models\InvoiceItems;
use App\Models\Invoices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class ClientsController extends Controller
{
    public function browse(Request $request)
    {
        $clients = Clients::whereBusinessId(Helpers::getJWTData("business_id"));
        
        if ($request->has("with-phone-country")) {
            $clients->with("phone_country");
        }
        if ($request->has("with-deals")) {
            $clients->with("deals", function ($query) {
                $query->with("deal");
                $query->with(["items" => function ($query) {
                    $query->whereRaw("quantity_available > quantity_utilized");
                }]);
                $query->where("expires_at", '>', Carbon::now());
            });
        }
        if($request->has("filter")){
            $filter = json_decode($request->filter, JSON_FORCE_OBJECT);
            $type = null;
            $daysLimit = 30;
            $lastRecord = null;
            
            if(isset($filter["new_clients"])){
                $type = $filter["new_clients"]["type"];
                $daysLimit = $filter["new_clients"]["day_limit"];
            }
            
            if(isset($filter["recent"])){
                $type = $filter["recent"]["type"];
                $daysLimit = $filter["recent"]["day_limit"];
            }

            if(isset($filter["loyal"])){
                $lastRecord = Carbon::now()->subMonths($filter['loyal']['months']);
            }

            if(isset($filter["lapsed"])){
                $lastRecord = Carbon::now()->subMonths($filter['lapsed']['months']);
            }

            if(isset($filter["spend"])){
                $lastRecord = Carbon::now()->subMonths($filter['spend']['months']);
            }

            if(!empty($type)){
                switch($type){
                    case "days":
                        $lastRecord = Carbon::now()->subDays($daysLimit);
                    break;
                    case "weeks":
                        $lastRecord = Carbon::now()->subWeeks($daysLimit);
                    break;
                    case "months":
                        $lastRecord = Carbon::now()->subMonths($daysLimit);
                    break;
                    default:
                        $lastRecord = Carbon::now()->subDays($daysLimit);
                    break;
                }
            }
            
            
            if(isset($filter["new_clients"])){
                $clients->whereDate('created_at', '>', $lastRecord); 
            }

            if(isset($filter["recent"]) || isset($filter["loyal"]) || isset($filter["lapsed"]) || isset($filter["spend"])){
                
                $clients->with(["invoices" => function($inv) use ($lastRecord, $filter){
                    $inv->whereStatus('completed');
                    $inv->whereDate('created_at', '>', $lastRecord);
                    if(isset($filter["spend"])){
                        $inv->where('grandtotal','>=',$filter["spend"]['total']);
                    }
                }])->whereHas('invoices', function($inv) use ($lastRecord, $filter){
                    $inv->whereStatus('completed');
                    $inv->whereDate('created_at', '>', $lastRecord);
                    if(isset($filter["spend"])){
                        $inv->where('grandtotal','>=',$filter["spend"]['total']);
                    }
                });

            }

            if(isset($filter["online_booking"]) || isset($filter["appointment"])){
                
                $clients->with(["appointments" => function( $app) use ($filter){
                    $app->where('status', '!=', 'cancelled');
                    if(isset($filter["appointment"]) && $filter["appointment"]["staff"] != 0){
                        $app->with(['items' => function($app_history) use ($filter){
                            $app_history->whereStaffId($filter["appointment"]["staff"]);
                        }])->whereHas('items',function($app_history) use ($filter){
                            $app_history->whereStaffId($filter["appointment"]["staff"]);
                        });
                        if($filter["appointment"]["date_type"] == "custom-date"){
                            $app->whereDate('created_at','>=',$filter["appointment"]["date_from"])
                            ->whereDate('created_at','<=',$filter["appointment"]["date_to"]);
                        }
                    }

                }]);

            }
            
            if(isset($filter["birthday"])){
                $select_month = $filter["birthday"]["select_month"];
                
                $clients->whereMonth("birthday", $select_month); 
            }

        }
        
        $clients = $clients->get();
        if(isset($filter)){
            $clients = $clients->filter(function ($name) use ($filter) {
    
                if(isset($filter["recent"]) && $name->invoices->count() == 1){
                    return $name;
                }
                
                else if(isset($filter["loyal"])  && ($name->invoices->count() >= $filter["loyal"]["sales"])){
                    foreach($name->invoices as $invoice){
    
                        if((float)$invoice->grandtotal >= (float)$filter["loyal"]["money"]){
                            
                            return $name;
                        }
    
                    }
                }
    
                else if(isset($filter["lapsed"])  && ($name->invoices->count() >= $filter["lapsed"]["sales"])){
                    
                    foreach($name->invoices as $invoice){
                        
                        if($invoice->created_at <= Carbon::now()->subMonths($filter['lapsed']['not_return']) ){
                            return $name;
                        }
                    
                    }
    
                }

                else if(isset($filter["online_booking"])){
                    if($filter["online_booking"]["data"] == "have-online-bookings"){
                        if($name->appointments->count() > 0){
                            return $name;
                        }
                    }
                    if($filter["online_booking"]["data"] == "no-online-bookings"){
                        if($name->appointments->count() == 0){
                            return $name;
                        }
                    }
    
                }

                else if(isset($filter["appointment"])){
                    if($name->appointments->count() > 0){
                        return $name;
                    }
                }

                else{
                    return $name;
                }
    
            })->values();
        }

        return $clients;
    }
    
    public function store(Request $request, $id = null)
    {
        $this->validate($request, [
            "first_name" => "required",
            "last_name" => "required",
            "phone_country_id" => "required",
            "phone_number" => "required",
            "email" => "nullable|email"
        ]);

        if (Clients::emailExists($request->email, $id)) {
            return response()->json(["email" => [__("This email is already taken.")]], 422);
        }

        if (Clients::phoneNumberExists($request->phone_number, $id)) {
            return response()->json(["phone_number" => [__("This phone number is already taken.")]], 422);
        }
        
        $client = Clients::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $client->first_name = $request->first_name;
        $client->last_name = $request->last_name;
        $client->phone_country_id = $request->phone_country_id;
        $client->phone_number = $request->phone_number;
        $client->email = $request->email;
        $client->address = $request->address;
        $client->suburb = $request->suburb;
        $client->state_id = $request->state_id;
        $client->city_id = $request->city_id;
        $client->zip_code = $request->zip_code;
        $client->birthday = $request->birthday;
        $client->gender = $request->gender;
        $client->notes = $request->notes;
        $client->save();

        return ["success" => true, "message" => __("Client saved successfully.")];
    }

    public function get_by_id(Request $request, $id)
    {
        $client = Clients::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id);

        if ($request->has("with-phone-country")) {
            $client->with("phone_country");
        }
        if ($request->has("with-state")) {
            $client->with("state");
        }
        if ($request->has("with-city")) {
            $client->with("city");
        }
        if ($request->has("with-deals")) {
            $client->with("deals", function ($query) {
                $query->with("deal");
                $query->with(["items" => function ($query) {
                    $query->whereRaw("quantity_available > quantity_utilized");
                }]);
                $query->where("expires_at", '>', Carbon::now());
            });
        }
        
        return $client->firstOrFail();
    }

    public function importClients(Request $request)
    {
        $file = $request->file("file");
        $variant = "success";
        $message = "Your clients are imported. If you do not see your client in the list, please contact support.";
        
        if (($handle = fopen($file, 'r')) !== false) {
            $index = 0;
            $country = Countries::whereId($request->business->country_id)->firstOrFail();
            while (($client = fgetcsv($handle, 1000)) !== false) {
                if ($index === 0) {
                    $index++;
                    continue;
                }

                $index++;

                if (!preg_match($country->phone_regex, $client[3])) {
                    $variant = "danger";
                    $message = "System could not import some clients due to technical error. Please make sure you have provided the data in the required format. Feel free to contact support";
                    continue;
                }

                $isNewClient = Clients::whereBusinessId(Helpers::getJWTData("business_id"))->where(function ($query) use ($client) {
                    $query->whereEmail($client[2]);
                    $query->orWhere("phone_number", $client[3]);
                })->exists();

                if ($isNewClient) {
                    $variant = "danger";
                    $message = "System could not import some clients due to technical error. Please make sure you have provided the data in the required format. Feel free to contact support";
                    continue;
                }

                // m,d,y

                try {
                    $clients = new Clients();
                    $clients->business_id = Helpers::getJWTData("business_id");
                    $clients->first_name = $client[0];
                    $clients->last_name = $client[1];
                    $clients->email = $client[2];
                    $clients->phone_country_id = $country->id;
                    $clients->phone_number = $client[3];
                    $clients->gender = $client[4];
                    $clients->notes = $client[6] ?? null;

                    if (!empty($client[5])) {
                        $clients->birthday = Carbon::createFromFormat("d/m/Y", $client[5])->format("Y-m-d");
                    }
                    
                    $clients->save();
                } catch (\Exception $e) {
                    $variant = "danger";
                    $message = "System could not import some clients due to technical error. Please make sure you have provided the data in the required format. Feel free to contact support";
                    continue;
                }
            }
        }

        return ["success" => true, "message" => __($message), "variant" => $variant];
    }

    public function block(Request $request, $id)
    {
        $this->validate($request, [
            "block_reason" => "required"
        ]);

        $client = Clients::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
        $client->is_blocked = 1;
        $client->block_reason_id = $request->block_reason;
        $client->save();

        return ["success" => true, "message" => __("Client blocked successfully.")];
    }

    public function unblock(Request $request, $id)
    {
        $client = Clients::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
        $client->is_blocked = null;
        $client->block_reason_id = null;
        $client->save();

        return ["success" => true, "message" => __("Client unblocked successfully.")];
    }

    public function getAppointments($id)
    {
        return Appointments::with(["items" => function ($query) {
            $query->with("service");
            $query->with("staff");
        }])
        ->whereClientId($id)
        ->orderBy("id", "DESC")
        ->get();
    }

    public function getProducts($id)
    {
        return InvoiceItems::with("invoice")->whereHas("invoice", function ($query) use ($id) {
            $query->whereBusinessId(Helpers::getJWTData("business_id"));
            $query->whereClientId($id);
        })
        ->get();
    }

    public function getDeals(Request $request, $id)
    {
        $clientDeals = ClientDeals::with("deal")->whereClientId($id);
        
        if ($request->has("with-items")) {
            $clientDeals->with(["items" => function ($query) {
                $query->with("service");
                $query->with("product");
            }]);
        }
        
        if ($request->has("with-invoice")) {
            $clientDeals->with("invoice");
        }

        return $clientDeals->get();
    }

    public function getSummary($id)
    {
        $totalSales = 0;
        $outstanding = 0;
        $allBookings = 0;
        $completed = 0;
        $cancelled = 0;
        $noShows = 0;

        $appointmentItems = AppointmentItems::whereHas("appointment", function ($query) use ($id) {
            $query->whereBusinessId(Helpers::getJWTData("business_id"));
            $query->whereClientId($id);
        })->get();

        $allBookings = count($appointmentItems);

        foreach ($appointmentItems as $appointmentItem) {
            if ($appointmentItem->status === "completed") {
                $totalSales += $appointmentItem->price;
                $completed += 1;
            } else if ($appointmentItem->status === "cancelled") {
                $cancelled += 1;
            } else if ($appointmentItem->status === "no-show") {
                $noShows += 1;
            }
        }

        return [
            "totalSales" => $totalSales,
            "outstanding" => $outstanding,
            "allBookings" => $allBookings,
            "completed" => $completed,
            "cancelled" => $cancelled,
            "noShows" => $noShows
        ];
    }

    public function doActionOnSms()
    {
        $data = request()->all();
        $data["method"] = request()->method();
        dispatch(new SendEmailJob(["danish.bhayani@genetechsolutions.com"], "EOcean Incoming Servu SMS", json_encode($data)));
        return ["success" => true, "message" => "sms received for further action."];
    }

    public function downloadImportFile()
    {
        return response()->download(base_path("public/Servu Partners Client Import.csv"));
    }
}