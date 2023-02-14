/**
 * @provides javelin-behavior-dark-mode-toggle
 * @requires javelin-behavior
 *           javelin-util
 *           javelin-dom
 *           javelin-stratcom
 */

JX.behavior('dark-mode-toggle', function(config, statics) {
  if (statics.initialized) {
    return;
  } else {
    statics.initialized = true;
  }

  var toggleSwitch = JX.$('theme-switch');
  var indicator = JX.$('theme-indicator-container');
  var indicatoricon = JX.$('theme-indicator');

  var current_request;

  function _setIndicator() {
    JX.DOM.alterClass(indicator, ' checked ', toggleSwitch.checked);
    JX.DOM.alterClass(indicatoricon, ' fa-sun-o ', !toggleSwitch.checked);
    JX.DOM.alterClass(indicatoricon, ' fa-moon-o ', toggleSwitch.checked);
  }

  function _reloadCss() {
    var oldTheme = toggleSwitch.checked ? 'defaultX' : 'darkmodeX';
    var newTheme = toggleSwitch.checked ? 'darkmodeX' : 'defaultX';
    for (var link of document.querySelectorAll("link[rel=stylesheet]")) {
      link.href = link.href
                  .replace(oldTheme, newTheme)
                  .replace(/\?.*|$/, "?" + Date.now());
    }

    JX.Stratcom.invoke('resize');
  }

  function _savePreference() {
    var value = toggleSwitch.checked ? 'darkmode' : 'default';
    if (current_request) {
      current_request.abort();
      current_request = null;
    }

    current_request = new JX.Request(config.uri, function(r) {
      console.log(r);
    }).setData({key:'resource-postprocessor',value:value});
    current_request.send();
  }

  JX.Stratcom.listen('click', 'theme-switch', function(e) {
    toggleSwitch.checked = !toggleSwitch.checked;

    _setIndicator();
    _reloadCss();
    _savePreference();
  });


  _setIndicator();
});
