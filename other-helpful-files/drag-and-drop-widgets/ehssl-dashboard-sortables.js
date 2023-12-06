jQuery(function() {

	if (typeof ehssl_dashboard_widgets_data === "undefined" ) {
		alert("Error");

		return;
	}

	const ehssl_savedOrder = ehssl_dashboard_widgets_data.saved_order;
	const ehssl_ajaxurl =  ehssl_dashboard_widgets_data.ajaxurl;

	console.log(ehssl_savedOrder);
	console.log(ehssl_ajaxurl);

	// jQuery("#sortable-list").sortable({
	//     items: ".sortable-column",
	//     axis: "x", // allow only horizontal sorting (columns)
	//     // cursor: "move",
	// });

	jQuery(".sortable-column").sortable({
		connectWith: ".sortable-column",
		items: ".sortable-item",
		cursor: "move",
		handle: ".handle",
		update: function(event, ui) {
			// Save changes
			saveSortingOrder();
		},
	});

	// Disable text selection during dragging
	jQuery(".sortable-column, .sortable-item").disableSelection();

	function saveSortingOrder() {
		let order = [];

		jQuery(".sortable-column").each(function() {
			const columnId = jQuery(this).data("column-id");
			const columnOrder = jQuery(this).sortable("toArray", {
				attribute: "data-item-id"
			});

			order.push({
				columnId,
				columnOrder,
			});
		});

		// console.log(order);

		// Send the order data to the server via AJAX
		jQuery.ajax({
			url: ehssl_ajaxurl, // replace with your server-side endpoint
			type: "POST",
			data: {
				action: "ehssl_save_dashboard_order",
				ehssl_sort_order: order,
			},
			success: function(response) {
				console.log('Response: ' ,response.data);
				console.log("Sorting order saved successfully");
			},
			error: function(xhr, status, error) {
				console.error("Error saving sorting order:", error);
			},
		});
	}

	function initializeSortingOrder(savedOrder) {
		// if (savedOrder.length === 0) {
		// 	return;
		// }
		// Loop through the saved order and apply it to the columns
		jQuery.each(savedOrder, function (index, column) {
			const columnId = column.columnId;
			const columnOrder = column.columnOrder;

			const $column = jQuery('[data-column-id="' + columnId + '"]');
			const $items = $column.find('[data-item-id]');

			// Move items to their corresponding positions
			jQuery.each(columnOrder, function (position, itemId) {
				const $item = jQuery('[data-item-id="' + itemId + '"]');
				$column.append($item);
			});
		});
	}

	// const savedOrder = [
	//     {
	//         "columnId": 1,
	//         "columnOrder": [
	//         "3",
	//         "2"
	//         ]
	//     },
	//     {
	//         "columnId": 2,
	//         "columnOrder": [
	//         "4",
	//         "5",
	//         "6",
	//         "1"
	//         ]
	//     },
	//     {
	//         "columnId": 3,
	//         "columnOrder": [
	//         "7",
	//         "8",
	//         "9"
	//         ]
	//     }
	// ];

	// Initialize the sorting order using ehssl_savedOrder data
	initializeSortingOrder(ehssl_savedOrder);

});