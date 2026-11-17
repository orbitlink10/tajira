import tinymce from 'tinymce';
import 'tinymce/icons/default/icons.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/table';

// Bundle the complete editor locally, including skins and plugins.
export async function mountDescriptionEditor(textarea) {
    const original = textarea.value;
    let changed = false;
    const editors = await tinymce.init({
        target: textarea,
        license_key: 'gpl',
        skin_url: 'default',
        content_css: 'default',
        plugins: 'code fullscreen image link lists media table',
        menubar: 'file edit view insert format tools table',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | outdent indent | link image media | code fullscreen',
        toolbar_mode: 'wrap',
        height: 500,
        resize: true,
        promotion: false,
        placeholder: textarea.dataset.editorPlaceholder || 'Write the product description here...',
        convert_urls: false,
        object_resizing: 'img,table',
        formats: {
            alignleft: { selector: 'p,h2,h3,h4,div,td,th', classes: 'content-align-left' },
            aligncenter: { selector: 'p,h2,h3,h4,div,td,th', classes: 'content-align-center' },
            alignright: { selector: 'p,h2,h3,h4,div,td,th', classes: 'content-align-right' },
        },
        content_style: 'body{font-family:Segoe UI,sans-serif;font-size:15px;line-height:1.7;color:#172033;margin:22px 18px} .content-align-left{text-align:left}.content-align-center{text-align:center}.content-align-right{text-align:right} img,video,iframe{max-width:100%}',
        setup(editor) {
            editor.on('input change undo redo', () => { changed = true; });
        },
    });
    const editor = editors[0];
    if (!editor) return;
    // Preserve existing HTML verbatim unless the author changes the description.
    textarea.form.addEventListener('submit', () => {
        textarea.value = changed || editor.isDirty() ? editor.getContent() : original;
    });
}
