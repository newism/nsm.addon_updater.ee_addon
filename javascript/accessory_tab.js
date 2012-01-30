$("#nsm_addon_updater").each(function(index) {
	
	var $acc = $(this),
		$content = $("#nsm_addon_updater_content"),
		url = EE.BASE + '&C=addons_accessories&M=process_request' + 
				'&accessory=nsm_addon_updater&method=process_ajax_feeds';
	$.ajax({
		url: url,
		success: function(data) {
			$data = $(data);
			// no correct return data? exit
			if( $data[0].id !== "nsm_addon_updater_ajax_return") {
				return false;
			}
		
			$content.html($data);
			$acc.find("a.note-trigger")
				.data("active", false)
				.click(function() {
					$trigger = $(this);
					$row = $(this).parent().parent()
					$header = $row.find("th");
					$target = $row.next();
					if ($trigger.data('active')) {
						$target.hide();
						$trigger.removeClass('active').data("active", false);
						$trigger.parent().removeClass('active');
						$header.attr('rowspan', 1);
					} else {
						$target.show();
						$trigger.addClass('active').data("active", true);
						$trigger.parent().addClass('active');
						$header.attr('rowspan', 2);
					}
					return false;
				});
			$updates = $("tbody tr th", $acc);
			$("#accessoryTabs .nsm_addon_updater").append("<span class='badge'>"+$updates.length+"</span>");
		},
		error: function() {
			$content.addClass('alert error').text('There was an error retrieving the update feeds.');
		}
	});
	
});