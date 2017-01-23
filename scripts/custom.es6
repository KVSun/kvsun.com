import polyfill from './std-js/polyfills.es6';
import {supportsAsClasses} from './std-js/support_test.es6';
import {default as $, zQ} from './std-js/zq.es6';
import * as mutations from './mutations.es6';
import {popstate} from './kvsapi.es6';
import PromisePolyfill from './es6-promise/dist/es6-promise.js';

if (!('Promise' in window)) {
	window.Promise = PromisePolyfill;
}

polyfill();

supportsAsClasses(
	'svg',
	'audio',
	'video',
	'picture',
	'canvas',
	'menuitem',
	'details',
	'dialog',
	'template',
	'dataset',
	'HTMLimports',
	'classList',
	'connectivity',
	'visibility',
	'notifications',
	'ApplicationCache',
	'indexedDB',
	'localStorage',
	'sessionStorage',
	'CSSgradients',
	'transitions',
	'animations',
	'CSSvars',
	'CSSsupports',
	'CSSmatches',
	'querySelectorAll',
	'workers',
	'promises',
	'ajax',
	'FormData'
);

zQ.prototype.bootstrap = mutations.bootstrap;

$(document.body).bootstrap().watch(
	mutations.watcher,
	mutations.config,
	mutations.attributeTree
);

window.addEventListener('popstate', popstate);
