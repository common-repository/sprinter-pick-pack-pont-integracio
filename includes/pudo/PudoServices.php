<?php

global $woocommerce;

include 'PudoDocumentService.php';
include 'PartnerPudoService.php';
include 'PudoWSDLUrls.php';
include 'PudoSandboxCredentials.php';


global $soap_options;
$soap_options = array(
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'cache_wsdl' => WSDL_CACHE_NONE,
);

global $PartnerPudoServiceWSDL;
global $PudoDocumentServiceWSDL;
global $partnerkod;
global $token;

if (get_option('sprinter_kornyezet')=='eles') {
    $PartnerPudoServiceWSDL = PudoWSDLUrl('partner','live');
    $PudoDocumentServiceWSDL = PudoWSDLUrl('document','live');
    $partnerkod=get_option('sprinter_partnerkod');
    $token=get_option('sprinter_token');
}
else {
    $PartnerPudoServiceWSDL = PudoWSDLUrl('partner','test');
    $PudoDocumentServiceWSDL = PudoWSDLUrl('document','test');
    $partnerkod=PudoSandboxPartnerkod();
    $token=PudoSandboxToken();
}


function sprinter_nextCounter() {
    $counter = get_option('sprinter_szamlalo');
    if (!$counter) {
        $counter=1;
        add_option('sprinter_szamlalo',$counter+1);
    }
    else {
        update_option('sprinter_szamlalo',$counter+1);
    }
    return str_pad($counter, 12, '0', STR_PAD_LEFT);
}

function sprinter_isCODorder($order)
{
    $payment_method = method_exists( $order, 'get_payment_method' ) ? $order->get_payment_method() : $order->payment_method;

    if ( $payment_method == 'cod' || in_array( $payment_method, get_option( 'sprinter_fizetesi_modok' ) ) )		
    {
        return true;
    }
    return false;
}

