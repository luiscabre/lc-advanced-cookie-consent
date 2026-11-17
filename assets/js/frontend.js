(() => {
	'use strict';

	const config = window.lcaccConfig;

	if (!config || !config.restUrl) {
		return;
	}

	const banner = document.getElementById('lcacc-banner');
	const settingsTrigger = document.getElementById('lcacc-settings-trigger');

	if (!banner) {
		return;
	}

	const preferencesPanel = document.getElementById('lcacc-preferences');
	const acceptButton = document.getElementById('lcacc-accept');
	const rejectButton = document.getElementById('lcacc-reject');
	const customizeButton = document.getElementById('lcacc-customize');
	const saveButton = document.getElementById('lcacc-save');
	const status = document.getElementById('lcacc-status');

	const categories = config.categories || {};

	const optionalCategories = [
		'functional',
		'statistics',
		'marketing'
	];

	const scriptBlockingEnabled =
		config.scriptBlockingEnabled === true
		|| config.scriptBlockingEnabled === 1
		|| config.scriptBlockingEnabled === '1';

	let consentReady = false;
	let isSaving = false;

	let activePreferences = {
		necessary: true,
		functional: false,
		statistics: false,
		marketing: false
	};

	/**
	 * Obtiene las preferencias seleccionadas en el banner.
	 *
	 * @returns {Object} Preferencias seleccionadas.
	 */
	function getPreferences() {
		const preferences = {
			functional: false,
			statistics: false,
			marketing: false
		};

		banner.querySelectorAll('.lcacc-category-toggle').forEach((input) => {
			if (
				Object.prototype.hasOwnProperty.call(
					preferences,
					input.value
				)
			) {
				preferences[input.value] = input.checked;
			}
		});

		return preferences;
	}

	/**
	 * Actualiza los controles de preferencias.
	 *
	 * @param {Object} preferences Preferencias guardadas.
	 */
	function setPreferences(preferences) {
		banner.querySelectorAll('.lcacc-category-toggle').forEach((input) => {
			input.checked = preferences[input.value] === true;
		});
	}

	/**
	 * Comprueba si una categoría está habilitada por el administrador.
	 *
	 * @param {string} category Nombre de la categoría.
	 * @returns {boolean} Estado de la categoría.
	 */
	function isCategoryEnabled(category) {
		return optionalCategories.includes(category)
			&& categories[category] === true;
	}

	/**
	 * Comprueba si existe autorización para una categoría.
	 *
	 * @param {string} category Nombre de la categoría.
	 * @returns {boolean} Estado del consentimiento.
	 */
	function hasCategoryConsent(category) {
		if (category === 'necessary') {
			return true;
		}

		return consentReady
			&& isCategoryEnabled(category)
			&& activePreferences[category] === true;
	}

	/**
	 * Comprueba si se ha retirado algún consentimiento opcional.
	 *
	 * @param {Object} previous Preferencias anteriores.
	 * @param {Object} current Preferencias nuevas.
	 * @returns {boolean} Indica si se ha retirado algún permiso.
	 */
	function hasRevokedConsent(previous, current) {
		return optionalCategories.some((category) => {
			return previous[category] === true
				&& current[category] !== true;
		});
	}

	/**
	 * Activa los scripts autorizados que todavía no se han ejecutado.
	 *
	 * Los scripts deben utilizar:
	 *
	 * type="text/plain"
	 * data-lcacc-category="functional|statistics|marketing"
	 *
	 * Para scripts externos puede utilizarse data-src.
	 */
	function activateAllowedScripts() {
		if (!scriptBlockingEnabled || !consentReady) {
			return;
		}

		document.querySelectorAll(
			'script[type="text/plain"][data-lcacc-category]'
		).forEach((originalScript) => {
			const category = originalScript.dataset.lcaccCategory;

			if (!hasCategoryConsent(category)) {
				return;
			}

			if (originalScript.dataset.lcaccActivated === 'true') {
				return;
			}

			const parent = originalScript.parentNode;

			if (!parent) {
				return;
			}

			const replacement = document.createElement('script');

			Array.from(originalScript.attributes).forEach((attribute) => {
				if (
					attribute.name === 'type'
					|| attribute.name === 'data-lcacc-category'
					|| attribute.name === 'data-lcacc-activated'
					|| attribute.name === 'data-src'
					|| attribute.name === 'src'
				) {
					return;
				}

				replacement.setAttribute(
					attribute.name,
					attribute.value
				);
			});

			const externalSource = originalScript.getAttribute('data-src');

			if (externalSource) {
				replacement.setAttribute('src', externalSource);
			} else if (originalScript.hasAttribute('src')) {
				replacement.setAttribute(
					'src',
					originalScript.getAttribute('src')
				);
			} else {
				replacement.textContent = originalScript.textContent;
			}

			parent.insertBefore(replacement, originalScript.nextSibling);

			originalScript.dataset.lcaccActivated = 'true';
		});
	}

	/**
	 * Observa scripts agregados dinámicamente al documento.
	 */
	function observeScripts() {
		if (
			!scriptBlockingEnabled
			|| !document.documentElement
			|| typeof MutationObserver === 'undefined'
		) {
			return;
		}

		const observer = new MutationObserver((mutations) => {
			const hasAddedNodes = mutations.some((mutation) => {
				return mutation.addedNodes.length > 0;
			});

			if (hasAddedNodes && consentReady) {
				activateAllowedScripts();
			}
		});

		observer.observe(document.documentElement, {
			childList: true,
			subtree: true
		});

		activateAllowedScripts();
	}

	/**
	 * Aplica las preferencias verificadas por el servidor.
	 *
	 * @param {Object} preferences Preferencias guardadas.
	 */
	function applyPreferences(preferences) {
		activePreferences = {
			necessary: true,
			functional: isCategoryEnabled('functional')
				&& preferences.functional === true,
			statistics: isCategoryEnabled('statistics')
				&& preferences.statistics === true,
			marketing: isCategoryEnabled('marketing')
				&& preferences.marketing === true
		};

		consentReady = true;

		activateAllowedScripts();
	}

	/**
	 * Limpia el mensaje de estado.
	 */
	function clearStatus() {
		if (!status) {
			return;
		}

		status.classList.remove('is-error');
		status.textContent = '';
	}

	/**
	 * Actualiza el estado visual de los botones.
	 *
	 * @param {boolean} busy Indica si hay una operación en curso.
	 */
	function setBusy(busy) {
		isSaving = busy;

		[
			acceptButton,
			rejectButton,
			customizeButton,
			saveButton,
			settingsTrigger
		].forEach((button) => {
			if (button) {
				button.disabled = busy;
			}
		});

		if (status && busy) {
			status.classList.remove('is-error');
			status.textContent = config.strings?.saving || '';
		}
	}

	/**
	 * Abre el banner y muestra las preferencias guardadas.
	 *
	 * @param {boolean} showPreferences Indica si abre la personalización.
	 */
	function openSettings(showPreferences = true) {
		if (isSaving) {
			return;
		}

		clearStatus();

		setPreferences(activePreferences);

		banner.hidden = false;

		if (preferencesPanel) {
			preferencesPanel.hidden = !showPreferences;
		}

		if (saveButton) {
			saveButton.hidden = !showPreferences;
		}

		if (acceptButton) {
			acceptButton.hidden = showPreferences;
		}

		if (customizeButton) {
			customizeButton.setAttribute(
				'aria-expanded',
				String(showPreferences)
			);
		}

		if (settingsTrigger) {
			settingsTrigger.setAttribute('aria-expanded', 'true');
		}

		if (showPreferences && preferencesPanel) {
			preferencesPanel.scrollIntoView({
				behavior: 'smooth',
				block: 'nearest'
			});
		}

		const firstButton = showPreferences ? saveButton : acceptButton;

		if (firstButton) {
			firstButton.focus({ preventScroll: true });
		}
	}

	/**
	 * Guarda las preferencias y las verifica mediante la API REST.
	 *
	 * @param {string} decision Decisión del usuario.
	 * @param {Object} preferences Preferencias seleccionadas.
	 */
	async function saveConsent(decision, preferences) {
		if (isSaving) {
			return;
		}

		const previousPreferences = { ...activePreferences };

		setBusy(true);

		try {
			const response = await fetch(config.restUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					decision,
					preferences
				})
			});

			const result = await response.json();

			if (!response.ok || !result.success) {
				throw new Error('Consent could not be saved.');
			}

			const consentResponse = await fetch(config.restUrl, {
				method: 'GET',
				credentials: 'same-origin',
				cache: 'no-store'
			});

			if (!consentResponse.ok) {
				throw new Error('Could not verify saved consent.');
			}

			const savedData = await consentResponse.json();

			if (
				!savedData.has_consent
				|| !savedData.consent
				|| !savedData.consent.preferences
			) {
				throw new Error('Saved consent could not be verified.');
			}

			const savedPreferences = savedData.consent.preferences;

			const newPreferences = {
				necessary: true,
				functional: isCategoryEnabled('functional')
					&& savedPreferences.functional === true,
				statistics: isCategoryEnabled('statistics')
					&& savedPreferences.statistics === true,
				marketing: isCategoryEnabled('marketing')
					&& savedPreferences.marketing === true
			};

			const consentRevoked = hasRevokedConsent(
				previousPreferences,
				newPreferences
			);

			applyPreferences(savedPreferences);
			setPreferences(activePreferences);

			if (preferencesPanel) {
				preferencesPanel.hidden = true;
			}

			if (saveButton) {
				saveButton.hidden = true;
			}

			if (acceptButton) {
				acceptButton.hidden = false;
			}

			if (customizeButton) {
				customizeButton.setAttribute('aria-expanded', 'false');
			}

			if (settingsTrigger) {
				settingsTrigger.setAttribute('aria-expanded', 'false');
			}

			banner.hidden = true;

			if (settingsTrigger) {
				settingsTrigger.hidden = false;
			}

			if (consentRevoked) {
				window.location.reload();
				return;
			}
		} catch (error) {
			console.error(
				'LCACC: error al guardar o verificar el consentimiento:',
				error
			);

			if (status) {
				status.classList.add('is-error');
				status.textContent =
					config.strings?.error
					|| 'Could not save your preferences. Please try again.';
			}
		} finally {
			setBusy(false);
		}
	}

	/*
	 * Aceptar todas las categorías opcionales habilitadas.
	 */
	if (acceptButton) {
		acceptButton.addEventListener('click', () => {
			const preferences = {};

			Object.keys(categories).forEach((category) => {
				preferences[category] = categories[category] === true;
			});

			saveConsent('accepted', preferences);
		});
	}

	/*
	 * Rechazar todas las categorías opcionales.
	 */
	if (rejectButton) {
		rejectButton.addEventListener('click', () => {
			const preferences = {};

			Object.keys(categories).forEach((category) => {
				preferences[category] = false;
			});

			saveConsent('rejected', preferences);
		});
	}

	/*
	 * Abrir o cerrar el panel de personalización.
	 */
	if (customizeButton && preferencesPanel && saveButton) {
		customizeButton.addEventListener('click', () => {
			if (isSaving) {
				return;
			}

			const isOpening = preferencesPanel.hidden;

			preferencesPanel.hidden = !isOpening;
			saveButton.hidden = !isOpening;

			if (acceptButton) {
				acceptButton.hidden = isOpening;
			}

			customizeButton.setAttribute(
				'aria-expanded',
				String(isOpening)
			);

			if (isOpening) {
				preferencesPanel.scrollIntoView({
					behavior: 'smooth',
					block: 'nearest'
				});
			}
		});
	}

	/*
	 * Guardar las preferencias personalizadas.
	 */
	if (saveButton) {
		saveButton.addEventListener('click', () => {
			saveConsent('customized', getPreferences());
		});
	}

	/*
	 * Reabrir las preferencias desde el botón permanente.
	 */
	if (settingsTrigger) {
		settingsTrigger.addEventListener('click', () => {
			openSettings(true);
		});
	}

	/**
	 * Comprueba si ya existe un consentimiento válido.
	 */
	async function initializeBanner() {
		if (settingsTrigger) {
			settingsTrigger.hidden = true;
		}

		try {
			const response = await fetch(config.restUrl, {
				method: 'GET',
				credentials: 'same-origin',
				cache: 'no-store'
			});

			if (!response.ok) {
				throw new Error('Consent status could not be checked.');
			}

			const data = await response.json();

			if (
				!data.has_consent
				|| !data.consent
				|| !data.consent.preferences
			) {
				activePreferences = {
					necessary: true,
					functional: false,
					statistics: false,
					marketing: false
				};

				consentReady = true;

				setPreferences(activePreferences);

				banner.hidden = false;

				if (preferencesPanel) {
					preferencesPanel.hidden = true;
				}

				if (saveButton) {
					saveButton.hidden = true;
				}

				if (acceptButton) {
					acceptButton.hidden = false;
				}

				if (customizeButton) {
					customizeButton.setAttribute('aria-expanded', 'false');
				}

				activateAllowedScripts();

				return;
			}

			setPreferences(data.consent.preferences);
			applyPreferences(data.consent.preferences);

			banner.hidden = true;

			if (settingsTrigger) {
				settingsTrigger.hidden = false;
				settingsTrigger.setAttribute('aria-expanded', 'false');
			}
		} catch (error) {
			console.error(
				'LCACC: error al comprobar el consentimiento:',
				error
			);

			activePreferences = {
				necessary: true,
				functional: false,
				statistics: false,
				marketing: false
			};

			consentReady = true;

			setPreferences(activePreferences);

			if (preferencesPanel) {
				preferencesPanel.hidden = true;
			}

			if (saveButton) {
				saveButton.hidden = true;
			}

			if (acceptButton) {
				acceptButton.hidden = false;
			}

			if (customizeButton) {
				customizeButton.setAttribute('aria-expanded', 'false');
			}

			banner.hidden = false;

			/*
			 * No se muestra el botón permanente hasta verificar
			 * que existe un consentimiento guardado.
			 */
			if (settingsTrigger) {
				settingsTrigger.hidden = true;
			}

			activateAllowedScripts();
		}
	}

	observeScripts();
	initializeBanner();
})();



