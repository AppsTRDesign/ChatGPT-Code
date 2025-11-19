(function($){
    var Reports = {
        init: function(){
            if ( 'undefined' === typeof NoaSoftAiReports ) {
                return;
            }
            this.$container = $('.noasoft-ai-reports');
            if ( ! this.$container.length ) {
                return;
            }
            this.$panel = this.$container.find('.noasoft-ai-report-panel');
            this.$list = this.$container.find('.noasoft-ai-reports-list tbody');
            this.$button = this.$container.find('.noasoft-generate-report');
            this.bindEvents();
            this.renderCharts();
        },
        bindEvents: function(){
            var self = this;
            this.$container.on('click', '.noasoft-generate-report', function(e){
                e.preventDefault();
                self.generateReport();
            });
            this.$container.on('click', '.view-report', function(e){
                e.preventDefault();
                var id = $(this).data('report-id');
                if ( id ) {
                    self.fetchReport( id );
                }
            });
        },
        generateReport: function(){
            if ( 'undefined' !== typeof NoaSoftAiReports && false === NoaSoftAiReports.enabled ) {
                return;
            }
            var self = this;
            this.toggleButton( true, ( NoaSoftAiReports && NoaSoftAiReports.strings ? NoaSoftAiReports.strings.creating : '' ) );
            $.post( NoaSoftAiReports.ajax_url, {
                action: 'noasoft_ai_generate_report',
                nonce: NoaSoftAiReports.nonce
            } ).done( function( response ){
                if ( response && response.success && response.data ) {
                    self.replacePanel( response.data.html );
                    self.prependRow( response.data.report );
                    self.showToast( response.data.message || (NoaSoftAiReports.strings ? NoaSoftAiReports.strings.created : '') , 'success' );
                } else {
                    self.showToast( self.getErrorMessage( response ), 'error' );
                }
            } ).fail( function(){
                self.showToast( self.getDefaultError(), 'error' );
            } ).always( function(){
                self.toggleButton( false );
            } );
        },
        fetchReport: function( id ){
            var self = this;
            this.$panel.addClass('loading');
            $.post( NoaSoftAiReports.ajax_url, {
                action: 'noasoft_ai_get_report',
                nonce: NoaSoftAiReports.nonce,
                report_id: id
            } ).done( function( response ){
                if ( response && response.success && response.data ) {
                    self.replacePanel( response.data.html );
                } else {
                    self.showToast( self.getErrorMessage( response ), 'error' );
                }
            } ).fail( function(){
                self.showToast( ( NoaSoftAiReports && NoaSoftAiReports.strings ? NoaSoftAiReports.strings.fetch_error : self.getDefaultError() ), 'error' );
            } ).always( function(){
                self.$panel.removeClass('loading');
            } );
        },
        prependRow: function( report ){
            if ( ! report || ! report.id ) {
                return;
            }
            var $existing = this.$list.find('tr[data-report-id="' + report.id + '"]');
            if ( $existing.length ) {
                $existing.remove();
            }
            this.$list.find('td[colspan]').closest('tr').remove();
            var rowHtml = '<tr class="noasoft-report-row" data-report-id="' + report.id + '">' +
                '<td>' + this.escape( report.title ) + '</td>' +
                '<td>' + this.escape( report.created_at ) + '</td>' +
                '<td>' +
                    '<button type="button" class="button button-small view-report" data-report-id="' + report.id + '">' + this.escape( NoaSoftAiReports.strings.view || 'Görüntüle' ) + '</button> ' +
                    '<a class="button button-small" href="' + report.pdf + '">' + this.escape( NoaSoftAiReports.strings.pdf || 'PDF' ) + '</a>' +
                '</td>' +
            '</tr>';
            this.$list.prepend( rowHtml );
        },
        replacePanel: function( html ){
            this.$panel.html( html );
            this.renderCharts();
        },
        renderCharts: function(){
            if ( 'undefined' === typeof Chart ) {
                return;
            }
            var $view = this.$panel.find('.noasoft-ai-report-view');
            if ( ! $view.length ) {
                return;
            }
            var dataAttr = $view.data('chart');
            if ( 'string' === typeof dataAttr ) {
                try {
                    dataAttr = JSON.parse( dataAttr );
                } catch (e) {
                    dataAttr = null;
                }
            }
            if ( ! dataAttr ) {
                return;
            }
            if ( this.engagementChart ) {
                this.engagementChart.destroy();
            }
            if ( this.ordersChart ) {
                this.ordersChart.destroy();
            }
            var engagementCtx = document.getElementById('noasoft-report-engagement');
            var ordersCtx = document.getElementById('noasoft-report-orders');
            if ( engagementCtx && dataAttr.engagement ) {
                this.engagementChart = new Chart( engagementCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: dataAttr.engagement.labels,
                        datasets: [{
                            label: 'UX',
                            backgroundColor: '#2563eb',
                            data: dataAttr.engagement.data
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                } );
            }
            if ( ordersCtx && dataAttr.orders ) {
                this.ordersChart = new Chart( ordersCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: dataAttr.orders.labels,
                        datasets: [{
                            backgroundColor: ['#16a34a','#eab308','#f97316','#ef4444','#94a3b8'],
                            data: dataAttr.orders.data
                        }]
                    },
                    options: { responsive: true }
                } );
            }
        },
        toggleButton: function( loading, text ){
            if ( ! this.$button.length ) {
                return;
            }
            if ( ! this.$button.data('label') ) {
                this.$button.data('label', this.$button.text() );
            }
            if ( loading ) {
                this.$button.prop('disabled', true).text( text || this.$button.data('loading') || this.$button.data('label') );
            } else {
                this.$button.prop('disabled', false).text( this.$button.data('label') );
            }
        },
        showToast: function( message, type ){
            if ( window.NoaSoftToast && message ) {
                NoaSoftToast.show( message, type || 'info' );
            }
        },
        getErrorMessage: function( response ){
            if ( response && response.data && response.data.message ) {
                return response.data.message;
            }
            return this.getDefaultError();
        },
        getDefaultError: function(){
            return NoaSoftAiReports && NoaSoftAiReports.strings ? NoaSoftAiReports.strings.error : 'Hata oluştu';
        },
        escape: function( text ){
            return $('<div>').text( text || '' ).html();
        }
    };

    $(function(){
        Reports.init();
    });
})(jQuery);
