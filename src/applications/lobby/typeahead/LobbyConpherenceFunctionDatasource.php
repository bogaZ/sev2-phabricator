<?php

final class LobbyConpherenceFunctionDatasource
  extends PhabricatorTypeaheadCompositeDatasource {

  public function getBrowseTitle() {
    return pht('Browse Channel');
  }

  public function getPlaceholderText() {
    return pht('Type a channel name or function...');
  }

  public function getComponentDatasources() {
    $sources = array(
      new LobbyConpherenceDatasource(),
    );

    return $sources;
  }

}
