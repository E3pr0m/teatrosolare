<footer class="site-footer">
    <div class="container">
        <div class="footerLogo">
            <a href="<?php echo get_site_url();?>">
                <?php 
                    include 'assets/images/siteLogo.svg';                 
                ?>
            </a>
            </div>
						<div style="text-align:center">
							<img src="/wp-content/uploads/2025/10/pnnr.jpg" alt="Progetto finanziato con il PNNR" style="width: 45%;margin-bottom: 25px"/>
						</div>
            <?php
                wp_nav_menu( array(
                    'menu' => 'Footer Menu',
                    'menu_id' => 'footer-menu', 
                    'menu_class' => 'unstyledList footerMenu',
                ) );
            ?>
            <ul class="unstyledList footerSocialMenu">
                <?php
                    $socialLinks = get_field('social_links', 'option');
                ?>
                <li><a href="<?php echo !empty($socialLinks['instagram']['url']) ? $socialLinks['instagram']['url'] : '#'; ?>"></a></li>
                <li><a href="<?php echo !empty($socialLinks['facebook']['url']) ? $socialLinks['facebook']['url'] : '#'; ?>"></a></li>
                <li><a href="<?php echo !empty($socialLinks['twitter']['url']) ? $socialLinks['twitter']['url'] : '#'; ?>"></a></li>
                <li><a href="<?php echo !empty($socialLinks['linkedin']['url']) ? $socialLinks['linkedin']['url'] : '#'; ?>"></a></li>
                <li><a href="<?php echo !empty($socialLinks['email']['url']) ? 'mailto:'.$socialLinks['email']['url'] : '#'; ?>"></a></li>                
            </ul>
            <div class="footerCopyright">
                &copy; Teatro Solare <?php echo date('Y'); ?>
            </div>
        </div>        
    </footer>
<?php  if ( is_page(11) ) { ?>
<script>
jQuery(function($){
    // Seleziona tutti gli input nella pagina con ID 11
    $('.page-id-11 input').each(function(){
        var $input = $(this);
        // Se il valore non è vuoto
        if($input.val() !== ''){
            $input.css({
                'pointer-events': 'none',
                'user-select': 'none',
                '-webkit-user-select': 'none',
                'caret-color': 'transparent',
                'background': '#f5f5f5',
                'color': '#666',
                'border': '1px solid #ddd'
            });
        }
    });
});

</script>
<?php } ?>




<?php wp_footer(); ?>
</body>
</html>