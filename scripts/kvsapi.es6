import handleJSON from './std-js/json_response.es6';
const REQUIRED = [
	'type',
	'data',
	'head',
	'url'
];

const Months = [
	'Jan',
	'Feb',
	'March',
	'April',
	'May',
	'June',
	'July',
	'Aug',
	'Sep.',
	'Oct',
	'Nov',
	'Dec'
];

const Days = [
	'Sun.',
	'Mon,',
	'Tue.',
	'Wed.',
	'Thu.',
	'Fri.',
	'Sat.'
];

export default async function getPage(page) {
	return loadPage(page);
}

async function loadPage(page) {
	let url = new URL('api.php', location.origin);
	let headers = new Headers();
	headers.set('Accept', 'application/json');
	url.searchParams.set('url', page);
	try {
		let resp = await fetch(url, {
			method: 'GET',
			headers,
			credentials: 'include'
		});
		document.querySelector('main').scrollIntoView({
			behavior: 'smooth',
			block: 'start'
		});
		return updatePage(resp);
	} catch(e) {
		console.error(e);
	}
}

export function popstate(pop) {
	try {
		if (
			('state' in pop) && pop.state !== null
			&& REQUIRED.every(prop => (prop in pop.state))
		) {
			updateContent(pop.state);
		} else {
			loadPage(document.location);
		}
	} catch(e) {
		console.error(e);
	}
}

async function updatePage(resp) {
	const type = resp.headers.get('Content-Type');
	if (type === 'application/json') {
		try {
			let json = await resp.json();
			return update(json);
		} catch(err) {
			console.error(err);
		}
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
		console.log(json);
		handleJSON(json);
		// throw new Error('Response did not contain type and data.');
	}
}

function updateContent(json) {
	console.info(json);
	document.title = getTitle(json);
	switch(json.type) {
	case 'home':
		makeHome(json.data);
		break;

	case 'article':
		makeArticle(json.data);
		break;

	case 'category':
		makeCategory(json.data);
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
function add_comments(parent, comments) {
	comments.forEach(comment => {
		const container = parent.appendChild(document.createElement('div'));
		const user = container.appendChild(document.createElement('span'));
		const grav = user.appendChild(document.createElement('img'));
		const posted = container.appendChild(document.createElement('span'));
		posted.appendChild(document.createTextNode(' on '));
		const time = posted.appendChild(document.createElement('time'));
		container.appendChild(document.createElement('br'));
		const addedComment = container.appendChild(document.createElement('div'));
		const byLine = user.appendChild(document.createElement('b'));
		byLine.appendChild(document.createTextNode('By '));
		const commenter = byLine.appendChild(document.createElement('u'));
		container.appendChild(document.createElement('hr'));

		container.setAttribute('itemprop', 'comment');
		container.setAttribute('itemtype', 'http://schema.org/Comment');
		container.setAttribute('itemscope', null);
		container.id = `comment-${comment.commentID}`;

		user.setAttribute('itemprop', 'author');
		user.setAttribute('itemtype', 'http://schema.org/Person');
		user.setAttribute('itemscope', null);
		grav.src = `https://www.gravatar.com/avatar/${comment.email}`;
		grav.width = 80;
		grav.height = 80;
		grav.alt = `${comment.username} avatar`;
		grav.setAttribute('itemprop', 'image');
		commenter.textContent = comment.name;
		commenter.setAttribute('itemprop', 'name');

		time.textContent = formatDate(new Date(comment.created));
		time.setAttribute('itemprop', 'dateCreated');
		time.setAttribute('datetime', comment.created);

		addedComment.textContent = comment.text;
		addedComment.setAttribute('itemprop', 'text');
	});
}

function makeCategory(category) {
	const main = document.querySelector('main');
	const template = getTemplate('section-template');
	let container = template.firstElementChild;
	let title = template.querySelector('h2');
	title.textContent = category.title;
	title.className = 'center';

	container.id = category.category;
	container.className = 'category';
	category.articles.forEach(article => {
		let div = container.appendChild(document.createElement('div'));
		let a = div.appendChild(document.createElement('a'));
		a.href = `${location.origin}/${article.url}`;
		a.textContent = article.title;
		a.className = 'currentColor';
	});

	Array.from(main.children).forEach(child => child.remove());
	main.appendChild(document.importNode(template, true));
}

function makeHome(cats) {
	const main = document.querySelector('main');
	const sections = cats.categories;
	Array.from(main.children).forEach(child => child.remove());
	Object.keys(sections).forEach(section => {
		if (sections[section]) {
			let template = getTemplate('section-template');
			let container = template.firstElementChild;
			let title = template.querySelector('h2');
			let link = title.appendChild(document.createElement('a'));
			title.className = 'center';

			container.id = sections[section].catURL;
			container.className = 'category';
			link.href = `${location.origin}/${sections[section].catURL}`;
			link.textContent = section;
			sections[section].posts.forEach(article => {
				let div = container.appendChild(document.createElement('div'));
				let a = div.appendChild(document.createElement('a'));
				a.href = `${location.origin}/${article.url}`;
				a.textContent = article.title;
				a.className = 'currentColor';
			});
			main.appendChild(document.importNode(template, true));
		}
	});
}

function makeArticle(post) {
	const main = document.querySelector('main');
	const created = new Date(post.posted);
	const template = getTemplate('article-template');
	const breadcrumbs = template.querySelectorAll('[itemprop="breadcrumb"] [itemprop="item"]');
	const article = template.querySelector('[itemprop="mainEntityOfPage"]');
	const publisher = template.querySelector('[itemprop="publisher"]');
	const commentCount = article.appendChild(document.createElement('meta'));
	breadcrumbs.item(0).querySelector('[itemprop="url"]').href = location.origin;
	breadcrumbs.item(1).querySelector('[itemprop="name"]').textContent = post.category.name;
	breadcrumbs.item(1).querySelector('[itemprop="url"]').href = post.category.url;
	breadcrumbs.item(2).querySelector('[itemprop="name"]').textContent = post.title;
	breadcrumbs.item(2).querySelector('[itemprop="url"]').href = location.href;
	article.querySelector('[itemprop="headline"]').textContent = post.title;
	article.querySelector('[itemprop="articleSection"]').textContent = post.category.name;
	article.querySelector('[itemprop="dateModified"]').setAttribute('content', post.updated);
	article.querySelector('[itemprop="datePublished"]').textContent = formatDate(created);
	article.querySelector('[itemprop="datePublished"]').setAttribute('datetime', created);
	article.querySelector('[itemprop="author"]').textContent = post.author;
	article.querySelector('[itemprop="name"]').textContent = 'Kern Valley Sun';
	article.querySelector('[itemprop="articleBody"]').innerHTML = post.content;
	publisher.querySelector('[itemprop="url"]').setAttribute('href', location.origin);
	publisher.querySelector('[itemprop="logo"]').setAttribute('content', new URL('/images/sun-icons/128.png', location.origin));
	article.appendChild(document.createElement('hr'));
	add_comments(article.querySelector('footer'), post.comments);
	commentCount.setAttribute('itemprop', 'commentCount');
	commentCount.setAttribute('content', post.comments.length);
	Array.from(main.children).forEach(child => child.remove());
	main.appendChild(document.importNode(template, true));
}

function formatDate(date) {
	return `${Days[date.getDay()]} ${Months[date.getMonth()]}, ${date.getFullYear()} at ${date.toLocaleTimeString()}`;
}
