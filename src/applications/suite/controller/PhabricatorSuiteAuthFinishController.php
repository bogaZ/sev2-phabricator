<?php

final class PhabricatorSuiteAuthFinishController extends PhabricatorController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {

    $next = PhabricatorCookies::getSuiteNextURICookie($request);

    $request->clearCookie(PhabricatorCookies::COOKIE_SUITE);
    $request->clearCookie(PhabricatorCookies::COOKIE_SUITE_NEXTURI);
    $request->clearCookie(PhabricatorCookies::COOKIE_NEXTURI);
    $request->clearCookie(PhabricatorCookies::COOKIE_HISEC);

    if (!$next) {
      // If there is no next URI to get back to the suite app,
      // we don't know what to do except
      // asking user to reload their suite
      // TODO : raise a slack to #feed-status ?
      $error_view = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_ERROR)
        ->setErrors(array(
        pht(
          'Something went wrong, please restart this Suite.'),
        ));

      return $this->newSuitePage()
        ->setTitle('Something went wrong')
        ->appendChild($error_view);
    }

    // Auto-generate token
    $viewer = $request->getViewer();
    $tokens = id(new PhabricatorConduitTokenQuery())
        ->setViewer($viewer)
        ->withObjectPHIDs(array($viewer->getPHID()))
        ->withExpired(false)
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->execute();

    $current_token = null;
    if (count($tokens) == 0) {
      // create token if there is no token yet
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $current_token = PhabricatorConduitToken::initializeNewToken(
          $viewer->getPHID(),
          PhabricatorConduitToken::TYPE_STANDARD);
        $current_token->save();
      unset($unguarded);
    } else {
      // use current token
      $current_token = current($tokens);
    }

    // Send base api uri and the token, back to our suite
    $host = $request->getHost();

    // If there's no base domain configured, just use whatever the request
    // domain is. This makes setup easier, and we'll tell administrators to
    // configure a base domain during the setup process.
    $base_uri = PhabricatorEnv::getEnvConfig('phabricator.base-uri');
    if (!strlen($base_uri)) {
      return new PhutilURI('http://'.$host.'/');
    }

    $payload = json_encode(array(
      'base_api_uri' => rtrim($base_uri,'/').'/api',
      'token' => $current_token->getToken(),
    ));

    return id(new AphrontRedirectResponse())
      ->setIsExternal(true)
      ->setIsSuite(true)
      ->setURI(rtrim($next, '/').'/'.base64_encode($payload));
  }
}
