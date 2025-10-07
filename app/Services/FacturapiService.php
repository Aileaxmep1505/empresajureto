<?php
namespace App\Services;
use Facturapi\Facturapi;

class FacturapiService {
  private Facturapi $api;
  public function __construct() {
    $this->api = new Facturapi(config('services.facturapi.key'));
  }
  public function createCustomer(array $data){ return $this->api->Customers->create($data); }
  public function createInvoice(array $data){ return $this->api->Invoices->create($data); }
  public function pdf(string $id){ return $this->api->Invoices->download_pdf($id); }
  public function xml(string $id){ return $this->api->Invoices->download_xml($id); }
}
