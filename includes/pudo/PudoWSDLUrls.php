<?php

function PudoWSDLUrl($service,$mode) {

    if (!$service || !$mode) {
        return '';
    }

    $wsdl_urls['partner']['live']='https://tsx.lapker.hu/PudoProd/PartnerPudoService?wsdl';
    $wsdl_urls['document']['live']='https://tsx.lapker.hu/PudoProd/PudoDocumentService?wsdl';
    $wsdl_urls['partner']['test']='https://tsx.lapker.hu/PudoTest/PartnerPudoService?wsdl';
    $wsdl_urls['document']['test']='https://tsx.lapker.hu/PudoTest/PudoDocumentService?wsdl';

    $url = $wsdl_urls[$service][$mode];

    if ($url) {
        return $url;
    }

    return '';
}

