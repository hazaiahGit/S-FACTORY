<?php
$content = <<<'EOT'
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
EOT;

file_put_contents('app/Http/Controllers/ActiveBranchController.php', $content);

// Add to web.php
$web = file_get_contents('routes/web.php');
if (strpos($web, 'ActiveBranchController') === false) {
    $route = "\n    Route::post('/set-active-branch', [\App\Http\Controllers\ActiveBranchController::class, 'update'])->name('active-branch.update');\n";
    $web = str_replace("Route::resource('targets'", $route . "    Route::resource('targets'", $web);
    file_put_contents('routes/web.php', $web);
}
echo "ActiveBranchController created.\n";
?>
