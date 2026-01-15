{{-- resources/views/publications/partials/icons.blade.php --}}
@php
  $name = $name ?? 'file';
@endphp

@if($name === 'stack')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 3l9 5-9 5-9-5 9-5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M3 12l9 5 9-5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M3 17l9 5 9-5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'upload')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M7 8l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@elseif($name === 'download')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 3v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M4 21h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@elseif($name === 'pin')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M14 9l7 7-2 2-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M3 21l7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M9 14l-2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M16 7l-5-5-4 4 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'clock')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" stroke="currentColor" stroke-width="2"/>
    <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'arrowUpRight')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M7 17L17 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M10 7h7v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'x')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@elseif($name === 'link')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 1 1 7 7l-1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 1 1-7-7l1-1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@elseif($name === 'check')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'photo')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="2"/>
    <path d="M8 11l3 3 5-5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'video')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M4 7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="2"/>
    <path d="M16 10l4-3v10l-4-3v-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
  </svg>
@elseif($name === 'pdf')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/>
    <path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M7.5 16.5h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@elseif($name === 'doc')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/>
    <path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M8 12h8M8 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@elseif($name === 'sheet')
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/>
    <path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    <path d="M8 12h8M8 16h8M10.5 10v10M13.5 10v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
@else {{-- file --}}
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/>
    <path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
  </svg>
@endif
