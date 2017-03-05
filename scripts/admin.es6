import * as fileUpload from './fileupload.es6';
function buildArticleForm(name) {
	const form = document.createElement('form');
	const template = getTemplate('article-template');
	const fieldset = document.createElement('fieldset');
	const details = document.createElement('details');
	const summary = document.createElement('summary');
	const legend = document.createElement('legend');
	const freeLabel = document.createElement('label');
	const free = document.createElement('input');
	const draftLabel = document.createElement('label');
	const draft = document.createElement('input');
	const keywords = document.createElement('input');
	const description = document.createElement('textarea');
	const keywordsLabel = document.createElement('label');
	const descriptionLabel = document.createElement('label');
	form.appendChild(fieldset);
	fieldset.appendChild(legend);
	fieldset.appendChild(details);
	details.appendChild(summary);
	details.appendChild(descriptionLabel);
	details.appendChild(document.createElement('br'));
	details.appendChild(description);
	details.appendChild(document.createElement('br'));
	details.appendChild(keywordsLabel);
	details.appendChild(keywords);
	details.appendChild(document.createElement('br'));
	details.appendChild(free);
	details.appendChild(freeLabel);
	details.appendChild(document.createElement('br'));
	details.appendChild(draft);
	details.appendChild(draftLabel);
	details.setAttribute('open', 'true');
	summary.textContent = 'Show/hide options';
	form.name = name;
	form.action = new URL('api.php', location.origin);
	form.method = 'POST';
	form.addEventListener('dragOver', fileUpload.dragOverHandler);
	form.addEventListener('dragEnd', fileUpload.dragEndHandler);
	form.addEventListener('drop', fileUpload.dropHandler);
	legend.textContent = 'Article options';
	description.name = `${form.name}[description]`;
	description.id = `${form.name}-description`;
	description.placeholder = 'Article description (max 140 characters)';
	description.setAttribute('maxlength', 140);
	descriptionLabel.setAttribute('for', description.id);
	descriptionLabel.textContent = 'Description';
	description.autocomplete = false;
	keywords.name = `${form.name}[keywords]`;
	keywords.id = `${form.name}-keywords`;
	keywords.placeholder = 'Article keywords/tags';
	keywords.pattern = '[\\w ]+(,[\\w ]+)*';
	keywords.autocomplete = false;
	keywordsLabel.textContent = 'Keywords: ';
	keywordsLabel.setAttribute('for', keywords.id);
	draft.name = `${form.name}[draft]`;
	draft.id = `${form.name}-draft`;
	draftLabel.textContent = 'Draft?';
	draftLabel.setAttribute('for', draft.id);
	draft.type = 'checkbox';
	free.name = `${form.name}[free]`;
	free.type = 'checkbox';
	free.id = `${form.name}-free`;
	freeLabel.setAttribute('for', free.id);
	freeLabel.textContent = 'Free?';
	form.appendChild(template);
	form.querySelector('footer').hidden = true;
	form.querySelector('[itemprop="publisher"]').hidden = true;
	form.querySelector('nav').hidden = true;

	return form;
}

function getTemplate(templateID) {
	let template = document.getElementById(templateID);
	if ('content' in document.createElement('template')) {
		return template.content.cloneNode(true);
	} else {
		let frag = document.createDocumentFragment();
		Array.from(template.children).forEach(child => {
			frag.appendChild(child.cloneNode(true));
		});
		return frag;
	}
}

export function makePost() {
	const main = document.querySelector('main');
	const form = buildArticleForm('new-post');
	try {
		const header = form.querySelector('header');
		const title = header.querySelector('[itemprop="headline"]').appendChild(document.createElement('input'));
		const author = header.querySelector('[itemprop="author"]').appendChild(document.createElement('input'));
		const category = header.querySelector('[itemprop="articleSection"]').appendChild(document.createElement('input'));
		const content = form.querySelector('[itemprop="articleBody"]');
		const submit = form.appendChild(document.createElement('button'));
		Array.from(form.querySelectorAll('footer, nav')).forEach(el => el.hidden);
		title.name = `${form.name}[title]`;
		title.autocomplete = 'off';
		title.required = true;
		title.placeholder = 'Title';
		author.name = `${form.name}[author]`;
		author.autocomplete = 'off';
		author.required = true;
		author.placeholder = 'Author';
		category.name = `${form.name}[category]`;
		category.pattern = '[\\w ]+';
		category.required = true;
		category.autocomplete = 'off';
		category.placeholder = 'Category';
		category.setAttribute('list', 'categories');
		author.setAttribute('list', 'author_list');
		content.contentEditable = 'true';
		content.setAttribute('contextmenu', 'wysiwyg_menu');
		content.dataset.inputName = `${form.name}[content]`;
		submit.type = 'submit';
		submit.textContent = 'Publish';
	} catch (e) {
		console.error(e);
	} finally {
		Array.from(main.querySelectorAll('*')).forEach(el => el.remove());
		main.appendChild(form);
	}
}

export async function updatePost() {
	const main = document.querySelector('main');
	const url = new URL('api.php', location.origin);
	const headers = new Headers();
	url.searchParams.set('url', location.href);
	headers.set('Accept', 'application/json');
	let resp = await fetch(url, {
		headers,
		credentials: 'include'
	});
	if (resp.headers.get('Content-Type') !== 'application/json') {
		throw new Error('Unsupported Content-Type in response');
	}
	const post = await resp.json();
	if (post.type !== 'article') {
		new Notification('Cannot edit post.', {
			body: 'There is no post to edit.',
			icon: '/images/octicons/lib/svg/circle-slash.svg'
		});
		return;
	}
	const form = buildArticleForm('update-post');
	try {
		const header = form.querySelector('header');
		const title = header.querySelector('[itemprop="headline"]').appendChild(document.createElement('input'));
		const author = header.querySelector('[itemprop="author"]').appendChild(document.createElement('input'));
		const category = header.querySelector('[itemprop="articleSection"]').appendChild(document.createElement('input'));
		const content = form.querySelector('[itemprop="articleBody"]');
		const submit = form.appendChild(document.createElement('button'));
		const postURL = form.appendChild(document.createElement('input'));
		form.querySelector(`#${form.name}-keywords`).value = post.data.keywords.join(', ');
		form.querySelector(`#${form.name}-description`).value = post.data.description;
		form.querySelector(`#${form.name}-free`).checked = post.data.is_free;
		form.querySelector(`#${form.name}-draft`).checked = post.data.draft;
		title.name = `${form.name}[title]`;
		title.autocomplete = 'off';
		title.required = true;
		title.placeholder = 'Title';
		author.name = `${form.name}[author]`;
		author.required = true;
		author.placeholder = 'Author';
		author.autocomplete = 'off';
		author.setAttribute('list', 'author_list');
		category.name = `${form.name}[category]`;
		category.value = post.data.category.name;
		category.pattern = '[\\w ]+';
		category.required = true;
		category.autocomplete = 'off';
		category.placeholder = 'Category';
		category.setAttribute('list', 'categories');
		content.contentEditable = 'true';
		content.setAttribute('contextmenu', 'wysiwyg_menu');
		content.dataset.inputName = `${form.name}[content]`;
		submit.type = 'submit';
		submit.textContent = 'Publish';
		title.value = post.data.title;
		author.value = post.data.author;
		content.innerHTML = post.data.content;
		postURL.type = 'hidden';
		postURL.name = `${form.name}[url]`;
		postURL.value = location.href;
	} catch (e) {
		console.error(e);
	} finally {
		Array.from(main.querySelectorAll('*')).forEach(el => el.remove());
		main.appendChild(form);
	}
}
