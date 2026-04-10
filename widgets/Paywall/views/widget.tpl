{% if paywall %}
<div id="pw-widget" class="{{ cls_widget | default('widget widget-paywall') }}" style="display:none" aria-hidden="true">
    <div class="{{ cls_fade | default('widget-paywall-fade') }}"></div>
    <div class="{{ cls_cta | default('widget-paywall-cta') }}">
        <i class="fas fa-lock {{ cls_icon | default('widget-paywall-icon') }}"></i>
        <h4 class="{{ cls_paywall_title | default('widget-paywall-title') }}">{% lang 'Blog.paywallTitle' %}</h4>
        <p class="{{ cls_message | default('widget-paywall-message') }}">{% lang 'Blog.paywallMessage' %}</p>
        <p id="pw-pricing" class="{{ cls_pricing | default('widget-paywall-pricing') }}" style="display:none"></p>
        <a id="pw-btn-login" href="{% site_url 'login' %}" class="{{ cls_btn_primary | default('widget-paywall-button widget-paywall-button-primary') }}" style="display:none">
            <i class="fas fa-right-to-bracket"></i> {% lang 'Paywall.loginToSubscribe' %}
        </a>
        <a id="pw-btn-subscribe" href="{% site_url 'paywall/subscribe' %}" class="{{ cls_btn_secondary | default('widget-paywall-button widget-paywall-button-secondary') }}" style="display:none">
            {% lang 'Paywall.subscribe' %}
        </a>
    </div>
</div>
<script>
(function () {
    var widget = document.getElementById('pw-widget');
    var pricing = document.getElementById('pw-pricing');
    var btnLogin = document.getElementById('pw-btn-login');
    var btnSubscribe = document.getElementById('pw-btn-subscribe');

    function showWidget(showLogin) {
        if (showLogin) { btnLogin.style.display = ''; }
        btnSubscribe.style.display = '';
        widget.style.display = '';
        widget.removeAttribute('aria-hidden');
    }

    function renderPricing(plans) {
        if (!plans || !plans.length) { return; }
        var plan = null;
        for (var i = 0; i < plans.length; i++) {
            if (plans[i].is_default) { plan = plans[i]; break; }
        }
        if (!plan) { plan = plans[0]; }
        var parts = [];
        if (plan.price_monthly) {
            parts.push(plan.currency + ' ' + parseFloat(plan.price_monthly).toFixed(2) + '/mo');
        }
        if (plan.price_yearly) {
            parts.push(plan.currency + ' ' + parseFloat(plan.price_yearly).toFixed(2) + '/yr');
        }
        if (parts.length) {
            pricing.textContent = parts.join(' \u00b7 ');
            pricing.style.display = '';
        }
    }

    Promise.all([
        fetch('/api/paywall/v1/plans', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .catch(function () { return null; }),
        fetch('/api/paywall/v1/status', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { return { logged_in: !!d.logged_in, is_premium: !!d.is_premium }; })
            .catch(function () { return { logged_in: false, is_premium: false }; })
    ]).then(function (results) {
        var plansData = results[0];
        var statusData = results[1];

        if (statusData.is_premium) { return; }

        if (plansData && plansData.plans) {
            renderPricing(plansData.plans);
        }

        showWidget(!statusData.logged_in);
    }).catch(function () { showWidget(false); });
}());
</script>
{% endif %}
