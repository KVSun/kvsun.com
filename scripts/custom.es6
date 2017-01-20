import polyfill from './std-js/polyfills.es6';
import {supportsAsClasses} from './std-js/support_test.es6';
import {default as $, zQ} from './std-js/zq.es6';
import * as mutations from './mutations.es6';
import {popstate} from './kvsapi.es6';

zQ.prototype.bootstrap = mutations.bootstrap;

polyfill();

supportsAsClasses('svg', 'audio', 'video', 'picture', 'canvas', 'menuitem',
'details', 'dialog', 'dataset', 'HTMLimports', 'classList', 'connectivity',
'visibility', 'notifications', 'ApplicationCache', 'indexedDB',
'localStorage', 'sessionStorage', 'CSSgradients', 'transitions',
'animations', 'CSSvars', 'CSSsupports', 'CSSmatches', 'querySelectorAll',
'workers', 'promises', 'ajax', 'FormData');
window.addEventListener('popstate', popstate);
$(document.body).bootstrap().watch(
	mutations.watcher,
	mutations.config,
	mutations.attributeTree
);
