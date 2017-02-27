import handleJSON from './std-js/json_response.es6';

function fileError(error) {
	console.error(error);
}

export function dragOverHandler(event) {
	event.stopPropagation();
	event.preventDefault();
	this.classList.add('receiving');
	return false;
}

export function dragEndHandler(event) {
	event.stopPropagation();
	event.preventDefault();
	this.classList.remove('receiving');
	return false;
}

export function dropHandler(drop) {
	drop.stopPropagation();
	drop.preventDefault();
	this.classList.remove('receiving');
	if (drop.dataTransfer.files.length) {
		const files = Array.from(drop.dataTransfer.files);
		files.forEach(file => {
			const reader = new FileReader();
			reader.addEventListener('load', load => {
				switch (file.type) {
				case 'text/plain':
					document.execCommand('insertText', null, load.target.result);
					break;
				case 'image/svg+xml':
				case 'text/html':
					document.execCommand('insertHTML', null, load.target.result);
					break;
				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
					try {
						uploadImage(file);
					} catch (e) {
						console.error(e);
					}
					break;
				}
			});

			reader.addEventListener('error', fileError);

			if (/image\/*/.test(file.type)) {
				reader.readAsBinaryString(file);
			} else if (/text\/*/.test(file.type)) {
				reader.readAsText(file);
			} else {
				console.error(`Unhandled file-type: "${file.type}".`);
			}
		});
	}
}

async function uploadImage(file) {
	const headers = new Headers();
	const url     = new URL('api.php', document.baseURI);
	const body    = new FormData();

	headers.set('Accept', 'application/json');
	body.set('upload', file, file.name);
	const resp = await fetch(url, {
		headers,
		method: 'POST',
		body,
		credentials: 'include'
	});
	if (resp.ok) {
		if (resp.headers.get('Content-Type') === 'application/json') {
			const json = await resp.json();

			if ('path' in json) {
				document.execCommand('insertImage', null, json.path);
			} else {
				handleJSON(json);
			}
		} else if (resp.headers.get('Content-Type').startsWith('text/html')) {
			const html = await resp.text();
			document.execCommand('insertHTML', null, `${html}<br/>`);
		} else {
			throw new TypeError('Request did not get a valid response.');
		}
	} else {
		throw new Error(`"${resp.url}" -> ${resp.status}:${resp.statusText}`);
	}
}
