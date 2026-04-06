jQuery(document).ready(function($) {
    // Модальное окно для редактирования
    var svgModal = $(
        '<div id="svg-editor-modal" style="display:none;">' +
        '    <div class="svg-editor-content">' +
        '        <div class="svg-editor-header">' +
        '            <h2>Edit SVG</h2>' +
        '            <button type="button" class="close-button">&times;</button>' +
        '        </div>' +
        '        <div class="svg-editor-body">' +
        '            <textarea id="svg-editor-textarea" spellcheck="false"></textarea>' +
        '        </div>' +
        '        <div class="svg-editor-footer">' +
        '            <button type="button" class="button button-secondary cancel-button">Cancel</button>' +
        '            <button type="button" class="button button-primary save-button">Save Changes</button>' +
        '            <span class="spinner is-active" style="float: none; display: none;"></span>' +
        '            <span class="save-message" style="margin-left: 10px;"></span>' +
        '        </div>' +
        '    </div>' +
        '</div>'
    ).appendTo('body');
    
    var currentAttachmentId = null;
    
    // Обработчик клика по кнопке Edit SVG
    $(document).on('click', '.svg-edit-button', function() {
        currentAttachmentId = $(this).data('attachment-id');
        openEditor(currentAttachmentId);
    });
    
    function openEditor(attachmentId) {
        var spinner = svgModal.find('.spinner');
        var message = svgModal.find('.save-message');
        
        message.text('');
        spinner.show();
        svgModal.show();
        
        // Загружаем содержимое SVG
        $.ajax({
            url: svg_editor.ajax_url,
            type: 'POST',
            data: {
                action: 'get_svg_content',
                attachment_id: attachmentId,
                nonce: svg_editor.nonce
            },
            success: function(response) {
                spinner.hide();
                if (response.success) {
                    $('#svg-editor-textarea').val(response.data.content);
                } else {
                    alert('Error loading SVG: ' + response.data);
                }
            },
            error: function() {
                spinner.hide();
                alert('Error loading SVG content');
            }
        });
    }
    
    // Сохранение изменений
    svgModal.on('click', '.save-button', function() {
        var spinner = $(this).siblings('.spinner');
        var message = $(this).siblings('.save-message');
        var newContent = $('#svg-editor-textarea').val();
        
        spinner.show();
        message.text('Saving...');
        
        $.ajax({
            url: svg_editor.ajax_url,
            type: 'POST',
            data: {
                action: 'save_svg_content',
                attachment_id: currentAttachmentId,
                content: newContent,
                nonce: svg_editor.nonce
            },
            success: function(response) {
                spinner.hide();
                if (response.success) {
                    message.text('Saved successfully!').css('color', 'green');
                    setTimeout(function() {
                        svgModal.hide();
                        // Обновляем страницу чтобы увидеть изменения
                        location.reload();
                    }, 1000);
                } else {
                    message.text('Error saving: ' + response.data).css('color', 'red');
                }
            },
            error: function() {
                spinner.hide();
                message.text('Error saving file').css('color', 'red');
            }
        });
    });
    
    // Закрытие модального окна
    svgModal.on('click', '.close-button, .cancel-button', function() {
        if ($('#svg-editor-textarea').val() !== svgModal.data('originalContent')) {
            if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                return;
            }
        }
        svgModal.hide();
    });
    
    // Закрытие по клику на фон
    svgModal.on('click', function(e) {
        if (e.target === this) {
            if ($('#svg-editor-textarea').val() !== svgModal.data('originalContent')) {
                if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                    return;
                }
            }
            svgModal.hide();
        }
    });
});