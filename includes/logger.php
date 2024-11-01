<?php

 function sprinter_logger($log_msg) {
    $log_filename = $_SERVER['DOCUMENT_ROOT']."/log";
    if (!is_dir($log_filename))
    {
        mkdir($log_filename, 0755, true);
    }
    $log_file_data = $log_filename.'/log_' . date('Y-M-d') . '.log';
    file_put_contents($log_file_data, date('Y-m-d H:i:s:') . $log_msg . "\n", FILE_APPEND);
}

?>