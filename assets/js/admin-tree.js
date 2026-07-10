/**
 * Admin category list: search and show-only-hidden filters.
 */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function initTree(root) {
		var searchInput = root.querySelector('.fliix-hcp__search');
		var showHiddenOnly = root.querySelector('.fliix-hcp__show-hidden-only');
		var noResults = root.querySelector('.fliix-hcp__no-results');
		var tree = root.querySelector('.fliix-hcp__tree');

		function getNodes() {
			return Array.prototype.slice.call(root.querySelectorAll('.fliix-hcp__node'));
		}

		function applyFilters() {
			var query = (searchInput && searchInput.value ? searchInput.value : '')
				.trim()
				.toLowerCase();
			var onlyHidden = showHiddenOnly && showHiddenOnly.checked;
			var nodes = getNodes();

			root.classList.toggle('is-searching', query.length > 0 || onlyHidden);

			nodes.forEach(function (node) {
				node.classList.remove('is-filtered-out', 'is-search-match');
			});

			if (!query && !onlyHidden) {
				if (noResults) {
					noResults.hidden = true;
				}
				if (tree) {
					tree.hidden = false;
				}
				return;
			}

			// Mark direct matches.
			nodes.forEach(function (node) {
				var searchBlob = (node.getAttribute('data-search') || '').toLowerCase();
				var isHidden = node.getAttribute('data-hidden') === '1';
				var matchesQuery = !query || searchBlob.indexOf(query) !== -1;
				var matchesHidden = !onlyHidden || isHidden;

				if (matchesQuery && matchesHidden) {
					node.classList.add('is-search-match');
				}
			});

			// Keep ancestors of matches visible so hierarchy context remains.
			nodes.forEach(function (node) {
				if (!node.classList.contains('is-search-match')) {
					return;
				}
				var parent = node.parentElement;
				while (parent && parent !== root) {
					if (parent.classList && parent.classList.contains('fliix-hcp__node')) {
						parent.classList.add('is-search-match');
					}
					parent = parent.parentElement;
				}
			});

			nodes.forEach(function (node) {
				if (!node.classList.contains('is-search-match')) {
					node.classList.add('is-filtered-out');
				}
			});

			var directMatches = nodes.filter(function (node) {
				var searchBlob = (node.getAttribute('data-search') || '').toLowerCase();
				var isHidden = node.getAttribute('data-hidden') === '1';
				var matchesQuery = !query || searchBlob.indexOf(query) !== -1;
				var matchesHidden = !onlyHidden || isHidden;
				return matchesQuery && matchesHidden;
			}).length;

			if (noResults) {
				noResults.hidden = directMatches > 0;
			}
			if (tree) {
				tree.hidden = directMatches === 0;
			}
		}

		if (searchInput) {
			searchInput.addEventListener('input', applyFilters);
			searchInput.addEventListener('search', applyFilters);
		}

		if (showHiddenOnly) {
			showHiddenOnly.addEventListener('change', applyFilters);
		}

		// Live-update data-hidden when checkboxes change (for "show only hidden").
		root.addEventListener('change', function (event) {
			var input = event.target;
			if (!input || input.type !== 'checkbox') {
				return;
			}
			if (
				input.name !== 'fliix_hcp_hide_category[]' &&
				input.name !== 'fliix_hcp_hide_products[]'
			) {
				return;
			}
			var node = input.closest('.fliix-hcp__node');
			if (!node) {
				return;
			}
			var catBox = node.querySelector('input[name="fliix_hcp_hide_category[]"]');
			var prodBox = node.querySelector('input[name="fliix_hcp_hide_products[]"]');
			var any = (catBox && catBox.checked) || (prodBox && prodBox.checked);
			node.setAttribute('data-hidden', any ? '1' : '0');
			node.classList.toggle('fliix-hcp__node--has-hidden', !!any);
			if (showHiddenOnly && showHiddenOnly.checked) {
				applyFilters();
			}
		});
	}

	ready(function () {
		var roots = document.querySelectorAll('[data-fliix-hcp-tree]');
		Array.prototype.forEach.call(roots, initTree);
	});
})();
