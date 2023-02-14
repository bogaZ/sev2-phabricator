<?php

final class PhabricatorBuiltinPatchList extends PhabricatorSQLPatchList {

  public function getNamespace() {
    return 'phabricator';
  }

  private function getPatchPath($file) {
    $root = dirname(phutil_get_library_root('phabricator'));
    $path = $root.'/resources/sql/'.$file;

    // Make sure it exists.
    Filesystem::readFile($path);

    return $path;
  }

  public function getPatches() {
    $patches = array();

    foreach ($this->getOldPatches() as $old_name => $old_patch) {
      if (preg_match('/^base\_/', $old_name)) {
        $old_patch['name'] = $this->getPatchPath($old_name);
        $old_patch['type'] = 'sql';
      } else {
        if (empty($old_patch['name'])) {
          $old_patch['name'] = $this->getPatchPath($old_name);
        }
        if (empty($old_patch['type'])) {
          $matches = null;
          preg_match('/\.(sql|php)$/', $old_name, $matches);
          $old_patch['type'] = $matches[1];
        }
      }

      $patches[$old_name] = $old_patch;
    }

    $root = dirname(phutil_get_library_root('phabricator'));
    $auto_root = $root.'/resources/sql/autopatches/';
    $patches += $this->buildPatchesFromDirectory($auto_root);

    return $patches;
  }

  public function getOldPatches() {
    return array(
      'base_almanac.sql' => array(
        'after' => array(),
      ),
      'base_application.sql' => array(),
      'base_audit.sql' => array(),
      'base_auth.sql' => array(),
      'base_badges.sql' => array(),
      'base_cache.sql' => array(),
      'base_calendar.sql' => array(),
      'base_chatlog.sql' => array(),
      'base_conduit.sql' => array(),
      'base_config.sql' => array(),
      'base_conpherence.sql' => array(),
      'base_countdown.sql' => array(),
      'base_coursepath.sql' => array(),
      'base_daemon.sql' => array(),
      'base_dashboard.sql' => array(),
      'base_differential.sql' => array(),
      'base_diviner.sql' => array(),
      'base_doorkeeper.sql' => array(),
      'base_draft.sql' => array(),
      'base_drydock.sql' => array(),
      'base_fact.sql' => array(),
      'base_feed.sql' => array(),
      'base_file.sql' => array(),
      'base_flag.sql' => array(),
      'base_fund.sql' => array(),
      'base_harbormaster.sql' => array(),
      'base_herald.sql' => array(),
      'base_job.sql' => array(),
      'base_legalpad.sql' => array(),
      'base_lobby.sql' => array(),
      'base_maniphest.sql' => array(),
      'base_mention.sql' => array(),
      'base_metamta.sql' => array(),
      'base_meta_data.sql' => array(),
      'base_mood.sql' => array(),
      'base_multimeter.sql' => array(),
      'base_nuance.sql' => array(),
      'base_oauth_server.sql' => array(),
      'base_owners.sql' => array(),
      'base_packages.sql' => array(),
      'base_passphrase.sql' => array(),
      'base_paste.sql' => array(),
      'base_performance.sql' => array(),
      'base_phame.sql' => array(),
      'base_phlux.sql' => array(),
      'base_pholio.sql' => array(),
      'base_phortune.sql' => array(),
      'base_phragment.sql' => array(),
      'base_phrequent.sql' => array(),
      'base_phriction.sql' => array(),
      'base_phurl.sql' => array(),
      'base_policy.sql' => array(),
      'base_ponder.sql' => array(),
      'base_project.sql' => array(),
      'base_releeph.sql' => array(),
      'base_repository.sql' => array(),
      'base_search.sql' => array(),
      'base_slowvote.sql' => array(),
      'base_spaces.sql' => array(),
      'base_suite.sql' => array(),
      'base_system.sql' => array(),
      'base_token.sql' => array(),
      'base_user.sql' => array(),
      'base_worker.sql' => array(),
      'base_xhpast.sql' => array(),
      'base_xhprof.sql' => array(),
    );

    // NOTE: STOP! Don't add new patches here.
    // Use 'resources/sql/autopatches/' instead!
  }
}
