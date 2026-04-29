</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> FinTrack</p>
    </div>
</footer>

<script>
    // User dropdown toggle
    function toggleDropdown(e) {
        e.stopPropagation();
        document.getElementById('nav-dd').classList.toggle('open');
    }
    document.addEventListener('click', () => {
        const dd = document.getElementById('nav-dd');
        if (dd) dd.classList.remove('open');
    });
</script>

</body>
</html>
