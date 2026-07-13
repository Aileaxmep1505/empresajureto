@props([
  'value' => 'ALTO',
  'label' => '',
  'tone' => 'yellow',
  'active' => false,
  'icon' => 'calendar',
])

@php
  $icons = [
    'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
    'money' => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/>',
    'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
    'pulse' => '<path d="M13 2 4 14h7l-1 8 10-13h-7l1-7z"/>',
  ];
@endphp

<button type="button" class="pjd-fx-score {{ $active ? 'is-active' : '' }}">
  <span class="pjd-fx-score-value">{{ $value }}</span>
  <span class="pjd-fx-score-label">{{ $label }}</span>
  <span class="pjd-fx-score-icon is-{{ $tone }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$icon] ?? $icons['document'] !!}</svg>
  </span>
</button>
