// Switches option section
jQuery(document).ready(function($) {

	// Hide all by default
	$('.group').hide();
	
	// Display active group
	$('.group:first').show();
	$('.nav-tab-wrapper a:first').addClass('nav-tab-active');

	$('.nav-tab-wrapper a').click(function(evt) {
		$('.nav-tab-wrapper a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active').blur();
		var clicked_group = $(this).attr('href');
		$('.group').hide();
		$(clicked_group).show();
		evt.preventDefault();
	});

});
