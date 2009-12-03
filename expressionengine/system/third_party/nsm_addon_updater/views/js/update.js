(function($) {
	$(document).ready(function() {
		$.ajax({
			url: EE.BASE + "&C=homepage&nsm_addon_updater=true",
			async: true,
			success: function(data){
				$resp = $(data);
				$("#mainContent .pageContents").prepend(data);
				$("#nsm_au_updates a.note-trigger")
					.data("active", false)
					.click(function() {
						$trigger = $(this);
						$header = $(this).parent().parent().find("th");
						$target = $(this).parent().parent().next();
						if($trigger.data('active'))
						{
							$target.hide();
							$trigger.removeClass('active').data("active", false);
							$header.attr('rowspan', 1);
						}
						else
						{
							$target.show();
							$trigger.addClass('active').data("active", true);
							$header.attr('rowspan', 2);
						}
						return false;
					});
			}
		});
	});
})(jQuery);
