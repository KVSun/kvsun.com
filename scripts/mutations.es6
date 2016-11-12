import $ from './std-js/zq.es6';
import handleJSON from './std-js/json_response.es6';
import {reportError, parseResponse, query, fullScreen} from './std-js/functions.es6';
import supports from './std-js/support_test.es6';
import {
	sameoriginFrom,
	submitForm,
	getDatalist,
	getContextMenu,
	// updateFetchHistory,
	matchPattern,
	matchInput,
	getLink,
	toggleDetails,
	toggleCheckboxes,
	closeOnEscapeKey,
	closeOnOutsideClick,
	confirmDialogClose
} from './eventHandlers.es6';

function pictureShim(picture) {
	if ('matchMedia' in window) {
		let sources = picture.querySelectorAll('source[media][srcset]');
		for (let n = 0; n < sources.length; n++) {
			if (matchMedia(sources[n].getAttribute('media')).matches) {
				picture.getElementsByTagName('img')[0].src = sources[n].getAttribute('srcset');
				break;
			}
		}
	} else {
		picture.getElementsByTagName('img')[0].src = picture.querySelector('source[media][srcset]').getAttribute('srcset');
	}
}

function toggleFullScreen(){
	if (fullScreen) {
		document.cancelFullScreen();
	} else {
		document.querySelector(this.dataset.fullscreen).requestFullScreen();
	}
}

export const watcher = {
	childList: function() {
		$(this.addedNodes).bootstrap();
		if ($(this.removedNodes).some(node => node.tagName === 'DIALOG')) {
			document.body.removeEventListener('click', closeOnOutsideClick);
			document.body.removeEventListener('keypress', closeOnEscapeKey);
		}
	},
	attributes: function() {
		switch (this.attributeName) {
		case 'contextmenu':
			var menu = this.target.getAttribute('contextmenu');
			if (this.oldValue !== '') {
				$(`menu#${this.oldValue}`).remove();
			}
			if (menu && menu !== '') {
				if (!$('menu#' + menu).found) {
					fetch(document.baseURI, {
						method: 'POST',
						headers: new Headers({Accept: 'application/json'}),
						body: new URLSearchParams(`load_menu=${menu.replace(/\_menu$/, '')}`),
						credentials: 'include'
					}).then(parseResponse).then(handleJSON).catch(reportError);
				}
			}
			break;

		case 'open':
			if (this.target.tagName === 'DIALOG') {
				if (this.target.hasAttribute('open')) {
					setTimeout(() => {
						$(document.body).click(closeOnOutsideClick).keypress(closeOnEscapeKey);
					}, 500);
				} else {
					document.body.removeEventListener('click', closeOnOutsideClick);
					document.body.removeEventListener('keypress', closeOnEscapeKey);
				}
			}
			break;

		default:
			console.error(`Unhandled attribute in watch: "${this.attributeName}"`);
		}
	}
};

export const config = [
	'subtree',
	'attributeOldValue'
];

export const attributeTree = [
	'contextmenu',
	'list',
	'open'
];

export function bootstrap() {
	'use strict';
	this.each(function(node) {
		if (node.nodeType !== 1) {
			return this;
		}
		if (!supports('details')) {
			query('details > summary', node).forEach(summary => {
				summary.addEventListener('click', toggleDetails);
			});
		}
		if (supports('menuitem')) {
			query('[contextmenu]', node).forEach(getContextMenu);
		}
		if (supports('datalist')) {
			query('[list]', node).forEach(getDatalist);
		}
		if (!supports('picture')) {
			query('picture', node).forEach(pictureShim);
		}
		query('[autofocus]', node).forEach(input => input.focus());
		query(
			'a[href]:not([target="_blank"]):not([download]):not([href*="\#"])',
			node
		).filter(link => link.origin === location.origin).forEach(a => {
			a.addEventListener('click', getLink);
		});
		query('form[name]', node).filter(sameoriginFrom).forEach(form => {
			form.addEventListener('submit', submitForm);
		});
		query('[data-show]', node).forEach(el => {
			el.addEventListener('click', () => {
				document.querySelector(el.dataset.show).show();
			});
		});
		query('[data-show-modal]', node).forEach(el => {
			el.addEventListener('click', () => {
				document.querySelector(el.dataset.showModal).showModal();
			});
		});
		query('[data-scroll-to]', node).forEach(el => {
			el.addEventListener('click', () => {
				document.querySelector(el.dataset.scrollTo).scrollIntoView();
			});
		});
		// query('[data-import]', node).forEach(el => {
		// 	el.HTMLimport();
		// });
		query('[data-close]', node).forEach(el => {
			el.addEventListener('click', () => {
				document.querySelector(el.dataset.close).close();
			});
		});
		query('fieldset button[type="button"].toggle', node).forEach(toggle => {
			toggle.addEventListener('click', toggleCheckboxes);
		});
		query('[data-must-match]', node).forEach(matchPattern);
		// query('[data-dropzone]', node) .forEach(function (el) {
		// 	document.querySelector(el.dataset.dropzone).DnD(el);
		// });
		query('input[data-equal-input]', node).forEach(input => {
			input.addEventListener('input', matchInput);
		});
		// query('menu[type="context"]', node).forEach(WYSIWYG);
		// query('[data-request]', node).forEach(el => {
		// 	el.addEventListener('click', click => {
		// 		click.preventDefault();
		// 		if (!(el.dataset.hasOwnProperty('confirm')) || confirm(el.dataset.confirm)) {
		// 			let url = new URL(el.dataset.url || document.baseURI);
		// 			let headers = new Headers();
		// 			let body = new URLSearchParams(el.dataset.request);
		// 			headers.set('Accept', 'application/json');
		// 			if ('prompt' in el.dataset) {
		// 				body.set('prompt_value', prompt(el.dataset.prompt));
		// 			}
		// 			fetch(url, {
		// 				method: 'POST',
		// 				headers,
		// 				body,
		// 				credentials: 'include'
		// 			}).then(parseResponse).then(handleJSON).catch(reportError);
		// 		}
		// 	});
		// });
		// query('[data-dropzone]', node).forEach(finput => {
		// 	document.querySelector(finput.dataset.dropzone).DnD(finput);
		// });
		query('[data-fullscreen]', node).forEach(el => {
			el.addEventListener('click', toggleFullScreen);
		});
		query('[data-delete]', node).forEach(function(el) {
			el.addEventListener('click', () => {
				let target = $(el.dataset.delete);
				target.each(el => {
					if (confirmDialogClose(el)) {
						try {
							if (el.nextElementSibling.matches('.backdrop')) {
								el.nextElementSibling.remove();
							}
							el.remove();
						} catch(e) {
							el.remove();
						}
					}
				});
			});
		});
	});
	return this;
}
