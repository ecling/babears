(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _modal = require('../ui/modal.jsx');

var _modal2 = _interopRequireDefault(_modal);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var IEOverlay = function () {

  var Overlay = React.createClass({
    displayName: 'Overlay',

    render: function render() {
      var overLayStyles = {
        width: '600px',
        height: '150px',
        position: 'relative',
        top: '50%',
        left: '50%',
        marginTop: '-75px',
        marginLeft: '-300px',
        backgroundColor: 'white',
        textAlign: 'center',
        fontFamily: 'helvetica, arial, sans-serif',
        zIndex: '11'
      };

      var h1Styles = {
        fontSize: '24px',
        lineHeight: '28px',
        color: '#141823',
        fontWeight: 'normal',
        paddingTop: '44px'
      };

      var h2Styles = {
        fontSize: '14px',
        lineHeight: '20px',
        color: '#9197a3',
        fontWeight: 'normal'
      };

      return React.createElement(
        'div',
        { style: overLayStyles, id: 'ieOverlay' },
        React.createElement(
          'h1',
          { style: h1Styles },
          'Internet Explorer Not Supported'
        ),
        React.createElement(
          'h2',
          { style: h2Styles },
          'Please use a modern browser such as Google Chrome or Mozilla Firefox'
        )
      );
    }
  });

  return {
    render: function render() {
      var containerId = 'page:main-container';
      var containerEl = document.getElementById(containerId);
      containerEl.style.position = 'relative';

      var ieContainer = document.createElement('div');
      ieContainer.id = 'ie-container';

      ieContainer.style.width = '100%';
      ieContainer.style.height = '100%';
      ieContainer.style.position = 'absolute';
      ieContainer.style.top = '0';
      ieContainer.style.left = '0';
      ieContainer.style.backgroundColor = 'rgba(0,0,0,0.3)';

      containerEl.appendChild(ieContainer);
      ReactDOM.render(React.createElement(Overlay, null), ieContainer);
    }
  };
}();

exports.default = IEOverlay;

},{"../ui/modal.jsx":12}],2:[function(require,module,exports){
'use strict';

var _utils = require('./utils.js');

var _container = require('./feedFlow/container.jsx');

var _container2 = _interopRequireDefault(_container);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

(0, _utils.onPageLoad)(function () {
  ReactDOM.render(React.createElement(_container2.default, {
    currentSetup: window.facebookAdsToolboxConfig.feedSetup }), document.getElementById('feed-flow'));
});

},{"./feedFlow/container.jsx":4,"./utils.js":13}],3:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _BUISwitch = require('../ui/BUISwitch.jsx');

var _BUISwitch2 = _interopRequireDefault(_BUISwitch);

var _tracking = require('../tracking.js');

var _lastrunlogs = require('./lastrunlogs.jsx');

var _lastrunlogs2 = _interopRequireDefault(_lastrunlogs);

var _generateitnow = require('./generateitnow.jsx');

