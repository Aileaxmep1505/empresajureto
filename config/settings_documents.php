<?php

return [
    'legal_docs' => [
        'articles_of_incorporation' => ['name' => 'Acta constitutiva', 'description' => 'Documento base que acredita la constitución formal de la empresa.', 'required' => true, 'max_mb' => 100],
        'assemblies' => ['name' => 'Asambleas', 'description' => 'Asambleas que modifican o actualizan el acta constitutiva.', 'required' => false, 'max_mb' => 100],
        'rfc_registration' => ['name' => 'Inscripción en el Registro Federal de Contribuyentes (RFC)', 'description' => 'Documento que acredita el alta de la empresa ante la SHCP.', 'required' => true, 'max_mb' => 20],
        'tax_status_certificate' => ['name' => 'Constancia de Situación Fiscal', 'description' => 'Emitida por el SAT, con antigüedad no mayor a 3 meses.', 'required' => true, 'max_mb' => 20],
        'sat_compliance' => ['name' => 'Opinión de cumplimiento SAT', 'description' => 'Documento que respalda el cumplimiento de obligaciones fiscales.', 'required' => true, 'max_mb' => 10],
        'imss_compliance' => ['name' => 'Opinión de Cumplimiento de IMSS', 'description' => 'Opinión positiva en materia de seguridad social emitida por el IMSS.', 'required' => false, 'max_mb' => 20],
        'proof_of_address' => ['name' => 'Comprobante de domicilio', 'description' => 'Comprobante reciente asociado al domicilio legal o fiscal.', 'required' => true, 'max_mb' => 20],
    ],
    'additional_docs' => [
        'company_profile' => ['name' => 'Perfil de la empresa (CV)', 'description' => 'Resumen ejecutivo: trayectoria, capacidades y casos de éxito.', 'required' => true, 'max_mb' => 50],
    ],
    'bond_legal' => [
        'murgia_appointment' => ['name' => 'Carta de nombramiento a favor de Murguía Consultores', 'description' => 'Designación formal de Murguía Consultores como tu intermediario.', 'required' => true, 'max_mb' => 20],
    ],
    'bond_tax' => [
        'tax_registration' => ['name' => 'Alta en Hacienda', 'description' => 'Documento de inscripción al RFC.', 'required' => true, 'max_mb' => 20],
        'annual_tax_return' => ['name' => 'Declaración anual del ejercicio inmediato anterior', 'description' => 'Acuse de presentación del ejercicio fiscal anterior.', 'required' => true, 'max_mb' => 30],
    ],
    'bond_financial' => [
        'current_financial_statements' => ['name' => 'Estados financieros parciales del año en curso', 'description' => 'Balance y resultados con antigüedad no mayor a 3 meses.', 'required' => true, 'max_mb' => 50],
        'audited_financial_statements' => ['name' => 'Estados financieros dictaminados al 31 de diciembre', 'description' => 'Ejercicio inmediato anterior, dictaminados por auditor externo.', 'required' => true, 'max_mb' => 50],
    ],
    'bond_receipts' => [
        'proof_of_address' => ['name' => 'Comprobante de domicilio', 'description' => 'Recibo de servicios con antigüedad no mayor a 3 meses.', 'required' => true, 'max_mb' => 20],
    ],
];
