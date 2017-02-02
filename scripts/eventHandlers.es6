import $ from './std-js/zq.es6';
import handleJSON from './std-js/json_response.es6';
import {parseResponse} from './std-js/functions.es6';
import getPage from './kvsapi.es6';
import SocialShare from './std-js/socialshare.es6';

export function dataSahre() {
	if (this.dataset.hasOwnProperty('share')) {
		switch(this.dataset.share) {
		case 'facebook':
			SocialShare.openPopup(SocialShare.getFacebook());
			break;

		case 'twitter':

			SocialShare.openPopup(SocialShare.getTwitter(location.href, document.title, 'kvsun'));
			break;

		case 'g+':
			SocialShare.openPopup(SocialShare.getGooglePlus());
			break;

		case 'reddit':
			SocialShare.openPopup(SocialShare.getReddit());
			break;

		case 'pintrest':
			SocialShare.openPopup(SocialShare.getPintrest());
			break;

		case 'linkedin':
			SocialShare.openPopup(SocialShare.getLinkedIn());
			break;
		}
	}
}

export function dataClose() {
	document.querySelector(this.dataset.close).close();
}

export function dataScrollTo() {
	document.querySelector(this.dataset.scrollTo).scrollIntoView();
}

export function dataShow() {
	document.querySelector(this.dataset.show).show();
}

export function dataDelete() {
	$(this.dataset.delete).each(el => {
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
}


export function dataShowModal(click) {
	click.preventDefault();
	if (this.dataset.hasOwnProperty('showModal')) {
		document.querySelector(this.dataset.showModal).showModal();
	}
}

export async function dataRequest(click) {
	click.preventDefault();
	if (!(this.dataset.hasOwnProperty('confirm')) || confirm(this.dataset.confirm)) {
		let url = new URL('api.php', location.origin);
		let headers = new Headers();
		url.search = `?${this.dataset.request}`;
		headers.set('Accept', 'application/json');
		if (this.dataset.hasOwnProperty('prompt')) {
			url.searchParams.set('prompt_value', prompt(this.dataset.prompt));
		}
		try {
			let resp = await fetch(url, {
				method: 'GET',
				headers,
				credentials: 'include'
			});
			let json = await parseResponse(resp);
			handleJSON(json);
		} catch(e) {
			console.error(e);
		}
	}
}

export async function dataLoadForm(click) {
	click.preventDefault();
	let url = new URL('api.php', location.origin);
	let headers = new Headers();
	url.searchParams.set('load_form', this.dataset.loadForm);
	headers.set('Accept', 'application/json');
	try {
		let resp = await fetch(url, {
			method: 'GET',
			headers,
			credentials: 'include'
		});
		let json = await parseResponse(resp);
		handleJSON(json);
	} catch(e) {
		console.error(e);
	}
}

export async function submitForm(submit) {
	submit.preventDefault();
	let els = Array.from(this.querySelectorAll('fieldset, button'));
	if (!(this.dataset.hasOwnProperty('confirm')) || confirm(this.dataset.confirm)) {
		let body = new FormData(this);
		let headers = new Headers();
		let url = new URL(this.action, location.origin);
		// body.append('nonce', sessionStorage.getItem('nonce'));
		body.append('form', this.name);
		$(`form[name="${this.name}"] [data-input-name]`).each(input => {
			body.append(input.dataset.inputName, input.innerHTML);
		});
		els.forEach(el => el.disabled = true);
		headers.set('Accept', 'application/json');
		try {
			let resp = await fetch(url, {
				method: submit.target.method,
				headers,
				body,
				credentials: 'include'
			});
			let json = await parseResponse(resp);
			handleJSON(json);
		} catch(e) {
			console.error(e);
		} finally {
			els.forEach(el => el.disabled = false);
		}
	}
}

export function sameoriginFrom(form) {
	return new URL(form.action).origin === location.origin;
}

export async function getDatalist(list) {
	if (!$('#' + list.getAttribute('list')).found) {
		let url = new URL('api.php', document.baseURI);
		let headers = new Headers();
		headers.set('Accept', 'application/json');
		url.searchParams.set('datalist', list.getAttribute('list'));
		try {
			let resp = await fetch(url, {
				method: 'GET',
				headers,
				credentials: 'include'
			});
			let json = await parseResponse(resp);
			handleJSON(json);
		} catch(e) {
			console.error(e);
		}
	}
}

export async function getContextMenu(el) {
	let menu = el.getAttribute('contextmenu');
	if (menu && menu !== '') {
		if (!$(`menu#${menu}`).found) {
			let headers = new Headers();
			let url = new URL('api.php', document.baseURI);
			url.searchParams.set('load_menu', menu.replace(/\_menu$/, ''));
			headers.set('Accept', 'application/json');
			try {
				let resp = await fetch(url, {
					method: 'GET',
					headers,
					credentials: 'include'
				});
				let json = await parseResponse(resp);
				handleJSON(json);
			} catch(e) {
				console.error(e);
			}
		}
	}
}

export function matchPattern(match) {
	match.pattern = new RegExp(document.querySelector(`[name="${match.dataset.mustMatch}"]`).value).escape();
	document.querySelector(`[name="${match.dataset.mustMatch}"]`).addEventListener('change', change => {
		document.querySelector(`[data-must-match="${change.target.name}"]`).pattern = new RegExp(change.target.value).escape();
	});
}

export function matchInput(input) {
	$(`input[data-equal-input="${input.target.dataset.equalInput}"]`).each(other => {
		if (other !== input) {
			other.value = input.value;
		}
	});
}

export function getLink(click) {
	click.preventDefault();
	if (this.classList.contains('disabled') || this.pathname === location.pathname) {
		return;
	} else {
		this.classList.add('disabled');
	}

	getPage(this.href).then(() => {
		this.classList.remove('disabled');
	}).catch(error => {
		this.classList.remove('disabled');
		console.error(error);
	});
}

export function toggleDetails() {
	if (this.parentElement.hasAttribute('open')) {
		this.parentElement.close();
	} else {
		this.parentElement.open();
	}
}

export function toggleCheckboxes() {
	let fieldset = this.closest('fieldset');
	let checkboxes = Array.from(fieldset.querySelectorAll('input[type="checkbox"]'));
	checkboxes.forEach(checkbox => {
		checkbox.checked = !checkbox.checked;
	});
}

export  function closeOnOutsideClick(click) {
	if (! click.target.matches('dialog, dialog *')) {
		$('dialog[open]:first-of-type').each(autoCloseDialog);
	}
}

export function confirmDialogClose(dialog) {
	if ($(dialog.childNodes).some(node => node.tagName === 'FORM')) {
		return confirm('This will cause you to lose any data entered in the form. Continue?');
	}
	return true;
}

export function autoCloseDialog(dialog) {
	if (confirmDialogClose(dialog)) {
		if ($(dialog.childNodes).some(node =>
			node.dataset.hasOwnProperty('delete')
			&& node.dataset.delete === `#${dialog.id}`
		)) {
			try {
				if (dialog.nextElementSibling.matches('.backdrop')) {
					dialog.nextElementSibling.remove();
					dialog.remove();
				}
			} catch(e) {
				dialog.remove();
			}
		} else {
			dialog.close();
		}
	}
}

export function closeOnEscapeKey(keypress) {
	if (keypress.key === 'Escape') {
		$('dialog[open]:first-of-type').each(autoCloseDialog);
	}
}
