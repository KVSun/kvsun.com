function buildArticleForm() {
	const form = document.createElement('form');
	const template = getTemplate('article-template');
	form.appendChild(template);
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
	const form = buildArticleForm('article-template');
	form.name = 'new-post';
	form.action = new URL('api.php', location.origin);
	form.method = 'POST';
	try {
		const header = form.querySelector('header');
		const title = header.querySelector('[itemprop="headline"]').appendChild(document.createElement('input'));
		const author = header.querySelector('[itemprop="author"]').appendChild(document.createElement('input'));
		const category = header.insertAdjacentElement('beforeend', document.createElement('input'));
		const content = form.querySelector('[itemprop="articleBody"]');
		const submit = form.appendChild(document.createElement('button'));
		title.name = `${form.name}[title]`;
		title.required = true;
		author.name = `${form.name}[author]`;
		author.required = true;
		author.placeholder = 'Author';
		category.name = `${form.name}[category]`;
		category.pattern = '[\\w ]+';
		category.required = true;
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

// export function makePost() {
// 	let main = document.querySelector('main');
// 	console.log(buildArticleForm('article-template').outerHTML);
// 	Array.from(main.querySelectorAll('*')).forEach(el => el.remove());
// 	let article = main.appendChild(document.createElement('article'));
// 	let form = article.appendChild(document.createElement('form'));
// 	let header = form.appendChild(document.createElement('header'));
// 	let title = header.appendChild(document.createElement('input'));
// 	header.appendChild(document.createElement('br'));
// 	let author = header.appendChild(document.createElement('input'));
// 	header.appendChild(document.createElement('br'));
// 	const cat = header.appendChild(document.createElement('input'));
// 	cat.setAttribute('list', 'categories');
// 	cat.name = `${form.name}[category]`;
// 	cat.required = true;
// 	cat.pattern = '[\\w ]+';
// 	cat.placeholder = 'Category';
// 	let content = form.appendChild(document.createElement('div'));
// 	let button = form.appendChild(document.createElement('button'));
// 	form.name = 'new-post';
// 	form.action = new URL('api.php', location.origin);
// 	form.method = 'POST';
// 	content.contentEditable = 'true';
// 	title.name = `${form.name}[title]`;
// 	author.name = `${form.name}[author]`;
// 	author.placeholder = 'Author';
// 	title.placeholder = 'Title';
// 	author.required = true;
// 	title.required = true;
// 	author.setAttribute('list', 'author_list');
// 	content.setAttribute('contextmenu', 'wysiwyg_menu');
// 	content.dataset.inputName = `${form.name}[content]`;
// 	button.textContent = 'Submit';
// 	button.type = 'submit';
//
// 	return article;
// }

export function updatePost() {
	const main = document.querySelector('main');
	if (main.querySelectorAll('[itemprop="mainEntityOfPage"]').length === 0) {
		new Notification('Cannot edit post.', {
			body: 'There is no post to edit.',
			icon: '/images/octicons/lib/svg/circle-slash.svg'
		});
		return;
	}
	const form = buildArticleForm('article-template');
	form.name = 'new-post';
	form.action = new URL('api.php', location.origin);
	form.method = 'POST';
	try {
		const header = form.querySelector('header');
		const title = header.querySelector('[itemprop="headline"]').appendChild(document.createElement('input'));
		const author = header.querySelector('[itemprop="author"]').appendChild(document.createElement('input'));
		const category = header.insertAdjacentElement('beforeend', document.createElement('input'));
		const content = form.querySelector('[itemprop="articleBody"]');
		const submit = form.appendChild(document.createElement('button'));
		title.name = `${form.name}[title]`;
		title.required = true;
		author.name = `${form.name}[author]`;
		author.required = true;
		author.placeholder = 'Author';
		author.setAttribute('list', 'author_list');
		category.name = `${form.name}[category]`;
		category.pattern = '[\\w ]+';
		category.required = true;
		category.placeholder = 'Category';
		category.setAttribute('list', 'categories');
		content.contentEditable = 'true';
		content.setAttribute('contextmenu', 'wysiwyg_menu');
		content.dataset.inputName = `${form.name}[content]`;
		submit.type = 'submit';
		submit.textContent = 'Publish';
		title.value = main.querySelector('[itemprop="headline"]').textContent;
		author.value = main.querySelector('[itemprop="author"]').textContent;
		content.innerHTML = main.querySelector('[itemprop="articleBody"]').innerHTML;
	} catch (e) {
		console.error(e);
	} finally {
		Array.from(main.querySelectorAll('*')).forEach(el => el.remove());
		main.appendChild(form);
	}
}
