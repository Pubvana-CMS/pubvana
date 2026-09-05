{# Footer partial, included from layout.tpl. #}
<footer class="bg-dark text-light pt-5 pb-3">
    <div class="container">
        <div class="row">
            {# Regions: the three footer columns. Each prints nothing while empty. #}
            <div class="col-md-4 mb-3">
                {% region 'footer-col-1' %}
            </div>
            <div class="col-md-4 mb-3">
                {% region 'footer-col-2' %}
            </div>
            <div class="col-md-4 mb-3">
                {% region 'footer-col-3' %}
            </div>
        </div>

        {# Region: the general footer region. #}
        {% region 'footer' %}

        {# Theme option branch: the bottom copyright line renders only when enabled. #}
        {% if theme_options.footer_bottom.enabled %}
        <hr class="border-secondary">
        {# Raw output: a custom copyright is owner-authored markup; site.copyright is the fallback. #}
        {% if theme_options.footer_bottom.copyright %}
        <p class="text-center text-muted small mb-0">{! theme_options.footer_bottom.copyright !}</p>
        {% else %}
        <p class="text-center text-muted small mb-0">{! site.copyright !}</p>
        {% endif %}
        {% endif %}
    </div>
</footer>
