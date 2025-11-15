
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <?php if (isset($extraJs)) echo $extraJs; ?>
    
    <!-- 版本信息 -->
    <footer class="bg-light text-center py-3 mt-5">
        <small class="text-muted">
            Billfish Web Manager <?php echo defined('BILLFISH_WEB_VERSION') ? BILLFISH_WEB_VERSION : '0.1.2'; ?> 
            | Build <?php echo defined('BILLFISH_WEB_BUILD_DATE') ? BILLFISH_WEB_BUILD_DATE : '2025-10-15'; ?>
            | <a href="docs-ui.php"></a>
            | <a href="https://rzx.me">rzx.me</a>
            | <a href="database-health.php"></a>
        </small>
    </footer>
</body>
</html>
