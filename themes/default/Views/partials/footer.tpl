<footer class="bg-dark text-light pt-5 pb-3">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                {! theme_regions.footer_col_1 !}
            </div>
            <div class="col-md-4 mb-3">
                {! theme_regions.footer_col_2 !}
            </div>
            <div class="col-md-4 mb-3">
                {! theme_regions.footer_col_3 !}
            </div>
        </div>

        {! theme_regions.footer !}

        {% if theme_options.footer_bottom.enabled %}
        <hr class="border-secondary">
        {% if theme_options.footer_bottom.copyright %}
        <p class="text-center text-muted small mb-0">{! theme_options.footer_bottom.copyright !}</p>
        {% else %}
        <p class="text-center text-muted small mb-0">{! site.copyright !}</p>
        {% endif %}
        {% endif %}
    </div>
</footer>
