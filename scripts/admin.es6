export function makePost() {
	let main = document.querySelector('main');
	Array.from(main.querySelectorAll('*')).forEach(el => el.remove());
	let article = main.appendChild(document.createElement('article'));
	let form = article.appendChild(document.createElement('form'));
	let header = form.appendChild(document.createElement('header'));
	let title = header.appendChild(document.createElement('input'));
	header.appendChild(document.createElement('br'));
	let author = header.appendChild(document.createElement('input'));
	header.appendChild(document.createElement('br'));
	const cat = header.appendChild(document.createElement('input'));
	cat.setAttribute('list', 'categories');
	cat.name = `${form.name}[category]`;
	cat.required = true;
	cat.pattern = '[\\w ]+';
	cat.placeholder = 'Category';
	let content = form.appendChild(document.createElement('div'));
	let button = form.appendChild(document.createElement('button'));
	form.name = 'new-post';
	form.action = new URL('api.php', location.origin);
	form.method = 'POST';
	content.contentEditable = 'true';
	title.name = `${form.name}[title]`;
	author.name = `${form.name}[author]`;
	author.placeholder = 'Author';
	title.placeholder = 'Title';
	author.required = true;
	title.required = true;
	author.setAttribute('list', 'author_list');
	content.setAttribute('contextmenu', 'wysiwyg_menu');
	content.dataset.inputName = `${form.name}[content]`;
	button.textContent = 'Submit';
	button.type = 'submit';

	return article;
}

export function updatePost() {
	const main = document.querySelector('main');
	const article = main.querySelector('article');
	if (!article) {
		return false;
	}

	const form = document.createElement('form');
	form.action = new URL('api.php', location.origin);
	form.method = 'POST';
	form.name = 'update-post';

	return form;
}
