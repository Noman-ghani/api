<?php

namespace App\Http\Controllers\Portal;

use App\Jobs\SendSmsJob;
use App\Helpers\Helpers;
use App\Models\Businesses;
use App\Models\ShortUrls;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignClients;
use App\Models\SmsCampaignLogs;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class SmsCampaignController extends Controller
{
    public function browse()
    {
        return SmsCampaign::whereBusinessId(Helpers::getJWTData("business_id"))->orderBy('id','desc')->get();
    }

    public function get_by_id($id)
    {
        $smsCampaign = SmsCampaign::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->firstOrFail();
        
        if(!empty($smsCampaign->short_url_id)){
            $short_urls = ShortUrls::whereId($smsCampaign->short_url_id)->firstOrFail();
            $smsCampaign->short_url = $short_urls;
        }
        
        $smsCampaign->clients = SmsCampaignClients::whereCampaignId($id)->with("client.phone_country")->get();
        return $smsCampaign;
    }

    public function store(Request $request, $id = null)
    {        
        $rules = [
            "name" => "required",
            "message" => "required",
        ];

        if($request->has("clients") && !empty($request->clients)){
            $rules['clients'] = "required|array";
        }

        if($request->has("filter") && !empty($request->filter)){
            $rules['filter'] = "required";
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        $smsCampaign = SmsCampaign::firstOrNew(["business_id" => Helpers::getJWTData("business_id"), "id" => $id]);
        $smsCampaign->name = $request->name;
        $smsCampaign->message = $request->message;
        $smsCampaign->short_url_id = $request->short_url_id;
        if($request->has("status")){
            $smsCampaign->status = $request->status;
        }
        if($request->has("filter")){
            $smsCampaign->filter =  json_encode($request->filter);
        }
        $smsCampaign->save();


        if($request->has("clients") && !empty($request->clients)){
            
            SmsCampaignClients::whereCampaignId($smsCampaign->id)->delete();
            
            foreach($request->clients as $client){
                $smsCampaignClents = new SmsCampaignClients();
                $smsCampaignClents->campaign_id = $smsCampaign->id;
                $smsCampaignClents->client_id = $client["id"];
                $smsCampaignClents->save();
            }
        }

        return ["success" => true, "message" => __("Sms Campaign saved successfully"), "campaign_id" => $smsCampaign->id];
    }

    public function runCampaign(Request $request, $id){
        try{
            $campaign = $this->get_by_id($id, true);
            if(!empty($campaign->short_url)){
                $campaign->message .= "\n".env("APP_URL"). $campaign->short_url->url_code;
            }
            
            $business = Businesses::whereId($campaign->business_id)->first();
            $message_count = (int)ceil(strlen($campaign->message) / 160) * $campaign->clients->count();
            
            if($message_count > $business->sms_limit){
                return ["success" => false, "message" => "Insufficient msgs in your account please upgrade your package."];
            }

            if($campaign->clients->count() > 0 ){
                foreach($campaign->clients as $data){
                    dispatch(new SendSmsJob($campaign->business_id, $data->client->phone_number, $campaign->message));
                }
                $campaignLog = new SmsCampaignLogs();
                $campaignLog->campaign_id = $id;
                $campaignLog->save();
            }

            return ["success" => true, "message" => "Campaign run successfully"];
        }
        catch(\Exception $e){
            return ["success" => false, "message" => $e->getMessage()]; 
        }
    }

    public function delete_by_id($id){
        // Delete Campaigns
        SmsCampaign::whereBusinessId(Helpers::getJWTData("business_id"))->whereId($id)->delete();
        // Delete Campaigns Clients
        SmsCampaignClients::whereCampaignId($id)->delete();
        $campsigns = SmsCampaign::whereBusinessId(Helpers::getJWTData("business_id"))->orderBy('id','desc')->get();
        return ["success" => true, "message" => __("Campaign deleted successfully."), "campaigns" => $campsigns];
    }
}