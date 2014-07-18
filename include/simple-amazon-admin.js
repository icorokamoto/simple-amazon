/*
jQuery(document).ready( function() {
	jQuery('#simple-amazon-options').tabs({
//		cookie: { expires: 30 },
		fx: { opacity: 'toggle' }
	});
});
*/

// Switches option section
jQuery(document).ready(function($) {
	// Hide all by default
	$('.group').hide();
	
	// Display active group
//	var activetab = '';
//	if (typeof(localStorage) != 'undefined') {
//		activetab = localStorage.getItem("activetab");
//	}
//	if (activetab != '' && $(activetab).length) {
//		$(activetab).show();
//	} else {
		$('.group:first').show();
//	}
//	console.log(activetab);

//	if (activetab != '' && $(activetab + '-tab').length) {
//		$(activetab + '-tab').addClass('nav-tab-active');
//	} else {
		$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
//	}

	$('.nav-tab-wrapper a').click(function(evt) {
		$('.nav-tab-wrapper a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active').blur();
		var clicked_group = $(this).attr('href');
//		if (typeof(localStorage) != 'undefined') {
//			localStorage.setItem("activetab", $(this).attr('href'));
//		}
		$('.group').hide();
		$(clicked_group).show();
		evt.preventDefault();
	});
});
