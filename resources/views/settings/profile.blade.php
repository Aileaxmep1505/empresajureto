@extends('layouts.app')

@section('title', 'Configuración')
@section('content_class', 'content--flush')

@section('content')
@php
    $activeSection = request('section', 'profile');

    $validSections = [
        'profile',
        'security',
        'identity',
        'legal_docs',
        'additional_docs',
        'certifications',
        'bond_legal',
        'bond_tax',
        'bond_financial',
        'bond_receipts',
        'bond_affiliation',
        'bond_special_cases',
    ];

    if (! in_array($activeSection, $validSections, true)) {
        $activeSection = 'profile';
    }

    $user = $user ?? auth()->user();
    $firstName = old('name', $profile->first_name ?: str($user->name)->beforeLast(' ')->value());
    $lastName = old('last_name', $profile->last_name ?: str($user->name)->afterLast(' ')->value());

    $baseSettingsUrl = route('settings.profile');
    $profileUrl = $baseSettingsUrl . '?section=profile';
    $securityUrl = $baseSettingsUrl . '?section=security';
    $identityUrl = $baseSettingsUrl . '?section=identity';
    $legalDocsUrl = $baseSettingsUrl . '?section=legal_docs';
    $additionalDocsUrl = $baseSettingsUrl . '?section=additional_docs';
    $certificationsUrl = $baseSettingsUrl . '?section=certifications';
    $bondLegalUrl = $baseSettingsUrl . '?section=bond_legal';
    $bondTaxUrl = $baseSettingsUrl . '?section=bond_tax';
    $bondFinancialUrl = $baseSettingsUrl . '?section=bond_financial';
    $bondReceiptsUrl = $baseSettingsUrl . '?section=bond_receipts';
    $bondAffiliationUrl = $baseSettingsUrl . '?section=bond_affiliation';
    $bondSpecialCasesUrl = $baseSettingsUrl . '?section=bond_special_cases';

    $pageTitle = match ($activeSection) {
        'security' => 'Seguridad', 'identity' => 'Identidad', 'legal_docs' => 'Documentos legales',
        'additional_docs' => 'Documentos adicionales', 'certifications' => 'Certificaciones',
        'bond_legal' => 'Información legal y corporativa', 'bond_tax' => 'Información fiscal',
        'bond_financial' => 'Información financiera', 'bond_receipts' => 'Comprobantes y vigencias',
        'bond_affiliation' => 'Documentación de afianzadora', 'bond_special_cases' => 'Casos especiales',
        default => 'Mi perfil',
    };
    $isOrganizationOpen = in_array($activeSection, ['identity','legal_docs','additional_docs','certifications'], true);
    $isBondsOpen = in_array($activeSection, ['bond_legal','bond_tax','bond_financial','bond_receipts','bond_affiliation','bond_special_cases'], true);

    $requiredSections = 7;
    $completedSections = 0;
    $completedSections += filled($profile->first_name) && filled($profile->last_name) ? 1 : 0;
    $completedSections += filled($organization->legal_name) && filled($organization->tax_id) ? 1 : 0;
    foreach (['legal_docs','bond_legal','bond_tax','bond_financial','bond_receipts'] as $sectionName) {
        $defs = collect($documentDefinitions[$sectionName] ?? [])->filter(fn ($d) => $d['required'] ?? false);
        if ($defs->isNotEmpty() && $defs->every(fn ($d, $k) => $documents->has($sectionName.'.'.$k))) $completedSections++;
    }
    $completedSections = min($completedSections, $requiredSections);
    $progressPercent = (string) round(($completedSections / $requiredSections) * 100) . '%';
    $progressText = $completedSections . ' de ' . $requiredSections . ' secciones requeridas';
@endphp

