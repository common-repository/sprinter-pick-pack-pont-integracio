<?php

require_once('logger.php');

function sprinter_export2csv($rows, $filename, $withHeaders=true, $stripFromHeader='') {
    $delimiter=';';
    $enclosure='';
    $header='';
    if ($rows) {
        $fp = fopen($filename, "w");
        if (!$fp) return false;
        if ($withHeaders && count($rows)>0) {
            $keyCount=count(array_keys($rows[0]));
            foreach(array_keys($rows[0]) as $key => $hdr) {
                $header .= ($enclosure . $hdr . $enclosure);
                if ($key < $keyCount-1) $header .= $delimiter;
            }
            if (!sprinter_isnullorempty($stripFromHeader)) $header = str_replace($stripFromHeader,'',$header);
            fputs($fp,$header . PHP_EOL);
        } 
        foreach($rows as $row) {
            if (sprinter_isnullorempty($enclosure)) {
                fputcsv($fp, array_values($row),$delimiter);
            }
            else {
                fputcsv($fp, array_values($row),$delimiter,$enclosure);
            }
        }
        fclose($fp);
        return true;
    }
    return false; 
}

?>