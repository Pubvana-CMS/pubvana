{% if show_paywall %}
<div class="{{ cls_widget | default('widget widget-paywall') }}">
    <div class="{{ cls_fade | default('widget-paywall-fade') }}"></div>
    <div class="{{ cls_cta | default('widget-paywall-cta') }}">
        <i class="fas fa-lock {{ cls_icon | default('widget-paywall-icon') }}"></i>
        <h4 class="{{ cls_paywall_title | default('widget-paywall-title') }}">{% lang 'Blog.paywallTitle' %}</h4>
        <p class="{{ cls_message | default('widget-paywall-message') }}">{% lang 'Blog.paywallMessage' %}</p>
        <a href="{{ login_url }}" class="{{ cls_btn_primary | default('widget-paywall-button widget-paywall-button-primary') }}">
            <i class="fas fa-right-to-bracket"></i> {% lang 'Blog.paywallSignIn' %}
        </a>
        <a href="{{ register_url }}" class="{{ cls_btn_secondary | default('widget-paywall-button widget-paywall-button-secondary') }}">
            {% lang 'Blog.paywallCreateAccount' %}
        </a>
    </div>
</div>
{% endif %}
