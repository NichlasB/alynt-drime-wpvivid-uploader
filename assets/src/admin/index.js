import './style.css';

(function () {
	'use strict';

	document.addEventListener('submit', function (event) {
		const confirmMessage = event.target.querySelector('[data-alynt-confirm]');

		if (confirmMessage && !window.confirm(confirmMessage.getAttribute('data-alynt-confirm'))) {
			event.preventDefault();
			return;
		}

		const button = event.target.querySelector('button[type="submit"]');

		if (!button) {
			return;
		}

		const loadingLabel = button.getAttribute('data-alynt-loading-label');

		if (loadingLabel) {
			button.setAttribute('data-alynt-original-label', button.textContent);
			button.textContent = loadingLabel;
		}

		button.disabled = true;
		button.setAttribute('aria-disabled', 'true');
		button.setAttribute('aria-busy', 'true');
	}, true);
}());
