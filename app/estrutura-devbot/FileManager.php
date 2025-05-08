<?php
// FileManager.php

require_once __DIR__ . '/config.php';

class FileManager {
    public static function save($filename, $content, $subdir = '') {
        $dir = rtrim(TMP_DIR . '/' . $subdir, '/');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents("$dir/$filename", $content);
    }

    public static function read($filename, $subdir = '') {
        $file = TMP_DIR . '/' . $subdir . '/' . $filename;
        return file_exists($file) ? file_get_contents($file) : null;
    }

    public static function diff($original, $modificado) {
        $a = explode("\n", $original);
        $b = explode("\n", $modificado);
        $out = [];
        foreach ($a as $i => $line) {
            if (!isset($b[$i])) {
                $out[] = "- $line";
            } elseif ($line !== $b[$i]) {
                $out[] = "- $line";
                $out[] = "+ " . $b[$i];
            } else {
                $out[] = "  $line";
            }
        }
        foreach (array_slice($b, count($a)) as $line) {
            $out[] = "+ $line";
        }
        return implode("\n", $out);
    }
}
