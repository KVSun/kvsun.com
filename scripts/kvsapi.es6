// import * as templates from './templates.es6';
export default function updatePage(resp) {
	if (resp.headers.get('Content-Type') === 'application/json') {
		resp.json().then(update);
	} else {
		throw new Error(`Unsupported Content-Type, ${resp.headers.get('Content-Type')}`);
	}
}

function update(json) {
	if (('type' in json) && ('data' in json)) {
		let url = new URL(json.url.path, location.origin);
		history.pushState(json.data, json.data.title, url);
		switch(json.type) {
		case 'article':
			document.querySelector('body > header > img').hidden = true;
			makeArticle(json.data);
			break;

		default:
			throw new Error(`Unsupported response type: ${json.type}`);
		}
	} else {
		throw new Error('Response did not contain type and data.');
	}
}

function getTemplate(templateID) {
	let template = document.getElementById(templateID);
	if ('content' in document.createElement('template')) {
		return template.content;
	} else {
		let frag = document.createDocumentFragment();
		Array.from(template.children).forEach(child => {
			frag.appendChild(child.cloneNode(true));
		});
		return frag;
	}
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