function PudoRequest($orders, $params, $fuvarlevel_index, $deliverytype)
{
    global $partnerkod;
    global $token;
    
    $ret=array([
        'status'=>'',
        'message'=>'',
        'order_id'=>'',
        'shipment'=>'',
        'barcode' => '',
        'orig_barcode' => '',
        'apirequest'=>'',
        'apiresponse'=>''
    ]);

    $ParcelContainer = array();
    $retContainer = array();

    $bulk = (count($orders)>1);
    $i = 0;
    $skipAPIcall = false;

    foreach ($orders as $order) {

        $delivery_type = get_post_meta($order->ID,'parcel_type',true);
        $sprinter_kivalasztott_pickpackpont = get_post_meta( $order->ID, '_sprinter_kivalasztott_pickpackpont',true );

        if (!isset($order)) {
            $ret[$i]["status"]="NOK";
            $ret[$i]["message"]="Order not set";
            $skipAPIcall = true;
            $i++;
            continue;
        }
        $ret[$i]['order_id']=$order->get_id();

        $isPPP = false;
        $PPP = get_post_meta( $order->get_id(), '_sprinter_kivalasztott_pickpackpont', true );

        $PPP_data = false;
        if (!sprinter_isnullorempty($PPP)) {
            $isPPP = true;
            $PPP_data = json_decode( str_replace( "'", '"', $PPP ), true  );
        }

        $parcel = new Parcel();
        if ($isPPP) {
            $parcel->ServiceType = ParcelServiceType::$Normal;
            $parcel->DestinationLocationId =  sprinter_ppp_azonosito($PPP_data['shopCode']);
            $pos2pos=get_option('sprinter_pickpackpont_pos2pos');
            if (!sprinter_isnullorempty($pos2pos)) {
                $parcel->PickupLocationId = sprinter_ppp_azonosito($pos2pos);
                if ($parcel->DestinationLocationId == $parcel->PickupLocationId) {
                    $parcel->ServiceType = ParcelServiceType::$Direct;
                }
                else {
                    $parcel->ServiceType = ParcelServiceType::$Pos2Pos;
                }
            }
        }
        else {
            $parcel->ServiceType = ParcelServiceType::$HomeDeliver;
        }

        $barcodePrefix = get_option('sprinter_prefix');
        if (sprinter_isnullorempty($barcodePrefix)) $barcodePrefix='TWC';

        $shp = get_post_meta($order->ID,'_sprinter_azonosito',true);


        if($fuvarlevel_index == 1) {
            $flv = get_post_meta($order->ID, '_sprinter_fuvarlevelszam', true);
        }
        else{
            // levizsgálni, hogy van e cserecsomag
            $flv = get_post_meta($order->ID,'_sprinter_fuvarlevelszam_cserecsomag',true);
        }

        if ($params['ctx']=='vonalkod') {
            $parcel->BarCode = $flv;
        }
        else if($params['ctx']=='dokumentumok'){
            if($delivery_type == 'SPRINTER-SWAP' && empty($sprinter_kivalasztott_pickpackpont) && $deliverytype == 'visszaru'){         
                $flv = get_post_meta($order->ID,'_sprinter_fuvarlevelszam_cserecsomag',true);
            } else {
                $flv = get_post_meta($order->ID, '_sprinter_fuvarlevelszam', true);
            }
            $parcel->BarCode = $flv;
        }
        else
        {
            $parcel->BarCode = $barcodePrefix . sprinter_nextCounter();
        }

        $ret[$i]['orig_barcode']=$parcel->BarCode;

        $auth_code = get_post_meta($order->ID,'_auth_code',true);
        $inv_number = get_post_meta($order->ID,'_invoice_number',true);
        if (!sprinter_isnullorempty($auth_code)) {
            $parcel->AutorizationCode = $auth_code;
        }
        else {
            $parcel->AutorizationCode = $order->get_id();
        }
        if (!sprinter_isnullorempty($inv_number)) {
            $parcel->InvoiceNumber = $inv_number;
        }        

        if (sprinter_isCODorder($order)) {
            $parcel->PriceAtDelivery = $order->get_total();
        }
        else {
            $parcel->PriceAtDelivery = 0;
        }
        $parcel->PriceAtDeliveryCurrency = $order->get_currency();
        $parcel->PackagePrice = $order->get_total();
        $parcel->PackagePriceCurrency = $order->get_currency();

        $parcel->CustomerName = $order->get_shipping_last_name() . ' ' . $order->get_shipping_first_name();
        $parcel->CustomerPhone = $order->get_billing_phone();
        $parcel->CustomerEmail = $order->get_billing_email();

        $parcel->CustomerCountryCode = $order->get_shipping_country();
        $parcel->CustomerPostalCode = $order->get_shipping_postcode();
        $parcel->CustomerCity = $order->get_shipping_city();
        $parcel->CustomerAddress = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $parcel->CustomerStreetNumber = "";

        if ($isPPP) {
            switch (get_option('sprinter_pickpackpont_meret'))
            {
                case 'S':
                    $parcel->PackageType = PackageType::$Small;
                    break;
                case 'M':
                    $parcel->PackageType = PackageType::$Medium;
                    break;
                case 'L':
                    $parcel->PackageType = PackageType::$Large;
                    break;
                case 'XL':
                    $parcel->PackageType = PackageType::$Special;
                    break;
                default:
                    $parcel->PackageType = PackageType::$None;
                    break;
            }
        }
        else {
            $parcel->PackageWeight = get_option('sprinter_suly');
            $parcel->PackageSizeX = get_option('sprinter_hosszusag');
            $parcel->PackageSizeY = get_option('sprinter_szelesseg');
            $parcel->PackageSizeZ = get_option('sprinter_magassag');
            $parcel->PackageVolume = ($parcel->PackageSizeX * $parcel->PackageSizeY * $parcel->PackageSizeZ) / (100 * 100 * 100);
        }

        $parcel->ParcelCount = (isset($params['custom_parcel_count']) && !empty($params['custom_parcel_count'] && is_numeric($params['custom_parcel_count']) && $params['custom_parcel_count'] > 0)) ? $params['custom_parcel_count'] : 1;
        $parcel->TransitTime = 2;

        
        if($fuvarlevel_index == 1 || $fuvarlevel_index == 2){
            // ha 1 es akkor only delivery 
            if($fuvarlevel_index == 1){
                $parcel->DeliveryType = ParcelDeliveryType::$OnlyDelivery;
            }
            else{
                $parcel->DeliveryType = ParcelDeliveryType::$DeliveryAndReturns;
                $parcel->ParcelCount = 1;
            }
        }
        else{
            if($delivery_type == 'SPRINTER-SWAP' && empty($sprinter_kivalasztott_pickpackpont) && $deliverytype == 'visszaru'){         
                $parcel->DeliveryType = ParcelDeliveryType::$DeliveryAndReturns;
                $parcel->ParcelCount = 1;
            } else {
                $parcel->DeliveryType = ParcelDeliveryType::$OnlyDelivery;
            }
        }  
        $ParcelContainer[] = $parcel;


        $i++;
    }

    $retContainer = $ret;

    $RegisterParcelContainerRequest = new RegisterParcelContainerRequest();

    $RegisterParcelContainerRequest->PartnerCode = $partnerkod;
    $RegisterParcelContainerRequest->Token = $token;
    $RegisterParcelContainerRequest->ParcelContainer = $ParcelContainer;

    $RegisterParcelContainerRequest->OnlyLabelPrinting = false; 
    $RegisterParcelContainerRequest->FinalizeLabelPrinting = false; 

    $SupplimentJSONData = '{"IsWooCommerceRegistration":true}';
    $RegisterParcelContainerRequest->SupplimentJSONData = $SupplimentJSONData;

    $result = PudoRegisterParcels($RegisterParcelContainerRequest, $retContainer, $params);

    return $result;
}