var _generateitnow2 = _interopRequireDefault(_generateitnow);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var FeedAdvanceOptions = React.createClass({
  displayName: 'FeedAdvanceOptions',
  getInitialState: function getInitialState() {
    return {
      lastrunlogs: ''
    };
  },
  componentDidMount: function componentDidMount() {
    this.loadLastRunLogs();
  },
  onToggleEnabled: function onToggleEnabled(event) {
    var _this = this;

    var settings = {
      enabled: !this.props.currentSetup.enabled
    };
    new Ajax.Request(window.facebookAdsToolboxConfig.feedAjaxRoute, {
      method: 'post',
      parameters: settings,
      onSuccess: function onSuccess(transport) {
        (0, _tracking.fireFacebookPixelEvent)(_tracking.PIXEL_ID, settings.enabled ? _tracking.EVENTS.FEED_ON : _tracking.EVENTS.FEED_OFF);
        _this.props.updateCurrentSetup(settings);
      }
    });
  },
  onFormatChange: function onFormatChange(event) {
    var _this2 = this;

    var settings = {
      format: event.target.value
    };
    new Ajax.Request(window.facebookAdsToolboxConfig.feedAjaxRoute, {
      method: 'post',
      parameters: settings,
      onSuccess: function onSuccess(transport) {
        (0, _tracking.fireFacebookPixelEvent)(_tracking.PIXEL_ID, settings.format === 'TSV' ? _tracking.EVENTS.FEED_FORMAT_TSV : _tracking.EVENTS.FEED_FORMAT_XML);
        _this2.props.updateCurrentSetup(settings);
      }
    });
  },
  loadLastRunLogs: function loadLastRunLogs() {
    var _this3 = this;

    new Ajax.Request(window.facebookAdsToolboxConfig.feedLastRunLogsAjaxRoute, {
      method: 'get',
      parameters: {},
      onSuccess: function onSuccess(transport) {
        var response = transport.responseText.evalJSON();
        _this3.setState({ lastrunlogs: response.lastrunlogs });
      }
    });
  },
  render: function render() {
    var _this4 = this;

    return React.createElement(
      'div',
      { className: 'feed-advance-options' },
      React.createElement(
        'div',
        { className: 'form-group' },
        React.createElement(
          'label',
          null,
          'Feed File Auto-generation Enabled ?'
        ),
        React.createElement(_BUISwitch2.default, { value: this.props.currentSetup.enabled,
          showLabel: true,
          onToggle: this.onToggleEnabled })
      ),
      React.createElement(
        'div',
        { className: 'form-group' },
        React.createElement(
          'label',
          null,
          'Feed File Format'
        ),
        React.createElement(
          'form',
          null,
          React.createElement('input', { className: 'radio', type: 'radio', name: 'TSV', value: 'TSV',
            checked: this.props.currentSetup.format === 'TSV',
            onChange: this.onFormatChange }),
          React.createElement(
            'span',
            { className: 'radio-label' },
            'TSV'
          ),
          React.createElement('input', { className: 'radio', type: 'radio', name: 'XML', value: 'XML',
            checked: this.props.currentSetup.format === 'XML',
            onChange: this.onFormatChange }),
          React.createElement(
            'span',
            { className: 'radio-label' },
            'XML'
          )
        )
      ),
      React.createElement(
        'div',
        { className: 'form-group' },
        React.createElement(
          'label',
          null,
          'Last Generation Logs'
        ),
        React.createElement(_lastrunlogs2.default, { lastrunlogs: this.state.lastrunlogs })
      ),
      React.createElement(
        'div',
        { className: 'form-group' },
        React.createElement(_generateitnow2.default, {
          clearLastRunLogs: function clearLastRunLogs() {
            _this4.setState({ lastrunlogs: '' });
          },
          loadLastRunLogs: this.loadLastRunLogs })
      )
    );
  }
});

exports.default = FeedAdvanceOptions;

},{"../tracking.js":8,"../ui/BUISwitch.jsx":9,"./generateitnow.jsx":5,"./lastrunlogs.jsx":6}],4:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _advanceoptions = require('./advanceoptions.jsx');

var _advanceoptions2 = _interopRequireDefault(_advanceoptions);

var _oneclicksetup = require('./oneclicksetup.jsx');

var _oneclicksetup2 = _interopRequireDefault(_oneclicksetup);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var FeedFlowContainer = React.createClass({
  displayName: 'FeedFlowContainer',
  getInitialState: function getInitialState() {
    return {
      currentSetup: this.props.currentSetup,
      showAdvanceOptions: false
    };
  },
  updateCurrentSetup: function updateCurrentSetup(newSetup) {
    var newCurrentSetup = this.state.currentSetup;
    for (var key in newSetup) {
      newCurrentSetup[key] = newSetup[key];
    }
    this.setState({ currentSetup: newCurrentSetup });
  },
  showAdvanceOptions: function showAdvanceOptions() {
    this.setState({ showAdvanceOptions: !this.state.showAdvanceOptions });
  },
  getFeedUrl: function getFeedUrl() {
    var url = window.facebookAdsToolboxConfig.baseUrl + 'facebook_adstoolbox_product_feed.' + this.state.currentSetup.format.toLowerCase();
    if (window.location.protocol === 'http:') {
      url = url.replace(/^https/g, 'http');
    }
    return url;
  },
  render: function render() {
    var advanceoptions = null;
    if (this.state.showAdvanceOptions) {
      advanceoptions = React.createElement(
        'div',
        { className: 'show-advance-link' },
        React.createElement(
          'a',
          { href: '#', onClick: this.showAdvanceOptions },
          'Hide Advanced Options'
        ),
        React.createElement('hr', null),
        React.createElement(_advanceoptions2.default, {
          currentSetup: this.state.currentSetup,
          updateCurrentSetup: this.updateCurrentSetup })
      );
    } else {
      advanceoptions = React.createElement(
        'div',
        { className: 'show-advance-link' },
        React.createElement(
          'a',
          { href: '#', onClick: this.showAdvanceOptions },
          'Show Advanced Options'
        )
      );
    }

    var footnotes = [];
    footnotes.push(React.createElement(
      'li',
      null,
      ' In order to make this work, you may need to setup Magento\'s cron job correctly.',
      React.createElement(
        'a',
        { href: "http://devdocs.magento.com/guides/m1x/install/" + "installing_install.html?#install-cron" },
        ' How to setup?'
      )
    ));
    if (window.facebookAdsToolboxConfig.hasGzipSupport) {
      footnotes.push(React.createElement(
        'li',
        null,
        ' The gzipped copy is at:',
        React.createElement(
          'a',
          { href: this.getFeedUrl() + '.gz' },
          this.getFeedUrl() + '.gz'
        ),
        '.'
      ));
    }

    return React.createElement(
      'div',
      { className: 'feed-flow-container' },
      React.createElement(
        'div',
        { className: 'header' },
        React.createElement(
          'h2',
          null,
          'Automatically Generate a Product Feed'
        )
      ),
      React.createElement(_oneclicksetup2.default, {
        currentSetup: this.state.currentSetup,
        updateCurrentSetup: this.updateCurrentSetup,
        showAdvanceOptions: this.state.showAdvanceOptions,
        getFeedUrl: this.getFeedUrl }),
      advanceoptions,
      React.createElement('hr', null),
      React.createElement(
        'ol',
        { className: 'feed-footnote' },
        footnotes
      )
    );
  }
});

