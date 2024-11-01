<?php

class MassDocumentRequest{
  public $PartnerContext;      //List<>
  public $Barcodes;				     //List<> BarcodeData
  public $DocumentSettings;		 //List<> DocumentSetting
  public $SupplimentJSONData;	 //string
}

class PartnerContextData {
  public $CultureInfo;
  public $LanguageName;
}

class BarcodeData  { 
  public function __construct($barcode){
  	$this->Barcode = $barcode;
  	$this->Type = "BT_PackageBarcode";
  }
  public $Barcode;				//string
  public $SupplimentJSONData;	//string
  public $Type;					//string BarcodeType 
}

class DocumentSetting  {  
  public function __construct()
  {
    $this->Format       = DocumentFormat::$DF_PDF;
  }
  protected $Format;				  //DocumentFormat
  public $IsPositioned;			  //boolean         ==> etiketre történő nyomtatás
  public $Position;				    //LabelDocumentPosition 
  public $Size;					      //LabelDocumentSize 
  public $SupplimentJSONData;	//string
  public $Type;					      //DocumentType 
}

class DocumentGenerationResult  {  
  public $DocumentName;			  //string 
  public $Result;				      //ResultCode
  public $ResultMessage;		  //string
  public $SupplimentJSONData;	//string
}

class DocumentData  {  
  public $Document;				    //base64Binary
  public $DocumentName;			  //string
  public $IsPositioned;			  //boolean
  public $SupplimentJSONData;	//string
  public $Type;					      //DocumentType
}

class MassDocumentResponse  {  
  public $DocumentGenerationResults;	//List<DocumentGenerationResult> 
  public $Documents;			            //List<DocumentData>
  public $SupplimentJSONData;	        //string
}

class DocumentResponseBase  {  
  public $Result;				      //DocumentResults
}

class GetDocument  {  
  public $request;				    //MassDocumentRequest
}

class GetDocumentResponse  {  
  public $GetDocumentResult;	//MassDocumentResponse
}


/** enum  */
class BarcodeType
{	//enum
    public static $BT_PackageBarcode = "BT_PackageBarcode"; //vonalkod tipusa
}
class DocumentFormat
{	//enum
	public static $DF_PDF = "DF_PDF";    // PDF
}
class LabelDocumentPosition
{
  //csomag cimke kezdo pozició A4-es lapon. Függ a LabelDocumentSize beállításától is. 
  //2x2 esetén P_3 a legnagyobb megadható kezdő pozició
  //2x4 esetén P_7 a legnagyobb megadható kezdő pozicio
                                      //     DS_2x2         DS_2x4
                                      //  ___________    ___________ 
    public static  $P_0 = "P_0";      // |     |     |  | P_0 | P_4 |
    public static  $P_1 = "P_1";      // | P_0 | P_2 |  |_____|_____|
    public static  $P_2 = "P_2";      // |     |     |  | P_1 | P_5 |
    public static  $P_3 = "P_3";      // |_____|_____|  |_____|_____|
    public static  $P_4 = "P_4";      // |     |     |  | P_2 | P_6 |
    public static  $P_5 = "P_5";      // | P_1 | P_3 |  |_____|_____|
    public static  $P_6 = "P_6";      // |     |     |  | P_3 | P_7 |
	  public static  $P_7 = "P_7";      // |_____|_____|  |_____|_____|
}
class LabelDocumentSize{
	public static $DS_2x2 = "DS_2x2";   // A4-lapra 4 csomag cimke
  public static $DS_2x4 = "DS_2x4";   // A4-lapra 8 csomag cimke
  public static $DS_A6 = "DS_A6";   // A4-lapra 8 csomag cimke
}  
class DocumentType{
	public static $DT_All            = "DT_All";
	public static $DT_PackageLabel   = "DT_PackageLabel";
  public static $DT_DeliveryNote   = "DT_DeliveryNote";
	public static $DT_ReceiptReport  = "DT_ReceiptReport";
}  

class DocumentService extends SoapClient {

  private static $classmap = array(
  'BarcodeData' => 'BarcodeData',
  'GetDocument' => 'GetDocument',
  'DocumentData' => 'DocumentData',
  'DocumentSetting' => 'DocumentSetting',
  'MassDocumentRequest' => 'MassDocumentRequest',
  'GetDocumentResponse' => 'GetDocumentResponse',
  'MassDocumentResponse' => 'MassDocumentResponse',
  'DocumentResponseBase' => 'DocumentResponseBase',
  'DocumentGenerationResult' => 'DocumentGenerationResult'
  );

  public function __construct($wsdl, $options = array()) {
    foreach(self::$classmap as $key => $value) {
      if(!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }
    parent::__construct($wsdl, $options);
  }



  public function GetDocument(MassDocumentRequest $request){
  	return $this->__soapCall("GetDocument",
  		array('parameters'=> array('request'=>$request)),
  		array(
  				'uri'=>'http://Lapker.Pudo.PudoService.Interface.Document',
  				'soapaction'=>'')
  	);
  }
}

?>