class Notification {
	/**
	 * Create new Notification instance.
	 * Most arguments have no effect. Title is required. Body and icon
	 * are optional
	 * @type {[type]}
	 */
	constructor(title, {
		body = '',
		dir = 'auto',
		lang = '',
		tag = '',
		icon = '',
		timestamp = Date.now()
	} = {}) {
		this.title = title;
		this.body = body;
		this.dir = dir;
		this.lang = lang;
		this.tag = tag;
		this.icon = icon;
		this.timestamp = timestamp;

		new Promise(resolve => {
			resolve(this);
		}).then(data => {
			const dialog = document.createElement('dialog');
			const close = dialog.appendChild(document.createElement('button'));
			dialog.id = `notification-${this.timestamp}`;
			close.type = 'button';
			close.dataset.delete = `#${dialog.id}`;
			dialog.appendChild(document.createElement('hr'));

			if (data.icon.length) {
				let icon = dialog.appendChild(new Image(128, 128));
				icon.src = data.icon;
				icon.style.marginRight = '20px';
			}
			const container = dialog.appendChild(document.createElement('div'));
			container.style.display = 'inline-block';
			container.appendChild(document.createElement('h1')).textContent = this.title;
			if (this.body.length) {
				container.appendChild(document.createElement('p')).textContent = this.body;
			}
			document.body.appendChild(dialog);
			dialog.showModal();
			return dialog;
		}).then(dialog => {
			if (this.onshow instanceof Function) {
				this.onshow();
			}
			if (this.onclick instanceof Function) {
				dialog.addEventListener('click', this.onclick);
			}
		}).catch(error => {
			if (this.onerror instanceof Function) {
				this.onerror(error);
			}
		});
	}
	static requestPermission() {
		return new Promise(resolve => resolve('granted'));
	}
	close() {
		let dialog = document.getElementById(`notification-${this.timestamp}`);
		if (this.onclose instanceof Function) {
			this.onclose();
		}
		dialog.close();
		Array.from(document.querySelectorAll('.backdrop')).forEach(backdrop => {
			backdrop.remove();
		});
	}
}
Notification.permission = 'granted';
export {Notification as default};
