<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;

use Yajra\Datatables\Facades\Datatables;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.index');
    }

    /**
     * @return mixed
     */
    public function getUsersData()
    {
        return Datatables::eloquent(User::where('id', '<>', Auth::user()->id))->make(true);
    }

    public function changeUserRole($id, $is_admin)
    {
        $user = User::find($id)->fill(['is_admin' => $is_admin]);

        return ($user->update()) ? $user : false;
    }
}
