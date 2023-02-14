<?php

abstract class PhabricatorSearchUserCheckInListAPIMethod
  extends ConduitAPIMethod {

  abstract public function newSearchEngine();

  final public function getQueryMaps($query) {
    $maps = $this->getCustomQueryMaps($query);

    // Make sure we emit empty maps as objects, not lists.
    foreach ($maps as $key => $map) {
      if (!$map) {
        $maps[$key] = (object)$map;
      }
    }

    if (!$maps) {
      $maps = (object)$maps;
    }

    return $maps;
  }

  protected function getCustomQueryMaps($query) {
    return array();
  }

  public function getApplication() {
    $engine = $this->newSearchEngine();
    $class = $engine->getApplicationClassName();
    return PhabricatorApplication::getByClass($class);
  }
  final protected function defineParamTypes() {
    return array(
      'constraints' => 'optional map<string, wild>',
    ) + $this->getPagerParamTypes();
  }

  final protected function defineReturnType() {
    return 'map<string, wild>';
  }

  final protected function execute(ConduitAPIRequest $request) {
    $engine = $this->newSearchEngine()
      ->setViewer($request->getUser());
    return $engine->buildConduitResponse($request, $this);
  }

  final public function getMethodDescription() {
    return pht(
      'This is a standard **ApplicationSearch** method which will let you '.
      'list, query, or search for objects. For documentation on these '.
      'endpoints, see **[[ %s | Conduit API: Using Search Endpoints ]]**.',
      PhabricatorEnv::getDoclink('Conduit API: Using Search Endpoints'));
  }

  final public function getMethodDocumentation() {
    $viewer = $this->getViewer();

    $engine = $this->newSearchEngine()
      ->setViewer($viewer);

    $query = $engine->newQuery();

    $out = array();

    $out[] = $this->buildConstraintsBox($engine);
    $out[] = $this->buildPagingBox($engine);

    return $out;
  }


  private function buildConstraintsBox(
    PhabricatorApplicationSearchEngine $engine) {

    $info = pht(<<<EOTEXT
You can apply custom constraints by passing a dictionary in `constraints`.
This will let you search for specific sets of results (for example, you may
want show only results with a certain state, status, or owner).


If you specify both a `queryKey` and `constraints`, the builtin or saved query
will be applied first as a starting point, then any additional values in
`constraints` will be applied, overwriting the defaults from the original query.

Different endpoints support different constraints. The constraints this method
supports are detailed below. As an example, you might specify constraints like
this:

```lang=json, name="Example Custom Constraints"
{
  ...
  "constraints": {
    "phids": ["PHID-USER-1111", "PHID-USER-2222"],
    ...
  },
  ...
}
```

This API endpoint supports these constraints:
EOTEXT
      );
    $constants_rows = array();
    $fields = $engine->getSearchFieldsForConduit();

    // As a convenience, put these fields at the very top, even if the engine
    // specifies and alternate display order for the web UI. These fields are
    // very important in the API and nearly useless in the web UI.
    $fields = array_select_keys(
      $fields,
      array('ids', 'phids')) + $fields;
    $constant_lists = array();

    $rows = array();
    foreach ($fields as $field) {
      $key = $field->getConduitKey();
      $label = $field->getLabel();
      $constants = $field->newConduitConstants();
      $show_table = false;

      $type_object = $field->getConduitParameterType();
      if ($type_object) {
        $type = $type_object->getTypeName();
        $description = $field->getDescription();
        if ($constants) {
          $description = array(
            $description,
            ' ',
            phutil_tag('em', array(), pht('(See table below.)')),
          );
          $show_table = true;
        }
      } else {
        $type = null;
        $description = phutil_tag('em', array(), pht('Not supported.'));
      }

      $rows[] = array(
        $key,
        $label,
        $type,
        $description,
      );

      if ($show_table) {
        $constant_lists[] = $this->newRemarkupDocumentationView(
          pht(
            'Constants supported by the `%s` constraint:',
            $key));

        foreach ($constants as $constant) {
          if ($constant->getIsDeprecated()) {
            $icon = id(new PHUIIconView())
              ->setIcon('fa-exclamation-triangle', 'red');
          } else {
            $icon = null;
          }

          $constants_rows[] = array(
            $constant->getKey(),
            array(
              $icon,
              ' ',
              $constant->getValue(),
            ),
          );
        }

        $constants_table = id(new AphrontTableView($constants_rows))
          ->setHeaders(
            array(
              pht('Key'),
              pht('Value'),
            ))
          ->setColumnClasses(
            array(
              'mono',
              'wide',
            ));

        $constant_lists[] = $constants_table;
      }
    }

    $table = id(new AphrontTableView($rows))
      ->setHeaders(
        array(
          pht('Key'),
          pht('Label'),
          pht('Type'),
          pht('Description'),
        ))
      ->setColumnClasses(
        array(
          'prewrap',
          'pri',
          'prewrap',
          'wide',
        ));

    return id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Custom Query Constraints'))
      ->setCollapsed(true)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->appendChild($this->newRemarkupDocumentationView($info))
      ->appendChild($table)
      ->appendChild($constant_lists);
  }


  private function buildPagingBox(
    PhabricatorApplicationSearchEngine $engine) {

    $info = pht(<<<EOTEXT
Queries are limited to returning 100 results at a time. If you want fewer
results than this, you can use `limit` to specify a smaller limit.

If you want more results, you'll need to make additional queries to retrieve
more pages of results.

The result structure contains a `cursor` key with information you'll need in
order to fetch the next page of results. After an initial query, it will
usually look something like this:

```lang=json, name="Example Cursor Result"
{
  ...
  "cursor": {
    "limit": 100,
    "after": "1234",
    "before": null,
    "order": null
  }
  ...
}
```

The `limit` and `order` fields are describing the effective limit and order the
query was executed with, and are usually not of much interest. The `after` and
`before` fields give you cursors which you can pass when making another API
call in order to get the next (or previous) page of results.

To get the next page of results, repeat your API call with all the same
parameters as the original call, but pass the `after` cursor you received from
the first call in the `after` parameter when making the second call.

If you do things correctly, you should get the second page of results, and
a cursor structure like this:

```lang=json, name="Second Result Page"
{
  ...
  "cursor": {
    "limit": 5,
    "after": "4567",
    "before": "7890",
    "order": null
  }
  ...
}
```

You can now continue to the third page of results by passing the new `after`
cursor to the `after` parameter in your third call, or return to the previous
page of results by passing the `before` cursor to the `before` parameter. This
might be useful if you are rendering a web UI for a user and want to provide
"Next Page" and "Previous Page" links.

If `after` is `null`, there is no next page of results available. Likewise,
if `before` is `null`, there are no previous results available.
EOTEXT
      );

    return id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Paging and Limits'))
      ->setCollapsed(true)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->appendChild($this->newRemarkupDocumentationView($info));
  }

}
