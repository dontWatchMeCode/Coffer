<?php

$content = file_get_contents('_ide_helper.php');

// Remove incrementEach method
$content = preg_replace('/public static function incrementEach[\s\S]*?^        }$/m', '', $content);

// Remove decrementEach method
$content = preg_replace('/public static function decrementEach[\s\S]*?^        }$/m', '', $content);

// Fix Collection type hint
$content = str_replace('@param Collection<int, string> ', '@param \Illuminate\Support\Collection<int, string> ', $content);

// Fix self type hint to Builder
$content = str_replace('@param self $', '@param \Illuminate\Database\Query\Builder $', $content);

file_put_contents('_ide_helper.php', $content);

echo "IDE Helper fixes applied.\n";
