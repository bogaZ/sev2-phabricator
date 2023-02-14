<?php

final class StatusSite extends PhabricatorSite {

  public function getDescription() {
    return pht('Serves readiness/liveness probe.');
  }

  public function shouldRequireHTTPS() {
    return false;
  }

  public function getPriority() {
    return 100;
  }

  public function newSiteForRequest(AphrontRequest $request) {
    $host = $request->getHost();
    $path = $request->getPath();

    return strpos($path, '/status') === false
          ? null
          : id(new self());
  }

  public function new404Controller(AphrontRequest $request) {
    return new PhameBlog404Controller();
  }

  public function getRoutingMaps() {
    $app = PhabricatorApplication::getByClass('PhabricatorSystemApplication');

    $maps = array();
    $maps[] = $this->newRoutingMap()
      ->setApplication($app)
      ->setRoutes($app->getRoutes());
    return $maps;
  }

}
