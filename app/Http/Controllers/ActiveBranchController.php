<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActiveBranchController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('business_id', $user->business_id),
            ],
        ]);

        if ($user->isTenantAdmin()) {
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : 'all';
            session(['active_branch_id' => $branchId]);
        }

        return back();
    }
}
