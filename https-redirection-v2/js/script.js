(function ($) {
    $(document).ready(function () {
        /* add notice about changing in the settings page */
        $(document).on('click', '.rewrite_item_add_btn', function () {
            $(this).each(function () {
                if ($(this).prev().val() != '') {
                    $(this).next().hide();
                    $(this).parents('.rewrite_new_item').removeClass('rewrite_new_item').clone().addClass('rewrite_new_item').appendTo($(this).parents("td")).find('input').val('');
                    $(this).addClass('rewrite_item_delete_btn').removeClass('rewrite_item_add_btn');
                    $(this).children('.dashicons').addClass('dashicons-trash').removeClass('dashicons-plus-alt2');
                } else {
                    $(this).next().show();
                }
            });
        });
        $(document).on('click', '.rewrite_item_delete_btn', function () {
            $(this).each(function () {
                $(this).parent().remove();
            });
        });
    });
})(jQuery);