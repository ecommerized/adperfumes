<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-conditions',
                'subtitle' => 'Legal',
                'meta_title' => 'Terms & Conditions - AD Perfumes',
                'meta_description' => 'Read the terms and conditions for shopping at AD Perfumes.',
                'content' => $this->termsContent(),
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'subtitle' => 'Legal',
                'meta_title' => 'Privacy Policy - AD Perfumes',
                'meta_description' => 'Learn how AD Perfumes protects your privacy and handles your personal information.',
                'content' => $this->privacyContent(),
            ],
            [
                'title' => 'Return & Refund Policy',
                'slug' => 'return-refund-policy',
                'subtitle' => 'Legal',
                'meta_title' => 'Return & Refund Policy - AD Perfumes',
                'meta_description' => 'Learn about our return and refund policy at AD Perfumes.',
                'content' => $this->returnContent(),
            ],
            [
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'subtitle' => 'Legal',
                'meta_title' => 'Shipping Policy - AD Perfumes',
                'meta_description' => 'Learn about our shipping policy and delivery information for UAE.',
                'content' => $this->shippingContent(),
            ],
            [
                'title' => 'About Us',
                'slug' => 'about',
                'subtitle' => 'Our Story',
                'meta_title' => 'About Us - AD Perfumes',
                'meta_description' => 'Learn about AD Perfumes, your trusted destination for authentic luxury fragrances in the UAE.',
                'content' => $this->aboutContent(),
            ],
            [
                'title' => 'Wholesale Program',
                'slug' => 'wholesale',
                'subtitle' => 'Partnership',
                'meta_title' => 'Wholesale Program - AD Perfumes',
                'meta_description' => 'Join our wholesale program for luxury fragrances. Competitive pricing, authentic products, and dedicated support.',
                'content' => $this->wholesaleContent(),
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }

    private function termsContent(): string
    {
        return <<<'HTML'
<p>Welcome to AD Perfumes. These Terms and Conditions outline the rules and regulations for the use of AD Perfumes' website and services. By accessing this website, we assume you accept these terms and conditions. Do not continue to use adperfumes.ae if you do not agree to all of the terms and conditions stated on this page.</p>

<h2>1. General Terms</h2>
<p>By using this website, you certify that you are at least 18 years of age and that you have the legal capacity to enter into contracts. The terminology "Client," "You," and "Your" refers to you, the person accessing this website. "AD Perfumes," "We," "Our," and "Us" refers to AD Perfumes.</p>
<p>We reserve the right to update, change, or replace any part of these Terms and Conditions by posting updates on our website. It is your responsibility to check this page periodically for changes. Your continued use of or access to the website following the posting of any changes constitutes acceptance of those changes.</p>

<h2>2. Product Information</h2>
<p>All products sold on AD Perfumes are 100% authentic and sourced from authorized distributors. We make every effort to display the colors and images of our products as accurately as possible. However, we cannot guarantee that your computer monitor's display of any color will be accurate.</p>
<p>We reserve the right to limit the quantities of any products or services that we offer. All product descriptions, pricing, and availability are subject to change at any time without notice, at our sole discretion.</p>
<p>We do not warrant that the quality of any products, services, information, or other material purchased or obtained by you will meet your expectations, or that any errors in the service will be corrected.</p>

<h2>3. Pricing &amp; Payment</h2>
<p>All prices are listed in UAE Dirhams (AED) and are inclusive of VAT unless otherwise stated. We reserve the right to change prices without prior notice. However, the price confirmed in your order will be honored.</p>
<p>Payment must be made in full before your order is processed. We accept various payment methods including credit cards, debit cards, and other electronic payment options as displayed during checkout.</p>
<p>In the event of a payment failure or declined transaction, your order will not be processed until payment is successfully completed.</p>

<h2>4. Orders &amp; Acceptance</h2>
<p>Placing an order on our website constitutes an offer to purchase products. All orders are subject to acceptance by us. We reserve the right to refuse or cancel any order for any reason, including but not limited to:</p>
<ul><li>Product availability issues</li><li>Errors in product or pricing information</li><li>Suspected fraudulent transactions</li><li>Payment authorization failures</li></ul>
<p>You will receive an order confirmation email once your order has been placed. This email confirms that we have received your order, but does not constitute acceptance. Acceptance occurs when we dispatch the products to you.</p>

<h2>5. Shipping &amp; Delivery</h2>
<p>We ship to addresses within the United Arab Emirates. Delivery times are estimated and not guaranteed. We are not liable for delays caused by courier services, customs, or other factors beyond our control.</p>
<p>Title and risk of loss for products pass to you upon delivery to the shipping carrier. Please inspect your order upon receipt and contact us immediately if there are any issues.</p>

<h2>6. Returns &amp; Refunds</h2>
<p>Please refer to our Return &amp; Refund Policy for detailed information about returns, exchanges, and refunds.</p>

<h2>7. Prohibited Uses</h2>
<p>You may not use our website for any of the following purposes:</p>
<ul><li>Violating any applicable laws or regulations</li><li>Transmitting any malicious code, viruses, or harmful components</li><li>Attempting to gain unauthorized access to our systems</li><li>Engaging in any form of fraudulent activity</li><li>Reselling products for commercial purposes without authorization</li><li>Using automated systems to scrape or collect data</li></ul>

<h2>8. Intellectual Property</h2>
<p>All content on this website, including but not limited to text, graphics, logos, images, and software, is the property of AD Perfumes or its content suppliers and is protected by international copyright laws.</p>
<p>You may not reproduce, distribute, modify, create derivative works of, publicly display, or exploit any content from this website without our express written permission.</p>

<h2>9. Limitation of Liability</h2>
<p>To the maximum extent permitted by law, AD Perfumes shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation loss of profits, data, or use, arising out of or in any way connected with the use of our website or products.</p>
<p>Our total liability for any claims arising out of or relating to these Terms and Conditions or your use of our website shall not exceed the amount you paid for the product(s) in question.</p>

<h2>10. Governing Law</h2>
<p>These Terms and Conditions shall be governed by and construed in accordance with the laws of the United Arab Emirates. Any disputes arising out of or in connection with these terms shall be subject to the exclusive jurisdiction of the courts of the UAE.</p>

<h2>11. Contact Information</h2>
<p>If you have any questions about these Terms and Conditions, please contact us:</p>
<ul><li>Email: info@adperfumes.ae</li><li>Phone: +971 50 123 4567</li></ul>
HTML;
    }

    private function privacyContent(): string
    {
        return <<<'HTML'
<p>At AD Perfumes, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your data when you visit our website or make a purchase.</p>

<h2>Information We Collect</h2>
<p><strong>Personal Information:</strong></p>
<p>When you place an order or register on our website, we may collect:</p>
<ul><li>Full name</li><li>Email address</li><li>Phone number</li><li>Delivery address</li><li>Billing address (if different from delivery)</li><li>Payment information (processed securely by our payment providers)</li></ul>

<p><strong>Automatically Collected Information:</strong></p>
<p>When you visit our website, we may automatically collect:</p>
<ul><li>IP address</li><li>Browser type and version</li><li>Device information</li><li>Pages visited and time spent on pages</li><li>Referring website URLs</li><li>Cookie data and usage patterns</li></ul>

<p><strong>Marketing Information:</strong></p>
<p>With your consent, we may collect:</p>
<ul><li>Newsletter subscription preferences</li><li>Communication preferences</li><li>Product interests and shopping behavior</li></ul>

<h2>How We Use Your Information</h2>
<p>We use the information we collect for the following purposes:</p>

<p><strong>Order Processing:</strong></p>
<ul><li>Process and fulfill your orders</li><li>Send order confirmations and shipping notifications</li><li>Process payments and prevent fraud</li><li>Provide customer support</li></ul>

<p><strong>Service Improvement:</strong></p>
<ul><li>Improve our website functionality and user experience</li><li>Analyze shopping trends and preferences</li><li>Conduct research and analytics</li><li>Develop new products and services</li></ul>

<p><strong>Marketing Communications:</strong></p>
<ul><li>Send promotional emails about new products, special offers, and exclusive deals (with your consent)</li><li>Personalize marketing content based on your preferences</li><li>Conduct surveys and gather feedback</li></ul>

<p><strong>Legal Compliance:</strong></p>
<ul><li>Comply with legal obligations and regulations</li><li>Protect against fraudulent or illegal activity</li><li>Enforce our terms and conditions</li></ul>

<h2>How We Share Your Information</h2>
<p>We do not sell your personal information to third parties. We may share your information with:</p>

<p><strong>Service Providers:</strong></p>
<ul><li>Payment processors (for secure payment transactions)</li><li>Shipping and delivery companies (to fulfill orders)</li><li>Email service providers (for communications)</li><li>Web hosting and analytics providers</li><li>Customer support platforms</li></ul>

<p><strong>Legal Requirements:</strong></p>
<p>We may disclose your information if required by law, court order, or government regulation, or if necessary to:</p>
<ul><li>Comply with legal processes</li><li>Protect our rights and property</li><li>Prevent fraud or security issues</li><li>Protect the safety of our customers and staff</li></ul>

<p><strong>Business Transfers:</strong></p>
<p>In the event of a merger, acquisition, or sale of assets, your personal information may be transferred to the new owner, subject to the same privacy protections outlined in this policy.</p>

<h2>Cookies and Tracking Technologies</h2>
<p>We use cookies and similar technologies to enhance your browsing experience:</p>
<p><strong>Essential Cookies:</strong> Required for the website to function properly, including shopping cart and checkout processes.</p>
<p><strong>Analytics Cookies:</strong> Help us understand how visitors interact with our website through anonymous data collection.</p>
<p><strong>Marketing Cookies:</strong> Used to deliver personalized advertisements and track campaign effectiveness.</p>
<p>You can control cookies through your browser settings. However, disabling certain cookies may affect your ability to use some features of our website.</p>

<h2>Data Security</h2>
<p>We implement appropriate technical and organizational security measures to protect your personal information:</p>
<ul><li>SSL encryption for all data transmission</li><li>Secure payment processing through PCI-compliant providers</li><li>Regular security audits and updates</li><li>Restricted access to personal data on a need-to-know basis</li><li>Employee training on data protection</li></ul>
<p>While we strive to protect your information, no method of transmission over the internet is 100% secure. We cannot guarantee absolute security but are committed to protecting your data to the best of our ability.</p>

<h2>Your Rights and Choices</h2>
<p>You have the following rights regarding your personal information:</p>
<p><strong>Access and Correction:</strong> Request access to the personal information we hold about you and request corrections if it's inaccurate.</p>
<p><strong>Deletion:</strong> Request deletion of your personal information, subject to legal obligations to retain certain data.</p>
<p><strong>Marketing Opt-Out:</strong> Unsubscribe from marketing emails at any time by clicking the "unsubscribe" link in our emails or contacting us directly.</p>
<p><strong>Data Portability:</strong> Request a copy of your data in a structured, commonly used format.</p>
<p><strong>Objection:</strong> Object to certain processing of your personal information, particularly for marketing purposes.</p>
<p>To exercise any of these rights, please contact us at privacy@adperfumes.ae</p>

<h2>Data Retention</h2>
<p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy, unless a longer retention period is required by law. Generally:</p>
<ul><li>Order and transaction data: 7 years (for tax and accounting purposes)</li><li>Marketing data: Until you opt out or request deletion</li><li>Account data: Until you request account deletion</li><li>Analytics data: Anonymized after 26 months</li></ul>

<h2>Children's Privacy</h2>
<p>Our website and services are not directed to individuals under the age of 18. We do not knowingly collect personal information from children. If you are a parent or guardian and believe we have collected information about a child, please contact us immediately.</p>

<h2>Third-Party Links</h2>
<p>Our website may contain links to third-party websites. We are not responsible for the privacy practices of these external sites. We encourage you to review the privacy policies of any third-party websites you visit.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. The "Last Updated" date at the top of this page indicates when this policy was last revised. We encourage you to review this policy periodically.</p>
<p>For material changes, we will notify you by email or by posting a prominent notice on our website.</p>

<h2>Contact Us About Privacy</h2>
<p>If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
<ul><li><strong>Privacy Email:</strong> privacy@adperfumes.ae</li><li><strong>General Email:</strong> info@adperfumes.ae</li><li><strong>Phone:</strong> +971 50 123 4567</li><li><strong>Address:</strong> United Arab Emirates</li></ul>
HTML;
    }

    private function returnContent(): string
    {
        return <<<'HTML'
<p>At AD Perfumes, we want you to be completely satisfied with your purchase. If you're not entirely happy with your order, we're here to help. Please read our return and refund policy carefully.</p>

<h2>Return Eligibility</h2>
<p>To be eligible for a return, your item must meet the following conditions:</p>
<ul><li>The product must be unused and in the same condition that you received it</li><li>The product must be in its original packaging with all seals intact</li><li>The return must be initiated within 14 days of receiving the product</li><li>Proof of purchase (order confirmation email or receipt) must be provided</li><li>The product must not be a specially ordered or customized item</li></ul>
<p><strong>Important:</strong> Due to hygiene reasons, we cannot accept returns on opened or used fragrances. All perfume bottles must have their original seals intact.</p>

<h2>Non-Returnable Items</h2>
<p>The following items cannot be returned:</p>
<ul><li>Opened or used fragrances (for hygiene and safety reasons)</li><li>Products with broken seals or packaging</li><li>Sale or clearance items marked as final sale</li><li>Gift cards or promotional items</li><li>Special orders or custom engraved items</li></ul>

<h2>Return Process</h2>
<p>To initiate a return, please follow these steps:</p>
<ol><li><strong>Contact Us:</strong> Email us at info@adperfumes.ae within 14 days of receiving your order. Include your order number, product details, and reason for return.</li><li><strong>Return Authorization:</strong> Our team will review your request and provide you with a Return Authorization (RA) number and return instructions within 1-2 business days.</li><li><strong>Package Your Return:</strong> Securely pack the item in its original packaging with all accessories and documentation. Include a copy of your order confirmation and the RA number.</li><li><strong>Ship the Return:</strong> Send the package using a trackable shipping method to the address provided. You are responsible for return shipping costs unless the return is due to our error.</li></ol>
<p><strong>Note:</strong> Returns sent without prior authorization may not be accepted. Please wait for confirmation and your RA number before shipping.</p>

<h2>Refund Process</h2>
<p>Once we receive your return, our team will inspect it to ensure it meets our return policy criteria. If approved, your refund will be processed as follows:</p>
<ul><li>Refund inspection typically takes 2-3 business days after receipt</li><li>Approved refunds will be credited to your original payment method</li><li>Refund processing time: 5-10 business days depending on your bank or payment provider</li><li>You will receive an email confirmation once your refund has been processed</li></ul>
<p><strong>Refund Amount:</strong> The refund will be for the product price only. Original shipping fees are non-refundable, unless the return is due to a defective product or our error. Return shipping costs are the customer's responsibility.</p>

<h2>Exchanges</h2>
<p>We currently do not offer direct exchanges. If you would like a different product, please return the original item for a refund and place a new order for the desired product.</p>
<p>This ensures you receive your preferred item as quickly as possible without waiting for the return processing.</p>

<h2>Damaged or Defective Products</h2>
<p>If you receive a damaged, defective, or incorrect product, please contact us immediately:</p>
<ul><li>Email us within 48 hours of delivery at info@adperfumes.ae</li><li>Include clear photos of the damage or defect</li><li>Provide your order number and description of the issue</li></ul>
<p>We will arrange for a replacement or full refund, including return shipping costs. In cases of damaged shipments, we may file a claim with the courier on your behalf.</p>

<h2>Late or Missing Refunds</h2>
<p>If you haven't received your refund after the expected processing time:</p>
<ol><li>Check your bank account or credit card statement again</li><li>Contact your bank or credit card company - it may take time for the refund to post</li><li>Contact your payment provider's customer service</li><li>If you've done all of this and still have not received your refund, contact us at info@adperfumes.ae</li></ol>

<h2>Cancellations</h2>
<p>You may cancel your order before it has been dispatched. Once an order has shipped, you will need to follow the standard return process outlined above.</p>
<p>To cancel an order, please contact us immediately at info@adperfumes.ae with your order number. If the order has not been processed, we will issue a full refund.</p>

<h2>Questions About Returns?</h2>
<p>If you have any questions about our return and refund policy, please don't hesitate to contact us:</p>
<ul><li><strong>Email:</strong> info@adperfumes.ae</li><li><strong>Phone:</strong> +971 50 123 4567</li><li><strong>Business Hours:</strong> Saturday - Thursday, 9:00 AM - 6:00 PM (UAE Time)</li></ul>
HTML;
    }

    private function shippingContent(): string
    {
        return <<<'HTML'
<p>At AD Perfumes, we strive to deliver your luxury fragrances safely and efficiently across the United Arab Emirates. Please review our shipping policy for important information about delivery times, costs, and procedures.</p>

<h2>Shipping Coverage</h2>
<p>We currently ship to all Emirates within the United Arab Emirates:</p>
<ul><li>Dubai</li><li>Abu Dhabi</li><li>Sharjah</li><li>Ajman</li><li>Ras Al Khaimah</li><li>Fujairah</li><li>Umm Al Quwain</li></ul>
<p>At this time, we do not offer international shipping. All orders must have a valid UAE delivery address.</p>

<h2>Shipping Costs</h2>
<p><strong>FREE SHIPPING ON ORDERS OVER AED 300</strong></p>
<p>We offer complimentary standard shipping on all orders totaling AED 300 or more.</p>
<p>For orders under AED 300:</p>
<ul><li><strong>Dubai &amp; Northern Emirates:</strong> AED 25</li><li><strong>Abu Dhabi &amp; Al Ain:</strong> AED 30</li><li><strong>Remote Areas:</strong> AED 40</li></ul>
<p>Shipping costs are calculated automatically at checkout based on your delivery location.</p>

<h2>Processing Time</h2>
<p>All orders are processed within 1-2 business days (Saturday - Thursday, excluding UAE public holidays). Orders placed on Fridays or public holidays will be processed the next business day.</p>
<p>You will receive an email confirmation once your order has been placed, and another email with tracking information once your order has been dispatched.</p>
<p><strong>Note:</strong> Processing time does not include delivery time. Please refer to the Delivery Timeframes section below.</p>

<h2>Delivery Timeframes</h2>
<p>Standard delivery times after order dispatch:</p>
<ul><li><strong>Dubai:</strong> 1-2 business days</li><li><strong>Sharjah, Ajman, Abu Dhabi:</strong> 2-3 business days</li><li><strong>Northern Emirates (RAK, Fujairah, UAQ):</strong> 2-4 business days</li><li><strong>Remote Areas &amp; Islands:</strong> 3-5 business days</li></ul>
<p>These are estimated timeframes. Actual delivery may vary due to courier schedules, weather conditions, or other unforeseen circumstances. We cannot guarantee specific delivery dates or times.</p>

<h2>Order Tracking</h2>
<p>Once your order has been shipped, you will receive an email with:</p>
<ul><li>Tracking number</li><li>Courier company details</li><li>Link to track your shipment online</li><li>Estimated delivery date</li></ul>
<p>You can track your package in real-time using the tracking number provided. Please allow 24 hours after shipment for tracking information to become active in the courier's system.</p>

<h2>Delivery Procedures</h2>
<p>To ensure successful delivery:</p>
<ul><li>Provide accurate and complete delivery address including building name, flat/villa number, and landmarks if applicable</li><li>Ensure a mobile number is provided for the courier to contact you</li><li>Someone must be available to receive the delivery and sign for it</li><li>Valid identification may be required for signature verification</li></ul>
<p><strong>Failed Delivery Attempts:</strong> If delivery is unsuccessful due to incorrect address, no recipient available, or other customer-related issues, the courier will make up to 2 additional attempts. After 3 failed attempts, the package will be returned to us, and you will be responsible for re-shipping fees.</p>

<h2>Packaging</h2>
<p>We take great care in packaging your fragrances to ensure they arrive in perfect condition:</p>
<ul><li>All bottles are securely wrapped with protective materials</li><li>Fragile items are clearly marked on the exterior packaging</li><li>Boxes are sealed with tamper-evident tape</li><li>Premium packaging reflects our commitment to luxury service</li></ul>
<p>Please inspect your package upon delivery. If there is visible damage to the exterior packaging, note it with the courier and contact us immediately.</p>

<h2>Damaged or Lost Shipments</h2>
<p><strong>Damaged Items:</strong> If your order arrives damaged, please:</p>
<ol><li>Take clear photos of the damaged item and packaging</li><li>Contact us within 48 hours at info@adperfumes.ae</li><li>Include your order number and description of the damage</li></ol>
<p>We will arrange for a replacement or refund as per our Return &amp; Refund Policy.</p>
<p><strong>Lost Shipments:</strong> If your tracking shows delivered but you haven't received your package:</p>
<ol><li>Check with neighbors, building security, or reception</li><li>Verify the delivery address on your order confirmation</li><li>Contact the courier company using your tracking number</li><li>If unresolved after 24 hours, contact us and we will investigate with the courier</li></ol>

<h2>Customs &amp; Duties</h2>
<p>All products sold are already located within the UAE. There are no customs duties or import fees for domestic shipments. All prices displayed include VAT where applicable.</p>

<h2>Shipping Restrictions</h2>
<p>Please note the following restrictions:</p>
<ul><li>We cannot deliver to PO Boxes - a physical street address is required</li><li>Certain remote areas may have limited courier access</li><li>Perfumes are flammable goods and must comply with courier handling regulations</li><li>We do not ship to mail forwarding services or freight forwarders</li></ul>

<h2>Shipping Questions?</h2>
<p>If you have any questions about shipping or your delivery, please contact us:</p>
<ul><li><strong>Email:</strong> info@adperfumes.ae</li><li><strong>Phone:</strong> +971 50 123 4567</li><li><strong>Business Hours:</strong> Saturday - Thursday, 9:00 AM - 6:00 PM (UAE Time)</li></ul>
HTML;
    }

    private function aboutContent(): string
    {
        return <<<'HTML'
<h2>The Art of Curation</h2>
<p>AD Perfumes was founded with a singular vision: to bring the world's finest luxury fragrances to discerning customers across the United Arab Emirates. What started as a passion for exceptional scents has grown into one of the region's most trusted destinations for authentic perfumes.</p>
<p>Our team of fragrance experts carefully curates each product in our collection, selecting only from authorized distributors and renowned perfume houses. From iconic designer fragrances to exclusive niche creations, every bottle tells a story.</p>
<p>We believe that finding the perfect fragrance is a deeply personal journey. That's why we go beyond simply selling perfumes — we provide expert guidance, detailed scent profiles, and personalized recommendations to help you discover your signature scent.</p>

<h2>Our Values</h2>
<p><strong>100% Authenticity:</strong> Every product we sell is genuine and sourced directly from authorized distributors. We guarantee the authenticity of every fragrance in our collection.</p>
<p><strong>Expert Guidance:</strong> Our fragrance specialists are passionate about perfumery and dedicated to helping you find the perfect scent. We provide personalized recommendations based on your preferences.</p>
<p><strong>Luxury Experience:</strong> From browsing to unboxing, we ensure a premium experience at every touchpoint. Our commitment to excellence extends to our packaging, delivery, and customer service.</p>

<h2>Why Choose AD Perfumes</h2>
<ul><li><strong>Extensive Collection:</strong> Thousands of fragrances from 100+ brands, including both designer and niche perfume houses</li><li><strong>Competitive Pricing:</strong> The best prices in the UAE with regular promotions and exclusive deals</li><li><strong>Fast UAE Delivery:</strong> Free shipping on orders over AED 300 with delivery across all 7 Emirates</li><li><strong>Easy Returns:</strong> Hassle-free returns within 14 days for unopened products</li></ul>
HTML;
    }

    private function wholesaleContent(): string
    {
        return <<<'HTML'
<h2>Partnership Opportunities</h2>
<p>AD Perfumes offers an exclusive wholesale program for businesses looking to stock authentic luxury fragrances. Whether you operate a retail store, boutique, e-commerce platform, or hospitality business, we have competitive wholesale pricing tailored to your needs.</p>

<h2>Wholesale Benefits</h2>
<ul><li><strong>Competitive Pricing:</strong> 15-40% discounts off retail prices depending on your tier</li><li><strong>100% Authentic Products:</strong> All products sourced from authorized distributors</li><li><strong>Wide Selection:</strong> Access to 12,000+ fragrances from 100+ brands</li><li><strong>Fast Fulfillment:</strong> Same-day dispatch for orders placed before 2 PM</li><li><strong>Flexible Payment:</strong> Net 30/60 payment terms available for established partners</li><li><strong>Dedicated Support:</strong> Personal account manager assigned to your business</li></ul>

<h2>Requirements</h2>
<p>To qualify for our wholesale program, you must meet the following criteria:</p>
<ul><li>Valid UAE business/trade license</li><li>Minimum initial order of AED 5,000 (subsequent orders: AED 2,000 minimum)</li><li>Business type: retail store, boutique, e-commerce, hotel, spa, or authorized reseller</li><li>Valid VAT/TRN registration</li></ul>

<h2>Pricing Tiers</h2>
<p><strong>Silver Tier (AED 2,000 - 9,999):</strong> 15-20% off retail prices</p>
<p><strong>Gold Tier (AED 10,000 - 24,999):</strong> 20-30% off retail prices</p>
<p><strong>Platinum Tier (AED 25,000+):</strong> 30-40% off retail prices</p>

<h2>Frequently Asked Questions</h2>
<p><strong>How long does the approval process take?</strong><br>We review wholesale applications within 2-3 business days. You will receive an email with your account details and wholesale pricing once approved.</p>
<p><strong>What payment methods do you accept?</strong><br>We accept bank transfers, credit cards, and cheques for wholesale orders. Net 30/60 terms are available for established partners with a proven track record.</p>
<p><strong>Do you offer dropshipping?</strong><br>Yes, we offer dropshipping services for approved wholesale partners. Products are shipped directly to your customers in neutral packaging.</p>
<p><strong>What is your return policy for wholesale orders?</strong><br>Wholesale returns are accepted within 30 days for unopened, undamaged products. A restocking fee of 10% may apply.</p>
<p><strong>Do you provide marketing materials?</strong><br>Yes, we provide high-resolution product images, descriptions, and marketing materials to help you promote our products.</p>

<h2>Get Started</h2>
<p>Ready to become a wholesale partner? Contact us at <strong>info@adperfumes.ae</strong> or call <strong>+971 50 123 4567</strong> to discuss your wholesale requirements.</p>
HTML;
    }
}
