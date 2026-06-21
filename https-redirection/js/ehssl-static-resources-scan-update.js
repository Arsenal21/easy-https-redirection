/* global ehssl_static_resources_scan_update_jd_data */

document.addEventListener('DOMContentLoaded', function () {
    const {ajaxUrl, texts} = ehssl_static_resources_scan_update_jd_data;
    const scanForm = document.getElementById('ehssl_static_resources_scan_form');
    const scanBtn = document.getElementById('ehssl_static_resources_scan_btn');
    const resultsBox = document.getElementById('ehssl_scan_results');

    scanForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        scanBtn.disabled = true;
        let scanBtnText = scanBtn.textContent;
        scanBtn.textContent = texts.scan_btn_loading;
        scanBtnText = texts.rescan_btn;

        const checked_post_types = scanForm.querySelectorAll('input[name="ehssl_post_types[]"]:checked');
        const postTypes = [];
        checked_post_types.forEach(function (item) {
            postTypes.push(item.value);
        });

        const checked_other_tables = scanForm.querySelectorAll('input[name="ehssl_other_tables[]"]:checked');
        const otherTables = [];
        checked_other_tables.forEach(function (item) {
            otherTables.push(item.value);
        });

        const checked_flags = scanForm.querySelectorAll('input[name="ehssl_additional_flags[]"]:checked');
        const flags = [];
        checked_flags.forEach(function (item) {
            flags.push(item.value);
        });

        const nonce_input = scanForm.querySelector('input[name="_wpnonce"]');
        const nonce = nonce_input?.value;

        if (!postTypes.length && !otherTables.length) {
            // resultsBox.innerHTML = texts.nothing_found;
            alert(texts.pls_select_an_item);
            scanBtn.disabled = false;
            scanBtn.innerText = scanBtnText;
            return;
        }

        resultsBox.innerHTML = '';

        const formData = new FormData(e.target);
        formData.append('action', 'ehssl_static_resources_scan');
        formData.append('nonce', nonce);
        formData.append('offset', 0);
        formData.append('total', JSON.stringify([]));

        const onComplete = async(resp) => {
            // console.log('processBatchScan onComplete:', resp); // Debug Purpose Only

            if (resp.success) {
                // Display the rendered table.
                await getScannedResourcesTable((tableHTML) => {
                    resultsBox.innerHTML = tableHTML;
                })
            }

            scanBtn.disabled = false;
            scanBtn.textContent = scanBtnText;
        }

        await processBatchScan(formData, onComplete);
    });

    async function processBatchScan(formData, onComplete) {
        try {
            let response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
            })

            response = await response.json();

            const {success, data} = response;
            if (!success) {
                // return response;
                alert('Something went wrong');
                console.log('Something went wrong', response);
                return;
            }

            // console.log('processBatchScan response:', response); // Debug Purpose Only

            if (!data.completed) {
                formData.append('total', JSON.stringify(data.total));
                formData.append('offset', data.next_offset);
                await processBatchScan(formData, onComplete);
            } else {
                onComplete(response);
            }

        } catch (error) {
            alert(error.message);
        }
    }

    async function getScannedResourcesTable(cb) {
        try {
            const formData = new FormData();
            formData.append('action', 'ehssl_get_scanned_resources_table');

            let response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
            })

            response = await response.text();

            cb(response);
        } catch (error) {
            alert(error.message);
        }
    }

    /**
     * AJAX pagination
     */
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.tablenav-pages a');
        if (!link) {
            return;
        }
        e.preventDefault();
        const url = new URL(link.href);
        const paged = url.searchParams.get('paged') || 1;
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },

            body: new URLSearchParams({
                action: 'ehssl_load_static_resources_table_page',
                paged: paged
            })
        })
            .then(response => response.text())
            .then(html => {
                resultsBox.innerHTML = html;
            });
    });

    /**
     * Update URLs action.
     */
    document.addEventListener('click', function (e) {
        if (e.target.matches('#doaction, #doaction2')) {
            handle_update_urls(e);
        }

        else if (e.target.matches('#ehssl_update_all_found_http_urls')) {
            handle_update_urls(e, true);
        }
    });


    function handle_update_urls(e, update_all = false){
        e.preventDefault();

        const actionBtn = e.target;
        const actionBtnText = actionBtn.textContent;

        const nonce_input = document.querySelector('input[name="ehssl_update_all_http_urls_nonce"]');
        const nonce = nonce_input.value || '';

        const formData = new FormData();
        formData.append('action', 'ehssl_update_http_urls');
        formData.append('nonce', nonce);
        formData.append('offset', 0);

        const onComplete = (resp) => {
            getScannedResourcesTable((tableHTML) => {
                resultsBox.innerHTML = tableHTML;

                disableButtons(false);
            })
        }

        if (update_all) {
            actionBtn.textContent = texts.update_btn_loading;
        } else {
            const bulkSelect = actionBtn.id === 'doaction'
                ? document.getElementById('bulk-action-selector-top')
                : document.getElementById('bulk-action-selector-bottom');

            if (!bulkSelect || bulkSelect.value !== 'update_to_https') {
                alert('Please select an action!');
                return;
            }

            const selected_ids = [];

            const checkboxes = document.querySelectorAll('input[name="ehssl_static_resources_scan_ids[]"]:checked');
            checkboxes?.forEach(function (el) {
                selected_ids.push(el.value);
            });

            if (!selected_ids.length) {
                alert('Please select at least one item!');
                return;
            }

            formData.append('selected_ids', JSON.stringify(selected_ids));
        }

        if (!confirm(texts.confirm_update)) {
            disableButtons(false);
            actionBtn.textContent = actionBtnText;
            return;
        }

        disableButtons();

        processBatchUpdate(formData, onComplete);
    }

    function disableButtons(value = true){
        document.querySelectorAll('#ehssl_update_all_found_http_urls, #doaction, #doaction2').forEach((btn) => {
            btn.disabled = value;
        });
    }

    async function processBatchUpdate(formData, onComplete) {
        try {
            let response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
            })

            response = await response.json();

            const {success, data} = response;
            if (!success) {
                alert('Something went wrong');
                console.log('Something went wrong', response);
                return;
            }

            // console.log(`${data.processed}/${data.total}`); // Debug purpose only.

            if (!data.completed) {
                formData.append('offset', data.next_offset);
                await processBatchUpdate(formData, onComplete);
            } else {
                onComplete(response);
            }
        } catch (error) {
            alert(error.message);
        }
    }
});