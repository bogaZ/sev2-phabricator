<?php

final class PhabricatorSuiteUsersViewController
  extends PhabricatorSuiteUsersDetailController {

    protected function afterMetRequiredCapabilities(AphrontRequest $request) {

      $request = $this->getRequest();
      $viewer = $this->getViewer();
      $id = $request->getURIData('id');
      $user = $this->getUser();

      if (!$user) {
        return new Aphront404Response();
      }

      $suite_profile = SuiteProfileQuery::loadProfileForUser(
        $user,
        PhabricatorContentSource::newFromRequest($request));

      $done_uri = '/suite/users/view/'.$id.'/';

      $validation_exception = null;

      // Primary fields
      $field_list = PhabricatorCustomField::getObjectFields(
        $suite_profile,
        PhabricatorCustomField::ROLE_EDIT);
      $field_list
        ->setViewer($viewer)
        ->readFieldsFromStorage($suite_profile);

      // Upload properties
      $supported_formats = PhabricatorFile::getTransformableImageFormats();
      $e_file = true;
      $errors = array();

      if ($request->isFormPost()) {
        if ($request->getStr('suite_profile:upFor')) {
          // Primary info being edited
          $xactions = $field_list->buildFieldTransactionsFromRequest(
            new SuiteProfileTransaction(),
            $request);

          $editor = id(new SuiteProfileEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnNoEffect(true);

          try {
            $editor->applyTransactions($suite_profile, $xactions);
            return id(new AphrontRedirectResponse())->setURI($done_uri);
          } catch (PhabricatorApplicationTransactionValidationException $ex) {
            $validation_exception = $ex;
          }
        } else {
          // Some uploads activities
          $phid = $request->getStr('phid');
          $is_default = false;
          $is_id = false;
          $is_tax = false;
          $is_family = false;
          $is_skck = false;
          $is_domicile = false;
          $is_certificate = false;
          $is_other = false;
          $is_additional = false;
          if ($phid == PhabricatorPHIDConstants::PHID_VOID) {
            $phid = null;
            $is_default = true;
          } else if ($phid) {
            $file = id(new PhabricatorFileQuery())
              ->setViewer($viewer)
              ->withPHIDs(array($phid))
              ->executeOne();
          } else {
            if ($request->getFileExists('identityDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['identityDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_id = true;
            } else if ($request->getFileExists('taxDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['taxDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_tax = true;
            } else if ($request->getFileExists('familyDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['familyDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_family = true;
            } else if ($request->getFileExists('skckDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['skckDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_skck = true;
            } else if ($request->getFileExists('domicileDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['domicileDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_domicile = true;
            } else if ($request->getFileExists('certificateDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['certificateDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_certificate = true;
            } else if ($request->getFileExists('otherDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['otherDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_other = true;
            } else if ($request->getFileExists('additionalDoc')) {
              $file = PhabricatorFile::newFromPHPUpload(
                $_FILES['additionalDoc'],
                array(
                  'authorPHID' => $viewer->getPHID(),
                  'canCDN' => true,
                ));

              $is_additional = true;
            } else {
              $e_file = pht('Required');
              $errors[] = pht(
                'You must choose a file when uploading a new document.');
            }
          }

          if (!$errors && !$is_default) {
            if (!$file->isTransformableImage()) {
              $e_file = pht('Not Supported');
              $errors[] = pht(
                'This server only supports these image formats: %s.',
                implode(', ', $supported_formats));
            } else {

              if ($is_id || $is_tax) {
                // Transform as ID card type
                $xform = PhabricatorFileTransform::getTransformByKey(
                  PhabricatorFileThumbnailTransform::TRANSFORM_ID_DOC);
              } else {
                // Transform as other doc type
                $xform = PhabricatorFileTransform::getTransformByKey(
                  PhabricatorFileThumbnailTransform::TRANSFORM_OTHER_DOC);
              }

              $xformed = $xform->executeTransform($file);
            }
          }

          if (!$errors) {
            if ($suite_profile && !$is_default) {

              if ($is_id) {
                $suite_profile->setIdentityDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_tax) {
                $suite_profile->setTaxDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_family) {
                $suite_profile->setFamilyDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_skck) {
                $suite_profile->setSkckDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_domicile) {
                $suite_profile->setDomicileDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_certificate) {
                $suite_profile->setCertificateDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_other) {
                $suite_profile->setOtherDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }

              if ($is_additional) {
                $suite_profile->setAdditionalDocPHID($xformed->getPHID());
                $xformed->attachToObject($suite_profile->getPHID());
                $suite_profile->save();
              }
            }

            return id(new AphrontRedirectResponse())->setURI($done_uri);
          }
        }
      }

      // Prepare boxes
      $title = pht('Edit Suite Profile');

      $images = array();
      $current_id = $suite_profile->getIdentityDocPHID();
      if ($current_id) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_id))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['id_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current ID Doc'),
            );
          }
        }
      }
      $current_tax = $suite_profile->getTaxDocPHID();
      if ($current_tax) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_tax))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['tax_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current Tax Doc'),
            );
          }
        }
      }
      $current_family = $suite_profile->getFamilyDocPHID();
      if ($current_family) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_family))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['family_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current Family Doc'),
            );
          }
        }
      }
      $current_skck = $suite_profile->getSkckDocPHID();
      if ($current_skck) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_skck))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['skck_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current SKCK Doc'),
            );
          }
        }
      }
      $current_domicile = $suite_profile->getDomicileDocPHID();
      if ($current_domicile) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_domicile))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['domicile_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current Domicile Doc'),
            );
          }
        }
      }
      $current_certificate = $suite_profile->getCertificateDocPHID();
      if ($current_certificate) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_certificate))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['certificate_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current Certificate Doc'),
            );
          }
        }
      }
      $current_other = $suite_profile->getOtherDocPHID();
      if ($current_other) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_other))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['other_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current Other Doc'),
            );
          }
        }
      }
      $current_additional = $suite_profile->getAdditionalDocPHID();
      if ($current_additional) {
        $files = id(new PhabricatorFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($current_additional))
          ->execute();
        if ($files) {
          $file = head($files);
          if ($file->isTransformableImage()) {
            $images['additional_doc'] = array(
              'uri' => $file->getBestURI(),
              'tip' => pht('Current Additional Doc'),
            );
          }
        }
      }

      require_celerity_resource('people-profile-css');
      Javelin::initBehavior('phabricator-tooltips', array());

      $docs_img = array();
      foreach ($images as $identifier => $spec) {
        $style = null;
        if (isset($spec['style'])) {
          $style = $spec['style'];
        }
        $doc_img = javelin_tag(
          'a',
          array(
            'href' => $spec['uri'],
            'class' => 'phui-oi-link',
            'meta' => array(
              'tip' => $spec['tip'],
              'size' => 300,
            ),
          ),
          phutil_tag(
            'img',
            array(
              'height' => 80,
              'width' => 160,
              'src' => $spec['uri'],
            )));

        $docs_img[$identifier] = $doc_img;
      }

      // ID card box
      $upload_id_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current ID Doc'))
            ->setValue(array_key_exists('id_doc', $docs_img)
                      ? $docs_img['id_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('identityDoc')
            ->setLabel(pht('Upload Identity (KTP)'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_id_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('ID Card (KTP)'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_id_form);

      // NPWP box
      $upload_tax_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current Tax Doc'))
            ->setValue(array_key_exists('tax_doc', $docs_img)
                      ? $docs_img['tax_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('taxDoc')
            ->setLabel(pht('Upload Tax Doc (NPWP)'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_tax_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Tax Document (NPWP)'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_tax_form);

      // KK box
      $upload_family_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current Family Doc'))
            ->setValue(array_key_exists('family_doc', $docs_img)
                      ? $docs_img['family_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('familyDoc')
            ->setLabel(pht('Upload Family Doc (KK)'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_family_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Family Document (KK)'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_family_form);

      // SKCK box
      $upload_skck_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current SKCK Doc'))
            ->setValue(array_key_exists('skck_doc', $docs_img)
                      ? $docs_img['skck_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('skckDoc')
            ->setLabel(pht('Upload SKCK Doc'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_skck_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('SKCK Document'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_skck_form);

      // Domicile box
      $upload_domicile_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current Domicile Doc'))
            ->setValue(array_key_exists('domicile_doc', $docs_img)
                      ? $docs_img['domicile_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('domicileDoc')
            ->setLabel(pht('Upload Domicile Doc'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_domicile_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Domicile Document'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_domicile_form);

      // Certificate box
      $upload_certificate_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current Certificate Doc (Ijazah)'))
            ->setValue(array_key_exists('certificate_doc', $docs_img)
                      ? $docs_img['certificate_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('certificateDoc')
            ->setLabel(pht('Upload Certificate Doc (Ijazah)'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_certificate_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Certificate Document (Ijazah)'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_certificate_form);

      // Other box
      $upload_other_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current Other Doc'))
            ->setValue(array_key_exists('other_doc', $docs_img)
                      ? $docs_img['other_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('otherDoc')
            ->setLabel(pht('Upload Other Doc'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_other_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Other Document'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_other_form);

      // Additional box
      $upload_additional_form = id(new AphrontFormView())
        ->setUser($viewer)
        ->setEncType('multipart/form-data')
        ->appendChild(
          id(new AphrontFormMarkupControl())
            ->setLabel(pht('Current Additional Doc'))
            ->setValue(array_key_exists('additional_doc', $docs_img)
                      ? $docs_img['additional_doc']
                      : ''))
        ->appendChild(
          id(new AphrontFormFileControl())
            ->setName('additionalDoc')
            ->setLabel(pht('Upload Additional Doc'))
            ->setError($e_file)
            ->setCaption(
              pht('Supported formats: %s', implode(', ', $supported_formats))))
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Upload')));

      $upload_additional_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Additional Document'))
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($upload_additional_form);

      // Main Info
      $profile_form = id(new AphrontFormView())
        ->setUser($viewer);

      $field_list->appendFieldsToForm($profile_form);
      $profile_form
        ->appendChild(
          id(new AphrontFormSubmitControl())
            ->addCancelButton($done_uri)
            ->setValue(pht('Save Profile')));

      $main_info_box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Profile'))
        ->setValidationException($validation_exception)
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
        ->setForm($profile_form);

      $crumbs = $this->buildApplicationCrumbs();
      $crumbs->addTextCrumb(pht('Edit Suite Profile'));
      $crumbs->setBorder(true);

      $nav = $this->newNavigation(
        $user,
        PhabricatorSuiteProfileMenuEngine::ITEM_MANAGE);

      $header = $this->buildProfileHeader();

      $id_tax_boxes = id(new AphrontMultiColumnView())
        ->addColumn($upload_id_box)
        ->addColumn($upload_tax_box)
        ->setFluidLayout(true)
        ->setGutter(AphrontMultiColumnView::GUTTER_MEDIUM);

      $family_skck_boxes = id(new AphrontMultiColumnView())
        ->addColumn($upload_family_box)
        ->addColumn($upload_skck_box)
        ->setFluidLayout(true)
        ->setGutter(AphrontMultiColumnView::GUTTER_MEDIUM);

      $domicile_certificate_boxes = id(new AphrontMultiColumnView())
        ->addColumn($upload_domicile_box)
        ->addColumn($upload_certificate_box)
        ->setFluidLayout(true)
        ->setGutter(AphrontMultiColumnView::GUTTER_MEDIUM);

      $other_additional_boxes = id(new AphrontMultiColumnView())
        ->addColumn($upload_other_box)
        ->addColumn($upload_additional_box)
        ->setFluidLayout(true)
        ->setGutter(AphrontMultiColumnView::GUTTER_MEDIUM);

      $view = id(new PHUITwoColumnView())
        ->setHeader($header)
        ->addClass('project-view-home')
        ->addClass('project-view-people-home')
        ->setFooter(array(
          $main_info_box,
          $id_tax_boxes,
          $family_skck_boxes,
          $domicile_certificate_boxes,
          $other_additional_boxes,
        ));

      return $this->newPage()
        ->setTitle($title)
        ->setCrumbs($crumbs)
        ->setNavigation($nav)
        ->appendChild($view);
    }

}
