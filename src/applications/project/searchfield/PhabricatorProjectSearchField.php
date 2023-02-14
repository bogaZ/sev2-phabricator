<?php

final class PhabricatorProjectSearchField
  extends PhabricatorSearchTokenizerField {

  private $edgeType;
  protected function getDefaultValue() {
    return array();
  }

  public function setEdgeType($edge_types) {
    $this->edgeType = $edge_types;
    return ($this);
  }

  protected function newDatasource() {
    if ($this->edgeType == null) {
       return new PhabricatorProjectLogicalDatasource();
      //  Using excluded edgeType to separate from main tag field
    } else if ($this->edgeType === 'excluded') {
      return new PhabricatorProjectExcludeLogicalDatasource();
    } else if ($this->edgeType === 'all_project') {
      // This created to get all phid of project without using anything
      // relationship on project
      return new PhabricatorProjectLogicalAllDatasource();
    }
  }

  protected function getValueFromRequest(AphrontRequest $request, $key) {
    $list = $this->getListFromRequest($request, $key);
    $phids = array();
    $slugs = array();
    $project_type = PhabricatorProjectProjectPHIDType::TYPECONST;
    foreach ($list as $item) {
      $type = phid_get_type($item);
      if ($type == $project_type) {
        $phids[] = $item;
      } else {
        if (PhabricatorTypeaheadDatasource::isFunctionToken($item)) {
          // If this is a function, pass it through unchanged; we'll evaluate
          // it later.
          $phids[] = $item;
        } else {
          $slugs[] = $item;
        }
      }
    }

    if ($slugs) {
      $projects = id(new PhabricatorProjectQuery())
        ->setViewer($this->getViewer())
        ->withSlugs($slugs)
        ->execute();
      foreach ($projects as $project) {
        $phids[] = $project->getPHID();
      }
      $phids = array_unique($phids);
    }
    return $phids;

  }

  protected function newConduitParameterType() {
    return new ConduitProjectListParameterType();
  }

}
