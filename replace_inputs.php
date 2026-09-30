<?php
$directory = new RecursiveDirectoryIterator('resources/js/Pages');
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.vue$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach ($regex as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    
    $newContent = preg_replace_callback('/<input\b([^>]*)>/is', function($matches) {
        $attrs = $matches[1];
        
        if (preg_match('/type=[\'"]number[\'"]/i', $attrs)) {
            $attrs = preg_replace('/\s*type=[\'"]number[\'"]/i', '', $attrs);
            $attrs = preg_replace('/\s*step=[\'"][^\'"]*[\'"]/i', '', $attrs);
            $attrs = preg_replace('/\s*min=[\'"][^\'"]*[\'"]/i', '', $attrs);
            $attrs = preg_replace('/\s*v-model\.number=/i', ' v-model=', $attrs);
            
            // Check if the original input was self-closing or not
            // Our regex /<input\b([^>]*)>/ matches the whole opening tag.
            // If it had a closing </input>, we are ignoring it, but Vue inputs are usually self-closing or handled correctly by compiler.
            // To be safe, we will just make it self-closing.
            $attrs = rtrim($attrs, '/ ');
            return '<FormattedNumberInput' . $attrs . ' />';
        }
        
        return $matches[0];
    }, $content);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        $count++;
        echo "Updated $path\n";
    }
}
echo "Total files updated: $count\n";
?>
