<?php

final class FileUploadConduitAPIMethod extends FileConduitAPIMethod {

  public function getAPIMethodName() {
    return 'file.upload';
  }

  public function getMethodDescription() {
    return pht('Upload a file to the server.');
  }

  protected function defineParamTypes() {
    return array(
      'data_base64' => 'required nonempty base64-bytes',
      'name' => 'optional string',
      'viewPolicy' => 'optional valid policy string or <phid>',
      'canCDN' => 'optional bool',
      'conpherencePHID' => 'optional string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty guid';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $name = $request->getValue('name');
    $can_cdn = (bool)$request->getValue('canCDN');
    $view_policy = $request->getValue('viewPolicy');

    $data = $request->getValue('data_base64');
    $data = $this->decodeBase64($data);

    $params = array(
      'authorPHID' => $viewer->getPHID(),
      'canCDN' => $can_cdn,
      'isExplicitUpload' => true,
    );

    if ($name !== null) {
      $params['name'] = $name;
    }

    if ($view_policy !== null) {
      $params['viewPolicy'] = $view_policy;
    }

    $file = PhabricatorFile::newFromFileData($data, $params);

    $this->attachConpherence($request, $file);

    return $file->getPHID();
  }

  protected function attachConpherence($request, $file)
  {
    $viewer = $request->getViewer();
    $conpherence_phid = $request->getValue('conpherencePHID');
    if ($conpherence_phid) {
      $key = ConpherenceThreadHasFileRelationship::RELATIONSHIPKEY;
      $objects = id(new PhabricatorFileQuery())
                      ->setViewer($viewer)
                      ->withIDs(array($file->getID()))
                      ->execute();
      $dst_objects = mpull($objects, null, 'getPHID');
      $add_phids = mpull($dst_objects, 'getPHID');

      $object = $this->loadRelationshipObject($viewer, $conpherence_phid);
      $relationship = $this->loadRelationship($viewer, $object, $key);
      $edge_type = $relationship->getEdgeConstant();

      $content_source = PhabricatorContentSource::newForSource(
        SuiteContentSource::SOURCECONST);
      $relationship->setContentSource($content_source);

      $editor = $object->getApplicationTransactionEditor()
        ->setActor($viewer)
        ->setContentSource($content_source)
        ->setContinueOnMissingFields(true)
        ->setContinueOnNoEffect(true);

      $xactions = array();
      $xactions[] = $object->getApplicationTransactionTemplate()
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', $edge_type)
        ->setNewValue(array(
          '+' => array_fuse($add_phids),
        ));

      $add_objects = array_select_keys($dst_objects, $add_phids);

      if ($add_objects) {
        $more_xactions = $relationship->willUpdateRelationships(
          $object,
          $add_objects,
          array());
        foreach ($more_xactions as $xaction) {
          $xactions[] = $xaction;
        }
      }

      $editor->applyTransactions($object, $xactions);

      if ($add_objects) {
        $relationship->didUpdateRelationships(
          $object,
          $add_objects,
          array());
      }
    }
  }

  protected function loadRelationshipObject($viewer, $phid) {
    return id(new PhabricatorObjectQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
  }

  protected function loadRelationship($viewer, $object, $key) {
    $list = PhabricatorObjectRelationshipList::newForObject(
      $viewer,
      $object);

    return $list->getRelationship($key);
  }

}
