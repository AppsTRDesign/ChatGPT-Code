(function(){
 document.addEventListener('DOMContentLoaded', () => {
  if (!document.body.classList.contains('pro-ultra-advanced-checkout')) { return; }

  const methods = document.querySelectorAll('#payment ul.payment_methods li.wc_payment_method');
  const summaryToggle = document.querySelector('[data-summary-toggle]');
  const review = document.querySelector('[data-checkout-review]');
  const steps = document.querySelectorAll('[data-checkout-steps] .step');

  const activateMethod = (el) => {
   methods.forEach(item => item.classList.remove('method-active'));
   if (el) { el.classList.add('method-active'); }
  };

  methods.forEach(item => {
   const input = item.querySelector('input[type="radio"]');
   if (input && input.checked) { activateMethod(item); }
   item.addEventListener('click', () => activateMethod(item));
  });

  if (summaryToggle && review) {
   summaryToggle.addEventListener('click', (e) => {
    e.preventDefault();
    const opened = document.body.classList.toggle('pro-ultra-summary-open');
    summaryToggle.classList.toggle('is-open', opened);
    const text = summaryToggle.querySelector(opened ? '.close-text' : '.open-text');
    if (text) { text.focus({ preventScroll: true }); }
   });
  }

  // Payment step indicator update.
  if (steps.length === 3) {
   const updateSteps = () => {
    steps.forEach((step, index) => {
     step.classList.toggle('is-active', index <= 1);
    });
   };
   updateSteps();
   document.body.addEventListener('payment_method_selected', updateSteps);
  }

  // Stripe styling hook if element exists.
  const stripeForm = document.getElementById('wc-stripe-cc-form');
  if (stripeForm) {
   stripeForm.classList.add('pro-ultra-stripe');
  }

  // Handle checkout errors to toast.
  document.body.addEventListener('checkout_error', () => {
   if (typeof toast === 'function' && typeof proUltraCheckoutUX !== 'undefined') {
    toast(proUltraCheckoutUX.toastError || 'Hata');
   }
  });
 });
})();