<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  body {
    background: var(--bg);
  }

  .settings-page,
  .settings-page * {
    box-sizing: border-box;
  }

  .settings-page {
    min-height: calc(100vh - 64px);
    padding: 0;
    color: var(--ink);
    font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .settings-topbar {
    height: 64px;
    padding: 0 28px;
    background: #ffffff;
    border-bottom: 1px solid var(--line);
    box-shadow: 0 4px 14px rgba(0,0,0,.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    position: sticky;
    top: 0;
    z-index: 20;
  }

  .settings-topbar-kicker {
    margin: 0 0 4px;
    color: #777777;
    font-size: 11px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
  }

  .settings-topbar-title {
    display: flex;
    align-items: baseline;
    gap: 8px;
    min-width: 0;
  }

  .settings-topbar-title h1 {
    margin: 0;
    color: #111111;
    font-size: 16px;
    line-height: 1.15;
    font-weight: 700;
  }

  .settings-topbar-title p {
    margin: 0;
    color: #666666;
    font-size: 13px;
    line-height: 1.3;
    font-weight: 500;
  }

  .settings-dashboard-btn {
    min-height: 36px;
    padding: 0 16px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: #333333;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    line-height: 1;
    font-weight: 700;
    white-space: nowrap;
    transition: background .16s ease, border-color .16s ease, transform .16s ease;
  }

  .settings-dashboard-btn:hover {
    background: #f9fafb;
    border-color: #dedede;
  }

  .settings-dashboard-btn:active {
    transform: scale(.98);
  }

  .settings-wrap {
    width: min(100%, 1480px);
    margin: 0 auto;
    padding: 26px 28px 36px;
  }

  .settings-summary {
    min-height: 68px;
    padding: 18px 22px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--card);
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 18px;
    align-items: center;
    margin-bottom: 14px;
  }

  .settings-summary h2 {
    margin: 0 0 4px;
    color: #111111;
    font-size: 16px;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: .02em;
    text-transform: uppercase;
  }

  .settings-summary p {
    margin: 0;
    color: #666666;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 500;
  }

  .settings-progress-info {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
  }

  .settings-progress-text {
    color: #111111;
    font-size: 12px;
    line-height: 1;
    font-weight: 700;
    white-space: nowrap;
  }

  .settings-progress-percent {
    color: #777777;
    font-size: 12px;
    line-height: 1;
    font-weight: 600;
    white-space: nowrap;
  }

  .settings-progress-track {
    width: 210px;
    height: 6px;
    border-radius: 999px;
    background: #f1f1f1;
    overflow: hidden;
  }

  .settings-progress-bar {
    width: {{ $progressPercent }};
    height: 100%;
    border-radius: inherit;
    background: var(--blue);
  }

  .settings-layout {
    display: grid;
    grid-template-columns: 270px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
  }

  .settings-sidebar {
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--card);
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    overflow: hidden;
  }

  .settings-nav-section {
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
  }

  .settings-nav-section:last-child {
    border-bottom: 0;
  }

  .settings-sidebar summary {
    list-style: none;
  }

  .settings-sidebar summary::-webkit-details-marker {
    display: none;
  }

  .settings-nav-head {
    width: 100%;
    height: 30px;
    border: 0;
    background: transparent;
    color: #777777;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 0 10px;
    font-size: 12px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    cursor: pointer;
  }

  .settings-nav-head span {
    display: inline-flex;
    align-items: center;
    gap: 9px;
  }

  .settings-nav-head svg {
    width: 16px;
    height: 16px;
    color: #777777;
  }

  .settings-chevron {
    transition: transform .16s ease;
  }

  .settings-sidebar details[open] .settings-chevron {
    transform: rotate(90deg);
  }

  .settings-subnav {
    padding: 0 0 10px;
  }

  .settings-nav-link {
    width: 100%;
    min-height: 42px;
    margin-top: 4px;
    padding: 0 12px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #555555;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 11px;
    font-size: 14px;
    line-height: 1;
    font-weight: 600;
    position: relative;
    cursor: pointer;
    transition: background .16s ease, color .16s ease;
  }

  .settings-nav-link svg {
    width: 17px;
    height: 17px;
    color: #737373;
    flex: 0 0 auto;
  }

  .settings-nav-link.is-active {
    background: var(--blue-soft);
    color: var(--blue);
    font-weight: 700;
  }

  .settings-nav-link.is-active svg {
    color: var(--blue);
  }

  .settings-status-dot {
    width: 13px;
    height: 13px;
    margin-left: auto;
    border-radius: 999px;
    flex: 0 0 auto;
  }

  .settings-status-dot.is-solid-green {
    background: #16a34a;
  }

  .settings-status-dot.is-dashed-orange {
    background: transparent;
    border: 1.5px dashed #f59e0b;
  }

  .settings-status-dot.is-gray-line {
    width: 12px;
    height: 2px;
    border-radius: 999px;
    background: #cfcfcf;
  }

  .settings-status-dot.is-empty {
    background: #ffffff;
    border: 2px solid #bdbdbd;
  }

  .settings-main {
    min-width: 0;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--card);
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .settings-main-head {
    min-height: 96px;
    padding: 22px 26px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .settings-head-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #f7f7f8;
    color: #111111;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
  }

  .settings-head-icon svg {
    width: 21px;
    height: 21px;
  }

  .settings-main-head h3 {
    margin: 0 0 4px;
    color: #111111;
    font-size: 20px;
    line-height: 1.15;
    font-weight: 700;
    letter-spacing: -.02em;
  }

  .settings-main-head p {
    margin: 0;
    color: #777777;
    font-size: 13px;
    line-height: 1.2;
    font-weight: 500;
  }

  .settings-form-scroll {
    flex: 1;
    max-height: calc(100vh - 350px);
    overflow-y: auto;
    padding: 26px 28px;
  }

  .settings-form-scroll::-webkit-scrollbar {
    width: 8px;
  }

  .settings-form-scroll::-webkit-scrollbar-track {
    background: transparent;
  }

  .settings-form-scroll::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 999px;
    border: 2px solid #ffffff;
  }

  .settings-card {
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,.01);
    padding: 26px 28px;
    margin-bottom: 16px;
    position: relative;
  }

  .settings-card-status {
    position: absolute;
    top: 30px;
    right: 28px;
    width: 14px;
    height: 14px;
    border-radius: 999px;
  }

  .settings-card-status.is-solid-green {
    background: #16a34a;
  }

  .settings-card-status.is-empty {
    background: #ffffff;
    border: 2px solid #cfcfcf;
  }

  .settings-card-status.is-dashed-orange {
    background: #ffffff;
    border: 2px dashed #f59e0b;
  }

  .settings-card-status.is-gray-line {
    width: 12px;
    height: 2px;
    top: 38px;
    right: 28px;
    border-radius: 999px;
    background: #cfcfcf;
  }

  .settings-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    padding-right: 30px;
    flex-wrap: wrap;
  }

  .settings-card-header h4 {
    margin: 0;
    color: #111111;
    font-size: 16px;
    line-height: 1.2;
    font-weight: 700;
  }

  .settings-card-desc {
    margin: 0 0 24px;
    color: #777777;
    font-size: 14px;
    line-height: 1.4;
    font-weight: 500;
  }

  .settings-badge,
  .legal-pill,
  .bond-pill,
  .cert-pill {
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    font-size: 11px;
    line-height: 1;
    font-weight: 700;
    white-space: nowrap;
    text-transform: none;
  }

  .legal-pill.is-ok,
  .bond-pill.is-ok {
    background: var(--success-soft);
    color: var(--success);
    border: 1px solid #86efac;
  }

  .legal-pill.is-pending,
  .bond-pill.is-pending {
    background: #ffffff;
    color: #777777;
    border: 1px solid #d4d4d8;
  }

  .legal-pill.is-version {
    background: #ffffff;
    color: #777777;
    border: 1px solid #e5e7eb;
  }

  .bond-pill.is-required {
    background: #f5f5f5;
    color: #333333;
    text-transform: uppercase;
  }

  .bond-pill.is-linked {
    background: var(--blue-soft);
    color: var(--blue);
    border: 1px solid #9cc6ff;
    text-transform: uppercase;
  }

  .bond-pill.is-warning {
    background: #fff7ed;
    color: #f97316;
    border: 1px solid #fed7aa;
  }

  .settings-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .settings-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
  }

  .settings-grid-full {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .settings-field {
    margin-bottom: 0;
    position: relative;
  }

  .settings-field label {
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 6px;
    color: #222222;
    font-size: 14px;
    line-height: 1;
    font-weight: 700;
  }

  .settings-required {
    color: var(--danger);
  }

  .settings-info {
    width: 14px;
    height: 14px;
    border: 1px solid #9ca3af;
    border-radius: 50%;
    color: #737373;
    display: inline-grid;
    place-items: center;
    font-size: 9px;
    line-height: 1;
    font-weight: 700;
    flex: 0 0 auto;
  }

  .settings-field input,
  .settings-field select,
  .settings-field textarea {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: #333333;
    padding: 0 14px;
    outline: none;
    font-size: 14px;
    line-height: 1;
    font-weight: 500;
    font-family: inherit;
    transition: border-color .16s ease, box-shadow .16s ease;
  }

  .settings-field input,
  .settings-field select {
    height: 44px;
  }

  .settings-field textarea {
    min-height: 104px;
    padding: 14px;
    resize: vertical;
    line-height: 1.45;
  }

  .settings-field select {
    appearance: none;
    background-image: linear-gradient(45deg, transparent 50%, #888 50%), linear-gradient(135deg, #888 50%, transparent 50%);
    background-position: calc(100% - 18px) 19px, calc(100% - 12px) 19px;
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 38px;
  }

  .settings-field input:focus,
  .settings-field select:focus,
  .settings-field textarea:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .settings-help {
    margin: 8px 0 0;
    color: #777777;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 500;
  }

  .settings-divider {
    height: 1px;
    background: var(--line);
    margin: 20px 0;
  }

  .settings-card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  .settings-main-footer {
    padding: 16px 28px;
    border-top: 1px solid var(--line);
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .settings-footer-note {
    color: #777777;
    font-size: 13px;
    font-weight: 500;
  }

  .settings-main-footer-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
  }

  .settings-btn {
    min-height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    border: 0;
    font-family: inherit;
    font-size: 13px;
    line-height: 1;
    font-weight: 700;
    cursor: pointer;
    transition: background .16s ease, border-color .16s ease, transform .16s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    white-space: nowrap;
  }

  .settings-btn:active {
    transform: scale(.98);
  }

  .settings-btn[disabled] {
    pointer-events: none;
  }

  .settings-btn-ghost {
    background: #ffffff;
    color: #555555;
    border: 1px solid #e2e8f0;
  }

  .settings-btn-ghost:hover {
    background: #f8fafc;
  }

  .settings-btn-blue-soft {
    background: #7fb0ff;
    color: #ffffff;
  }

  .settings-btn-blue-soft:hover {
    background: #6a9df5;
  }

  .settings-btn-primary {
    background: #0b70f0;
    color: #ffffff;
  }

  .settings-btn-primary:hover {
    background: #0064d9;
  }

  .settings-btn-outline {
    background: #ffffff;
    color: #333333;
    border: 1px solid #e5e7eb;
  }

  .settings-btn-outline:hover {
    background: #f9fafb;
  }

  .settings-btn-danger-text {
    background: transparent;
    color: #777777;
    border: 0;
    padding-inline: 6px;
  }

  .settings-btn-danger-text:hover {
    color: #ef4444;
    background: #fff5f5;
  }

  .legal-overview,
  .bond-progress-card {
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 20px;
    background: #ffffff;
  }

  .legal-overview-row,
  .bond-progress-row {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 10px;
    color: #111111;
    font-size: 13px;
    font-weight: 700;
  }

  .legal-overview-muted,
  .bond-progress-row span:last-child {
    color: #777777;
    font-weight: 500;
  }

  .legal-progress,
  .bond-progress-track {
    height: 7px;
    border-radius: 999px;
    background: #edf2f7;
    overflow: hidden;
  }

  .legal-progress span,
  .bond-progress-track span {
    display: block;
    height: 100%;
    background: #0b70f0;
    border-radius: inherit;
  }

  .legal-progress span {
    width: 100%;
  }

  .bond-progress-track span {
    width: 67%;
  }

  .legal-section-title {
    margin: 20px 0 14px;
    color: #222222;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .legal-section-title small {
    color: #777777;
    font-size: 12px;
    margin-left: 8px;
  }

  .legal-doc-list {
    display: grid;
    gap: 14px;
  }

  .legal-doc-item,
  .bond-doc-card {
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    padding: 20px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 20px;
    align-items: start;
  }

  .legal-doc-main,
  .bond-doc-main {
    min-width: 0;
    display: flex;
    gap: 14px;
  }

  .legal-doc-icon,
  .bond-doc-icon,
  .additional-doc-icon,
  .cert-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: #f7f7f8;
    color: #777777;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
  }

  .legal-doc-icon svg,
  .bond-doc-icon svg,
  .additional-doc-icon svg,
  .cert-icon svg {
    width: 20px;
    height: 20px;
  }

  .legal-doc-title-row,
  .bond-doc-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 8px;
  }

  .legal-doc-title,
  .bond-doc-title-row h5 {
    margin: 0;
    color: #222222;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.25;
  }

  .legal-doc-desc,
  .bond-doc-desc {
    margin: 0 0 14px;
    color: #777777;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 500;
  }

  .legal-file-name,
  .bond-file-name {
    margin: 0 0 5px;
    color: #222222;
    font-size: 13px;
    line-height: 1.3;
    font-weight: 700;
  }

  .legal-file-meta,
  .legal-file-accepts,
  .bond-file-meta,
  .bond-file-accepts {
    margin: 0 0 5px;
    color: #777777;
    font-size: 12px;
    line-height: 1.35;
    font-weight: 500;
  }

  .legal-empty-file,
  .bond-empty-file {
    margin: 0 0 12px;
    color: #777777;
    font-size: 13px;
    font-weight: 600;
    font-style: italic;
  }

  .legal-doc-actions,
  .bond-doc-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-top: 6px;
  }

  .bond-link {
    color: var(--blue);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
  }

  .bond-link:hover {
    text-decoration: underline;
  }

  .bond-toggle-card {
    min-height: 78px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    padding: 18px 20px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
  }

  .bond-toggle-card h5 {
    margin: 0 0 8px;
    color: #222222;
    font-size: 15px;
    line-height: 1.2;
    font-weight: 700;
  }

  .bond-toggle-card p {
    margin: 0;
    color: #777777;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 500;
  }

  .bond-alert {
    width: 100%;
    min-height: 78px;
    margin: 18px 0 24px;
    padding: 18px 20px;
    border: 1px solid #fed7aa;
    border-radius: 10px;
    background: #fff7ed;
    color: #444444;
    display: flex;
    align-items: flex-start;
    gap: 14px;
  }

  .bond-alert-icon {
    width: 22px;
    height: 22px;
    color: #f97316;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    margin-top: 1px;
  }

  .bond-alert-icon svg {
    width: 20px;
    height: 20px;
  }

  .bond-alert p {
    margin: 0;
    color: #444444;
    font-size: 14px;
    line-height: 1.65;
    font-weight: 500;
  }

  .settings-switch {
    position: relative;
    width: 52px;
    height: 30px;
    flex: 0 0 auto;
  }

  .settings-switch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .settings-switch span {
    position: absolute;
    inset: 0;
    border-radius: 999px;
    background: #e5e7eb;
    cursor: pointer;
    transition: background .16s ease;
  }

  .settings-switch span::before {
    content: "";
    position: absolute;
    width: 24px;
    height: 24px;
    top: 3px;
    left: 3px;
    border-radius: 999px;
    background: #ffffff;
    box-shadow: 0 4px 10px rgba(0,0,0,.12);
    transition: transform .16s ease;
  }

  .settings-switch input:checked + span {
    background: #0b70f0;
  }

  .settings-switch input:checked + span::before {
    transform: translateX(22px);
  }

  .legal-representative-card,
  .bond-representative-box {
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 22px;
    background: #ffffff;
    margin-bottom: 18px;
  }

  .legal-representative-head,
  .bond-section-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 22px;
  }

  .legal-representative-title,
  .bond-representative-head {
    display: flex;
    gap: 14px;
    align-items: center;
  }

  .legal-representative-icon {
    width: 42px;
    height: 42px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #f7f7f8;
    display: grid;
    place-items: center;
    color: #777777;
  }

  .legal-representative-kicker,
  .bond-representative-kicker {
    margin: 0 0 4px;
    color: #777777;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 700;
  }

  .legal-representative-name,
  .bond-representative-name {
    margin: 0;
    color: #222222;
    font-size: 16px;
    font-weight: 700;
  }

  .bond-representative-name span {
    color: #777777;
    font-weight: 500;
  }

  .legal-mini-file,
  .bond-mini-doc {
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    padding: 14px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
    margin-top: 14px;
  }

  .legal-mini-file-main,
  .bond-mini-doc-main {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
  }

  .bond-mini-doc-title {
    margin: 0 0 4px;
    color: #222222;
    font-size: 13px;
    font-weight: 700;
  }

  .bond-mini-doc-meta {
    margin: 0 0 4px;
    color: #777777;
    font-size: 12px;
    font-weight: 500;
  }

  .bond-mini-divider {
    height: 1px;
    background: var(--line);
    margin: 14px 0;
  }

  .bond-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .bond-section-title h4 {
    margin: 0;
    color: #111111;
    font-size: 17px;
    line-height: 1.2;
    font-weight: 700;
  }

  .bond-section-desc {
    margin: 8px 0 0;
    color: #777777;
    font-size: 14px;
    line-height: 1.45;
    font-weight: 500;
  }

  .legal-checkbox-row {
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    padding: 16px;
    display: flex;
    gap: 14px;
    align-items: flex-start;
    margin-top: 14px;
  }

  .legal-checkbox-row input {
    width: 22px;
    height: 22px;
    accent-color: var(--blue);
    margin-top: 2px;
  }

  .legal-add-row {
    width: 100%;
    min-height: 46px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: #222222;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: background .16s ease, transform .16s ease;
  }

  .legal-add-row:hover {
    background: #f9fafb;
  }

  .additional-doc-card {
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    padding: 22px;
    margin-bottom: 16px;
  }

  .additional-doc-head,
  .cert-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 20px;
  }

  .additional-doc-title,
  .cert-title-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
  }

  .additional-doc-title h5,
  .cert-title-row h5 {
    margin: 0 0 4px;
    color: #222222;
    font-size: 16px;
    line-height: 1.2;
    font-weight: 700;
  }

  .additional-doc-title p {
    margin: 0;
    color: #777777;
    font-size: 13px;
    line-height: 1.3;
    font-weight: 500;
  }

  .additional-doc-file {
    margin-top: 18px;
    border: 1px dashed #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
    padding: 14px 16px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    align-items: center;
  }

  .additional-doc-file strong {
    display: block;
    margin: 0 0 4px;
    color: #222222;
    font-size: 13px;
    line-height: 1.2;
    font-weight: 700;
  }

  .additional-doc-file span {
    color: #777777;
    font-size: 12px;
    line-height: 1.2;
    font-weight: 500;
  }

  .additional-add-link {
    width: fit-content;
    min-height: 40px;
    margin-top: 18px;
    border: 0;
    background: transparent;
    color: var(--blue);
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
  }

  .additional-add-link:hover {
    text-decoration: underline;
  }

  .cert-card {
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #ffffff;
    padding: 22px;
  }

  .cert-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .cert-upload-box {
    margin-top: 22px;
    min-height: 78px;
    border: 1px dashed #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
  }

  .cert-upload-box p {
    margin: 0;
    color: #777777;
    font-size: 13px;
    line-height: 1.35;
    font-weight: 600;
    font-style: italic;
  }

  .cert-add-btn {
    min-height: 50px;
    margin-top: 18px;
    padding: 0 26px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #ffffff;
    color: #222222;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: background .16s ease, transform .16s ease;
  }

  .cert-add-btn:hover {
    background: #f9fafb;
  }

  @media (max-width: 960px) {
    .settings-summary {
      grid-template-columns: 1fr;
    }

    .settings-progress-info {
      justify-content: flex-start;
      flex-wrap: wrap;
    }

    .settings-layout {
      grid-template-columns: 1fr;
    }

    .settings-sidebar {
      order: 2;
    }

    .settings-main {
      order: 1;
    }

    .legal-doc-item,
    .legal-mini-file,
    .additional-doc-file,
    .bond-doc-card,
    .bond-mini-doc {
      grid-template-columns: 1fr;
    }

    .legal-doc-actions,
    .bond-doc-actions {
      justify-content: flex-start;
      flex-wrap: wrap;
    }
  }

  @media (max-width: 700px) {
    .settings-topbar {
      height: auto;
      padding: 16px;
      align-items: flex-start;
      flex-direction: column;
    }

    .settings-wrap {
      padding: 16px;
    }

    .settings-grid-2,
    .settings-grid-3 {
      grid-template-columns: 1fr;
    }

    .settings-form-scroll {
      max-height: none;
      padding: 20px 16px 20px;
    }

    .settings-card {
      padding: 20px 16px;
    }

    .settings-card-actions,
    .settings-main-footer,
    .additional-doc-head,
    .cert-card-head,
    .cert-upload-box,
    .bond-section-head,
    .bond-toggle-card {
      justify-content: stretch;
      flex-direction: column;
      align-items: stretch;
    }

    .settings-main-footer-actions {
      flex-direction: column;
      align-items: stretch;
    }

    .settings-btn {
      width: 100%;
    }
  }
