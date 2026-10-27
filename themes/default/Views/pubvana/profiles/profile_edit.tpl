{# Profile edit form. Content-only; the layout owns the sidebar. #}
{# Tabs: Profile and Security, then any plugin tab registered through
   profile.tab. Only the open tab is rendered. #}
{% if hasTabs %}
<nav class="pv-profile-tabs" aria-label="Profile sections">
    {% for tab in tabs %}
    <a href="{{ profileBase }}/{{ user.id }}/edit?tab={{ tab.id }}"
       class="pv-profile-tab{% if tab.active %} pv-profile-tab-active{% endif %}"
       {% if tab.active %}aria-current="page"{% endif %}>{{ tab.label }}</a>
    {% endfor %}
</nav>
{% endif %}
{% if activeTabId == 'profile' %}
{# The h1: this page has no other heading, so the section title is the top
   level one. The class keeps the size the design already used. #}
<div class="pv-profile-card">
    <h1 class="pv-profile-section-title">Edit Profile</h1>
    {# Plain HTML form: POSTs to the profile update endpoint. #}
    <form method="post" action="{{ profileBase }}/{{ user.id }}/update" class="pv-profile-form">
        {# Custom tag: emits the hidden CSRF token input the framework validates on POST. #}
        {% csrf_field %}

        {# Every field follows the same pattern: label, input, value from the profile. #}
        {# Escaped output in each value attribute. #}
        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="display_name">Display Name</label>
            <input type="text" class="pv-profile-form-input" id="display_name" name="display_name"
                   value="{{ profile.display_name }}">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="bio">Bio</label>
            <textarea class="pv-profile-form-textarea" id="bio" name="bio" rows="4">{{ profile.bio }}</textarea>
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label">Avatar</label>
            {# Custom tag: the Media plugin's picker, arguments are the field name and current value. #}
            {% media_picker 'avatar' profile.avatar %}
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="website">Website</label>
            <input type="text" class="pv-profile-form-input" id="website" name="website"
                   value="{{ profile.website }}">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="twitter">Twitter</label>
            <input type="text" class="pv-profile-form-input" id="twitter" name="twitter"
                   value="{{ profile.twitter }}">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="facebook">Facebook</label>
            <input type="text" class="pv-profile-form-input" id="facebook" name="facebook"
                   value="{{ profile.facebook }}">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="linkedin">LinkedIn</label>
            <input type="text" class="pv-profile-form-input" id="linkedin" name="linkedin"
                   value="{{ profile.linkedin }}">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="job_title">Job Title</label>
            <input type="text" class="pv-profile-form-input" id="job_title" name="job_title"
                   value="{{ profile.job_title }}">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="works_for">Works For</label>
            <input type="text" class="pv-profile-form-input" id="works_for" name="works_for"
                   value="{{ profile.works_for }}">
        </div>

        <div class="pv-profile-form-actions">
            <button type="submit" class="pv-profile-btn">Save Profile</button>
            <a href="{{ profileBase }}/{{ user.id }}" class="pv-profile-btn pv-profile-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
{% elseif activeTabId == 'security' and security %}
{# Same Security tab as the admin profile. #}
<div class="pv-profile-card">
    <h1 class="pv-profile-section-title">Security</h1>

    <form method="post" action="{{ profileBase }}/{{ user.id }}/security/credentials" class="pv-profile-form pv-profile-form-section">
        {% csrf_field %}
        <input type="hidden" name="return_url" value="{{ profileBase }}/{{ user.id }}/edit?tab=security">

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="sec_email">Email</label>
            <input type="email" class="pv-profile-form-input" id="sec_email" name="email"
                   value="{{ security.email }}" autocomplete="email">
        </div>

        <div class="pv-profile-form-actions">
            <button type="submit" class="pv-profile-btn">Change Email</button>
        </div>
    </form>

    <form method="post" action="{{ profileBase }}/{{ user.id }}/security/password" class="pv-profile-form pv-profile-form-section">
        {% csrf_field %}
        <input type="hidden" name="return_url" value="{{ profileBase }}/{{ user.id }}/edit?tab=security">

        <h2 class="pv-profile-section-title">Change Password</h2>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="sec_current_password">Current Password</label>
            <input type="password" class="pv-profile-form-input" id="sec_current_password"
                   name="current_password" autocomplete="current-password">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="sec_password">New Password</label>
            <input type="password" class="pv-profile-form-input" id="sec_password"
                   name="password" autocomplete="new-password">
        </div>

        <div class="pv-profile-form-field">
            <label class="pv-profile-form-label" for="sec_password_confirm">Confirm New Password</label>
            <input type="password" class="pv-profile-form-input" id="sec_password_confirm"
                   name="password_confirm" autocomplete="new-password">
        </div>

        <p class="pv-profile-meta">Changing the password signs out every other device.</p>

        <div class="pv-profile-form-actions">
            <button type="submit" class="pv-profile-btn">Change Password</button>
        </div>
    </form>

    {% if security.two_factor %}
    <div class="pv-profile-section">
        <h2 class="pv-profile-section-title">Two-Factor Authentication</h2>
        <p class="pv-profile-bio">Email two-factor authentication is on. A code arrives after each sign-in.</p>
    </div>
    {% endif %}

    <div class="pv-profile-section">
        <h2 class="pv-profile-section-title">Sessions</h2>
        <table class="pv-profile-table">
            <thead>
                <tr>
                    <th scope="col">Device</th>
                    <th scope="col">IP Address</th>
                    <th scope="col">Last Active</th>
                    <th scope="col"><span class="visually-hidden">Action</span></th>
                </tr>
            </thead>
            <tbody>
                {% for device in security.sessions %}
                <tr>
                    <td>
                        {{ device.device }}
                        {% if device.current %}<span class="pv-profile-badge">This device</span>{% endif %}
                    </td>
                    <td>{{ device.ip }}</td>
                    <td>{{ device.last_active }}</td>
                    <td>
                        {% if not device.current %}
                        <form method="post" action="{{ profileBase }}/{{ user.id }}/security/sessions/revoke">
                            {% csrf_field %}
                            <input type="hidden" name="return_url" value="{{ profileBase }}/{{ user.id }}/edit?tab=security">
                            <input type="hidden" name="session_id" value="{{ device.id }}">
                            <button type="submit" class="pv-profile-btn pv-profile-btn-secondary">Sign out</button>
                        </form>
                        {% endif %}
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>

        <form method="post" action="{{ profileBase }}/{{ user.id }}/security/sessions/revoke" class="pv-profile-form-actions">
            {% csrf_field %}
            <input type="hidden" name="return_url" value="{{ profileBase }}/{{ user.id }}/edit?tab=security">
            <button type="submit" class="pv-profile-btn pv-profile-btn-secondary">Sign out other devices</button>
        </form>
    </div>
</div>
{% else %}
{# Raw output: a plugin tab body, trusted HTML built by the plugin. #}
<div class="pv-profile-card">{! activeTabOutput !}</div>
{% endif %}
