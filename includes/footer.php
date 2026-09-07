<?php
/**
 * Global Footer Layout Component
 * Customer Management System
 */

$baseUrl = getBaseUrl();
?>
        </main>
        
        <!-- Footer -->
        <footer class="mt-auto py-3 bg-white border-top text-center text-muted fs-7">
            <div class="container-fluid">
                <span>&copy; <?php echo date('Y'); ?> <strong>Customer Management System</strong>. All rights reserved. | Core PHP & MySQL System</span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<!-- System Custom Script -->
<script src="<?php echo $baseUrl; ?>assets/js/script.js"></script>
</body>
</html>
