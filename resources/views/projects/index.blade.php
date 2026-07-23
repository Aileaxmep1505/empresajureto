@extends('layouts.app')

@section('content_class', 'content--flush')
@section('title', 'Proyectos')
<link rel="stylesheet" href="{{ asset('css/proyecto.css') }}?v={{ time() }}">

@push('styles')
<style>
  /* Hacer toda la card/row clicable */
  .js-project-row[data-href]{ cursor:pointer; transition: transform .12s ease, box-shadow .12s ease; }
  .js-project-row[data-href]:hover{ transform: translateY(-1px); box-shadow: 0 6px 18px rgba(15,23,42,.08); }
  /* No mostrar el cursor pointer encima de controles internos */
  .js-project-row a, .js-project-row button, .js-project-row label, .js-project-row input { cursor: auto; }
  .js-project-row .pj-drag-btn, .js-project-row .pj-icon-btn, .js-project-row .pj-dots-btn,
  .js-project-row .pj-star-btn, .js-project-row .pj-tag-add, .js-project-row .pj-label-pill-menu { cursor: pointer; }
  .js-project-row.is-saving-labels .pj-label-cell { opacity: .65; pointer-events: none; }
  .js-project-row.is-saving-favorite .pj-star-btn { opacity: .55; pointer-events: none; }
  .pj-star-btn.is-active { color: #f59e0b; }
  .pj-label-filter-pop { width: 280px; }
  .pj-label-filter-empty { padding: 12px; color: #888; font-weight: 600; font-size: .9rem; }
  .pj-label-filter-row { display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%; padding:10px 12px; border:0; background:transparent; border-radius:10px; color:#333; font-weight:700; cursor:pointer; }
  .pj-label-filter-row:hover { background:#f9fafb; }
  .pj-label-filter-row.is-active { background:#e6f0ff; color:#007aff; }
  .pj-label-filter-dot { width:10px; height:10px; border-radius:999px; background:#ff4a4a; flex:0 0 auto; }
  .pj-label-filter-name { flex:1; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .pj-label-filter-count { color:#888; font-size:.82rem; }
  .js-project-row.is-hidden-by-label { display:none !important; }


  /* Ajuste puntual: toolbar compacto, fijo y en una sola fila */
  .pj-page{
    padding: 0 0 48px;
    overflow-x: hidden;
  }

  .pj-page .pj-toolbar{
    position: sticky;
    top: 0;
    z-index: 30;
    min-height: 58px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    flex-wrap: nowrap;
    overflow: visible;
    white-space: nowrap;
    background: #ffffff;
    border-bottom: 1px solid #ebebeb;
    box-shadow: 0 4px 12px rgba(0,0,0,.04);
  }

  .pj-page .pj-toolbar-left,
  .pj-page .pj-toolbar-right{
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: nowrap;
    min-width: 0;
  }

  .pj-page .pj-toolbar-left{
    flex: 0 1 auto;
  }

  .pj-page .pj-toolbar-right{
    flex: 0 0 auto;
    margin-left: auto;
    justify-content: flex-end;
  }

  .pj-page .pj-title{
    flex: 0 0 auto;
    margin: 0 6px 0 0;
    font-size: .98rem;
    line-height: 1;
    white-space: nowrap;
  }

  .pj-page .pj-search-wrap{
    flex: 0 0 320px;
    width: 320px;
    max-width: 320px;
    min-width: 260px;
    display: flex;
    align-items: center;
    margin: 0;
  }

  .pj-page .pj-search-box{
    width: 100%;
    height: 38px;
    display: flex;
    align-items: center;
    border-radius: 12px;
  }

  .pj-page .pj-search-box input{
    height: 100%;
    font-size: .88rem;
  }

  .pj-page .pj-btn{
    height: 38px;
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 12px;
    border-radius: 10px;
    white-space: nowrap;
    flex: 0 0 auto;
    font-size: .88rem;
  }

  .pj-page .pj-btn-icon-only{
    width: 38px;
    min-width: 38px;
    padding: 0;
  }

  .pj-page .pj-btn-create{
    padding-inline: 14px;
  }

  .pj-page .pj-icon{
    width: 17px;
    height: 17px;
    flex-shrink: 0;
  }

  .pj-page .pj-view-transition{
    padding: 18px 22px 0;
    overflow-x: auto;
  }

  .pj-page .pj-board{
    display: flex;
    align-items: flex-start;
    gap: 16px;
    width: max-content;
    min-width: 100%;
  }

  .pj-page .pj-column.is-collapsed{
    width: 54px;
    min-width: 54px;
  }

  .pj-page .pj-column-collapsed-btn{
    width: 54px;
    min-height: 292px;
    border-radius: 16px;
    padding: 18px 0;
  }

  .pj-page .pj-column.is-open{
    width: 330px;
    min-width: 330px;
  }

  .pj-page .pj-column-open{
    border-radius: 16px;
  }

  .pj-page .pj-column-header{
    padding: 16px 14px;
  }

  .pj-page .pj-column-body{
    padding: 12px 10px 18px;
  }

  .pj-page .pj-cards{
    gap: 12px;
  }

  .pj-page .pj-card{
    border-radius: 16px;
    padding: 14px;
  }

  @media (max-width: 1550px){
    .pj-page .pj-toolbar{
      gap: 7px;
      padding-inline: 10px;
    }

    .pj-page .pj-toolbar-left,
    .pj-page .pj-toolbar-right{
      gap: 7px;
    }

    .pj-page .pj-search-wrap{
      flex-basis: 280px;
      width: 280px;
      max-width: 280px;
      min-width: 220px;
    }

    .pj-page .pj-btn{
      height: 36px;
      min-height: 36px;
      padding: 0 10px;
      font-size: .84rem;
    }

    .pj-page .pj-btn-icon-only{
      width: 36px;
      min-width: 36px;
    }
  }

  @media (max-width: 1200px){
    .pj-page .pj-search-wrap{
      flex-basis: 230px;
      width: 230px;
      max-width: 230px;
      min-width: 200px;
    }

    .pj-page .pj-btn{
      padding: 0 9px;
      gap: 5px;
    }
  }


  .pj-page .pj-drop-target {
    outline: 2px dashed #007aff;
    outline-offset: 4px;
    border-radius: 16px;
  }

  .pj-page .is-saving-workflow {
    opacity: .62;
    pointer-events: none;
    transition: opacity .16s ease;
  }


  /* Prioridad editable y asignacion de usuario */
  .pj-priority-btn {
    border: 0;
    cursor: pointer;
    transition: transform .14s ease, box-shadow .14s ease, opacity .14s ease;
  }

  .pj-priority-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.06);
  }

  .pj-priority-btn:active {
    transform: scale(.98);
  }

  .pj-priority.is-high { background: #ffebeb; color: #ff4a4a; }
  .pj-priority.is-medium { background: #fff7ed; color: #ea580c; }
  .pj-priority.is-low { background: #e6ffe6; color: #15803d; }
  .pj-priority.is-normal { background: #eef2f7; color: #667085; }

  .pj-priority-popover {
    position: absolute;
    z-index: 120;
    display: none;
    width: 190px;
    padding: 8px;
    background: #fff;
    border: 1px solid #ebebeb;
    border-radius: 16px;
    box-shadow: 0 18px 44px rgba(15,23,42,.14);
  }

  .pj-priority-popover.is-open { display: block; }

  .pj-priority-option {
    width: 100%;
    border: 0;
    background: transparent;
    padding: 8px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 700;
    color: #333;
  }

  .pj-priority-option:hover,
  .pj-priority-option.is-active { background: #f9fafb; }

  .pj-priority-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    flex: 0 0 auto;
  }

  .pj-priority-dot.is-high { background: #ff4a4a; }
  .pj-priority-dot.is-medium { background: #ea580c; }
  .pj-priority-dot.is-low { background: #15803d; }
  .pj-priority-dot.is-normal { background: #667085; }

  .pj-avatar-btn,
  .pj-assigned-btn {
    border: 0;
    background: transparent;
    padding: 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: inherit;
    font: inherit;
  }

  .pj-avatar-btn:hover .pj-avatar,
  .pj-assigned-btn:hover .pj-avatar {
    box-shadow: 0 0 0 3px #e6f0ff;
  }

  .pj-assignee-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 22px;
    background: rgba(255,255,255,.62);
    backdrop-filter: blur(10px);
  }

  .pj-assignee-modal-backdrop.is-open { display: flex; }

  .pj-assignee-modal {
    width: min(520px, calc(100vw - 32px));
    max-height: min(680px, calc(100vh - 44px));
    background: #fff;
    border: 1px solid #ebebeb;
    border-radius: 16px;
    box-shadow: 0 24px 70px rgba(15,23,42,.16);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .pj-assignee-head {
    padding: 18px 22px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
  }

  .pj-assignee-title {
    margin: 0;
    color: #111;
    font-size: 1rem;
    font-weight: 700;
  }

  .pj-assignee-close {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #555;
    cursor: pointer;
  }

  .pj-assignee-close:hover { background: #f9fafb; }

  .pj-assignee-list {
    padding: 0 22px 14px;
    overflow: auto;
    display: grid;
    gap: 9px;
  }

  .pj-assignee-option {
    border: 1px solid #ebebeb;
    background: #fff;
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    text-align: left;
  }

  .pj-assignee-option:hover,
  .pj-assignee-option.is-selected {
    border-color: #007aff;
    box-shadow: 0 0 0 3px #e6f0ff;
  }

  .pj-assignee-avatar {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    background: #f3f4f6;
    color: #555;
    display: grid;
    place-items: center;
    font-weight: 700;
    flex: 0 0 auto;
  }

  .pj-assignee-name {
    color: #111;
    font-weight: 700;
    line-height: 1.15;
  }

  .pj-assignee-email {
    color: #888;
    font-weight: 600;
    font-size: .84rem;
    margin-top: 3px;
  }



  .pj-assignee-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  .pj-assignee-name,
  .pj-assignee-email {
    display: block;
  }

  .pj-assignee-email {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .pj-assignee-avatar img,
  .pj-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 999px;
    object-fit: cover;
    display: block;
  }

  .pj-assignee-actions {
    border-top: 1px solid #ebebeb;
    padding: 14px 22px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #fff;
  }

  .js-project-row.is-saving-priority .pj-priority-btn,
  .js-project-row.is-saving-assignee .pj-avatar-btn,
  .js-project-row.is-saving-assignee .pj-assigned-btn {
    opacity: .55;
    pointer-events: none;
  }


  .pj-project-menu, .pj-project-color-popover {
    position: fixed; z-index: 80; display: none;
  }
  .pj-project-menu.is-open, .pj-project-color-popover.is-open {
    display: block;
  }
  .pj-project-menu-card, .pj-project-color-card {
    background: #fff; border: 1px solid #e7e7e7; border-radius: 18px; box-shadow: 0 18px 45px rgba(15,23,42,.16);
  }
  .pj-project-menu-card { min-width: 305px; padding: 10px; }
  .pj-project-menu-item {
    width: 100%; border: 0; background: transparent; padding: 13px 14px; border-radius: 14px; display: flex; align-items: center; gap: 12px;
    color: #222; font-family: inherit; font-size: 15px; font-weight: 700; text-align: left; cursor: pointer; transition: background .16s ease, color .16s ease;
  }
  .pj-project-menu-item:hover { background: #f7f8fb; }
  .pj-project-menu-item.is-danger { color: #ef4444; }
  .pj-project-menu-item.is-danger:hover { background: #fff1f1; }
  .pj-project-menu-icon { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 22px; }
  .pj-project-menu-icon svg { width: 22px; height: 22px; }
  .pj-project-menu-divider { height: 1px; background: #ececec; margin: 8px 4px; }

  .pj-project-color-card { min-width: 240px; padding: 14px; }
  .pj-project-color-head { font-size: 14px; font-weight: 800; color: #333; margin-bottom: 10px; }
  .pj-project-color-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
  .pj-project-color-dot {
    width: 38px; height: 38px; border-radius: 999px; border: 3px solid #fff; box-shadow: 0 0 0 1px rgba(15,23,42,.08); cursor: pointer;
    transition: transform .14s ease, box-shadow .14s ease;
  }
  .pj-project-color-dot:hover { transform: scale(1.08); box-shadow: 0 0 0 2px rgba(59,130,246,.25); }

  .pj-stage-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 120;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 22px;
    background: rgba(255,255,255,.62);
    backdrop-filter: blur(12px);
  }

  .pj-stage-modal-backdrop.is-open {
    display: flex;
  }

  .pj-stage-modal {
    width: min(460px, 100%);
    background: #fff;
    border: 1px solid #ebebeb;
    border-radius: 12px;
    box-shadow: 0 24px 70px rgba(15,23,42,.16);
    overflow: hidden;
  }

  .pj-stage-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 28px 14px;
  }

  .pj-stage-modal-title {
    margin: 0;
    color: #111;
    font-size: 1.05rem;
    font-weight: 700;
  }

  .pj-stage-modal-close {
    width: 30px;
    height: 30px;
    border: 0;
    background: transparent;
    color: #555;
    border-radius: 8px;
    font-size: 1.3rem;
    line-height: 1;
    cursor: pointer;
  }

  .pj-stage-modal-close:hover {
    background: #f9fafb;
  }

  .pj-stage-modal-body {
    padding: 0 28px 18px;
  }

  .pj-stage-select {
    width: 100%;
    height: 48px;
    border: 1px solid #111;
    border-radius: 8px;
    background: #fff;
    color: #333;
    padding: 0 14px;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 600;
    outline: none;
  }

  .pj-stage-select:focus {
    border-color: #007aff;
    box-shadow: 0 0 0 3px #e6f0ff;
  }

  .pj-stage-modal-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    padding: 0 28px 28px;
  }

  .pj-stage-modal-btn {
    min-width: 98px;
    height: 46px;
    border-radius: 8px;
    border: 0;
    font-family: inherit;
    font-size: .92rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform .14s ease, background .14s ease;
  }

  .pj-stage-modal-btn:active {
    transform: scale(.98);
  }

  .pj-stage-modal-btn.is-ghost {
    background: transparent;
    color: #222;
  }

  .pj-stage-modal-btn.is-ghost:hover {
    background: #f9fafb;
  }

  .pj-stage-modal-btn.is-primary {
    background: #007aff;
    color: #fff;
  }

  .pj-stage-modal-btn.is-primary:hover {
    filter: brightness(.98);
  }


  /* Toasts y modales profesionales */
  .pj-toast-stack {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 300;
    display: grid;
    gap: 10px;
    width: min(380px, calc(100vw - 32px));
    pointer-events: none;
  }

  .pj-toast {
    pointer-events: auto;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 15px;
    border-radius: 16px;
    background: rgba(255,255,255,.94);
    border: 1px solid #ebebeb;
    box-shadow: 0 18px 48px rgba(15,23,42,.14);
    backdrop-filter: blur(14px);
    transform: translateY(10px);
    opacity: 0;
    animation: pjToastIn .22s cubic-bezier(.22,1,.36,1) forwards;
  }

  .pj-toast.is-leaving {
    animation: pjToastOut .18s ease forwards;
  }

  @keyframes pjToastIn {
    to { transform: translateY(0); opacity: 1; }
  }

  @keyframes pjToastOut {
    to { transform: translateY(8px); opacity: 0; }
  }

  .pj-toast-icon {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    font-size: .9rem;
    font-weight: 800;
  }

  .pj-toast.is-success .pj-toast-icon { background: #e6ffe6; color: #15803d; }
  .pj-toast.is-error .pj-toast-icon { background: #ffebeb; color: #ff4a4a; }
  .pj-toast.is-info .pj-toast-icon { background: #e6f0ff; color: #007aff; }

  .pj-toast-body {
    min-width: 0;
    flex: 1;
  }

  .pj-toast-title {
    margin: 0 0 2px;
    color: #111;
    font-size: .92rem;
    font-weight: 800;
  }

  .pj-toast-message {
    margin: 0;
    color: #555;
    font-size: .84rem;
    font-weight: 600;
    line-height: 1.35;
  }

  .pj-toast-close {
    width: 26px;
    height: 26px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #888;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
  }

  .pj-toast-close:hover { background: #f9fafb; color: #333; }

  .pj-confirm-backdrop,
  .pj-prompt-backdrop {
    position: fixed;
    inset: 0;
    z-index: 260;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 22px;
    background: rgba(255,255,255,.62);
    backdrop-filter: blur(14px);
  }

  .pj-confirm-backdrop.is-open,
  .pj-prompt-backdrop.is-open {
    display: flex;
  }

  .pj-confirm-modal,
  .pj-prompt-modal {
    width: min(460px, 100%);
    background: #fff;
    border: 1px solid #ebebeb;
    border-radius: 18px;
    box-shadow: 0 28px 80px rgba(15,23,42,.16);
    overflow: hidden;
  }

  .pj-confirm-head,
  .pj-prompt-head {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    padding: 24px 26px 14px;
  }

  .pj-confirm-title,
  .pj-prompt-title {
    margin: 0 0 7px;
    color: #111;
    font-size: 1.05rem;
    font-weight: 800;
  }

  .pj-confirm-message,
  .pj-prompt-message {
    margin: 0;
    color: #666;
    font-size: .9rem;
    font-weight: 600;
    line-height: 1.45;
  }

  .pj-confirm-close,
  .pj-prompt-close {
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #777;
    cursor: pointer;
    font-size: 1.3rem;
    line-height: 1;
  }

  .pj-confirm-close:hover,
  .pj-prompt-close:hover {
    background: #f9fafb;
    color: #111;
  }

  .pj-prompt-body {
    padding: 0 26px 18px;
  }

  .pj-prompt-input {
    width: 100%;
    height: 48px;
    border: 1px solid #ebebeb;
    border-radius: 10px;
    background: #fff;
    color: #111;
    padding: 0 14px;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 700;
    outline: none;
  }

  .pj-prompt-input:focus {
    border-color: #007aff;
    box-shadow: 0 0 0 3px #e6f0ff;
  }

  .pj-confirm-actions,
  .pj-prompt-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 26px 24px;
  }

  .pj-confirm-btn,
  .pj-prompt-btn {
    height: 44px;
    min-width: 96px;
    border-radius: 10px;
    border: 0;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform .14s ease, background .14s ease, filter .14s ease;
  }

  .pj-confirm-btn:active,
  .pj-prompt-btn:active {
    transform: scale(.98);
  }

  .pj-confirm-btn.is-ghost,
  .pj-prompt-btn.is-ghost {
    background: transparent;
    color: #333;
  }

  .pj-confirm-btn.is-ghost:hover,
  .pj-prompt-btn.is-ghost:hover {
    background: #f9fafb;
  }

  .pj-confirm-btn.is-primary,
  .pj-prompt-btn.is-primary {
    background: #007aff;
    color: #fff;
  }

  .pj-confirm-btn.is-danger {
    background: #ff4a4a;
    color: #fff;
  }

  .pj-confirm-btn.is-primary:hover,
  .pj-prompt-btn.is-primary:hover,
  .pj-confirm-btn.is-danger:hover {
    filter: brightness(.98);
  }


  .pj-column-menu {
    position: fixed;
    z-index: 95;
    display: none;
  }

  .pj-column-menu.is-open {
    display: block;
  }

  .pj-column-menu-card {
    min-width: 360px;
    padding: 8px;
    background: rgba(255,255,255,.96);
    border: 1px solid #ebebeb;
    border-radius: 12px;
    box-shadow: 0 18px 46px rgba(15,23,42,.16);
    backdrop-filter: blur(14px);
  }

  .pj-column-menu-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 11px;
    min-height: 46px;
    padding: 10px 12px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: #222;
    font-family: inherit;
    font-size: .98rem;
    font-weight: 700;
    text-align: left;
    cursor: pointer;
    transition: background .16s ease, color .16s ease;
  }

  .pj-column-menu-item:hover,
  .pj-column-menu-item.is-primary {
    background: #f7f9fc;
    color: #18376b;
  }

  .pj-column-menu-icon {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 22px;
  }

  .pj-column-menu-icon svg {
    width: 22px;
    height: 22px;
  }


  /* Ajuste definitivo: popovers de la barra inferior siempre arriba, nunca hacia abajo */
  .pj-bulk-floating {
    position: fixed !important;
    z-index: 260 !important;
    max-width: min(520px, calc(100vw - 32px));
  }

  .pj-bulk-floating.pj-label-popover,
  .pj-bulk-floating .pj-label-popover-card {
    width: min(420px, calc(100vw - 32px));
  }

  .pj-bulk-floating.pj-priority-popover {
    width: min(310px, calc(100vw - 32px));
  }

  .pj-bulk-floating.pj-project-color-popover .pj-project-color-card {
    width: min(300px, calc(100vw - 32px));
  }


  .pj-stage-modal-backdrop.is-bulk-popover,
  .pj-assignee-modal-backdrop.is-bulk-popover {
    background: transparent;
    backdrop-filter: none;
    align-items: flex-end;
    justify-content: center;
    pointer-events: none;
    padding: 0 18px 112px;
  }

  .pj-stage-modal-backdrop.is-bulk-popover .pj-stage-modal,
  .pj-assignee-modal-backdrop.is-bulk-popover .pj-assignee-modal {
    pointer-events: auto;
    width: min(430px, calc(100vw - 36px));
    max-height: min(520px, calc(100vh - 160px));
    border-radius: 14px;
  }


  /* Ajuste final: paneles masivos separados de la barra inferior */
  .pj-bulk-floating {
    position: fixed !important;
    z-index: 420 !important;
  }

  .pj-bulk-floating.pj-label-popover,
  .pj-bulk-floating.pj-priority-popover,
  .pj-bulk-floating.pj-project-color-popover {
    transform: none !important;
  }

  .pj-bulkbar {
    z-index: 360;
  }

  .pj-bulk-floating.pj-label-popover {
    width: min(420px, calc(100vw - 36px));
  }

  .pj-bulk-floating.pj-priority-popover {
    width: min(310px, calc(100vw - 36px));
  }

  .pj-bulk-floating.pj-project-color-popover .pj-project-color-card {
    width: min(310px, calc(100vw - 36px));
  }

  .pj-stage-modal-backdrop.is-bulk-popover,
  .pj-assignee-modal-backdrop.is-bulk-popover {
    z-index: 420;
    padding-bottom: 128px;
  }


  /* Paneles de la barra inferior: estilo compacto tipo dropdown */
  .pj-bulk-floating {
    position: fixed !important;
    z-index: 520 !important;
    transform: none !important;
  }

  .pj-bulk-floating .pj-label-popover-card,
  .pj-bulk-floating .pj-project-color-card,
  .pj-bulk-floating.pj-priority-popover {
    border-radius: 12px !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 18px 44px rgba(15,23,42,.16) !important;
  }

  .pj-bulk-floating.pj-label-popover {
    width: min(360px, calc(100vw - 32px)) !important;
  }

  .pj-bulk-floating .pj-label-popover-card {
    padding: 18px 20px 20px !important;
  }

  .pj-label-option {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    width: 100% !important;
    min-height: 42px !important;
    padding: 8px 4px !important;
    border: 0 !important;
    background: transparent !important;
    color: #333 !important;
    font: inherit !important;
    font-weight: 700 !important;
    text-align: left !important;
    cursor: pointer !important;
  }

  .pj-label-option:hover {
    background: #f9fafb !important;
    border-radius: 10px !important;
  }

  .pj-label-option-check {
    width: 22px;
    height: 22px;
    border: 2px solid #9ca3af;
    border-radius: 5px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 22px;
    color: transparent;
    transition: background .14s ease, border-color .14s ease, color .14s ease;
  }

  .pj-label-option.is-selected .pj-label-option-check {
    background: #007aff;
    border-color: #007aff;
    color: #fff;
  }

  .pj-label-option-check svg {
    width: 15px;
    height: 15px;
  }

  .pj-bulk-apply-btn {
    display: none;
    margin-top: 12px;
    height: 42px;
    min-width: 112px;
    border: 0;
    border-radius: 10px;
    background: #007aff;
    color: #fff;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform .14s ease, filter .14s ease;
  }

  .pj-bulk-apply-btn:hover {
    filter: brightness(.98);
  }

  .pj-bulk-apply-btn:active {
    transform: scale(.98);
  }

  .pj-label-popover.is-bulk-mode .pj-label-create {
    display: none !important;
  }

  .pj-label-popover.is-bulk-mode .pj-bulk-apply-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .pj-label-popover.is-bulk-mode .pj-label-options {
    margin-top: 10px;
  }

  .pj-stage-modal-backdrop.is-bulk-popover,
  .pj-assignee-modal-backdrop.is-bulk-popover {
    display: block !important;
    background: transparent !important;
    backdrop-filter: none !important;
    pointer-events: none !important;
    padding: 0 !important;
    z-index: 520 !important;
  }

  .pj-stage-modal-backdrop.is-bulk-popover .pj-stage-modal,
  .pj-assignee-modal-backdrop.is-bulk-popover .pj-assignee-modal {
    position: fixed !important;
    pointer-events: auto !important;
    width: min(340px, calc(100vw - 32px)) !important;
    max-height: min(520px, calc(100vh - 150px)) !important;
    border-radius: 12px !important;
    box-shadow: 0 18px 44px rgba(15,23,42,.16) !important;
  }

  .pj-stage-modal-backdrop.is-bulk-popover .pj-stage-modal-head,
  .pj-stage-modal-backdrop.is-bulk-popover .pj-stage-modal-body,
  .pj-stage-modal-backdrop.is-bulk-popover .pj-stage-modal-actions {
    padding-left: 20px !important;
    padding-right: 20px !important;
  }

  .pj-stage-modal-backdrop.is-bulk-popover .pj-stage-modal-actions {
    justify-content: flex-start !important;
  }

  .pj-bulkbar {
    z-index: 450 !important;
  }


  /* Fix visual: panel arriba de la barra y checkbox seleccionado azul */
  .pj-bulk-floating {
    position: fixed !important;
    z-index: 650 !important;
    bottom: auto !important;
    transform: none !important;
  }

  .pj-bulkbar {
    z-index: 560 !important;
  }

  .pj-bulk-floating.pj-label-popover {
    width: min(340px, calc(100vw - 32px)) !important;
  }

  .pj-bulk-floating .pj-label-popover-card {
    padding: 18px !important;
    max-height: min(520px, calc(100vh - 170px)) !important;
    overflow: auto !important;
  }

  .pj-label-option.is-selected .pj-label-option-check,
  .pj-label-option[aria-checked="true"] .pj-label-option-check {
    background: #007aff !important;
    border-color: #007aff !important;
    color: #ffffff !important;
  }

  .pj-label-option.is-selected .pj-label-option-check svg,
  .pj-label-option[aria-checked="true"] .pj-label-option-check svg {
    color: #ffffff !important;
    opacity: 1 !important;
  }

  .pj-label-option:not(.is-selected):not([aria-checked="true"]) .pj-label-option-check svg {
    opacity: 0 !important;
  }

  .pj-label-option.is-selected,
  .pj-label-option[aria-checked="true"] {
    background: #f8fbff !important;
    border-radius: 10px !important;
  }


  /* Checkboxes seleccionados: azul con palomita */
  .pj-label-option.is-selected .pj-label-option-check,
  .pj-label-option[aria-checked="true"] .pj-label-option-check {
    background: #007aff !important;
    border-color: #007aff !important;
    color: #ffffff !important;
  }

  .pj-label-option.is-selected .pj-label-option-check svg,
  .pj-label-option[aria-checked="true"] .pj-label-option-check svg {
    opacity: 1 !important;
    color: #ffffff !important;
    display: block !important;
  }

  .pj-label-option:not(.is-selected):not([aria-checked="true"]) .pj-label-option-check svg {
    opacity: 0 !important;
  }

  .pj-check input:checked + span,
  .pj-row-check input:checked + span,
  .pj-group-check input:checked + span {
    background: #007aff !important;
    border-color: #007aff !important;
    box-shadow: 0 0 0 3px #e6f0ff !important;
  }

  .pj-check input:checked + span::after,
  .pj-row-check input:checked + span::after,
  .pj-group-check input:checked + span::after {
    content: "" !important;
    position: absolute !important;
    left: 50% !important;
    top: 47% !important;
    width: 6px !important;
    height: 10px !important;
    border: solid #ffffff !important;
    border-width: 0 2px 2px 0 !important;
    transform: translate(-50%, -50%) rotate(45deg) !important;
  }

  .pj-check span,
  .pj-row-check span,
  .pj-group-check span {
    position: relative !important;
  }


  #pjBulkFavoriteBtn {
    min-width: 118px;
  }


  .js-project-row.is-hidden-by-toolbar-filter { display: none !important; }
  .pj-page.is-archive-mode .pj-title::after {
    content: "Archivados";
    display: inline-flex;
    margin-left: 10px;
    padding: 4px 9px;
    border-radius: 999px;
    background: #e6f0ff;
    color: #007aff;
    font-size: .75rem;
    font-weight: 800;
    vertical-align: middle;
  }


  /* Tooltips minimalistas del toolbar */
  .pj-toolbar [data-tooltip] {
    position: relative;
  }

  .pj-toolbar [data-tooltip]::before,
  .pj-toolbar [data-tooltip]::after {
    position: absolute;
    left: 50%;
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, 6px);
    transition: opacity .14s ease, transform .14s ease;
  }

  .pj-toolbar [data-tooltip]::before {
    content: attr(data-tooltip);
    top: calc(100% + 10px);
    z-index: 700;
    max-width: 230px;
    padding: 8px 10px;
    border-radius: 10px;
    background: rgba(17, 24, 39, .96);
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
    box-shadow: 0 12px 28px rgba(15,23,42,.18);
  }

  .pj-toolbar [data-tooltip]::after {
    content: "";
    top: calc(100% + 4px);
    z-index: 701;
    width: 10px;
    height: 10px;
    background: rgba(17, 24, 39, .96);
    transform: translate(-50%, 6px) rotate(45deg);
  }

  .pj-toolbar [data-tooltip]:hover::before,
  .pj-toolbar [data-tooltip]:hover::after,
  .pj-toolbar [data-tooltip]:focus-visible::before,
  .pj-toolbar [data-tooltip]:focus-visible::after {
    opacity: 1;
    transform: translate(-50%, 0);
  }

  .pj-toolbar [data-tooltip]:hover::after,
  .pj-toolbar [data-tooltip]:focus-visible::after {
    transform: translate(-50%, 0) rotate(45deg);
  }

  .pj-toolbar .pj-btn.is-working {
    opacity: .7;
    pointer-events: none;
  }


  /* Menús de opciones: mismo estilo compacto para etapas y proyectos */
  .pj-project-menu {
    z-index: 180 !important;
  }

  .pj-project-menu-card {
    min-width: 330px !important;
    padding: 8px !important;
    border-radius: 12px !important;
    background: rgba(255,255,255,.97) !important;
    border: 1px solid #ebebeb !important;
    box-shadow: 0 18px 46px rgba(15,23,42,.16) !important;
    backdrop-filter: blur(14px);
  }

  .pj-project-menu-item {
    min-height: 46px !important;
    padding: 10px 12px !important;
    border-radius: 9px !important;
    font-size: .98rem !important;
    font-weight: 700 !important;
  }

  .pj-project-menu-item:hover {
    background: #f7f9fc !important;
    color: #18376b !important;
  }

  .pj-project-menu-item.is-danger:hover {
    background: #fff1f1 !important;
    color: #ff4a4a !important;
  }
</style>
@endpush

@php
    $currentView = request('view', 'cards');
    $hasActiveSearch = trim((string) request('q', '')) !== '' || trim((string) request('label', '')) !== '';

    $openColumns = collect(explode(',', (string) request('open', 'analisis_bases')))
        ->map(fn ($id) => trim((string) $id))
        ->filter(fn ($id) => $id !== '')
        ->values()
        ->all();

    $statusMap = [
        'Análisis de Bases' => 'Vigente',
        'Revisión' => 'Vigente',
        'Participa' => 'Vigente',
        'No participa' => 'Vigente',
        'Ganado' => 'Vigente',
        'Perdido' => 'Vigente',
        'Desierta' => 'Vigente',
    ];

    $assignedNames = [
        'S' => 'Samantha Michelle',
        'G' => 'Geovanni Emmanuel',
        'A' => 'Jose Alfredo',
        'J' => 'Juan Rene',
        'M' => 'Samantha Michelle',
        'R' => 'Geovanni Emmanuel',
        'L' => 'Juan Rene',
    ];

    $toneStyles = [
        'blue' => ['bg' => '#dde6f6', 'text' => '#2563eb', 'dot' => '#2563eb'],
        'orange' => ['bg' => '#f4eadf', 'text' => '#ef8c35', 'dot' => '#f59e0b'],
        'green' => ['bg' => '#dfece6', 'text' => '#1f9d55', 'dot' => '#22c55e'],
        'red' => ['bg' => '#f6e3e3', 'text' => '#ef4444', 'dot' => '#ef4444'],
        'purple' => ['bg' => '#ece6f6', 'text' => '#7c5cf5', 'dot' => '#8b5cf6'],
        'gray' => ['bg' => '#ececec', 'text' => '#6b7280', 'dot' => '#6b7280'],
        'rose' => ['bg' => '#f3e7e7', 'text' => '#b91c1c', 'dot' => '#b91c1c'],
    ];
@endphp

@php
    $isArchiveMode = request()->boolean('archived');
    $activeQuery = request()->query();
    unset($activeQuery['archived']);

    $archiveQuery = request()->query();
    $archiveQuery['archived'] = 1;
@endphp

@section('content')
@include('projects.partials.control-sidebar')
<div class="pj-page {{ $isArchiveMode ? 'is-archive-mode' : '' }}">
    <div class="pj-toolbar">
        <div class="pj-toolbar-left">
            <div class="pj-title">Proyectos</div>

            <form class="pj-search-wrap" method="GET" action="{{ route('projects.index') }}" data-ajax-search>
                <input type="hidden" name="view" value="{{ $currentView }}">
                <div class="pj-search-box">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-search-icon">
                        <path d="M21 21L16.65 16.65M10.8 18.6a7.8 7.8 0 1 0 0-15.6 7.8 7.8 0 0 0 0 15.6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input type="text" name="q" placeholder="Buscar por nombre, asignado, tag..." value="{{ request('q') }}">
                </div>
            </form>

            <button type="button" class="pj-btn pj-btn-light pj-btn-create" id="openProjectModal">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
               Nuevo Proyecto
            </button>

            <a href="{{ route('projects.index', array_merge(request()->except('view'), ['view' => 'cards'])) }}"
               class="pj-btn {{ $currentView === 'cards' ? 'pj-btn-primary is-active' : 'pj-btn-light' }}">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                Tarjetas
            </a>

            <a href="{{ route('projects.index', array_merge(request()->except('view'), ['view' => 'list'])) }}"
               class="pj-btn {{ $currentView === 'list' ? 'pj-btn-primary is-active' : 'pj-btn-light' }}">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Lista
            </a>

            <button type="button" class="pj-btn pj-btn-light pj-btn-icon-only" title="Fijar" data-tooltip="Fijar vista" aria-label="Fijar vista">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                    <path d="M8 4h8v3l-2 2v4l2 2v2H8v-2l2-2V9L8 7V4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="pj-toolbar-right">
            <div class="pj-pop-wrap">
                <button type="button" class="pj-btn pj-btn-light js-toggle-pop" data-pop="sort-pop" data-tooltip="Ordenar proyectos" aria-label="Ordenar proyectos">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                        <path d="M8 4v16M8 4l-3 3M8 4l3 3M16 20V4m0 16l-3-3m3 3l3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Ordenar
                </button>

                <div class="pj-popover pj-sort-pop" id="sort-pop">
                    <div class="pj-pop-head">
                        <span>FILTRO</span>
                        <button type="button" class="pj-link-btn js-clear-sort">Limpiar</button>
                    </div>
                    <div class="pj-form-group">
                        <select class="pj-select" id="pjSortField">
                            <option value="manual">Manual</option>
                            <option value="name">Nombre</option>
                            <option value="date">Fecha de inicio</option>
                            <option value="priority">Prioridad</option>
                            <option value="assigned">Asignado</option>
                        </select>
                    </div>
                    <div class="pj-form-group">
                        <select class="pj-select" id="pjSortDirection">
                            <option value="asc">Asc</option>
                            <option value="desc">Desc</option>
                        </select>
                    </div>
                    <div class="pj-pop-divider"></div>
                    <div class="pj-pop-footer">
                        <div class="pj-pop-count">Filtro: 0</div>
                        <div class="pj-pop-actions">
                            <button type="button" class="pj-btn pj-btn-light js-close-pop">Cancelar</button>
                            <button type="button" class="pj-btn pj-btn-primary" id="pjApplySort">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pj-pop-wrap">
                <button type="button" class="pj-btn pj-btn-light js-toggle-pop" data-pop="filter-pop" data-tooltip="Filtrar proyectos" aria-label="Filtrar proyectos">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon">
                        <path d="M4 5h16l-6 7v6l-4-2v-4L4 5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    Filtros
                </button>

                <div class="pj-popover pj-filter-pop" id="filter-pop">
                    <div class="pj-filter-scroll">
                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Asignado</div>
                            <div class="pj-check-list">
                                @foreach(($users ?? collect()) as $user)
                                    <label class="pj-check-row">
                                        <span>{{ $user->name }}</span>
                                        <input type="checkbox" class="js-filter-assignee" value="{{ $user->id }}">
                                        <span class="pj-square"></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Preferencias</div>
                            <label class="pj-check-row">
                                <span>Solo favoritos</span>
                                <input type="checkbox" id="pjFilterFavorites">
                                <span class="pj-square"></span>
                            </label>
                        </div>
                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Prioridad</div>
                            <div class="pj-check-list">
                                <label class="pj-check-row"><span class="pj-pri-label"><i class="pj-pri-dot is-red"></i> Alta</span><input type="checkbox" class="js-filter-priority" value="alta"><span class="pj-square"></span></label>
                                <label class="pj-check-row"><span class="pj-pri-label"><i class="pj-pri-dot is-orange"></i> Media</span><input type="checkbox" class="js-filter-priority" value="media"><span class="pj-square"></span></label>
                                <label class="pj-check-row"><span class="pj-pri-label"><i class="pj-pri-dot is-green"></i> Baja</span><input type="checkbox" class="js-filter-priority" value="baja"><span class="pj-square"></span></label>
                                <label class="pj-check-row"><span class="pj-pri-label"><i class="pj-pri-dot is-gray"></i> Normal</span><input type="checkbox" class="js-filter-priority" value="normal"><span class="pj-square"></span></label>
                            </div>
                        </div>
                        <div class="pj-filter-section">
                            <div class="pj-filter-title">Rango de fechas</div>
                            <div class="pj-date-grid">
                                <div class="pj-date-input"><input type="date" id="pjFilterDateFrom" placeholder="dd/mm/aaaa"><svg viewBox="0 0 24 24" fill="none" class="pj-date-icon"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
                                <div class="pj-date-input"><input type="date" id="pjFilterDateTo" placeholder="dd/mm/aaaa"><svg viewBox="0 0 24 24" fill="none" class="pj-date-icon"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
                            </div>
                        </div>
                    </div>
                    <div class="pj-pop-divider"></div>
                    <div class="pj-pop-footer">
                        <div class="pj-pop-count" id="pjFilterCount">Filtro: 0</div>
                        <div class="pj-pop-actions">
                            <button type="button" class="pj-btn pj-btn-light" id="pjClearToolbarFilters">Limpiar</button>
                            <button type="button" class="pj-btn pj-btn-primary" id="pjApplyToolbarFilters">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pj-pop-wrap">
                <button type="button" class="pj-btn pj-btn-light js-toggle-pop" data-pop="label-filter-pop" data-tooltip="Filtrar por etiquetas" aria-label="Filtrar por etiquetas">
                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M4 7h16l-5 6v5l-6-3v-2L4 7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    Etiquetas
                </button>

                <div class="pj-popover pj-label-filter-pop" id="label-filter-pop">
                    <div class="pj-pop-head">
                        <span>ETIQUETAS</span>
                        <button type="button" class="pj-link-btn" id="pjClearLabelFilter">Limpiar</button>
                    </div>
                    <div id="pjLabelFilterOptions"></div>
                </div>
            </div>

            <a href="{{ route('projects.index', $activeQuery) }}"
               class="pj-btn {{ $isArchiveMode ? 'pj-btn-light' : 'pj-btn-primary' }} pj-btn-icon-only"
               title="Proyectos activos" data-tooltip="Ver proyectos activos" aria-label="Ver proyectos activos">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </a>

            <a href="{{ route('projects.index', $archiveQuery) }}"
               class="pj-btn {{ $isArchiveMode ? 'pj-btn-primary' : 'pj-btn-light' }} pj-btn-icon-only"
               title="Mostrar archivados" data-tooltip="Ver proyectos archivados" aria-label="Ver proyectos archivados">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M3 7h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 7h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 4h8v3H8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </a>

            <button type="button" class="pj-btn pj-btn-light pj-btn-icon-only" title="Limpiar filtros" id="pjClearAllToolbarFilters" data-tooltip="Limpiar filtros, búsqueda y orden" aria-label="Limpiar filtros, búsqueda y orden">
                <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M4 5h16l-6 7v6l-4-2v-4L4 5Zm14-1v3m-2-1.5h4" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>

    <div class="pj-view-transition">
        @if($currentView === 'list')
            <div class="pj-list-wrap">
                <div class="pj-list-head">
                    <div class="pj-col pj-col-project">
                        Proyecto
                        <svg viewBox="0 0 24 24" fill="none" class="pj-sort-mini"><path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="pj-col pj-col-label">Etiqueta</div>
                    <div class="pj-col pj-col-status">Estado</div>
                    <div class="pj-col pj-col-priority">Prioridad</div>
                    <div class="pj-col pj-col-date">Fecha de inicio</div>
                    <div class="pj-col pj-col-assigned">Asignado</div>
                    <div class="pj-col pj-col-star">
                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6"/></svg>
                    </div>
                    <div class="pj-col pj-col-options">Opciones</div>
                </div>

                <div class="pj-list-body">
                    @foreach($columns as $column)
                        @php
                            $tone = $toneStyles[$column['color']] ?? $toneStyles['gray'];
                            $isExpanded = in_array($column['id'], $openColumns, true) || ($hasActiveSearch && ($column['count'] ?? 0) > 0);
                        @endphp

                        <div class="pj-group" data-group-id="{{ $column['id'] }}">
                            <div class="pj-group-row" style="background: {{ $tone['bg'] }};">
                                <div class="pj-col pj-col-project">
                                    <label class="pj-group-check pj-group-check-master">
                                        <input type="checkbox" class="js-select-column" data-column-id="{{ $column['id'] }}">
                                        <span></span>
                                    </label>
                                    <button type="button" class="pj-group-arrow js-toggle-group" data-id="{{ $column['id'] }}" aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon pj-group-chevron"><path d="M10 8l4 4-4 4" stroke="{{ $tone['text'] }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <span class="pj-group-title">{{ $column['name'] }}</span>
                                    <span class="pj-group-count">({{ $column['count'] }})</span>
                                    @if($column['name'] === 'Análisis de Bases')
                                        <button type="button" class="pj-inline-add">+</button>
                                    @endif
                                </div>
                                <div class="pj-col pj-col-label"></div>
                                <div class="pj-col pj-col-status"></div>
                                <div class="pj-col pj-col-priority"></div>
                                <div class="pj-col pj-col-date"></div>
                                <div class="pj-col pj-col-assigned"></div>
                                <div class="pj-col pj-col-star"></div>
                                <div class="pj-col pj-col-options">
                                    <button type="button"
                                            class="pj-dots-btn js-open-column-menu"
                                            data-column-id="{{ $column['id'] }}"
                                            data-column-name="{{ $column['name'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="pj-group-children {{ $isExpanded ? 'is-open' : '' }}">
                                @if($column['count'] > 0)
                                    @foreach($column['projects'] as $index => $project)
                                        @php
                                            $projectAssignee = $project->assignee ?? null;
                                            $assignedName = $projectAssignee?->name ?? ($project['assigned_name'] ?? ($assignedNames[$project['assigned'] ?? ''] ?? ($project['assigned'] ?? '')));
                                            $assignedEmail = $projectAssignee?->email ?? ($project['assigned_email'] ?? '');
                                            $assignedAvatar = $projectAssignee?->avatar_url ?? '';
                                            $assignedInitial = $project['assigned'] ?? '';
                                            if ($assignedInitial === '' && $assignedName !== '') { $assignedInitial = mb_strtoupper(mb_substr($assignedName, 0, 1, 'UTF-8'), 'UTF-8'); }
                                            if ($assignedInitial === '' && $assignedEmail !== '') { $assignedInitial = mb_strtoupper(mb_substr($assignedEmail, 0, 1, 'UTF-8'), 'UTF-8'); }
                                            $assignedUserId = $project['assigned_to'] ?? ($projectAssignee?->id ?? '');
                                            $priority = $project['priority'] ?? 'Normal';
                                            $status = $statusMap[$column['name']] ?? 'Vigente';
                                            $dotColor = $tone['dot'];
                                            $label = $project['labels'][0] ?? null;
                                            $projectId = $column['id'].'-'.$index;
                                            $projectSlug = $project['slug'] ?? null;
                                            $projectHref = $projectSlug ? route('projects.show', $projectSlug) : null;
                                        @endphp

                                        <div class="pj-item-row js-project-row"
                                             draggable="true"
                                             data-column-id="{{ $column['id'] }}"
                                             data-project-id="{{ $projectId }}"
                                             data-project-slug="{{ $projectSlug }}"
                                             data-project-name="{{ $project['name'] }}"
                                             @if($projectSlug) data-workflow-url="{{ route('projects.workflow-status', $projectSlug) }}" @endif
                                             @if($projectSlug) data-labels-url="{{ route('projects.labels.update', $projectSlug) }}" @endif
                                             @if($projectSlug) data-favorite-url="{{ route('projects.favorite.update', $projectSlug) }}" @endif
                                             @if($projectSlug) data-priority-url="{{ url('/projects/' . $projectSlug . '/priority') }}" @endif
                                             @if($projectSlug) data-assignee-url="{{ url('/projects/' . $projectSlug . '/assignee') }}" @endif
                                             @if($projectSlug) data-update-url="{{ url('/projects/' . $projectSlug . '/quick-update') }}" @endif
                                             @if($projectSlug) data-archive-url="{{ url('/projects/' . $projectSlug . '/archive') }}" @endif
                                             @if($projectSlug) data-restore-url="{{ url('/projects/' . $projectSlug . '/restore') }}" @endif
                                             @if($projectSlug) data-delete-url="{{ url('/projects/' . $projectSlug) }}" @endif
                                             data-project-color="{{ $project['color'] ?? ($dotColor ?: '#22c55e') }}"
                                             data-project-assignee-id="{{ $assignedUserId }}"
                                             data-project-assigned="{{ $assignedInitial }}"
                                             data-project-assigned-name="{{ $assignedName }}"
                                             data-project-assigned-email="{{ $assignedEmail }}"
                                             data-project-assigned-avatar="{{ $assignedAvatar }}"
                                             @if($projectHref) data-href="{{ $projectHref }}" @endif>
                                            <div class="pj-col pj-col-project">
                                                <label class="pj-row-check">
                                                    <input type="checkbox" class="js-project-check" data-column-id="{{ $column['id'] }}" data-project-id="{{ $projectId }}" data-project-name="{{ $project['name'] }}">
                                                    <span></span>
                                                </label>
                                                <span class="pj-item-dot js-project-color-dot" style="background: {{ $project['color'] ?? ($dotColor ?: '#22c55e') }};"></span>
                                                <button type="button" class="pj-drag-btn js-drag-handle" title="Mover">
                                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                                </button>
                                                @if($projectSlug)
                                                    <a href="{{ route('projects.show', $projectSlug) }}" class="pj-item-title" style="color:inherit;text-decoration:none;">{{ $project['name'] }}</a>
                                                @else
                                                    <div class="pj-item-title">{{ $project['name'] }}</div>
                                                @endif
                                            </div>
                                            <div class="pj-col pj-col-label">
                                                <div class="pj-label-cell">
                                                    <div class="pj-label-list js-label-list">
                                                        @foreach(collect($project['labels'] ?? [])->filter()->values() as $projectLabel)
                                                            @php
                                                                $projectLabelStyles = collect($project['label_styles'] ?? []);
                                                                $projectLabelStyle = $projectLabelStyles->get($projectLabel)
                                                                    ?? $projectLabelStyles->get(mb_strtolower($projectLabel, 'UTF-8'))
                                                                    ?? ['bg' => '#ffebeb', 'border' => '#ffcaca', 'text' => '#ff4a4a'];
                                                            @endphp
                                                            <div class="pj-label-pill js-label-pill"
                                                                 data-color="{{ $projectLabelStyle['bg'] ?? '#ffebeb' }}"
                                                                 data-border="{{ $projectLabelStyle['border'] ?? '#ffcaca' }}"
                                                                 data-text="{{ $projectLabelStyle['text'] ?? '#ff4a4a' }}"
                                                                 style="background: {{ $projectLabelStyle['bg'] ?? '#ffebeb' }}; border-color: {{ $projectLabelStyle['border'] ?? '#ffcaca' }}; color: {{ $projectLabelStyle['text'] ?? '#ff4a4a' }};">
                                                                <span class="pj-label-pill-text">{{ $projectLabel }}</span>
                                                                <button type="button" class="pj-label-pill-menu js-open-tag-menu" aria-label="Opciones etiqueta">
                                                                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="pj-tag-add js-open-label-pop" data-project-id="{{ $projectId }}" data-project-name="{{ $project['name'] }}">+ Agregar</button>
                                                </div>
                                            </div>
                                            <div class="pj-col pj-col-status"><span class="pj-status-pill">{{ $status }}</span></div>
                                            <div class="pj-col pj-col-priority">
                                                <button type="button"
                                                        class="pj-priority pj-priority-btn js-open-priority-menu @if(strtolower($priority) === 'alta') is-high @elseif(strtolower($priority) === 'media') is-medium @elseif(strtolower($priority) === 'baja') is-low @else is-normal @endif"
                                                        data-priority="{{ strtolower($priority) }}">
                                                    {{ $priority }}
                                                </button>
                                            </div>
                                            <div class="pj-col pj-col-date">{{ $project['start_date'] }}</div>
                                            <div class="pj-col pj-col-assigned">
                                                <button type="button" class="pj-assigned-btn js-open-assignee-modal">
                                                    <span class="pj-avatar">
                                                        @if($assignedAvatar)
                                                            <img src="{{ $assignedAvatar }}" alt="{{ $assignedName }}">
                                                        @else
                                                            {{ $assignedInitial }}
                                                        @endif
                                                    </span>
                                                    <span class="pj-assigned-name">{{ $assignedName ?: 'Sin asignar' }}</span>
                                                </button>
                                            </div>
                                            <div class="pj-col pj-col-star">
                                                <button type="button" class="pj-star-btn {{ !empty($project['starred']) || !empty($project['favorite']) ? 'is-active' : '' }}" aria-pressed="{{ !empty($project['starred']) || !empty($project['favorite']) ? 'true' : 'false' }}">
                                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6" fill="{{ !empty($project['starred']) || !empty($project['favorite']) ? 'currentColor' : 'none' }}"/></svg>
                                                </button>
                                            </div>
                                            <div class="pj-col pj-col-options">
                                                <button type="button" class="pj-dots-btn js-open-project-menu">
                                                    <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="pj-board" id="projectBoard">
                @foreach($columns as $column)
                    @php
                        $isOpen = in_array($column['id'], $openColumns, true);
                        $toneClass = 'tone-' . $column['color'];
                    @endphp

                    <div class="pj-column {{ $toneClass }} {{ $isOpen ? 'is-open' : 'is-collapsed' }}" data-column-id="{{ $column['id'] }}">
                        <button type="button" class="pj-column-collapsed-btn js-open-column" data-id="{{ $column['id'] }}">
                            <div class="pj-collapsed-title">{{ $column['name'] }}</div>
                            <div class="pj-collapsed-count">({{ $column['count'] }})</div>
                        </button>

                        <div class="pj-column-open">
                            <div class="pj-column-header">
                                <div class="pj-column-header-left">
                                    <label class="pj-group-check pj-group-check-master">
                                        <input type="checkbox" class="js-select-column" data-column-id="{{ $column['id'] }}">
                                        <span></span>
                                    </label>
                                    <h3 class="pj-column-title">{{ $column['name'] }}</h3>
                                    <span class="pj-column-count">({{ $column['count'] }})</span>
                                </div>
                                <div class="pj-column-header-actions">
                                    <button type="button" class="pj-icon-btn js-close-column" title="Colapsar etapa">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>

                                    @if($column['id'] === 'analisis_bases')
                                        <button type="button" class="pj-icon-btn js-column-create-project" title="Nuevo proyecto">
                                            <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                        </button>
                                    @endif

                                    <button type="button"
                                            class="pj-icon-btn js-open-column-menu"
                                            title="Opciones de etapa"
                                            data-column-id="{{ $column['id'] }}"
                                            data-column-name="{{ $column['name'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="pj-column-body">
                                @if($column['count'] === 0)
                                    <div class="pj-empty"><div class="pj-empty-box">Sin proyectos</div></div>
                                @else
                                    <div class="pj-cards">
                                        @foreach($column['projects'] as $index => $project)
                                            @php
                                                $projectAssignee = $project->assignee ?? null;
                                                $assignedName = $projectAssignee?->name ?? ($project['assigned_name'] ?? ($assignedNames[$project['assigned'] ?? ''] ?? ($project['assigned'] ?? '')));
                                                $assignedEmail = $projectAssignee?->email ?? ($project['assigned_email'] ?? '');
                                                $assignedAvatar = $projectAssignee?->avatar_url ?? '';
                                                $assignedInitial = $project['assigned'] ?? '';
                                                if ($assignedInitial === '' && $assignedName !== '') { $assignedInitial = mb_strtoupper(mb_substr($assignedName, 0, 1, 'UTF-8'), 'UTF-8'); }
                                                if ($assignedInitial === '' && $assignedEmail !== '') { $assignedInitial = mb_strtoupper(mb_substr($assignedEmail, 0, 1, 'UTF-8'), 'UTF-8'); }
                                                $assignedUserId = $project['assigned_to'] ?? ($projectAssignee?->id ?? '');
                                                $label = $project['labels'][0] ?? null;
                                                $projectId = $column['id'].'-card-'.$index;
                                                $projectSlug = $project['slug'] ?? null;
                                                $projectHref = $projectSlug ? route('projects.show', $projectSlug) : null;
                                            @endphp

                                            <div class="pj-card js-project-row"
                                                 draggable="true"
                                                 data-column-id="{{ $column['id'] }}"
                                                 data-project-id="{{ $projectId }}"
                                                 data-project-slug="{{ $projectSlug }}"
                                                 data-project-name="{{ $project['name'] }}"
                                                 @if($projectSlug) data-workflow-url="{{ route('projects.workflow-status', $projectSlug) }}" @endif
                                                 @if($projectSlug) data-labels-url="{{ route('projects.labels.update', $projectSlug) }}" @endif
                                                 @if($projectSlug) data-favorite-url="{{ route('projects.favorite.update', $projectSlug) }}" @endif
                                                 @if($projectSlug) data-priority-url="{{ url('/projects/' . $projectSlug . '/priority') }}" @endif
                                                 @if($projectSlug) data-assignee-url="{{ url('/projects/' . $projectSlug . '/assignee') }}" @endif
                                                 @if($projectSlug) data-update-url="{{ url('/projects/' . $projectSlug . '/quick-update') }}" @endif
                                                 @if($projectSlug) data-archive-url="{{ url('/projects/' . $projectSlug . '/archive') }}" @endif
                                                 @if($projectSlug) data-restore-url="{{ url('/projects/' . $projectSlug . '/restore') }}" @endif
                                                 @if($projectSlug) data-delete-url="{{ url('/projects/' . $projectSlug) }}" @endif
                                                 data-project-color="{{ $project['color'] ?? ($dotColor ?? '#22c55e') }}"
                                                 data-project-assignee-id="{{ $assignedUserId }}"
                                                 data-project-assigned="{{ $assignedInitial }}"
                                                 data-project-assigned-name="{{ $assignedName }}"
                                                 data-project-assigned-email="{{ $assignedEmail }}"
                                                 data-project-assigned-avatar="{{ $assignedAvatar }}"
                                                 @if($projectHref) data-href="{{ $projectHref }}" @endif>
                                                <div class="pj-card-top">
                                                    <div class="pj-card-main">
                                                        <label class="pj-check">
                                                            <input type="checkbox" class="js-project-check" data-column-id="{{ $column['id'] }}" data-project-id="{{ $projectId }}" data-project-name="{{ $project['name'] }}">
                                                            <span></span>
                                                        </label>
                                                        <span class="pj-dot js-project-color-dot" style="background: {{ $project['color'] ?? ($dotColor ?: '#22c55e') }};"></span>
                                                        @if($projectSlug)
                                                            <a href="{{ route('projects.show', $projectSlug) }}" class="pj-card-title" style="color:inherit;text-decoration:none;">{{ $project['name'] }}</a>
                                                        @else
                                                            <div class="pj-card-title">{{ $project['name'] }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="pj-card-actions">
                                                        <button type="button" class="pj-icon-btn pj-star-btn {{ !empty($project['starred']) || !empty($project['favorite']) ? 'is-active' : '' }}" title="Favorito" aria-pressed="{{ !empty($project['starred']) || !empty($project['favorite']) ? 'true' : 'false' }}">
                                                            <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6" fill="{{ !empty($project['starred']) || !empty($project['favorite']) ? 'currentColor' : 'none' }}"/></svg>
                                                        </button>
                                                        <button type="button" class="pj-icon-btn js-open-project-menu" title="Más">
                                                            <svg viewBox="0 0 24 24" fill="none" class="pj-icon"><path d="M12 5h.01M12 12h.01M12 19h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="pj-divider"></div>

                                                <div class="pj-card-meta">
                                                    <div class="pj-meta-row pj-meta-row-labels">
                                                        <div class="pj-meta-label">Etiquetas</div>
                                                        <div class="pj-label-list js-label-list">
                                                            @foreach(collect($project['labels'] ?? [])->filter()->values() as $projectLabel)
                                                                @php
                                                                    $projectLabelStyles = collect($project['label_styles'] ?? []);
                                                                    $projectLabelStyle = $projectLabelStyles->get($projectLabel)
                                                                        ?? $projectLabelStyles->get(mb_strtolower($projectLabel, 'UTF-8'))
                                                                        ?? ['bg' => '#ffebeb', 'border' => '#ffcaca', 'text' => '#ff4a4a'];
                                                                @endphp
                                                                <div class="pj-label-pill js-label-pill"
                                                                     data-color="{{ $projectLabelStyle['bg'] ?? '#ffebeb' }}"
                                                                     data-border="{{ $projectLabelStyle['border'] ?? '#ffcaca' }}"
                                                                     data-text="{{ $projectLabelStyle['text'] ?? '#ff4a4a' }}"
                                                                     style="background: {{ $projectLabelStyle['bg'] ?? '#ffebeb' }}; border-color: {{ $projectLabelStyle['border'] ?? '#ffcaca' }}; color: {{ $projectLabelStyle['text'] ?? '#ff4a4a' }};">
                                                                    <span class="pj-label-pill-text">{{ $projectLabel }}</span>
                                                                    <button type="button" class="pj-label-pill-menu js-open-tag-menu" aria-label="Opciones etiqueta">
                                                                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" class="pj-tag-add js-open-label-pop" data-project-id="{{ $projectId }}" data-project-name="{{ $project['name'] }}">+ Agregar</button>
                                                    </div>

                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Prioridad:</div>
                                                        <button type="button"
                                                                class="pj-priority pj-priority-btn js-open-priority-menu @if(strtolower($project['priority'] ?? 'Normal') === 'alta') is-high @elseif(strtolower($project['priority'] ?? 'Normal') === 'media') is-medium @elseif(strtolower($project['priority'] ?? 'Normal') === 'baja') is-low @else is-normal @endif"
                                                                data-priority="{{ strtolower($project['priority'] ?? 'Normal') }}">
                                                            {{ $project['priority'] ?? 'Normal' }}
                                                        </button>
                                                    </div>

                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Fecha de inicio:</div>
                                                        <div class="pj-meta-value">{{ $project['start_date'] }}</div>
                                                    </div>

                                                    <div class="pj-meta-row">
                                                        <div class="pj-meta-label">Asignado:</div>
                                                        <button type="button" class="pj-avatar-btn js-open-assignee-modal" title="Asignar usuario">
                                                            <span class="pj-avatar">
                                                                @if($assignedAvatar)
                                                                    <img src="{{ $assignedAvatar }}" alt="{{ $assignedName }}">
                                                                @else
                                                                    {{ $assignedInitial }}
                                                                @endif
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="pj-bulkbar" id="pjBulkbar" aria-hidden="true">
    <div class="pj-bulkbar-inner">
        <div class="pj-bulkbar-count">
            <span id="pjSelectedCount">0</span> Proyectos seleccionados
            <button type="button" class="pj-bulkbar-clear" id="pjClearSelection" aria-label="Limpiar selección">
                <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="pj-bulkbar-divider"></div>
        <div class="pj-bulkbar-actions">
            <button type="button" class="pj-bulk-action" id="pjBulkLabelsBtn" data-bulk-action="labels">Etiquetas</button>
            <button type="button" class="pj-bulk-action" data-bulk-action="favorite" id="pjBulkFavoriteBtn">Favoritos</button>
            <button type="button" class="pj-bulk-action" data-bulk-action="color">Color</button>
            <button type="button" class="pj-bulk-action" data-bulk-action="stage">Mover a etapa</button>
            <button type="button" class="pj-bulk-action" data-bulk-action="priority">Prioridad</button>
            <button type="button" class="pj-bulk-action" data-bulk-action="assignee">Asignar</button>
            <button type="button" class="pj-bulk-action" data-bulk-action="archive">Archivar</button>
            <button type="button" class="pj-bulk-action is-danger" data-bulk-action="delete">Eliminar</button>
        </div>
    </div>
</div>

<div class="pj-label-popover" id="pjLabelPopover" aria-hidden="true">
    <div class="pj-label-popover-card">
        <div class="pj-label-search">
            <svg viewBox="0 0 24 24" fill="none"><path d="M21 21L16.65 16.65M10.8 18.6a7.8 7.8 0 1 0 0-15.6 7.8 7.8 0 0 0 0 15.6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <input type="text" id="pjLabelSearchInput" placeholder="Buscar etiqueta">
        </div>
        <button type="button" class="pj-label-create" id="pjCreateLabelBtn"><span>+</span> Crear "<strong id="pjCreateLabelText">Etiqueta</strong>"</button>
        <div class="pj-label-options" id="pjLabelOptions"></div>
        <button type="button" class="pj-bulk-apply-btn" id="pjBulkLabelApplyBtn">Aplicar</button>
    </div>
</div>

<div class="pj-tag-menu" id="pjTagMenu" aria-hidden="true">
    <div class="pj-tag-menu-card">
        <div class="pj-tag-menu-head">
            <span>Color de etiqueta</span>
            <button type="button" class="pj-tag-menu-close" id="pjCloseTagMenu">
                <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="pj-color-grid" id="pjColorGrid">
            <button type="button" class="pj-color-dot" data-bg="#dbeafe" data-border="#93c5fd" data-text="#2563eb" style="background:#3b82f6"></button>
            <button type="button" class="pj-color-dot" data-bg="#d1fae5" data-border="#86efac" data-text="#059669" style="background:#10b981"></button>
            <button type="button" class="pj-color-dot" data-bg="#fef3c7" data-border="#fde68a" data-text="#ca8a04" style="background:#f59e0b"></button>
            <button type="button" class="pj-color-dot" data-bg="#fee2e2" data-border="#fecaca" data-text="#ef4444" style="background:#ef4444"></button>
            <button type="button" class="pj-color-dot" data-bg="#ede9fe" data-border="#c4b5fd" data-text="#7c3aed" style="background:#8b5cf6"></button>
            <button type="button" class="pj-color-dot" data-bg="#fce7f3" data-border="#f9a8d4" data-text="#db2777" style="background:#ec4899"></button>
            <button type="button" class="pj-color-dot" data-bg="#cffafe" data-border="#67e8f9" data-text="#0891b2" style="background:#06b6d4"></button>
            <button type="button" class="pj-color-dot" data-bg="#ecfccb" data-border="#bef264" data-text="#65a30d" style="background:#84cc16"></button>
            <button type="button" class="pj-color-dot" data-bg="#ffedd5" data-border="#fdba74" data-text="#ea580c" style="background:#f97316"></button>
            <button type="button" class="pj-color-dot" data-bg="#e0e7ff" data-border="#a5b4fc" data-text="#4f46e5" style="background:#6366f1"></button>
            <button type="button" class="pj-color-dot" data-bg="#ccfbf1" data-border="#5eead4" data-text="#0f766e" style="background:#14b8a6"></button>
            <button type="button" class="pj-color-dot" data-bg="#f3e8ff" data-border="#d8b4fe" data-text="#9333ea" style="background:#a855f7"></button>
        </div>
        <div class="pj-tag-menu-actions">
            <button type="button" class="pj-tag-menu-action is-danger" id="pjDeleteTagBtn">Eliminar</button>
        </div>
    </div>
</div>

<div class="pj-project-menu" id="pjProjectMenu" aria-hidden="true">
    <div class="pj-project-menu-card">
        <button type="button" class="pj-project-menu-item" data-action="rename">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 17.25V21h3.75L18.81 8.94l-3.75-3.75L3 17.25Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m14.06 4.19 3.75 3.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span>Cambiar nombre</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="assignee">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="10" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M21 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span>Asignar usuario</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="move-stage">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span>Mover a otra etapa</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="favorite" id="pjProjectMenuFavoriteAction">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.7"/></svg></span>
            <span class="pj-project-menu-favorite-text">Quitar de favoritos</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="labels">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M20 10.5 12.5 3H5a2 2 0 0 0-2 2v7.5l7.5 7.5a2.12 2.12 0 0 0 3 0l6.5-6.5a2.12 2.12 0 0 0 0-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1" fill="currentColor"/></svg></span>
            <span>Editar etiquetas</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="priority">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 17h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
            <span>Editar prioridad</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="copy-link">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 1 0-7.07-7.07L11 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 1 0 7.07 7.07L13 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span>Copiar link</span>
        </button>
        <button type="button" class="pj-project-menu-item" data-action="color">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3c4.97 0 9 3.58 9 8 0 2.18-1.68 3.95-3.75 3.95h-1.12a1.13 1.13 0 0 0-1.13 1.13c0 .35.16.68.43.9.54.43.87 1.06.87 1.76 0 1.6-1.43 2.88-3.2 2.88C7.55 21.62 3 17.44 3 12.3 3 7.16 7.03 3 12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7.5" cy="11" r="1" fill="currentColor"/><circle cx="10.5" cy="7.5" r="1" fill="currentColor"/><circle cx="15.5" cy="8" r="1" fill="currentColor"/><circle cx="17" cy="12" r="1" fill="currentColor"/></svg></span>
            <span>Cambiar color</span>
        </button>
        <div class="pj-project-menu-divider"></div>
        <button type="button" class="pj-project-menu-item" data-action="{{ $isArchiveMode ? 'restore' : 'archive' }}">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 7h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 7h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 4h8v3H8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
            <span>{{ $isArchiveMode ? 'Activar proyecto' : 'Archivar' }}</span>
        </button>
        <button type="button" class="pj-project-menu-item is-danger" data-action="delete">
            <span class="pj-project-menu-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 6h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 6V4h8v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M18 6l-1 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span>Eliminar</span>
        </button>
    </div>
</div>

<div class="pj-project-color-popover" id="pjProjectColorPopover" aria-hidden="true">
    <div class="pj-project-color-card">
        <div class="pj-project-color-head">Cambiar color</div>
        <div class="pj-project-color-grid">
            <button type="button" class="pj-project-color-dot" data-color="#22c55e" style="background:#22c55e"></button>
            <button type="button" class="pj-project-color-dot" data-color="#3b82f6" style="background:#3b82f6"></button>
            <button type="button" class="pj-project-color-dot" data-color="#f59e0b" style="background:#f59e0b"></button>
            <button type="button" class="pj-project-color-dot" data-color="#ef4444" style="background:#ef4444"></button>
            <button type="button" class="pj-project-color-dot" data-color="#8b5cf6" style="background:#8b5cf6"></button>
            <button type="button" class="pj-project-color-dot" data-color="#06b6d4" style="background:#06b6d4"></button>
            <button type="button" class="pj-project-color-dot" data-color="#ec4899" style="background:#ec4899"></button>
            <button type="button" class="pj-project-color-dot" data-color="#64748b" style="background:#64748b"></button>
        </div>
    </div>
</div>

<div class="pj-stage-modal-backdrop" id="pjStageModalBackdrop" aria-hidden="true">
    <div class="pj-stage-modal" role="dialog" aria-modal="true" aria-labelledby="pjStageModalTitle">
        <div class="pj-stage-modal-head">
            <h3 class="pj-stage-modal-title" id="pjStageModalTitle">Seleccionar etapa</h3>
            <button type="button" class="pj-stage-modal-close" id="pjStageModalClose" aria-label="Cerrar">×</button>
        </div>

        <div class="pj-stage-modal-body">
            <select class="pj-stage-select" id="pjStageSelect">
                <option value="analisis_bases">Análisis de Bases</option>
                <option value="revision">Revisión</option>
                <option value="participa">Participa</option>
                <option value="junta_aclaraciones">Junta de Aclaraciones</option>
                <option value="armado_propuesta">Armado de Propuesta</option>
                <option value="entrega">Entrega</option>
                <option value="no_participa">No participa</option>
                <option value="ganado">Ganado</option>
                <option value="perdido">Perdido</option>
                <option value="desierta">Desierta</option>
            </select>
        </div>

        <div class="pj-stage-modal-actions">
            <button type="button" class="pj-stage-modal-btn is-ghost" id="pjStageModalCancel">Cancelar</button>
            <button type="button" class="pj-stage-modal-btn is-primary" id="pjStageModalSave">Guardar</button>
        </div>
    </div>
</div>


<div class="pj-priority-popover" id="pjPriorityPopover" aria-hidden="true">
    <button type="button" class="pj-priority-option" data-priority="alta"><span class="pj-priority-dot is-high"></span>Alta</button>
    <button type="button" class="pj-priority-option" data-priority="media"><span class="pj-priority-dot is-medium"></span>Media</button>
    <button type="button" class="pj-priority-option" data-priority="baja"><span class="pj-priority-dot is-low"></span>Baja</button>
    <button type="button" class="pj-priority-option" data-priority="normal"><span class="pj-priority-dot is-normal"></span>Normal</button>
</div>

<div class="pj-assignee-modal-backdrop" id="pjAssigneeModalBackdrop" aria-hidden="true">
    <div class="pj-assignee-modal" role="dialog" aria-modal="true" aria-labelledby="pjAssigneeTitle">
        <div class="pj-assignee-head">
            <h3 class="pj-assignee-title" id="pjAssigneeTitle">Asignar usuario</h3>
            <button type="button" class="pj-assignee-close" id="pjAssigneeClose" aria-label="Cerrar">×</button>
        </div>
        <div class="pj-assignee-list" id="pjAssigneeList">
            @forelse(($assignableUsers ?? collect()) as $user)
                <button type="button"
                        class="pj-assignee-option"
                        data-user-id="{{ $user['id'] }}"
                        data-assigned="{{ $user['initial'] }}"
                        data-name="{{ $user['name'] }}"
                        data-email="{{ $user['email'] }}"
                        data-avatar="{{ $user['avatar_url'] }}">
                    <span class="pj-assignee-avatar">
                        @if(!empty($user['avatar_url']))
                            <img src="{{ $user['avatar_url'] }}" alt="{{ $user['name'] }}">
                        @else
                            {{ $user['initial'] }}
                        @endif
                    </span>
                    <span class="pj-assignee-info">
                        <span class="pj-assignee-name">{{ $user['name'] }}</span>
                        <span class="pj-assignee-email">{{ $user['email'] }}</span>
                    </span>
                </button>
            @empty
                <div class="pj-assignee-empty">No hay usuarios disponibles para asignar.</div>
            @endforelse
        </div>
        <div class="pj-assignee-actions">
            <button type="button" class="pj-btn pj-btn-light" id="pjAssigneeCancel">Cancelar</button>
            <button type="button" class="pj-btn pj-btn-primary" id="pjAssigneeSave">Asignar</button>
        </div>
    </div>
</div>


<div class="pj-column-menu" id="pjColumnMenu" aria-hidden="true">
    <div class="pj-column-menu-card">
        <button type="button" class="pj-column-menu-item is-primary" data-action="collapse">
            <span class="pj-column-menu-icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span>Colapsar etapa</span>
        </button>
        <button type="button" class="pj-column-menu-item" data-action="toggle-selection">
            <span class="pj-column-menu-icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="pj-column-menu-selection-text">Seleccionar proyectos</span>
        </button>
        <button type="button" class="pj-column-menu-item" data-action="{{ $isArchiveMode ? 'restore-all' : 'archive-all' }}">
            <span class="pj-column-menu-icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 7h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 7h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 4h8v3H8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </span>
            <span>{{ $isArchiveMode ? 'Activar todos los proyectos' : 'Archivar todos los proyectos' }}</span>
        </button>
    </div>
</div>

<div class="pj-toast-stack" id="pjToastStack" aria-live="polite" aria-atomic="true"></div>

<div class="pj-confirm-backdrop" id="pjConfirmBackdrop" aria-hidden="true">
    <div class="pj-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="pjConfirmTitle">
        <div class="pj-confirm-head">
            <div>
                <h3 class="pj-confirm-title" id="pjConfirmTitle">Confirmar acción</h3>
                <p class="pj-confirm-message" id="pjConfirmMessage">¿Deseas continuar?</p>
            </div>
            <button type="button" class="pj-confirm-close" id="pjConfirmClose" aria-label="Cerrar">×</button>
        </div>
        <div class="pj-confirm-actions">
            <button type="button" class="pj-confirm-btn is-ghost" id="pjConfirmCancel">Cancelar</button>
            <button type="button" class="pj-confirm-btn is-primary" id="pjConfirmAccept">Continuar</button>
        </div>
    </div>
</div>

<div class="pj-prompt-backdrop" id="pjPromptBackdrop" aria-hidden="true">
    <div class="pj-prompt-modal" role="dialog" aria-modal="true" aria-labelledby="pjPromptTitle">
        <div class="pj-prompt-head">
            <div>
                <h3 class="pj-prompt-title" id="pjPromptTitle">Editar</h3>
                <p class="pj-prompt-message" id="pjPromptMessage">Escribe el nuevo valor.</p>
            </div>
            <button type="button" class="pj-prompt-close" id="pjPromptClose" aria-label="Cerrar">×</button>
        </div>
        <div class="pj-prompt-body">
            <input type="text" class="pj-prompt-input" id="pjPromptInput">
        </div>
        <div class="pj-prompt-actions">
            <button type="button" class="pj-prompt-btn is-ghost" id="pjPromptCancel">Cancelar</button>
            <button type="button" class="pj-prompt-btn is-primary" id="pjPromptAccept">Guardar</button>
        </div>
    </div>
</div>

{{-- ══ MODAL CREAR PROYECTO ══ --}}
<div class="pj-modal-backdrop" id="projectModalBackdrop">
    <div class="pj-modal" id="projectModal" role="dialog" aria-modal="true" aria-labelledby="projectModalTitle">
        <div class="pj-modal-head">
            <div>
                <h2 class="pj-modal-title" id="projectModalTitle">Nuevo proyecto</h2>
                <p class="pj-modal-subtitle">La organización empieza aquí: asigna un nombre a tu licitación y comencemos.</p>
            </div>
            <button type="button" class="pj-modal-close" id="closeProjectModal" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" class="pj-modal-close-icon"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>

        <div class="pj-modal-body">
            <form class="pj-modal-form" id="projectCreateForm" method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="pj-modal-section">
                    <input type="text" class="pj-input pj-input-main" name="name" placeholder="Mi proyecto">
                </div>

                <div class="pj-modal-row-top">
                    <div class="pj-inline-field">
                        <label class="pj-inline-label">Fecha inicio</label>
                        <div class="pj-date-inline">
                            <input type="date" class="pj-inline-input" name="start_date" value="{{ now()->format('Y-m-d') }}">
                            <svg viewBox="0 0 24 24" fill="none" class="pj-inline-icon"><path d="M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </div>
                    </div>
                    <div class="pj-inline-field pj-inline-field-color">
                        <label class="pj-inline-label">Color</label>
                        <input type="color" name="color" class="pj-color-input" value="#2563eb">
                    </div>
                    <div class="pj-inline-field pj-inline-field-fav">
                        <label class="pj-favorite-toggle">
                            <input type="checkbox" name="favorite">
                            <span class="pj-favorite-box">
                                <svg viewBox="0 0 24 24" fill="none" class="pj-favorite-star"><path d="M12 3.8l2.57 5.2 5.74.83-4.15 4.05.98 5.72L12 16.88 6.86 19.6l.98-5.72L3.69 9.83l5.74-.83L12 3.8Z" stroke="currentColor" stroke-width="1.6"/></svg>
                            </span>
                            <span>Favorito</span>
                        </label>
                    </div>
                </div>

                <div class="pj-upload-box" id="projectDropzone">
                    <input type="file" name="files[]" id="projectDocuments" class="pj-file-input" multiple accept=".pdf,.doc,.docx">
                    <div class="pj-upload-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" class="pj-upload-icon"><path d="M7 16a4 4 0 0 1-.3-7.99A5.5 5.5 0 0 1 17 6.5a4.5 4.5 0 0 1 1 8.89M12 21V10m0 0-4 4m4-4 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="pj-upload-title">Carga aquí los documentos de la <span>Licitación</span></div>
                    <div class="pj-upload-subtitle">Arrastra tus documentos aquí o haz clic para seleccionarlos</div>
                    <div class="pj-upload-note">Puedes subir máximo <strong>9 archivos</strong> en formato .docx o .pdf. Los archivos .xlsx no están permitidos.</div>
                </div>

                <div class="pj-selected-files">
                    <div id="projectSelectedFiles" class="pj-selected-list">
                        <div class="pj-selected-empty">No hay archivos seleccionados</div>
                    </div>
                </div>

                <div class="pj-create-no-docs">
                    <label class="pj-checkbox-line">
                        <input type="checkbox" name="without_documents" id="withoutDocuments">
                        <span class="pj-checkbox-box"></span>
                        <span>Crear proyecto sin documentos</span>
                        <span class="pj-help-dot">?</span>
                    </label>
                </div>

                <div class="pj-modal-actions">
                    <button type="submit" class="pj-btn pj-btn-primary pj-btn-submit">Comenzar</button>
                    <button type="button" class="pj-btn pj-btn-ghost" id="cancelProjectModal">Cancelar</button>
                </div>

                <div id="pjUploadStatus" class="pj-upload-status" style="display:none;margin-top:14px;padding:12px;border-radius:10px;background:#f0f7ff;border:1px solid #b6d6ff;color:#1e40af;font-size:.88rem;font-weight:600;text-align:center;"></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ============ UI PROFESIONAL: TOASTS Y MODALES ============
    const toastStack = document.getElementById('pjToastStack');

    function showToast(message, type = 'info', title = '') {
        if (!toastStack) return;

        const safeType = ['success', 'error', 'info'].includes(type) ? type : 'info';
        const toast = document.createElement('div');
        toast.className = `pj-toast is-${safeType}`;

        const icon = safeType === 'success' ? '✓' : (safeType === 'error' ? '!' : 'i');
        const defaultTitle = safeType === 'success' ? 'Listo' : (safeType === 'error' ? 'No se pudo completar' : 'Aviso');

        toast.innerHTML = `
            <div class="pj-toast-icon">${icon}</div>
            <div class="pj-toast-body">
                <p class="pj-toast-title">${title || defaultTitle}</p>
                <p class="pj-toast-message">${message || ''}</p>
            </div>
            <button type="button" class="pj-toast-close" aria-label="Cerrar">×</button>
        `;

        toastStack.appendChild(toast);

        const close = () => {
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 190);
        };

        toast.querySelector('.pj-toast-close')?.addEventListener('click', close);
        setTimeout(close, safeType === 'error' ? 5200 : 3400);
    }

    const confirmBackdrop = document.getElementById('pjConfirmBackdrop');
    const confirmTitle = document.getElementById('pjConfirmTitle');
    const confirmMessage = document.getElementById('pjConfirmMessage');
    const confirmClose = document.getElementById('pjConfirmClose');
    const confirmCancel = document.getElementById('pjConfirmCancel');
    const confirmAccept = document.getElementById('pjConfirmAccept');

    let confirmResolver = null;

    function openConfirmModal(options = {}) {
        if (!confirmBackdrop) return Promise.resolve(false);

        confirmTitle.textContent = options.title || 'Confirmar acción';
        confirmMessage.textContent = options.message || '¿Deseas continuar?';
        confirmAccept.textContent = options.acceptText || 'Continuar';
        confirmCancel.textContent = options.cancelText || 'Cancelar';
        confirmAccept.classList.toggle('is-danger', options.variant === 'danger');
        confirmAccept.classList.toggle('is-primary', options.variant !== 'danger');

        confirmBackdrop.classList.add('is-open');
        confirmBackdrop.setAttribute('aria-hidden', 'false');

        setTimeout(() => confirmAccept?.focus(), 30);

        return new Promise(resolve => {
            confirmResolver = resolve;
        });
    }

    function closeConfirmModal(result = false) {
        if (!confirmBackdrop) return;
        confirmBackdrop.classList.remove('is-open');
        confirmBackdrop.setAttribute('aria-hidden', 'true');

        if (confirmResolver) {
            confirmResolver(result);
            confirmResolver = null;
        }
    }

    confirmAccept?.addEventListener('click', () => closeConfirmModal(true));
    [confirmClose, confirmCancel].forEach(btn => btn?.addEventListener('click', () => closeConfirmModal(false)));
    confirmBackdrop?.addEventListener('click', event => {
        if (event.target === confirmBackdrop) closeConfirmModal(false);
    });

    const promptBackdrop = document.getElementById('pjPromptBackdrop');
    const promptTitle = document.getElementById('pjPromptTitle');
    const promptMessage = document.getElementById('pjPromptMessage');
    const promptClose = document.getElementById('pjPromptClose');
    const promptCancel = document.getElementById('pjPromptCancel');
    const promptAccept = document.getElementById('pjPromptAccept');
    const promptInput = document.getElementById('pjPromptInput');

    let promptResolver = null;

    function openPromptModal(options = {}) {
        if (!promptBackdrop) return Promise.resolve(null);

        promptTitle.textContent = options.title || 'Editar';
        promptMessage.textContent = options.message || 'Escribe el nuevo valor.';
        promptAccept.textContent = options.acceptText || 'Guardar';
        promptCancel.textContent = options.cancelText || 'Cancelar';
        promptInput.value = options.value || '';

        promptBackdrop.classList.add('is-open');
        promptBackdrop.setAttribute('aria-hidden', 'false');

        setTimeout(() => {
            promptInput?.focus();
            promptInput?.select();
        }, 30);

        return new Promise(resolve => {
            promptResolver = resolve;
        });
    }

    function closePromptModal(result = null) {
        if (!promptBackdrop) return;
        promptBackdrop.classList.remove('is-open');
        promptBackdrop.setAttribute('aria-hidden', 'true');

        if (promptResolver) {
            promptResolver(result);
            promptResolver = null;
        }
    }

    promptAccept?.addEventListener('click', () => closePromptModal(promptInput?.value ?? ''));
    [promptClose, promptCancel].forEach(btn => btn?.addEventListener('click', () => closePromptModal(null)));
    promptBackdrop?.addEventListener('click', event => {
        if (event.target === promptBackdrop) closePromptModal(null);
    });

    promptInput?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            closePromptModal(promptInput.value);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        if (promptBackdrop?.classList.contains('is-open')) closePromptModal(null);
        if (confirmBackdrop?.classList.contains('is-open')) closeConfirmModal(false);
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const board = document.getElementById('projectBoard');
    if (board) {
        board.addEventListener('click', function (e) {
            const openBtn = e.target.closest('.js-open-column');
            const closeBtn = e.target.closest('.js-close-column');
            if (openBtn) {
                const column = openBtn.closest('.pj-column');
                if (!column) return;
                column.classList.remove('is-collapsed');
                column.classList.add('is-open');
                syncOpenedColumnsInUrl();
                return;
            }
            if (closeBtn) {
                const column = closeBtn.closest('.pj-column');
                if (!column) return;
                column.classList.remove('is-open');
                column.classList.add('is-collapsed');
                syncOpenedColumnsInUrl();
            }
        });
        function syncOpenedColumnsInUrl() {
            const openIds = Array.from(document.querySelectorAll('.pj-column.is-open')).map(col => col.getAttribute('data-column-id')).filter(Boolean);
            const url = new URL(window.location.href);
            if (openIds.length) url.searchParams.set('open', openIds.join(','));
            else url.searchParams.delete('open');
            history.replaceState({}, '', url.toString());
        }

        board.addEventListener('click', function (event) {
            const createBtn = event.target.closest('.js-column-create-project');
            if (!createBtn) return;
            event.preventDefault();
            event.stopPropagation();
            document.getElementById('openProjectModal')?.click();
        });
    }

    // ============ MENU DE ETAPAS ============
    const columnMenu = document.getElementById('pjColumnMenu');
    let activeColumnMenuColumn = null;
    let activeColumnMenuAnchor = null;

    function openColumnMenu(anchor) {
        const column = anchor?.closest('.pj-column') || anchor?.closest('.pj-group');
        if (!column || !columnMenu) return;

        activeColumnMenuColumn = column;
        activeColumnMenuAnchor = anchor;

        const selectedInColumn = Array.from(column.querySelectorAll('.js-project-check')).filter(input => input.checked).length;
        const selectionText = columnMenu.querySelector('.pj-column-menu-selection-text');
        if (selectionText) {
            selectionText.textContent = selectedInColumn > 0 ? 'Deseleccionar proyectos' : 'Seleccionar proyectos';
        }

        const group = anchor.closest('.pj-group');
        const groupChildren = group?.querySelector('.pj-group-children');
        const columnIsOpen = groupChildren
            ? groupChildren.classList.contains('is-open')
            : column.classList.contains('is-open');

        const collapseText = columnMenu.querySelector('[data-action="collapse"] span:last-child');
        if (collapseText) {
            collapseText.textContent = columnIsOpen ? 'Colapsar etapa' : 'Expandir etapa';
        }

        placeFloating(columnMenu, anchor, 8);
        columnMenu.classList.add('is-open');
        columnMenu.setAttribute('aria-hidden', 'false');
    }

    function closeColumnMenu() {
        activeColumnMenuColumn = null;
        activeColumnMenuAnchor = null;
        columnMenu?.classList.remove('is-open');
        columnMenu?.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-open-column-menu');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();
        openColumnMenu(btn);
    });

    columnMenu?.addEventListener('click', async function (event) {
        const item = event.target.closest('.pj-column-menu-item');
        if (!item || !activeColumnMenuColumn) return;

        event.preventDefault();
        event.stopPropagation();

        const column = activeColumnMenuColumn;
        const action = item.dataset.action;

        if (action === 'collapse') {
            const group = column.classList.contains('pj-group') ? column : activeColumnMenuAnchor?.closest('.pj-group');

            if (group) {
                const children = group.querySelector('.pj-group-children');
                const btn = group.querySelector('.js-toggle-group');
                const open = children?.classList.contains('is-open');

                children?.classList.toggle('is-open', !open);
                btn?.setAttribute('aria-expanded', open ? 'false' : 'true');

                if (typeof syncOpenedGroupsInUrl === 'function') syncOpenedGroupsInUrl();
            } else {
                const open = column.classList.contains('is-open');
                column.classList.toggle('is-open', !open);
                column.classList.toggle('is-collapsed', open);
                syncOpenedColumnsInUrl();
            }

            closeColumnMenu();
            return;
        }

        if (action === 'toggle-selection') {
            const checks = Array.from(column.querySelectorAll('.js-project-check'));
            const selectedCount = checks.filter(input => input.checked).length;
            const shouldSelect = selectedCount === 0;

            checks.forEach(input => {
                input.checked = shouldSelect;
            });

            const master = column.querySelector('.js-select-column');
            if (master) {
                master.checked = shouldSelect && checks.length > 0;
                master.indeterminate = false;
            }

            updateBulkbar();
            closeColumnMenu();

            showToast(
                shouldSelect ? 'Se seleccionaron los proyectos de esta etapa.' : 'Se deseleccionaron los proyectos de esta etapa.',
                'success'
            );
            return;
        }

        if (action === 'archive-all' || action === 'restore-all') {
            const rows = Array.from(column.querySelectorAll('.js-project-row'));
            const isRestore = action === 'restore-all';

            if (!rows.length) {
                closeColumnMenu();
                showToast(isRestore ? 'No hay proyectos para activar en esta etapa.' : 'No hay proyectos para archivar en esta etapa.', 'info');
                return;
            }

            const confirmed = await openConfirmModal({
                title: isRestore ? 'Activar etapa' : 'Archivar etapa',
                message: isRestore
                    ? `Se activarán ${rows.length} proyecto(s) de esta etapa.`
                    : `Se archivarán ${rows.length} proyecto(s) de esta etapa. No se eliminarán.`,
                acceptText: isRestore ? 'Activar' : 'Archivar',
            });

            if (!confirmed) return;

            closeColumnMenu();

            try {
                for (const row of rows) {
                    if (isRestore) await restoreProjectRow(row);
                    else await archiveProjectRow(row);
                    removeProjectRowFromBoard(row);
                }

                showToast(isRestore ? 'Todos los proyectos de la etapa fueron activados.' : 'Todos los proyectos de la etapa fueron archivados.', 'success');
            } catch (error) {
                showToast(error.message || (isRestore ? 'No se pudieron activar todos los proyectos.' : 'No se pudieron archivar todos los proyectos.'), 'error');
            }
        }
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.js-open-column-menu') && !event.target.closest('#pjColumnMenu')) {
            closeColumnMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (activeColumnMenuAnchor && columnMenu?.classList.contains('is-open')) {
            placeFloating(columnMenu, activeColumnMenuAnchor, 8);
        }
    });

    window.addEventListener('scroll', function () {
        if (activeColumnMenuAnchor && columnMenu?.classList.contains('is-open')) {
            placeFloating(columnMenu, activeColumnMenuAnchor, 8);
        }
    }, true);

    function syncOpenedGroupsInUrl() {
        const openIds = Array.from(document.querySelectorAll('.pj-group'))
            .filter(group => group.querySelector('.pj-group-children')?.classList.contains('is-open'))
            .map(group => group.dataset.groupId)
            .filter(Boolean);

        const url = new URL(window.location.href);

        if (openIds.length) {
            url.searchParams.set('open', openIds.join(','));
        } else {
            url.searchParams.delete('open');
        }

        history.replaceState({}, '', url.toString());
    }


    function openGroupsWithVisibleResults() {
        document.querySelectorAll('.pj-group').forEach(group => {
            const hasVisibleRows = Array.from(group.querySelectorAll('.js-project-row')).some(row =>
                !row.classList.contains('is-hidden-by-label') &&
                !row.classList.contains('is-hidden-by-toolbar-filter')
            );

            if (!hasVisibleRows) return;

            const children = group.querySelector('.pj-group-children');
            const btn = group.querySelector('.js-toggle-group');

            children?.classList.add('is-open');
            btn?.setAttribute('aria-expanded', 'true');
        });

        syncOpenedGroupsInUrl();
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-toggle-group');
        if (!btn) return;

        event.preventDefault();
        event.stopPropagation();

        const group = btn.closest('.pj-group') || document.querySelector(`.pj-group[data-group-id="${btn.dataset.id}"]`);
        const children = group ? group.querySelector('.pj-group-children') : null;

        if (!children) return;

        const isOpen = children.classList.contains('is-open');

        children.classList.toggle('is-open', !isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

        syncOpenedGroupsInUrl();
    });

    document.addEventListener('click', function (event) {
        const row = event.target.closest('.pj-group-row');
        if (!row) return;

        if (event.target.closest('input, label, button, a, select, textarea, .pj-dots-btn, .pj-inline-add')) return;

        const btn = row.querySelector('.js-toggle-group');
        if (btn) btn.click();
    });


    document.querySelectorAll('.js-toggle-pop').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const target = document.getElementById(this.dataset.pop);
            document.querySelectorAll('.pj-popover').forEach(p => { if (p !== target) p.classList.remove('is-open'); });
            if (target) target.classList.toggle('is-open');
        });
    });
    document.querySelectorAll('.js-close-pop').forEach(btn => btn.addEventListener('click', () => document.querySelectorAll('.pj-popover').forEach(p => p.classList.remove('is-open'))));
    document.querySelectorAll('.js-clear-sort').forEach(btn => btn.addEventListener('click', function () {
        const pop = this.closest('.pj-popover');
        if (!pop) return;
        pop.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    }));
    document.addEventListener('click', e => {
        if (!e.target.closest('.pj-pop-wrap')) document.querySelectorAll('.pj-popover').forEach(p => p.classList.remove('is-open'));
    });


    // ============ TOOLBAR: ORDENAR / FILTROS / ARCHIVADOS ============
    const sortField = document.getElementById('pjSortField');
    const sortDirection = document.getElementById('pjSortDirection');
    const applySortBtn = document.getElementById('pjApplySort');
    const filterFavoritesInput = document.getElementById('pjFilterFavorites');
    const applyToolbarFiltersBtn = document.getElementById('pjApplyToolbarFilters');
    const clearToolbarFiltersBtn = document.getElementById('pjClearToolbarFilters');
    const clearAllToolbarFiltersBtn = document.getElementById('pjClearAllToolbarFilters');
    const filterCount = document.getElementById('pjFilterCount');

    function normalizeTextForFilter(value) {
        return String(value || '').trim().toLowerCase();
    }

    function getRowDateValue(row) {
        const text = row?.querySelector('.pj-date-value, .pj-card-date-value, .pj-date')?.textContent || row?.textContent || '';
        const match = text.match(/\d{4}-\d{2}-\d{2}/);
        return match ? match[0] : '';
    }

    function getRowPriorityValue(row) {
        const value = row?.querySelector('.js-open-priority-menu')?.dataset.priority
            || row?.querySelector('.pj-priority-badge')?.textContent
            || '';
        return normalizeTextForFilter(value);
    }

    function getRowAssignedValue(row) {
        return normalizeTextForFilter(row?.dataset.projectAssigneeId || row?.dataset.projectAssignedName || row?.dataset.projectAssigned || '');
    }

    function getActiveToolbarFilters() {
        return {
            favorites: !!filterFavoritesInput?.checked,
            assignees: Array.from(document.querySelectorAll('.js-filter-assignee:checked')).map(input => String(input.value)),
            priorities: Array.from(document.querySelectorAll('.js-filter-priority:checked')).map(input => String(input.value).toLowerCase()),
            dateFrom: document.getElementById('pjFilterDateFrom')?.value || '',
            dateTo: document.getElementById('pjFilterDateTo')?.value || '',
        };
    }

    function rowMatchesToolbarFilters(row, filters) {
        if (!row) return false;

        if (filters.favorites && !isRowFavorite(row)) return false;

        if (filters.assignees.length) {
            const assignedId = String(row.dataset.projectAssigneeId || '');
            if (!filters.assignees.includes(assignedId)) return false;
        }

        if (filters.priorities.length) {
            const priority = getRowPriorityValue(row);
            if (!filters.priorities.includes(priority)) return false;
        }

        const rowDate = getRowDateValue(row);
        if (filters.dateFrom && rowDate && rowDate < filters.dateFrom) return false;
        if (filters.dateTo && rowDate && rowDate > filters.dateTo) return false;

        return true;
    }

    function applyToolbarFilters() {
        const filters = getActiveToolbarFilters();
        let activeCount = 0;

        if (filters.favorites) activeCount++;
        activeCount += filters.assignees.length;
        activeCount += filters.priorities.length;
        if (filters.dateFrom) activeCount++;
        if (filters.dateTo) activeCount++;

        document.querySelectorAll('.js-project-row').forEach(row => {
            row.classList.toggle('is-hidden-by-toolbar-filter', !rowMatchesToolbarFilters(row, filters));
        });

        if (filterCount) filterCount.textContent = `Filtro: ${activeCount}`;

        updateColumnCountsAfterAllFilters();
        openGroupsWithVisibleResults();
        document.querySelectorAll('.pj-popover').forEach(p => p.classList.remove('is-open'));

        showToast(activeCount ? 'Filtros aplicados.' : 'Filtros limpiados.', 'success');
    }

    function clearToolbarFilters({ silent = false } = {}) {
        document.querySelectorAll('.js-filter-assignee, .js-filter-priority').forEach(input => input.checked = false);
        if (filterFavoritesInput) filterFavoritesInput.checked = false;

        const from = document.getElementById('pjFilterDateFrom');
        const to = document.getElementById('pjFilterDateTo');
        if (from) from.value = '';
        if (to) to.value = '';

        document.querySelectorAll('.js-project-row').forEach(row => row.classList.remove('is-hidden-by-toolbar-filter'));

        if (filterCount) filterCount.textContent = 'Filtro: 0';
        updateColumnCountsAfterAllFilters();

        if (!silent) showToast('Filtros limpiados.', 'success');
    }

    function getSortValue(row, field) {
        if (!row) return '';

        if (field === 'name') return normalizeTextForFilter(row.dataset.projectName || '');
        if (field === 'date') return getRowDateValue(row);
        if (field === 'priority') {
            const weights = { alta: 4, media: 3, normal: 2, baja: 1 };
            return weights[getRowPriorityValue(row)] || 0;
        }
        if (field === 'assigned') return getRowAssignedValue(row);

        return Number(row.dataset.originalIndex || 0);
    }

    function applyToolbarSort() {
        const field = sortField?.value || 'manual';
        const direction = sortDirection?.value || 'asc';
        const multiplier = direction === 'desc' ? -1 : 1;

        document.querySelectorAll('.js-project-row').forEach((row, index) => {
            if (!row.dataset.originalIndex) row.dataset.originalIndex = String(index);
        });

        document.querySelectorAll('.pj-column-body, .pj-list-body').forEach(container => {
            const rows = Array.from(container.querySelectorAll(':scope > .js-project-row'));
            rows.sort((a, b) => {
                const av = getSortValue(a, field);
                const bv = getSortValue(b, field);

                if (typeof av === 'number' || typeof bv === 'number') {
                    return ((Number(av) || 0) - (Number(bv) || 0)) * multiplier;
                }

                return String(av).localeCompare(String(bv), 'es', { numeric: true, sensitivity: 'base' }) * multiplier;
            });

            rows.forEach(row => container.appendChild(row));
        });

        document.querySelectorAll('.pj-popover').forEach(p => p.classList.remove('is-open'));
        showToast('Orden aplicado.', 'success');
    }

    function updateColumnCountsAfterAllFilters() {
        document.querySelectorAll('.pj-column').forEach(column => {
            const visibleRows = Array.from(column.querySelectorAll('.js-project-row')).filter(row =>
                !row.classList.contains('is-hidden-by-label') &&
                !row.classList.contains('is-hidden-by-toolbar-filter')
            );

            const countEl = column.querySelector('.pj-column-count');
            const collapsedCountEl = column.querySelector('.pj-collapsed-count');

            if (countEl) countEl.textContent = `(${visibleRows.length})`;
            if (collapsedCountEl) collapsedCountEl.textContent = `(${visibleRows.length})`;
        });

        document.querySelectorAll('.pj-group').forEach(group => {
            const visibleRows = Array.from(group.querySelectorAll('.js-project-row')).filter(row =>
                !row.classList.contains('is-hidden-by-label') &&
                !row.classList.contains('is-hidden-by-toolbar-filter')
            );

            const countEl = group.querySelector('.pj-group-count');
            if (countEl) countEl.textContent = `(${visibleRows.length})`;
        });
    }

    applySortBtn?.addEventListener('click', applyToolbarSort);
    applyToolbarFiltersBtn?.addEventListener('click', applyToolbarFilters);
    clearToolbarFiltersBtn?.addEventListener('click', () => clearToolbarFilters());
    function clearAllProjectFiltersAndSearch() {
        clearAllToolbarFiltersBtn?.classList.add('is-working');

        // Limpia filtros del panel Filtros
        clearToolbarFilters({ silent: true });

        // Limpia filtro por etiquetas
        applyLabelFilter('');

        // Limpia orden
        if (sortField) sortField.value = 'manual';
        if (sortDirection) sortDirection.value = 'asc';

        // Limpia busqueda visible y querystring
        const searchInput = document.querySelector('.pj-search-box input[name="q"], .pj-search-wrap input[name="q"]');
        if (searchInput) searchInput.value = '';

        const url = new URL(window.location.href);
        [
            'q',
            'label',
            'sort',
            'direction',
            'priority',
            'assignee',
            'favorite',
            'date_from',
            'date_to',
            'page'
        ].forEach(key => url.searchParams.delete(key));

        // Conserva modo archivado y vista actual
        if (new URL(window.location.href).searchParams.get('archived')) {
            url.searchParams.set('archived', '1');
        }

        if (new URL(window.location.href).searchParams.get('view')) {
            url.searchParams.set('view', new URL(window.location.href).searchParams.get('view'));
        }

        history.replaceState({}, '', url.toString());

        // Limpia clases visuales que ocultan filas
        document.querySelectorAll('.js-project-row').forEach(row => {
            row.classList.remove('is-hidden-by-label', 'is-hidden-by-toolbar-filter');
        });

        // Si hay busqueda AJAX montada, recarga la URL limpia para traer dataset completo
        window.location.href = url.toString();
    }

    clearAllToolbarFiltersBtn?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        clearAllProjectFiltersAndSearch();
    });

    // ============ NAVEGAR AL DASHBOARD AL HACER CLIC EN CUALQUIER PARTE DE LA CARD/FILA ============
    document.addEventListener('click', function (e) {
        const row = e.target.closest('.js-project-row[data-href]');
        if (!row) return;

        // Ignorar si el clic fue sobre un control interactivo / popover / drag handle
        if (e.target.closest(
            'input, label, button, a, select, textarea, [role="button"], ' +
            '.js-open-label-pop, .js-open-tag-menu, .js-open-project-menu, .js-open-column-menu, .js-column-create-project, ' +
            '.js-drag-handle, .pj-drag-btn, .pj-star-btn, .pj-icon-btn, .js-open-priority-menu, .js-open-assignee-modal, ' +
            '.pj-label-pill, .pj-label-pill-menu, .pj-tag-add, ' +
            '#pjLabelPopover, #pjTagMenu, #pjProjectMenu, #pjColumnMenu, .pj-popover, .pj-bulkbar'
        )) return;

        const href = row.dataset.href || (row.dataset.projectSlug ? `/projects/${encodeURIComponent(row.dataset.projectSlug)}` : '');
        if (!href) return;

        if (e.metaKey || e.ctrlKey || e.button === 1) {
            window.open(href, '_blank');
        } else {
            window.location.href = href;
        }
    });

    // ============ MODAL CREATE ============
    const modalBackdrop = document.getElementById('projectModalBackdrop');
    const openProjectModal = document.getElementById('openProjectModal');
    const closeModalsBtns = document.querySelectorAll('#closeProjectModal, #cancelProjectModal');
    function openModal() { modalBackdrop?.classList.add('is-open'); document.body.classList.add('pj-modal-open'); }
    function closeModal() { modalBackdrop?.classList.remove('is-open'); setTimeout(() => document.body.classList.remove('pj-modal-open'), 220); }
    if (openProjectModal) openProjectModal.addEventListener('click', openModal);
    closeModalsBtns.forEach(btn => btn?.addEventListener('click', closeModal));
    if (modalBackdrop) modalBackdrop.addEventListener('click', e => { if (e.target === modalBackdrop) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modalBackdrop?.classList.contains('is-open')) closeModal(); });

    // ============ FILE UPLOAD ============
    const form = document.getElementById('projectCreateForm');
    const inputFiles = document.getElementById('projectDocuments');
    const selectedFilesContainer = document.getElementById('projectSelectedFiles');
    const withoutDocuments = document.getElementById('withoutDocuments');
    const dropzone = document.getElementById('projectDropzone');
    const submitBtn = form?.querySelector('.pj-btn-submit');
    const uploadStatus = document.getElementById('pjUploadStatus');

    let projectFiles = [];

    function renderSelectedFiles() {
        if (!selectedFilesContainer) return;
        if (!projectFiles.length) { selectedFilesContainer.innerHTML = '<div class="pj-selected-empty">No hay archivos seleccionados</div>'; return; }
        selectedFilesContainer.innerHTML = projectFiles.map((file, i) => `
            <div class="pj-file-row">
                <div class="pj-file-name">${file.name}</div>
                <button type="button" class="pj-file-remove" data-index="${i}">Quitar</button>
            </div>
        `).join('');
    }
    function syncInputFiles() {
        if (!inputFiles) return;
        const dt = new DataTransfer();
        projectFiles.forEach(file => dt.items.add(file));
        inputFiles.files = dt.files;
    }
    function addFiles(fileList) {
        if (!fileList || !fileList.length) return;
        const allowedExt = ['pdf', 'doc', 'docx'];
        Array.from(fileList).forEach(file => {
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!allowedExt.includes(ext)) return;
            if (projectFiles.length >= 9) return;
            const exists = projectFiles.some(f => f.name === file.name && f.size === file.size);
            if (!exists) projectFiles.push(file);
        });
        syncInputFiles();
        renderSelectedFiles();
    }
    if (inputFiles) inputFiles.addEventListener('change', e => addFiles(e.target.files));
    if (dropzone) {
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('is-dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragover'));
        dropzone.addEventListener('drop', e => { e.preventDefault(); dropzone.classList.remove('is-dragover'); addFiles(e.dataTransfer.files); });
    }
    if (selectedFilesContainer) {
        selectedFilesContainer.addEventListener('click', e => {
            const btn = e.target.closest('.pj-file-remove');
            if (!btn) return;
            const i = parseInt(btn.dataset.index, 10);
            projectFiles.splice(i, 1);
            syncInputFiles();
            renderSelectedFiles();
        });
    }
    if (withoutDocuments) {
        withoutDocuments.addEventListener('change', function () {
            if (inputFiles) inputFiles.disabled = this.checked;
            if (dropzone) dropzone.style.opacity = this.checked ? '.55' : '1';
        });
    }

    // ============ SUBMIT al backend (Azure + OpenAI vía Python) ============
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const noDocs = withoutDocuments?.checked;
            if (!noDocs && projectFiles.length === 0) {
                showToast('Selecciona al menos un archivo o marca "Crear proyecto sin documentos".', 'info', 'Documento requerido');
                return;
            }

            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Procesando…'; }
            if (uploadStatus) {
                uploadStatus.style.display = 'block';
                uploadStatus.style.background = '#f0f7ff';
                uploadStatus.style.borderColor = '#b6d6ff';
                uploadStatus.style.color = '#1e40af';
                uploadStatus.textContent = noDocs
                    ? 'Creando proyecto…'
                    : 'Subiendo y procesando documentos. Esto puede tardar 30 seg – 5 min según el tamaño…';
            }

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                    credentials: 'same-origin',
                });

                const json = await res.json();

                if (!res.ok || !(json.ok || json.redirect || json.redirect_url)) {
                    if (uploadStatus) {
                        uploadStatus.style.background = '#fee2e2';
                        uploadStatus.style.borderColor = '#fecaca';
                        uploadStatus.style.color = '#b91c1c';
                        uploadStatus.textContent = json.message || 'Error al crear el proyecto.';
                    }
                    return;
                }

              if (uploadStatus) {
    uploadStatus.style.background = '#dcfce7';
    uploadStatus.style.borderColor = '#bbf7d0';
    uploadStatus.style.color = '#15803d';
    uploadStatus.textContent = noDocs
        ? '¡Listo! Abriendo el proyecto…'
        : '¡Proyecto creado! El análisis continúa en segundo plano. Abriendo el proyecto…';
}

                const target = json.redirect_url || json.redirect;
                setTimeout(() => { window.location.href = target; }, 600);
            } catch (err) {
                console.error(err);
                if (uploadStatus) {
                    uploadStatus.style.background = '#fee2e2';
                    uploadStatus.style.borderColor = '#fecaca';
                    uploadStatus.style.color = '#b91c1c';
                    uploadStatus.textContent = 'Error de red. Verifica tu conexión.';
                }
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Comenzar'; }
            }
        });
    }

    renderSelectedFiles();

    // ============ BUSQUEDA EN TIEMPO REAL ============
    const liveSearchForm = document.querySelector('[data-ajax-search]');
    const liveSearchInput = liveSearchForm?.querySelector('input[name="q"]');
    const viewTransition = document.querySelector('.pj-view-transition');
    let liveSearchTimer = null;
    let liveSearchController = null;

    async function runLiveSearch() {
        if (!liveSearchForm || !liveSearchInput || !viewTransition) return;

        const url = new URL(liveSearchForm.action, window.location.origin);
        const params = new URLSearchParams(new FormData(liveSearchForm));

        if (!params.get('q')) params.delete('q');
        params.forEach((value, key) => {
            if (value === '') params.delete(key);
        });

        url.search = params.toString();

        if (liveSearchController) liveSearchController.abort();
        liveSearchController = new AbortController();

        liveSearchInput.classList.add('is-searching');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                signal: liveSearchController.signal,
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('No se pudo buscar.');

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextView = doc.querySelector('.pj-view-transition');

            if (!nextView) throw new Error('Respuesta inválida.');

            viewTransition.innerHTML = nextView.innerHTML;
            history.replaceState({}, '', url.toString());
            closeLabelPopover();
            closeTagMenu();
            closeProjectMenu();
            updateBulkbar();
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        } finally {
            liveSearchInput.classList.remove('is-searching');
        }
    }

    liveSearchForm?.addEventListener('submit', function (event) {
        event.preventDefault();
        runLiveSearch();
    });

    liveSearchInput?.addEventListener('input', function () {
        clearTimeout(liveSearchTimer);
        liveSearchTimer = setTimeout(runLiveSearch, 280);
    });

    // ============ BULKBAR ============
    const bulkbar = document.getElementById('pjBulkbar');
    const selectedCountEl = document.getElementById('pjSelectedCount');
    const clearSelectionBtn = document.getElementById('pjClearSelection');
    const bulkFavoriteBtn = document.getElementById('pjBulkFavoriteBtn');
    function getProjectChecks() { return Array.from(document.querySelectorAll('.js-project-check')); }
    function getColumnChecks() { return Array.from(document.querySelectorAll('.js-select-column')); }
    function getProjectRows() { return Array.from(document.querySelectorAll('.js-project-row')); }

    let activeBulkAction = null;
    let activeBulkRows = [];
    let activeBulkAnchor = null;

    function getCheckedProjects() { return getProjectChecks().filter(c => c.checked); }

    function getSelectedRows() {
        return getCheckedProjects()
            .map(ch => document.querySelector(`.js-project-row[data-project-id="${ch.dataset.projectId}"]`))
            .filter(Boolean);
    }

    function isRowFavorite(row) {
        return !!row?.querySelector('.pj-star-btn')?.classList.contains('is-active');
    }

    function clearBulkContext() {
        activeBulkAction = null;
        activeBulkRows = [];
        activeBulkAnchor = null;
        initialBulkLabels = new Map();
    }

    function preloadBulkLabelsFromSelectedRows(rows) {
        pendingBulkLabels = new Map();
        initialBulkLabels = new Map();

        (rows || []).forEach(row => {
            getRowLabels(row).forEach(label => {
                const clean = normalizeLabelText(label);
                if (!clean) return;
                pendingBulkLabels.set(clean.toLowerCase(), clean);
                initialBulkLabels.set(clean.toLowerCase(), clean);
            });
        });
    }
    function updateBulkbar() {
        const projectChecks = getProjectChecks();
        const columnChecks = getColumnChecks();
        const projectRows = getProjectRows();
        const n = getCheckedProjects().length;
        if (selectedCountEl) selectedCountEl.textContent = n;

        if (bulkFavoriteBtn) {
            const selectedRows = getSelectedRows();
            const allFavorites = selectedRows.length > 0 && selectedRows.every(row => isRowFavorite(row));
            bulkFavoriteBtn.textContent = allFavorites ? 'Quitar favoritos' : 'Favoritos';
            bulkFavoriteBtn.dataset.favoriteMode = allFavorites ? 'remove' : 'add';
        }

        bulkbar?.classList.toggle('is-open', n > 0);
        bulkbar?.setAttribute('aria-hidden', n > 0 ? 'false' : 'true');
        projectRows.forEach(row => {
            const cb = document.querySelector(`.js-project-check[data-project-id="${row.dataset.projectId}"]`);
            if (cb) row.classList.toggle('is-selected', cb.checked);
        });
        columnChecks.forEach(master => {
            const colId = master.dataset.columnId;
            const children = projectChecks.filter(c => c.dataset.columnId === colId);
            const checked = children.filter(c => c.checked);
            if (!children.length) { master.checked = false; master.indeterminate = false; return; }
            if (!checked.length) { master.checked = false; master.indeterminate = false; }
            else if (checked.length === children.length) { master.checked = true; master.indeterminate = false; }
            else { master.checked = false; master.indeterminate = true; }
        });
    }

    document.addEventListener('change', function (event) {
        const projectCheck = event.target.closest('.js-project-check');
        if (projectCheck) { updateBulkbar(); return; }

        const columnCheck = event.target.closest('.js-select-column');
        if (!columnCheck) return;

        const colId = columnCheck.dataset.columnId;
        getProjectChecks()
            .filter(c => c.dataset.columnId === colId)
            .forEach(c => c.checked = columnCheck.checked);
        updateBulkbar();
    });

    if (clearSelectionBtn) clearSelectionBtn.addEventListener('click', () => {
        getProjectChecks().forEach(c => c.checked = false);
        getColumnChecks().forEach(c => { c.checked = false; c.indeterminate = false; });
        updateBulkbar();
    });

    bulkbar?.addEventListener('click', async function (event) {
        const btn = event.target.closest('.pj-bulk-action[data-bulk-action]');
        if (!btn) return;

        event.preventDefault();
        event.stopPropagation();

        const action = btn.dataset.bulkAction;
        const rows = getSelectedRows();

        if (!rows.length) {
            showToast('Selecciona al menos un proyecto para aplicar esta acción.', 'info', 'Sin selección');
            return;
        }

        activeBulkAction = action;
        activeBulkRows = rows;
        activeBulkAnchor = btn;

        closeBulkFloatingPanels();

        if (action === 'labels') {
            preloadBulkLabelsFromSelectedRows(rows);
            openLabelPopover(btn, 'bulk', true);
            return;
        }

        if (action === 'favorite') {
            const allFavorites = rows.length > 0 && rows.every(row => isRowFavorite(row));
            const nextFavorite = !allFavorites;

            try {
                for (const row of rows) {
                    const favBtn = row.querySelector('.pj-star-btn');
                    paintFavoriteButton(favBtn, nextFavorite);
                    const saved = await saveProjectFavorite(row, nextFavorite);
                    paintFavoriteButton(favBtn, saved);
                }

                showToast(
                    nextFavorite ? 'Proyectos marcados como favoritos.' : 'Proyectos quitados de favoritos.',
                    'success'
                );

                updateBulkbar();
            } catch (error) {
                showToast(error.message || 'No se pudieron actualizar los favoritos.', 'error');
            } finally {
                clearBulkContext();
            }
            return;
        }

        if (action === 'color') {
            activeProjectRow = null;
            activeProjectColorAnchor = btn;
            projectColorPopover?.classList.add('is-open');
            projectColorPopover?.setAttribute('aria-hidden', 'false');
            placeFloatingForAnchor(projectColorPopover, btn, 8);
            projectColorPopover?.classList.add('is-open');
            projectColorPopover?.setAttribute('aria-hidden', 'false');
            return;
        }

        if (action === 'stage') {
            activeProjectRow = null;
            if (stageSelect) stageSelect.value = 'analisis_bases';
            stageModalBackdrop?.classList.add('is-open', 'is-bulk-popover');
            stageModalBackdrop?.setAttribute('aria-hidden', 'false');
            setTimeout(() => {
                placeBulkModal(stageModalBackdrop?.querySelector('.pj-stage-modal'), btn, 28);
                stageSelect?.focus();
            }, 30);
            return;
        }

        if (action === 'priority') {
            activePriorityRow = null;
            activePriorityButton = null;
            priorityPopover?.classList.add('is-open');
            priorityPopover?.setAttribute('aria-hidden', 'false');
            placeFloatingForAnchor(priorityPopover, btn, 8);
            priorityPopover?.querySelectorAll('.pj-priority-option').forEach(opt => opt.classList.remove('is-active'));
            priorityPopover?.classList.add('is-open');
            priorityPopover?.setAttribute('aria-hidden', 'false');
            return;
        }

        if (action === 'assignee') {
            activeAssigneeRow = null;
            selectedAssignee = null;
            assigneeList?.querySelectorAll('.pj-assignee-option').forEach(option => option.classList.remove('is-selected'));
            assigneeBackdrop?.classList.add('is-open', 'is-bulk-popover');
            assigneeBackdrop?.setAttribute('aria-hidden', 'false');
            setTimeout(() => placeBulkModal(assigneeBackdrop?.querySelector('.pj-assignee-modal'), btn, 28), 30);
            return;
        }

        if (action === 'archive') {
            const confirmed = await openConfirmModal({
                title: '¿Archivar proyectos seleccionados?',
                message: 'Los proyectos seleccionados se moverán al archivo.',
                acceptText: 'Sí, archivar',
            });

            if (!confirmed) {
                clearBulkContext();
                return;
            }

            try {
                for (const row of rows) {
                    await archiveProjectRow(row);
                    removeProjectRowFromBoard(row);
                }
                showToast('Los proyectos seleccionados fueron archivados.', 'success');
            } catch (error) {
                showToast(error.message || 'No se pudieron archivar los proyectos.', 'error');
            } finally {
                clearBulkContext();
            }
            return;
        }

        if (action === 'delete') {
            const confirmed = await openConfirmModal({
                title: '¿Eliminar proyectos seleccionados?',
                message: 'Esta acción no se puede deshacer. Los proyectos serán eliminados permanentemente.',
                acceptText: 'Eliminar',
                variant: 'danger',
            });

            if (!confirmed) {
                clearBulkContext();
                return;
            }

            try {
                for (const row of rows) {
                    await deleteProjectRowRequest(row);
                    removeProjectRowFromBoard(row);
                }
                showToast('Los proyectos seleccionados fueron eliminados.', 'success');
            } catch (error) {
                showToast(error.message || 'No se pudieron eliminar los proyectos.', 'error');
            } finally {
                clearBulkContext();
            }
        }
    });

    updateBulkbar();

    // ============ LABEL POPOVER ============
    const labelPopover = document.getElementById('pjLabelPopover');
    const labelSearchInput = document.getElementById('pjLabelSearchInput');
    const createLabelBtn = document.getElementById('pjCreateLabelBtn');
    const createLabelText = document.getElementById('pjCreateLabelText');
    const labelOptions = document.getElementById('pjLabelOptions');
    const bulkLabelsBtn = document.getElementById('pjBulkLabelsBtn');
    const bulkLabelApplyBtn = document.getElementById('pjBulkLabelApplyBtn');
    let pendingBulkLabels = new Map();
    let initialBulkLabels = new Map();

    function closeBulkFloatingPanels(except = null) {
        const panels = [labelPopover, projectColorPopover, priorityPopover];

        panels.forEach(panel => {
            if (!panel || panel === except) return;
            panel.classList.remove('is-open', 'pj-bulk-floating');
            panel.setAttribute('aria-hidden', 'true');
        });

        if (except !== stageModalBackdrop) {
            stageModalBackdrop?.classList.remove('is-open', 'is-bulk-popover');
            stageModalBackdrop?.setAttribute('aria-hidden', 'true');
        }

        if (except !== assigneeBackdrop) {
            assigneeBackdrop?.classList.remove('is-open', 'is-bulk-popover');
            assigneeBackdrop?.setAttribute('aria-hidden', 'true');
        }
    }


    let activeLabelTarget = null;
    let labelMode = 'single';
    let availableLabels = [
        { name: 'papeleria' }, { name: '*PRUEBA*' }, { name: 'urgente' }, { name: 'licitación' }, { name: 'base' }
    ];

    document.querySelectorAll('.pj-label-pill-text').forEach(el => {
        const name = normalizeLabelText(el.textContent);
        if (name && !availableLabels.some(item => item.name.toLowerCase() === name.toLowerCase())) {
            availableLabels.push({ name });
        }
    });

    function createLabelPill(label, colorSet = {bg:'#ffebeb', border:'#ffcaca', text:'#ff4a4a'}) {
        const wrap = document.createElement('div');
        wrap.className = 'pj-label-pill js-label-pill';
        wrap.dataset.color = colorSet.bg; wrap.dataset.border = colorSet.border; wrap.dataset.text = colorSet.text;
        wrap.style.background = colorSet.bg; wrap.style.borderColor = colorSet.border; wrap.style.color = colorSet.text;
        wrap.innerHTML = `<span class="pj-label-pill-text">${label}</span><button type="button" class="pj-label-pill-menu js-open-tag-menu" aria-label="Opciones etiqueta"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg></button>`;
        return wrap;
    }
    function renderLabelOptions(filter = '') {
        const q = (filter || '').trim().toLowerCase();
        const filtered = availableLabels.filter(item => item.name.toLowerCase().includes(q));

        labelOptions.innerHTML = filtered.map(item => {
            const key = item.name.toLowerCase();
            const selected = pendingBulkLabels.has(key);
            return `
                <button type="button"
                        class="pj-label-option ${selected ? 'is-selected' : ''}"
                        data-label="${item.name}"
                        aria-checked="${selected ? 'true' : 'false'}">
                    <span class="pj-label-option-check">
                        <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>${item.name}</span>
                </button>
            `;
        }).join('');
    }
    function clampFloatingLeft(left, el) {
        const width = el?.offsetWidth || 320;
        const min = 16;
        const max = Math.max(16, window.innerWidth - width - 16);
        return Math.min(Math.max(left, min), max);
    }

    function placeFloating(el, anchor, offsetY = 8) {
        if (!el || !anchor) return;
        const rect = anchor.getBoundingClientRect();
        const top = rect.bottom + window.scrollY + offsetY;
        const left = clampFloatingLeft(rect.left + window.scrollX, el);
        el.style.top = `${top}px`;
        el.style.left = `${left}px`;
    }

    function placeFloatingAbove(el, anchor, offsetY = 12) {
        if (!el || !anchor) return;
        const rect = anchor.getBoundingClientRect();
        const height = el.offsetHeight || 260;
        const top = Math.max(16, rect.top + window.scrollY - height - offsetY);
        const left = clampFloatingLeft(rect.left + window.scrollX, el);
        el.style.top = `${top}px`;
        el.style.left = `${left}px`;
    }

    function placeBulkFloating(el, anchor, offsetY = 28) {
        if (!el || !anchor) return;

        el.classList.add('pj-bulk-floating');

        const bulkRect = bulkbar?.getBoundingClientRect();
        const anchorRect = anchor.getBoundingClientRect();

        const height = el.offsetHeight || 280;
        const width = el.offsetWidth || 340;

        const barTop = bulkRect ? bulkRect.top : window.innerHeight - 104;
        const top = Math.max(14, barTop - height - offsetY);

        let left = anchorRect.left + (anchorRect.width / 2) - (width / 2);
        left = Math.min(Math.max(left, 16), window.innerWidth - width - 16);

        el.style.top = `${top}px`;
        el.style.left = `${left}px`;
        el.style.bottom = 'auto';
    }

    function placeBulkModal(modal, anchor, offsetY = 28) {
        if (!modal || !anchor) return;

        const bulkRect = bulkbar?.getBoundingClientRect();
        const anchorRect = anchor.getBoundingClientRect();

        const height = modal.offsetHeight || 250;
        const width = modal.offsetWidth || 340;

        const barTop = bulkRect ? bulkRect.top : window.innerHeight - 104;
        const top = Math.max(14, barTop - height - offsetY);

        let left = anchorRect.left + (anchorRect.width / 2) - (width / 2);
        left = Math.min(Math.max(left, 16), window.innerWidth - width - 16);

        modal.style.top = `${top}px`;
        modal.style.left = `${left}px`;
        modal.style.bottom = 'auto';
    }

    function placeFloatingForAnchor(el, anchor, offsetY = 8) {
        if (!el || !anchor) return;

        if (anchor.closest('.pj-bulkbar')) {
            placeBulkFloating(el, anchor, 28);
            return;
        }

        el.classList.remove('pj-bulk-floating');
        placeFloating(el, anchor, offsetY);
    }
    function openLabelPopover(anchor, mode = 'single', keepPendingLabels = false) {
        activeLabelTarget = anchor;
        labelMode = mode;

        if (!keepPendingLabels) {
            pendingBulkLabels = new Map();
            initialBulkLabels = new Map();
        }

        labelPopover.classList.toggle('is-bulk-mode', mode === 'bulk');
        labelPopover.classList.toggle('pj-bulk-floating', !!anchor?.closest('.pj-bulkbar'));

        labelPopover.classList.add('is-open');
        labelPopover.setAttribute('aria-hidden', 'false');

        labelSearchInput.value = '';
        createLabelText.textContent = 'Etiqueta';

        renderLabelOptions('');
        placeFloatingForAnchor(labelPopover, anchor, 8);

        setTimeout(() => labelSearchInput.focus(), 40);
    }

    function closeLabelPopover() {
        activeLabelTarget = null;
        pendingBulkLabels = new Map();
        initialBulkLabels = new Map();
        labelPopover.classList.remove('is-open', 'pj-bulk-floating', 'is-bulk-mode');
        labelPopover.setAttribute('aria-hidden', 'true');
    }
    function normalizeLabelText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function getRowLabels(row) {
        if (!row) return [];
        return Array.from(row.querySelectorAll('.pj-label-pill-text'))
            .map(el => normalizeLabelText(el.textContent))
            .filter(Boolean)
            .filter((label, index, arr) => arr.findIndex(x => x.toLowerCase() === label.toLowerCase()) === index);
    }

    function getRowLabelStyles(row) {
        const styles = {};
        if (!row) return styles;
        row.querySelectorAll('.js-label-pill').forEach(pill => {
            const label = normalizeLabelText(pill.querySelector('.pj-label-pill-text')?.textContent || '');
            if (!label) return;
            styles[label] = {
                bg: pill.dataset.color || pill.style.backgroundColor || '#ffebeb',
                border: pill.dataset.border || pill.style.borderColor || '#ffcaca',
                text: pill.dataset.text || pill.style.color || '#ff4a4a',
            };
        });
        return styles;
    }

    function getLabelsUrl(row) {
        if (!row) return '';
        if (row.dataset.labelsUrl) return row.dataset.labelsUrl;
        if (row.dataset.projectSlug) return `/projects/${encodeURIComponent(row.dataset.projectSlug)}/labels`;
        return '';
    }

    async function saveProjectLabels(row, labels) {
        const url = getLabelsUrl(row);
        if (!row || !url) {
            throw new Error('No se encontró la ruta para guardar etiquetas del proyecto.');
        }

        const cleanLabels = (labels || [])
            .map(normalizeLabelText)
            .filter(Boolean)
            .filter((label, index, arr) => arr.findIndex(x => x.toLowerCase() === label.toLowerCase()) === index);

        row.classList.add('is-saving-labels');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ labels: cleanLabels, label_styles: getRowLabelStyles(row) }),
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok === false) {
                throw new Error(payload.message || 'No se pudieron guardar las etiquetas.');
            }

            return { ok: true, labels: Array.isArray(payload.labels) ? payload.labels : cleanLabels, label_styles: payload.label_styles || getRowLabelStyles(row) };
        } finally {
            row.classList.remove('is-saving-labels');
        }
    }

    function renderRowLabels(row, labels, labelStyles = {}) {
        if (!row) return;
        const list = row.querySelector('.js-label-list');
        if (!list) return;
        list.innerHTML = '';
        (labels || [])
            .map(normalizeLabelText)
            .filter(Boolean)
            .forEach(label => list.appendChild(createLabelPill(label, labelStyles[label] || labelStyles[label.toLowerCase()] || undefined)));
        if (typeof renderLabelFilterOptions === 'function') renderLabelFilterOptions();
        if (activeLabelFilter) applyLabelFilter(activeLabelFilter);
    }

    function addLabelPillToRow(row, label) {
        if (!row) return;
        const list = row.querySelector('.js-label-list');
        if (!list) return;

        const clean = normalizeLabelText(label);
        const already = Array.from(list.querySelectorAll('.pj-label-pill-text'))
            .some(el => normalizeLabelText(el.textContent).toLowerCase() === clean.toLowerCase());

        if (!already) list.appendChild(createLabelPill(clean));
    }

    async function applyLabelToTarget(label) {
        const clean = normalizeLabelText(label);
        if (!clean) return;

        if (!availableLabels.some(item => item.name.toLowerCase() === clean.toLowerCase())) {
            availableLabels.unshift({ name: clean });
        }

        if (labelMode === 'bulk') {
            const checked = getCheckedProjects();

            for (const ch of checked) {
                const row = document.querySelector(`.js-project-row[data-project-id="${ch.dataset.projectId}"]`);
                if (!row) continue;

                const previousLabels = getRowLabels(row);
                const nextLabels = previousLabels.some(item => item.toLowerCase() === clean.toLowerCase())
                    ? previousLabels
                    : [...previousLabels, clean];

                addLabelPillToRow(row, clean);

                try {
                    const saved = await saveProjectLabels(row, nextLabels);
                    renderRowLabels(row, saved.labels, saved.label_styles);
                } catch (error) {
                    renderRowLabels(row, previousLabels);
                    showToast(error.message || 'No se pudieron guardar las etiquetas.', 'error');
                }
            }

            return;
        }

        if (!activeLabelTarget) return;
        const row = activeLabelTarget.closest('.js-project-row');
        if (!row) return;

        const previousLabels = getRowLabels(row);
        const nextLabels = previousLabels.some(item => item.toLowerCase() === clean.toLowerCase())
            ? previousLabels
            : [...previousLabels, clean];

        addLabelPillToRow(row, clean);

        try {
            const saved = await saveProjectLabels(row, nextLabels);
            renderRowLabels(row, saved.labels, saved.label_styles);
        } catch (error) {
            renderRowLabels(row, previousLabels);
            showToast(error.message || 'No se pudieron guardar las etiquetas.', 'error');
        }
    }

    document.addEventListener('click', function (event) {
        const openLabelBtn = event.target.closest('.js-open-label-pop');
        if (!openLabelBtn) return;
        event.stopPropagation();
        openLabelPopover(openLabelBtn, 'single');
    });
    if (labelSearchInput) labelSearchInput.addEventListener('input', function () {
        const v = this.value.trim(); createLabelText.textContent = v || 'Etiqueta'; renderLabelOptions(v);
    });
    if (createLabelBtn) createLabelBtn.addEventListener('click', async function () {
        const v = (labelSearchInput.value || '').trim(); if (!v) return;
        await applyLabelToTarget(v); closeLabelPopover();
    });

    async function applyBulkLabelsFinal() {
        const rows = activeBulkRows.length ? [...activeBulkRows] : getSelectedRows();
        if (!rows.length) {
            showToast('Selecciona al menos un proyecto.', 'info', 'Sin selección');
            return;
        }

        const finalLabels = Array.from(pendingBulkLabels.values());
        const finalKeys = new Set(Array.from(pendingBulkLabels.keys()));
        const initialKeys = new Set(Array.from(initialBulkLabels.keys()));

        for (const row of rows) {
            const previousLabels = getRowLabels(row);
            const previousStyles = getRowLabelStyles(row);

            let nextLabels = previousLabels.filter(label => {
                const key = label.toLowerCase();
                return !(initialKeys.has(key) && !finalKeys.has(key));
            });

            finalLabels.forEach(label => {
                const exists = nextLabels.some(item => item.toLowerCase() === label.toLowerCase());
                if (!exists) nextLabels.push(label);
            });

            try {
                const saved = await saveProjectLabels(row, nextLabels);
                renderRowLabels(row, saved.labels, saved.label_styles);
            } catch (error) {
                renderRowLabels(row, previousLabels, previousStyles);
                showToast(error.message || 'No se pudieron guardar las etiquetas.', 'error');
                throw error;
            }
        }
    }

    if (bulkLabelApplyBtn) bulkLabelApplyBtn.addEventListener('click', async function () {
        if (labelMode !== 'bulk') return;

        try {
            await applyBulkLabelsFinal();
            closeLabelPopover();
            clearBulkContext();
            showToast('Etiquetas actualizadas.', 'success');
        } catch (error) {
            // El toast especifico ya se muestra dentro de applyBulkLabelsFinal.
        }
    });
    if (labelOptions) labelOptions.addEventListener('click', async function (e) {
        const opt = e.target.closest('.pj-label-option'); if (!opt) return;
        const label = normalizeLabelText(opt.dataset.label || '');
        if (!label) return;

        if (labelMode === 'bulk') {
            const key = label.toLowerCase();

            if (pendingBulkLabels.has(key)) {
                pendingBulkLabels.delete(key);
                opt.classList.remove('is-selected');
                opt.setAttribute('aria-checked', 'false');
            } else {
                pendingBulkLabels.set(key, label);
                opt.classList.add('is-selected');
                opt.setAttribute('aria-checked', 'true');
            }

            return;
        }

        await applyLabelToTarget(label);
        closeLabelPopover();
    });

    // ============ TAG MENU ============
    const tagMenu = document.getElementById('pjTagMenu');
    const closeTagMenuBtn = document.getElementById('pjCloseTagMenu');
    const deleteTagBtn = document.getElementById('pjDeleteTagBtn');
    let activeTagPill = null, activeTagAnchor = null;
    function openTagMenu(anchor, pill) { activeTagPill = pill; activeTagAnchor = anchor; placeFloating(tagMenu, anchor, 10); tagMenu.classList.add('is-open'); tagMenu.setAttribute('aria-hidden', 'false'); }
    function closeTagMenu() { activeTagPill = null; activeTagAnchor = null; tagMenu.classList.remove('is-open'); tagMenu.setAttribute('aria-hidden', 'true'); }
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-open-tag-menu');
        if (btn) { e.stopPropagation(); const pill = btn.closest('.js-label-pill'); if (!pill) return; openTagMenu(btn, pill); }
    });
    if (closeTagMenuBtn) closeTagMenuBtn.addEventListener('click', closeTagMenu);
    document.querySelectorAll('.pj-color-dot').forEach(dot => dot.addEventListener('click', async function () {
        if (!activeTagPill) return;
        const row = activeTagPill.closest('.js-project-row');
        const previousLabels = getRowLabels(row);
        const previousStyles = getRowLabelStyles(row);
        const bg = this.dataset.bg, border = this.dataset.border, text = this.dataset.text;

        activeTagPill.dataset.color = bg;
        activeTagPill.dataset.border = border;
        activeTagPill.dataset.text = text;
        activeTagPill.style.background = bg;
        activeTagPill.style.borderColor = border;
        activeTagPill.style.color = text;

        try {
            const saved = await saveProjectLabels(row, getRowLabels(row));
            renderRowLabels(row, saved.labels, saved.label_styles);
        } catch (error) {
            renderRowLabels(row, previousLabels, previousStyles);
            showToast(error.message || 'No se pudo guardar el color de la etiqueta.', 'error');
        }
    }));
    if (deleteTagBtn) deleteTagBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (!activeTagPill) {
            closeTagMenu();
            return;
        }

        const row = activeTagPill.closest('.js-project-row');
        if (!row) {
            closeTagMenu();
            return;
        }

        const deletedLabel = normalizeLabelText(activeTagPill.querySelector('.pj-label-pill-text')?.textContent || '');
        const previousLabels = getRowLabels(row);
        const previousStyles = getRowLabelStyles(row);

        activeTagPill.remove();

        try {
            const saved = await saveProjectLabels(row, getRowLabels(row));

            if (!clearLabelFilterIfNeeded(deletedLabel)) {
                renderRowLabels(row, saved.labels, saved.label_styles);
            } else {
                renderRowLabels(row, saved.labels, saved.label_styles);
                applyLabelFilter('');
            }
        } catch (error) {
            renderRowLabels(row, previousLabels, previousStyles);
            showToast(error.message || 'No se pudieron guardar las etiquetas.', 'error');
        }

        closeTagMenu();
    });


    // ============ FAVORITOS ==========
    function getFavoriteUrl(row) {
        if (!row) return '';
        if (row.dataset.favoriteUrl) return row.dataset.favoriteUrl;
        if (row.dataset.projectSlug) return `/projects/${encodeURIComponent(row.dataset.projectSlug)}/favorite`;
        return '';
    }

    async function saveProjectFavorite(row, favorite) {
        const url = getFavoriteUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para guardar favorito.');

        row.classList.add('is-saving-favorite');
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ favorite }),
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo guardar favorito.');
            return !!payload.favorite;
        } finally {
            row.classList.remove('is-saving-favorite');
        }
    }

    function paintFavoriteButton(button, favorite) {
        if (!button) return;
        button.classList.toggle('is-active', favorite);
        button.setAttribute('aria-pressed', favorite ? 'true' : 'false');
        const path = button.querySelector('svg path');
        if (path) path.setAttribute('fill', favorite ? 'currentColor' : 'none');
    }

    document.addEventListener('click', async function (event) {
        const btn = event.target.closest('.pj-star-btn');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();

        const row = btn.closest('.js-project-row');
        const current = btn.classList.contains('is-active');
        const next = !current;
        paintFavoriteButton(btn, next);

        try {
            const savedFavorite = await saveProjectFavorite(row, next);
            paintFavoriteButton(btn, savedFavorite);
        } catch (error) {
            paintFavoriteButton(btn, current);
            showToast(error.message || 'No se pudo guardar favorito.', 'error');
        }
    });

    // ============ FILTRO POR ETIQUETA ==========
    const labelFilterOptions = document.getElementById('pjLabelFilterOptions');
    const clearLabelFilterBtn = document.getElementById('pjClearLabelFilter');
    let activeLabelFilter = '';

    function getAllVisibleLabelsForFilter() {
        const map = new Map();
        document.querySelectorAll('.js-project-row').forEach(row => {
            getRowLabels(row).forEach(label => {
                const key = label.toLowerCase();
                if (!map.has(key)) map.set(key, { name: label, count: 0 });
                map.get(key).count += 1;
            });
        });
        return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
    }

    function renderLabelFilterOptions() {
        if (!labelFilterOptions) return;
        const labels = getAllVisibleLabelsForFilter();
        if (!labels.length) {
            labelFilterOptions.innerHTML = '<div class="pj-label-filter-empty">Sin etiquetas</div>';
            return;
        }
        labelFilterOptions.innerHTML = labels.map(item => `
            <button type="button" class="pj-label-filter-row ${activeLabelFilter.toLowerCase() === item.name.toLowerCase() ? 'is-active' : ''}" data-label="${item.name.replace(/"/g, '&quot;')}">
                <span class="pj-label-filter-dot"></span>
                <span class="pj-label-filter-name">${item.name}</span>
                <span class="pj-label-filter-count">${item.count}</span>
            </button>
        `).join('');
    }

    function updateColumnCountsAfterLabelFilter() {
        document.querySelectorAll('.pj-column').forEach(column => {
            const visibleRows = Array.from(column.querySelectorAll('.js-project-row'))
                .filter(row => !row.classList.contains('is-hidden-by-label') && !row.classList.contains('is-hidden-by-toolbar-filter'));
            const count = visibleRows.length;
            const countEl = column.querySelector('.pj-column-count');
            const collapsedCountEl = column.querySelector('.pj-collapsed-count');
            if (countEl) countEl.textContent = `(${count})`;
            if (collapsedCountEl) collapsedCountEl.textContent = `(${count})`;
        });

        document.querySelectorAll('.pj-group').forEach(group => {
            const visibleRows = Array.from(group.querySelectorAll('.js-project-row'))
                .filter(row => !row.classList.contains('is-hidden-by-label') && !row.classList.contains('is-hidden-by-toolbar-filter'));
            const countEl = group.querySelector('.pj-group-count');
            if (countEl) countEl.textContent = `(${visibleRows.length})`;
        });
    }

    function applyLabelFilter(label) {
        activeLabelFilter = normalizeLabelText(label);
        document.querySelectorAll('.js-project-row').forEach(row => {
            const labels = getRowLabels(row).map(item => item.toLowerCase());
            const match = !activeLabelFilter || labels.includes(activeLabelFilter.toLowerCase());
            row.classList.toggle('is-hidden-by-label', !match);
        });

        const url = new URL(window.location.href);
        if (activeLabelFilter) url.searchParams.set('label', activeLabelFilter);
        else url.searchParams.delete('label');
        history.replaceState({}, '', url.toString());

        updateColumnCountsAfterLabelFilter();
        if (activeLabelFilter) openGroupsWithVisibleResults();
        renderLabelFilterOptions();
    }

    function clearLabelFilterIfNeeded(labelName) {
        const removedLabel = normalizeLabelText(labelName).toLowerCase();
        const activeLabel = normalizeLabelText(activeLabelFilter).toLowerCase();

        if (removedLabel && activeLabel && removedLabel === activeLabel) {
            applyLabelFilter('');
            return true;
        }

        return false;
    }

    if (labelFilterOptions) {
        labelFilterOptions.addEventListener('click', function (event) {
            const btn = event.target.closest('.pj-label-filter-row');
            if (!btn) return;
            applyLabelFilter(btn.dataset.label || '');
        });
        renderLabelFilterOptions();
    }

    const initialLabelFromUrl = new URL(window.location.href).searchParams.get('label') || '';
    if (initialLabelFromUrl) applyLabelFilter(initialLabelFromUrl);

    if (clearLabelFilterBtn) {
        clearLabelFilterBtn.addEventListener('click', function () {
            applyLabelFilter('');
        });
    }



    // ============ PRIORIDAD ==========
    const priorityPopover = document.getElementById('pjPriorityPopover');
    let activePriorityRow = null;
    let activePriorityButton = null;

    const priorityMeta = {
        alta: { label: 'Alta', cls: 'is-high' },
        media: { label: 'Media', cls: 'is-medium' },
        baja: { label: 'Baja', cls: 'is-low' },
        normal: { label: 'Normal', cls: 'is-normal' },
    };

    function normalizePriority(value) {
        const key = String(value || '').trim().toLowerCase();
        return priorityMeta[key] ? key : 'normal';
    }

    function getPriorityUrl(row) {
        if (!row) return '';
        if (row.dataset.priorityUrl) return row.dataset.priorityUrl;
        if (row.dataset.projectSlug) return `/projects/${encodeURIComponent(row.dataset.projectSlug)}/priority`;
        return '';
    }

    function paintPriority(row, value) {
        const priority = normalizePriority(value);
        const meta = priorityMeta[priority];
        row.querySelectorAll('.js-open-priority-menu').forEach(btn => {
            btn.classList.remove('is-high', 'is-medium', 'is-low', 'is-normal');
            btn.classList.add(meta.cls);
            btn.dataset.priority = priority;
            btn.textContent = meta.label;
        });
    }

    async function saveProjectPriority(row, value) {
        const url = getPriorityUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para guardar prioridad.');
        const priority = normalizePriority(value);
        row.classList.add('is-saving-priority');
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ priority }),
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo guardar la prioridad.');
            return normalizePriority(payload.priority || priority);
        } finally {
            row.classList.remove('is-saving-priority');
        }
    }

    function openPriorityPopover(button) {
        activePriorityButton = button;
        activePriorityRow = button.closest('.js-project-row');
        if (!priorityPopover || !activePriorityRow) return;
        placeFloatingForAnchor(priorityPopover, button, 8);
        const current = normalizePriority(button.dataset.priority || button.textContent);
        priorityPopover.querySelectorAll('.pj-priority-option').forEach(opt => {
            opt.classList.toggle('is-active', normalizePriority(opt.dataset.priority) === current);
        });
        priorityPopover.classList.add('is-open');
        priorityPopover.setAttribute('aria-hidden', 'false');
    }

    function closePriorityPopover() {
        if (!priorityPopover) return;
        priorityPopover.classList.remove('is-open', 'pj-bulk-floating');
        priorityPopover.setAttribute('aria-hidden', 'true');
        activePriorityButton = null;
        activePriorityRow = null;
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-open-priority-menu');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();
        openPriorityPopover(btn);
    });

    if (priorityPopover) {
        priorityPopover.addEventListener('click', async function (event) {
            const option = event.target.closest('.pj-priority-option');
            if (!option) return;
            event.preventDefault();
            event.stopPropagation();

            const next = normalizePriority(option.dataset.priority);

            if (activeBulkAction === 'priority' && activeBulkRows.length) {
                const rows = [...activeBulkRows];
                closePriorityPopover();

                try {
                    for (const row of rows) {
                        paintPriority(row, next);
                        const saved = await saveProjectPriority(row, next);
                        paintPriority(row, saved);
                    }
                    showToast('La prioridad se actualizó en los proyectos seleccionados.', 'success');
                } catch (error) {
                    showToast(error.message || 'No se pudo actualizar la prioridad.', 'error');
                } finally {
                    clearBulkContext();
                }
                return;
            }

            if (!activePriorityRow) return;

            const row = activePriorityRow;
            const previous = normalizePriority(activePriorityButton?.dataset.priority || activePriorityButton?.textContent);
            paintPriority(row, next);
            closePriorityPopover();

            try {
                const saved = await saveProjectPriority(row, next);
                paintPriority(row, saved);
            } catch (error) {
                paintPriority(row, previous);
                showToast(error.message || 'No se pudo guardar la prioridad.', 'error');
            }
        });
    }

    // ============ ASIGNAR USUARIO ==========
    const assigneeBackdrop = document.getElementById('pjAssigneeModalBackdrop');
    const assigneeList = document.getElementById('pjAssigneeList');
    const assigneeClose = document.getElementById('pjAssigneeClose');
    const assigneeCancel = document.getElementById('pjAssigneeCancel');
    const assigneeSave = document.getElementById('pjAssigneeSave');
    let activeAssigneeRow = null;
    let selectedAssignee = null;

    function getAssigneeUrl(row) {
        if (!row) return '';
        if (row.dataset.assigneeUrl) return row.dataset.assigneeUrl;
        if (row.dataset.projectSlug) return `/projects/${encodeURIComponent(row.dataset.projectSlug)}/assignee`;
        return '';
    }

    function openAssigneeModal(button) {
        activeAssigneeRow = button.closest('.js-project-row');
        if (!activeAssigneeRow || !assigneeBackdrop) return;
        selectedAssignee = {
            user_id: activeAssigneeRow.dataset.projectAssigneeId || '',
            assigned: activeAssigneeRow.dataset.projectAssigned || '',
            name: activeAssigneeRow.dataset.projectAssignedName || '',
            email: activeAssigneeRow.dataset.projectAssignedEmail || '',
            avatar: activeAssigneeRow.dataset.projectAssignedAvatar || '',
        };
        assigneeList?.querySelectorAll('.pj-assignee-option').forEach(option => {
            const sameUser = (option.dataset.userId || '') === String(selectedAssignee.user_id || '');
            option.classList.toggle('is-selected', sameUser);
        });
        assigneeBackdrop.classList.add('is-open');
        assigneeBackdrop.setAttribute('aria-hidden', 'false');
    }

    function closeAssigneeModal() {
        assigneeBackdrop?.classList.remove('is-open', 'is-bulk-popover');
        assigneeBackdrop?.setAttribute('aria-hidden', 'true');
        activeAssigneeRow = null;
        selectedAssignee = null;
    }

    function paintAssignee(row, assignee) {
        if (!row || !assignee) return;
        row.dataset.projectAssigneeId = assignee.user_id || assignee.assigned_user_id || '';
        row.dataset.projectAssigned = assignee.assigned || '';
        row.dataset.projectAssignedName = assignee.name || '';
        row.dataset.projectAssignedEmail = assignee.email || '';
        row.dataset.projectAssignedAvatar = assignee.avatar || assignee.avatar_url || '';
        row.querySelectorAll('.pj-avatar').forEach(el => {
            const avatar = assignee.avatar || assignee.avatar_url || '';
            if (avatar) el.innerHTML = `<img src="${avatar}" alt="${assignee.name || 'Usuario'}">`;
            else el.textContent = assignee.assigned || '';
        });
        row.querySelectorAll('.pj-assigned-name').forEach(el => { el.textContent = assignee.name || assignee.assigned || 'Sin asignar'; });
    }

    async function saveProjectAssignee(row, assignee) {
        const url = getAssigneeUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para asignar usuario.');
        row.classList.add('is-saving-assignee');
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    assigned_user_id: assignee.user_id || assignee.assigned_user_id,
                }),
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.ok === false) throw new Error(payload.message || 'No se pudo asignar usuario.');
            return {
                user_id: payload.assigned_user_id || assignee.user_id,
                assigned: payload.assigned || assignee.assigned,
                name: payload.assigned_name || assignee.name,
                email: payload.assigned_email || assignee.email,
                avatar: payload.avatar_url || assignee.avatar,
            };
        } finally {
            row.classList.remove('is-saving-assignee');
        }
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-open-assignee-modal');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();
        openAssigneeModal(btn);
    });

    assigneeList?.addEventListener('click', function (event) {
        const option = event.target.closest('.pj-assignee-option');
        if (!option) return;
        selectedAssignee = {
            user_id: option.dataset.userId || '',
            assigned: option.dataset.assigned || '',
            name: option.dataset.name || '',
            email: option.dataset.email || '',
            avatar: option.dataset.avatar || '',
        };
        assigneeList.querySelectorAll('.pj-assignee-option').forEach(btn => btn.classList.remove('is-selected'));
        option.classList.add('is-selected');
    });

    assigneeSave?.addEventListener('click', async function () {
        if (!selectedAssignee) return;

        const assigneeToSave = { ...selectedAssignee };

        if (!assigneeToSave.user_id && !assigneeToSave.assigned_user_id) {
            showToast('Selecciona un usuario válido para asignar.', 'info', 'Usuario requerido');
            return;
        }

        if (activeBulkAction === 'assignee' && activeBulkRows.length) {
            const rows = [...activeBulkRows];
            closeAssigneeModal();

            try {
                for (const row of rows) {
                    paintAssignee(row, assigneeToSave);
                    const saved = await saveProjectAssignee(row, assigneeToSave);
                    paintAssignee(row, saved);
                }
                showToast('Usuario asignado a los proyectos seleccionados.', 'success');
            } catch (error) {
                showToast(error.message || 'No se pudo asignar usuario a todos los proyectos.', 'error');
            } finally {
                clearBulkContext();
            }
            return;
        }

        if (!activeAssigneeRow) return;

        const row = activeAssigneeRow;

        const previous = {
            user_id: row.dataset.projectAssigneeId || '',
            assigned: row.dataset.projectAssigned || '',
            name: row.dataset.projectAssignedName || '',
            email: row.dataset.projectAssignedEmail || '',
            avatar: row.dataset.projectAssignedAvatar || '',
        };

        paintAssignee(row, assigneeToSave);
        closeAssigneeModal();

        try {
            const saved = await saveProjectAssignee(row, assigneeToSave);
            paintAssignee(row, saved);
        } catch (error) {
            paintAssignee(row, previous);
            showToast(error.message || 'No se pudo asignar usuario.', 'error');
        }
    });

    [assigneeClose, assigneeCancel].forEach(btn => btn?.addEventListener('click', function () { closeAssigneeModal(); if (activeBulkAction === 'assignee') clearBulkContext(); }));
    assigneeBackdrop?.addEventListener('click', function (event) {
        if (event.target === assigneeBackdrop) closeAssigneeModal();
    });

    // ============ PROJECT MENU ============
    const projectMenu = document.getElementById('pjProjectMenu');
    const projectColorPopover = document.getElementById('pjProjectColorPopover');
    const stageModalBackdrop = document.getElementById('pjStageModalBackdrop');
    const stageModalClose = document.getElementById('pjStageModalClose');
    const stageModalCancel = document.getElementById('pjStageModalCancel');
    const stageModalSave = document.getElementById('pjStageModalSave');
    const stageSelect = document.getElementById('pjStageSelect');
    const projectMenuFavoriteText = document.querySelector('.pj-project-menu-favorite-text');
    let activeProjectAnchor = null;
    let activeProjectRow = null;
    let activeProjectColorAnchor = null;
    let activeProjectStageAnchor = null;

    function getProjectUpdateUrl(row) {
        if (!row) return '';
        return row.dataset.updateUrl || (row.dataset.projectSlug ? `/projects/${encodeURIComponent(row.dataset.projectSlug)}/quick-update` : '');
    }
    function getProjectArchiveUrl(row) {
        if (!row) return '';
        return row.dataset.archiveUrl || (row.dataset.projectSlug ? `/projects/${encodeURIComponent(row.dataset.projectSlug)}/archive` : '');
    }

    function getProjectRestoreUrl(row) {
        if (!row) return '';
        return row.dataset.restoreUrl || (row.dataset.projectSlug ? `/projects/${encodeURIComponent(row.dataset.projectSlug)}/restore` : '');
    }

    function getProjectDeleteUrl(row) {
        if (!row) return '';
        return row.dataset.deleteUrl || (row.dataset.projectSlug ? `/projects/${encodeURIComponent(row.dataset.projectSlug)}` : '');
    }

    function paintProjectName(row, name) {
        if (!row) return;
        row.dataset.projectName = name || '';
        row.querySelectorAll('.pj-item-title, .pj-card-title').forEach(el => { el.textContent = name || 'Proyecto'; });
        row.querySelectorAll('.js-project-check').forEach(el => { el.dataset.projectName = name || 'Proyecto'; });
        const labelAdd = row.querySelector('.js-open-label-pop');
        if (labelAdd) labelAdd.dataset.projectName = name || 'Proyecto';
    }

    function paintProjectColor(row, color) {
        if (!row || !color) return;
        row.dataset.projectColor = color;
        row.querySelectorAll('.js-project-color-dot').forEach(el => { el.style.background = color; });
    }

    async function saveProjectQuickUpdate(row, payload) {
        const url = getProjectUpdateUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para actualizar el proyecto.');
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false) throw new Error(data.message || 'No se pudo actualizar el proyecto.');
        return data;
    }

    async function archiveProjectRow(row) {
        const url = getProjectArchiveUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para archivar el proyecto.');
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false) throw new Error(data.message || 'No se pudo archivar el proyecto.');
        return data;
    }

    async function restoreProjectRow(row) {
        const url = getProjectRestoreUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para activar el proyecto.');
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false) throw new Error(data.message || 'No se pudo activar el proyecto.');
        return data;
    }

    async function deleteProjectRowRequest(row) {
        const url = getProjectDeleteUrl(row);
        if (!row || !url) throw new Error('No se encontró la ruta para eliminar el proyecto.');
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false) throw new Error(data.message || 'No se pudo eliminar el proyecto.');
        return data;
    }

    function removeProjectRowFromBoard(row) {
        if (!row) return;
        const parent = row.parentElement;
        row.remove();
        if (parent && !parent.querySelector('.js-project-row')) {
            const empty = document.createElement('div');
            empty.className = 'pj-empty';
            empty.innerHTML = '<div class="pj-empty-illustration"></div><div class="pj-empty-title">Sin proyectos</div><div class="pj-empty-text">Aquí aparecerán tus proyectos de esta etapa.</div>';
            parent.appendChild(empty);
        }
        updateBulkbar();
        updateColumnCountsAfterLabelFilter();
        renderLabelFilterOptions();
    }

    function closeProjectColorPopover() {
        activeProjectColorAnchor = null;
        projectColorPopover?.classList.remove('is-open', 'pj-bulk-floating');
        projectColorPopover?.setAttribute('aria-hidden', 'true');
    }
    function closeProjectStageModal() {
        activeProjectStageAnchor = null;
        stageModalBackdrop?.classList.remove('is-open', 'is-bulk-popover');
        stageModalBackdrop?.setAttribute('aria-hidden', 'true');
    }

    function openProjectMenu(anchor) {
        const row = anchor?.closest('.js-project-row');
        if (!row || !projectMenu) return;
        activeProjectAnchor = anchor;
        activeProjectRow = row;
        const isFavorite = row.querySelector('.pj-star-btn')?.classList.contains('is-active');
        if (projectMenuFavoriteText) projectMenuFavoriteText.textContent = isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos';
        placeFloating(projectMenu, anchor, 8);
        projectMenu.classList.add('is-open');
        projectMenu.setAttribute('aria-hidden', 'false');
    }
    function closeProjectMenu() {
        activeProjectAnchor = null;
        activeProjectRow = null;
        projectMenu?.classList.remove('is-open');
        projectMenu?.setAttribute('aria-hidden', 'true');
        closeProjectColorPopover();
        closeProjectStageModal();
    }

    function openProjectColorPopover(anchor) {
        if (!anchor || !projectColorPopover) return;
        activeProjectColorAnchor = anchor;
        projectColorPopover.classList.add('is-open');
        projectColorPopover.setAttribute('aria-hidden', 'false');
        placeFloatingForAnchor(projectColorPopover, anchor, 8);
        projectColorPopover.classList.add('is-open');
        projectColorPopover.setAttribute('aria-hidden', 'false');
    }

    function openProjectStageModal(anchor) {
        if (!anchor || !stageModalBackdrop || !activeProjectRow) return;
        activeProjectStageAnchor = anchor;
        if (stageSelect) {
            stageSelect.value = activeProjectRow.dataset.columnId || 'analisis_bases';
        }
        stageModalBackdrop.classList.add('is-open');
        stageModalBackdrop.setAttribute('aria-hidden', 'false');
        setTimeout(() => stageSelect?.focus(), 30);
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-open-project-menu');
        if (!btn) return;

        event.preventDefault();
        event.stopPropagation();

        openProjectMenu(btn);
    });

    projectMenu?.addEventListener('click', async function (event) {
        const item = event.target.closest('.pj-project-menu-item');
        if (!item || !activeProjectRow) return;
        event.preventDefault();
        event.stopPropagation();

        const row = activeProjectRow;
        const action = item.dataset.action;

        if (action === 'rename') {
            closeProjectMenu();
            const currentName = row.dataset.projectName || '';
            const promptValue = await openPromptModal({
                title: 'Cambiar nombre',
                message: 'Actualiza el nombre del proyecto.',
                value: currentName,
                acceptText: 'Guardar',
            });
            const nextName = (promptValue || '').trim();
            if (!nextName || nextName === currentName) return;
            paintProjectName(row, nextName);
            try {
                const saved = await saveProjectQuickUpdate(row, { name: nextName });
                paintProjectName(row, saved.name || nextName);
            } catch (error) {
                paintProjectName(row, currentName);
                showToast(error.message || 'No se pudo cambiar el nombre.', 'error');
            }
            return;
        }

        if (action === 'assignee') {
            closeProjectMenu();
            const btn = row.querySelector('.js-open-assignee-modal') || row;
            openAssigneeModal(btn);
            return;
        }

        if (action === 'move-stage') {
            openProjectStageModal(item);
            projectMenu?.classList.remove('is-open');
            projectMenu?.setAttribute('aria-hidden', 'true');
            return;
        }

        if (action === 'favorite') {
            closeProjectMenu();
            const favBtn = row.querySelector('.pj-star-btn');
            if (!favBtn) return;
            const current = favBtn.classList.contains('is-active');
            const next = !current;
            paintFavoriteButton(favBtn, next);
            try {
                const savedFavorite = await saveProjectFavorite(row, next);
                paintFavoriteButton(favBtn, savedFavorite);
            } catch (error) {
                paintFavoriteButton(favBtn, current);
                showToast(error.message || 'No se pudo guardar favorito.', 'error');
            }
            return;
        }

        if (action === 'labels') {
            closeProjectMenu();
            const btn = row.querySelector('.js-open-label-pop');
            if (btn) openLabelPopover(btn, 'single');
            return;
        }

        if (action === 'priority') {
            closeProjectMenu();
            const btn = row.querySelector('.js-open-priority-menu');
            if (btn) openPriorityPopover(btn, row);
            return;
        }

        if (action === 'copy-link') {
            closeProjectMenu();
            const link = row.dataset.href || `${window.location.origin}/projects/${row.dataset.projectSlug || ''}`;
            try {
                await navigator.clipboard.writeText(link);
                showToast('Link copiado al portapapeles.', 'success');
            } catch (_) {
                await openPromptModal({
                    title: 'Copiar link',
                    message: 'Copia este enlace manualmente.',
                    value: link,
                    acceptText: 'Cerrar',
                });
            }
            return;
        }

        if (action === 'color') {
            openProjectColorPopover(item);
            return;
        }

        if (action === 'archive' || action === 'restore') {
            closeProjectMenu();
            const isRestore = action === 'restore';
            const confirmed = await openConfirmModal({
                title: isRestore ? 'Activar proyecto' : 'Archivar proyecto',
                message: isRestore ? 'El proyecto volverá al tablero activo.' : 'El proyecto saldrá del tablero, pero no se eliminará.',
                acceptText: isRestore ? 'Activar' : 'Archivar',
            });
            if (!confirmed) return;
            try {
                if (isRestore) await restoreProjectRow(row);
                else await archiveProjectRow(row);
                removeProjectRowFromBoard(row);
                showToast(isRestore ? 'Proyecto activado.' : 'Proyecto archivado.', 'success');
            } catch (error) {
                showToast(error.message || (isRestore ? 'No se pudo activar el proyecto.' : 'No se pudo archivar el proyecto.'), 'error');
            }
            return;
        }

        if (action === 'delete') {
            closeProjectMenu();
            const confirmed = await openConfirmModal({
                title: 'Eliminar proyecto',
                message: 'Esta acción no se puede deshacer. El proyecto será eliminado permanentemente.',
                acceptText: 'Eliminar',
                variant: 'danger',
            });
            if (!confirmed) return;
            try {
                await deleteProjectRowRequest(row);
                removeProjectRowFromBoard(row);
            } catch (error) {
                showToast(error.message || 'No se pudo eliminar el proyecto.', 'error');
            }
        }
    });

    projectColorPopover?.addEventListener('click', async function (event) {
        const dot = event.target.closest('.pj-project-color-dot');
        if (!dot) return;

        const nextColor = dot.dataset.color || '#22c55e';

        if (activeBulkAction === 'color' && activeBulkRows.length) {
            const rows = [...activeBulkRows];
            closeProjectColorPopover();

            try {
                for (const row of rows) {
                    paintProjectColor(row, nextColor);
                    const saved = await saveProjectQuickUpdate(row, { color: nextColor });
                    paintProjectColor(row, saved.color || nextColor);
                }
                showToast('Color actualizado en los proyectos seleccionados.', 'success');
            } catch (error) {
                showToast(error.message || 'No se pudo actualizar el color.', 'error');
            } finally {
                clearBulkContext();
            }
            return;
        }

        if (!activeProjectRow) return;

        const row = activeProjectRow;
        const currentColor = row.dataset.projectColor || '#22c55e';
        paintProjectColor(row, nextColor);
        closeProjectColorPopover();
        closeProjectMenu();

        try {
            const saved = await saveProjectQuickUpdate(row, { color: nextColor });
            paintProjectColor(row, saved.color || nextColor);
        } catch (error) {
            paintProjectColor(row, currentColor);
            showToast(error.message || 'No se pudo guardar el color del proyecto.', 'error');
        }
    });

    async function saveSelectedStageFromModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!stageSelect) return;

        const stage = stageSelect.value;

        if (activeBulkAction === 'stage' && activeBulkRows.length) {
            const rows = [...activeBulkRows];

            closeProjectStageModal();

            try {
                for (const row of rows) {
                    await updateProjectWorkflow(row, stage, false);
                }
                showToast('Los proyectos seleccionados fueron movidos de etapa.', 'success');
                setTimeout(() => window.location.reload(), 450);
            } catch (error) {
                showToast(error.message || 'No se pudieron mover los proyectos.', 'error');
            } finally {
                clearBulkContext();
            }
            return;
        }

        if (!activeProjectRow) return;

        const row = activeProjectRow;

        closeProjectStageModal();
        projectMenu?.classList.remove('is-open');
        projectMenu?.setAttribute('aria-hidden', 'true');

        try {
            await updateProjectWorkflow(row, stage);
        } catch (error) {
            showToast(error.message || 'No se pudo mover el proyecto de etapa.', 'error');
        }
    }

    stageModalSave?.addEventListener('click', saveSelectedStageFromModal);

    stageSelect?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            saveSelectedStageFromModal(event);
        }
    });

    [stageModalClose, stageModalCancel].forEach(btn => {
        btn?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeProjectStageModal();
            activeProjectRow = null;
            if (activeBulkAction === 'stage') clearBulkContext();
        });
    });

    stageModalBackdrop?.addEventListener('click', function (event) {
        if (event.target === stageModalBackdrop) {
            event.preventDefault();
            event.stopPropagation();
            closeProjectStageModal();
            activeProjectRow = null;
            if (activeBulkAction === 'stage') clearBulkContext();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && stageModalBackdrop?.classList.contains('is-open')) {
            closeProjectStageModal();
            activeProjectRow = null;
            if (activeBulkAction === 'stage') clearBulkContext();
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.js-open-label-pop') && !e.target.closest('#pjLabelPopover') && !e.target.closest('#pjBulkLabelsBtn')) closeLabelPopover();
        if (!e.target.closest('.js-open-tag-menu') && !e.target.closest('#pjTagMenu')) closeTagMenu();
        if (!e.target.closest('.js-open-priority-menu') && !e.target.closest('#pjPriorityPopover')) closePriorityPopover();
        if (!e.target.closest('.js-open-project-menu') && !e.target.closest('#pjProjectMenu') && !e.target.closest('#pjProjectColorPopover') && !e.target.closest('#pjStageModalBackdrop')) closeProjectMenu();
    });

    window.addEventListener('resize', () => {
        if (activeLabelTarget && labelPopover.classList.contains('is-open')) placeFloatingForAnchor(labelPopover, activeLabelTarget, 8);
        if (activeTagAnchor && tagMenu.classList.contains('is-open')) placeFloating(tagMenu, activeTagAnchor, 10);
        if (activeProjectAnchor && projectMenu.classList.contains('is-open')) placeFloating(projectMenu, activeProjectAnchor, 8);
        if (activeProjectColorAnchor && projectColorPopover.classList.contains('is-open')) placeFloatingForAnchor(projectColorPopover, activeProjectColorAnchor, 8);
        
    });
    window.addEventListener('scroll', () => {
        if (activeLabelTarget && labelPopover.classList.contains('is-open')) placeFloatingForAnchor(labelPopover, activeLabelTarget, 8);
        if (activeTagAnchor && tagMenu.classList.contains('is-open')) placeFloating(tagMenu, activeTagAnchor, 10);
        if (activeProjectAnchor && projectMenu.classList.contains('is-open')) placeFloating(projectMenu, activeProjectAnchor, 8);
        if (activeProjectColorAnchor && projectColorPopover.classList.contains('is-open')) placeFloatingForAnchor(projectColorPopover, activeProjectColorAnchor, 8);
        
    }, true);

    // ============ DRAG & DROP + ACTUALIZAR ESTADO ============
    let draggingEl = null;
    let draggingOriginalColumnId = null;

    function getWorkflowFromDropTarget(target) {
        if (!target) return null;
        const column = target.closest('.pj-column, .pj-group');
        return column ? column.getAttribute('data-column-id') : null;
    }

    function getBestDropZone(target) {
        if (!target) return null;
        return target.closest('.js-project-row, .pj-cards, .pj-column-body, .pj-column, .pj-group-children, .pj-group');
    }

    function getDropContainerFromZone(zone) {
        if (!zone) return null;

        if (zone.classList.contains('js-project-row')) {
            return zone.parentElement;
        }

        if (zone.classList.contains('pj-cards') || zone.classList.contains('pj-group-children')) {
            return zone;
        }

        const cards = zone.querySelector('.pj-cards');
        if (cards) return cards;

        const groupChildren = zone.querySelector('.pj-group-children');
        if (groupChildren) return groupChildren;

        if (zone.classList.contains('pj-column-body')) return zone;

        return null;
    }

    async function updateProjectWorkflow(row, workflowStatus, shouldReload = true) {
        if (!row || !workflowStatus) return false;

        const url = row.dataset.workflowUrl;
        if (!url) {
            showToast('No se encontró la ruta para actualizar el estado de este proyecto.', 'error');
            if (shouldReload) window.location.reload();
            return false;
        }

        row.classList.add('is-saving-workflow');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ workflow_status: workflowStatus }),
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.ok === false) {
                throw new Error(payload.message || 'No se pudo actualizar el estado del proyecto.');
            }

            row.dataset.columnId = workflowStatus;
            if (shouldReload) window.location.reload();
            return true;
        } catch (error) {
            showToast(error.message || 'No se pudo actualizar el estado del proyecto.', 'error');
            if (shouldReload) window.location.reload();
            return false;
        } finally {
            row.classList.remove('is-saving-workflow');
        }
    }

    function moveDraggedElement(zone, event) {
        if (!draggingEl || !zone) return null;

        const targetRow = zone.classList.contains('js-project-row') ? zone : event.target.closest('.js-project-row');
        const container = getDropContainerFromZone(zone);
        if (!container) return null;

        if (targetRow && targetRow !== draggingEl && targetRow.parentElement === container) {
            const rect = targetRow.getBoundingClientRect();
            const after = (event.clientY - rect.top) > (rect.height / 2);

            if (after) {
                if (targetRow.nextSibling) container.insertBefore(draggingEl, targetRow.nextSibling);
                else container.appendChild(draggingEl);
            } else {
                container.insertBefore(draggingEl, targetRow);
            }
        } else {
            container.appendChild(draggingEl);
        }

        return container;
    }

    document.addEventListener('dragstart', function (e) {
        const row = e.target.closest('.js-project-row');
        if (!row) return;

        draggingEl = row;
        draggingOriginalColumnId = row.dataset.columnId || getWorkflowFromDropTarget(row);
        row.classList.add('is-dragging');

        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.projectId || row.dataset.projectSlug || 'project');
        }
    });

    document.addEventListener('dragend', function () {
        if (draggingEl) draggingEl.classList.remove('is-dragging');
        document.querySelectorAll('.pj-drop-target').forEach(el => el.classList.remove('pj-drop-target'));
        draggingEl = null;
        draggingOriginalColumnId = null;
    });

    document.addEventListener('dragover', function (e) {
        if (!draggingEl) return;

        const zone = getBestDropZone(e.target);
        if (!zone) return;

        const workflow = getWorkflowFromDropTarget(zone);
        if (!workflow) return;

        e.preventDefault();
        zone.classList.add('pj-drop-target');
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });

    document.addEventListener('dragleave', function (e) {
        const zone = getBestDropZone(e.target);
        if (!zone) return;

        if (!zone.contains(e.relatedTarget)) {
            zone.classList.remove('pj-drop-target');
        }
    });

    document.addEventListener('drop', async function (e) {
        if (!draggingEl) return;

        const zone = getBestDropZone(e.target);
        if (!zone) return;

        const targetWorkflow = getWorkflowFromDropTarget(zone);
        if (!targetWorkflow) return;

        e.preventDefault();
        e.stopPropagation();

        document.querySelectorAll('.pj-drop-target').forEach(el => el.classList.remove('pj-drop-target'));

        const previousWorkflow = draggingOriginalColumnId;
        moveDraggedElement(zone, e);

        if (targetWorkflow !== previousWorkflow) {
            await updateProjectWorkflow(draggingEl, targetWorkflow);
        }
    });
});
</script>
@endsection