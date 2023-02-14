<?php

final class SpaceConduitListAPIMethod
  extends SpaceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'space.list';
  }

  public function getMethodDescription() {
    return pht('Get List Spaces.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getViewer();
    $results = array();

    $spaces = id(new PhabricatorSpacesNamespaceQuery())
        ->setViewer($viewer)
        ->execute();

    if (!empty($spaces)) {
      foreach ($spaces as $space) {
        $result['id'] = $space->getID();
        $result['phid'] = $space->getPHID();
        $result['name'] = $space->getNamespaceName();
        $result['description'] = $space->getDescription();
        $result['default'] = $space->getIsDefaultNamespace();

        $results[] = $result;
      }

      return array(
        'data' => $results
      );
    } else {
      return array(
        'message' => "You don't have any spaces in this workspace",
        'error' => false
      );
    }
  }
}