function PudoRegisterParcels($RegisterParcelContainerRequest, $retContainer, $params)
{
    global $PartnerPudoServiceWSDL;
    global $soap_options;
    global $partnerkod;
    global $token;
    
    $i = 0;

    try
    {
        if ($params['csom']) {
            $PartnerPudoServiceClient = new PartnerPudoService($PartnerPudoServiceWSDL, $soap_options);

            $RegisterParcelContainerRequest->PartnerCode=$partnerkod;
            $RegisterParcelContainerRequest->Token=$token;
        
            $RegisterParcelContainerResponse = $PartnerPudoServiceClient->RegisterParcelContainer($RegisterParcelContainerRequest);

            $retContainer[0]["apirequest"]=json_encode($RegisterParcelContainerRequest,JSON_PRETTY_PRINT);

            if ($PartnerPudoServiceClient == null || $RegisterParcelContainerResponse->RegisterParcelContainerResult->ParcelResults == null) {
                $retContainter[0]["status"]="NOK";
                $retContainer[0]["message"]= __("Nem érkezett válaszüzenet a Sprinter/PPP rendszerétől");
                return $retContainer;
            }

            $retContainer[0]["apiresponse"]=json_encode($RegisterParcelContainerResponse,JSON_PRETTY_PRINT);

            $Barcodes = [];
            $barcodeList = [];

            $shipmentErrorCode = $RegisterParcelContainerResponse->ErrorCode;
            if (!sprinter_isnullorempty($shipmentErrorCode) && $shipmentErrorCode!='PSR_OK') {
                $retContainer[0]['status']='NOK';
                $retContainer[0]['message']= __('Hiba a szállítmány létrehozásakor:') . ' ' . $shipmentErrorCode;
                return $retContainer;
            }

            $ParcelResults = $RegisterParcelContainerResponse->RegisterParcelContainerResult->ParcelResults;
            foreach ($ParcelResults->ParcelResult as $Parcel) {
                foreach ($retContainer as $key => $ret) {
                    if ($ret['orig_barcode']==$Parcel->OriginalBarCode) {
                        $retContainer[$key]['message']=$Parcel->ErrorCode;
                        if (!is_null($Parcel->ErrorMessageInternational) || $Parcel->ErrorMessageInternational != "") {
                            $retContainer[$key]["message"].=$Parcel->ErrorMessageInternational;
                        }
                        if ($Parcel->ErrorCode == 'PSR_OK' || ($RegisterParcelContainerRequest->OnlyLabelPrinting && $Parcel->ErrorCode == 'PSR_OK_PACKAGE_LABEL_PRINTING')) {
                            $retContainer[$key]['status']='OK';
                            $retContainer[$key]['message']='';
                            $retContainer[$key]['barcode']=$Parcel->NewBarCode;
                            $barcodeList[]=$Parcel->NewBarCode;
                            $Barcodes[] = new BarcodeData($Parcel->NewBarCode);
                        }
                        else {
                            $retContainer[$key]['status']='NOK';
                            $retContainer[$key]['message']= __('Hiba a csomag létrehozásakor:') . ' ' . $Parcel->ErrorCode;
                        }
                        break;
                    }
                }
            }
        }
        else {
            foreach ($retContainer as $retItem) {
                if ($retItem['status']=='') $retItem['status']='OK';
            }
        }

        if ($params['dok']) {
            $retContainer = PudoGetDocuments($retContainer,$params);
        }
    }
    catch (Exception $ex) {
        $retContainer[0]["status"]="NOK";
        $retContainer[0]["message"]="Exception occurred: " . print_r($ex, true);
        $retContainer[0]["shipment"]="";
        $retContainer[0]["barcode"]="";
        $$retContainer[0]["report"]="";
    }
    return $retContainer;
}


