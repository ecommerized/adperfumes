<?php

namespace App\Services;

/**
 * FTA QR Code Service
 *
 * Generates FTA-compliant QR codes for UAE e-invoicing (ZATCA/FATOORA Phase 2).
 *
 * QR Code Format (TLV - Tag Length Value):
 * - Tag 1: Seller Name
 * - Tag 2: VAT Registration Number
 * - Tag 3: Invoice Timestamp (ISO 8601)
 * - Tag 4: Invoice Total (including VAT)
 * - Tag 5: VAT Amount
 *
 * The encoded data is then Base64 encoded for the QR code.
 *
 * Reference: UAE FTA e-Invoicing Technical Specifications
 */
class FtaQrCodeService
{
    /**
     * Generate FTA-compliant QR code data (Base64 encoded TLV).
     *
     * @param string $sellerName Company/seller name
     * @param string $vatNumber VAT registration number (TRN)
     * @param string $timestamp Invoice date/time (ISO 8601 format)
     * @param float $invoiceTotal Total amount including VAT
     * @param float $vatAmount Total VAT amount
     * @return string Base64 encoded TLV data for QR code
     */
    public function generateQrCodeData(
        string $sellerName,
        string $vatNumber,
        string $timestamp,
        float $invoiceTotal,
        float $vatAmount
    ): string {
        $tlvData = '';

        // Tag 1: Seller Name
        $tlvData .= $this->encodeTlv(1, $sellerName);

        // Tag 2: VAT Registration Number
        $tlvData .= $this->encodeTlv(2, $vatNumber);

        // Tag 3: Timestamp (ISO 8601)
        $tlvData .= $this->encodeTlv(3, $timestamp);

        // Tag 4: Invoice Total (including VAT)
        $tlvData .= $this->encodeTlv(4, number_format($invoiceTotal, 2, '.', ''));

        // Tag 5: VAT Amount
        $tlvData .= $this->encodeTlv(5, number_format($vatAmount, 2, '.', ''));

        // Base64 encode the TLV data
        return base64_encode($tlvData);
    }

    /**
     * Encode a single TLV (Tag-Length-Value) entry.
     *
     * Format:
     * - 1 byte: Tag number
     * - 1 byte: Length of value
     * - N bytes: Value
     *
     * @param int $tag Tag number (1-255)
     * @param string $value Value to encode
     * @return string Binary TLV data
     */
    protected function encodeTlv(int $tag, string $value): string
    {
        $length = strlen($value);

        // Convert tag and length to binary bytes
        $tagByte = chr($tag);
        $lengthByte = chr($length);

        return $tagByte . $lengthByte . $value;
    }

    /**
     * Generate QR code SVG for embedding in PDF.
     *
     * @param string $base64Data Base64 encoded TLV data
     * @param int $size QR code size in pixels
     * @return string SVG markup
     */
    public function generateQrCodeSvg(string $base64Data, int $size = 200): string
    {
        // Using simple-qrcode package
        // Install: composer require simplesoftwareio/simple-qrcode
        try {
            return \QrCode::size($size)
                ->format('svg')
                ->generate($base64Data);
        } catch (\Exception $e) {
            // Fallback if package not installed
            return $this->generateFallbackQrCode($base64Data, $size);
        }
    }

    /**
     * Generate QR code PNG data URL for embedding.
     *
     * @param string $base64Data Base64 encoded TLV data
     * @param int $size QR code size in pixels
     * @return string Data URL (data:image/png;base64,...)
     */
    public function generateQrCodeDataUrl(string $base64Data, int $size = 200): string
    {
        try {
            $png = \QrCode::size($size)
                ->format('png')
                ->generate($base64Data);

            return 'data:image/png;base64,' . base64_encode($png);
        } catch (\Exception $e) {
            return $this->generateFallbackDataUrl($base64Data);
        }
    }

