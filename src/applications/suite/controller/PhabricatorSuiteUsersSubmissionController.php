<?php

final class PhabricatorSuiteUsersSubmissionController
  extends PhabricatorSuiteUsersDetailController {

    protected function afterMetRequiredCapabilities(AphrontRequest $request) {
      $request = $this->getRequest();
      $viewer = $this->getViewer();
      $id = $request->getURIData('id');
      $user = $this->getUser();
      $name = $user->getUserName();

      $done_uri = '/suite/users/view/'.$id.'/test-submission';

      $title = pht('Test Skill Submissions');


      $crumbs = $this->buildApplicationCrumbs();
      $crumbs->addTextCrumb(pht('Test Skill Submissions'));
      $crumbs->setBorder(true);

      $nav = $this->newNavigation(
        $user,
        PhabricatorSuiteProfileMenuEngine::ITEM_SUBMISSIONS);

      $header = $this->buildProfileHeader();

      $empty_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('All Submissions'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->appendChild(id(new PHUIBoxView())
          ->addPadding(PHUI::PADDING_MEDIUM)
          ->appendChild(pht('No data')));

      $view = id(new PHUITwoColumnView())
        ->setHeader($header)
        ->addClass('project-view-home')
        ->addClass('project-view-people-home')
        ->setFooter(array(
          $empty_box,
        ));

      return $this->newPage()
        ->setTitle($title)
        ->setCrumbs($crumbs)
        ->setNavigation($nav)
        ->appendChild($view);
    }

}
