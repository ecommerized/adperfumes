@extends('layouts.app')

@section('title', 'Return & Refund Policy - AD Perfumes')
@section('description', 'Learn about our return and refund policy at AD Perfumes.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Legal</p>
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            Return & Refund Policy
        </h1>
        <p class="text-[14px] text-brand-muted">
            Last updated: {{ date('F d, Y') }}
        </p>
    </div>
</section>

<!-- Policy Content -->
<section class="bg-brand-ivory py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10">
        <div class="prose prose-sm max-w-none">
            <div class="mb-10">
                <p class="text-[15px] text-brand-gray leading-relaxed">
                    At AD Perfumes, we want you to be completely satisfied with your purchase. If you're not entirely happy with your order,
                    we're here to help. Please read our return and refund policy carefully.
                </p>
            </div>

            <div class="space-y-8">
                <!-- Section 1 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Return Eligibility</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            To be eligible for a return, your item must meet the following conditions:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>The product must be unused and in the same condition that you received it</li>
                            <li>The product must be in its original packaging with all seals intact</li>
                            <li>The return must be initiated within 14 days of receiving the product</li>
                            <li>Proof of purchase (order confirmation email or receipt) must be provided</li>
                            <li>The product must not be a specially ordered or customized item</li>
                        </ul>
                        <p class="mt-4 font-semibold text-brand-dark">
                            Important: Due to hygiene reasons, we cannot accept returns on opened or used fragrances. All perfume bottles must have their original seals intact.
                        </p>
                    </div>
                </div>

                <!-- Section 2 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Non-Returnable Items</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>The following items cannot be returned:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Opened or used fragrances (for hygiene and safety reasons)</li>
                            <li>Products with broken seals or packaging</li>
                            <li>Sale or clearance items marked as final sale</li>
                            <li>Gift cards or promotional items</li>
                            <li>Special orders or custom engraved items</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 3 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Return Process</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>To initiate a return, please follow these steps:</p>
                        <ol class="list-decimal list-inside ml-4 space-y-3">
                            <li>
                                <strong>Contact Us:</strong> Email us at <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a>
                                within 14 days of receiving your order. Include your order number, product details, and reason for return.
                            </li>
                            <li>
                                <strong>Return Authorization:</strong> Our team will review your request and provide you with a Return Authorization (RA) number
                                and return instructions within 1-2 business days.
                            </li>
                            <li>
                                <strong>Package Your Return:</strong> Securely pack the item in its original packaging with all accessories and documentation.
                                Include a copy of your order confirmation and the RA number.
                            </li>
                            <li>
                                <strong>Ship the Return:</strong> Send the package using a trackable shipping method to the address provided.
                                You are responsible for return shipping costs unless the return is due to our error.
                            </li>
                        </ol>
                        <p class="mt-4 bg-brand-light border-l-2 border-brand-primary p-4">
                            <strong>Note:</strong> Returns sent without prior authorization may not be accepted. Please wait for confirmation and your RA number before shipping.
                        </p>
                    </div>
                </div>

                <!-- Section 4 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Refund Process</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Once we receive your return, our team will inspect it to ensure it meets our return policy criteria.
                            If approved, your refund will be processed as follows:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Refund inspection typically takes 2-3 business days after receipt</li>
                            <li>Approved refunds will be credited to your original payment method</li>
                            <li>Refund processing time: 5-10 business days depending on your bank or payment provider</li>
                            <li>You will receive an email confirmation once your refund has been processed</li>
                        </ul>
                        <p class="mt-4">
                            <strong>Refund Amount:</strong> The refund will be for the product price only. Original shipping fees are non-refundable,
                            unless the return is due to a defective product or our error. Return shipping costs are the customer's responsibility.
                        </p>
                    </div>
                </div>

                <!-- Section 5 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Exchanges</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We currently do not offer direct exchanges. If you would like a different product, please return the original item
                            for a refund and place a new order for the desired product.
                        </p>
                        <p>
                            This ensures you receive your preferred item as quickly as possible without waiting for the return processing.
                        </p>
                    </div>
                </div>

                <!-- Section 6 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Damaged or Defective Products</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            If you receive a damaged, defective, or incorrect product, please contact us immediately:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Email us within 48 hours of delivery at <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a></li>
                            <li>Include clear photos of the damage or defect</li>
                            <li>Provide your order number and description of the issue</li>
                        </ul>
                        <p class="mt-4">
                            We will arrange for a replacement or full refund, including return shipping costs. In cases of damaged shipments,
                            we may file a claim with the courier on your behalf.
                        </p>
                    </div>
                </div>

                <!-- Section 7 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Late or Missing Refunds</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            If you haven't received your refund after the expected processing time:
                        </p>
                        <ol class="list-decimal list-inside ml-4 space-y-2">
                            <li>Check your bank account or credit card statement again</li>
                            <li>Contact your bank or credit card company - it may take time for the refund to post</li>
                            <li>Contact your payment provider's customer service</li>
                            <li>If you've done all of this and still have not received your refund, contact us at <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a></li>
                        </ol>
                    </div>
                </div>

                <!-- Section 8 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Cancellations</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            You may cancel your order before it has been dispatched. Once an order has shipped, you will need to follow
                            the standard return process outlined above.
                        </p>
                        <p>
                            To cancel an order, please contact us immediately at <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a>
                            with your order number. If the order has not been processed, we will issue a full refund.
                        </p>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="mt-12 bg-brand-light p-8">
                    <h3 class="text-[20px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Questions About Returns?</h3>
                    <p class="text-[15px] text-brand-gray mb-4">
                        If you have any questions about our return and refund policy, please don't hesitate to contact us:
                    </p>
                    <div class="space-y-2 text-[15px]">
                        <p><strong>Email:</strong> <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a></p>
                        <p><strong>Phone:</strong> <a href="tel:+971501234567" class="text-brand-primary hover:underline">+971 50 123 4567</a></p>
                        <p><strong>Business Hours:</strong> Saturday - Thursday, 9:00 AM - 6:00 PM (UAE Time)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