</style>

<div class="settings-page">
  @include('settings.partials.toasts')
  <header class="settings-topbar">
    <div>
      <p class="settings-topbar-kicker">Configuración</p>
      <div class="settings-topbar-title">
        <h1>{{ $pageTitle }}</h1>
        <p>Administra tu información personal, protege tu acceso y controla cómo usas monico día a día</p>
      </div>
    </div>

    <a href="{{ Route::has('projects.control') ? route('projects.control') : url('/') }}" class="settings-dashboard-btn">
      Ir al dashboard
    </a>
  </header>

  <main class="settings-wrap">
    <section class="settings-summary">
      <div>
        <h2>Configuración</h2>
        <p>Administra tu información personal, protege tu acceso y controla cómo usas monico día a día</p>
      </div>

      <div class="settings-progress-info">
        <span class="settings-progress-text">{{ $progressText }}</span>
        <div class="settings-progress-track">
          <div class="settings-progress-bar"></div>
        </div>
        <span class="settings-progress-percent">{{ $progressPercent }}</span>
      </div>
    </section>

    <div class="settings-layout">
      <aside class="settings-sidebar">
        <details class="settings-nav-section" {{ in_array($activeSection, ['profile', 'security'], true) ? 'open' : '' }}>
          <summary class="settings-nav-head">
            <span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M20 21a8 8 0 1 0-16 0"></path></svg>
              Personal
            </span>
            <svg class="settings-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
          </summary>

          <div class="settings-subnav">
            <a href="{{ $profileUrl }}" class="settings-nav-link {{ $activeSection === 'profile' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              Mi perfil
              <span class="settings-status-dot is-solid-green"></span>
            </a>

            <a href="{{ $securityUrl }}" class="settings-nav-link {{ $activeSection === 'security' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
              Seguridad
              <span class="settings-status-dot is-dashed-orange"></span>
            </a>
          </div>
        </details>

        <details class="settings-nav-section" {{ $isOrganizationOpen ? 'open' : '' }}>
          <summary class="settings-nav-head">
            <span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 21V9h6v12"></path><path d="M9 3v6h6V3"></path></svg>
              Organización
            </span>
            <svg class="settings-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
          </summary>

          <div class="settings-subnav">
            <a href="{{ $identityUrl }}" class="settings-nav-link {{ $activeSection === 'identity' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M9 21v-6h6v6"></path><path d="M8 7h.01"></path><path d="M12 7h.01"></path><path d="M16 7h.01"></path></svg>
              Identidad
              <span class="settings-status-dot is-dashed-orange"></span>
            </a>

            <a href="{{ $legalDocsUrl }}" class="settings-nav-link {{ $activeSection === 'legal_docs' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              Documentos legales
              <span class="settings-status-dot is-solid-green"></span>
            </a>

            <a href="{{ $additionalDocsUrl }}" class="settings-nav-link {{ $activeSection === 'additional_docs' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              Documentos adicional...
              <span class="settings-status-dot is-solid-green"></span>
            </a>

            <a href="{{ $certificationsUrl }}" class="settings-nav-link {{ $activeSection === 'certifications' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              Certificaciones
              <span class="settings-status-dot is-gray-line"></span>
            </a>
          </div>
        </details>

        <details class="settings-nav-section" {{ $isBondsOpen ? 'open' : '' }}>
          <summary class="settings-nav-head">
            <span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Fianzas
            </span>
            <svg class="settings-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
          </summary>

          <div class="settings-subnav">
            <a href="{{ $bondLegalUrl }}" class="settings-nav-link {{ $activeSection === 'bond_legal' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Información legal y co...
              <span class="settings-status-dot is-dashed-orange"></span>
            </a>

            <a href="{{ $bondTaxUrl }}" class="settings-nav-link {{ $activeSection === 'bond_tax' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Información fiscal
              <span class="settings-status-dot is-dashed-orange"></span>
            </a>

            <a href="{{ $bondFinancialUrl }}" class="settings-nav-link {{ $activeSection === 'bond_financial' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Información financiera
              <span class="settings-status-dot is-empty"></span>
            </a>

            <a href="{{ $bondReceiptsUrl }}" class="settings-nav-link {{ $activeSection === 'bond_receipts' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Comprobantes y vige...
              <span class="settings-status-dot is-empty"></span>
            </a>

            <a href="{{ $bondAffiliationUrl }}" class="settings-nav-link {{ $activeSection === 'bond_affiliation' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Documentación de afi...
              <span class="settings-status-dot is-gray-line"></span>
            </a>

            <a href="{{ $bondSpecialCasesUrl }}" class="settings-nav-link {{ $activeSection === 'bond_special_cases' ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              Casos especiales
              <span class="settings-status-dot is-gray-line"></span>
            </a>
          </div>
        </details>

        <details class="settings-nav-section">
          <summary class="settings-nav-head">
            <span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line></svg>
              Sistema
            </span>
            <svg class="settings-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"></path></svg>
          </summary>
        </details>
      </aside>

      {{-- La sección activa ya fue validada en el bloque @php superior. --}}
      @include('settings.sections.' . $activeSection)
    </div>
  </main>
</div>
@endsection