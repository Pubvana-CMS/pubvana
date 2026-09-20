{# Themed error page body. Rendered by PublicController::renderErrorPage #}
{# and wrapped in the active theme's layout. status and message are #}
{# controller-provided plain text, escaped by Vision. This same file is #}
{# the fallback when a theme ships no errors/error.tpl of its own. #}
<div class="error-page text-center py-5">
    <h1 class="display-1">{{ status }}</h1>
    <p class="lead">{{ message }}</p>
</div>