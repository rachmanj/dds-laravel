/* global jQuery */
(function ($) {
    'use strict';

    function searchUrl() {
        var el = document.querySelector('meta[name="menu-search-url"]');
        return (el && el.getAttribute('content')) || '/api/menu/search';
    }

    function scoreItem(item, q) {
        var title = (item.title || '').toLowerCase();
        if (title.indexOf(q) === 0) {
            return 0;
        }
        if (title.indexOf(q) !== -1) {
            return 1;
        }
        return 2;
    }

    function filterItems(allItems, q) {
        if (!q) {
            return [];
        }
        var scored = allItems
            .filter(function (item) {
                return (item.searchText || '').indexOf(q) !== -1;
            })
            .map(function (item) {
                return { item: item, score: scoreItem(item, q) };
            })
            .sort(function (a, b) {
                if (a.score !== b.score) {
                    return a.score - b.score;
                }
                return (a.item.title || '').localeCompare(b.item.title || '');
            });

        return scored.slice(0, 15).map(function (x) {
            return x.item;
        });
    }

    $(function () {
        var $input = $('#menu-search-input');
        var $results = $('#menu-search-results');
        var $wrapper = $('#menu-search-input-wrapper');
        var $container = $('#menu-search-container');
        if (!$input.length || !$results.length) {
            return;
        }

        var allItems = [];
        var filteredItems = [];
        var selectedIndex = -1;
        var loaded = false;
        var loading = false;
        var debounceTimer = null;

        function ensureItems(done) {
            if (loaded) {
                done();
                return;
            }
            if (loading) {
                return;
            }
            loading = true;
            $.getJSON(searchUrl())
                .done(function (data) {
                    allItems = data.items || [];
                    loaded = true;
                })
                .always(function () {
                    loading = false;
                    done();
                });
        }

        function hideResults() {
            $results.empty().hide();
            selectedIndex = -1;
        }

        function render() {
            $results.empty();
            if (!filteredItems.length) {
                $results.hide();
                return;
            }
            filteredItems.forEach(function (item, idx) {
                var $row = $('<div class="menu-search-item"/>')
                    .attr('data-url', item.route || '')
                    .attr('data-index', idx)
                    .toggleClass('active', idx === selectedIndex);
                var $icon = $('<i/>').addClass(item.icon || 'far fa-circle');
                var $text = $('<div class="menu-search-item-text"/>');
                $text.append($('<div class="menu-search-title"/>').text(item.title || ''));
                $text.append($('<div class="menu-search-breadcrumb"/>').text(item.breadcrumb || ''));
                $row.append($icon).append($text);
                $results.append($row);
            });
            $results.show();
        }

        function goToIndex(idx) {
            if (idx < 0 || idx >= filteredItems.length) {
                return;
            }
            var url = filteredItems[idx].route;
            if (url) {
                window.location.href = url;
            }
        }

        function updateFiltered() {
            var q = ($input.val() || '').toLowerCase().trim();
            if (!q) {
                filteredItems = [];
                hideResults();
                return;
            }
            filteredItems = filterItems(allItems, q);
            selectedIndex = filteredItems.length ? 0 : -1;
            render();
        }

        $input.on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                ensureItems(function () {
                    updateFiltered();
                });
            }, 200);
        });

        $input.on('focus', function () {
            ensureItems(function () {
                updateFiltered();
            });
        });

        $results.on('mousedown', '.menu-search-item', function (e) {
            e.preventDefault();
            var url = $(this).attr('data-url');
            if (url) {
                window.location.href = url;
            }
        });

        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                if ($container.length && $container.is(':visible')) {
                    e.preventDefault();
                    $input.trigger('focus');
                }
            }
        });

        $input.on('keydown', function (e) {
            if (!$results.is(':visible') || !filteredItems.length) {
                if (e.key === 'Escape') {
                    hideResults();
                }
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1);
                render();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                render();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                goToIndex(selectedIndex);
            } else if (e.key === 'Escape') {
                hideResults();
            }
        });

        $(document).on('click', function (e) {
            if (
                $wrapper.length &&
                !$wrapper.is(e.target) &&
                $wrapper.has(e.target).length === 0 &&
                !$results.is(e.target) &&
                $results.has(e.target).length === 0
            ) {
                hideResults();
            }
        });
    });
})(jQuery);
