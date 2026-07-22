
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php if (isset($extraJs)) echo $extraJs; ?>

    <!-- 版本信息 -->
    <footer class="site-footer bg-light text-center py-2">
        <small class="text-muted">
            Billfish Web Manager <?php echo defined('BILLFISH_WEB_VERSION') ? BILLFISH_WEB_VERSION : '0.1.2'; ?>
            | Build <?php echo defined('BILLFISH_WEB_BUILD_DATE') ? BILLFISH_WEB_BUILD_DATE : '2025-10-15'; ?>
            | <a href="https://rzx.me" target="_blank" rel="noopener noreferrer">rzx.me</a>
        </small>
    </footer>
</body>
</html>
