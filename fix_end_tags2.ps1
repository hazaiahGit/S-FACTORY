$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$content = $content -replace '</div>`n        </div>`n    </AppLayout>', "                    </div>`n                </div>`n        </div>`n    </AppLayout>"

Set-Content -Path $file -Value $content
