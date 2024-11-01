<?php

/*
 * PartnerPudoService - RegisterParcelContainer
 * Description in PUDO Partner Interface document.
 */
 
class RegisterParcelContainerRequest { 
	public function __construct()
	{
		$this->Supplier = Supplier::$Partner;
	}
    public $ArriveDate;			    //dateTime  
    public $ParcelContainer;		//List<Parcel>  
    public $PartnerAddress;			//string  
    public $PartnerCode;			//string 
    protected $Supplier;			//Supplier 
    public $SupplimentJSONData;		//string 
    public $Token;					//string
	public $OnlyLabelPrinting;		//boolean
	public $FinalizeLabelPrinting;	//boolean
}

class Parcel  {  
	public function __construct()
	{
		$this->CustomerType = CustomerType::$B2C;
		// Parcel creation status, we only handle Create
		$this->ParcelCreationStatus = ParcelCreationStatus::$Create;
		$this->ParcelCount = 1;	//Default 1
	}

	public $AccountingDeadlineDate; 			//dateTime 
	public $AutorizationCode;					//string 
	public $BarCode;							//string
	public $ContactName;						//string 
	public $CustomerAddress;					//string 
	public $CustomerCity;						//string 
	public $CustomerCode;						//string 
	public $CustomerCountryCode;				//string 
	public $CustomerEmail;						//string 
	public $CustomerName;						//string
	public $CustomerPhone;						//string
	public $CustomerPostalCode;					//string
	public $CustomerRegion;						//string
	public $CustomerStreetNumber;		    	//string
	protected $CustomerType;					//CustomerType
	public $DeliveryPrice;						//decimal
	public $DeliveryPriceCurrency;				//string
	public $DeliveryType;						//ParcelDeliveryType
	public $Description;						//string
	public $DestinationLocationId;				//string
	public $DirectPackageBarCode;				//string
	public $InvoiceNumber;						//string
	public $IsPartnerInvoiced;					//boolean
	public $PackagePrice;						//decimal
	public $PackagePriceCurrency;				//string
	public $PackageSizeX;						//decimal
	public $PackageSizeY;						//decimal
	public $PackageSizeZ;						//decimal
	public $PackageType;
	public $PackageVolume;						//decimal
	public $PackageWeight;						//decimal
	public $ParcelContentDescription1;			//string
	public $ParcelContentDescription2;			//string
	public $ParcelCount;						//int
	protected $ParcelCreationStatus; 				//ParcelCreationStatus;
	public $PickupCustomerName;					//string
	public $PickupLocationId;					//string
	public $PickupNotificationEmailAddress; 	//string
	public $PickupNotificationPhone;			//string
	public $PriceAtDelivery;					//decimal
	public $PriceAtDeliveryCurrency;			//string
	public $ReferenceBarCode;					//string
	public $RegisterDate;						//dateTime
	public $ReturnOfDocument;					//boolean
	public $ServiceType;						//ParcelServiceType
	public $SupplimentJSONData;					//string
	public $Tracking;							//boolean
	public $TransitTime;						//int
}

class ParcelResult {
  public $DirectionNumber;
  public $DirectionNumberSender;
  public $ErrorCode;
  public $IsDelayedDelivery;
  public $NewBarCode;
  public $NewDirectPackageBarCode;
  public $OriginalBarCode;
  public $OriginalDirectPackageBarCode;
  public $ShipmentID;
}


class RegisterParcelContainerResult {
  public $ErrorCode;
  public $ParcelResults;
}

class RegisterParcelContainerResponse {
  public $ErrorCode;
  public $ParcelResults;
  public $RegisterParcelContainerResult;
}

/// Additional Package Data
class PackageSupplimentData
{
    public $InternationalData;// InternationalData { get; set; }
}

class InternationalData
{
    public $DeliveryPartner; //string { get; set; }
	public $FanCourierData;// FanCourierData { get; set; }
}

class FanCourierData
{
    public $LabelSize; // LabelSize { get; set; }
    public $IsDocument; // { get; set; }
}

/** enum  */
class CustomerType 
{ //enum
	public static $B2C = "B2C";	//Business-to-Consumer
}

class ParcelServiceType
{ //enum
	public static $Normal = "Normal";
    public static $Direct = "Direct";
    public static $Pos2Pos = "Pos2Pos";
    public static $Return = "Return";
	public static $HomeDeliver = "HomeDeliver";
}

class Supplier
{ //enum
	public static $Partner = "Partner";
}

class ParcelDeliveryType
{ //enum
    public static $DeliveryAndReturns = "DeliveryAndReturns";
    public static $OnlyDelivery ="OnlyDelivery";     
    public static $OnlyReturns = "OnlyReturns";
}

class ParcelCreationStatus
{ //enum
	public static $Create = "Create";
}

class PackageType
{
    public static $Small = "Small";
    public static $Medium = "Medium";
    public static $Large = "Large";
    public static $Special = "Special";
    public static $None = "None";
}

//FanCourier label size
class LabelSize
{
    /// <summary>
    /// Only A6
    /// </summary>
    public static $A6 = 2;
}


class PartnerPudoService extends SoapClient {

  private static $classmap = array(
	'Parcel' => 'Parcel',
	'ParcelResult' => 'ParcelResult',
	'PackageSupplimentData' => 'PackageSupplimentData',
	'InternationalData' => 'InternationalData',
	'FanCourierData'=>'FanCourierData',
	'RegisterParcelContainerRequest' => 'RegisterParcelContainerRequest',
	'RegisterParcelContainerResponse' => 'RegisterParcelContainerResponse',
	'RegisterParcelContainerResult' => 'RegisterParcelContainerResult',
	);

  public function __construct($wsdl, $options = array()) {
    foreach(self::$classmap as $key => $value) {
      if(!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }
    parent::__construct($wsdl, $options);
  }

  public function RegisterParcelContainer(RegisterParcelContainerRequest $request) {
    return $this->__soapCall('RegisterParcelContainer', 
		array('parameters' => array('request' => $request)),
		array(
            'uri' => 'http://Lapker.Pudo.PudoService.Interface.PartnerPudo',
            'soapaction' => ''
           )
      );
  }
 
}

?>
