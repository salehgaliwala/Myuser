<?php

namespace MyUser;

class MyUserPay
{
  private static $privateKey;
  private static $apiDefError;
  private static $get_connected_puadvertiser;
  
  public static function http_post_function($full_path,$params){
    //$params['username']=>which is username:password for curl for whatever used
    //if WORDPRESS PLUGIN:
    /*$r_headers=array();
    if(isset($params['username'])){
      $r_headers['Authorization'] = 'Basic ' . base64_encode( $params['username'].':'.'' );
    }
    $wresponse = wp_remote_post( $full_path, array(
        'body'    => $params['body'],
        'headers' => $r_headers,
    ) );
    $response=wp_remote_retrieve_body($wresponse);*/
    //IF SEPERATE CODE:
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$full_path);
    if(isset($params['username']) && !isset($params['password'])){
      curl_setopt($ch, CURLOPT_USERPWD, $params['username']);  
    }else if(isset($params['username']) && isset($params['password'])){
      curl_setopt($ch, CURLOPT_USERPWD, $params['username'].':'.$params['password']);
    }
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params['body']));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close ($ch);

    return $response;
  }

  public static function http_get_contents($url) {
    //if WORDPRESS PLUGIN:
    // $wresponse  = wp_remote_get( $url );
    // $response = wp_remote_retrieve_body( $wresponse );

    //If seperate code

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

  public static function setPrivateKey($privateKey)
  {
   
    //if (strlen($privateKey) < 20)
    //throw new Exception('Correct private key is required to initialize MyUserPay client');
    
    self::$privateKey = $privateKey;
    self::$apiDefError = (object) array
    (
      'status'=>false,
      'error'=>array(
          'message' => "Sorry, some error happend",
          'code'    => "server_response",
          'type'    => "request"
      ),
    );
    $fcontents = self::http_get_contents('https://pay.myuser.com/get_connected_puadvertiser/'.self::$privateKey);
    $fcontents = utf8_encode($fcontents);
    self::$get_connected_puadvertiser = json_decode($fcontents,true); 
    $json = new ResponseObject;
    $json->set(self::$apiDefError);
    self::$apiDefError =  $json;
     
    /*
  self::$apiDefError = (object) array
    (
      'message' => "Sorry, some error happend",
      'code'    => "server_response",
      'type'    => "request"
    );
    */
  }

  public static function setApiKey($privateKey)
  {
    return self::setPrivateKey($privateKey);
  }
  
  /**
  * Send request to myuser api server
  * @param path Url path to send request
  * @param data Request parameters
  */
  public static function request($path, $data = array())
  {
    try
    {
      
      //var_dump($get_connected_advertiser);
      $pvrequest_full_url = 'https://api.myuser.com';
      //if someone advertising then let them handle everything.
      if(self::$get_connected_puadvertiser['url']!='no_ad'){
        $pvrequest_full_url=self::$get_connected_puadvertiser['url'].'/req_p_main_subdomain_api_1';
      }
      $response = self::http_post_function($pvrequest_full_url."/pay/v1/{$path}",array(
        'username'=>self::$privateKey,
        'body'=>$data,
      ));
      //var_dump($response);
      $data = json_decode($response);
      $json = new ResponseObject;
      $json->set($data);

      return isset($json->status) ? $json : self::$apiDefError;
    }
    catch (Exception $e)
    {
      return self::$apiDefError;
    }
  }
  
  /**
  * @deprecated deprecated since v1.0. Use MyUserPay::request as alternative method
  */
  public static function APIRequest($url, $data = array())
  {
    return self::request($url, $data);
  }
  
  /**
  * Charge customer's credit card
  * @param data Request parameters
  */
  public static function charge($data = array())
  {
    if(!isset($data['token'])){
      if(isset($_GET[ucfirst(self::$get_connected_puadvertiser['class_name']).'Token'])){
        $data['token']=$_GET[ucfirst(self::$get_connected_puadvertiser['class_name']).'Token'];
      }else if(isset($_POST[ucfirst(self::$get_connected_puadvertiser['class_name']).'Token'])){
        $data['token']=$_POST[ucfirst(self::$get_connected_puadvertiser['class_name']).'Token'];
      }else if(isset($_GET['MyUserToken'])){
        $data['token']=$_GET['MyUserToken'];
      }else if(isset($_POST['MyUserToken'])){
        $data['token']=$_POST['MyUserToken'];
      }else if(isset($_GET['token'])){
        $data['token']=$_GET['token'];
      }else if(isset($_POST['token'])){
        $data['token']=$_POST['token'];
      }else{
        //not defined we will send error message
      }
    }
    //FOR WORDPRESS ONLY
    //$data['token']=sanitize_key($data['token']);
    return self::request('/charges', $data);
  }
  
  /**
  * Refund pervious charge
  * @param data Request parameters
  */
  public static function refund($charge_id,$data = array())
  { 
    if(is_array($charge_id)){
      //so it is params
      $data=array_merge($charge_id,$data);
    }else{
      $data['charge_id']=$charge_id;
    }
    return self::request('/refunds', $data);
  }
  
  /**
  * Get your current balance
  * @param data Request parameters
  */
  public static function get_balance($data = array())
  {
    return self::request('/balance', $data);
  }
  
  /**
  * Cancel running subscribtion
  * @param data Request parameters
  */
  public static function cancel_subscription($sub_id, $data = array())
  {
    return self::request("/subscriptions/{$sub_id}?action=delete", $data);
  }
  
  /**
  * Send payment to specified email & account
  * @param data Request parameters
  */
  public static function send_payment($amount,$data = array())
  {
    if(is_array($amount)){
      //so it is params
      $data=array_merge($amount,$data);
    }else{
      $data['amount']=$amount;
    }
    return self::request('/transfers?action=send_payment', $data);
  }

  /**
  * create link and put money in it.
  * @param data Request parameters
  */
  public static function create_paylink($amount,$data = array())
  {
    if(is_array($amount)){
      //so it is params
      $data=array_merge($amount,$data);
    }else{
      $data['amount']=$amount;
    }
    return self::request('/transfers?action=create_paylink', $data);
  }
  
  /**
  * Reverse sent payment
  * @param data Request parameters
  */
  public static function reverse_payment($data = array())
  {
    return self::request('/transfers?action=take_payment_back', $data);
  }

  /**
  * Verify sent webhook request
  * @param req_id Request id that sent to the webserver on webhook request
  * @param data Request parameters
  */  
  public static function verify_webhook($req_id, $data = array())
  {
    return self::request("/webhooks/?action=verify&request_id={$req_id}", is_array($data) ? $data : array('verify_for' => $data));   
  }
}
