$("#nsm_au_updates").each(function(index) {
	$("a.note-trigger", $(this))
		.data("active", false)
		.click(function() {
			$trigger = $(this);
			$row = $(this).parent().parent()
			$header = $row.find("th");
			$target = $row.next();
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
	$updates = $("tbody tr th", $(this));
	$("#accessoryTabs .nsm_addon_updater").append("<span class='badge'>"+$updates.length+"</span>");
}); 