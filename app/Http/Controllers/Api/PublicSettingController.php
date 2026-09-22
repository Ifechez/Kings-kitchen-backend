<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class PublicSettingController extends Controller
{
    /** Only safe, non-sensitive settings the frontend needs before login. */
    public function index()
    {
        $keys = ['delivery_fee', 'whatsapp_number', 'hero_video_url', 'brand_tagline', 'restaurant_lat', 'restaurant_lng'];
        return response()->json(['settings' => Setting::whereIn('key', $keys)->pluck('value', 'key')]);
    }
}