exports.default = FeedFlowContainer;

},{"./advanceoptions.jsx":3,"./oneclicksetup.jsx":7}],5:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _tracking = require('../tracking.js');

var FeedGenerateItNow = React.createClass({
  displayName: 'FeedGenerateItNow',
  onGenerate: function onGenerate() {
    var _this = this;

    this.props.clearLastRunLogs();
    new Ajax.Request(window.facebookAdsToolboxConfig.feedGenerateNowAjaxRoute, {
      method: 'post',
      parameters: {},
      onSuccess: function onSuccess(transport) {
        (0, _tracking.fireFacebookPixelEvent)(_tracking.PIXEL_ID, _tracking.EVENTS.FEED_ADHOC_GENERATION);
        _this.props.loadLastRunLogs();
      }
    });
  },
  render: function render() {
    return React.createElement(
      'button',
      { className: 'blue', onClick: this.onGenerate },
      'Generate Product Feed Now!'
    );
  }
}); /**
     * Copyright (c) 2016-present, Facebook, Inc.
     * All rights reserved.
     *
     * This source code is licensed under the BSD-style license found in the
     * LICENSE file in the root directory of this source tree. An additional grant
     * of patent rights can be found in the PATENTS file in the code directory.
     */

exports.default = FeedGenerateItNow;

},{"../tracking.js":8}],6:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var FeedLastRunLogs = React.createClass({
  displayName: "FeedLastRunLogs",
  render: function render() {
    return React.createElement("textarea", { rows: "8", value: this.props.lastrunlogs });
  }
});

exports.default = FeedLastRunLogs;

},{}],7:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _BUISwitch = require('../ui/BUISwitch.jsx');

var _BUISwitch2 = _interopRequireDefault(_BUISwitch);

