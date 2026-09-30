<?php
$content = <<<'EOT'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_system_admin) {
            abort(403, 'Unauthorized. System Administrator access required.');
        }

        return $next($request);
    }
}
EOT;

file_put_contents('app/Http/Middleware/SystemAdminMiddleware.php', $content);
echo "Updated SystemAdminMiddleware.\n";
?>
