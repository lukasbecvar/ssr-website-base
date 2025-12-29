/* articles eidtor script */

// import TinyMCE editor
import tinymce from 'tinymce/tinymce'

// import plugins and themes
import 'tinymce/models/dom'
import 'tinymce/plugins/link'
import 'tinymce/plugins/code'
import 'tinymce/icons/default'
import 'tinymce/themes/silver'
import 'tinymce/plugins/lists'
import 'tinymce/plugins/image'
import 'tinymce/plugins/table'
import 'tinymce/plugins/media'
import 'tinymce/plugins/anchor'
import 'tinymce/plugins/charmap'
import 'tinymce/plugins/preview'
import 'tinymce/plugins/advlist'
import 'tinymce/plugins/autosave'
import 'tinymce/plugins/autolink'
import 'tinymce/plugins/wordcount'
import 'tinymce/plugins/pagebreak'
import 'tinymce/plugins/fullscreen'
import 'tinymce/plugins/nonbreaking'
import 'tinymce/plugins/visualblocks'
import 'tinymce/plugins/searchreplace'
import 'tinymce/plugins/insertdatetime'

// initialize app
document.addEventListener('DOMContentLoaded', () => {
    tinymce.init({
        selector: '.tinymce-editor',
        license_key: 'gpl',
        iframe_attrs: {
            tabindex: '0'
        },

        // skin configuration
        skin: 'oxide-dark',
        content_css: 'dark',
        skin_url: '/build/skins/ui/oxide-dark',
        content_css_url: '/build/skins/content/dark/content.css',
        promotion: false,
        statusbar: false,
        menubar: false,
        resize: false,
        height: 600,
        
        plugins: [
            'lists', 'link', 'image', 'table', 'code',
            'wordcount', 'fullscreen',
            'fontfamily', 'fontsize'
        ],
        
        toolbar:
            'undo redo | formatselect | ' +
            'fontfamily fontsize | ' +
            'bold italic underline | ' +
            'forecolor backcolor | ' +
            'image alignleft aligncenter alignright | ' +
            'bullist numlist | code fullscreen',

        font_family_formats:
            'Verdana=verdana,geneva;' +
            'Arial=arial,helvetica,sans-serif;' +
            'Tahoma=tahoma,arial,sans-serif;' +
            'Georgia=georgia,serif;' +
            'Times New Roman=times new roman,times,serif;' +
            'Courier New=courier new,courier,monospace',

        font_size_formats: '10px 12px 14px 16px 18px 20px 24px 32px',

        setup: function(editor) {
            editor.on('change', function() {
                editor.save()
            })
        },

        // custom styles to match the admin theme
        content_style: `
            *:focus {
                outline: none !important;
            }
            *:focus-visible {
                outline: none !important;
            }
            body {
                outline: none !important;
            }
            html, body {
                outline: none !important;
            }
            body { 
                font-family: Verdana, sans-serif;
                font-size: 14px; 
                background-color: #191e28; 
                color: #f8fafc; 
                margin: 10px;
                line-height: 1.2 !important;
                outline: none !important;
            }
            p { 
                margin: 0 !important;
                padding: 0 !important;
            }
            body.mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before {
                color: rgba(255, 255, 255, 0.5) !important;
                font-style: italic;
            }
            a { color: #60a5fa; }
            ::-webkit-scrollbar { width: 10px; height: 10px; }
            ::-webkit-scrollbar-track { background: rgb(53, 53, 53); }
            ::-webkit-scrollbar-thumb { background: #4a5056; transition: all 0.2s ease; }
            ::-webkit-scrollbar-thumb:hover { background: #5c646b; }
        `
    })

    const form = document.getElementById('article-form')
    if (form) {
        form.addEventListener('submit', function() {
            tinymce.triggerSave()
        })
    }
})
