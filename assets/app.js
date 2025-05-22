import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Load CKEditor configurations from Symfony
document.addEventListener('DOMContentLoaded', () => {
    // Fetch CKEditor configurations from endpoint
    fetch('/api/ckeditor-config')
        .then(response => response.json())
        .then(data => {
            // Store configurations in global variable
            window.ckeditorConfigs = data;
            console.log('CKEditor configurations loaded:', data);
        })
        .catch(error => {
            console.error('Failed to load CKEditor configurations:', error);
            // Set default configurations if fetch fails
            window.ckeditorConfigs = {
                default: {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
                    height: 300
                }
            };
        });
});
