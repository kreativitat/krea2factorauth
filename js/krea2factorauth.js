/*
 * Copyright (C) 2026 Kreativität Works <mail@kreativitat.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

(function () {
	'use strict';

	var loginBrandingObserverStarted = false;
	var loginBrandingApplying = false;
	var loginChallengeImageRequestId = 0;
	var loginLogoUrls = window.Krea2FactorAuthLoginLogoUrls || {};
	var missingLoginLogoEntities = window.Krea2FactorAuthMissingLoginLogoEntities || {};

	function normalizeDigits(value) {
		return String(value || '').replace(/\D/g, '').slice(0, 6);
	}

	function syncValue(container) {
		var hidden = container.querySelector('.krea2factorauth-code-value');
		var digits = container.querySelectorAll('.krea2factorauth-code-digit');
		var code = '';

		for (var i = 0; i < digits.length; i++) {
			code += normalizeDigits(digits[i].value).slice(0, 1);
		}

		if (hidden) {
			hidden.value = code;
		}
	}

	function fillFrom(container, startIndex, value) {
		var digits = container.querySelectorAll('.krea2factorauth-code-digit');
		var clean = normalizeDigits(value);

		for (var i = 0; i < clean.length && (startIndex + i) < digits.length; i++) {
			digits[startIndex + i].value = clean.charAt(i);
		}

		syncValue(container);

		var nextIndex = Math.min(startIndex + clean.length, digits.length - 1);
		if (digits[nextIndex]) {
			digits[nextIndex].focus();
			digits[nextIndex].select();
		}
	}

	function initContainer(container) {
		var digits = container.querySelectorAll('.krea2factorauth-code-digit');
		if (!digits.length) {
			return;
		}

		for (var i = 0; i < digits.length; i++) {
			(function (index) {
				var input = digits[index];

				input.addEventListener('input', function () {
					var clean = normalizeDigits(input.value);
					if (clean.length > 1) {
						fillFrom(container, index, clean);
						return;
					}

					input.value = clean;
					syncValue(container);

					if (clean && digits[index + 1]) {
						digits[index + 1].focus();
						digits[index + 1].select();
					}
				});

				input.addEventListener('keydown', function (event) {
					if (event.key === 'Backspace' && input.value === '' && digits[index - 1]) {
						digits[index - 1].focus();
						digits[index - 1].select();
					}
				});

				input.addEventListener('paste', function (event) {
					var clipboard = event.clipboardData || window.clipboardData;
					if (!clipboard) {
						return;
					}

					event.preventDefault();
					fillFrom(container, index, clipboard.getData('text'));
				});
			})(i);
		}

		var form = container.closest('form');
		if (form) {
			form.addEventListener('submit', function () {
				syncValue(container);
			});
		}

		syncValue(container);
	}

	function brandLogin() {
		var form = document.getElementById('login');
		if (!form) {
			return;
		}

		document.documentElement.classList.add('krea2factorauth-login-page');
		form.classList.add('krea2factorauth-branded-login');

		removeLoginLogoBlock();
		applyStableLoginBackground();

		var buttonLabel = window.Krea2FactorAuthLoginButtonLabel || '';
		var submit = document.querySelector('#login-submit-wrapper input[type="submit"]');
		if (submit && buttonLabel) {
			submit.value = buttonLabel;
			submit.setAttribute('aria-label', buttonLabel);
		}

		placeGoogleLoginAfterSubmit(form);
		removeNativeGoogleLogin(form);
		bindGoogleLoginSubmit(form);
		restoreLoginEntityIcon(form);
		bindLoginEntityVisualUpdates(form);
		bindLoginChallengeImage();
		scheduleLoginBranding(form);
		startLoginBrandingObserver(form);
	}

	function placeGoogleLoginAfterSubmit(form) {
		var row = form.querySelector('[data-krea2factorauth-google-login-row]');
		var submitWrapper = form.querySelector('#login-submit-wrapper');
		if (!row || !submitWrapper || !submitWrapper.parentNode) {
			return;
		}

		if (row.parentNode !== submitWrapper.parentNode || submitWrapper.nextElementSibling !== row) {
			submitWrapper.parentNode.insertBefore(row, submitWrapper.nextSibling);
		}

		row.classList.add('krea2factorauth-google-login-ready');
	}

	function removeNativeGoogleLogin(form) {
		if (!form.querySelector('[data-krea2factorauth-google-login]')) {
			return;
		}

		var nativeFlag = form.querySelector('input#beforeoauthloginredirect[name="beforeoauthloginredirect"]');
		if (nativeFlag && nativeFlag.parentNode && nativeFlag.parentNode.parentNode && nativeFlag.parentNode !== form) {
			nativeFlag.parentNode.parentNode.removeChild(nativeFlag.parentNode);
			return;
		}

		var links = form.querySelectorAll('a.alogin');
		for (var i = 0; i < links.length; i++) {
			var onclick = links[i].getAttribute('onclick') || '';
			if (onclick.indexOf('beforeoauthloginredirect') === -1) {
				continue;
			}

			var node = links[i];
			while (node && node.parentNode && node.parentNode !== form && String(node.parentNode.tagName || '').toLowerCase() !== 'body') {
				node = node.parentNode;
				if (node.className && String(node.className).indexOf('center') !== -1) {
					break;
				}
			}

			if (node && node.parentNode && node !== form) {
				node.parentNode.removeChild(node);
			}
		}
	}

	function bindGoogleLoginSubmit(form) {
		var button = form.querySelector('[data-krea2factorauth-google-login]');
		if (!button || button.getAttribute('data-krea2factorauth-google-login-bound') === '1') {
			return;
		}

		button.setAttribute('data-krea2factorauth-google-login-bound', '1');
		button.addEventListener('click', function (event) {
			var bridgeUrl = button.getAttribute('formaction') || '';
			if (!bridgeUrl) {
				return;
			}

			event.preventDefault();

			var flag = form.querySelector('[data-krea2factorauth-google-login-flag]');
			if (flag) {
				flag.value = 'google';
			}

			var containers = form.querySelectorAll('[data-krea2factorauth-code]');
			for (var i = 0; i < containers.length; i++) {
				syncValue(containers[i]);
			}

			form.setAttribute('action', bridgeUrl);
			form.setAttribute('method', 'post');

			if (typeof form.requestSubmit === 'function') {
				form.requestSubmit(button);
			} else {
				form.submit();
			}
		});
	}

	function restoreLoginEntityIcon(form) {
		var holder = form.querySelector('.multicompany-trinputlogin .tdinputlogin > .krea2factorauth-login-company-icon-holder')
			|| form.querySelector('.multicompany-trinputlogin .tdinputlogin > .fa-globe');
		if (!holder) {
			return;
		}

		var existingIcon = holder.querySelector('.krea2factorauth-login-company-icon-img');
		if (existingIcon && existingIcon.parentNode) {
			existingIcon.parentNode.removeChild(existingIcon);
		}

		holder.classList.remove('krea2factorauth-login-company-icon-holder', 'fa-shield-alt', 'krea2factorauth-login-company-icon-fallback');
		holder.classList.add('fa', 'fa-globe');
	}

	function removeLoginLogoBlock() {
		var logo = document.getElementById('img_logo');
		var block = logo ? logo.parentNode : document.getElementById('login_left');
		if (!block) {
			return;
		}

		while (block && block.id !== 'login_left' && block !== document.body) {
			block = block.parentNode;
		}
		if (!block || block.id !== 'login_left' || !block.parentNode) {
			return;
		}

		var next = block.nextSibling;
		while (next && next.nodeType === 3 && next.nodeValue.replace(/\s/g, '') === '') {
			next = next.nextSibling;
		}
		if (next && next.nodeType === 1 && String(next.tagName || '').toLowerCase() === 'br' && next.parentNode) {
			next.parentNode.removeChild(next);
		}

		block.parentNode.removeChild(block);
	}

	function applyStableLoginBackground() {
		if (!document.body) {
			return;
		}

		document.body.classList.add('krea2factorauth-stable-login-background');
		applyLoginBackgroundColor();
		document.body.style.backgroundImage = 'none';
		document.body.style.backgroundRepeat = 'no-repeat';
		document.body.style.backgroundAttachment = 'fixed';
		document.body.style.backgroundPosition = 'center center';
		document.body.style.backgroundSize = 'cover';
		if (document.body.style.opacity !== '1') {
			document.body.style.opacity = '1';
		}
	}

	function applyLoginBackgroundColor() {
		var color = normalizeHexColor(window.Krea2FactorAuthLoginBackgroundColor || '');
		if (!color) {
			return;
		}

		document.documentElement.style.setProperty('--krea2fa-login-background', color);
		if (document.body) {
			document.body.classList.add('krea2factorauth-stable-login-background');
			document.body.style.backgroundColor = color;
			document.body.style.backgroundImage = 'none';
			document.body.style.opacity = '1';
		}
	}

	function normalizeHexColor(color) {
		color = String(color || '').toLowerCase();
		if (/^#[0-9a-f]{6}$/.test(color)) {
			return color;
		}
		return '';
	}

	function reinforceLoginBranding(form) {
		if (loginBrandingApplying) {
			return;
		}

		loginBrandingApplying = true;
		removeLoginLogoBlock();
		applyLoginBackgroundColorForEntity(getSelectedLoginEntity());
		updateLoginChallengeImageForEntity(getSelectedLoginEntity());
		placeGoogleLoginAfterSubmit(form);
		bindGoogleLoginSubmit(form);
		restoreLoginEntityIcon(form);
		loginBrandingApplying = false;
	}

	function scheduleLoginBranding(form) {
		var delays = [0, 60, 180, 420, 760, 1200, 2000, 3200];

		for (var i = 0; i < delays.length; i++) {
			window.setTimeout(function () {
				reinforceLoginBranding(form);
			}, delays[i]);
		}

	}

	function bindLoginEntityVisualUpdates(form) {
		var entitySelect = form.querySelector('[name="entity"]');
		if (!entitySelect || entitySelect.getAttribute('data-krea2factorauth-visual-bound') === '1') {
			return;
		}

		entitySelect.setAttribute('data-krea2factorauth-visual-bound', '1');
		entitySelect.addEventListener('change', function () {
			scheduleLoginEntityVisualUpdate(entitySelect.value || '');
		});
		if (window.jQuery) {
			window.jQuery(entitySelect).on('select2:select.krea2factorauth-visual change.krea2factorauth-visual', function () {
				scheduleLoginEntityVisualUpdate(entitySelect.value || '');
				return true;
			});
		}
	}

	function scheduleLoginEntityVisualUpdate(entity) {
		var delays = [0, 80, 240, 600, 1200, 2200, 4200];
		for (var i = 0; i < delays.length; i++) {
			window.setTimeout(function () {
				var form = document.getElementById('login');
				if (form) {
					reinforceLoginBranding(form);
				} else {
					removeLoginLogoBlock();
					applyLoginBackgroundColorForEntity(entity);
				}
				updateLoginChallengeImageForEntity(getSelectedLoginEntity() || entity);
			}, delays[i]);
		}
	}

	function getSelectedLoginEntity() {
		var entitySelect = document.querySelector('form#login [name="entity"]');
		if (entitySelect && entitySelect.value) {
			return entitySelect.value;
		}

		return window.Krea2FactorAuthLoginEntity || '';
	}

	function startLoginBrandingObserver(form) {
		if (loginBrandingObserverStarted || typeof MutationObserver === 'undefined') {
			return;
		}

		if (!document.body) {
			return;
		}

		loginBrandingObserverStarted = true;
		var scheduled = false;
		var observer = new MutationObserver(function () {
			if (loginBrandingApplying || scheduled) {
				return;
			}
			if (!loginBrandingNeedsRepair()) {
				return;
			}

			scheduled = true;
			window.setTimeout(function () {
				scheduled = false;
				if (loginBrandingNeedsRepair()) {
					reinforceLoginBranding(form);
				}
			}, 0);
		});

		observer.observe(document.body, { attributes: true, attributeFilter: ['style', 'class'] });
	}

	function applyLoginBackgroundColorForEntity(entity) {
		var color = getLoginBackgroundColorForEntity(entity);
		if (!color) {
			return;
		}

		document.documentElement.style.setProperty('--krea2fa-login-background', color);
		if (document.body) {
			document.body.classList.add('krea2factorauth-stable-login-background');
			document.body.style.backgroundColor = color;
			document.body.style.backgroundImage = 'none';
			document.body.style.opacity = '1';
		}
	}

	function loginBrandingNeedsRepair() {
		if (document.body) {
			if (!document.body.classList.contains('krea2factorauth-stable-login-background')) {
				return true;
			}

			var bodystyle = window.getComputedStyle ? window.getComputedStyle(document.body) : null;
			if (bodystyle && bodystyle.backgroundImage && bodystyle.backgroundImage !== 'none') {
				return true;
			}
			if (bodystyle && bodystyle.opacity && bodystyle.opacity !== '1') {
				return true;
			}
		}

		return false;
	}

	function getLoginBackgroundColorForEntity(entity) {
		var colors = window.Krea2FactorAuthEntityBackgroundColors || {};
		var key = String(entity || window.Krea2FactorAuthLoginEntity || '');
		var color = normalizeHexColor(colors[key] || '');
		if (color) {
			return color;
		}

		return normalizeHexColor(window.Krea2FactorAuthLoginBackgroundDefaultColor || window.Krea2FactorAuthLoginBackgroundColor || '');
	}

	function normalizeLoginEntity(entity) {
		entity = String(entity || '').replace(/\D/g, '');
		entity = entity ? parseInt(entity, 10) : 0;

		return entity > 0 ? String(entity) : '';
	}

	function buildLoginLogoUrl(entity) {
		entity = normalizeLoginEntity(entity);
		if (!entity) {
			return '';
		}

		if (Object.prototype.hasOwnProperty.call(loginLogoUrls, entity)) {
			return String(loginLogoUrls[entity] || '');
		}

		var template = String(window.Krea2FactorAuthLoginLogoUrlTemplate || '');
		if (!template) {
			return '';
		}

		return template.replace('__ENTITY__', encodeURIComponent(entity));
	}

	function setLoginChallengeImageMissing(image, missing) {
		var row = image && image.closest ? image.closest('.krea2factorauth-login-challenge-image-row') : null;
		if (!row) {
			return;
		}

		row.classList.toggle('krea2factorauth-login-challenge-image-missing', !!missing);
	}

	function setLoginChallengeImageLoading(image, loading) {
		var row = image && image.closest ? image.closest('.krea2factorauth-login-challenge-image-row') : null;
		if (!row) {
			return;
		}

		row.classList.toggle('krea2factorauth-login-challenge-image-loading', !!loading);
	}

	function getLoginFallbackLogoUrl() {
		return String(window.Krea2FactorAuthLoginFallbackLogoUrl || '');
	}

	function applyLoadedLoginChallengeImage(image, entity, url) {
		image.setAttribute('data-krea2factorauth-login-logo-entity', normalizeLoginEntity(entity || ''));
		if (image.getAttribute('src') !== url) {
			image.setAttribute('src', url);
		}
		setLoginChallengeImageMissing(image, false);
		setLoginChallengeImageLoading(image, false);
	}

	function preloadLoginChallengeImage(image, entity, url, fallback) {
		var requestId;
		var probe;

		if (!url) {
			setLoginChallengeImageLoading(image, false);
			setLoginChallengeImageMissing(image, true);
			return;
		}

		if (image.getAttribute('src') === url) {
			applyLoadedLoginChallengeImage(image, entity, url);
			return;
		}

		requestId = ++loginChallengeImageRequestId;
		setLoginChallengeImageMissing(image, false);
		setLoginChallengeImageLoading(image, true);

		probe = new Image();
		probe.onload = function () {
			if (requestId !== loginChallengeImageRequestId) {
				return;
			}
			applyLoadedLoginChallengeImage(image, entity, url);
		};
		probe.onerror = function () {
			if (requestId !== loginChallengeImageRequestId) {
				return;
			}

			entity = normalizeLoginEntity(entity || '');
			if (entity) {
				missingLoginLogoEntities[entity] = true;
			}
			setLoginChallengeImageLoading(image, false);
			if (fallback) {
				setLoginChallengeImageMissing(image, true);
				return;
			}

			applyLoginChallengeFallbackImage(image, false, entity);
		};
		probe.src = url;
	}

	function applyLoginChallengeFallbackImage(image, fallbackfailed, entity) {
		var fallback = getLoginFallbackLogoUrl();
		if (!fallback || fallbackfailed) {
			setLoginChallengeImageLoading(image, false);
			setLoginChallengeImageMissing(image, true);
			return;
		}

		preloadLoginChallengeImage(image, entity || '', fallback, true);
	}

	function bindLoginChallengeImage() {
		var image = document.querySelector('form#login [data-krea2factorauth-login-logo]');
		if (!image || image.getAttribute('data-krea2factorauth-image-bound') === '1') {
			return;
		}

		image.setAttribute('data-krea2factorauth-image-bound', '1');
		image.addEventListener('load', function () {
			setLoginChallengeImageMissing(image, false);
		});
		image.addEventListener('error', function () {
			var failedEntity = normalizeLoginEntity(image.getAttribute('data-krea2factorauth-login-logo-entity') || '');
			if (failedEntity) {
				missingLoginLogoEntities[failedEntity] = true;
			}
			applyLoginChallengeFallbackImage(image, image.getAttribute('src') === getLoginFallbackLogoUrl(), failedEntity);
		});

		if (image.complete) {
			if (image.naturalWidth > 0) {
				setLoginChallengeImageMissing(image, false);
			} else {
				applyLoginChallengeFallbackImage(image, image.getAttribute('src') === getLoginFallbackLogoUrl(), image.getAttribute('data-krea2factorauth-login-logo-entity') || '');
			}
		}
	}

	function updateLoginChallengeImageForEntity(entity) {
		var image = document.querySelector('form#login [data-krea2factorauth-login-logo]');
		if (!image) {
			return;
		}

		bindLoginChallengeImage();
		entity = normalizeLoginEntity(entity || getSelectedLoginEntity());
		if (entity && missingLoginLogoEntities[entity]) {
			applyLoginChallengeFallbackImage(image, false, entity);
			return;
		}
		var url = buildLoginLogoUrl(entity);
		if (!url) {
			applyLoginChallengeFallbackImage(image, false, entity);
			return;
		}

		if (image.getAttribute('data-krea2factorauth-login-logo-entity') === entity && image.getAttribute('src') === url) {
			return;
		}

		preloadLoginChallengeImage(image, entity, url, url === getLoginFallbackLogoUrl());
	}

	function closeResetConfirm(backdrop) {
		if (backdrop && backdrop.parentNode) {
			backdrop.parentNode.removeChild(backdrop);
		}
	}

	function setFormPageY(form) {
		var input = form ? form.querySelector('input[name="page_y"]') : null;
		if (!input) {
			return;
		}

		input.value = String(window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0);
	}

	function normalizeGoogleEmails(value) {
		var seen = {};
		var emails = [];
		var invalid = [];
		var parts = String(value || '').split(/[,;\s]+/);
		var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

		for (var i = 0; i < parts.length; i++) {
			var raw = parts[i].trim();
			if (!raw) {
				continue;
			}
			var email = raw.toLowerCase();
			if (!emailPattern.test(email)) {
				invalid.push(raw);
				continue;
			}
			if (!seen[email]) {
				seen[email] = true;
				emails.push(email);
			}
		}

		return {
			emails: emails,
			invalid: invalid
		};
	}

	function showGoogleEmailModal(form) {
		var title = form.getAttribute('data-modal-title') || 'Google login emails';
		var text = form.getAttribute('data-modal-text') || '';
		var placeholder = form.getAttribute('data-modal-placeholder') || 'user@example.com, other@example.com';
		var action = form.getAttribute('data-modal-action') || 'Save';
		var cancel = form.getAttribute('data-modal-cancel') || 'Cancel';
		var invalid = form.getAttribute('data-modal-invalid') || 'Enter valid email addresses separated by commas.';
		var backdrop = document.createElement('div');

		backdrop.className = 'krea2factorauth-enrollment-modal-backdrop krea2factorauth-google-email-modal-backdrop';
		backdrop.setAttribute('role', 'presentation');
		backdrop.innerHTML = [
			'<section class="krea2factorauth-enrollment-modal krea2factorauth-google-email-modal" role="dialog" aria-modal="true">',
			'<div class="krea2factorauth-enrollment-modal-icon"><span class="fab fa-google"></span></div>',
			'<h2></h2>',
			'<p></p>',
			'<input type="text" class="flat krea2factorauth-google-email-modal-input">',
			'<div class="krea2factorauth-google-email-modal-error" role="alert" hidden></div>',
			'<div class="krea2factorauth-enrollment-modal-actions">',
			'<button type="button" class="krea2factorauth-primary-button" data-krea2factorauth-google-email-save></button>',
			'<button type="button" class="krea2factorauth-secondary-button" data-krea2factorauth-google-email-cancel></button>',
			'</div>',
			'</section>'
		].join('');

		backdrop.querySelector('h2').textContent = title;
		backdrop.querySelector('p').textContent = text;
		backdrop.querySelector('[data-krea2factorauth-google-email-save]').textContent = action;
		backdrop.querySelector('[data-krea2factorauth-google-email-cancel]').textContent = cancel;

		var input = backdrop.querySelector('.krea2factorauth-google-email-modal-input');
		var error = backdrop.querySelector('.krea2factorauth-google-email-modal-error');
		input.setAttribute('placeholder', placeholder);

		function submitEmails() {
			var normalized = normalizeGoogleEmails(input.value);
			if (!normalized.emails.length || normalized.invalid.length) {
				error.textContent = invalid;
				error.hidden = false;
				input.focus();
				return;
			}

			var hidden = form.querySelector('input[name="google_login_emails"]');
			if (hidden) {
				hidden.value = normalized.emails.join(', ');
			}
			setFormPageY(form);
			closeResetConfirm(backdrop);
			form.submit();
		}

		backdrop.addEventListener('click', function (event) {
			if (event.target === backdrop || event.target.hasAttribute('data-krea2factorauth-google-email-cancel')) {
				closeResetConfirm(backdrop);
			}
		});
		input.addEventListener('keydown', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				submitEmails();
			}
			if (event.key === 'Escape') {
				closeResetConfirm(backdrop);
			}
		});
		backdrop.querySelector('[data-krea2factorauth-google-email-save]').addEventListener('click', submitEmails);

		document.body.appendChild(backdrop);
		input.focus();
	}

	function getConfirmAttribute(form, submitter, attribute, fallback) {
		if (submitter && submitter.getAttribute && submitter.getAttribute(attribute)) {
			return submitter.getAttribute(attribute);
		}
		if (form && form.getAttribute && form.getAttribute(attribute)) {
			return form.getAttribute(attribute);
		}

		return fallback;
	}

	function submitConfirmedForm(form, submitter) {
		form.setAttribute('data-krea2factorauth-confirmed', '1');
		setFormPageY(form);

		if (submitter && typeof form.requestSubmit === 'function') {
			form.requestSubmit(submitter);
			return;
		}

		if (submitter && submitter.name && submitter.value) {
			var hidden = document.createElement('input');
			hidden.type = 'hidden';
			hidden.name = submitter.name;
			hidden.value = submitter.value;
			form.appendChild(hidden);
		}
		if (submitter && submitter.getAttribute) {
			if (submitter.getAttribute('formaction')) {
				form.setAttribute('action', submitter.getAttribute('formaction'));
			}
			if (submitter.getAttribute('formmethod')) {
				form.setAttribute('method', submitter.getAttribute('formmethod'));
			}
		}
		form.submit();
	}

	function showResetConfirm(form, submitter) {
		var title = getConfirmAttribute(form, submitter, 'data-confirm-title', 'Reset enrollment');
		var text = getConfirmAttribute(form, submitter, 'data-confirm-text', 'This will reset the current enrollment setup.');
		var action = getConfirmAttribute(form, submitter, 'data-confirm-action', 'Continue');
		var cancel = getConfirmAttribute(form, submitter, 'data-confirm-cancel', 'Cancel');
		var shouldSubmit = getConfirmAttribute(form, submitter, 'data-confirm-submit', '1') !== '0';
		var backdrop = document.createElement('div');

		backdrop.className = 'krea2factorauth-enrollment-modal-backdrop krea2factorauth-reset-confirm-backdrop';
		backdrop.setAttribute('role', 'presentation');
		backdrop.innerHTML = [
			'<section class="krea2factorauth-enrollment-modal krea2factorauth-reset-confirm" role="dialog" aria-modal="true">',
			'<div class="krea2factorauth-enrollment-modal-icon"><span class="fa fa-sync-alt"></span></div>',
			'<h2></h2>',
			'<p></p>',
			'<div class="krea2factorauth-enrollment-modal-actions">',
			'<button type="button" class="krea2factorauth-danger-button" data-krea2factorauth-confirm-yes></button>',
			'<button type="button" class="krea2factorauth-secondary-button" data-krea2factorauth-confirm-no></button>',
			'</div>',
			'</section>'
		].join('');

		backdrop.querySelector('h2').textContent = title;
		backdrop.querySelector('p').textContent = text;
		backdrop.querySelector('[data-krea2factorauth-confirm-yes]').textContent = action;
		backdrop.querySelector('[data-krea2factorauth-confirm-no]').textContent = cancel;

		backdrop.addEventListener('click', function (event) {
			if (event.target === backdrop || event.target.hasAttribute('data-krea2factorauth-confirm-no')) {
				closeResetConfirm(backdrop);
			}
		});

		backdrop.querySelector('[data-krea2factorauth-confirm-yes]').addEventListener('click', function () {
			closeResetConfirm(backdrop);
			if (!shouldSubmit) {
				return;
			}
			submitConfirmedForm(form, submitter);
		});

		document.body.appendChild(backdrop);
		backdrop.querySelector('[data-krea2factorauth-confirm-no]').focus();
	}

	function initResetConfirm() {
		document.addEventListener('submit', function (event) {
			var form = event.target;
			var submitter = event.submitter || document.activeElement;
			var formRequiresConfirm = form && form.getAttribute && form.getAttribute('data-krea2factorauth-reset-confirm') === '1';
			var submitterRequiresConfirm = submitter && submitter.getAttribute && submitter.getAttribute('data-krea2factorauth-button-confirm') === '1';

			if (!form || !form.getAttribute || (!formRequiresConfirm && !submitterRequiresConfirm)) {
				return;
			}
			if (form.getAttribute('data-krea2factorauth-confirmed') === '1') {
				return;
			}

			event.preventDefault();
			showResetConfirm(form, submitterRequiresConfirm ? submitter : null);
		});
	}

	function initExemptUserPasswordLock() {
		if (!document.querySelector('[data-krea2factorauth-exempt-password-lock]')) {
			return;
		}

		var links = document.querySelectorAll('a[href]');
		for (var i = 0; i < links.length; i++) {
			var href = links[i].getAttribute('href') || '';
			if (/[?&]action=password(send)?(&|$)/.test(href)) {
				links[i].style.display = 'none';
			}
		}
	}

	function initGoogleEmailModals() {
		document.addEventListener('click', function (event) {
			var button = event.target && event.target.closest ? event.target.closest('[data-krea2factorauth-google-email-form] button') : null;
			if (!button) {
				return;
			}

			var form = button.closest('[data-krea2factorauth-google-email-form]');
			if (!form) {
				return;
			}

			event.preventDefault();
			showGoogleEmailModal(form);
		});
	}

	function fallbackCopyText(value) {
		var textarea = document.createElement('textarea');
		textarea.value = value;
		textarea.setAttribute('readonly', 'readonly');
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		textarea.style.top = '0';
		document.body.appendChild(textarea);
		textarea.focus();
		textarea.select();

		try {
			document.execCommand('copy');
			document.body.removeChild(textarea);
			return true;
		} catch (error) {
			document.body.removeChild(textarea);
			return false;
		}
	}

	function setCopyPasswordButtonLabel(button, label) {
		var labelNode = button.querySelector('[data-krea2factorauth-copy-password-label]');
		if (labelNode) {
			labelNode.textContent = label;
		}
	}

	function initExceptionPasswordCopy() {
		document.addEventListener('click', function (event) {
			var button = event.target && event.target.closest ? event.target.closest('[data-krea2factorauth-copy-password]') : null;
			if (!button) {
				return;
			}

			event.preventDefault();

			var password = button.getAttribute('data-krea2factorauth-copy-password') || '';
			var copyLabel = button.getAttribute('data-copy-label') || 'Copy password';
			var copiedLabel = button.getAttribute('data-copied-label') || 'Copied';
			var errorLabel = button.getAttribute('data-copy-error-label') || 'Copy failed';

			function markCopied() {
				button.classList.remove('krea2factorauth-password-copy-error');
				button.classList.add('krea2factorauth-password-copy-copied');
				setCopyPasswordButtonLabel(button, copiedLabel);
				window.setTimeout(function () {
					button.classList.remove('krea2factorauth-password-copy-copied');
					setCopyPasswordButtonLabel(button, copyLabel);
				}, 2200);
			}

			function markError() {
				button.classList.remove('krea2factorauth-password-copy-copied');
				button.classList.add('krea2factorauth-password-copy-error');
				setCopyPasswordButtonLabel(button, errorLabel);
				window.setTimeout(function () {
					button.classList.remove('krea2factorauth-password-copy-error');
					setCopyPasswordButtonLabel(button, copyLabel);
				}, 2600);
			}

			if (!password) {
				markError();
				return;
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(password).then(markCopied).catch(function () {
					if (fallbackCopyText(password)) {
						markCopied();
					} else {
						markError();
					}
				});
				return;
			}

			if (fallbackCopyText(password)) {
				markCopied();
			} else {
				markError();
			}
		});
	}

	function initColorInputs() {
		var inputs = document.querySelectorAll('.krea2factorauth-color-input');
		for (var i = 0; i < inputs.length; i++) {
			(function (input) {
				var code = input.parentNode ? input.parentNode.querySelector('code') : null;
				if (!code) {
					return;
				}
				input.addEventListener('input', function () {
					code.textContent = input.value;
					var useDefault = input.parentNode ? input.parentNode.querySelector('input[name="login_background_entity_use_default"]') : null;
					if (useDefault) {
						useDefault.checked = false;
					}
				});
			})(inputs[i]);
		}
	}

	function init() {
		brandLogin();
		initResetConfirm();
		initGoogleEmailModals();
		initExceptionPasswordCopy();
		initExemptUserPasswordLock();
		initColorInputs();

		var containers = document.querySelectorAll('[data-krea2factorauth-code]');
		for (var i = 0; i < containers.length; i++) {
			initContainer(containers[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
