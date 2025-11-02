(function($){
    'use strict';

    function renderMembers(container, data, settings) {
        var layout = settings.layout;
        var columns = settings.columns;
        var wrapperClass = (layout === 'grid') ? 'yogesh-tm-grid yogesh-tm-col-' + columns : 'yogesh-tm-list';
        var $wrapper = $('<div/>', { 'class': wrapperClass });

        if ( data.members && data.members.length ) {
            data.members.forEach(function(m){
                var $item = $('<div/>', { 'class': 'yogesh-tm-item' });
                var $photo = $('<div/>', { 'class': 'yogesh-tm-photo' });
                if ( m.photo ) {
                    $photo.append('<img src="'+m.photo+'" alt="'+(m.full_name||'')+'" />');
                } else {
                    $photo.append('<div class="yogesh-placeholder">No Image</div>');
                }
                $item.append($photo);

                var $meta = $('<div/>', { 'class': 'yogesh-tm-meta' });
                $meta.append('<h4 class="yogesh-name">'+(m.full_name||'')+'</h4>');
                if ( m.role ) $meta.append('<div class="yogesh-role">'+m.role+'</div>');
                if ( m.email ) $meta.append('<div class="yogesh-email"><a href="mailto:'+m.email+'">'+m.email+'</a></div>');
                if ( m.skills && m.skills.length ) {
                    var $skills = $('<div class="yogesh-skills"></div>');
                    m.skills.forEach(function(s){ $skills.append('<span class="yogesh-skill">'+s+'</span>'); });
                    $meta.append($skills);
                }
                $item.append($meta);
                $wrapper.append($item);
            });
        } else {
            $wrapper.append('<div class="yogesh-no-members">No team members found.</div>');
        }

        container.find('.yogesh-tm-grid, .yogesh-tm-list, .yogesh-tm-loading').remove();
        container.append($wrapper);
    }

    function renderPagination(container, total, per_page, page) {
        var $p = container.find('.yogesh-tm-pagination');
        if ( !$p.length ) {
            $p = $('<div class="yogesh-tm-pagination"></div>');
            container.append($p);
        }
        var totalPages = Math.ceil(total / per_page) || 1;
        var html = '';
        html += '<button class="yogesh-page-prev" '+ (page<=1 ? 'disabled' : '') +'>Prev</button>';
        html += '<span class="yogesh-page-info"> Page ' + page + ' of ' + totalPages + '</span>';
        html += '<button class="yogesh-page-next" '+ (page>=totalPages ? 'disabled' : '') +'>Next</button>';
        $p.html(html);
    }

    function buildQueryFromState(container, state) {
        var q = {
            per_page: state.per_page,
            page: state.page
        };
        if ( state.role ) q.role = state.role;
        if ( state.skills ) q.skills = state.skills;
        if ( state.search ) q.search = state.search;
        return q;
    }

    function fetchMembers(container, state, settings) {
        var url = YOGESH_TM.rest_url;
        var q = buildQueryFromState(container, state);
        var paramString = Object.keys(q).map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(q[k]); }).join('&');
        var fullUrl = url + '?' + paramString;

        // Simple in-memory cache (short-lived) to avoid duplicate requests in quick succession
        if (!window._yogesh_tm_cache) window._yogesh_tm_cache = {};
        var cacheKey = fullUrl;
        if (window._yogesh_tm_cache[cacheKey]) {
            var cached = window._yogesh_tm_cache[cacheKey];
            renderMembers(container, cached, settings);
            renderPagination(container, cached.total, state.per_page, state.page);
            return;
        }

        $.getJSON(fullUrl).done(function(resp){
            window._yogesh_tm_cache[cacheKey] = resp;
            renderMembers(container, resp, settings);
            renderPagination(container, resp.total, state.per_page, state.page);
        }).fail(function(){
            container.find('.yogesh-tm-loading').text('Failed to load members.');
        });
    }

    function attachControls(container, settings, state) {
        // Search box
        if ( settings.showSearch ) {
            var $search = container.find('.yogesh-tm-search');
            if ( !$search.length ) {
                $search = $('<input class="yogesh-tm-search" placeholder="Search members..." />');
                container.prepend($search);
            }
            $search.off('input.yogesh').on('input.yogesh', function(){
                var v = $(this).val();
                state.search = v;
                state.page = 1;
                fetchMembers(container, state, settings);
            });
        }

        // Dark/light toggle
        if ( settings.showToggle ) {
            var $toggle = container.find('.yogesh-tm-toggle');
            if ( !$toggle.length ) {
                $toggle = $('<button class="yogesh-tm-toggle" data-mode="light">Dark</button>');
                container.prepend($toggle);
            }
            $toggle.off('click.yogesh').on('click.yogesh', function(){
                var $c = container;
                var mode = $(this).attr('data-mode') === 'dark' ? 'light' : 'dark';
                if ( mode === 'dark' ) {
                    $c.addClass('yogesh-tm-dark');
                    $(this).attr('data-mode','dark').text('Light');
                } else {
                    $c.removeClass('yogesh-tm-dark');
                    $(this).attr('data-mode','light').text('Dark');
                }
            });
        }

        // Pagination handlers
        container.off('click.yogesh', '.yogesh-page-prev, .yogesh-page-next');
        container.on('click.yogesh', '.yogesh-page-prev', function(){
            if ( state.page > 1 ) {
                state.page--;
                fetchMembers(container, state, settings);
            }
        });
        container.on('click.yogesh', '.yogesh-page-next', function(){
            state.page++;
            fetchMembers(container, state, settings);
        });
    }

    // Initialization: find widget containers and kick off fetch
    function initContainer($container) {
        var settings = {
            per_page: parseInt($container.attr('data-per-page') || 6, 10),
            role: $container.attr('data-filter-role') || '',
            skills: $container.attr('data-filter-skills') || '',
            layout: $container.attr('data-layout') || 'grid',
            columns: $container.attr('data-columns') || '3',
            showSearch: $container.attr('data-show-search') === '1',
            showToggle: $container.attr('data-show-toggle') === '1'
        };

        var state = {
            per_page: settings.per_page,
            page: 1,
            role: settings.role,
            skills: settings.skills,
            search: ''
        };

        // Attach controls (search, toggle) and handlers
        attachControls($container, settings, state);

        // Initial fetch
        fetchMembers($container, state, settings);
    }

    // On DOM ready, init all instances
    $(document).ready(function(){
        $('.yogesh-tm-widget').each(function(){
            var $c = $(this);
            // avoid double-init
            if ( $c.data('yogesh-initialized') ) return;
            $c.data('yogesh-initialized', true);
            initContainer($c);
        });
    });

    // For Elementor editor where elements can be live-inserted, listen to custom event
    // Elementor triggers 'preview:loaded' and other events; also handle elementor/frontend/init
    $(window).on('elementor/frontend/init', function() {
        // re-init on widget mount (Elementor fires global events — but to keep generic, watch DOM changes)
        $(document).on('DOMContentLoaded elementor/popup/show elementor/frontend/init', function(){
            $('.yogesh-tm-widget').each(function(){
                var $c = $(this);
                if ( $c.data('yogesh-initialized') ) return;
                $c.data('yogesh-initialized', true);
                initContainer($c);
            });
        });
    });

})(jQuery);
