<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paste;

class KodonizleController extends Controller
{
    public function index(Request $request)
    {
        $paste = null;
        $recent_pastes = get_recent_pastes();
        
        if ($request->has('id')) {
            $id = $request->input('id');
            $paste = Paste::where('id', $id)->first();
        } else if ($request->has('slug')) {
            $slug = $request->input('slug');
            $paste = Paste::where('slug', $slug)->first();
        }

        $pageTitle = config('settings.kodonizle_description_site_name');
        if ($paste) {
            $pageTitle .= ' - ' . $paste->title;
        }

        return view('kodonizle', compact('paste', 'recent_pastes'))->with('page_title', $pageTitle);
    }
}
