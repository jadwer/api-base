<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\Remission;
use Modules\Billing\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * SA-M006: Generate PDF documents for Remissions
 *
 * Creates professional remission PDFs (delivery notes) with company branding,
 * product details, and signature fields for proof of delivery.
 */
class RemissionPDFGenerator
{
    /**
     * Generate PDF for remission
     *
     * @param Remission $remission
     * @param array $options Custom options for PDF generation
     * @return string Path to generated PDF
     */
    public function generate(Remission $remission, array $options = []): string
    {
        $data = $this->prepareData($remission, $options);

        $pdf = Pdf::loadView('sales::remission-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', true);

        $filename = $this->generateFilename($remission);
        $path = "remissions/{$remission->id}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        // Update remission with PDF path
        $remission->update([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ]);

        return $path;
    }

    /**
     * Prepare data for PDF template
     *
     * @param Remission $remission
     * @param array $options
     * @return array
     */
    protected function prepareData(Remission $remission, array $options = []): array
    {
        $company = CompanySetting::getActive();

        // Build company address
        $companyAddress = null;
        if ($company) {
            if ($company->address || $company->city || $company->state) {
                $companyAddress = [
                    'street' => $company->address,
                    'city' => $company->city,
                    'state' => $company->state,
                    'postal_code' => $company->postal_code,
                ];
            } else {
                $companyAddress = $company->additional_settings['address'] ?? null;
            }
        }

        // Load order with contact and addresses
        $order = $remission->salesOrder()->with('contact.contactAddresses')->first();
        $contact = $order?->contact;
        $contactAddress = null;

        if ($contact) {
            $contactAddress = $contact->contactAddresses->where('is_default', true)->first()
                ?? $contact->contactAddresses->first();
        }

        // Effective shipping address (remission override or order shipping)
        $shippingAddress = $remission->shipping_address ?? $order?->shipping_address;

        return [
            'remission' => $remission,
            'items' => $remission->items()->with(['product.unit'])->get(),
            'order' => $order,
            'contact' => $contact,
            'contactAddress' => $contactAddress,
            'shippingAddress' => $shippingAddress,
            'warehouse' => $remission->warehouse,
            'company' => $company,
            'companyAddress' => $companyAddress,
            'companyPhone' => $company?->phone ?? $company?->additional_settings['phone'] ?? null,
            'companyEmail' => $company?->email ?? $company?->additional_settings['email'] ?? null,
            'showSignatureLines' => $options['show_signature_lines'] ?? true,
            'copies' => $options['copies'] ?? 2, // Default: original + copy
        ];
    }

    /**
     * Generate filename for PDF
     *
     * @param Remission $remission
     * @return string
     */
    protected function generateFilename(Remission $remission): string
    {
        $safeNumber = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $remission->remission_number);
        return "remision_{$safeNumber}.pdf";
    }

    /**
     * Download PDF as response
     *
     * @param Remission $remission
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Remission $remission, array $options = [])
    {
        $path = $this->getOrGeneratePath($remission, $options);
        $filename = $this->generateFilename($remission);

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Stream PDF inline (for preview)
     *
     * @param Remission $remission
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(Remission $remission, array $options = [])
    {
        $path = $this->getOrGeneratePath($remission, $options);

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]
        );
    }

    /**
     * Stream PDF directly without storing (for real-time generation)
     *
     * @param Remission $remission
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stream(Remission $remission, array $options = [])
    {
        $data = $this->prepareData($remission, $options);

        return Pdf::loadView('sales::remission-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', true)
            ->stream($this->generateFilename($remission));
    }

    /**
     * Get existing PDF path or generate new one
     *
     * @param Remission $remission
     * @param array $options
     * @return string
     */
    protected function getOrGeneratePath(Remission $remission, array $options = []): string
    {
        $expectedPath = "remissions/{$remission->id}/" . $this->generateFilename($remission);

        // Always regenerate if options are provided or remission was recently updated
        if (!empty($options) || !Storage::disk('public')->exists($expectedPath)) {
            return $this->generate($remission, $options);
        }

        // Check if PDF is older than last remission update
        $pdfLastModified = Storage::disk('public')->lastModified($expectedPath);
        if ($remission->updated_at && $remission->updated_at->timestamp > $pdfLastModified) {
            return $this->generate($remission, $options);
        }

        return $expectedPath;
    }

    /**
     * Regenerate PDF (force new generation)
     *
     * @param Remission $remission
     * @param array $options
     * @return string
     */
    public function regenerate(Remission $remission, array $options = []): string
    {
        return $this->generate($remission, $options);
    }

    /**
     * Delete generated PDF
     *
     * @param Remission $remission
     * @return bool
     */
    public function delete(Remission $remission): bool
    {
        $path = "remissions/{$remission->id}/" . $this->generateFilename($remission);
        return Storage::disk('public')->delete($path);
    }
}
