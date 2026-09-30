$file = "resources/js/Layouts/AppLayout.vue"
$content = Get-Content $file -Raw

$content = $content -replace "import \{ UserCog, ref, computed \} from 'vue';", "import { ref, computed } from 'vue';"
$content = $content -replace "import \{ UserCog, Link, usePage, router \} from '@inertiajs/vue3';", "import { Link, usePage, router } from '@inertiajs/vue3';"
$content = $content -replace "import \{ UserCog,\s*LayoutDashboard", "import { UserCog, LayoutDashboard"
$content = $content -replace "import \{ UserCog, Menu as HeadlessMenu", "import { Menu as HeadlessMenu"

Set-Content -Path $file -Value $content
