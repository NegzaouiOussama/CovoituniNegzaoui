// CKEditor Setup
document.addEventListener('DOMContentLoaded', function() {
    console.log('CKEditor Setup: Script loaded');
    
    // Default configurations
    window.ckeditorConfigs = window.ckeditorConfigs || {
        default: {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
            height: 300,
            placeholder: "Tapez votre contenu ici..."
        },
        minimal: {
            toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
            height: 200,
            placeholder: "Texte court..."
        },
        full: {
            toolbar: [
                'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'link', '|', 
                'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote',
                'insertTable', 'horizontalLine', '|', 'undo', 'redo'
            ],
            height: 400,
            placeholder: "Contenu détaillé..."
        }
    };
    
    // Find all textareas with the ckeditor-enable class
    const textareas = document.querySelectorAll('textarea.ckeditor-enable');
    console.log('CKEditor Setup: Found', textareas.length, 'textareas');
    
    if (textareas.length > 0) {
        if (typeof ClassicEditor !== 'undefined') {
            textareas.forEach(function(textarea) {
                // Get configuration name if specified
                const configName = textarea.dataset.config || 'default';
                const configData = window.ckeditorConfigs[configName] || window.ckeditorConfigs.default;
                
                // Apply placeholder if specified on the element
                if (textarea.placeholder && !configData.placeholder) {
                    configData.placeholder = textarea.placeholder;
                }
                
                ClassicEditor
                    .create(textarea, configData)
                    .then(editor => {
                        console.log('CKEditor initialized on', textarea.id, 'with config:', configName);
                        
                        // Set minimum height
                        if (configData.height) {
                            editor.editing.view.change(writer => {
                                writer.setStyle(
                                    'min-height',
                                    `${configData.height}px`,
                                    editor.editing.view.document.getRoot()
                                );
                            });
                        }
                    })
                    .catch(error => {
                        console.error('CKEditor initialization error:', error);
                    });
            });
        } else {
            console.error('CKEditor not loaded - ClassicEditor is undefined');
        }
    }
}); 