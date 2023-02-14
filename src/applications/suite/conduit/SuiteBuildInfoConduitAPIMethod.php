<?php

final class SuiteBuildInfoConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.build.info';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodDescription() {
    return pht('Get project build info.');
  }

  protected function defineParamTypes() {
    return array(
      'repositoryPHID' => 'string | required',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();
    $repository_phid = $request->getValue('repositoryPHID');

    if (!$repository_phid) {
      throw new ConduitException('ERR_REPOSITORY_NOT_FOUND');
    }

    $build = id(new PhabricatorRepositoryBuildInfoQuery())
      ->setViewer($viewer)
      ->withRepositoryPHIDs(array($repository_phid))
      ->executeOne();

    if (!$build) {
      throw new ConduitException('ERR_BUILD_INFO_NOT_FOUND');
    }

    $file_result = array();
    $file_results = array();

    $build_files = id(new PhabricatorRepositoryBuildInfoFileQuery())
      ->setViewer($viewer)
      ->withBuildPHIDs(array($build->getPHID()))
      ->execute();

    $file_phids = mpull($build_files, 'getFilePHID');

    if ($file_phids) {
      $files = id(new PhabricatorFileQuery())
        ->setViewer($viewer)
        ->withPHIDs($file_phids)
        ->execute();

      foreach ($files as $file) {
        $file_result['filename'] = $file->getName();
        $file_result['uri'] = $file->getCDNUri('data');
        $file_results[] = $file_result;
      }
    }

    $config = $build->getConfiguration();
    $config_array = json_decode($config, true);
    if (json_last_error() === JSON_ERROR_NONE) {
    // JSON is valid
    } else {
      $config_array = [];
    }

    $result = array(
      'configuration' => $config_array,
      'files' => $file_results,
    );

    return array(
      'data' => array_merge($result),
    );
  }
}