function PudoGetDocuments($retContainerIn, $params) {

    global $PudoDocumentServiceWSDL;
    global $soap_options;

    $doNothing = '<<do-nothing>>';
    $retContainer=$retContainerIn;

    $count = count($retContainer);

    if ($count > 0) {

        $Barcodes = array();
        foreach ($retContainer as $key => $x) {
            if (sprinter_isnullorempty($retContainer[$key]['barcode'])  && $retContainer[$key]['status']!='NOK') {
                if (!sprinter_isnullorempty($retContainer[$key]['orig_barcode'])) {
                    $retContainer[$key]['barcode']=$retContainer[$key]['orig_barcode'];
                    $retContainer[$key]['status']='OK';
                }
                else {
                    $retContainer[$key]['status']='NOK';
                    $retContainer[$key]['message']=__('Ehhez a megrendeléshez még nem készült fuvarlevél, ez a funkció nem használható');
                }
            }
            if (!sprinter_isnullorempty($retContainer[$key]['barcode'] && $retContainer[$key]['status']!='NOK')) {
                $Barcodes[]=new BarcodeData($retContainer[$key]['barcode']);
            }
        }

        $partnerContext = new PartnerContextData();
        $partnerContext->CultureInfo='hu-HU';
        $partnerContext->LanguageName='HU';

        $documentSetting = new DocumentSetting();
        $documentSetting->IsPositioned = false;
        // TODO: if rész, beállítások alapján:
        if(get_option('sprinter_cimke_meret')=='a4'){
            $documentSetting->Size = LabelDocumentSize::$DS_2x2;
        }
        elseif(get_option('sprinter_cimke_meret')=='a6'){
            $documentSetting->Size = LabelDocumentSize::$DS_A6;
        }
        $documentSetting->Type = DocumentType::$DT_All;
        if ($params['flv'] && !$params['atv']) $documentSetting->Type = DocumentType::$DT_PackageLabel;
        if (!$params['flv'] && $params['atv']) $documentSetting->Type = DocumentType::$DT_DeliveryNote;

        $DocumentSettings[] = $documentSetting;

        $MassDocumentRequest = new MassDocumentRequest();
        $MassDocumentRequest->Barcodes = $Barcodes;
        $MassDocumentRequest->DocumentSettings = $DocumentSettings; 
        $MassDocumentRequest->PartnerContext=$partnerContext;

        $DocumentClient = new DocumentService($PudoDocumentServiceWSDL, $soap_options);
        $retContainer[0]["apirequest"].=json_encode($MassDocumentRequest,JSON_PRETTY_PRINT);

        $MassDocumentResponse = $DocumentClient->GetDocument($MassDocumentRequest);
        $retContainer[0]["apiresponse"].=json_encode($MassDocumentResponse,JSON_PRETTY_PRINT);

        if ($DocumentClient == null || $MassDocumentResponse == null) {
            $retContainter[0]["status"]="NOK";
            $retContainer[0]["message"]= __("Nem érkezett válaszüzenet a Sprinter/PPP rendszerétől");
            return $retContainer;
        }

        $docCount=0;
        switch ($MassDocumentResponse->GetDocumentResult->Result) {
            case 'RES_OK':{
                    foreach ($MassDocumentResponse->GetDocumentResult->Documents->DocumentData as $docResponse) {
                        switch ($docResponse->Type)
                        {
                            case DocumentType::$DT_PackageLabel:
                                if ($params['flv']) {
                                    if ($count==1) {
                                        $filename=$retContainer[0]['barcode'];
                                    }
                                    else {
                                        $filename='fuvarlevelek_' . strtotime( date( 'Y-m-d H:i:s' )) ;
                                    }
                                    foreach ($retContainer as $key => $val) {
                                        $retContainer[$key]['barcode_file']=$filename;
                                        $retContainer[$key]['status']='OK';
                                    }
                                    $filename .= '.pdf';
                                }
                                else {
                                    $filename=$doNothing;
                                }
                                break;
                            case DocumentType::$DT_DeliveryNote:
                                if ($params['atv']) {
                                    $shp = 'atveteli_elismerveny_' . strtotime( date( 'Y-m-d H:i:s' )) . '_' . $docCount;
                                    $filename= $shp . ".pdf";
                                    $retContainer[$docCount]['shipment']=$shp;
                                    if ($docCount==0) {
                                        foreach ($retContainer as $key => $val) {
                                            $retContainer[$key]['shipment']=$shp;
                                            $retContainer[$key]['status']='OK';
                                        }
                                    }
                                    $docCount++;
                                }
                                else {
                                    $filename=$doNothing;
                                }
                                break;
                            default:
                                $filename=$docResponse->DocumentName . "pdf";
                                break;
                        }
                        if ($filename != $doNothing) {
                            $PrintDoc = fopen(sprinter_get_folder() . $filename, "w");
                            fwrite($PrintDoc, $docResponse->Document);
                            fclose($PrintDoc);
                        }
                    }
                    break;
                }
            default: {
                    $retContainer[0]["status"]="NOK";
                    $retContainer[0]["message"] .= $MassDocumentResponse->GetDocumentResult->Result;
                    break;
                }
        }
    }
    return $retContainer;
}

