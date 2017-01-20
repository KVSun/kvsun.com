// import * as templates from './templates.es6';
export default function updatePage(resp) {
	if (resp.headers.get('Content-Type') === 'application/json') {
		resp.json().then(update);
	} else {
		throw new Error(`Unsupported Content-Type, ${resp.headers.get('Content-Type')}`);
	}
}

export function popstate(pop)
{
	console.log(pop);
	if (pop.state) {
		updateContent(pop.state);
	} else {
		let url = new URL('api.php', location.origin);
		let headers = new Headers();
		headers.set('Content-Type', 'application/json');
		url.searchParams.set('url', document.location);
		fetch(url, {
			method: 'GET',
			headers,
			credentials: 'include'
		}).then(updateContent).catch(error => {
			console.error(error);
		});
	}
}

function update(json) {
	if (('type' in json) && ('data' in json)) {
		let url = new URL(json.url.path, location.origin);
		history.pushState(json, json.data.title, url);
		updateContent(json);
	} else {
		throw new Error('Response did not contain type and data.');
	}
}

function updateContent(json) {
	switch(json.type) {
	case 'article':
		document.querySelector('body > header > img').hidden = true;
		makeArticle(json.data);
		break;

	case 'home':
		document.querySelector('body > header > img').hidden = false;
		makeHome(json.data);
		break;

	default:
		throw new Error(`Unsupported response type: ${json.type}`);
	}
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

function makeHome(cats)
{
	const main = document.querySelector('main');
	const sections = cats.sections;
	Array.from(main.children).forEach(child => child.remove());
	Object.keys(sections).forEach(section => {
		if (sections[section].length) {
			let template = getTemplate('section-template');
			let container = template.firstElementChild;
			let title = template.querySelector('h2');
			title.className = 'center';
			let link = title.appendChild(document.createElement('a'));

			container.id = section;
			container.className = 'category';
			link.href = `${location.origin}/${section}`;
			link.textContent = sections[section][0].category;
			// template.querySelector('h2').textContent = section;
			console.info({section: sections[section]});
			sections[section].forEach(article => {
				let div = container.appendChild(document.createElement('div'));
				console.log(article);
				let a = div.appendChild(document.createElement('a'));
				a.href = `${location.origin}/${article.catURL}/${article.url}`;
				a.textContent = article.title;
				a.className = 'currentColor';
				// div.appendChild(document.createElement('br'));
			});
			main.appendChild(document.importNode(template, true));
		}
	});
}

function makeArticle(post) {
	console.log(post);
	document.title = post.title;
	const main = document.querySelector('main');
	const created = new Date(post.posted);
	const article = getTemplate('article-template');
	// main.innerHTML = templates.article;
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
	main.firstElementChild.scrollIntoView(true);
}
