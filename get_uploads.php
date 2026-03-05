<?php
$uploadDir = 'uploads/';

$files = [];
if (is_dir($uploadDir)) {
    $dirContents = scandir($uploadDir);
    foreach ($dirContents as $item) {
        if ($item !== '.' && $item !== '..' && is_file($uploadDir . $item)) {
            $files[] = [
                'name' => $item,
                'path' => $uploadDir . $item,
                'size' => filesize($uploadDir . $item),
                'modified' => date('Y-m-d H:i:s', filemtime($uploadDir . $item))
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($files);
?>