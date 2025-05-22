import { Controller } from '@hotwired/stimulus';

/**
 * CKEditor Stimulus controller
 * 
 * Initializes CKEditor on textareas with data-controller="ckeditor" attribute
 * Supports different configurations via data-ckeditor-config attribute
 */
export default class extends Controller {
    connect() {
        // Check if CKEditor is loaded
        if (typeof window.ClassicEditor === 'undefined') {
            console.error('CKEditor is not loaded. Make sure CKEditor script is included in your template.');
            return;
        }

        // Get configuration name from data attribute
        const configName = this.element.dataset.ckeditorConfig || 'default';
        
        // Get configuration data
        let config = this.getConfig(configName);
        
        // Apply placeholder if specified on the element
        if (this.element.placeholder && !config.placeholder) {
            config.placeholder = this.element.placeholder;
        }

        // Initialize CKEditor
        window.ClassicEditor
            .create(this.element, config)
            .then(editor => {
                this.editor = editor;
                
                // Set editor height
                if (config.height) {
                    editor.editing.view.change(writer => {
                        writer.setStyle(
                            'min-height',
                            `${config.height}px`,
                            editor.editing.view.document.getRoot()
                        );
                    });
                }
                
                console.log(`CKEditor initialized with config: ${configName}`);
            })
            .catch(error => {
                console.error('Error initializing CKEditor:', error);
            });
    }
    
    disconnect() {
        if (this.editor) {
            this.editor.destroy()
                .then(() => {
                    this.editor = null;
                    console.log('CKEditor instance destroyed.');
                })
                .catch(error => {
                    console.error('Error destroying CKEditor instance:', error);
                });
        }
    }
    
    getConfig(configName) {
        // Default configuration
        const defaultConfig = {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
            height: 300,
            placeholder: "Tapez votre contenu ici..."
        };
        
        // Try to get configuration from global variable
        if (window.ckeditorConfigs && window.ckeditorConfigs[configName]) {
            return window.ckeditorConfigs[configName];
        }
        
        console.warn(`CKEditor configuration "${configName}" not found. Using default configuration.`);
        return defaultConfig;
    }
} 