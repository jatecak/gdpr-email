jQuery(document).ready(function ($) {
    $('.edit-email').on('click', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const row = $(`#edit-row-${id}`);

        // Schovat ostatní editaèní formuláøe
        $('.edit-row').not(row).hide();

        // Zobrazit/skrýt formuláø pro úpravy
        row.toggle();
    });
});



document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('gdpr-email-form');
    const popup = document.getElementById('payment-popup');
    const closePopup = document.getElementById('close-popup');
    const payButton = document.getElementById('pay-button');

    // Zobraz popup po kliknutí na tlaèítko Odeslat
    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Zastaví odeslání formuláøe
        popup.style.display = 'block';
    });

    // Zavøít popup a pokraèovat v odesílání e-mailu
    closePopup.addEventListener('click', function () {
        popup.style.display = 'none'; // Zavøe popup
        form.submit(); // Odeslat formuláø
    });

    // Stripe platba
    const stripe = Stripe('pk_test_51QkZmAGEmNpP0A8n7bpFCzPx4wVOVlQjOcaItW4pzRR2uF8VuBUwtwpazQFr9R0v6ZDGzsnBDWD0t0K2DoxQFcgR00hecGoXAn'); // Nahraï veøejným klíèem ze Stripe
    payButton.addEventListener('click', function () {
        stripe.redirectToCheckout({
            lineItems: [{ price: 'price_1Qka1bGEmNpP0A8nKp8sewot', quantity: 1 }], // Nahraï Price ID
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
