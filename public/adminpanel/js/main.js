require.config({
    baseUrl: "adminpanel/js",
    
    // alias libraries paths.  Must set 'angular'
    paths: {
        'angular': '/lib/angular/angular-1.5.5.min',
        'angular-route': '/lib/angular/angular-route-1.2.18.min',
        'angularAMD': '/lib/angular/angularAMD.min',
		'jquery': '/js/jquery.min',		
		'jquery-ui': '/js/jquery.ui',
		'multiselect': '/js/multiselect',		
		'bootstrap': '/bootstrap/js/bootstrap.min',
		'sidebar_menu': '/bootstrap/js/sidebar_menu',
		//DataTables core
        'datatables.net' : '/js/jquery.dataTables.min',
        'datatables.net-bs' : '/js/dataTables.bootstrap.min',
		'angular-file' : '/lib/angular/angular-file-upload.min',		
		'toggle-switch' : '/lib/angular/angular-toggle-switch.min',
		'checklist-model' : '/lib/angular/checklist-model',
		'cookies' : '/libs/angular/angular-cookies/angular-cookies',
		'ngStorage' : '/libs/angular/ngstorage/ngstorage_amd',
		'ngImgCrop' : '/libs/angular/ngImgCrop/compile/minified/ng-img-crop',
		'file-model' : '/libs/angular/angular-file-model/angular-file-model',
		'authservice' : '/frontpage/js/services/auth.service',
        'ui.bootstrap' : '/frontpage/bower_components/angular-bootstrap/ui-bootstrap-tpls',
		'lodash': '/frontpage/bower_components/lodash/dist/lodash',
		'angularjs-dropdown-multiselect':'/frontpage/bower_components/angularjs-dropdown-multiselect/dist/angularjs-dropdown-multiselect.min2',
		'ngQuill':'/frontpage/bower_components/ngQuill/dist/ng-quill',
		'quill' : '/frontpage/bower_components/quill/quill.min',
		'ngTagsInput':'/libs/angular/ng-tag/ng-tags-input.min',
		'btford.socket-io':'/frontpage/bower_components/angular-socket-io/socket',
		'base64': '/frontpage/bower_components/angular-base64/angular-base64'
    },
    
    // Add angular modules that does not support AMD out of the box, put it in a shim
    shim: {
		'angularAMD': ['angular'],
        'angular-route': ['angular'],
		'angular-file': ['angular'],
		'toggle-switch': ['angular'],
		'checklist-model': ['angular'],
		'bootstrap': ['jquery'],			
		'sidebar_menu': ['jquery'],
		'multiselect': ['jquery'],
		'cookies': ['angular'],
		'ngStorage': ['angular'],
		'ngImgCrop': ['angular'],
		'authservice': ['angular'],
		'ui.bootstrap': ['angular', 'bootstrap'],
		'datatables.net' : ['jquery','bootstrap'],
		'angularjs-dropdown-multiselect' : ['angular','bootstrap','jquery', 'lodash'],
		'ngQuill' :['angular'],
		'ngTagsInput': ['angular'],
		'btford.socket-io':['angular'],
		'base64' : ['angular']
    },
    
    // kick start application
    deps: ['app']
});