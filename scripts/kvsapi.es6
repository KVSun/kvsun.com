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
	if (typeof ga === 'function') {
		ga('send', 'pageview', page);
	}
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
		if (location.hash.length) {
			let target = document.getElementById(location.hash.substr(1));
			if (target instanceof Element) {
				target.scrollIntoView({
					behavior: 'smooth',
					block: 'start'
				});
			} else {
				location.hash = '';
			}
		} else {
			document.querySelector('main').scrollIntoView({
				behavior: 'smooth',
				block: 'start'
			});
		}
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
			if (typeof ga === 'function') {
				ga('send', 'pageview', pop.state.url);
			}
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
		url.hash = location.hash;
		history.pushState(json, getTitle(json), url);
		return updateContent(json);
	} else {
		console.log(json);
		handleJSON(json);
		// throw new Error('Response did not contain type and data.');
	}
}

function updateContent(json) {
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

	case 'classifieds':
		makeClassifieds(json.data);
		break;

	case 'contact':
		makeContact(json.data);
		break;

	case 'businessdirectory':
		makeBusinessDirectory(json.data);
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

function makeClassifieds(data) {
	const main = document.querySelector('main');
	const container = document.createElement('div');
	let details = {};

	container.class = 'classifieds';
	container.dataset.cols = 'auto';

	Object.keys(data.categories).forEach(i => {
		if ((i in data.content) || (i in data.ads)) {
			let summary = document.createElement('summary');
			let title = document.createElement('b');
			summary.appendChild(title);
			details[i] = document.createElement('details');
			details[i].setAttribute('open', '');
			title.textContent = data.categories[i];
			details[i].appendChild(summary);
			container.appendChild(details[i]);
		}
		if (i in data.content) {
			details[i].innerHTML += data.content[i];
		}
		if (i in data.ads) {
			try {
				data.ads[i].forEach(ad => {
					let img = document.createElement('img');
					img.at = ad.text;
					img.src = new URL(ad.image, location.origin);
					img.title = ad.text;
					details[i].appendChild(img);
				});
			} catch (e) {
				console.error(e);
			}
		}
	});
	Array.from(main.children).forEach(child => child.remove());
	main.appendChild(container);
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

function makeBusinessDirectory(data) {
	/**
	 * $main = $dom->getElementsByTagName('main')->item(0);
 	$template = $dom->getElementById('business-listing');
 	$container = $main->append('div', null, ['data-cols' => 'auto']);
 	$xpath = new \DOMXPath($dom);

 	foreach ($kvs->categories as $category => $list) {
 		$details = $container->append('details', null, ['open' => null]);
 		$details->append('summary', $category);

 		foreach ($list as $item) {
 			$org = $details->append('div');
 			foreach ($template->childNodes as $node) {
 				$org->appendChild($node->cloneNode(true));
 			}
 			$name = $xpath->query('.//*[@itemprop="legalName"]', $org)->item(0);
 			$img = $xpath->query('.//*[@itemprop="image"]', $org)->item(0);
 			$desc = $xpath->query('.//*[@itemprop="description"]', $org)->item(0);
 			$name->textContent = $item->name;
 			if (isset($item->image)) {
 				$img->src = DOMAIN . ltrim($item->image, '/');
 				$img->content = $img->src;
 			} else {
 				$img->parentNode->removeChild($img);
 			}

 			if (isset($item->text)) {
 				$desc->textContent = $item->text;
 			} else {
 				$desc->parentNode->removeChild($desc);
 			}
 		}
 	}
	 * @type {[type]}
	 */
	const main = document.querySelector('main');
	const template = getTemplate('business-listing');
	const container = document.createElement('div');
	const title = document.createElement('h3');
	container.dataset.cols = 'auto';
	title.textContent = data.title;
	title.classList.add('center');

	Object.keys(data.categories).forEach(category => {
		let list = data.categories[category];
		let details = document.createElement('details');
		let summary = document.createElement('summary');

		summary.textContent = category;
		details.appendChild(summary);
		details.setAttribute('open', '');
		container.appendChild(details);
		list.forEach(item => {
			let node = details.appendChild(template.firstElementChild.cloneNode(true));
			let img = node.querySelector('[itemprop="image"]');
			let name = node.querySelector('[itemprop="legalName"]');
			let desc = node.querySelector('[itemprop="description"]');
			name.textContent = item.name;
			if ('image' in item) {
				img.src = `${location.origin}${item.image}`;
				img.setAttribute('content', img.src);
				img.setAttribute('alt', item.name);
			} else {
				img.remove();
			}

			if ('text' in item) {
				desc.textContent = item.text;
			} else {
				desc.remove();
			}
		});
	});
	Array.from(main.children).forEach(child => child.remove());
	main.appendChild(title);
	main.appendChild(container);
}

function makeContact(/*info*/) {
	const template = getTemplate('itemtype-Organization');
	const main = document.querySelector('main');
	Array.from(main.children).forEach(child => child.remove());
	main.appendChild(document.importNode(template, true));
}

function formatDate(date) {
	return `${Days[date.getDay()]} ${Months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}
