<?php
function asset_v($path)
{
    $publicPath = public_path(ltrim($path, '/'));
    $version = file_exists($publicPath) ? filemtime($publicPath) : time();
    return $path . '?v=' . $version;
}
