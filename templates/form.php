<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="front" id="gdpr-email-form">
    <input type="hidden" name="action" value="gdpr_email_form">
    <?php wp_nonce_field('gdpr_email_form_action', 'gdpr_email_form_nonce'); ?>

    <!-- Honeypot -->
    <input type="text" name="name" style="display:none;" tabindex="-1" autocomplete="off">

    <!-- reCAPTCHA -->
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

    <label for="email">Zadejte e-mail kam doručit šablony:</label>
    <input type="email" name="email" placeholder="kamto@dorucit.cz" id="email" required>
    <br>
    <label class="souhlas">
        <input type="checkbox" name="consent" value="1" required>
        Souhlasím se zpracováním údajů.
    </label>
    <br>
    <div class="wp-block-button">
        <button type="submit" class="wp-block-button__link wp-element-button" id="submit-email">Odeslat</button>
    </div>
</form>


<!-- Popup okno -->
<?php include plugin_dir_path(__FILE__) . 'popup.php'; ?>



<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr(get_option('gdpr_recaptcha_site_key', '')); ?>"></script>
<script>
    // Google reCAPTCHA
    grecaptcha.ready(function () {
        grecaptcha.execute('<?php echo esc_attr(get_option('gdpr_recaptcha_site_key', '')); ?>', { action: 'submit' }).then(function (token) {
            document.getElementById('g-recaptcha-response').value = token;
        });
    });

    // Zpracování popup okna
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('gdpr-email-form');
        const popup = document.getElementById('payment-popup');
        const closePopup = document.getElementById('close-popup');
        const payButton = document.getElementById('pay-button');

        // Zobraz popup po kliknutí na tlačítko Odeslat
        form.addEventListener('submit', function (e) {
            e.preventDefault(); // Zastaví odeslání formuláře
            popup.style.display = 'block';
        });

        // Zavřít popup a pokračovat v odesílání e-mailu
        closePopup.addEventListener('click', function () {
            popup.style.display = 'none'; // Zavře popup
            form.submit(); // Odeslat formulář
        });

        // Stripe platba
        const stripe = Stripe('pk_test_51QkZmAGEmNpP0A8n7bpFCzPx4wVOVlQjOcaItW4pzRR2uF8VuBUwtwpazQFr9R0v6ZDGzsnBDWD0t0K2DoxQFcgR00hecGoXAn'); // Nahraď veřejným klíčem ze Stripe
        payButton.addEventListener('click', function () {
            stripe.redirectToCheckout({
                lineItems: [{ price: 'price_1Qka1bGEmNpP0A8nKp8sewot', quantity: 1 }], // Nahraď Price ID
                mode: 'payment',
                successUrl: window.location.href + '?payment=success',
                cancelUrl: window.location.href + '?payment=cancel',
            }).then(function (result) {
                if (result.error) {
                    alert(result.error.message);
                }
            });
        });
    });
</script>
