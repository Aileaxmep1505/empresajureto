<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SkydropxProClient;

class SkydropxDebugController extends Controller
{
    public function carriers(SkydropxProClient $sdk)
    {
        return response()->json($sdk->carriers());
    }

    public function quote(Request $r, SkydropxProClient $sdk)
    {
        $zip = (string) $r->input('to', '06100');
        $parcel = [
            'weight' => (float) $r->input('weight', 1),
            'length' => (int) $r->input('length', 10),
            'width'  => (int) $r->input('width', 10),
            'height' => (int) $r->input('height', 10),
        ];
        return response()->json($sdk->quote($zip, $parcel));
    }
}
