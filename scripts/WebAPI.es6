// https://developer.mozilla.org/en-US/docs/Web/API/Body
export class Body {
	constructor(data) {
		this._body = data;
		this.bodyUsed = false;
	}
	text() {
		return new Promise((resolve, reject) => {
			try {
				resolve(this._body);
			} catch(e) {
				reject(e);
			}
		});
	}
	json() {
		return new Promise((resolve, reject) => {
			try {
				resolve(this.text().then(json => JSON.parse(json)));
			} catch(e) {
				reject(e);
			}
		});
	}

}

// https://developer.mozilla.org/en-US/docs/Web/API/Headers
export class Headers {
	constructor(headers = {}) {
		for (let name in headers) {
			this.append(name, headers[name]);
		}
	}

	set(name, value) {
		this[name] = [value];
	}
	append(name, value) {
		if (this.has(name)) {
			this[name].push(value);
		} else {
			this.set(name, value);
		}
	}
	get(name) {
		let all = this.getAll(name);
		return all[0];
	}
	getAll(name) {
		return this[name];
	}
	has(name) {
		return this.hasOwnProperty(name);
	}
}

// https://developer.mozilla.org/en-US/docs/Web/API/Response
export class Response {
	constructor(event) {
		this.status = event.target.status;
		this.ok = this.status > 199 && this.status < 301;
		this.statusText = event.target.statusText;
		this.url = event.target.responseURL;
		this._resp = new Body(event.target.response);
		this.headers = new Headers();
		event.target.getAllResponseHeaders().split('\n').filter(str => str.length !== 0).forEach(line => {
			let [header, value] = line.split(':');
			this.headers.append(header.trim(), value.trim());
		});
	}
	json() {
		return this._resp.json();
	}
	text() {
		return this._resp.text();
	}
}

// https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest
// https://developer.mozilla.org/en-US/docs/Web/API/Request
export class Request extends XMLHttpRequest {
	constructor(url, {
		method = 'GET',
		headers = {},
		body = null,
		credentials = 'omit',
		// mode = 'no-cors',
		referrer = null,
		// referrerPolicy = null,
		// cache = 'default',
		// redirect = 'follow',
		// integrity = null
	} = {}) {
		super();
		this.url = url;
		this.method = method;
		this.body = body;
		this.headers = new Headers(headers);
		this.referrer = referrer;
		this.credentials = credentials;
	}
	send() {
		this.open(this.method, this.url);

		for (let name in this.headers) {
			this.headers.getAll(name).forEach(value => {
				this.setRequestHeader(name, value);
			});
		}

		switch(this.credentials) {
		case 'include':
			this.withCredentials = true;
			break;

		case 'same-origin':
			this.withCredentials = this.url.startsWith(location.origin);
			break;

		default:
			this.withCredentials = false;
		}

		return new Promise((resolve, reject) => {
			this.addEventListener('load', load => {
				try {
					let resp = new Response(load);
					resolve(resp);
				} catch(e) {
					reject(e);
				}
			});
			this.addEventListener('error', error => reject(error));
			super.send(this.body);
		});
	}
}

export function fetch(req, init = {}) {
	return new Promise((resolve, reject) => {
		if (!(req instanceof Request)) {
			req = new Request(req, init);
			console.log(req);
		}
		try {
			resolve(req.send());
		} catch (e) {
			reject(e);
		}
	});
}
