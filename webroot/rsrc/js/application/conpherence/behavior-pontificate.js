/**
 * @provides javelin-behavior-conpherence-pontificate
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-stratcom
 *           conpherence-thread-manager
 */

JX.behavior('conpherence-pontificate', function() {

  var _sendMessage = function(e) {
    e.kill();
    var form = e.getNode('tag:form');
    var threadManager = JX.ConpherenceThreadManager.getInstance();
    threadManager.sendMessage(form, {});
  };

  JX.Stratcom.listen(
    ['submit', 'didSyntheticSubmit'],
    'conpherence-pontificate',
    _sendMessage);

  /**
   * Generified adding new stuff to widgets technology!
   */
  JX.Stratcom.listen(
    ['touchstart', 'mousedown'],
    'reaction',
    function (e) {

      var threadManager = JX.ConpherenceThreadManager.getInstance();
      var href = threadManager._getReactionURI();
      var data = e.getNodeData('reaction');

      var loadedPhid = threadManager.getLoadedThreadPHID();
      threadManager.setLoadedThreadPHID(null);

      new JX.Workflow(href+data.trans_id+'/', data)
        .setCloseHandler(function() {
          threadManager.setLoadedThreadPHID(loadedPhid);
        })
        // we re-direct to conpherence home so the thread manager will
        // fix itself there
        .setHandler(function(r) {
          JX.$U(r.href).go();
        })
        .start();
    }
  );

});
