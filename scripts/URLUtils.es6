(() => {
	// https://developer.mozilla.org/en-US/docs/Web/API/URL
	class URL {
		constructor(url, base = null) {
			const link = document.createElement('a');
			base = new URL(base);
			link.href = url;
			[
				this.href,
				this.protocol,
				this.hostname,
				this.port,
				this.pathname,
				this.search,
				this.hash,
				this.host
			] = [
				link.href || base.href,
				link.protocol || base.protocol,
				link.hostname || base.hostname,
				link.port || base.port,
				link.pathname || base.pathname,
				link.search || base.search,
				link.hash || base.hash,
				link.host || base.host
			];
			this.searchParams = new URLSearchParams(this.search);
		}

		toString() {
			return this.href;
		}
	}

	// https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
	class URLSearchParams {
		constructor(q = null) {
			q.split('&').forEach(param => {
				let[name, value = null] = param.split('=');
				this.append(name, value);
			});
		}

		toString() {
			let params = Object.keys(this).reduce(
			(params, param) => {
				let vals = this.getAll(param);
				if (vals.length === 1) {
					params.push(`${encodeURIComponent(param)}=${encodeURIComponent(vals[0])}`);
				} else {
					vals.forEach(val =>{
						params.push(`${encodeURIComponent(param)}[]=${encodeURIComponent(val)}`);
					});
				}
				return params;
			},
			[]);
			return params.join('&');
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

		has(name) {
			return this.hasOwnProperty(name);
		}

		get(name) {
			return this.getAll(name)[0];
		}

		getAll(name) {
			return this[name];
		}

		delete(name) {
			delete this[name];
		}
	}

	let p = new URLSearchParams('foo=bar');
	p.append('foo', 'bar2');
	p.set('bar', 'baz');

	console.log(`${p}`);
})();
