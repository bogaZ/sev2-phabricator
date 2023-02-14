/**
 * @provides javelin-behavior-prepare-side-menu
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-util
 *           javelin-workflow
 *           javelin-stratcom
 *           conpherence-thread-manager
 */

JX.behavior('prepare-side-menu', function(config, statics) {

  var current_request;
  var content;

  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
    NodeList.prototype.forEach = Array.prototype.forEach;
  }

  function _showSidebar() {
    var node = JX.$('lobby-container');
    JX.DOM.alterClass(node, 'show-lobby-sidebar', true);
    JX.Stratcom.invoke('resize');
  }

  function _hideSidebar() {
    var node = JX.$('lobby-container');
    JX.DOM.alterClass(node, 'show-lobby-sidebar', false);
    JX.Stratcom.invoke('resize');
  }

  function _showLoading() {
    var body = JX.$('lobby-utility-body');
    JX.DOM.setContent(body, JX.$H(`<span class="lobby-utility-loading">Loading...</span>`));
  }

  function _showError() {
    return JX.$H(`<div class="phui-info-view phui-info-severity-warning
     grouped phui-info-has-icon ">
      <div class="phui-info-view-icon">
        <span class="visual-only phui-icon-view phui-font-fa
        fa-exclamation-triangle phui-info-icon" data-meta="0_3" aria-hidden="true">
        </span>
      </div>
    <h1 class="phui-info-view-head">Not good, something is not quite right.</h1></div>`)
  }

  function _showContent() {
    var body = JX.$('lobby-utility-body');
    if (content) {
      JX.DOM.setContent(body, JX.$H(content));
    } else {
      JX.DOM.setContent(body, _showError());
    }
  }

  /**
   * Handle sidebar navigation
   */
  JX.Stratcom.listen(
    ['click'],
    'lobby-sidebar-menu-item',
    function (e) {
      e.kill();

      var current_menu = e.getNodes()["tag:a"];
      var current_menu_item = e.getNodes()["tag:li"];
      var parent_menu = e.getNodes()["tag:ul"];
      var menu_items = parent_menu.childNodes;
      var menu_uri = current_menu.href;
      var uri = new URL(menu_uri);

      menu_items.forEach((menu_item) => {
        JX.DOM.alterClass(menu_item, 'phui-list-item-selected', false);
      });

      JX.DOM.alterClass(current_menu_item, 'phui-list-item-selected', true);

      if (!uri.pathname.startsWith('/lobby')) {
        _hideSidebar();
      } else {
        _showSidebar();

        if (current_request) {
          current_request.abort();
          current_request = null;
        }
        content = null;

        current_request = new JX.Request(menu_uri, function(r) {
          content = r.content;
        });
        current_request.listen('send', _showLoading);
        current_request.listen('finally', _showContent);
        current_request.send();
      }
    }
  );
});
