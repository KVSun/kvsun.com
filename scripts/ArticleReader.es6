export default class ArticleReader {
	constructor(article, controls) {
		this.utterance = new SpeechSynthesisUtterance(article.textContent);

		this.playButton = document.createElement('button');
		this.pauseButton = document.createElement('button');
		this.stopButton = document.createElement('button');

		controls.appendChild(document.createElement('br'));
		this.playButton.textContent = 'Play';
		this.pauseButton.textContent = 'Pause';
		this.stopButton.textContent = 'Stop';

		this.playButton.addEventListener('click',this.play.bind(this));
		this.pauseButton.addEventListener('click', this.pause.bind(this));
		this.stopButton.addEventListener('click', this.stop.bind(this));

		controls.appendChild(this.playButton);
		controls.appendChild(this.pauseButton);
		controls.appendChild(this.stopButton);
	}

	play() {
		if (! (window.speechSynthesis.speaking)) {
			window.speechSynthesis.speak(this.utterance);
		}
		window.speechSynthesis.resume();

		this.pauseButton.disabled = false;
		this.playButton.disabled = true;
		this.stopButton.disabled = false;
	}

	pause() {
		window.speechSynthesis.pause();

		this.playButton.disabled = false;
		this.pauseButton.disabled = true;
	}

	stop() {
		window.speechSynthesis.cancel();

		this.pauseButton.disabled = true;
		this.playButton.disabled = false;
		this.stopButton.disabled = true;
	}

	static speechSupported() {
		return window.speechSynthesis instanceof Object;
	}
}
