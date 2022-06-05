<?php

const DB_URL = 'https://github.com/gatheringhallstudios/MHWorldData/releases/latest/download/mhw.db';

function updateDatabaseFromSource()
{
    $file_name = basename(DB_URL);
    file_put_contents_atomically($file_name, file_get_contents(DB_URL), FILE_APPEND);
}

function file_put_contents_atomically($filename, $data, $flags = 0, $context = null)
{
    if (file_put_contents($filename . '~', $data, $flags, $context) === strlen($data)) {
        chmod($filename . '~', 0755);
        return rename($filename . '~', $filename, $context);
    }

    @unlink($filename . '~', $context);
    return FALSE;
}

updateDatabaseFromSource();