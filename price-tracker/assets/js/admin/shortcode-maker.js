document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('shortcode-type');
    const taxonomy = document.getElementById('taxonomy');
    const category = document.getElementById('category');
    const title = document.getElementById('title');
    const categoryField = document.querySelector('.category-field');
    const output = document.getElementById('generated-shortcode');
    const copyBtn = document.getElementById('copy-shortcode');

    function generateShortcode() {
        const t = type.value.trim();
        const tax = taxonomy.value.trim();
        const cat = category.value.trim();
        const ttl = title.value.trim();

        let shortcode = `[${t}`;

        if (tax) shortcode += ` taxonomy="${tax}"`;
        if (t === 'price_tracker_chart' && cat) shortcode += ` category="${cat}"`;
        if (ttl) shortcode += ` title="${ttl}"`;

        shortcode += `]`;

        output.value = shortcode;
    }

    [type, title].forEach(el =>
        el.addEventListener('input', generateShortcode)
    );

    type.addEventListener('change', () => {
        categoryField.style.display = type.value === 'price_tracker_chart' ? 'block' : 'none';
        generateShortcode();
    });
    taxonomy.addEventListener('change', generateShortcode);
    category.addEventListener('change', generateShortcode);

    if (window.jQuery) {
        (function ($) {
            $(document).on('change select2:select select2:unselect', '#taxonomy, #category', function () {
                generateShortcode();
            });
        })(jQuery);
    }

    copyBtn.addEventListener('click', () => {
        output.select();
        document.execCommand('copy');
        copyBtn.textContent = 'Copied!';
        setTimeout(() => (copyBtn.textContent = 'Copy to Clipboard'), 1500);
    });

    generateShortcode(); // initialize
});