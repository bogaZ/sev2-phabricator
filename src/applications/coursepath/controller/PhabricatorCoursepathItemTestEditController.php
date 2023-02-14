<?php

final class PhabricatorCoursepathItemTestEditController
  extends PhabricatorCoursepathItemController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $item_phid = $request->getStr('itemPHID');
    $type = $request->getStr('type');

    if (!$item_phid) {
      return new Aphront404Response();
    }

    $item = id(new CoursepathItemQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($item_phid))
        ->executeOne();

    if ($id) {
      $test = id(new CoursepathItemTestQuery())
        ->setViewer($viewer)
        ->needOptions(true)
        ->withIDs(array($id))
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->executeOne();
      if (!$test) {
        return new Aphront404Response();
      }
      $is_new = false;
    } else {
      $test = CoursepathItemTest::initializeNewTest($viewer, $item->getPHID());
      $is_new = true;
    }

    $e_question = true;
    $e_option = true;
    $errors = array();

    $v_title = $test->getTitle();
    $v_question = $test->getQuestion();
    $v_answer = $test->getAnswer();
    $v_type = $test->getType();
    $v_severity = $test->getSeverity();
    $v_stack = $test->getStack();
    $v_creator = $test->getCreatorPHID();
    $v_test_code = $test->getTestCode();
    $v_not_auto_grade = $test->getIsNotAutomaticallyGraded();
    $v_options = array();

    $v_wpm = false;
    $suite_type = null;

    if ($is_new) {
      $v_projects = array();
    } else {
      $v_options = $test->getOptions();
      $v_projects = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $test->getPHID(),
        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);
      $v_projects = array_reverse($v_projects);
    }

    if ($request->isFormPost()) {
      $v_title = $request->getStr('title');
      $v_question = $request->getStr('question');
      $v_view_policy = $request->getStr('viewPolicy');
      $v_answer = $request->getStr('answer');
      $v_type = $request->getStr('type');
      $v_severity = $request->getStr('severity');
      $v_stack = $request->getStr('stack');
      $v_creator = $request->getStr('authorPHID');
      $v_not_auto_grade = (bool)$request->getBool('isNotAutoGrade');
      $v_wpm = (bool)$request->getBool('isWPM');
      $v_options = $request->getArr('option');

      if ($v_wpm) {
        $suite_type = CoursepathItemTest::SUITE_WPM;
      }

      $v_projects = $request->getArr('projects');
      if ($v_projects) {
        $project = id(new PhabricatorProjectQuery())
            ->setViewer($viewer)
            ->withPHIDs($v_projects)
            ->executeOne();
         $v_test_code = trim($project->getName());
      }

      if ($is_new) {
        // NOTE: Make sure common and useful response "0" is preserved.
        foreach ($v_options as $key => $response) {
          if (!strlen($response)) {
            unset($v_options[$key]);
          }
        }
      }

      $template = id(new CoursepathItemTestTransaction());
      $xactions = array();

      if ($is_new) {
        $xactions[] = id(new CoursepathItemTestTransaction())
          ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);
      }

      $xactions[] = id(clone $template)
        ->setTransactionType(
            CoursepathItemTestQuestionTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_question);

      $xactions[] = id(clone $template)
        ->setTransactionType(
            CoursepathItemTestAnswerTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_answer);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestTitleTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_title);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestSeverityTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_severity);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestTypeTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_type);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestItemPHIDTransaction::TRANSACTIONTYPE)
        ->setNewValue($item_phid);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestTestCodeTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_test_code);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestStackTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_stack);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          CoursepathItemTestIsAutoGradeTransaction::TRANSACTIONTYPE)
        ->setNewValue($v_not_auto_grade);

      $xactions[] = id(clone $template)
        ->setTransactionType(
          PhabricatorCoursepathTestSuiteTypeTransaction::TRANSACTIONTYPE)
        ->setNewValue($suite_type);

      $xactions[] = id(clone $template)
        ->setTransactionType(PhabricatorTransactions::TYPE_EDIT_POLICY)
        ->setNewValue($v_view_policy);

      $proj_edge_type = PhabricatorProjectObjectHasProjectEdgeType::EDGECONST;
      $xactions[] = id(new PhrictionTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', $proj_edge_type)
        ->setNewValue(array('=' => array_fuse($v_projects)));

      if (empty($errors)) {
        $editor = id(new PhabricatorCoursepathItemTestEditor())
          ->setActor($viewer)
          ->setContinueOnNoEffect(true)
          ->setContentSourceFromRequest($request);

        $xactions = $editor->applyTransactions($test, $xactions);

        if ($is_new) {
          $test->save();
          foreach ($v_options as $response) {
            $option = new CoursepathItemTestOption();
            $option->setName($response);
            $option->setTestID($test->getID());
            $option->save();
          }
        } else {
          /**
           * Need to revisit this query later
           */
          foreach ($test->getOptions() as $opt) {
            $opt->delete();
          }

          foreach ($v_options as $response) {
            $option = new CoursepathItemTestOption();
            $option->setName($response);
            $option->setTestID($test->getID());
            $option->save();
          }
        }

        return id(new AphrontRedirectResponse())
          ->setURI($test->getViewURI(
            $item->getID(),
            $item->getID()));
      } else {
        $test->setViewPolicy($v_view_policy);
      }
    }

    $form =
    id(new AphrontFormView())
      ->setAction($request->getrequestURI())
      ->setUser($viewer)
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Title'))
          ->setName('title')
          ->setValue($v_title))
      ->appendChild(
        id(new PhabricatorRemarkupControl())
          ->setUser($viewer)
          ->setLabel(pht('Question'))
          ->setName('question')
          ->setValue($v_question));

    if ($type !== CoursepathItemTest::TYPE_DAILY) {
      for ($ii = 0; $ii < 6; $ii++) {
        $alphabet = range('A', 'Z');
        $answer = array_values(mpull($v_options, 'getName'));
        $option = id(new PhabricatorRemarkupControl())
          ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_SHORT)
          ->setLabel(pht('Answer %s', $alphabet[$ii]))
          ->setName('option[]')
          ->setValue($answer[$ii] ?? '');

        if ($ii == 0) {
          $option->setError($e_option);
        }

        $form->appendControl($option);
      }
      $form->appendChild(
        id(new AphrontFormSelectControl())
          ->setLabel(pht('Correct Answer'))
          ->setName('answer')
          ->setValue($v_answer)
          ->setOptions($test->getAvailableAnswerOptions()));
    }

    $form->appendChild(
      id(new AphrontFormCheckboxControl())
        ->setLabel('Is Not Auto Grade')
        ->addCheckbox(
          'isNotAutoGrade',
          1,
        null));

    if ($type !== CoursepathItemTest::TYPE_DAILY) {
      $form->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Stack'))
          ->setName('stack')
          ->setValue($v_stack));
    } else {
      $form->appendChild(
        id(new AphrontFormCheckboxControl())
          ->setLabel('WPM')
          ->addCheckbox(
            'isWPM',
            1,
          null));
    }

    $form
    ->appendChild(
      id(new AphrontFormSelectControl())
        ->setLabel(pht('Severity'))
        ->setName('severity')
        ->setValue($v_severity)
        ->setOptions($test->getSeverityMap()));

    if ($is_new) {
      $title = pht('Create %s Skill Test', ucwords($type));
      $button = pht('Create');
      $cancel_uri = $this->getApplicationURI();
      $header_icon = 'fa-plus-square';
    } else {
      $title = pht('Edit Poll: %s', $test->getQuestion());
      $button = pht('Save Changes');
      $cancel_uri = '/V'.$test->getID();
      $header_icon = 'fa-pencil';
    }

    $policies = id(new PhabricatorPolicyQuery())
      ->setViewer($viewer)
      ->setObject($test)
      ->execute();

    $form
    ->appendControl(
      id(new AphrontFormTokenizerControl())
        ->setDatasource(new PhabricatorProjectDatasource())
        ->setName('projects')
        ->setLabel(pht('Tags'))
        ->setValue($v_projects))
    ->appendChild(
        id(new AphrontFormPolicyControl())
          ->setUser($viewer)
          ->setName('viewPolicy')
          ->setPolicyObject($test)
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
