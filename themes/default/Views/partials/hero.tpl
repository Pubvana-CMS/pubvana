{# Hero banner partial, included from layout.tpl when the site owner enables it. #}
{# The section picks its background: an owner-chosen image, or the fallback color. #}
<section class="py-5 text-center text-white"
    {# Conditional inline style: image background when set, solid color otherwise. #}
    {% if theme_options.hero.background %}
    style="background: url('/{{ theme_options.hero.background }}') center/cover no-repeat; min-height: 300px;"
    {% else %}
    style="background-color: #2c3e50; min-height: 300px;"
    {% endif %}
>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 300px;">
        <div>
            {# Conditional: the hero title renders only when the option is filled. #}
            {# Deliberately not a heading. The page templates own the h1, and an
               h1 here would give every page with the hero enabled two of them. #}
            {% if theme_options.hero.title %}
            <p class="display-4 fw-bold mb-0">{{ theme_options.hero.title }}</p>
            {% endif %}
        </div>
    </div>
</section>
