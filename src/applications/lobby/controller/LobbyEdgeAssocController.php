<?php

final class LobbyEdgeAssocController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    if ($request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $user = $request->getUser();
    $edgetype = $request->getURIData('edgetype');
    $conpherence_id = $request->getURIData('room_id');
    $object_id = $request->getURIData('id');

    $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer($user)
        ->withIDs(array($conpherence_id))
        ->needProfileImage(true)
        ->execute();

    $current_thread = head($conpherences);

    if (!$current_thread) {
      return new Aphront404Response();
    }

    $dst_objects = array();
    $key = null;
    switch ($edgetype) {
      case 'stickit':
        $key = ConpherenceThreadHasStickitRelationship::RELATIONSHIPKEY;
        $objects = id(new LobbyStickitQuery())
                        ->setViewer($user)
                        ->withIDs(array($object_id))
                        ->execute();
        $dst_objects = mpull($objects, null, 'getPHID');
        $add_phids = mpull($dst_objects, 'getPHID');
        break;
      case 'tasks':
        $key = ConpherenceThreadHasTaskRelationship::RELATIONSHIPKEY;
        $objects = id(new ManiphestTaskQuery())
                        ->setViewer($user)
                        ->withIDs(array($object_id))
                        ->execute();
        $dst_objects = mpull($objects, null, 'getPHID');
        $add_phids = mpull($dst_objects, 'getPHID');
        break;
      case 'files':
        $key = ConpherenceThreadHasFileRelationship::RELATIONSHIPKEY;
        $objects = id(new PhabricatorFileQuery())
                        ->setViewer($user)
                        ->withIDs(array($object_id))
                        ->execute();
        $dst_objects = mpull($objects, null, 'getPHID');
        $add_phids = mpull($dst_objects, 'getPHID');
        break;
    case 'calendar':
        $key = ConpherenceThreadHasCalendarRelationship::RELATIONSHIPKEY;
        $objects = id(new PhabricatorCalendarEventQuery())
                        ->setViewer($user)
                        ->withIDs(array($object_id))
                        ->execute();
        $dst_objects = mpull($objects, null, 'getPHID');
        $add_phids = mpull($dst_objects, 'getPHID');
        break;
    case 'goals':
        $key = ConpherenceThreadHasGoalsRelationship::RELATIONSHIPKEY;
        $objects = id(new LobbyStickitQuery())
                        ->setViewer($user)
                        ->withIDs(array($object_id))
                        ->execute();
        $dst_objects = mpull($objects, null, 'getPHID');
        $add_phids = mpull($dst_objects, 'getPHID');
        break;
    }

    if (!$key || empty($dst_objects)) {
      return new Aphront404Response();
    }

    $object = $this->loadRelationshipObject($current_thread->getPHID());
    $relationship = $this->loadRelationship($object, $key);
    $edge_type = $relationship->getEdgeConstant();

    $done_uri = '/Z'.$conpherence_id;

    $content_source = PhabricatorContentSource::newFromRequest($request);
    $relationship->setContentSource($content_source);

    $editor = $object->getApplicationTransactionEditor()
      ->setActor($user)
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

    try {
      $editor->applyTransactions($object, $xactions);

      if ($add_objects) {
        $relationship->didUpdateRelationships(
          $object,
          $add_objects,
          array());
      }

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    } catch (PhabricatorEdgeCycleException $ex) {
      return $this->newGraphCycleResponse($ex, $done_uri);
    }
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }

  protected function loadRelationshipObject($phid) {
    $request = $this->getRequest();
    $viewer = $this->getViewer();

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

  protected function loadRelationship($object, $key) {
    $request = $this->getRequest();
    $viewer = $this->getViewer();

    $list = PhabricatorObjectRelationshipList::newForObject(
      $viewer,
      $object);

    return $list->getRelationship($key);
  }
}
