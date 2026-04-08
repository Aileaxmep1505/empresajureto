<div class="agenda-side-shell">
    <style>
        .agenda-side-shell {
            width: 92px;
            flex: 0 0 92px;
        }

        .agenda-side-menu {
            position: sticky;
            top: 18px;
            background: #ffffff;
            border: 1px solid rgba(229, 231, 235, 0.6);
            border-radius: 28px;
            padding: 20px 10px;
            box-shadow: 0 20px 40px -8px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.02);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            min-height: calc(100vh - 120px);
            backdrop-filter: blur(10px);
        }

        /* --- ANIMACIÓN DEL CUADRO DEL LOGO --- */
        @keyframes fluid-bg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-2.5px); }
            100% { transform: translateY(0px); }
        }

        @keyframes shine {
            0% { left: -100%; }
            20% { left: 200%; }
            100% { left: 200%; }
        }

        .agenda-side-logo {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(120deg, #f8fafc, #e0e7ff, #eef2ff, #f8fafc);
            background-size: 250% 250%;
            animation: fluid-bg 6s ease-in-out infinite;
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 1), 0 4px 12px rgba(99, 102, 241, 0.12);
            color: #4f46e5; 
        }

        .agenda-side-logo svg {
            width: 24px;
            height: 24px;
            stroke: currentColor; 
            /* Un poco más grueso para que parezca un logo y no un simple icono */
            stroke-width: 2.5; 
            animation: float 3s ease-in-out infinite;
        }

        .agenda-side-logo::after {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
            transform: skewX(-20deg);
            animation: shine 4s infinite 2s;
        }

        /* --- SEPARADOR --- */
        .agenda-side-divider {
            width: 36px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 4px 0;
            border-radius: 2px;
        }

        /* --- NAVEGACIÓN Y ENLACES --- */
        .agenda-side-nav {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
            align-items: center;
        }

        .agenda-side-link {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #64748b;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .agenda-side-link:hover {
            color: #4f46e5;
            background: #f8fafc;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.1);
        }

        .agenda-side-link.is-active {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .agenda-side-link svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 2;
            transition: stroke-width 0.2s ease;
        }

        .agenda-side-link:hover svg {
            stroke-width: 2.2;
        }

        /* --- TOOLTIPS ANIMADOS --- */
        .agenda-side-link[data-tip]::after {
            content: attr(data-tip);
            position: absolute;
            left: 60px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(4px);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            padding: 8px 14px;
            border-radius: 10px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .agenda-side-link:hover::after {
            opacity: 1;
            left: 72px;
        }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 900px) {
            .agenda-side-shell { width: 100%; flex: 1 1 100%; }
            .agenda-side-menu {
                position: relative; top: 0; min-height: auto; border-radius: 22px;
                flex-direction: row; justify-content: space-between; padding: 12px 16px; margin-bottom: 14px;
            }
            .agenda-side-logo { margin-bottom: 0; flex-shrink: 0; width: 48px; height: 48px; }
            .agenda-side-nav { flex-direction: row; justify-content: flex-end; width: auto; gap: 12px; }
            .agenda-side-divider, .agenda-side-link[data-tip]::after { display: none; }
            .agenda-side-link:hover { transform: translateY(-2px) scale(1); }
        }
    </style>

    <aside class="agenda-side-menu">
        <!-- LOGO: Ícono de Capas / Organización -->
        <div class="agenda-side-logo" title="Sistema de Agenda">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 12 12 17 22 12"></polyline>
                <polyline points="2 17 12 22 22 17"></polyline>
            </svg>
        </div>

        <div class="agenda-side-divider"></div>

        <nav class="agenda-side-nav">
            <!-- BOTÓN 1: Calendario -->
            <a
                href="{{ route('agenda.calendar') }}"
                class="agenda-side-link {{ request()->routeIs('agenda.calendar') ? 'is-active' : '' }}"
                data-tip="Calendario"
                aria-label="Calendario"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="17" rx="4"></rect>
                    <path d="M8 2v4M16 2v4M3 9h18"></path>
                </svg>
            </a>

            <!-- BOTÓN 2: Resumen -->
            <a
                href="{{ route('agenda.summary') }}"
                class="agenda-side-link {{ request()->routeIs('agenda.summary') ? 'is-active' : '' }}"
                data-tip="Resumen"
                aria-label="Resumen"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 6.5h14"></path>
                    <path d="M5 12h14"></path>
                    <path d="M5 17.5h9"></path>
                </svg>
            </a>
        </nav>
    </aside>
</div>