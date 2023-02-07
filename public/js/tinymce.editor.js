/* editior-description:text_tinymce */

$.extend({
	ceEditorMethods: {
		runEditor: function(elm) {
			if (typeof($.fn.tinymce) == 'undefined') {
				$.ceEditor('state', 'loading');
				return $.getScript(current_path + '/lib/js/tinymce/jquery.tinymce.js', function() {
					$.ceEditor('state', 'loaded');
					elm.ceEditor('run');
				});
			}
			
			// You have to change this array if you want to add a new lang pack.
			var available_langs = {ar: true, az: true, be: true, bg: true, bn: true, br: true, bs: true, ca: true, ch: true, cs: true, cy: true, da: true, de: true, dv: true, el: true, en: true, es: true, et: true, eu: true, fa: true, fi: true, fr: true, gl: true, gu: true, he: true, hi: true, hr: true, hu: true, hy: true, ia: true, id: true, ii: true, is: true, it: true, ja: true, ka: true, kl: true, ko: true, lb: true, lt: true, lv: true, mk: true, ml: true, mn: true, ms: true, nb: true, nl: true, nn: true, no: true, pl: true, ps: true, pt: true, ro: true, ru: true, sc: true, se: true, si: true, sk: true, sl: true, sq: true, sr: true, sv: true, ta: true, te: true, th: true, tr: true, tt: true, tw: true, uk: true, ur: true, vi: true, zh: true, zu: true};
			
			var lang = (typeof(available_langs[cart_language.toLowerCase()]) != 'undefined') ? cart_language.toLowerCase() : 'en';
			
			elm.tinymce({
				script_url : current_path + '/lib/js/tinymce/tiny_mce.js',

				plugins : 'safari,style,advimage,advlink,xhtmlxtras,inlinepopups',
				theme_advanced_buttons1: 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,backcolor,|,link,image,|,numlist,bullist,indent,outdent,justifyleft,justifycenter,justifyright,|,code',
				theme_advanced_buttons2: '',
				theme_advanced_buttons3: '',
				theme_advanced_toolbar_location : 'top',
				theme_advanced_toolbar_align : 'left',
				theme_advanced_statusbar_location : 'bottom',
				theme_advanced_resizing : true,
				theme_advanced_resize_horizontal : false,
				theme : 'advanced',
				language: lang,
				strict_loading_mode: true,
				convert_urls: false,
				remove_script_host: false,
				body_class: 'wysiwyg-content',
				content_css: customer_skin_path + '/customer/styles.css,' + current_path + '/skins/' + skin_name + '/admin/wysiwyg_reset.css',

				file_browser_callback : function(field_name, url, type, win) {
					tinyMCE.activeEditor.windowManager.open({
						file : current_path + '/lib/js/elfinder/elfinder.tinymce.html',
						width : 600,
						height : 450,
						resizable : 'yes',
						inline : 'yes',
						close_previous : 'no',
						popup_css : false // Disable TinyMCE's default popup CSS
					}, {
						'window': win,
						'input': field_name,
						'current_location': current_location + '/',
						'connector_url': fn_url('elf_connector.images?ajax_custom=1')
					});	
				}
			});
		},

		destroyEditor: function(elm) {
			if (!$.browser.msie) {
				tinyMCE.execCommand('mceRemoveControl', false, elm.attr('id'));
			}
		},

		recoverEditor: function(elm) {
			tinyMCE.execCommand('mceAddControl', false, elm.attr('id'));
		}
	}
});