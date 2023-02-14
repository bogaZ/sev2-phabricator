<?php

final class PhabricatorSuiteUsersCvController
  extends PhabricatorSuiteUsersDetailController {

    protected function afterMetRequiredCapabilities(AphrontRequest $request) {
      $request = $this->getRequest();
      $viewer = $this->getViewer();
      $id = $request->getURIData('id');
      $user = $this->getUser();
      $name = $user->getUserName();

      $done_uri = '/suite/users/view/'.$id.'/cv';


      $title = pht('%s Job Profile', $name);

      $profile = SuiteProfileQuery::loadProfileForUser($user,
      PhabricatorContentSource::newFromRequest($request));

      $crumbs = $this->buildApplicationCrumbs();
      $crumbs->addTextCrumb(pht('Edit Job Profile'));
      $crumbs->setBorder(true);

      $nav = $this->newNavigation(
        $user,
        PhabricatorSuiteProfileMenuEngine::ITEM_JOB_PROFILE);

      $header = $this->buildProfileHeader();

      $empty_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Job Profile'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->appendChild(id(new PHUIBoxView())
          ->addPadding(PHUI::PADDING_MEDIUM)
          ->appendChild($this->renderCv($profile->getCv())));

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

    protected function renderCv(array $array, $child = false) {
      $cv = new PHUIPropertyListView();
      if (!$child) {
        $cv->addSectionHeader(pht('Curriculum Vitae'));
      }

      foreach ($array as $label => $value) {
        if (is_array($value)) {
          $cv->addProperty($label, $this->renderCv($value, true));
        } else {
          $cv->addProperty($label, $value);
        }
      }

      return $cv;
    }

}
