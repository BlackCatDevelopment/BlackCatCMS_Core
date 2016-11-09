<div class="cat-alert">
    <?php echo $errinfo ?>: <?php echo $message ?>
    <script>
        var close = document.getElementsByClassName("cat-alert");
        close[0].addEventListener('click', function() {
            this.className += " hide";
        }, false);
    </script>
</div>