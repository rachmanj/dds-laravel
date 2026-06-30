(function ($) {
    'use strict';

    const PAGE_SIZE = 20;

    const DocumentsTable = {
        currentPage: 1,

        init() {
            this.$table = $('#distributedDocumentsTable');
            if (!this.$table.length) {
                return;
            }

            this.$groupRows = this.$table.find('.doc-group-row');
            this.$childRows = this.$table.find('.doc-child-row');
            this.$search = $('#docSearch');
            this.$statusFilter = $('#docStatusFilter');
            this.$resultsCount = $('#docResultsCount');
            this.$pagination = $('#docPagination');

            this.bindEvents();
            this.applyFilters();
        },

        bindEvents() {
            this.$search.on('input', () => {
                this.currentPage = 1;
                this.applyFilters();
            });

            this.$statusFilter.on('change', () => {
                this.currentPage = 1;
                this.applyFilters();
            });

            this.$table.on('click', '.doc-toggle', (event) => {
                event.preventDefault();
                const $groupRow = $(event.currentTarget).closest('.doc-group-row');
                this.toggleGroup($groupRow, $groupRow.attr('data-expanded') !== 'true');
            });

            $('#expandAllDocs').on('click', () => {
                this.$groupRows.each((_, row) => {
                    this.toggleGroup($(row), true);
                });
            });

            $('#collapseAllDocs').on('click', () => {
                this.$groupRows.each((_, row) => {
                    this.toggleGroup($(row), false);
                });
            });

            this.$pagination.on('click', '.page-link', (event) => {
                event.preventDefault();
                const $link = $(event.currentTarget);
                const page = parseInt($link.data('page'), 10);

                if (!page || $link.parent().hasClass('disabled') || $link.parent().hasClass('active')) {
                    return;
                }

                this.currentPage = page;
                this.renderPage(this.getFilteredGroups());
            });
        },

        toggleGroup($groupRow, expand) {
            const groupId = $groupRow.attr('data-group');
            const $children = this.$childRows.filter(`[data-parent="${groupId}"]`);

            $groupRow.attr('data-expanded', expand ? 'true' : 'false');

            if ($groupRow.is(':visible')) {
                $children.toggleClass('d-none', !expand);
            }
        },

        getFilteredGroups() {
            const searchTerm = (this.$search.val() || '').trim().toLowerCase();
            const statusFilter = this.$statusFilter.val() || '';
            const filtered = [];

            this.$groupRows.each((_, row) => {
                const $groupRow = $(row);
                const groupId = $groupRow.attr('data-group');
                const $children = this.$childRows.filter(`[data-parent="${groupId}"]`);
                const rows = [$groupRow.get(0), ...$children.get()];

                const matchesSearch =
                    !searchTerm ||
                    rows.some((element) => {
                        return ($(element).attr('data-search') || '').includes(searchTerm);
                    });

                const matchesStatus =
                    !statusFilter ||
                    rows.some((element) => {
                        return ($(element).attr('data-status') || '') === statusFilter;
                    });

                if (matchesSearch && matchesStatus) {
                    filtered.push({
                        id: groupId,
                        $groupRow,
                        $children,
                    });
                }
            });

            return filtered;
        },

        applyFilters() {
            const filteredGroups = this.getFilteredGroups();
            const totalPages = Math.max(1, Math.ceil(filteredGroups.length / PAGE_SIZE));

            if (this.currentPage > totalPages) {
                this.currentPage = totalPages;
            }

            this.renderPage(filteredGroups);
        },

        renderPage(filteredGroups) {
            const totalGroups = filteredGroups.length;
            const totalPages = Math.max(1, Math.ceil(totalGroups / PAGE_SIZE));
            const startIndex = (this.currentPage - 1) * PAGE_SIZE;
            const pageGroups = filteredGroups.slice(startIndex, startIndex + PAGE_SIZE);
            const visibleGroupIds = new Set(pageGroups.map((group) => group.id));

            this.$groupRows.addClass('d-none');
            this.$childRows.addClass('d-none');

            pageGroups.forEach((group) => {
                group.$groupRow.removeClass('d-none');

                if (group.$groupRow.attr('data-expanded') === 'true') {
                    group.$children.removeClass('d-none');
                }
            });

            this.$groupRows.each((_, row) => {
                const $groupRow = $(row);
                const groupId = $groupRow.attr('data-group');

                if (!visibleGroupIds.has(groupId) && $groupRow.attr('data-expanded') === 'true') {
                    this.$childRows.filter(`[data-parent="${groupId}"]`).addClass('d-none');
                }
            });

            this.updateResultsCount(totalGroups, pageGroups.length, startIndex);
            this.renderPagination(totalPages);
        },

        updateResultsCount(totalGroups, visibleCount, startIndex) {
            if (!this.$resultsCount.length) {
                return;
            }

            if (totalGroups === 0) {
                this.$resultsCount.text('No matching documents');
                return;
            }

            const from = startIndex + 1;
            const to = startIndex + visibleCount;
            this.$resultsCount.text(`Showing ${from}-${to} of ${totalGroups} groups`);
        },

        renderPagination(totalPages) {
            if (!this.$pagination.length) {
                return;
            }

            if (totalPages <= 1) {
                this.$pagination.empty();
                return;
            }

            let html = '<ul class="pagination pagination-sm">';
            html += this.paginationItem('Previous', this.currentPage - 1, this.currentPage === 1, false);

            let previousPage = 0;
            for (let page = 1; page <= totalPages; page += 1) {
                if (this.shouldShowPage(page, totalPages)) {
                    if (previousPage && page - previousPage > 1) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }

                    html += this.paginationItem(page, page, false, page === this.currentPage);
                    previousPage = page;
                }
            }

            html += this.paginationItem('Next', this.currentPage + 1, this.currentPage === totalPages, false);
            html += '</ul>';
            this.$pagination.html(html);
        },

        shouldShowPage(page, totalPages) {
            return (
                page === 1 ||
                page === totalPages ||
                Math.abs(page - this.currentPage) <= 1
            );
        },

        paginationItem(label, page, disabled, active) {
            const classes = ['page-item'];

            if (disabled) {
                classes.push('disabled');
            }

            if (active) {
                classes.push('active');
            }

            return `<li class="${classes.join(' ')}"><a class="page-link" href="#" data-page="${page}">${label}</a></li>`;
        },
    };

    $(document).ready(function () {
        DocumentsTable.init();
    });
})(jQuery);
