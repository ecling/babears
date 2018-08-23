(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var _utils = require('./utils.js');

var _container = require('./diaFlow/container.jsx');

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

  // liyuhk:following closure will be removed when I land this, currently it is
  // here because want to use this for helping DIA team testing with their sandbox
  (function () {
    var QueryString = function () {
      // This function is anonymous, is executed immediately and
      // the return value is assigned to QueryString!
      var query_string = {};
      var query = window.location.search.substring(1);
      var vars = query.split("&");
      for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        // If first entry with this name
        if (typeof query_string[pair[0]] === "undefined") {
          query_string[pair[0]] = decodeURIComponent(pair[1]);
          // If second entry with this name
        } else if (typeof query_string[pair[0]] === "string") {
          var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
          query_string[pair[0]] = arr;
          // If third or later entry with this name
        } else {
          query_string[pair[0]].push(decodeURIComponent(pair[1]));
        }
      }
      return query_string;
    }();
    if (QueryString.p) {
      window.facebookAdsToolboxConfig.popupOrigin = QueryString.p;
      window.facebookAdsToolboxConfig.devEnv = true;
    }
  })();

  ReactDOM.render(React.createElement(_container2.default, null), document.getElementById('dia-flow'));
});

},{"./diaFlow/container.jsx":2,"./utils.js":5}],2:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; }; /**
                                                                                                                                                                                                                                                   * Copyright (c) 2016-present, Facebook, Inc.
                                                                                                                                                                                                                                                   * All rights reserved.
                                                                                                                                                                                                                                                   *
                                                                                                                                                                                                                                                   * This source code is licensed under the BSD-style license found in the
                                                                                                                                                                                                                                                   * LICENSE file in the root directory of this source tree. An additional grant
                                                                                                                                                                                                                                                   * of patent rights can be found in the PATENTS file in the code directory.
                                                                                                                                                                                                                                                   */


var _utils = require('../utils.js');

var _modal = require('../ui/modal.jsx');

