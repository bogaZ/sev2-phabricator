<?php

final class PhabricatorAppleAuthProvider
  extends PhabricatorOAuth2AuthProvider {

  public function getProviderName() {
    return pht('Apple');
  }

  protected function getProviderConfigurationHelp() {
    $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

    return $login_uri;
  }

  protected function newOAuthAdapter() {
    return new PhutilAppleAuthAdapter();
  }

  protected function getLoginIcon() {
    return 'Apple';
  }

  public function getLoginURI() {
    return '/oauth/apple/login/';
  }

}
