<?php

final class PhabricatorProjectLogicalAllDatasource
  extends PhabricatorTypeaheadCompositeDatasource {

  public function getBrowseTitle() {
    return pht('Browse Projects');
  }

  public function getPlaceholderText() {
    return pht('Type a project name or function...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhabricatorProjectApplication';
  }

  public function getComponentDatasources() {
    return array(
      new PhabricatorProjectLogicalSearchAllDatasource(),
    );
  }

}
