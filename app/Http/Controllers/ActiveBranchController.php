<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActiveBranchController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $user = $request->user();
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            session(['active_branch_id' => $request->branch_id]);
        }

        return back();
    }
}