    /**
     * Validate QR code data.
     *
     * @param string $base64Data Base64 encoded TLV data
     * @return array Validation result with 'valid' and 'errors' keys
     */
    public function validateQrCodeData(string $base64Data): array
    {
        $errors = [];

        // Decode Base64
        $tlvData = base64_decode($base64Data, true);
        if ($tlvData === false) {
            $errors[] = 'Invalid Base64 encoding';
            return ['valid' => false, 'errors' => $errors];
        }

        // Parse TLV data
        $parsed = $this->parseTlvData($tlvData);

        // Check required tags
        $requiredTags = [1, 2, 3, 4, 5];
        foreach ($requiredTags as $tag) {
            if (!isset($parsed[$tag])) {
                $errors[] = "Missing required tag: {$tag}";
            }
        }

        // Validate VAT number format (UAE TRN: 15 digits)
        if (isset($parsed[2])) {
            $vatNumber = $parsed[2];
            if (!preg_match('/^\d{15}$/', $vatNumber)) {
                $errors[] = "Invalid VAT number format (must be 15 digits)";
            }
        }

        // Validate amounts are numeric
        if (isset($parsed[4]) && !is_numeric($parsed[4])) {
            $errors[] = "Invalid invoice total (must be numeric)";
        }

        if (isset($parsed[5]) && !is_numeric($parsed[5])) {
            $errors[] = "Invalid VAT amount (must be numeric)";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $parsed,
        ];
    }

    /**
     * Parse TLV data back into an array.
     *
     * @param string $tlvData Binary TLV data
     * @return array Associative array with tag => value
     */
    protected function parseTlvData(string $tlvData): array
    {
        $result = [];
        $offset = 0;
        $length = strlen($tlvData);

        while ($offset < $length) {
            // Read tag (1 byte)
            $tag = ord($tlvData[$offset]);
            $offset++;

            // Read length (1 byte)
            if ($offset >= $length) {
                break;
            }
            $valueLength = ord($tlvData[$offset]);
            $offset++;

            // Read value
            if ($offset + $valueLength > $length) {
                break;
            }
            $value = substr($tlvData, $offset, $valueLength);
            $offset += $valueLength;

            $result[$tag] = $value;
        }

        return $result;
    }

    /**
     * Generate fallback QR code if library not available.
     *
     * @param string $data Data to encode
     * @param int $size Size in pixels
     * @return string SVG placeholder
     */
    protected function generateFallbackQrCode(string $data, int $size): string
    {
        return sprintf(
            '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%%" height="100%%" fill="#ffffff"/>
                <text x="50%%" y="50%%" text-anchor="middle" font-size="12" fill="#000000">
                    QR Code: Install simplesoftwareio/simple-qrcode
                </text>
            </svg>',
            $size,
            $size
        );
    }

    /**
     * Generate fallback data URL.
     *
     * @param string $data Data to encode
     * @return string Empty data URL
     */
    protected function generateFallbackDataUrl(string $data): string
    {
        // Return a 1x1 transparent PNG
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    /**
     * Generate complete FTA invoice QR code from invoice data.
     *
     * @param array $invoiceData Invoice data array
     * @return array QR code data with base64, svg, and data_url keys
     */
    public function generateInvoiceQrCode(array $invoiceData): array
    {
        // Extract required fields
        $sellerName = $invoiceData['seller_name'] ?? config('app.name', 'AD Perfumes');
        $vatNumber = $invoiceData['seller_vat_number'] ?? $invoiceData['merchant_trn'] ?? '';
        $timestamp = $invoiceData['created_at'] ?? date('c'); // ISO 8601
        $invoiceTotal = $invoiceData['total'] ?? $invoiceData['total_amount'] ?? 0;
        $vatAmount = $invoiceData['tax_amount'] ?? 0;

        // Generate Base64 TLV data
        $base64Data = $this->generateQrCodeData(
            $sellerName,
            $vatNumber,
            $timestamp,
            $invoiceTotal,
            $vatAmount
        );

        return [
            'base64' => $base64Data,
            'svg' => $this->generateQrCodeSvg($base64Data),
            'data_url' => $this->generateQrCodeDataUrl($base64Data),
        ];
    }

    /**
     * Get QR code tag descriptions for documentation.
     *
     * @return array Tag descriptions
     */
    public function getTagDescriptions(): array
    {
        return [
            1 => 'Seller Name',
            2 => 'VAT Registration Number (TRN)',
            3 => 'Invoice Timestamp (ISO 8601)',
            4 => 'Invoice Total (including VAT)',
            5 => 'VAT Amount',
        ];
    }
}
