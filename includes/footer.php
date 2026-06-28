<?php
// includes/footer.php
?>
</div> <!-- End content-container -->
</div> <!-- End main-panel -->
</div> <!-- End wrapper -->

<script>
    // Sidebar Toggle Script
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const wrapper = document.querySelector('.wrapper');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            wrapper.classList.toggle('sidebar-collapsed');
        });
    }
</script>
</body>

</html>