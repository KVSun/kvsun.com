export default function() {
	let main = document.querySelector('main');
	Array.from(main.querySelectorAll('*')).forEach(el => el.remove());
	let article = main.appendChild(document.createElement('article'));
	let form = article.appendChild(document.createElement('form'));
	let header = form.appendChild(document.createElement('header'));
	let title = header.appendChild(document.createElement('input'));
	header.appendChild(document.createElement('br'));
	let author = header.appendChild(document.createElement('input'));
	header.appendChild(document.createElement('br'));
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
}
