<?php

/*
  VitalSource API Class

  VitalSource Authentication requires apikey, username and password. It uses 
  those to request an access_key. The access_key and apikey are then sent with
  every subsequent request. The access_key does expire, and when that happens a
  new access_key will have to be requested.

  It actually looks like access_keys and user/pass information is only for 
  making calls on behalf of a specific user.

*/


class vitalsource_api {

  // api authentication credentials
  protected $api_key;
  protected $api_user;
  protected $api_pass;
  protected $api_access_token;

  // API url
  protected $api_base_url = 'https://api.vitalsource.com/';
  protected $api_endpoint;

  // API Request
  protected $api_request_xml;
  protected $api_request_type;

  // API Response
  protected $api_response_raw;      // raw response
  protected $api_response;          // parsed, error checked, formatted response
  protected $api_response_format = "XML";
  protected $api_response_code;
  protected $api_response_errors = array();  // keyed by error code

  /**
   * 
   */
  function __construct($api_key) {
    $this->api_key = $api_key;
  }

  /**
   * Get XML request header
   */
  public function getXMLRequestHeader() {
    return 'X-VitalSource-API-Key: '.$this->api_key;
  }


  /**
   * Send an XML Request
   */
  public function sendRequest() {
    // get the request headers
    $xml_request_header = $this->getXMLRequestHeader();

    // get the api url
    $api_request_url = $this->api_base_url.$this->api_endpoint;

    // setup the cURL request
    $ch = curl_init();
    // set the request url and returntransfer
    curl_setopt($ch, CURLOPT_URL, $api_request_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // check request type and set options
    switch($this->api_request_type) {
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_HTTPGET, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->api_request_xml);
        break;
      case 'GET':
        //curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        break;
      case 'DELETE':
        //curl_setopt($ch, CURLOPT_POST, false);
        //curl_setopt($ch, CURLOPT_HTTPGET, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
    }

    // set the header
    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($xml_request_header));

    // store the raw response
    // @todo should this be in a try/catch block in case some other connection 
    // issue prevents the request from being made?
    $this->api_response_raw = curl_exec($ch);

    // store the http response code
    $this->api_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // close the curl connection
    curl_close($ch);
  }

  /**
   * Execute the current XML request
   */
  public function execRequest() {
    // clear any current response data
    $this->clearResponse();
    // send the request
    $this->sendRequest();
    // parse the response
    $this->parseResponse();
  }

  /**
   * Clear / wipe the current response data
   */
  protected function clearResponse() {
    $this->api_response = '';
    $this->api_response_raw = '';
    $this->api_response_code = '';
    $this->api_response_errors = array();
  }

  

  /**
   * Parse the response and check for any errors
   */
  public function parseResponse() {
    // first let's do an error check on the on the response code
    // https://developer.vitalsource.com/hc/en-us/articles/204286058-HTTP-Response-Codes
    switch($this->api_response_code) {
      case '200':
        // success, don't do anything
        break;
      case '403':
        $this->api_response_errors['http'] = "403: Permissions Denied";
        break;
      case '404':
        $this->api_response_errors['http'] = "404: Not Found";
        break;
      case '429':
        $this->api_response_errors['http'] = "429: Rate Limit Reached";
        break;
      default:
        break;
    }
    // if there is an HTTP error, then we won't have a response to parse
    if(!isset($this->api_response_errors['http'])) {
      // no http errors so continue to parse
      if($this->api_response_format == 'XML') {
        // parse the XML response and check for an error code
        $this->api_response = json_decode(json_encode(simplexml_load_string($this->api_response_raw)), TRUE);
        // if the response failed to parse, it's because it's blank. Set the
        // response to TRUE for now (some responses will be successful without
        // any actual return data)
        if(!$this->api_response) $this->api_response = TRUE;
        // check for an error code
        if(isset($this->api_response['error-code'])) {
          // store the error code and message in errors
          $error_code = $this->api_response['error-code'];
          $this->api_response_errors[$error_code] = $this->api_response['error-text'];
        }
      }
    }
    // check for any errors and set the API response to false if found
    if(!empty($this->api_response_errors)) $this->api_response = FALSE;
  }



  /* 
    GET METHODS
  */
  /**
   * Get the response off the API object
   */
  public function getResponse() {
     return $this->api_response;
  }
  /**
   * Get the raw response off the API object
   */
  public function getRawResponse() {
     return $this->api_response_raw;
  }
  /**
   * Get the errors off the API object
   */
  public function getErrors() {
     return $this->api_response_errors;
  }





  /* 
    API REQUESTS
  */

  /**
   * API REQUEST: POST v3/codes - Create
   * https://developer.vitalsource.com/hc/en-us/articles/205014207-POST-v3-codes-Create
   *
   * Create one or more redemption codes for specific VitalSource books with 
   * this call. These codes will be redeemed by user-specific accounts. Your 
   * company must have distribution rights to the products requested, or you 
   * will receive errors. Please note below we have documented the five ways you 
   * can define the license type. License MUST be specified for both the 
   * installed and online versions of Bookshelf. 
   *
   * Codes can be created against the following values:
   * - VBID/SKU
   * - eISBN
   * - FPID
   *
   */
  public function createCode($sku) {
    // set request specific parameters: url endpoint, http request type and 
    // response format
    $this->api_endpoint = "v3/codes";
    $this->api_request_type = 'POST';
    $this->api_response_format = "XML";
    // build and set the xml request
    $this->api_request_xml = '<?xml version=\"1.0\" encoding=\"UTF-8\"?>
      <codes sku="'.$sku.'" license-type="default" online-license-type="default" num-codes="1" tag="postman_created_code"/>';
    // execute the request and generally parse the response
    $this->execRequest();
  }

  /**
   * API REQUEST: GET v4/codes/:code - Read
   * https://developer.vitalsource.com/hc/en-us/articles/208441407-GET-v4-codes-code-Read
   *
   * Use this API to retrieve the status and metadata for an existing VitalSource redemption code.
   *
   * Only codes created by the company of the API keys will be returned.
   *
   */
  public function checkCode($code) {
    // set request specific parameters: url endpoint, http request type and 
    // response format
    $this->api_endpoint = "v4/codes/".$code;
    $this->api_request_type = 'GET';
    $this->api_response_format = "XML";
    // unset any request xml (not needed for this request)
    $this->api_request_xml = '';
    // execute the request and generally parse the response
    $this->execRequest();
  }

  /**
   * API REQUEST: DELETE v4/codes/:code - Delete/Cancel/Refund
   * https://developer.vitalsource.com/hc/en-us/articles/204317258-DELETE-v4-codes-code-Delete-Cancel-Refund
   *
   * This end-point is for canceling and issuing refunds for specific codes. 
   * There are a variety of reasons a code can and cannot be cancelled which can 
   * be reviewed at the bottom of the page. Specific error messaging is returned
   * depending on current codes' refund eligibility requirements.
   *
   */
  public function cancelCode($code) {
    // set request specific parameters: url endpoint, http request type and 
    // response format
    $this->api_endpoint = "v4/codes/".$code;
    $this->api_request_type = 'DELETE';
    $this->api_response_format = "XML";
    // unset any request xml (not needed for this request)
    $this->api_request_xml = '';
    // execute the request and generally parse the response
    $this->execRequest();
  }




}




?>