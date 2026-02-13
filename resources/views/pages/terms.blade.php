@extends('layouts.app')

@section('title', 'Terms & Conditions - AD Perfumes')
@section('description', 'Read the terms and conditions for shopping at AD Perfumes.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Legal</p>
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            Terms & Conditions
        </h1>
        <p class="text-[14px] text-brand-muted">
            Last updated: {{ date('F d, Y') }}
        </p>
    </div>
</section>

<!-- Terms Content -->
<section class="bg-brand-ivory py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10">
        <div class="prose prose-sm max-w-none">
            <div class="mb-10">
                <p class="text-[15px] text-brand-gray leading-relaxed">
                    Welcome to AD Perfumes. These Terms and Conditions outline the rules and regulations for the use of AD Perfumes' website and services.
                    By accessing this website, we assume you accept these terms and conditions. Do not continue to use adperfumes.ae if you do not agree to all of the terms and conditions stated on this page.
                </p>
            </div>

            <div class="space-y-8">
                <!-- Section 1 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">1. General Terms</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            By using this website, you certify that you are at least 18 years of age and that you have the legal capacity to enter into contracts.
                            The terminology "Client," "You," and "Your" refers to you, the person accessing this website. "AD Perfumes," "We," "Our," and "Us" refers to AD Perfumes.
                        </p>
                        <p>
                            We reserve the right to update, change, or replace any part of these Terms and Conditions by posting updates on our website.
                            It is your responsibility to check this page periodically for changes. Your continued use of or access to the website following the posting of any changes constitutes acceptance of those changes.
                        </p>
                    </div>
                </div>

                <!-- Section 2 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">2. Product Information</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            All products sold on AD Perfumes are 100% authentic and sourced from authorized distributors. We make every effort to display the colors and images of our products as accurately as possible. However, we cannot guarantee that your computer monitor's display of any color will be accurate.
                        </p>
                        <p>
                            We reserve the right to limit the quantities of any products or services that we offer. All product descriptions, pricing, and availability are subject to change at any time without notice, at our sole discretion.
                        </p>
                        <p>
                            We do not warrant that the quality of any products, services, information, or other material purchased or obtained by you will meet your expectations, or that any errors in the service will be corrected.
                        </p>
                    </div>
                </div>

                <!-- Section 3 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">3. Pricing & Payment</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            All prices are listed in UAE Dirhams (AED) and are inclusive of VAT unless otherwise stated. We reserve the right to change prices without prior notice. However, the price confirmed in your order will be honored.
                        </p>
                        <p>
                            Payment must be made in full before your order is processed. We accept various payment methods including credit cards, debit cards, and other electronic payment options as displayed during checkout.
                        </p>
                        <p>
                            In the event of a payment failure or declined transaction, your order will not be processed until payment is successfully completed.
                        </p>
                    </div>
                </div>

                <!-- Section 4 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">4. Orders & Acceptance</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Placing an order on our website constitutes an offer to purchase products. All orders are subject to acceptance by us. We reserve the right to refuse or cancel any order for any reason, including but not limited to:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Product availability issues</li>
                            <li>Errors in product or pricing information</li>
                            <li>Suspected fraudulent transactions</li>
                            <li>Payment authorization failures</li>
                        </ul>
                        <p>
                            You will receive an order confirmation email once your order has been placed. This email confirms that we have received your order, but does not constitute acceptance. Acceptance occurs when we dispatch the products to you.
                        </p>
                    </div>
                </div>

                <!-- Section 5 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">5. Shipping & Delivery</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We ship to addresses within the United Arab Emirates. Delivery times are estimated and not guaranteed. We are not liable for delays caused by courier services, customs, or other factors beyond our control.
                        </p>
                        <p>
                            Title and risk of loss for products pass to you upon delivery to the shipping carrier. Please inspect your order upon receipt and contact us immediately if there are any issues.
                        </p>
                    </div>
                </div>

                <!-- Section 6 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">6. Returns & Refunds</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Please refer to our <a href="{{ route('return-policy') }}" class="text-brand-primary hover:underline">Return & Refund Policy</a> for detailed information about returns, exchanges, and refunds.
                        </p>
                    </div>
                </div>

                <!-- Section 7 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">7. Prohibited Uses</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>You may not use our website for any of the following purposes:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Violating any applicable laws or regulations</li>
                            <li>Transmitting any malicious code, viruses, or harmful components</li>
                            <li>Attempting to gain unauthorized access to our systems</li>
                            <li>Engaging in any form of fraudulent activity</li>
                            <li>Reselling products for commercial purposes without authorization</li>
                            <li>Using automated systems to scrape or collect data</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 8 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">8. Intellectual Property</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            All content on this website, including but not limited to text, graphics, logos, images, and software, is the property of AD Perfumes or its content suppliers and is protected by international copyright laws.
                        </p>
                        <p>
                            You may not reproduce, distribute, modify, create derivative works of, publicly display, or exploit any content from this website without our express written permission.
                        </p>
                    </div>
                </div>

                <!-- Section 9 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">9. Limitation of Liability</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            To the maximum extent permitted by law, AD Perfumes shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation loss of profits, data, or use, arising out of or in any way connected with the use of our website or products.
                        </p>
                        <p>
                            Our total liability for any claims arising out of or relating to these Terms and Conditions or your use of our website shall not exceed the amount you paid for the product(s) in question.
                        </p>
                    </div>
                </div>

                <!-- Section 10 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">10. Governing Law</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            These Terms and Conditions shall be governed by and construed in accordance with the laws of the United Arab Emirates. Any disputes arising out of or in connection with these terms shall be subject to the exclusive jurisdiction of the courts of the UAE.
                        </p>
                    </div>
                </div>

                <!-- Section 11 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">11. Contact Information</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            If you have any questions about these Terms and Conditions, please contact us:
                        </p>
                        <ul class="list-none ml-4 space-y-2">
                            <li>Email: <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a></li>
                            <li>Phone: +971 50 123 4567</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
