    </main>
    <?php if (isLoggedIn()): ?>
            <footer class="footer">
                <div class="footer-content">
                    <p>&copy; <?php echo date('Y'); ?> Plaza Management System</p>
                </div>
            </footer>
        </div>
    <?php else: ?>
        <footer class="footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> Plaza Management System</p>
            </div>
        </footer>
    <?php endif; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