var _modal2 = _interopRequireDefault(_modal);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var DiaFlowContainer = React.createClass({
  displayName: 'DiaFlowContainer',


  diaConfig: null,
  popupWindow: null,
  modalMessage: null,

  getInitialState: function getInitialState() {
    return {
      diaSettingId: window.facebookAdsToolboxConfig.diaSettingId,
      showAdvancedOptions: false,
      showModal: false
    };
  },
  bindMessageEvents: function bindMessageEvents(callback) {
    if ((0, _utils.isIE)() && window.MessageChannel) {
      // do nothing, wait for our messaging utils ready
    } else {
      window.addEventListener('message', function (event) {
        if (window.facebookAdsToolboxConfig.devEnv) {
          console.log('get event', event);
        }
        var origin = event.origin || event.originalEvent.origin;
        if ((0, _utils.urlFromSameDomain)(origin, window.facebookAdsToolboxConfig.popupOrigin)) {
          _utils.togglePopupOriginWeb(origin);
          callback && callback(event.data);
        }
      }, false);
    }
  },
  _showModal: function _showModal(msg) {
    if (msg && msg.trim().length > 0) {
      this.modalMessage = msg;
      this.setState({ showModal: true });
    }
  },


  // To check the protocol we used here, go to: https://fb.quip.com/FENaAXmOYyJn
  onEvent: function onEvent(evdata) {
    var _this = this;

    var evswitch = {
      'get dia settings': function getDiaSettings(params) {
        _this.sendDiaConfigToPopup();
      },

      'set merchant settings': function setMerchantSettings(params) {
        if (!params.setting_id) {
          console.error('Facebook Ads Extension Error: find no merchant settings', params);
          return;
        }
        var payload = { diaSettingId: params.setting_id };
        new Ajax.Request(window.facebookAdsToolboxAjax.setDiaSettingId, {
          parameters: {
            diaSettingId: payload.diaSettingId
          },
          onSuccess: function onSuccess() {
            _this.setState({ diaSettingId: payload.diaSettingId });
            _this.ackToPopup('set merchant settings', params);
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('set merchant settings', params);
          }
        });
      },

      'set pixel': function setPixel(params) {
        if (!params.pixel_id) {
          console.error('Facebook Ads Extension Error: got no pixel_id', params);
          return;
        }
        new Ajax.Request(window.facebookAdsToolboxAjax.setPixelId, {
          parameters: {
            pixelId: params.pixel_id,
            pixelUsePii: params.pixel_use_pii
          },
          onSuccess: function onSuccess(transport) {
            var response = transport.responseText.evalJSON();
            var msg = '';
            if (response.success) {
              window.setCurrentPixelId = response.pixelid;
              msg = "The Facebook Pixel with ID: " + response.pixelId + " is now installed on your website.";
            } else {
              msg = "There was a problem saving the pixel. Please try again";
            }
            if (window.facebookAdsToolboxConfig.devEnv) {
              _this._showModal(msg);
            }
            _this.ackToPopup('set pixel', params);
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('set pixel', params);
          }
        });
      },

      'gen feed': function genFeed(params) {
        new Ajax.Request(window.facebookAdsToolboxAjax.generateFeedNow, {
          parameters: {},
          onSuccess: function onSuccess(transport) {
            var response = transport.responseText.evalJSON();
            if (response.success) {
              _this.ackToPopup('feed', params);
            } else {
              _this.failAckToPopup('feed', params);
            }
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('feed', params);
          }
        });
      }
    };

    if (evdata !== null && (typeof evdata === 'undefined' ? 'undefined' : _typeof(evdata)) === 'object' && evdata.type) {
      evswitch[evdata.type] && evswitch[evdata.type](evdata.params);
    } else {
      console.error('Facebook Ads Extension Error: get unsupport msg:', evdata);
    }
  },
  ackToPopup: function ackToPopup(type, params) {
    this.popupWindow.postMessage({
      type: 'ack ' + type,
      params: params
    }, window.facebookAdsToolboxConfig.popupOrigin);
  },
  failAckToPopup: function failAckToPopup(type, params) {
    this.popupWindow.postMessage({
      type: 'fail ' + type,
      params: params
    }, window.facebookAdsToolboxConfig.popupOrigin);
  },
  sendDiaConfigToPopup: function sendDiaConfigToPopup() {
    this.popupWindow.postMessage({
      type: 'dia settings',
      params: this.diaConfig
    }, window.facebookAdsToolboxConfig.popupOrigin);
  },
  openPopup: function openPopup() {
    if (!this.state.diaSettingId && window.facebookAdsToolboxConfig.feed.totalVisibleProducts < 10000) {
      new Ajax.Request(window.facebookAdsToolboxAjax.generateFeedNow, {
        parameters: {useCache : true},
        onSuccess: function onSuccess() {}
      });
    }

    var width = 1153;
    var height = 808;
    var topPos = screen.height / 2 - height / 2;
    var leftPos = screen.width / 2 - width / 2;
    var originParam = window.location.protocol + '//' + window.location.host;
    var popupUrl = window.facebookAdsToolboxConfig.popupOrigin;

    if (this.popupWindow) {
      this.popupWindow.close();
    }

    this.popupWindow = window.open(popupUrl + '?origin=' + encodeURIComponent(originParam) + (this.state.diaSettingId ? '&merchant_settings_id=' + this.state.diaSettingId : ''), 'DiaWizard', ['toolbar=no', 'location=no', 'directories=no', 'status=no', 'menubar=no', 'scrollbars=no', 'resizable=no', 'copyhistory=no', 'width=' + width, 'height=' + height, 'top=' + topPos, 'left=' + leftPos].join(','));
  },
  launchDiaWizard: function launchDiaWizard() {
    this.diaConfig = { 'clientSetup': window.facebookAdsToolboxConfig };
    this.openPopup();
  },
  closeModal: function closeModal() {
    this.setState({ showModal: false });
  },
  componentDidMount: function componentDidMount() {
    this.bindMessageEvents(this.onEvent);
  },
  selectorOnChange: function selectorOnChange() {
    var sel = document.getElementById('fbStoreSelector');
    var new_store_id = sel.options[sel.selectedIndex].value;

    // Submit a request to the controller to update the store id
    var loc = window.location.pathname + 'store_id/' + new_store_id + '/';

    // This isn't bound when ajax call returns
    var fbWindow = this;
    new Ajax.Request(window.facebookAdsToolboxAjax.setStoreId, {
      parameters: {
        storeId: new_store_id
      },
      onSuccess: function onSuccess(transport) {
        var response = transport.responseText.evalJSON();
        // Update product count in the popup
        window.facebookAdsToolboxConfig.feed.totalVisibleProducts =
          response.product_count;
        window.facebookAdsToolboxConfig.defaultStoreId = new_store_id;

        if (fbWindow) {
          fbWindow.sendDiaConfigToPopup();
          const params = {
            storeId: new_store_id
          }
          fbWindow.ackToPopup('set store id', params);
        }
      },
      onFailure: function onFailure(message) {
        if (fbWindow) {
          const failParams = {
            exception: message.transport.responseText,
            storeId: new_store_id
          }
          fbWindow.failAckToPopup('set store id', failParams);
        }
      }
    });

  },
  showAdvancedOptions: function showAdvancedOptions(e) {
    if (!this.state.showAdvancedOptions) {
      document.getElementById('fbAdvancedOptions').show();
    } else {
      document.getElementById('fbAdvancedOptions').hide();
    }
    this.setState({showAdvancedOptions: !this.state.showAdvancedOptions});
  },


  render: function render() {
    var currentDiaSettingId = this.state.diaSettingId ? React.createElement(
      'h2',
      null,
      'Your Facebook Store ID: ',
      this.state.diaSettingId
    ) : '';

    // Add store options
    const options = [];
    const stores = JSON.parse(window.facebookAdsToolboxConfig.stores);
    const default_id = window.facebookAdsToolboxConfig.defaultStoreId

    Object.keys(stores).forEach(function(key, index) {
      var optionValues = { value: stores[key] };
      if (default_id === stores[key]) {
          optionValues.selected = "selected";
      }
      options.push(React.createElement("option", optionValues, key));
    });

    var storeSelector = React.createElement(
      'select',
      {id: 'fbStoreSelector', onChange: this.selectorOnChange},
      options
    );

    var advancedOptionsText = (this.state.showAdvancedOptions ? 'Hide' : 'Show') + ' Advanced Options';
    var advancedOptionsLink = React.createElement(
      'a',
      {onClick: this.showAdvancedOptions},
      advancedOptionsText
    );

    var advancedOptions = React.createElement(
      'div',
      {id: 'fbAdvancedOptions', style: {display: 'none'}},
      React.createElement(
        'h2',
        null,
        'Store Synced with Facebook'
      ),
      storeSelector
    );

    var feedWritePermissionError = window.facebookAdsToolboxConfig.feedWritePermissionError;
    var modal = this.state.showModal ? React.createElement(_modal2.default, { onClose: this.closeModal, message: this.modalMessage }) : null;
    return React.createElement(
      'div',
      { className: 'dia-flow-container' },
      modal,
      React.createElement(
        'h1',
        null,
        'Turn your products into ads on Facebook'
      ),
      React.createElement(
        'h2',
        null,
        'Easily install a pixel and create a product catalog on Facebook to sell more of your products. Use the pixel to build the right audience and measure the return on your ad spend. Promote all your products at once with your catalog instead of having to create individual ads.'
      ),
      currentDiaSettingId,
      React.createElement(
        'div',
        null,
        React.createElement(
          'center',
          null,
          (!feedWritePermissionError) ? React.createElement(
            'button',
            { className: 'blue', onClick: this.launchDiaWizard },
            this.state.diaSettingId ? 'Manage Settings' : 'Get Started'
          )
          :
          React.createElement(
            'h2',
            {style: {color: 'red'}},
            'Please enable write permissions in the ',
            feedWritePermissionError,
            ' directory to use this extension.'
          )
        )
      ),
      advancedOptionsLink,
      advancedOptions
    );
  }
});

