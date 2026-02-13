@extends('layouts.app')

@section('title', 'Shipping Policy - AD Perfumes')
@section('description', 'Learn about our shipping policy and delivery information for UAE.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Legal</p>
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            Shipping Policy
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
                    At AD Perfumes, we strive to deliver your luxury fragrances safely and efficiently across the United Arab Emirates.
                    Please review our shipping policy for important information about delivery times, costs, and procedures.
                </p>
            </div>

            <div class="space-y-8">
                <!-- Section 1 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Shipping Coverage</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We currently ship to all Emirates within the United Arab Emirates:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Dubai</li>
                            <li>Abu Dhabi</li>
                            <li>Sharjah</li>
                            <li>Ajman</li>
                            <li>Ras Al Khaimah</li>
                            <li>Fujairah</li>
                            <li>Umm Al Quwain</li>
                        </ul>
                        <p class="mt-4">
                            At this time, we do not offer international shipping. All orders must have a valid UAE delivery address.
                        </p>
                    </div>
                </div>

                <!-- Section 2 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Shipping Costs</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <div class="bg-brand-light p-6 border-l-4 border-brand-primary">
                            <p class="font-semibold text-brand-dark mb-2">FREE SHIPPING ON ORDERS OVER AED 300</p>
                            <p>We offer complimentary standard shipping on all orders totaling AED 300 or more.</p>
                        </div>

                        <p class="mt-6">For orders under AED 300:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li><strong>Dubai & Northern Emirates:</strong> AED 25</li>
                            <li><strong>Abu Dhabi & Al Ain:</strong> AED 30</li>
                            <li><strong>Remote Areas:</strong> AED 40</li>
                        </ul>

                        <p class="mt-4">
                            Shipping costs are calculated automatically at checkout based on your delivery location.
                        </p>
                    </div>
                </div>

                <!-- Section 3 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Processing Time</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            All orders are processed within 1-2 business days (Saturday - Thursday, excluding UAE public holidays).
                            Orders placed on Fridays or public holidays will be processed the next business day.
                        </p>
                        <p>
                            You will receive an email confirmation once your order has been placed, and another email with tracking information
                            once your order has been dispatched.
                        </p>
                        <p class="bg-brand-light border-l-2 border-brand-primary p-4 mt-4">
                            <strong>Note:</strong> Processing time does not include delivery time. Please refer to the Delivery Timeframes section below.
                        </p>
                    </div>
                </div>

                <!-- Section 4 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Delivery Timeframes</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Standard delivery times after order dispatch:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li><strong>Dubai:</strong> 1-2 business days</li>
                            <li><strong>Sharjah, Ajman, Abu Dhabi:</strong> 2-3 business days</li>
                            <li><strong>Northern Emirates (RAK, Fujairah, UAQ):</strong> 2-4 business days</li>
                            <li><strong>Remote Areas & Islands:</strong> 3-5 business days</li>
                        </ul>
                        <p class="mt-4">
                            These are estimated timeframes. Actual delivery may vary due to courier schedules, weather conditions, or other unforeseen circumstances.
                            We cannot guarantee specific delivery dates or times.
                        </p>
                    </div>
                </div>

                <!-- Section 5 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Order Tracking</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Once your order has been shipped, you will receive an email with:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Tracking number</li>
                            <li>Courier company details</li>
                            <li>Link to track your shipment online</li>
                            <li>Estimated delivery date</li>
                        </ul>
                        <p class="mt-4">
                            You can track your package in real-time using the tracking number provided. Please allow 24 hours after shipment
                            for tracking information to become active in the courier's system.
                        </p>
                    </div>
                </div>

                <!-- Section 6 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Delivery Procedures</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            To ensure successful delivery:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Provide accurate and complete delivery address including building name, flat/villa number, and landmarks if applicable</li>
                            <li>Ensure a mobile number is provided for the courier to contact you</li>
                            <li>Someone must be available to receive the delivery and sign for it</li>
                            <li>Valid identification may be required for signature verification</li>
                        </ul>
                        <p class="mt-4">
                            <strong>Failed Delivery Attempts:</strong> If delivery is unsuccessful due to incorrect address, no recipient available,
                            or other customer-related issues, the courier will make up to 2 additional attempts. After 3 failed attempts,
                            the package will be returned to us, and you will be responsible for re-shipping fees.
                        </p>
                    </div>
                </div>

                <!-- Section 7 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Packaging</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We take great care in packaging your fragrances to ensure they arrive in perfect condition:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>All bottles are securely wrapped with protective materials</li>
                            <li>Fragile items are clearly marked on the exterior packaging</li>
                            <li>Boxes are sealed with tamper-evident tape</li>
                            <li>Premium packaging reflects our commitment to luxury service</li>
                        </ul>
                        <p class="mt-4">
                            Please inspect your package upon delivery. If there is visible damage to the exterior packaging, note it with
                            the courier and contact us immediately.
                        </p>
                    </div>
                </div>

                <!-- Section 8 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Damaged or Lost Shipments</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            <strong>Damaged Items:</strong> If your order arrives damaged, please:
                        </p>
                        <ol class="list-decimal list-inside ml-4 space-y-2">
                            <li>Take clear photos of the damaged item and packaging</li>
                            <li>Contact us within 48 hours at <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a></li>
                            <li>Include your order number and description of the damage</li>
                        </ol>
                        <p class="mt-4">
                            We will arrange for a replacement or refund as per our Return & Refund Policy.
                        </p>

                        <p class="mt-6">
                            <strong>Lost Shipments:</strong> If your tracking shows delivered but you haven't received your package:
                        </p>
                        <ol class="list-decimal list-inside ml-4 space-y-2">
                            <li>Check with neighbors, building security, or reception</li>
                            <li>Verify the delivery address on your order confirmation</li>
                            <li>Contact the courier company using your tracking number</li>
                            <li>If unresolved after 24 hours, contact us and we will investigate with the courier</li>
                        </ol>
                    </div>
                </div>

                <!-- Section 9 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Customs & Duties</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            All products sold are already located within the UAE. There are no customs duties or import fees for domestic shipments.
                            All prices displayed include VAT where applicable.
                        </p>
                    </div>
                </div>

                <!-- Section 10 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Shipping Restrictions</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Please note the following restrictions:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>We cannot deliver to PO Boxes - a physical street address is required</li>
                            <li>Certain remote areas may have limited courier access</li>
                            <li>Perfumes are flammable goods and must comply with courier handling regulations</li>
                            <li>We do not ship to mail forwarding services or freight forwarders</li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="mt-12 bg-brand-light p-8">
                    <h3 class="text-[20px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Shipping Questions?</h3>
                    <p class="text-[15px] text-brand-gray mb-4">
                        If you have any questions about shipping or your delivery, please contact us:
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
