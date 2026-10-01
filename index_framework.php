
<?php
$dirs = ["vendor/eber/framework/Base/Module/", "vendor/eber/framework/Base/Helpers/", "vendor/eber/framework/Base/Control/", "vendor/eber/framework/Base/Builder/"];
$res = [];
foreach ($dirs as $dir) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iter as $file) {
        if ($file->isDir() || $file->getExtension() !== "php") continue;
        $content = file_get_contents($file->getPathname());
        if (strpos($file->getPathname(), "Helpers") !== false) {
            preg_match_all("/function\s+([a-zA-Z0-9_]+)\s*\(/", $content, $m);
            $res["File: " . $file->getFilename()] = $m[1];
        } else {
            if (preg_match("/namespace\s+([^;]+);/", $content, $m_ns) && preg_match("/class\s+([a-zA-Z0-9_]+)/", $content, $m_cl)) {
                $cls = $m_ns[1] . "\\\\" . $m_cl[1];
                preg_match_all("/public\s+(static\s+)?function\s+([a-zA-Z0-9_]+)\s*\(/", $content, $m);
                $res[$cls] = [];
                foreach ($m[2] as $i => $name) {
                    $res[$cls][] = trim($m[1][$i] . " " . $name);
                }
            }
        }
    }
}
echo json_encode($res, JSON_PRETTY_PRINT);

