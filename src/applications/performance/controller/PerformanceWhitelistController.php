<?php

final class PerformanceWhitelistController
  extends PerformanceController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $action = $request->getURIData('action');

    $via = $request->getStr('via');
    if ($via == 'pip') {
      $done_uri = "/performance/pip/";
    } else {
      $done_uri = "/performance";
    }


    if ($request->isDialogFormPost()) {
      $phids = $request->getArr('targetPHIDs');
      $selected_users = id(new PhabricatorPeopleQuery())
        ->setViewer($viewer)
        ->withPHIDs($phids)
        ->execute();
      $selected_ids = mpull($selected_users, null, 'getPHID');

      $whitelist = id(new PerformanceWhitelistQuery())
                        ->setViewer(PhabricatorUser::getOmnipotentUser())
                        ->execute();
      $all_whitelist = mpull($whitelist, null, 'getTargetPHID');

      foreach($phids as $phid) {
        $user = $selected_ids[$phid];

        $current_whitelist = null;
        if (array_key_exists($phid, $all_whitelist)) {
          $current_whitelist = $all_whitelist[$phid];
        }

        if (!$current_whitelist) {
          // Create!
          PerformanceWhitelist::addNewTarget($viewer, $user,
          PhabricatorContentSource::newFromRequest($request));
        } else {
          $xactions = array();
          $xactions[] = id(new PerformanceWhitelistTransaction())
            ->setTransactionType(
              PerformanceWhitelistIsActiveTransaction::TRANSACTIONTYPE)
            ->setNewValue(true);

          id(new PerformanceWhitelistEditor())
            ->setActor($viewer)
            ->setActingAsPHID($viewer->getPHID())
            ->setContentSourceFromRequest($request)
            ->setContinueOnMissingFields(true)
            ->setContinueOnNoEffect(true)
            ->applyTransactions($current_whitelist, $xactions);
        }

        unset($all_whitelist[$phid]);
      }

      if (!empty($all_whitelist)) {
        foreach($all_whitelist as $i => $removed) {

          $xactions = array();
          $xactions[] = id(new PerformanceWhitelistTransaction())
            ->setTransactionType(
              PerformanceWhitelistIsActiveTransaction::TRANSACTIONTYPE)
            ->setNewValue(false);

          id(new PerformanceWhitelistEditor())
            ->setActor($viewer)
            ->setActingAsPHID($viewer->getPHID())
            ->setContentSourceFromRequest($request)
            ->setContinueOnMissingFields(true)
            ->setContinueOnNoEffect(true)
            ->applyTransactions($removed, $xactions);
        }
      }

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $people_datasource = id(new PhabricatorPeopleDatasource());

    $all = id(new PerformanceWhitelistQuery())
            ->setViewer($viewer)
            ->withIsActive(true)
            ->execute();
    $whitelist_phid = mpull($all, 'getTargetPHID');
    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendControl(
        id(new AphrontFormTokenizerControl())
          ->setLabel(pht('Ammend User'))
          ->setName('targetPHIDs')
          ->setValue($whitelist_phid)
          ->setDatasource($people_datasource));

    $dialog = $this->newDialog()
      ->setTitle(pht('Whitelist'))
      ->addHiddenInput('via', $via)
      ->appendForm($form)
      ->addCancelButton($done_uri)
      ->addSubmitButton(pht('Save'));

    return $dialog;
  }

  protected function requiresManageCapability() {
    return $this->getViewer()->getIsAdmin() ? false : true;
  }

  protected function requiresViewCapability() {
    return $this->getViewer()->getIsAdmin() ? false : true;
  }
}
