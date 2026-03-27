{% if profile %}
{% if profile.bio or profile.avatar or profile.twitter or profile.facebook or profile.linkedin %}
<div class="{{ cls_widget | default('widget widget-author-bio') }}">
    <div class="{{ cls_card | default('widget-card') }}">
        {% if profile.avatar %}
            <img src="{% base_url profile.avatar %}"
                 alt="{{ profile.display_name | default(profile.username) }}"
                 class="{{ cls_card_image | default('widget-card-image') }}">
        {% else %}
            <img src="https://www.gravatar.com/avatar/{{ profile.email | strtolower | md5 }}?s=96&d=mp"
                 alt="{{ profile.display_name | default(profile.username) }}"
                 class="{{ cls_card_image | default('widget-card-image') }}">
        {% endif %}
        <div class="{{ cls_card_body | default('widget-card-body') }}">
            <div class="{{ cls_card_title | default('widget-card-title') }}">{{ profile.display_name | default(profile.username) }}</div>
            {% if profile.bio %}
                <p class="{{ cls_card_text | default('widget-card-text') }}">{{ profile.bio | nl2br | raw }}</p>
            {% endif %}
            {% if profile.website or profile.twitter or profile.facebook or profile.linkedin %}
            <div class="{{ cls_social_links | default('widget-social-links') }}">
                {% if profile.website %}
                    <a href="{{ profile.website }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="Website"><i class="fas fa-globe"></i></a>
                {% endif %}
                {% if profile.twitter %}
                    <a href="https://twitter.com/{{ profile.twitter }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="Twitter"><i class="fab fa-twitter"></i></a>
                {% endif %}
                {% if profile.facebook %}
                    <a href="https://facebook.com/{{ profile.facebook }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook"></i></a>
                {% endif %}
                {% if profile.linkedin %}
                    <a href="https://linkedin.com/in/{{ profile.linkedin }}" class="{{ cls_social_link | default('widget-social-link') }}" target="_blank" rel="noopener" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                {% endif %}
            </div>
            {% endif %}
        </div>
    </div>
</div>
{% endif %}
{% endif %}
