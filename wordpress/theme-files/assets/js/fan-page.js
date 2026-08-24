/**
 * Fan page behaviour: spoiler reveals and section-nav highlighting.
 *
 * Both are progressive — with JS off, spoilers stay hidden (the safe state)
 * and the nav is a plain list of anchor links.
 */
(function () {
	'use strict';

	/* ---- Spoilers: reveal on request, and stay revealed ---- */
	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('.fan-spoiler-toggle');
		if (!toggle) {
			return;
		}
		var shell = toggle.closest('[data-spoiler]');
		var content = shell && shell.querySelector('.fan-spoiler-content');
		if (!content) {
			return;
		}
		content.hidden = false;
		toggle.remove();
	});

	/* ---- Section nav: mark the section currently in view ---- */
	var nav = document.querySelector('.fan-nav');
	if (!nav || !('IntersectionObserver' in window)) {
		return;
	}

	var links = {};
	nav.querySelectorAll('a[href^="#"]').forEach(function (link) {
		links[link.getAttribute('href').slice(1)] = link;
	});

	var sections = Object.keys(links)
		.map(function (id) { return document.getElementById(id); })
		.filter(Boolean);

	if (!sections.length) {
		return;
	}

	var visible = new Set();

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				visible.add(entry.target.id);
			} else {
				visible.delete(entry.target.id);
			}
		});

		// Highlight the topmost section currently on screen.
		var current = sections.filter(function (section) {
			return visible.has(section.id);
		})[0];

		Object.keys(links).forEach(function (id) {
			links[id].classList.toggle('is-current', !!current && id === current.id);
		});
	}, {
		// Ignore the sticky header + nav strip when deciding what is "in view".
		rootMargin: '-140px 0px -60% 0px'
	});

	sections.forEach(function (section) {
		observer.observe(section);
	});
})();
