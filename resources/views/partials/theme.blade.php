<script>
    // Start in white; retain a theme explicitly selected afterwards.
    if (!localStorage.getItem('flux.appearance')) {
        localStorage.setItem('flux.appearance', 'light');
    }
</script>
@fluxAppearance
