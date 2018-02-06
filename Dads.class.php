<?php

function dirname_r($path, $count=1){
    if ($count > 1){
        return dirname(dirname_r($path, --$count));
    }else{
        return dirname($path);
    }
}

function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source) === true) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

function Preview($source)
{
    define("IMAGETYPE", 'thumbnails');

    $source = str_replace('\\', '/', realpath($source));
    $source = rtrim($source, "\\");

    if (is_dir($source . "/" . "thumbnails") === true) {

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . "/" . IMAGETYPE), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);
            $imagedirectory = basename(dirname_r($file, 2));
            if (is_file($file))
                echo '<img src="' . dirname(htmlspecialchars($_SERVER['HTTP_REFERER'])) . '/images/' . $imagedirectory . '/' . IMAGETYPE . '/' . basename($file) . '" width="40"/>';
        }

    }
    return;
}
?>