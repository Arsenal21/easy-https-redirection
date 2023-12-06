<?php

if (isset($_GET['page']) && 'ehssl' == $_GET['page']) {
	global $httpsrdrctn_options;

	$ehssl_saved_order = isset($httpsrdrctn_options['dashboard_widget_sort_order']) ? $httpsrdrctn_options['dashboard_widget_sort_order'] : array();
	// wp_add_inline_script('ehssl-dashboard-sortables' , "const ehssl_ajaxurl = '" . get_admin_url() . 'admin-ajax.php'."';" , 'before');
	$ehssl_dashboard_widgets_data = array(
		'ajaxurl' => get_admin_url() . 'admin-ajax.php',
		'saved_order' => $ehssl_saved_order,
	);

	wp_enqueue_script('ehssl-dashboard-sortables', EASY_HTTPS_SSL_URL . "/js/ehssl-dashboard-sortables.js", array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'), wp_rand(1, 10000));
	wp_localize_script('ehssl-dashboard-sortables', 'ehssl_dashboard_widgets_data', $ehssl_dashboard_widgets_data);
}

?>


<div id="sortable-list" style="display: flex;">
	<div class="sortable-column postbox-container" data-column-id="1">
		<?php $this->widget_ssl_status(); ?>
	</div>
	<div class="sortable-column postbox-container" data-column-id="2">
		<?php
		if (is_ssl()) {
			$this->widget_ssl_info();
		}
		?>
		<div class="sortable-item postbox" data-item-id="5">
			<div class="postbox-header handle">
				<h2>Item 5</h2>
			</div>
		</div>
		<div class="sortable-item postbox" data-item-id="6">
			<div class="postbox-header handle">
				<h2>Item 6</h2>
			</div>
		</div>
	</div>
	<div class="sortable-column postbox-container" data-column-id="3">
		<div class="sortable-item postbox" data-item-id="7">
			<div class="postbox-header handle">
				<h2>Item 7</h2>
			</div>
		</div>
		<div class="sortable-item postbox" data-item-id="8">
			<div class="postbox-header handle">
				<h2>Item 8</h2>
			</div>
		</div>
		<div class="sortable-item postbox" data-item-id="9">
			<div class="postbox-header handle">
				<h2>Item 9</h2>
			</div>
		</div>
	</div>
</div>
<style>
	#sortable-list {
		display: flex;
		justify-content: space-around;
	}

	.sortable-column {
		flex: 1;
		background-color: transparent;
		/* margin: 0 10px; */
		min-width: 200px;
	}

	.sortable-item {
		background-color: #fff;
		margin: 0px 8px 28px;
	}

	.sortable-item .handle {
		cursor: move;
	}
</style>