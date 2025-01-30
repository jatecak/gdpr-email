<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="front" id="gdpr-email-form">
    <input type="hidden" name="action" value="gdpr_email_form">
    <?php wp_nonce_field('gdpr_email_form_action', 'gdpr_email_form_nonce'); ?>

    <!-- Honeypot -->
    <input type="text" name="name" style="display:none;" tabindex="-1" autocomplete="off">

    <!-- reCAPTCHA -->
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

    <!-- Donation -->
    <input type="hidden" name="donation" value="no" id="donation-input">

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


<div id="payment-popup" style="display:none; position:fixed; top:0; left:0; max-width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:320px; padding:20px; background:#f9d66d; text-align:center; border-radius:0px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
        <h3 style="font-family: Jost, sans-serif !important;">Oslovil vás tento projekt?</h3>
        <p style="color:#900000;">Líbí se vám myšlenka Hravého Spoření?<br>Podpořte tento projekt a pozvěte mě na kávu za 69 Kč.</p>
        <button id="close-popup" style="padding:10px 20px; background:none; color:blue; border:none; cursor:pointer; text-decoration:underline;">Nechci podpořit projekt</button><br>
        <button id="pay-button" style="padding:10px 30px; background:green; color:white; border:none; cursor:pointer; font-size:22px; margin-top:10px;">Dejte si kávu na mě</button>
        <p style="color:#900000; font-size: 1.2em;">Vaše podpora mi pomůže dál tvořit materiály, které dětem a rodinám pomáhají přiblížit finanční gramotnost!</p>
        <p style="color:#900000; font-size: 1em;">Díky tomu bude tento projekt žít a dále přinášet užitek.</p>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr(get_option('gdpr_recaptcha_site_key', '')); ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('gdpr-email-form');
        const popup = document.getElementById('payment-popup');
        const closePopup = document.getElementById('close-popup');
        const payButton = document.getElementById('pay-button');
        const donationInput = document.getElementById('donation-input');

        // Google reCAPTCHA
        grecaptcha.ready(function () {
            grecaptcha.execute('<?php echo esc_attr(get_option('gdpr_recaptcha_site_key', '')); ?>', { action: 'submit' }).then(function (token) {
                document.getElementById('g-recaptcha-response').value = token;
            });
        });

        // Zpracování formuláře (zobraz popup okno)
        form.addEventListener('submit', function (e) {
            e.preventDefault(); // Zastaví odeslání formuláře
            popup.style.display = 'block';
        });

        // Zavřít popup a pokračovat bez příspěvku
        closePopup.addEventListener('click', function () {
            donationInput.value = 'no'; // Nastaví hodnotu "Nepřispívat"
            popup.style.display = 'none';
            form.submit(); // Odeslat formulář
        });

        // Stripe platba (příspěvek na kávu)
        const stripe = Stripe('pk_test_51QkZmAGEmNpP0A8n7bpFCzPx4wVOVlQjOcaItW4pzRR2uF8VuBUwtwpazQFr9R0v6ZDGzsnBDWD0t0K2DoxQFcgR00hecGoXAn'); // Nahraď veřejným klíčem ze Stripe
        payButton.addEventListener('click', function () {
            donationInput.value = 'yes'; // Nastaví hodnotu "Chci přispět"
            
            // Přesměrování na Stripe Checkout
            stripe.redirectToCheckout({
                lineItems: [{ price: 'price_1Qka1bGEmNpP0A8nKp8sewot', quantity: 1 }], // Nahraď Price ID
                mode: 'payment',
                successUrl: '<?php echo esc_url(home_url('/dekujeme?payment=success')); ?>',
                cancelUrl: '<?php echo esc_url(home_url('/formular?payment=cancel')); ?>',
            }).then(function (result) {
                if (result.error) {
                    alert('Chyba při zpracování platby: ' + result.error.message);
                }
            });
        });
    });
</script>
