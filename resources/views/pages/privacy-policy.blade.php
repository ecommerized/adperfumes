@extends('layouts.app')

@section('title', 'Privacy Policy - AD Perfumes')
@section('description', 'Learn how AD Perfumes protects your privacy and handles your personal information.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Legal</p>
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            Privacy Policy
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
                    At AD Perfumes, we are committed to protecting your privacy and ensuring the security of your personal information.
                    This Privacy Policy explains how we collect, use, disclose, and safeguard your data when you visit our website or make a purchase.
                </p>
            </div>

            <div class="space-y-8">
                <!-- Section 1 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Information We Collect</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p><strong>Personal Information:</strong></p>
                        <p>When you place an order or register on our website, we may collect:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Full name</li>
                            <li>Email address</li>
                            <li>Phone number</li>
                            <li>Delivery address</li>
                            <li>Billing address (if different from delivery)</li>
                            <li>Payment information (processed securely by our payment providers)</li>
                        </ul>

                        <p class="mt-6"><strong>Automatically Collected Information:</strong></p>
                        <p>When you visit our website, we may automatically collect:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>IP address</li>
                            <li>Browser type and version</li>
                            <li>Device information</li>
                            <li>Pages visited and time spent on pages</li>
                            <li>Referring website URLs</li>
                            <li>Cookie data and usage patterns</li>
                        </ul>

                        <p class="mt-6"><strong>Marketing Information:</strong></p>
                        <p>With your consent, we may collect:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Newsletter subscription preferences</li>
                            <li>Communication preferences</li>
                            <li>Product interests and shopping behavior</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 2 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">How We Use Your Information</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>We use the information we collect for the following purposes:</p>

                        <p class="mt-4"><strong>Order Processing:</strong></p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Process and fulfill your orders</li>
                            <li>Send order confirmations and shipping notifications</li>
                            <li>Process payments and prevent fraud</li>
                            <li>Provide customer support</li>
                        </ul>

                        <p class="mt-4"><strong>Service Improvement:</strong></p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Improve our website functionality and user experience</li>
                            <li>Analyze shopping trends and preferences</li>
                            <li>Conduct research and analytics</li>
                            <li>Develop new products and services</li>
                        </ul>

                        <p class="mt-4"><strong>Marketing Communications:</strong></p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Send promotional emails about new products, special offers, and exclusive deals (with your consent)</li>
                            <li>Personalize marketing content based on your preferences</li>
                            <li>Conduct surveys and gather feedback</li>
                        </ul>

                        <p class="mt-4"><strong>Legal Compliance:</strong></p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Comply with legal obligations and regulations</li>
                            <li>Protect against fraudulent or illegal activity</li>
                            <li>Enforce our terms and conditions</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 3 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">How We Share Your Information</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>We do not sell your personal information to third parties. We may share your information with:</p>

                        <p class="mt-4"><strong>Service Providers:</strong></p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Payment processors (for secure payment transactions)</li>
                            <li>Shipping and delivery companies (to fulfill orders)</li>
                            <li>Email service providers (for communications)</li>
                            <li>Web hosting and analytics providers</li>
                            <li>Customer support platforms</li>
                        </ul>

                        <p class="mt-4"><strong>Legal Requirements:</strong></p>
                        <p>We may disclose your information if required by law, court order, or government regulation, or if necessary to:</p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Comply with legal processes</li>
                            <li>Protect our rights and property</li>
                            <li>Prevent fraud or security issues</li>
                            <li>Protect the safety of our customers and staff</li>
                        </ul>

                        <p class="mt-4"><strong>Business Transfers:</strong></p>
                        <p>
                            In the event of a merger, acquisition, or sale of assets, your personal information may be transferred to the new owner,
                            subject to the same privacy protections outlined in this policy.
                        </p>
                    </div>
                </div>

                <!-- Section 4 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Cookies and Tracking Technologies</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>We use cookies and similar technologies to enhance your browsing experience:</p>

                        <p class="mt-4"><strong>Essential Cookies:</strong></p>
                        <p>Required for the website to function properly, including shopping cart and checkout processes.</p>

                        <p class="mt-4"><strong>Analytics Cookies:</strong></p>
                        <p>Help us understand how visitors interact with our website through anonymous data collection.</p>

                        <p class="mt-4"><strong>Marketing Cookies:</strong></p>
                        <p>Used to deliver personalized advertisements and track campaign effectiveness.</p>

                        <p class="mt-4">
                            You can control cookies through your browser settings. However, disabling certain cookies may affect your ability
                            to use some features of our website.
                        </p>
                    </div>
                </div>

                <!-- Section 5 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Data Security</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We implement appropriate technical and organizational security measures to protect your personal information:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>SSL encryption for all data transmission</li>
                            <li>Secure payment processing through PCI-compliant providers</li>
                            <li>Regular security audits and updates</li>
                            <li>Restricted access to personal data on a need-to-know basis</li>
                            <li>Employee training on data protection</li>
                        </ul>
                        <p class="mt-4">
                            While we strive to protect your information, no method of transmission over the internet is 100% secure.
                            We cannot guarantee absolute security but are committed to protecting your data to the best of our ability.
                        </p>
                    </div>
                </div>

                <!-- Section 6 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Your Rights and Choices</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>You have the following rights regarding your personal information:</p>

                        <p class="mt-4"><strong>Access and Correction:</strong></p>
                        <p>Request access to the personal information we hold about you and request corrections if it's inaccurate.</p>

                        <p class="mt-4"><strong>Deletion:</strong></p>
                        <p>Request deletion of your personal information, subject to legal obligations to retain certain data.</p>

                        <p class="mt-4"><strong>Marketing Opt-Out:</strong></p>
                        <p>Unsubscribe from marketing emails at any time by clicking the "unsubscribe" link in our emails or contacting us directly.</p>

                        <p class="mt-4"><strong>Data Portability:</strong></p>
                        <p>Request a copy of your data in a structured, commonly used format.</p>

                        <p class="mt-4"><strong>Objection:</strong></p>
                        <p>Object to certain processing of your personal information, particularly for marketing purposes.</p>

                        <p class="mt-4">
                            To exercise any of these rights, please contact us at <a href="mailto:privacy@adperfumes.ae" class="text-brand-primary hover:underline">privacy@adperfumes.ae</a>
                        </p>
                    </div>
                </div>

                <!-- Section 7 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Data Retention</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy,
                            unless a longer retention period is required by law. Generally:
                        </p>
                        <ul class="list-disc list-inside ml-4 space-y-2">
                            <li>Order and transaction data: 7 years (for tax and accounting purposes)</li>
                            <li>Marketing data: Until you opt out or request deletion</li>
                            <li>Account data: Until you request account deletion</li>
                            <li>Analytics data: Anonymized after 26 months</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 8 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Children's Privacy</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Our website and services are not directed to individuals under the age of 18. We do not knowingly collect
                            personal information from children. If you are a parent or guardian and believe we have collected information
                            about a child, please contact us immediately.
                        </p>
                    </div>
                </div>

                <!-- Section 9 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Third-Party Links</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            Our website may contain links to third-party websites. We are not responsible for the privacy practices
                            of these external sites. We encourage you to review the privacy policies of any third-party websites you visit.
                        </p>
                    </div>
                </div>

                <!-- Section 10 -->
                <div>
                    <h2 class="text-[24px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Changes to This Policy</h2>
                    <div class="text-[15px] text-brand-gray leading-relaxed space-y-3">
                        <p>
                            We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements.
                            The "Last Updated" date at the top of this page indicates when this policy was last revised. We encourage you
                            to review this policy periodically.
                        </p>
                        <p>
                            For material changes, we will notify you by email or by posting a prominent notice on our website.
                        </p>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="mt-12 bg-brand-light p-8">
                    <h3 class="text-[20px] font-bold text-brand-dark mb-4 uppercase tracking-luxury">Contact Us About Privacy</h3>
                    <p class="text-[15px] text-brand-gray mb-4">
                        If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:
                    </p>
                    <div class="space-y-2 text-[15px]">
                        <p><strong>Privacy Email:</strong> <a href="mailto:privacy@adperfumes.ae" class="text-brand-primary hover:underline">privacy@adperfumes.ae</a></p>
                        <p><strong>General Email:</strong> <a href="mailto:info@adperfumes.ae" class="text-brand-primary hover:underline">info@adperfumes.ae</a></p>
                        <p><strong>Phone:</strong> <a href="tel:+971501234567" class="text-brand-primary hover:underline">+971 50 123 4567</a></p>
                        <p><strong>Address:</strong> United Arab Emirates</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
