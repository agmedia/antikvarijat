<?php

namespace Tests\Unit;

use App\Models\Front\Checkout\Shipping\Gls;
use RuntimeException;
use Tests\TestCase;

class GlsLabelTest extends TestCase
{
    public function test_it_fetches_the_pdf_for_an_existing_parcel(): void
    {
        config([
            'services.gls.username' => 'gls-user',
            'services.gls.password' => 'gls-password',
            'services.gls.wsdl' => 'https://gls.example.test/ParcelService.svc?singleWsdl',
        ]);

        $gls = new class(['shipping_parcel_id' => 'PARCEL/123']) extends Gls {
            public $request;

            protected function soapClient($wsdl, $soapOptions)
            {
                return new class($this) {
                    private $gls;

                    public function __construct($gls)
                    {
                        $this->gls = $gls;
                    }

                    public function GetPrintedLabels($request)
                    {
                        $this->gls->request = $request;

                        return (object) [
                            'GetPrintedLabelsResult' => (object) [
                                'GetPrintedLabelsErrorList' => [],
                                'Labels' => '%PDF-1.4 test-label',
                            ],
                        ];
                    }
                };
            }
        };

        $label = $gls->label();

        $this->assertSame('%PDF-1.4 test-label', $label['contents']);
        $this->assertSame('gls-PARCEL-123.pdf', $label['filename']);
        $this->assertSame(['PARCEL/123'], data_get($gls->request, 'getPrintedLabelsRequest.ParcelIdList'));
        $this->assertSame('gls-user', data_get($gls->request, 'getPrintedLabelsRequest.Username'));
        $this->assertSame(
            hash('sha512', 'gls-password', true),
            data_get($gls->request, 'getPrintedLabelsRequest.Password')
        );
    }

    public function test_it_rejects_an_order_without_a_parcel_id(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GLS naljepnica još nije dostupna za ovu narudžbu.');

        (new Gls(['shipping_parcel_id' => null]))->label();
    }
}
