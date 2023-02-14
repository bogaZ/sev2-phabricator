<?php

final class TeachableFuture extends FutureProxy {

  private $future;
  private $accessToken;
  private $action;
  private $params;
  private $method = 'GET';

  public function __construct() {
    parent::__construct(null);
  }

  public function setAccessToken($token) {
    $this->accessToken = $token;
    return $this;
  }

  public function setRawTeachableQuery($action, array $params = array()) {
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
        throw new Exception(pht('You must %s!', 'setRawTeachableQuery()'));
      }

      $config = id(new TeachableConfigurationQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->executeOne();

      if (!$config) {
        throw new Exception(pht('You must %s!',
          'set teachable configuration proxy first'));
      }

      $uri = new PhutilURI($config->getUrl());
      $uri->setPath('/api/v1/'.$this->action.'/');

      $future = new HTTPSFuture($uri);
      $future->setData($this->params);
      $future->setMethod($this->method);

      // basic authorization
      $basic_token = base64_encode(pht('%s:%s',
          $config->getEmail(),
          $config->getPassword()));
      $future->addHeader('Authorization', pht('Basic %s', $basic_token));
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
        pht('Expected JSON response from Teachable.'),
        $ex);
    }

    if (idx($data, 'error')) {
      $error = $data['error'];
      throw new Exception(pht('Received error from Teachable: %s', $error));
    }

    return $data;
  }

}