exports.default = DiaFlowContainer;

},{"../ui/modal.jsx":4,"../utils.js":5}],3:[function(require,module,exports){
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

},{"../ui/modal.jsx":4}],4:[function(require,module,exports){
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

},{}],5:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.onPageLoad = onPageLoad;
exports.isIE = isIE;
exports.parseURL = parseURL;
exports.urlFromSameDomain = urlFromSameDomain;
exports.safeJSONParse = safeJSONParse;
exports.togglePopupOriginWeb = togglePopupOriginWeb;

var _ieOverlay = require('./fb/ieOverlay.jsx');

var _ieOverlay2 = _interopRequireDefault(_ieOverlay);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/*
(function () {
  Ajax._getTransport = Ajax.getTransport;
  Ajax._timeout = 30 * 60 * 1000; // 30min
  Ajax.getTransport = function () {
    var t = Ajax._getTransport();
    t.timeout = Ajax._timeout;
    return t;
  };
})();
*/ /**
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
  var u1host = u1.host.replace('web.', 'www.');
  var u2host = u2.host.replace('web.', 'www.');
  return u1.protocol === u2.protocol && u1host === u2host;
};

function safeJSONParse(jsonstr) {
  try {
    return JSON.parse(jsonstr);
  } catch (e) {
    console.log('Failed parse jsonstr:' + jsonstr);
    return undefined;
  }
};

function togglePopupOriginWeb(dia_origin) {
  var current_origin = window.facebookAdsToolboxConfig.popupOrigin;
  if (dia_origin.includes('web.') && !current_origin.includes('web.')) {
    window.facebookAdsToolboxConfig.popupOrigin = current_origin.replace('www.', 'web.');
  } else if (!dia_origin.includes('web.') && current_origin.includes('web.')) {
    window.facebookAdsToolboxConfig.popupOrigin = current_origin.replace('web.', 'www.');
  }
};

},{"./fb/ieOverlay.jsx":3}]},{},[1]);
