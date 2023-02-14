<?php

final class PhabricatorProjectsSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Projects');
  }

  public function getAttachmentDescription() {
    return pht('Get information about projects.');
  }

  public function loadAttachmentData(array $objects, $spec) {
    $object_phids = mpull($objects, 'getPHID');

    $projects_query = id(new PhabricatorEdgeQuery())
      ->withSourcePHIDs($object_phids)
      ->withEdgeTypes(
        array(
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
        ));
    $projects_query->execute();

    return array(
      'projects.query' => $projects_query,
    );
  }

  public function getAttachmentForObject($object, $data, $spec) {
    $projects = array();
    $projects_query = $data['projects.query'];
    $object_phid = $object->getPHID();

    $project_id = '';
    $project_phid = '';
    $project_name = '';
    $project_icon = 'project';
    $project_color = 'blue';
    $project_profile_image_uri = '';
    $project_parent = null;

    $project_phids = $projects_query->getDestinationPHIDs(
      array($object_phid),
      array(PhabricatorProjectObjectHasProjectEdgeType::EDGECONST));

    if (!empty($project_phids)) {
      $projects = id(new PhabricatorProjectQuery())
        ->setViewer($this->getViewer())
        ->withPHIDs($project_phids)
        ->needImages(true)
        ->execute();
    }

    if ($projects) {
      $project_only = array_filter(array_values($projects), function ($item) {
        return $item->getDisplayIconKey() == 'project';
      });

      $current_project = current($project_only);

      if ($current_project) {
        $project_id = $current_project->getID();
        $project_phid = $current_project->getPHID();
        $project_name = $current_project->getName();
        $project_icon = $current_project->getIcon();
        $project_color = $current_project->getColor();
        $project_profile_image_uri = $current_project->getProfileImageURI();

        if ($current_project->getParentProject()) {
          $project_parent = array(
            'phid' => $current_project->getParentProject()->getPHID(),
            'id' => $current_project->getParentProject()->getID(),
            'name' => $current_project->getParentProject()->getName(),
          );
        }
      }
    }

    return array(
      'id'              => $project_id,
      'phid'            => $project_phid,
      'name'            => $project_name,
      'icon'            => $project_icon,
      'color'           => $project_color,
      'profileImageUri' => $project_profile_image_uri,
      'parent'          => $project_parent,
      'projectPHIDs'    => array_values($project_phids),
      'projectPHID'     => array_values($project_phids),
      'tags'            => array_map(function ($proj) {

        $proj_parent = null;
        if ($proj->getParentProject()) {
          $proj_parent = array(
            'id'   => $proj->getParentProject()->getID(),
            'phid' => $proj->getParentProject()->getPHID(),
            'name' => $proj->getParentProject()->getName(),
          );
        }

        return [
          'id'              => $proj->getID(),
          'phid'            => $proj->getPHID(),
          'name'            => $proj->getName(),
          'icon'            => $proj->getIcon(),
          'color'           => $proj->getColor(),
          'profileImageUri' => $proj->getProfileImageURI(),
          'parent'          => $proj_parent,
        ];
      }, $projects),
    );
  }

}
