/**
 * @provides javelin-behavior-prepare-chat
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-scrollbar
 *           javelin-aphlict
 *           javelin-stratcom
 *           javelin-behavior-device
 *           phabricator-keyboard-shortcut
 *           conpherence-thread-manager
 */

JX.behavior('prepare-chat', function(config, statics) {

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
  }

  var userVisible = true;
  var userMinimize = false;
  var show = true;
  var loadThreadID = config.threadID;
  var scrollbar = null;

  var margin = JX.Scrollbar.getScrollbarControlMargin();

  function _getColumnNode() {
    return JX.$('lobby-chat-container');
  }

  function _getColumnScrollNode() {
    var column = _getColumnNode();
    return JX.DOM.find(column, 'div', 'conpherence-durable-column-main');
  }

  JX.Stratcom.listen('aphlict-server-message', null, function(e) {
    var message = e.getData();
    if (message.type) {
      switch (message.type) {
        case "message":
          // New message, scroll down
          var messages = _getColumnMessagesNode();
          console.log(scrollbar, messages.scrollHeight);
          window.chatScroll = scrollbar;
          scrollbar.scrollTo(messages.scrollHeight);
          break;
        default:

      }
    }
    // Check if this update notification is about the currently visible
    // board. If it is, update the board state.
  });

  JX.Stratcom.listen('aphlict-reconnect', null, function(e) {
  });


  function _markLoading(loading) {
    var column = _getColumnNode();
    JX.DOM.alterClass(column, 'loading', loading);
  }

  function _drawColumn() {
    JX.DOM.alterClass(
      document.body,
      'with-lobby-chat-column',
      true);
    var column = _getColumnNode();
    JX.DOM.show(column);
    threadManager.loadThreadByID(loadThreadID);
  }


  scrollbar = new JX.Scrollbar(_getColumnScrollNode());



  /* Conpherence Thread Manager configuration - lots of display
   * callbacks.
   */

  var threadManager = new JX.ConpherenceThreadManager();
  threadManager.setMessagesRootCallback(function() {
    return _getColumnMessagesNode();
  });
  threadManager.setLoadThreadURI('/conpherence/columnview/');
  threadManager.setWillLoadThreadCallback(function() {
    _markLoading(true);
  });
  threadManager.setDidLoadThreadCallback(function(r) {
    var column = _getColumnNode();
    var new_column = JX.$H(r.content);
    JX.DOM.replace(column, new_column);
    JX.DOM.show(_getColumnNode());
    var messages = _getColumnMessagesNode();
    scrollbar = new JX.Scrollbar(_getColumnScrollNode());
    scrollbar.scrollTo(messages.scrollHeight);
    _markLoading(false);
    JX.Stratcom.invoke('resize');
    loadThreadID = threadManager.getLoadedThreadID();
  });
  threadManager.setDidUpdateThreadCallback(function(r) {
    var messages = _getColumnMessagesNode();
    scrollbar.scrollTo(messages.scrollHeight);
  });

  threadManager.setWillSendMessageCallback(function() {
    // Wipe the textarea immediately so the user can start typing more text.
  });

  threadManager.setDidSendMessageCallback(function(r, non_update) {
    if (non_update) {
      return;
    }
    var messages = _getColumnMessagesNode();
    scrollbar.scrollTo(messages.scrollHeight);
  });

  threadManager.setWillUpdateWorkflowCallback(function() {
    JX.Stratcom.invoke('notification-panel-close');
  });
  threadManager.setDidUpdateWorkflowCallback(function(r) {
    var messages = _getColumnMessagesNode();
    scrollbar.scrollTo(messages.scrollHeight);
    JX.DOM.setContent(_getColumnTitleNode(), r.conpherence_title);
  });
  threadManager.start();

  JX.Stratcom.listen(
    'click',
    'conpherence-durable-column-header-action',
    function (e) {
      e.kill();
      var data = e.getNodeData('conpherence-durable-column-header-action');
      var action = data.action;
      var link = e.getNode('tag:a');
      var params = null;

      switch (action) {
        case 'go_edit':
          threadManager.runUpdateWorkflowFromLink(
            link,
            {
              action: action,
              force_ajax: true,
              stage: 'submit'
            });
          break;
        case 'add_person':
          threadManager.runUpdateWorkflowFromLink(
            link,
            {
              action: action,
              stage: 'submit'
            });
          break;
          break;
      }
    });

  JX.Stratcom.listen('resize', null, _drawColumn);

  function _getColumnBodyNode() {
    var column = JX.$('lobby-chat-container');
    return JX.DOM.find(
      column,
      'div',
      'lobby-chat');
  }

  function _getColumnMessagesNode() {
    var column = JX.$('lobby-chat-container');
    return JX.DOM.find(
      column,
      'div',
      'conpherence-durable-column-transactions');
  }

  function _getColumnTitleNode() {
    var column = JX.$('lobby-chat-container');
    return JX.DOM.find(
      column,
      'div',
      'conpherence-durable-column-header-text');
  }

  function _markLoading(loading) {
    var column = _getColumnNode();
    JX.DOM.alterClass(column, 'loading', loading);
  }

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

  _drawColumn();
});
