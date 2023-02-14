<?php

final class PhabricatorProjectExcludeDatasource
  extends PhabricatorTypeaheadCompositeDatasource {

  public function getBrowseTitle() {
    return pht('Browse Projects');
  }

  public function getPlaceholderText() {
    return pht('Type any excluded project...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhabricatorProjectApplication';
  }

  public function getComponentDatasources() {
    return array(
      new PhabricatorProjectDatasource(),
    );
  }

  public function getDatasourceFunctions() {
    return array(
    'not' => array(
        'name' => pht('Not Isnt: ...'),
        'arguments' => pht('project'),
        'summary' => pht('Find results not in specific projects.'),
        'description' => pht(
          'This function allows you to find results which are not in '.
          'one or more projects. For example, use this query to find '.
          'results which are not associated with a specific project:'.
          "\n\n".
          '> not(vanilla)'.
          "\n\n".
          'You can exclude multiple projects. This will cause the query '.
          'to return only results which are not in any of the excluded '.
          'projects:'.
          "\n\n".
          '> not(vanilla), not(chocolate)'.
          "\n\n".
          'You can combine this function with other functions to refine '.
          'results. For example, use this query to find iOS results which '.
          'are not bugs:'.
          "\n\n".
          '> ios, not(bug)'),
      ),
    );
  }


  /* This function generate like phabircator project datasource
  But for default it only search tags that will be exclude from maniphest
  advance search */
  protected function didLoadResults(array $results) {
    $function = $this->getCurrentFunction();
    $return = array();
    foreach ($results as $result) {
      $result
        ->setTokenType(PhabricatorTypeaheadTokenView::TYPE_FUNCTION)
        ->setIcon('fa-asterisk')
        ->setColor(null)
        ->resetAttributes()
        ->addAttribute(pht('Function'));

        $return[] = id(clone $result)
        ->setPHID('not('.$result->getPHID().')')
        ->setDisplayName(pht('%s', $result->getDisplayName()))
        ->setName('not '.$result->getName())
        ->addAttribute(pht('Excluded results tagged with this project.'));
    }

    return $return;
  }

  protected function evaluateFunction($function, array $argv_list) {
    $phids = array();
    foreach ($argv_list as $argv) {
      $phids[] = head($argv);
    }

    $operator = array(
      'not' => PhabricatorQueryConstraint::OPERATOR_NOT,
    );

    $results = array();
    foreach ($phids as $phid) {
      $results[] = new PhabricatorQueryConstraint(
        $operator[$function],
        $phid);
    }

    return $results;
  }

  public function renderFunctionTokens($function, array $argv_list) {
    $phids = array();
    foreach ($argv_list as $argv) {
      $phids[] = head($argv);
    }

    $tokens = $this->renderTokens($phids);
    foreach ($tokens as $token) {
      $token->setColor(null);
      if ($token->isInvalid()) {
        $token->setValue(pht('In Any: Invalid Project'));
      } else {
        $token
          ->setIcon('fa-asterisk')
          ->setTokenType(PhabricatorTypeaheadTokenView::TYPE_FUNCTION);

        $token
            ->setKey('not('.$token->getKey().')')
            ->setValue(pht('Not In: %s', $token->getValue()));
      }
    }

    return $tokens;
  }

}