var _tracking = require('../tracking.js');

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var FeedOneClickSetup = React.createClass({
  displayName: 'FeedOneClickSetup',
  oneClickSetup: function oneClickSetup() {
    var _this = this;

    if (this.props.currentSetup.enabled) {
      new Ajax.Request(window.facebookAdsToolboxConfig.feedAjaxRoute, {
        method: 'post',
        parameters: {
          enabled: false
        },
        onSuccess: function onSuccess(transport) {
          (0, _tracking.fireFacebookPixelEvent)(_tracking.PIXEL_ID, _tracking.EVENTS.FEED_OFF);
          _this.props.updateCurrentSetup({ enabled: false });
        }
      });
    } else {
      // If not enabled, post to enable it with default settings
      var defaultSettings = {
        enabled: true,
        format: 'TSV'
      };
      new Ajax.Request(window.facebookAdsToolboxConfig.feedAjaxRoute, {
        method: 'post',
        parameters: defaultSettings,
        onSuccess: function onSuccess(transport) {
          (0, _tracking.fireFacebookPixelEvent)(_tracking.PIXEL_ID, _tracking.EVENTS.FEED_ON);
          (0, _tracking.fireFacebookPixelEvent)(_tracking.PIXEL_ID, _tracking.EVENTS.FEED_FORMAT_TSV);
          _this.props.updateCurrentSetup(defaultSettings);
        }
      });
    }
  },
  render: function render() {
    var feedurl = this.props.getFeedUrl();

    var form = React.createElement(
      'form',
      null,
      React.createElement(
        'div',
        { className: 'form-group' },
        React.createElement(
          'label',
          null,
          'Automatically Generate my Product Feed'
        ),
        React.createElement(
          'span',
          { className: 'switch' },
          React.createElement(_BUISwitch2.default, { value: this.props.currentSetup.enabled,
            showLabel: true,
            onToggle: this.oneClickSetup })
        )
      )
    );

    return React.createElement(
      'div',
      { className: 'one-click-setup' },
      React.createElement(
        'p',
        null,
        'Automatically generate a Facebook Product Feed of your inventory that updates ',
        React.createElement(
          'b',
          null,
          'daily at 1 AM '
        ),
        React.createElement(
          'sup',
          null,
          '1'
        ),
        '.',
        React.createElement('br', null),
        'Your feed file will be avaliable at: ',
        React.createElement(
          'a',
          { href: feedurl },
          feedurl
        ),
        window.facebookAdsToolboxConfig.hasGzipSupport ? React.createElement(
          'sup',
          null,
          ' 2'
        ) : ''
      ),
      React.createElement(
        'a',
        { href: "https://developers.facebook.com/docs/marketing-api/" + "dynamic-product-ads/product-catalog#productfeed" },
        'Learn more about Product Feeds'
      ),
      this.props.showAdvanceOptions ? '' : form
    );
  }
});

exports.default = FeedOneClickSetup;

},{"../tracking.js":8,"../ui/BUISwitch.jsx":9}],8:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

exports.fireFacebookPixelEvent = fireFacebookPixelEvent;
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var PIXEL_ID = exports.PIXEL_ID = 1979385372287162;

var EVENTS = exports.EVENTS = {
  FEED_ON: 'facebookadstoolbox_feed_on',
  FEED_OFF: 'facebookadstoolbox_feed_off',
  FEED_FORMAT_TSV: 'facebookadstoolbox_feed_format_tsv',
  FEED_FORMAT_XML: 'facebookadstoolbox_feed_format_xml',
  FEED_ADHOC_GENERATION: 'facebookadstoolbox_feed_adhoc_generation'
};

// This will transform an array like
//   [['key1', 'value1'], ['key2', 'value2']]
// into string like
//   key1=value1&key2=value2
function qsEncode(array) {
  var output = [];
  for (var i = 0, len = array.length; i < len; i++) {
    // does not encode key, expects it to be clean or already encoded
    output.push(array[i][0] + '=' + encodeURIComponent(array[i][1]));
  }
  return output.join('&');
}

function fireFacebookPixelEvent(pixelId, eventName, params) {
  var FB_ENDPOINT = 'https://www.facebook.com/tr/';
  var currentUrl = location.href;
  var referrerUrl = document.referrer;
  var inIframe = window.top !== window;

  var tuples = [];
  tuples.push(['id', pixelId]);
  tuples.push(['ev', eventName]);
  tuples.push(['dl', currentUrl]);
  tuples.push(['rl', referrerUrl]);
  tuples.push(['if', inIframe]);
  // add timestamp; fixes issue where if two identical events are fired (the
  // second starts before the first finishes), the browser will send only the
  // first request and give the result to both
  tuples.push(['ts', new Date().valueOf()]);
  if (params && (typeof params === 'undefined' ? 'undefined' : _typeof(params)) === 'object') {
    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        var value = params[key];
        var type = value === null ? 'null' : typeof value === 'undefined' ? 'undefined' : _typeof(value);
        if (type in { number: 1, string: 1, boolean: 1 }) {
          // here we encode key because it could contain [ or ]
          // the value will be encoded in qsEncode
          tuples.push(['cd[' + encodeURIComponent(key) + ']', value]);
        } else if (type === 'object') {
          value = typeof JSON === 'undefined' ? String(value) : JSON.stringify(value);
          tuples.push(['cd[' + encodeURIComponent(key) + ']', value]);
        }
      }
    }
  }

  var queryString = qsEncode(tuples);
  var image = new Image();
  image.src = FB_ENDPOINT + '?' + queryString;
}

},{}],9:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; /**
                                                                                                                                                                                                                                                                   * Copyright (c) 2016-present, Facebook, Inc.
                                                                                                                                                                                                                                                                   * All rights reserved.
                                                                                                                                                                                                                                                                   *
                                                                                                                                                                                                                                                                   * This source code is licensed under the BSD-style license found in the
                                                                                                                                                                                                                                                                   * LICENSE file in the root directory of this source tree. An additional grant
                                                                                                                                                                                                                                                                   * of patent rights can be found in the PATENTS file in the code directory.
                                                                                                                                                                                                                                                                   */

