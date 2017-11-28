<?php

require __DIR__ . '/vendor/autoload.php';

define('STORAGE_FOLDER', __DIR__ . '/storage');

if (!is_dir(STORAGE_FOLDER)) {
    mkdir(STORAGE_FOLDER);
}
