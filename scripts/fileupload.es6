export default function DnD(el) {
	el.ondragover = dragOverHandler;
	el.ondragend = dragEndHandler;
	el.ondrop = function(drop) {
		drop.stopPropagation();
		drop.preventDefault();
		this.classList.remove('receiving');
		if (drop.dataTransfer.files.length) {
			let files = Array.from(drop.dataTransfer.files);
			files.forEach(file => {
				let reader = new FileReader();
				reader.addEventListener('load', load => {
					console.log(load);
					let headers = new Headers();
					headers.set('Content-Type', file.type);
					let url = new URL('api.php', document.baseURI);
					fetch(url, {
						headers,
						method: 'POST',
						body: file.result
					}).then(resp => resp.json()).then(json => console.log(json)).catch(err => console.error(err));
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
	};
}

// function fileLoad(event) {
// 	console.log(event.target);
// 	let headers = new Headers();
// 	headers.set('Content-Type', event.target.type);
// 	let url = new URL('api.php', document.baseURI);
// 	fetch(url, {
// 		headers,
// 		method: 'POST',
// 		body: event.target.response
// 	}).then(resp => console.log(resp)).catch(err => console.error(err));
// }

function fileError(error) {
	console.error(error);
}

function dragOverHandler(event) {
	event.stopPropagation();
	event.preventDefault();
	this.classList.add('receiving');
	return false;
}

function dragEndHandler(event) {
	event.stopPropagation();
	event.preventDefault();
	this.classList.remove('receiving');
	return false;
}

// function dropHandler(event) {
// 	event.stopPropagation();
// 	event.preventDefault();
// 	this.classList.remove('receiving');
// 	if (event.dataTransfer.files.length) {
// 		let files = Array.from(event.dataTransfer.files);
// 		files.forEach(file => {
// 			let reader = new FileReader();
// 			reader.addEventListener('load', fileLoad);
// 			reader.addEventListener('error', fileError);
// 			if (/image\/*/.test(file.type)) {
// 				reader.readAsBinaryString(file);
// 			} else if (/text\/*/.test(file.type)) {
// 				reader.readAsText(file);
// 			} else {
// 				console.error(`Unhandled file-type: "${file.type}".`);
// 			}
// 		});
// 	}
// }
