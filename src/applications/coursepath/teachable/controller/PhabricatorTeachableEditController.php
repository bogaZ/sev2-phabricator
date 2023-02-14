<?php

final class PhabricatorTeachableEditController
  extends PhabricatorCoursepathController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    if ($id) {
      $config = id(new TeachableConfigurationQuery())
        ->setViewer($viewer)
        ->withIDs(array($id))
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->executeOne();
      if (!$config) {
        return new Aphront404Response();
      }
      $is_new = false;
    } else {
      $config = TeachableConfiguration::initializeNewConfig($viewer);
      $is_new = true;
    }

    $e_question = true;
    $e_response = true;
    $errors = array();

    $v_url = $config->getUrl();
    $v_email = $config->getEmail();
    $v_password = $config->getPassword();
    $v_creator = $config->getCreatorPHID();

    if ($request->isFormPost()) {
      $v_url = $request->getStr('url');
      $v_email = $request->getStr('email');
      $v_password = $request->getStr('password');
      $v_view_policy = $request->getStr('viewPolicy');
      $v_creator = $request->getStr('authodPHID');

      $template = id(new TeachableTransaction());
      $xactions = array();

      if ($is_new) {
        $xactions[] = id(new TeachableTransaction())
          ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);
      }

      $xactions[] = id(clone $template)
        ->setTransactionType(
            TeachableConfigurationURLTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_url);

      $xactions[] = id(clone $template)
        ->setTransactionType(
            TeachableConfigurationEmailTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_email);

      $xactions[] = id(clone $template)
        ->setTransactionType(
            TeachableConfigurationPasswordTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_password);

      $xactions[] = id(clone $template)
        ->setTransactionType(PhabricatorTransactions::TYPE_EDIT_POLICY)
        ->setNewValue($v_view_policy);

      if (empty($errors)) {
        $editor = id(new PhabricatorTeachableEditor())
          ->setActor($viewer)
          ->setContinueOnNoEffect(true)
          ->setContentSourceFromRequest($request);

        $xactions = $editor->applyTransactions($config, $xactions);

        if ($is_new) {
          $config->save();
        }

        return id(new AphrontRedirectResponse())
          ->setURI('/coursepath/teachable');
      } else {
        $config->setViewPolicy($v_view_policy);
      }
    }

    $config_note = 'NOTE: This is one time configuration.
     Please fill all following forms using admin credential';
    $form =
    id(new AphrontFormView())
      ->setAction($request->getrequestURI())
      ->setUser($viewer)
      ->appendRemarkupInstructions($config_note)
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Teachable URL'))
          ->setName('url')
          ->setValue($v_url)
          ->setError($e_question))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Email - must admin'))
          ->setName('email')
          ->setValue($v_email)
          ->setError($e_question))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Password'))
          ->setName('password')
          ->setValue($v_password)
          ->setError($e_question));

    if ($is_new) {
      $title = pht('Create Teachable Configuration');
      $button = pht('Create');
      $cancel_uri = $this->getApplicationURI();
      $header_icon = 'fa-plus-square';
    } else {
      $title = pht('Edit Teachable Configuration');
      $button = pht('Save Changes');
      $cancel_uri = '/V'.$config->getID();
      $header_icon = 'fa-pencil';
    }

    $policies = id(new PhabricatorPolicyQuery())
      ->setViewer($viewer)
      ->setObject($config)
      ->execute();

    $form->appendChild(
        id(new AphrontFormPolicyControl())
          ->setUser($viewer)
          ->setName('viewPolicy')
          ->setPolicyObject($config)
          ->setPolicies($policies)
          ->setCapability(PhabricatorPolicyCapability::CAN_VIEW)
          ->setSpacePHID($v_creator))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->setValue($button)
          ->addCancelButton($cancel_uri));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb($title);
    $crumbs->setBorder(true);

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText($title)
      ->setFormErrors($errors)
      ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
      ->setForm($form);

    $view = id(new PHUITwoColumnView())
      ->setFooter($form_box);

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild(
        array(
          $view,
      ));
  }

}
