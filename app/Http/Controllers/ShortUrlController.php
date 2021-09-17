<?php

namespace App\Http\Controllers;

use App\Models\Branches;
use App\Models\Deals;
use App\Models\ShortUrls;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class ShortUrlController extends Controller
{
    public function browse(Request $request)
    {   
        $requested_url = str_replace('/', '', $request->getRequestUri());
        $short_url = ShortUrls::whereUrlCode($requested_url)->firstOrFail();
        
        if ($short_url->type == "deals") {
            $target = Deals::whereId($short_url->type_id)->firstOrFail();
            return redirect(env("MARKETPLACE_URL") . "deals/" . $target->slug);
        } else if ($short_url->type == "branch") {
            $target = Branches::with("business")->whereId($short_url->type_id)->firstOrFail();
            return redirect(env("MARKETPLACE_URL") . "b/" . $target->business->slug .'/' . $target->slug . "/booking");
        }

        return redirect(env("MARKETPLACE_URL"));
    }

    public function get_by_id($id)
    {
        $short_url = ShortUrls::whereId($id)->firstOrFail();

        return $short_url;
    }
}