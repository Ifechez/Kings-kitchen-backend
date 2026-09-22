<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(['settings' => Setting::pluck('value', 'key')]);
    }

    /** Body: { "delivery_fee": "1500", "whatsapp_number": "09018300789", "hero_video_url": "...", ... } */
    public function update(Request $request)
    {
        $data = $request->validate(['*' => 'nullable']);
        foreach ($request->all() as $key => $value) {
            Setting::set($key, $value);
        }
        return response()->json(['settings' => Setting::pluck('value', 'key')]);
    }
}
