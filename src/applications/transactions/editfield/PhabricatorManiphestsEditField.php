<?php

final class PhabricatorManiphestsEditField
  extends PhabricatorTokenizerEditField {

  protected function newDatasource() {
    return new ManiphestTaskDatasource();
  }

  protected function newHTTPParameterType() {
    return new AphrontProjectListHTTPParameterType();
  }

//   Conduit not yet develop cause it'll be need more time
//   So this commented and create the conduit after needed
//   protected function newConduitParameterType() {
//     return new ConduitProjectListParameterType();
//   }

}
