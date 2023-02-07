// lazyload config

angular.module('app')
    /**
   * jQuery plugin config use ui-jq directive , config the js and css files that required
   * key: function name of the jQuery plugin
   * value: array of the css js file located
   */
  .constant('JQ_CONFIG', {
      easyPieChart:   [   '../libs/jquery/jquery.easy-pie-chart/dist/jquery.easypiechart.fill.js'],
      sparkline:      [   '../libs/jquery/jquery.sparkline/dist/jquery.sparkline.retina.js'],
      plot:           [   '../libs/jquery/flot/jquery.flot.js',
                          '../libs/jquery/flot/jquery.flot.pie.js', 
                          '../libs/jquery/flot/jquery.flot.resize.js',
                          '../libs/jquery/flot.tooltip/js/jquery.flot.tooltip.min.js',
                          '../libs/jquery/flot.orderbars/js/jquery.flot.orderBars.js',
                          '../libs/jquery/flot-spline/js/jquery.flot.spline.min.js'],
      moment:         [   '../libs/jquery/moment/moment.js'],
      screenfull:     [   '../libs/jquery/screenfull/dist/screenfull.js'],
      slimScroll:     [   '../libs/jquery/slimscroll/jquery.slimscroll.min.js'],
      sortable:       [   '../libs/jquery/html5sortable/jquery.sortable.js'],
      nestable:       [   '../libs/jquery/nestable/jquery.nestable.js',
                          '../libs/jquery/nestable/jquery.nestable.css'],
      filestyle:      [   '../libs/jquery/bootstrap-filestyle/src/bootstrap-filestyle.js'],
      slider:         [   '../libs/jquery/bootstrap-slider/bootstrap-slider.js',
                          '../libs/jquery/bootstrap-slider/bootstrap-slider.css'],
      chosen:         [   '../libs/jquery/chosen/chosen.jquery.min.js',
                          '../libs/jquery/chosen/bootstrap-chosen.css'],
      TouchSpin:      [   '../libs/jquery/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.js',
                          '../libs/jquery/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.css'],
      wysiwyg:        [   '../libs/jquery/bootstrap-wysiwyg/bootstrap-wysiwyg.js',
                          '../libs/jquery/bootstrap-wysiwyg/external/jquery.hotkeys.js'],
      dataTable:      [   '../libs/jquery/datatables/media/js/jquery.dataTables.min.js',
                          '../libs/jquery/plugins/integration/bootstrap/3/dataTables.bootstrap.js',
                          '../libs/jquery/plugins/integration/bootstrap/3/dataTables.bootstrap.css'],
      vectorMap:      [   '../libs/jquery/bower-jvectormap/jquery-jvectormap-1.2.2.min.js', 
                          '../libs/jquery/bower-jvectormap/jquery-jvectormap-world-mill-en.js',
                          '../libs/jquery/bower-jvectormap/jquery-jvectormap-us-aea-en.js',
                          '../libs/jquery/bower-jvectormap/jquery-jvectormap.css'],
      footable:       [   '../libs/jquery/footable/v3/js/footable.min.js',
                          '../libs/jquery/footable/v3/css/footable.bootstrap.min.css'],
      fullcalendar:   [   '../libs/jquery/moment/moment.js',
                          '../libs/jquery/fullcalendar/dist/fullcalendar.min.js',
                          '../libs/jquery/fullcalendar/dist/fullcalendar.css',
                          '../libs/jquery/fullcalendar/dist/fullcalendar.theme.css'],
      daterangepicker:[   '../libs/jquery/moment/moment.js',
                          '../libs/jquery/bootstrap-daterangepicker/daterangepicker.js',
                          '../libs/jquery/bootstrap-daterangepicker/daterangepicker-bs3.css'],
      tagsinput:      [   '../libs/jquery/bootstrap-tagsinput/dist/bootstrap-tagsinput.js',
                          '../libs/jquery/bootstrap-tagsinput/dist/bootstrap-tagsinput.css'],
      pdfmake:        [
                            'bower_components/pdfmake/build/pdfmake.min.js',
                            'bower_components/pdfmake/build/vfs_fonts.js',
                        ],
        html2canvas:        [
            'bower_components/html2canvas/build/html2canvas.min.js',
        ],
      EXIF:        [
                            'bower_components/exif-js/exif.js',
                        ]
  
                      
    }
  )
  .constant('MODULE_CONFIG', [
      {
          name: 'ngAnimate',
          files: [
              '../libs/angular/angular-animate/angular-animate.js',
              '../libs/assets/animate.css/animate.css',
          ]
      },
      {
          name: 'ngGrid',
          files: [
              '../libs/angular/ng-grid/build/ng-grid.min.js',
              '../libs/angular/ng-grid/ng-grid.min.css',
              '../libs/angular/ng-grid/ng-grid.bootstrap.css'
          ]
      },
      {
          name: 'ui.grid',
          files: [
              '../libs/angular/angular-ui-grid/ui-grid.min.js',
              '../libs/angular/angular-ui-grid/ui-grid.min.css',
              '../libs/angular/angular-ui-grid/ui-grid.bootstrap.css'
          ]
      },
      { name: 'ui.grid.edit', files: [] },
      { name: 'ui.grid.selection', files: [] },
      { name: 'ui.grid.autoResize', files: [] },
      { name: 'ui.grid.pagination', files: [] },
      { name: 'ui.grid.resizeColumns', files: [] },
      { name: 'ui.grid.moveColumns', files: [] },
      {
          name: 'ui.select',
          files: [
              '../libs/angular/angular-ui-select/dist/select.min.js',
              '../libs/angular/angular-ui-select/dist/select.min.css',
              'https://cdnjs.cloudflare.com/ajax/libs/select2/3.4.5/select2.css'
          ]
      },
      {
          name:'angularFileUpload',
          files: [
            '../libs/angular/angular-file-upload/angular-file-upload.js'
          ]
      },
      {
          name:'ui.calendar',
          files: ['../libs/angular/angular-ui-calendar/src/calendar.js']
      },
      {
          name: 'ngImgCrop',
          files: [
              '../libs/angular/ngImgCrop/compile/minified/ng-img-crop.js',
              '../libs/angular/ngImgCrop/compile/minified/ng-img-crop.css'
          ]
      },
      {
          name: 'angularBootstrapNavTree',
          files: [
              '../libs/angular/angular-bootstrap-nav-tree/dist/abn_tree_directive.js',
              '../libs/angular/angular-bootstrap-nav-tree/dist/abn_tree.css'
          ]
      },
      {
          name: 'toaster',
          files: [
              '../libs/angular/angularjs-toaster/toaster.js',
              '../libs/angular/angularjs-toaster/toaster.css'
          ]
      },
      {
          name: 'textAngular',
          files: [
              '../libs/angular/textAngular/dist/textAngular-sanitize.min.js',
              '../libs/angular/textAngular/dist/textAngular.min.js'
          ]
      },
      {
          name: 'vr.directives.slider',
          files: [
              '../libs/angular/venturocket-angular-slider/build/angular-slider.min.js',
              '../libs/angular/venturocket-angular-slider/build/angular-slider.css'
          ]
      },
      {
          name: 'com.2fdevs.videogular',
          files: [
              '../libs/angular/videogular/videogular.min.js'
          ]
      },
      {
          name: 'com.2fdevs.videogular.plugins.controls',
          files: [
              '../libs/angular/videogular-controls/controls.min.js'
          ]
      },
      {
          name: 'com.2fdevs.videogular.plugins.buffering',
          files: [
              '../libs/angular/videogular-buffering/buffering.min.js'
          ]
      },
      {
          name: 'com.2fdevs.videogular.plugins.overlayplay',
          files: [
              '../libs/angular/videogular-overlay-play/overlay-play.min.js'
          ]
      },
      {
          name: 'com.2fdevs.videogular.plugins.poster',
          files: [
              '../libs/angular/videogular-poster/poster.min.js'
          ]
      },
      {
          name: 'com.2fdevs.videogular.plugins.imaads',
          files: [
              '../libs/angular/videogular-ima-ads/ima-ads.min.js'
          ]
      },
      {
          name: 'xeditable',
          files: [
              '../libs/angular/angular-xeditable/dist/js/xeditable.min.js',
              '../libs/angular/angular-xeditable/dist/css/xeditable.css'
          ]
      },
      {
          name: 'smart-table',
          files: [
              'bower_components/angular-smart-table/dist/smart-table.js'
          ]
      },
      {
          name: 'angular-skycons',
          files: [
              '../libs/angular/angular-skycons/angular-skycons.js'
          ]
      },
      {
          name: 'angular-hotkeys',
          files: [
              'bower_components/angular-hotkeys/build/hotkeys.min.js',
              'bower_components/angular-hotkeys/build/hotkeys.min.css',
          ]
      },
      {
          name: 'ngTagsInput',
          files: [
              '../libs/angular/ng-tag/ng-tags-input.min.js',
              '../libs/angular/ng-tag/ng-tags-input.min.css'
          ]
      },
      {
          name: 'rgkevin.datetimeRangePicker',
          files: [
              'bower_components/datetimeRangePicker/range-picker.js',
              'bower_components/datetimeRangePicker/range-picker.css'
          ]
      },
      {
          name: 'ui.bootstrap.datetimepicker',
          files: [              
              'js/libs/datetimepicker.js',
              'js/libs/datetimepicker.templates.js',
              'css/datetimepicker.css',
          ]
      },
      {
          name: 'ui.chart',
          files: [
              'bower_components/angular-ui-chart/src/chart.js',
              'bower_components/jqplot/plugins/jqplot.barRenderer.min.js',
              'bower_components/jqplot/plugins/jqplot.pieRenderer.min.js',
              'bower_components/jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
              'bower_components/jqplot/plugins/jqplot.cursor.min.js',
              'bower_components/jqplot/plugins/jqplot.highlighter.min.js',
              'bower_components/jqplot/plugins/jqplot.donutRenderer.min.js',
              'bower_components/jqplot/plugins/jqplot.pointLabels.min.js',
              'bower_components/jqplot/plugins/jqplot.enhancedLegendRenderer.min.js',
              'bower_components/jqplot/plugins/jqplot.canvasTextRenderer.min.js',
              'bower_components/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js'
            ]
      },
      {
          name: 'ngListSelect',
          files: [
              'bower_components/ngListSelect/ngListSelect.js',
          ]
      },
      {
          name: 'ngAside',
          files: [
              'bower_components/angular-aside/dist/js/angular-aside.js',
              'bower_components/angular-aside/dist/css/angular-aside.css',
              //'js/controllers/aside/ticketfilter.controller.js'
          ]
      },
      {
          name: 'cfp.hotkeys',
          files: [
              'bower_components/angular-hotkeys/build/hotkeys.js',
          ]
      },
      {
          name: 'ui.bootstrap.contextMenu',
          files: [
              'js/directives/ui-contextmenu.js',
          ]
      },
      {
          name: 'ui.jq',
          files: [
              'js/directives/ui-jq.js',
          ]
      },
      {
          name: 'ngDraggable',
          files: [
              'bower_components/ngDraggable/ngDraggable.js',
          ]
      },
      {
          name: 'material.components.sidenav',
          files: [
            
              
              //'bower_components/bower_components/angular-material/modules/js/sidenav/sidenav.min.css',
              // 'bower_components/angular-material/angular-material.js',
              // 'bower_components/angular-material/angular-material.css',
              // 'bower_components/angular-material/angular-material.min.js',
              // 'bower_components/angular-material/angular-material.min.css',
              // 'bower_components/angular-material/modules/js/sidenav/sidenav.js',
              // 'bower_components/angular-material/modules/js/sidenav/sidenav.css',
              // 'bower_components/angular-material/modules/js/sidenav/sidenav.min.css',
              // 'bower_components/angular-material/modules/js/sidenav/sidenav.min.js'
              
          ]
      },
      {
          name: 'ngDragDrop',
          files: [
              'bower_components/angular-dragdrop/src/angular-dragdrop.min.js',
          ]
      },
      {
          name: 'angularjs-dropdown-multiselect',
          files: [
              'bower_components/lodash/dist/lodash.js',
              'bower_components/angularjs-dropdown-multiselect/dist/index.css',
              'bower_components/angularjs-dropdown-multiselect/dist/angularjs-dropdown-multiselect.min.js',
          ]
      },
      {
          name: 'blockUI',
          files: [
              'bower_components/angular-block-ui/dist/angular-block-ui.min.css',
              'bower_components/angular-block-ui/dist/angular-block-ui.min.js',
          ]
      },
      {
          name: 'ngWebworker',
          files: [
              'bower_components/ng-webworker/src/ng-webworker.js',
          ]
      },
      {
          name: 'ngThread',
          files: [
              'bower_components/thread/thread.js',
          ]
      },
      {
          name: 'angular-duration-format',
          files: [
              'bower_components/angular-duration-format/dist/angular-duration-format.js',
          ]
      },
      {
          name: 'nvd3',
          files: [
              'bower_components/angular-nvd3/dist/angular-nvd3.js',
              'bower_components/nvd3/build/nv.d3.css',
          ]
      },
      {
          name: 'dndLists',
          files: [
              'bower_components/angular-drag-and-drop-lists/angular-drag-and-drop-lists.js',
              'css/multi.css',
          ]
      },
      {
          name: 'luegg.directives',
          files: [
              'bower_components/angular-scroll-glue/src/scrollglue.js',
          ]
      },
      {
          name: 'ngFileUpload',
          files: [
              'bower_components/ng-file-upload-shim/ng-file-upload-shim.js',
              'bower_components/ng-file-upload/ng-file-upload.js',
          ]
      },
      {
          name: 'mgcrea.ngStrap',
          files: [
              'bower_components/angular-strap/dist/angular-strap.min.js',
              'bower_components/angular-strap/dist/angular-strap.tpl.min.js',
          ]
      },
      {
          name: 'ngQuill',
          files: [                            
              'bower_components/quill/quill.snow.css',
              // 'bower_components/quill/quill.bubble.css',              
              'bower_components/ngQuill/dist/ng-quill.js',
          ]
      },
      {
          name: 'angular-highlight',
          files: [                            
              '../libs/angular/angular-highlight/angular-highlight.js',              
          ]
      },
      {
          name: 'angularResizable',
          files: [                            
              'bower_components/angular-resizable/angular-resizable.min.css',
              'bower_components/angular-resizable/src/angular-resizable.js',
          ]
      },
      {
          name: 'bootstrapLightbox',
          files: [                            
              'bower_components/angular-bootstrap-lightbox/dist/angular-bootstrap-lightbox.css',
              'bower_components/angular-bootstrap-lightbox/dist/angular-bootstrap-lightbox.js',
          ]
      },
      {
          name: 'cp.ng.fix-image-orientation',
          files: [                                          
              'bower_components/angular-fix-image-orientation/angular-fix-image-orientation.js',
          ]
      },
      {
          name: 'disableAll',
          files: [                                          
              'bower_components/angular-disable-all/dist/angular-disable-all.js',
          ]
      },
      {
          name: 'ui.utils.masks',
          files: [                                          
              'bower_components/angular-input-masks/angular-input-masks-standalone.min.js',
          ]
      },
      {
          name: 'duScroll',
          files: [                                          
              'bower_components/angular-scroll/angular-scroll.js',
          ]
      },
      {
        name: 'angularLazyImg',
        files: [                                          
            'bower_components/angular-lazy-img/angular-lazy-img.js',
        ]
      },
      {
        name: 'InlineTextEditor',
        files: [                                          
            'bower_components/rangy/rangy-core.js',
            'bower_components/rangy/rangy-selectionsaverestore.js',
            'bower_components/rangy/rangy-classapplier.js',
            'bower_components/rangy/ite.js',
            'bower_components/rangy/ite.css',
        ]
      },
      {
        name: 'colorpicker',
        files: [                                          
            '../lib/color_picker/color-picker.js',            
            '../lib/color_picker/color-picker.css',
        ]
      },
      {
          name: 'infinite-scroll',
          files: [                                          
              '../libs/infinite-scroll/ng-infinite-scroll.js',
          ]
      }
    ]
  )
  // oclazyload config
  .config(['$ocLazyLoadProvider', 'MODULE_CONFIG', function($ocLazyLoadProvider, MODULE_CONFIG) {
      // We configure ocLazyLoad to use the lib script.js as the async loader
      $ocLazyLoadProvider.config({
          debug:  false,
          events: true,
          modules: MODULE_CONFIG
      });
  }])
;
