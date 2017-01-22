const REQUIRED = [
	'type',
	'data',
	'head',
	'url'
];

export default function getPage(page) {
	let url = new URL('api.php', location.origin);
	let headers = new Headers();
	headers.set('Accept', 'application/json');
	url.searchParams.set('url', page);
	return fetch(url, {
		method: 'GET',
		headers,
		credentials: 'include'
	}).then(updatePage);
}

export function popstate(pop) {
	if (
		('state' in pop) && pop.state !== null
		&& REQUIRED.every(prop => (prop in pop.state))
	) {
		updateContent(pop.state);
	} else {
		getPage(document.location);
	}
}

function updatePage(resp) {
	const type = resp.headers.get('Content-Type');
	if (type === 'application/json') {
		return resp.json().then(update);
	} else {
		throw new Error(`Unsupported Content-Type, ${type}`);
	}
}

function getTitle(json) {
	let title = '';
	if ('title' in json.head) {
		title = `${json.head.title}`;
	}
	if ('title' in json.data) {
		title += ` | ${json.data.title}`;
	}
	return title;
}

function update(json) {
	if (REQUIRED.every(prop => prop in json)) {
		let url = new URL(json.url.path, location.origin);
		history.pushState(json, getTitle(json), url);
		return updateContent(json);
	} else {
		throw new Error('Response did not contain type and data.');
	}
}

function updateContent(json) {
	console.info(json);
	document.title = getTitle(json);
	switch(json.type) {
	case 'article':
		makeArticle(json.data);
		break;

	case 'home':
		makeHome(json.data);
		break;

	default:
		throw new Error(`Unsupported response type: ${json.type}`);
	}
	return json;
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

function makeHome(cats) {
	document.querySelector('body > header > img').hidden = false;
	const main = document.querySelector('main');
	const sections = cats.sections;
	Array.from(main.children).forEach(child => child.remove());
	Object.keys(sections).forEach(section => {
		if (sections[section].length) {
			let template = getTemplate('section-template');
			let container = template.firstElementChild;
			let title = template.querySelector('h2');
			let link = title.appendChild(document.createElement('a'));
			title.className = 'center';

			container.id = section;
			container.className = 'category';
			link.href = `${location.origin}/${section}`;
			link.textContent = sections[section][0].category;
			sections[section].forEach(article => {
				let div = container.appendChild(document.createElement('div'));
				let a = div.appendChild(document.createElement('a'));
				a.href = `${location.origin}/${article.catURL}/${article.url}`;
				a.textContent = article.title;
				a.className = 'currentColor';
			});
			main.appendChild(document.importNode(template, true));
		}
	});
}

function makeArticle(post) {
	document.querySelector('body > header > img').hidden = true;
	const main = document.querySelector('main');
	const created = new Date(post.posted);
	const article = getTemplate('article-template');
	article.querySelector('[itemprop="headline"]').textContent = post.title;
	article.querySelector('[itemprop="dateModified"]').setAttribute('content', post.updated);
	article.querySelector('[itemprop="datePublished"]').textContent = created;
	article.querySelector('[itemprop="datePublished"]').setAttribute('datetime', created);
	article.querySelector('[itemprop="author"]').textContent = post.author;
	article.querySelector('[itemprop="name"]').textContent = 'Kern Valley Sun';
	article.querySelector('[itemprop="articleBody"]').innerHTML = post.content;
	article.querySelector('[itemprop="publisher"] [itemprop="url"]').setAttribute('href', location.origin);
	article.querySelector('[itemprop="logo"]').setAttribute('content', new URL('/images/sun-icons/128.png', location.origin));
	Array.from(main.children).forEach(child => child.remove());
	main.appendChild(document.importNode(article, true));
}
