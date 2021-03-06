Q.push(function() {
	
	// Ensure all POST requests have the CSRF token
	$.ajaxSetup({ data: { crbscsrftoken: $.cookie('crbscsrf') } });
	
	// Attach events for tabs
	$("body").on("click", "dl.htabs > dd > a", function(e) {
		var location = $(this).data("tab");
		if (location) {
			e.preventDefault();
			$(this).closest("dl").find("a.active").removeClass("active");
			$(this).addClass("active");
			$("#" + location + "Tab").closest(".htabs-content").children("li").hide();
			$("#" + location + "Tab").show();
		}
	});
	
	// Generic delete buttons
	$(".action-delete").on("click", function(e) {
		
		// Cancel click event
		e.preventDefault();
		
		// Get all data attributes for use in the template
		var delete_data = $(this).data();
		delete_data.csrf = $.cookie('crbscsrf');
		
		// Merge template + data and set dialog container HTML
		var dialog_html = ich.ich_delete(delete_data);
		
		// Set the modal's container's HTML and show it
		$("#delete_dialog")
			.html(dialog_html)
			.modal({
				overlayClose: true,
				opacity: 60,
				minWidth: 500,
				maxWidth: 500,
				minHeight: 200,
				maxHeight: 320,
			});
		
	});
	
	// Button for cancelling/closing the dialog
	$("#delete_dialog").on("click", ".close-dialog", function(e) {
		e.preventDefault();
		$.modal.close();
	});
	
});