var _keys = require('./keys.js');

var _keys2 = _interopRequireDefault(_keys);

var _cx = require('./cx.js');

var _cx2 = _interopRequireDefault(_cx);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var PropTypes = React.PropTypes;

var BUISwitch = React.createClass({
  displayName: 'BUISwitch',

  propTypes: {
    /**
     * Whether to animate the switch when toggling it. Defaults to true.
     */
    animate: PropTypes.bool,
    /**
     * Whether the switch should be disabled (i.e. read-only)
     */
    disabled: PropTypes.bool,
    /**
     * Callback function which is called when the user clicks on the switch.
     * It is passed the new boolean value of the switch.
     */
    onToggle: PropTypes.func,
    /**
     * Whether to show the ON/OFF label to the right of the switch.
     */
    showLabel: PropTypes.bool,
    /**
     * The ON/OFF value of the switch.
     */
    value: PropTypes.bool.isRequired
  },

  getDefaultProps: function getDefaultProps() {
    return {
      animate: true
    };
  },
  render: function render() {
    return React.createElement(
      'div',
      _extends({}, this.props, {
        className: (0, _cx2.default)({
          'buiSwitch/root': true,
          'buiSwitch/active': this.props.value,
          'buiSwitch/inactive': !this.props.value,
          'buiSwitch/disabled': this.props.disabled,
          'buiSwitch/animated': this.props.animate
        }),
        role: 'checkbox',
        'aria-checked': this.props.value ? 'true' : 'false' }),
      React.createElement(
        'div',
        {
          className: (0, _cx2.default)('buiSwitch/background'),
          onClick: this._onClick,
          onKeyDown: this._onKeyDown,
          onMouseDown: this._preventFocus,
          tabIndex: this.props.disabled ? '-1' : '0' },
        React.createElement('div', { className: (0, _cx2.default)('buiSwitch/circle') })
      ),
      this._renderLabel()
    );
  },
  _renderLabel: function _renderLabel() {
    if (!this.props.showLabel) {
      return null;
    }

    return React.createElement(
      'span',
      { className: (0, _cx2.default)('buiSwitch/label') },
      this.props.value ? "ON" : "OFF"
    );
  },
  _onClick: function _onClick(event) {
    if (this.props.disabled) {
      return;
    }

    this.props.onToggle && this.props.onToggle(!this.props.value);
  },
  _onKeyDown: function _onKeyDown(event) {
    if (this.props.disabled) {
      return;
    }

    var keyCode = event.keyCode;
    if (keyCode === _keys2.default.RETURN || keyCode === _keys2.default.SPACE) {
      this.props.onToggle && this.props.onToggle(!this.props.value);
    }
  },
  _preventFocus: function _preventFocus(event) {
    event.preventDefault();
  }
});

exports.default = BUISwitch;

},{"./cx.js":10,"./keys.js":11}],10:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

/**
 * Copyright 2004-present Facebook. All Rights Reserved.
 *
 */

function cx_string(clsname) {
  // 'module/name' => 'module-name'
  return clsname.replace('/', '-');
}

function cx_object(obj) {
  /* obj is in the form of
    {
      'buiSwitch/root': true,
      'buiSwitch/active': this.props.value,
      'buiSwitch/inactive': !this.props.value,
      'buiSwitch/disabled': this.props.disabled,
      'buiSwitch/animated': this.props.animate,
    }
   */
  var clsnames = [];
  var clss = Object.keys(obj);
  for (var cls in obj) {
    var cond = obj[cls];
    if (cond) {
      clsnames.push(cx_string(cls));
    }
  }
  return clsnames.join(' ');
}

