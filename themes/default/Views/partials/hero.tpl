<section class="py-5 text-center text-white"
    {% if theme_options.hero.background %}
    style="background: url('/{{ theme_options.hero.background }}') center/cover no-repeat; min-height: 300px;"
    {% else %}
    style="background-color: #2c3e50; min-height: 300px;"
    {% endif %}
>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 300px;">
        <div>
            {% if theme_options.hero.title %}
            <h1 class="display-4 fw-bold">{{ theme_options.hero.title }}</h1>
            {% endif %}
        </div>
    </div>
</section>
