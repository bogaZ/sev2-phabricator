<?php

final class ZapierFuture extends FutureProxy {

  private $future;
  private $accessToken;
  private $action;
  private $params;
  private $method = 'POST';

  const ZAPIER_URL = 'https://hooks.zapier.com';
  const USER_CREATE = 'osfxfcj';
  const USER_ENROLL = 'o84ht2v';
  const USER_UNENROLL = 'o84tm6j';
  const USER_CREATE_ENROLL = 'onimqxq';

  public function __construct() {
    parent::__construct(null);
  }

  public function setAccessToken($token) {
    $this->accessToken = $token;
    return $this;
  }

  public function setZapierRawQuery($action, array $params = array()) {
    $this->action = $action;
    $this->params = $params;
    return $this;
  }

  public function setMethod($method) {
    $this->method = $method;
    return $this;
  }

  protected function getProxiedFuture() {
    if (!$this->future) {
      $params = $this->params;

      if (!$this->action) {
        throw new Exception(pht('You must %s!', 'setZapierRawQuery()'));
      }

      $uri = new PhutilURI(self::ZAPIER_URL);
      $uri->setPath('/hooks/catch/7763917/'.$this->action.'/');

      $future = new HTTPSFuture($uri);
      $future->setData($this->params);
      $future->setMethod($this->method);

      $future->addHeader('Accept', 'application/json');

      $this->future = $future;
    }

    return $this->future;
  }

  protected function didReceiveResult($result) {
    list($status, $body, $headers) = $result;

    if ($status->isError()) {
      throw $status;
    }

    $data = null;
    try {
      $data = phutil_json_decode($body);
    } catch (PhutilJSONParserException $ex) {
      throw new PhutilProxyException(
        pht('Expected JSON response from Zapier.'),
        $ex);
    }

    if (idx($data, 'error')) {
      $error = $data['error'];
      throw new Exception(pht('Received error from Zapier: %s', $error));
    }

    return $data;
  }

}
