<?php

/*
Copied pretty much exactly from:
http://shikii.net/blog/creating-a-custom-image-service-for-twitter-for-iphone/
*/


class TwitterOAuthEcho
{
  public $verificationUrl = 'https://api.twitter.com/1/account/verify_credentials.json';
  public $userAgent = __CLASS__;

  public $verificationCredentials;

  /**
   *
   * @var int
   */
  public $resultHttpCode;
  /**
   *
   * @var array
   */
  public $resultHttpInfo;
  public $responseText;

  /**
   * Save the OAuth credentials sent by the Consumer (e.g. Twitter for iPhone, Twitterrific)
   */
  public function setCredentialsFromRequestHeaders()
  {    
    $this->verificationCredentials = isset($_SERVER['HTTP_X_VERIFY_CREDENTIALS_AUTHORIZATION']) 
      ? stripslashes($_SERVER['HTTP_X_VERIFY_CREDENTIALS_AUTHORIZATION']) : '';
  }

  /**
   * Verify the given OAuth credentials with Twitter
   * @return boolean
   */
  public function verify()
  {
    $curl = curl_init($this->verificationUrl);
    curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: ' . $this->verificationCredentials,
      ));

    $this->responseText = curl_exec($curl);
    $this->resultHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $this->resultHttpInfo = curl_getinfo($curl);
    curl_close($curl);

    return $this->resultHttpCode == 200;
  }
}

?>