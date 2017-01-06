export default function() {
	let main = document.querySelector('main');
	main.querySelector('article').remove();
	let article = main.appendChild(document.createElement('article'));
	let form = article.appendChild(document.createElement('form'));
	let header = form.appendChild(document.createElement('header'));
	let title = header.appendChild(document.createElement('h2'));
	let author = header.appendChild(document.createElement('div'));
	let content = form.appendChild(document.createElement('div'));
	let button = form.appendChild(document.createElement('button'));
	form.name = 'new-post';
	form.action = new URL('api.php', location.origin);
	form.method = 'POST';
	content.contentEditable = 'true';
	title.contentEditable = 'true';
	author.contentEditable = 'true';
	content.setAttribute('contextmenu', 'wysiwyg_menu');
	author.dataset.inputName = `${form.name}[author]`;
	title.dataset.inputName = `${form.name}[title]`;
	content.dataset.inputName = `${form.name}[content]`;
	button.textContent = 'Submit';
	button.type = 'submit';
}
