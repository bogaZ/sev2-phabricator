<?php

final class PhabricatorProjectsRspSpecSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('Project RSP Spec');
  }

  public function getAttachmentDescription() {
    return pht('Get the RSP spec of the project.');
  }

  public function willLoadAttachmentData($query, $spec) {
    $query->needRspSpec(true);
  }

  public function getAttachmentForObject($object, $data, $spec) {
    $rsp_spec = null;
    if ($object->hasRspSpec()) {
      $found_spec = $object->getRspSpec();
      $course_path = id(new CoursepathItem())
        ->loadOneWhere('phid = %s',
                       $found_spec->getCoursepathItemPHID());
      $rsp_spec = array(
        'required_course_path' => array(
          'id' => $course_path->getPHID(),
          'name' => $course_path->getName(),
          'stack' => $object->getRspSpec()->getStack(),
        ),
        'story_point' => array(
          'currency' => $found_spec->getStoryPointCurrency(),
          'value' => $found_spec->getStoryPointValue(),
        ),
      );
    }
    return $rsp_spec;
  }

}