function cx(input) {
  if ((typeof input === 'undefined' ? 'undefined' : _typeof(input)) === 'object') {
    return cx_object(input);
  } else if (typeof input === 'string') {
    return cx_string(input);
  }
  return '';
}

exports.default = cx;

},{}],11:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
/**
 * Copyright 2004-present Facebook. All Rights Reserved.
 *
 */

exports.default = {
  BACKSPACE: 8,
  TAB: 9,
  RETURN: 13,
  ALT: 18,
  ESC: 27,
  SPACE: 32,
  PAGE_UP: 33,
  PAGE_DOWN: 34,
  END: 35,
  HOME: 36,
  LEFT: 37,
  UP: 38,
  RIGHT: 39,
  DOWN: 40,
  DELETE: 46,
  COMMA: 188,
  PERIOD: 190,
  A: 65,
  Z: 90,
  ZERO: 48,
  NUMPAD_0: 96,
  NUMPAD_9: 105
};

},{}],12:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

var Modal = React.createClass({
  displayName: 'Modal',

  render: function render() {
    return React.createElement(
      'div',
      { className: 'modal-container' },
      React.createElement(
        'div',
        { className: 'modal' },
        React.createElement(
          'div',
          { className: 'modal-header' },
          this.props.title
        ),
        React.createElement(
          'div',
          { className: 'modal-content' },
          this.props.message
        ),
        React.createElement(
          'div',
          { className: 'modal-close' },
          React.createElement(
            'button',
            { onClick: this.props.onClose, className: 'medium blue' },
            'OK'
          )
        )
      )
    );
  }
});

exports.default = Modal;

},{}],13:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.onPageLoad = onPageLoad;
exports.isIE = isIE;
exports.parseURL = parseURL;
exports.urlFromSameDomain = urlFromSameDomain;
exports.safeJSONParse = safeJSONParse;
exports.togglePopupOriginBusiness = togglePopupOriginBusiness;

var _ieOverlay = require('./fb/ieOverlay.jsx');

var _ieOverlay2 = _interopRequireDefault(_ieOverlay);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

(function () {
  Ajax._getTransport = Ajax.getTransport;
  Ajax._timeout = 30 * 60 * 1000; // 30min
  Ajax.getTransport = function () {
    var t = Ajax._getTransport();
    t.timeout = Ajax._timeout;
    return t;
  };

  console.log('[Facebook Ads Toolbox] hack Ajax.Request to support timeout complete.');
})(); /**
       * Copyright (c) 2016-present, Facebook, Inc.
       * All rights reserved.
       *
       * This source code is licensed under the BSD-style license found in the
       * LICENSE file in the root directory of this source tree. An additional grant
       * of patent rights can be found in the PATENTS file in the code directory.
       */

function onPageLoad(runfn) {
  if (!runfn) {
    return;
  }

  var display = function display() {
    if (isIE()) {
      _ieOverlay2.default.render();
    }
    runfn();
  };

  if (document.readyState === 'interactive') {
    // in case the document is already rendered
    display();
  } else if (document.addEventListener) {
    // modern browsers
    document.addEventListener('DOMContentLoaded', display);
  } else {
    document.attachEvent('onreadystatechange', function () {
      // IE <= 8
      if (document.readyState === 'complete') {
        display();
      }
    });
  }
};

function isIE() {
  return (/MSIE |Trident\/|Edge\//.test(window.navigator.userAgent)
  );
};

function parseURL(url) {
  var parser = document.createElement('a');
  parser.href = url;
  return parser;
};

function urlFromSameDomain(url1, url2) {
  var u1 = parseURL(url1);
  var u2 = parseURL(url2);
  return u1.protocol === u2.protocol && u1.host === u2.host;
};

function safeJSONParse(jsonstr) {
  try {
    return JSON.parse(jsonstr);
  } catch (e) {
    console.log('Failed parse jsonstr:' + jsonstr);
    return undefined;
  }
};

function togglePopupOriginBusiness(dia_origin) {
  var current_origin = window.facebookAdsToolboxConfig.popupOrigin;
  if (dia_origin.includes('business') && !current_origin.includes('business')) {
    window.facebookAdsToolboxConfig.popupOrigin = current_origin.replace('www', 'business');
  } else if (!dia_origin.includes('business') && current_origin.includes('business')) {
    window.facebookAdsToolboxConfig.popupOrigin = current_origin.replace('business', 'www');
  }
};

},{"./fb/ieOverlay.jsx":1}]},{},[2]);
