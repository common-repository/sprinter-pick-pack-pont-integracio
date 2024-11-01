<?php

function sprinter_isnullorempty( $v ) {
    if (isset($v) && strlen($v)>0) return false;
    return true;
}

?>