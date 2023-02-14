/**
 * @provides javelin-behavior-reaction
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-scrollbar
 *           javelin-stratcom
 *           conpherence-thread-manager
 */

JX.behavior('reaction', function(config, statics) {

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
  }

  /**
   * Generified adding new stuff to widgets technology!
   */
  JX.Stratcom.listen(
    ['touchstart', 'mousedown'],
    'reaction',
    function (e) {

      var threadManager = JX.ConpherenceThreadManager.getInstance();
      if (!threadManager) {
        threadManager = new JX.ConpherenceThreadManager();
      }
      var href = threadManager._getReactionURI();
      var data = e.getNodeData('reaction');

      var loadedPhid = threadManager.getLoadedThreadPHID();
      threadManager.setLoadedThreadPHID(null);

      new JX.Workflow('/lobby/reaction/'+data.trans_id+'/', data)
        .setCloseHandler(function() {
          threadManager.setLoadedThreadPHID(loadedPhid);
        })
        // we re-direct to conpherence home so the thread manager will
        // fix itself there
        .setHandler(function(r) {
          var modified_trans = r.transaction_id;
          var new_transaction = r.transactions[0];
          var msg = JX.$(r.transaction_element_id);
          var new_msg = JX.$H(new_transaction);
          JX.DOM.replace(msg, new_msg);
          threadManager.setLoadedThreadPHID(loadedPhid);
        })
        .start();
    }
  );
});
