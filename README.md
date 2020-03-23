# VitalSource API Class

A class I made to interact with the VitalSource API: https://developer.vitalsource.com/hc/en-us/categories/360001974433

It currently only makes 3 calls: Create Code, Check Code Status, and Cancel/Refund Code. 

I did not include any API actions made on behalf of a specific user, as they were not necessary for my use-case -- they would require additional interaction to request an access token on behalf of the user, using their username and password.
  
Include and Construct API Class

    // include api class
    include_once('vitalsource_api.php');

    // setup vars for creds
    $api_key = "XXXXXXXXXXXXXXXX";

    // construct a new vitalsource_api object with the API key
    $vitalsource_api = new vitalsource_api($api_key);

Create Redemption Code

    // set test sku
    $test_sku = 'VCS-0074009900852';
    
    // create a redemption code for the test sku
    $vitalsource_api->createCode($test_sku);
    // check for a response before continuing
    if(!$vitalsource_api->getResponse()) {
      // no valid response found; check for errors
      var_dump($vitalsource_api->getErrors());
    }
    // pull the response (code status or error) off the api object
    $response = $vitalsource_api->getResponse();
    $code = $response['code'];

Check Status of Redemption Code

    // get the status of the code we just created in above creation example ($code)
    $vitalsource_api->checkCode($code);
    // check for a response before continuing
    if(!$vitalsource_api->getResponse()) {
      // no valid response found; check for errors
      var_dump($vitalsource_api->getErrors());
    }
    // pull the response (code status or error) off the api object
    $response = $vitalsource_api->getResponse();

Cancel/Refund Redemption Code

    // cancel/refund the code we just created in above creation example ($code)
    $vitalsource_api->cancelCode($code);
    // check for a response before continuing
    if(!$vitalsource_api->getResponse()) {
      // no valid response found; check for errors
      var_dump($vitalsource_api->getErrors());
    }
    // pull the response (code status or error) off the api object
    $response = $vitalsource_api->getResponse();

