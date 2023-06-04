<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Syntax;
use Datatables;
use Illuminate\Http\Request;
use Validator;

class SyntaxController extends Controller
{
    public function index()
    {
        return view('admin.syntax.index')->with('page_title', 'Syntax Languages');
    }

    public function get(Request $request)
    {
        if ($request->ajax()) {
            $syntaxes = Syntax::select(['id', 'name', 'slug', 'extension', 'active', 'popular', 'created_at']);
            return Datatables::of($syntaxes)
                ->editColumn('active', function ($item) {
                    if ($item->active == 1) {
                        return '<span class="text-success">Active</span>';
                    } else {
                        return '<span class="text-danger">Inactive</span>';
                    }

                })
                ->editColumn('popular', function ($item) {
                    if ($item->popular == 1) {
                        return '<span class="text-success">Yes</span>';
                    } else {
                        return '<span class="text-danger">No</span>';
                    }

                })
                ->editColumn('created_at', function ($item) {
                    return (!empty($item->created_at)) ? $item->created_at->format('Y-m-d H:i:s') : '-';
                })                  
                ->addColumn('action', function ($item) {
                    return '<a class="btn btn-sm btn-default" href="' . url('admin/syntax-languages/' . $item->id . '/edit') . '"><i class="fa fa-edit"></i> Edit</a> <a class="btn btn-sm btn-danger" href="' . url('admin/syntax-languages/' . $item->id . '/delete') . '"><i class="fa fa-trash"></i> Delete</a>';
                })
                ->make(true);
        }
    }

    public function create()
    {
        return view('admin.syntax.create')->with('page_title', 'Syntax Languages');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|min:3|max:25|eco_string|unique:syntax,name',
            'extension' => 'nullable|alpha_num|max:15',
            'popular'   => 'required|numeric|in:0,1',
            'active'    => 'required|numeric|in:0,1',
            'active_link'    => 'required|numeric|in:0,1',
            'redirect_page'    => 'required|numeric|in:0,1',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $syntax            = new Syntax();
            $syntax->name      = $request->name;
            $syntax->slug      = str_slug($request->name);
            $syntax->extension = $request->extension;
            $syntax->popular   = $request->popular;
            $syntax->active    = $request->active;
            $syntax->active_link    = $request->active_link;
            $syntax->redirect_page    = $request->redirect_page;

            $syntax->save();
            \Cache::forget('syntaxes');
            return redirect()->back()->withSuccess('Successfully created.');
        }
    }

    public function edit($id)
    {
        $syntax = Syntax::findOrfail($id);
        return view('admin.syntax.edit', compact('syntax'))->with('page_title', 'Syntax Languages');
    }

    public function update($id, Request $request)
    {
        $syntax    = Syntax::where('id', $id)->firstOrfail();
        $validator = Validator::make($request->all(), [
            'name'      => 'required|min:3|max:25|eco_string|unique:syntax,name,' . $syntax->id,
            'extension' => 'nullable|alpha_num|max:15',
            'popular'   => 'required|numeric|in:0,1',
            'active'    => 'required|numeric|in:0,1',
            'active_link'    => 'required|numeric|in:0,1',
            'redirect_page'    => 'required|numeric|in:0,1',            
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $syntax->name      = $request->name;
            $syntax->extension = $request->extension;
            $syntax->popular   = $request->popular;
            $syntax->active    = $request->active;
            $syntax->active_link    = $request->active_link;
            $syntax->redirect_page    = $request->redirect_page;            
            $syntax->save();
            \Cache::forget('syntaxes');
            return redirect()->back()->withSuccess('Successfully updated.');
        }
    }

    public function destroy($id)
    {
        Syntax::where('id', $id)->delete();
        \Cache::forget('syntaxes');
        return redirect()->back()->withSuccess('Syntax Successfully deleted.');
    }
}
